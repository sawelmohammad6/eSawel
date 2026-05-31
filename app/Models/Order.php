<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'coupon_id',
        'order_number',
        'shipping_address',
        'delivery_method',
        'tracking_number',
        'status',
        'delivery_status',
        'payment_method',
        'payment_status',
        'subtotal',
        'discount_amount',
        'shipping_amount',
        'total_amount',
        'notes',
        'admin_seen_at',
        'placed_at',
        'estimated_delivery_at',
        'delivered_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'shipping_address' => 'array',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'shipping_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'admin_seen_at' => 'datetime',
            'placed_at' => 'datetime',
            'estimated_delivery_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function pointTransactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class);
    }

    public function scopeNewForAdmin(Builder $query): Builder
    {
        return $query
            ->whereNull('admin_seen_at')
            ->where('status', 'processing')
            ->where('delivery_status', 'processing');
    }

    public function isNewForAdmin(): bool
    {
        return ! $this->admin_seen_at
            && $this->status === 'processing'
            && $this->delivery_status === 'processing';
    }

    public function purchasedWithPoints(): bool
    {
        return $this->payment_method === 'points';
    }
}
