<?php
// app/Models/MenuItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class MenuItem extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, LogsActivity;

    protected $fillable = [
        'restaurant_id',
        'branch_id',
        'menu_category_id',
        'name',
        'slug',
        'description',
        'ingredients',
        'price',
        'cost',
        'discount_price',
        'discount_starts_at',
        'discount_ends_at',
        'calories',
        'protein',
        'carbs',
        'fat',
        'prep_time',
        'dietary_tags',
        'allergens',
        'spice_level',
        'sizes',
        'extras',
        'is_available',
        'is_featured',
        'stock_quantity',
        'available_times',
        'available_days',
        'sort_order',
        'view_count',
        'order_count',
        'rating',
        'rating_count',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'ingredients' => 'array',
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'discount_starts_at' => 'datetime',
        'discount_ends_at' => 'datetime',
        'calories' => 'integer',
        'protein' => 'decimal:2',
        'carbs' => 'decimal:2',
        'fat' => 'decimal:2',
        'prep_time' => 'integer',
        'dietary_tags' => 'array',
        'allergens' => 'array',
        'sizes' => 'array',
        'extras' => 'array',
        'is_available' => 'boolean',
        'is_featured' => 'boolean',
        'stock_quantity' => 'integer',
        'available_times' => 'array',
        'available_days' => 'array',
        'sort_order' => 'integer',
        'view_count' => 'integer',
        'order_count' => 'integer',
        'rating' => 'decimal:2',
        'rating_count' => 'integer',
        'meta_title' => 'array',
        'meta_description' => 'array',
    ];

    // Relationships
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function menuCategory(): BelongsTo
    {
        return $this->belongsTo(MenuCategory::class);
    }

    public function qrCodes(): MorphMany
    {
        return $this->morphMany(QRCode::class, 'qrcodeable');
    }

    // Scopes
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_available', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('stock_quantity')
              ->orWhere('stock_quantity', '>', 0);
        });
    }

    public function scopeOnSale(Builder $query): Builder
    {
        return $query->whereNotNull('discount_price')
                    ->where('discount_starts_at', '<=', now())
                    ->where('discount_ends_at', '>=', now());
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeForRestaurant(Builder $query, int $restaurantId): Builder
    {
        return $query->where('restaurant_id', $restaurantId);
    }

    public function scopeForBranch(Builder $query, int $branchId): Builder
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeForCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('menu_category_id', $categoryId);
    }

    public function scopePriceRange(Builder $query, float $min, float $max): Builder
    {
        return $query->whereBetween('price', [$min, $max]);
    }

    public function scopeByDietaryTag(Builder $query, string $tag): Builder
    {
        return $query->whereJsonContains('dietary_tags', $tag);
    }

    public function scopeWithoutAllergen(Builder $query, string $allergen): Builder
    {
        return $query->whereJsonDoesntContain('allergens', $allergen);
    }

    public function scopeBySpiceLevel(Builder $query, string $level): Builder
    {
        return $query->where('spice_level', $level);
    }

    public function scopePopular(Builder $query): Builder
    {
        return $query->orderByDesc('order_count');
    }

    public function scopeHighRated(Builder $query, float $minRating = 4.0): Builder
    {
        return $query->where('rating', '>=', $minRating);
    }

    // Accessors
    public function getLocalizedNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $this->name[$locale] ?? $this->name['en'] ?? 'Menu Item';
    }

    public function getLocalizedDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $this->description[$locale] ?? $this->description['en'] ?? null;
    }

    public function getLocalizedIngredientsAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $this->ingredients[$locale] ?? $this->ingredients['en'] ?? null;
    }

    public function getCurrentPriceAttribute(): float
    {
        if ($this->isOnSale()) {
            return $this->discount_price;
        }

        return $this->price;
    }

    public function getDiscountPercentageAttribute(): ?float
    {
        if (!$this->isOnSale()) {
            return null;
        }

        return round((($this->price - $this->discount_price) / $this->price) * 100, 2);
    }

    public function getFormattedPriceAttribute(): string
    {
        $currency = $this->restaurant->currency ?? 'USD';
        $symbol = match($currency) {
            'TRY' => '₺',
            'EUR' => '€',
            'GBP' => '£',
            default => '$',
        };

        $price = number_format($this->current_price, 2);
        
        if ($this->isOnSale()) {
            $originalPrice = number_format($this->price, 2);
            return "{$symbol}{$price} <s>{$symbol}{$originalPrice}</s>";
        }

        return "{$symbol}{$price}";
    }

    public function getProfitMarginAttribute(): ?float
    {
        if (!$this->cost) {
            return null;
        }

        return round((($this->current_price - $this->cost) / $this->current_price) * 100, 2);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('images', 'medium') ?: null;
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('images', 'thumb') ?: null;
    }

    public function getNutritionInfoAttribute(): array
    {
        return [
            'calories' => $this->calories,
            'protein' => $this->protein,
            'carbs' => $this->carbs,
            'fat' => $this->fat,
        ];
    }

    // Media Collections
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(200)
            ->height(200)
            ->sharpen(10);

        $this->addMediaConversion('medium')
            ->width(400)
            ->height(300)
            ->quality(90);

        $this->addMediaConversion('large')
            ->width(800)
            ->height(600)
            ->quality(85);
    }

    // Business logic
    public function isAvailableNow(): bool
    {
        if (!$this->is_available) {
            return false;
        }

        // Check stock
        if ($this->stock_quantity !== null && $this->stock_quantity <= 0) {
            return false;
        }

        // Check time restrictions
        if ($this->available_times) {
            $currentTime = now()->format('H:i');
            $start = $this->available_times['start'] ?? '00:00';
            $end = $this->available_times['end'] ?? '23:59';

            if ($currentTime < $start || $currentTime > $end) {
                return false;
            }
        }

        // Check day restrictions
        if ($this->available_days) {
            $currentDay = strtolower(now()->format('l'));
            if (!in_array($currentDay, $this->available_days)) {
                return false;
            }
        }

        return true;
    }

    public function isOnSale(): bool
    {
        if (!$this->discount_price) {
            return false;
        }

        $now = now();
        
        if ($this->discount_starts_at && $this->discount_starts_at->isFuture()) {
            return false;
        }

        if ($this->discount_ends_at && $this->discount_ends_at->isPast()) {
            return false;
        }

        return true;
    }

    public function isInStock(): bool
    {
        return $this->stock_quantity === null || $this->stock_quantity > 0;
    }

    public function hasDietaryTag(string $tag): bool
    {
        return in_array($tag, $this->dietary_tags ?? []);
    }

    public function hasAllergen(string $allergen): bool
    {
        return in_array($allergen, $this->allergens ?? []);
    }

    public function isVegetarian(): bool
    {
        return $this->hasDietaryTag('vegetarian');
    }

    public function isVegan(): bool
    {
        return $this->hasDietaryTag('vegan');
    }

    public function isGlutenFree(): bool
    {
        return $this->hasDietaryTag('gluten_free');
    }

    public function isHalal(): bool
    {
        return $this->hasDietaryTag('halal');
    }

    // Stock management
    public function decreaseStock(int $quantity = 1): void
    {
        if ($this->stock_quantity !== null) {
            $this->decrement('stock_quantity', $quantity);
        }
    }

    public function increaseStock(int $quantity = 1): void
    {
        if ($this->stock_quantity !== null) {
            $this->increment('stock_quantity', $quantity);
        }
    }

    public function setOutOfStock(): void
    {
        $this->update(['stock_quantity' => 0]);
    }

    // Analytics
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    public function incrementOrderCount(): void
    {
        $this->increment('order_count');
    }

    public function addRating(float $rating): void
    {
        $currentTotal = $this->rating * $this->rating_count;
        $newTotal = $currentTotal + $rating;
        $newCount = $this->rating_count + 1;
        
        $this->update([
            'rating' => round($newTotal / $newCount, 2),
            'rating_count' => $newCount,
        ]);
    }

    // QR Code generation
    public function generateQRCode(): QRCode
    {
        $restaurant = $this->restaurant;
        $branch = $this->branch;

        $routeParams = [$restaurant->slug];
        if ($branch) {
            $routeParams[] = $branch->slug;
        }
        $routeParams[] = $this->menuCategory->slug;
        $routeParams[] = $this->slug;

        return $this->qrCodes()->create([
            'code' => 'item_' . $restaurant->slug . '_' . ($branch ? $branch->slug . '_' : '') . $this->slug . '_' . time(),
            'type' => 'item',
            'url' => route('menu.item', $routeParams),
            'is_active' => true,
        ]);
    }

    // Activity Logging
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'price', 'discount_price', 'is_available', 'stock_quantity'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Route binding
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // Mutators
    public function setSlugAttribute($value): void
    {
        $this->attributes['slug'] = \Str::slug($value);
    }

    // Status management
    public function activate(): void
    {
        $this->update(['is_available' => true]);
    }

    public function deactivate(): void
    {
        $this->update(['is_available' => false]);
    }

    public function feature(): void
    {
        $this->update(['is_featured' => true]);
    }

    public function unfeature(): void
    {
        $this->update(['is_featured' => false]);
    }

    // Pricing
    public function setDiscount(float $discountPrice, ?\Carbon\Carbon $startDate = null, ?\Carbon\Carbon $endDate = null): void
    {
        $this->update([
            'discount_price' => $discountPrice,
            'discount_starts_at' => $startDate ?? now(),
            'discount_ends_at' => $endDate,
        ]);
    }

    public function removeDiscount(): void
    {
        $this->update([
            'discount_price' => null,
            'discount_starts_at' => null,
            'discount_ends_at' => null,
        ]);
    }
}