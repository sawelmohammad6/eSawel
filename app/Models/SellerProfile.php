<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class SellerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shop_name',
        'slug',
        'description',
        'contact_phone',
        'contact_email',
        'logo',
        'banner',
        'commission_rate',
        'is_approved',
        'approved_at',
        'total_earnings',
        'total_paid',
        'payout_details',
    ];

    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
            'is_approved' => 'boolean',
            'approved_at' => 'datetime',
            'total_earnings' => 'decimal:2',
            'total_paid' => 'decimal:2',
            'payout_details' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
