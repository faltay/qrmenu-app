<?php
// app/Models/MenuCategory.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class MenuCategory extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, LogsActivity;

    protected $fillable = [
        'restaurant_id',
        'branch_id',
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'image_path',
        'sort_order',
        'parent_id',
        'is_active',
        'available_times',
        'available_days',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'available_times' => 'array',
        'available_days' => 'array',
        'meta_title' => 'array',
        'meta_description' => 'array',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(MenuCategory::class, 'parent_id');
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    public function qrCodes(): MorphMany
    {
        return $this->morphMany(QRCode::class, 'qrcodeable');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeRootCategories(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeSubCategories(Builder $query): Builder
    {
        return $query->whereNotNull('parent_id');
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

    public function scopeAvailableNow(Builder $query): Builder
    {
        $currentTime = now()->format('H:i');
        $currentDay = strtolower(now()->format('l'));

        return $query->where(function ($q) use ($currentTime, $currentDay) {
            // No time restrictions
            $q->whereNull('available_times')
              ->whereNull('available_days')
              // Or current time is allowed
              ->orWhere(function ($subQ) use ($currentTime, $currentDay) {
                  $subQ->whereJsonContains('available_times->start', '<=', $currentTime)
                       ->whereJsonContains('available_times->end', '>=', $currentTime)
                       ->whereJsonContains('available_days', $currentDay);
              });
        });
    }

    // Accessors
    public function getLocalizedNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $this->name[$locale] ?? $this->name['en'] ?? 'Category';
    }

    public function getLocalizedDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $this->description[$locale] ?? $this->description['en'] ?? null;
    }

    public function getLocalizedMetaTitleAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $this->meta_title[$locale] ?? $this->meta_title['en'] ?? null;
    }

    public function getLocalizedMetaDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $this->meta_description[$locale] ?? $this->meta_description['en'] ?? null;
    }

    public function getFullNameAttribute(): string
    {
        if ($this->parent) {
            return $this->parent->localized_name . ' > ' . $this->localized_name;
        }

        return $this->localized_name;
    }

    public function getIconDisplayAttribute(): string
    {
        return $this->icon ?? '🍽️';
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('images', 'thumb') ?: null;
    }

    // Media Collections
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->singleFile()
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
        // Check if category is active
        if (!$this->is_active) {
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

    public function hasSubCategories(): bool
    {
        return $this->children()->exists();
    }

    public function isSubCategory(): bool
    {
        return !is_null($this->parent_id);
    }

    public function getDepth(): int
    {
        $depth = 0;
        $category = $this;

        while ($category->parent) {
            $depth++;
            $category = $category->parent;
        }

        return $depth;
    }

    // Menu item management
    public function getActiveMenuItemsCount(): int
    {
        return $this->menuItems()->where('is_available', true)->count();
    }

    public function getAveragePriceAttribute(): float
    {
        return $this->menuItems()
                   ->where('is_available', true)
                   ->avg('price') ?? 0;
    }

    public function getCheapestItemAttribute(): ?MenuItem
    {
        return $this->menuItems()
                   ->where('is_available', true)
                   ->orderBy('price')
                   ->first();
    }

    public function getMostExpensiveItemAttribute(): ?MenuItem
    {
        return $this->menuItems()
                   ->where('is_available', true)
                   ->orderByDesc('price')
                   ->first();
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
        $routeParams[] = $this->slug;

        return $this->qrCodes()->create([
            'code' => 'category_' . $restaurant->slug . '_' . ($branch ? $branch->slug . '_' : '') . $this->slug . '_' . time(),
            'type' => 'category',
            'url' => route('menu.category', $routeParams),
            'is_active' => true,
        ]);
    }

    // Tree operations
    public function getAllParents(): \Illuminate\Support\Collection
    {
        $parents = collect();
        $category = $this->parent;

        while ($category) {
            $parents->prepend($category);
            $category = $category->parent;
        }

        return $parents;
    }

    public function getAllChildren(): \Illuminate\Support\Collection
    {
        $children = collect();

        foreach ($this->children as $child) {
            $children->push($child);
            $children = $children->merge($child->getAllChildren());
        }

        return $children;
    }

    // Activity Logging
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'description', 'is_active', 'sort_order'])
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

    public function setColorAttribute($value): void
    {
        if ($value && !str_starts_with($value, '#')) {
            $value = '#' . $value;
        }
        $this->attributes['color'] = $value;
    }

    // Status management
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
        
        // Also deactivate all children and menu items
        $this->children()->update(['is_active' => false]);
        $this->menuItems()->update(['is_available' => false]);
    }

    // Ordering
    public function moveUp(): void
    {
        $previousCategory = static::where('restaurant_id', $this->restaurant_id)
            ->where('parent_id', $this->parent_id)
            ->where('sort_order', '<', $this->sort_order)
            ->orderByDesc('sort_order')
            ->first();

        if ($previousCategory) {
            $temp = $this->sort_order;
            $this->update(['sort_order' => $previousCategory->sort_order]);
            $previousCategory->update(['sort_order' => $temp]);
        }
    }

    public function moveDown(): void
    {
        $nextCategory = static::where('restaurant_id', $this->restaurant_id)
            ->where('parent_id', $this->parent_id)
            ->where('sort_order', '>', $this->sort_order)
            ->orderBy('sort_order')
            ->first();

        if ($nextCategory) {
            $temp = $this->sort_order;
            $this->update(['sort_order' => $nextCategory->sort_order]);
            $nextCategory->update(['sort_order' => $temp]);
        }
    }
}