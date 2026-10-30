<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OpenBill extends Model
{
    use HasFactory;

    protected $fillable = [
        'bill_number',
        'table_number',
        'customer_name',
        'status', // requested, open, paid, rejected
        'opened_by',
        'opened_at',
        'closed_by',
        'closed_at',
        'total_amount',
        'snap_token',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'total_amount' => 'decimal:2',
        ];
    }

    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'table_number', 'table_number');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'open_bill_id');
    }

    public function isRequested(): bool
    {
        return $this->status === 'requested';
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function recalculateTotal(): void
    {
        $this->total_amount = $this->sales()
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');
        $this->save();
    }
}
