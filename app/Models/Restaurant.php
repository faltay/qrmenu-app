<?php
// app/Models/Restaurant.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Restaurant extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, LogsActivity;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'email',
        'phone',
        'address',
        'website',
        'country',
        'city',
        'latitude',
        'longitude',
        'business_hours',
        'cuisine_type',
        'average_price',
        'primary_color',
        'secondary_color',
        'logo_path',
        'banner_path',
        'currency',
        'timezone',
        'settings',
        'is_active',
        'is_verified',
        'verified_at',
    ];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'address' => 'array',
        'business_hours' => 'array',
        'settings' => 'array',
        'average_price' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    // Relationships
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function menuCategories(): HasMany
    {
        return $this->hasMany(MenuCategory::class);
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

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    public function scopeInCountry(Builder $query, string $country): Builder
    {
        return $query->where('country', $country);
    }

    public function scopeInCity(Builder $query, string $city): Builder
    {
        return $query->where('city', $city);
    }

    public function scopeByCuisine(Builder $query, string $cuisine): Builder
    {
        return $query->where('cuisine_type', $cuisine);
    }

    // Accessors
    public function getLocalizedNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $this->name[$locale] ?? $this->name['en'] ?? 'Restaurant';
    }

    public function getLocalizedDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $this->description[$locale] ?? $this->description['en'] ?? null;
    }

    public function getLocalizedAddressAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $this->address[$locale] ?? $this->address['en'] ?? null;
    }

    public function getFormattedPriceAttribute(): string
    {
        if (!$this->average_price) {
            return '';
        }

        $symbol = match($this->currency) {
            'TRY' => '₺',
            'EUR' => '€',
            'GBP' => '£',
            default => '$',
        };

        return $symbol . number_format($this->average_price, 2);
    }

    public function getFullAddressAttribute(): string
    {
        $address = $this->localized_address;
        $city = $this->city;
        
        return trim($address . ', ' . $city);
    }

    // Media Collections
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/svg+xml']);

        $this->addMediaCollection('banner')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png']);

        $this->addMediaCollection('gallery')
            ->acceptsMimeTypes(['image/jpeg', 'image/png']);
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300)
            ->sharpen(10);

        $this->addMediaConversion('preview')
            ->width(800)
            ->height(600)
            ->quality(90);
    }

    // Media helpers
    public function getLogoUrl(): ?string
    {
        return $this->getFirstMediaUrl('logo', 'thumb') ?: null;
    }

    public function getBannerUrl(): ?string
    {
        return $this->getFirstMediaUrl('banner', 'preview') ?: null;
    }

    // Business logic
    public function isOpen(): bool
    {
        if (!$this->business_hours) {
            return true; // Default open if no hours set
        }

        $now = now($this->timezone);
        $dayOfWeek = strtolower($now->format('l')); // monday, tuesday, etc.
        
        $hours = $this->business_hours[$dayOfWeek] ?? null;
        
        if (!$hours || !$hours['is_open']) {
            return false;
        }

        $currentTime = $now->format('H:i');
        
        return $currentTime >= $hours['open'] && $currentTime <= $hours['close'];
    }

    public function getOpeningHours(string $day = null): array
    {
        if ($day) {
            return $this->business_hours[strtolower($day)] ?? [];
        }

        return $this->business_hours ?? [];
    }

    // QR Code generation
    public function generateQRCode(): QRCode
    {
        return $this->qrCodes()->create([
            'code' => 'restaurant_' . $this->slug . '_' . time(),
            'type' => 'restaurant',
            'url' => route('menu.restaurant', $this->slug),
            'is_active' => true,
        ]);
    }

    // Statistics
    public function getTotalMenuItems(): int
    {
        return $this->menuItems()->count();
    }

    public function getTotalBranches(): int
    {
        return $this->branches()->count();
    }

    public function getTotalUsers(): int
    {
        return $this->users()->count();
    }

    // Activity Logging
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'description', 'email', 'phone', 'is_active'])
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

    public function setPrimaryColorAttribute($value): void
    {
        $this->attributes['primary_color'] = str_starts_with($value, '#') ? $value : '#' . $value;
    }

    public function setSecondaryColorAttribute($value): void
    {
        $this->attributes['secondary_color'] = str_starts_with($value, '#') ? $value : '#' . $value;
    }
}