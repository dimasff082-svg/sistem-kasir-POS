<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'method',
        'amount_tendered',
        'amount_paid',
        'change_amount',
        'status',
        'reference_number',
        'verified_by',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_tendered' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function verifiedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function methodLabel(): string
    {
        return match ($this->method) {
            'cash' => 'Tunai (Cash)',
            'transfer' => 'Transfer Bank',
            'qris' => 'QRIS',
            default => strtoupper($this->method),
        };
    }
}
