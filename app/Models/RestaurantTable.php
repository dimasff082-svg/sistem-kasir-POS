<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RestaurantTable extends Model
{
    use HasFactory;

    protected $fillable = [
        'table_number',
        'name',
        'status',
    ];

    public function openBills(): HasMany
    {
        return $this->hasMany(OpenBill::class, 'table_number', 'table_number');
    }

    public function activeOpenBill(): HasOne
    {
        return $this->hasOne(OpenBill::class, 'table_number', 'table_number')
            ->where('status', 'open')
            ->latestOfMany();
    }

    public function requestedOpenBill(): HasOne
    {
        return $this->hasOne(OpenBill::class, 'table_number', 'table_number')
            ->where('status', 'requested')
            ->latestOfMany();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Sale::class, 'table_number', 'table_number');
    }

    public function activeOrders(): HasMany
    {
        return $this->hasMany(Sale::class, 'table_number', 'table_number')
            ->where('payment_status', 'unpaid');
    }

    public function hasActiveOpenBill(): bool
    {
        return $this->openBills()->where('status', 'open')->exists();
    }

    public function hasRequestedOpenBill(): bool
    {
        return $this->openBills()->where('status', 'requested')->exists();
    }
}
