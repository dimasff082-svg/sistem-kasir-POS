<?php

namespace App\Services;

use App\Models\OpenBill;
use App\Models\Payment;
use App\Models\RestaurantTable;
use App\Models\Sale;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MidtransService
{
    protected string $serverKey;

    protected string $clientKey;

    protected bool $isProduction;

    protected string $snapApiUrl;

    public function __construct()
    {
        $this->serverKey = config('services.midtrans.server_key', 'SB-Mid-server-kI._QxEwcrQvcmPBFzdy2zFA');
        $this->clientKey = config('services.midtrans.client_key', 'SB-Mid-client-cclA53nEGemMIzdKixoGyyx7');
        $this->isProduction = (bool) config('services.midtrans.is_production', false);

        $this->snapApiUrl = $this->isProduction
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
    }

    /**
     * Generate Snap Token dari Midtrans untuk pembayaran pelanggan secara online (QRIS/E-Wallet/VA).
     */
    public function createSnapToken(Sale $sale): ?string
    {
        // Jika sudah ada snap_token valid, langsung gunakan
        if (! empty($sale->snap_token)) {
            return $sale->snap_token;
        }

        $sale->loadMissing(['items.product']);

        $orderId = $sale->transaction_number.'-'.time();

        $itemDetails = [];
        foreach ($sale->items as $item) {
            $itemDetails[] = [
                'id' => (string) $item->product_id,
                'price' => (int) round((float) $item->unit_price),
                'quantity' => (int) $item->quantity,
                'name' => mb_substr($item->product_name_snapshot, 0, 50),
            ];
        }

        $payload = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) round((float) $sale->total_amount),
            ],
            'customer_details' => [
                'first_name' => $sale->customer_name ?: 'Pelanggan Meja '.$sale->table_number,
            ],
            'item_details' => $itemDetails,
        ];

        try {
            $response = Http::withBasicAuth($this->serverKey, '')
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->timeout(10)
                ->post($this->snapApiUrl, $payload);

            if ($response->successful()) {
                $token = $response->json('token');
                if ($token) {
                    $sale->update(['snap_token' => $token]);

                    return $token;
                }
            }

            Log::warning('Midtrans Snap Error: '.$response->body());
        } catch (Exception $e) {
            Log::error('Midtrans Snap Request Exception: '.$e->getMessage());
        }

        return null;
    }

    /**
     * Generate Snap Token dari Midtrans untuk pelunasan seluruh tagihan akumulasi Open Bill Meja.
     */
    public function createSnapTokenForOpenBill(OpenBill $openBill): ?string
    {
        $openBill->recalculateTotal();
        $totalAmount = (int) round((float) $openBill->total_amount);

        if ($totalAmount <= 0) {
            return null;
        }

        $orderId = $openBill->bill_number.'-'.time();

        $itemDetails = [
            [
                'id' => $openBill->bill_number,
                'price' => $totalAmount,
                'quantity' => 1,
                'name' => 'Pelunasan Tagihan Meja '.$openBill->table_number,
            ],
        ];

        $payload = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $totalAmount,
            ],
            'customer_details' => [
                'first_name' => $openBill->customer_name ?: 'Pelanggan Meja '.$openBill->table_number,
            ],
            'item_details' => $itemDetails,
        ];

        try {
            $response = Http::withBasicAuth($this->serverKey, '')
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->timeout(10)
                ->post($this->snapApiUrl, $payload);

            if ($response->successful()) {
                $token = $response->json('token');
                if ($token) {
                    $openBill->update(['snap_token' => $token]);

                    return $token;
                }
            }

            Log::warning('Midtrans Snap OpenBill Error: '.$response->body());
        } catch (Exception $e) {
            Log::error('Midtrans Snap OpenBill Exception: '.$e->getMessage());
        }

        return null;
    }

    /**
     * Verifikasi & tandai transaksi sebagai Lunas setelah pembayaran online berhasil.
     */
    public function markSaleAsPaid(Sale $sale, ?string $referenceNumber = null, string $paymentMethod = 'midtrans'): void
    {
        $activeCashierId = User::getActiveCashierId();

        $sale->update([
            'payment_status' => 'paid',
            'status' => 'completed',
            'user_id' => $sale->user_id ?: $activeCashierId,
            'confirmed_by' => $sale->confirmed_by ?: $activeCashierId,
            'confirmed_at' => $sale->confirmed_at ?: now(),
        ]);

        Payment::updateOrCreate(
            ['sale_id' => $sale->id],
            [
                'method' => $paymentMethod,
                'amount_paid' => $sale->total_amount,
                'amount_tendered' => $sale->total_amount,
                'change_amount' => 0,
                'status' => 'paid',
                'reference_number' => $referenceNumber ?: $sale->transaction_number,
                'verified_by' => $sale->confirmed_by ?: $activeCashierId,
                'paid_at' => now(),
            ]
        );
    }

    /**
     * Pelunasan online seluruh tagihan akumulasi Open Bill Meja.
     */
    public function markOpenBillAsPaid(OpenBill $openBill, ?string $referenceNumber = null, string $paymentMethod = 'midtrans'): void
    {
        $openBill->loadMissing('sales');
        $activeCashierId = User::getActiveCashierId();

        foreach ($openBill->sales as $sale) {
            if ($sale->payment_status !== 'paid') {
                $sale->update([
                    'payment_status' => 'paid',
                    'status' => 'completed',
                    'user_id' => $sale->user_id ?: $activeCashierId,
                    'confirmed_by' => $sale->confirmed_by ?: $activeCashierId,
                    'confirmed_at' => $sale->confirmed_at ?: now(),
                ]);

                Payment::updateOrCreate(
                    ['sale_id' => $sale->id],
                    [
                        'method' => $paymentMethod,
                        'amount_paid' => $sale->total_amount,
                        'amount_tendered' => $sale->total_amount,
                        'change_amount' => 0,
                        'status' => 'paid',
                        'reference_number' => $referenceNumber ?: $openBill->bill_number,
                        'verified_by' => $sale->confirmed_by ?: $activeCashierId,
                        'paid_at' => now(),
                    ]
                );
            }
        }

        $openBill->update([
            'status' => 'paid',
            'closed_by' => $openBill->closed_by ?: $activeCashierId,
            'closed_at' => now(),
        ]);

        RestaurantTable::where('table_number', $openBill->table_number)->update(['status' => 'available']);
    }

    /**
     * Memproses callback Webhook HTTP Notification dari Midtrans.
     */
    public function handleNotification(array $payload): ?Sale
    {
        $orderId = $payload['order_id'] ?? null;
        $transactionStatus = $payload['transaction_status'] ?? null;
        $fraudStatus = $payload['fraud_status'] ?? null;
        $transactionId = $payload['transaction_id'] ?? null;

        if (! $orderId) {
            return null;
        }

        // Cek jika order_id berasal dari Open Bill (BILL-...)
        if (str_starts_with($orderId, 'BILL-')) {
            $parts = explode('-', $orderId);
            // BILL-YYYYMMDD-XXXX
            if (count($parts) >= 3) {
                $billNumber = $parts[0].'-'.$parts[1].'-'.$parts[2];
                $openBill = OpenBill::where('bill_number', $billNumber)->first();
                if ($openBill && ($transactionStatus === 'settlement' || ($transactionStatus === 'capture' && $fraudStatus === 'accept'))) {
                    $this->markOpenBillAsPaid($openBill, $transactionId, 'midtrans');
                }
            }

            return null;
        }

        // Format order_id: ORD001-1730... Ambil ORD001
        $transactionNumber = explode('-', $orderId)[0];

        $sale = Sale::where('transaction_number', $transactionNumber)->first();
        if (! $sale) {
            return null;
        }

        // Status sukses bayar di Midtrans
        if ($transactionStatus === 'settlement' || ($transactionStatus === 'capture' && $fraudStatus === 'accept')) {
            $this->markSaleAsPaid($sale, $transactionId, 'midtrans');
        } elseif (in_array($transactionStatus, ['cancel', 'deny', 'expire'])) {
            // Jika kedaluwarsa atau gagal online, pelanggan tetap dapat bayar tunai ke kasir
            $sale->update([
                'payment_status' => 'unpaid',
            ]);
        }

        return $sale;
    }
}
