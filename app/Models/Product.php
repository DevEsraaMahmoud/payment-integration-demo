<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'image',
        'stock',
        'category_id',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the category that owns this product
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get image URL or placeholder
     */
    public function getImageUrlAttribute(): string
    {
        if ($this->image) {
            // If image is already a full URL (http/https), return as-is
            if (filter_var($this->image, FILTER_VALIDATE_URL)) {
                return $this->image;
            }
            
            // Otherwise, treat as relative path and prepend storage
            return asset('storage/' . $this->image);
        }

        return 'https://via.placeholder.com/400x400?text=' . urlencode($this->name);
    }

    /**
     * Check if product is in stock
     */
    public function isInStock(int $quantity = 1): bool
    {
        return $this->stock >= $quantity && $this->is_active;
    }
}

