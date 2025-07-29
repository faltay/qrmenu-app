<?php
// app/Models/Branch.php

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

class Branch extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, LogsActivity;

    protected $fillable = [
        'restaurant_id',
        'name',
        'slug',
        'description',
        'phone',
        'address',
        'city',
        'district',
        'postal_code',
        'latitude',
        'longitude',
        'business_hours',
        'table_count',
        'capacity',
        'manager_id',
        'is_active',
        'accepts_orders',
        'opening_date',
    ];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'address' => 'array',
        'business_hours' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'table_count' => 'integer',
        'capacity' => 'integer',
        'is_active' => 'boolean',
        'accepts_orders' => 'boolean',
        'opening_date' => 'date',
    ];

    // Relationships
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function tables(): HasMany
    {
        return $this->hasMany(Table::class);
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

    public function scopeAcceptingOrders(Builder $query): Builder
    {
        return $query->where('accepts_orders', true);
    }

    public function scopeInCity(Builder $query, string $city): Builder
    {
        return $query->where('city', $city);
    }

    public function scopeInDistrict(Builder $query, string $district): Builder
    {
        return $query->where('district', $district);
    }

    public function scopeForRestaurant(Builder $query, int $restaurantId): Builder
    {
        return $query->where('restaurant_id', $restaurantId);
    }

    // Accessors
    public function getLocalizedNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $this->name[$locale] ?? $this->name['en'] ?? 'Branch';
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

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->localized_address,
            $this->district,
            $this->city,
            $this->postal_code
        ]);

        return implode(', ', $parts);
    }

    public function getDistanceFromAttribute(): ?float
    {
        // Bu method daha sonra location-based search için kullanılacak
        return null;
    }

    // Media Collections
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos')
            ->acceptsMimeTypes(['image/jpeg', 'image/png']);

        $this->addMediaCollection('documents')
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png']);
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300)
            ->sharpen(10);

        $this->addMediaConversion('medium')
            ->width(600)
            ->height(400)
            ->quality(90);
    }

    // Business logic
    public function isOpen(): bool
    {
        // Eğer branch'in kendi saatleri varsa onları kullan, yoksa restaurant'ın
        $hours = $this->business_hours ?? $this->restaurant->business_hours;
        
        if (!$hours) {
            return true;
        }

        $timezone = $this->restaurant->timezone ?? 'UTC';
        $now = now($timezone);
        $dayOfWeek = strtolower($now->format('l'));
        
        $dayHours = $hours[$dayOfWeek] ?? null;
        
        if (!$dayHours || !$dayHours['is_open']) {
            return false;
        }

        $currentTime = $now->format('H:i');
        
        return $currentTime >= $dayHours['open'] && $currentTime <= $dayHours['close'];
    }

    public function getOpeningHours(string $day = null): array
    {
        $hours = $this->business_hours ?? $this->restaurant->business_hours ?? [];
        
        if ($day) {
            return $hours[strtolower($day)] ?? [];
        }

        return $hours;
    }

    public function canAcceptOrders(): bool
    {
        return $this->is_active && $this->accepts_orders && $this->isOpen();
    }

    // Table management
    public function getAvailableTablesCount(): int
    {
        return $this->tables()->where('status', 'available')->count();
    }

    public function getOccupiedTablesCount(): int
    {
        return $this->tables()->where('status', 'occupied')->count();
    }

    public function getTableUtilization(): float
    {
        if ($this->table_count === 0) {
            return 0;
        }

        $occupied = $this->getOccupiedTablesCount();
        return round(($occupied / $this->table_count) * 100, 2);
    }

    // QR Code generation
    public function generateQRCode(): QRCode
    {
        return $this->qrCodes()->create([
            'code' => 'branch_' . $this->restaurant->slug . '_' . $this->slug . '_' . time(),
            'type' => 'branch',
            'url' => route('menu.branch', [$this->restaurant->slug, $this->slug]),
            'is_active' => true,
        ]);
    }

    // Statistics
    public function getTotalMenuItems(): int
    {
        return $this->menuItems()->count();
    }

    public function getTotalTables(): int
    {
        return $this->tables()->count();
    }

    // Distance calculation (for location-based features)
    public function distanceTo(float $lat, float $lng): float
    {
        if (!$this->latitude || !$this->longitude) {
            return PHP_FLOAT_MAX;
        }

        $earthRadius = 6371; // km

        $latDelta = deg2rad($lat - $this->latitude);
        $lngDelta = deg2rad($lng - $this->longitude);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($this->latitude)) * cos(deg2rad($lat)) *
             sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    // Activity Logging
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'phone', 'address', 'is_active', 'accepts_orders'])
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
        $this->update(['is_active' => true]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public function enableOrders(): void
    {
        $this->update(['accepts_orders' => true]);
    }

    public function disableOrders(): void
    {
        $this->update(['accepts_orders' => false]);
    }
}