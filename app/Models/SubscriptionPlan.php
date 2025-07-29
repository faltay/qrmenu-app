<?php
// app/Models/SubscriptionPlan.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'price_usd',
        'price_try',
        'price_eur',
        'billing_period',
        'max_restaurants',
        'max_branches',
        'max_menu_items',
        'max_users',
        'max_qr_scans_monthly',
        'features',
        'stripe_price_id',
        'iyzico_plan_code',
        'is_popular',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'features' => 'array',
        'price_usd' => 'decimal:2',
        'price_try' => 'decimal:2',
        'price_eur' => 'decimal:2',
        'is_popular' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Relationships
    public function userSubscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('price_usd');
    }

    public function scopePopular(Builder $query): Builder
    {
        return $query->where('is_popular', true);
    }

    public function scopeMonthly(Builder $query): Builder
    {
        return $query->where('billing_period', 'monthly');
    }

    public function scopeYearly(Builder $query): Builder
    {
        return $query->where('billing_period', 'yearly');
    }

    // Accessors
    public function getLocalizedNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $this->name[$locale] ?? $this->name['en'] ?? 'Unknown Plan';
    }

    public function getLocalizedDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $this->description[$locale] ?? $this->description['en'] ?? null;
    }

    public function getPriceAttribute(): string
    {
        $currency = config('app.currency', 'USD');
        
        return match($currency) {
            'TRY' => number_format($this->price_try, 2) . ' ₺',
            'EUR' => number_format($this->price_eur, 2) . ' €',
            default => number_format($this->price_usd, 2) . ' $',
        };
    }

    // Methods
    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }

    public function isUnlimited(string $limit): bool
    {
        $field = "max_{$limit}";
        return is_null($this->$field);
    }

    public function getLimit(string $limit): ?int
    {
        $field = "max_{$limit}";
        return $this->$field;
    }
}