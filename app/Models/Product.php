<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'category_id',
        'brand_id',
        'name',
        'slug',
        'sku',
        'short_description',
        'description',
        'specifications',
        'attributes',
        'base_price',
        'sale_price',
        'stock_quantity',
        'low_stock_threshold',
        'weight',
        'is_featured',
        'is_trending',
        'is_flash_deal',
        'flash_starts_at',
        'flash_ends_at',
        'status',
        'approval_status',
        'approved_at',
    ];

    protected $appends = [
        'effective_price',
        'average_rating',
    ];

    protected function casts(): array
    {
        return [
            'specifications' => 'array',
            'attributes' => 'array',
            'base_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'weight' => 'decimal:2',
            'is_featured' => 'boolean',
            'is_trending' => 'boolean',
            'is_flash_deal' => 'boolean',
            'flash_starts_at' => 'datetime',
            'flash_ends_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->where('approval_status', 'approved');
    }

    public function getEffectivePriceAttribute(): float
    {
        return (float) ($this->sale_price ?: $this->base_price);
    }

    public function getAverageRatingAttribute(): float
    {
        return round((float) $this->reviews()->avg('rating'), 1);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
