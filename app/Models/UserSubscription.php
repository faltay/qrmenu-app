<?php
// app/Models/UserSubscription.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany; 
use Carbon\Carbon;

class UserSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subscription_plan_id',
        'gateway',
        'gateway_subscription_id',
        'gateway_customer_id',
        'status',
        'amount',
        'currency',
        'trial_ends_at',
        'current_period_start',
        'current_period_end',
        'canceled_at',
        'ends_at',
        'current_restaurants',
        'current_branches',
        'current_menu_items',
        'current_users',
        'monthly_qr_scans',
        'usage_reset_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'trial_ends_at' => 'datetime',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'canceled_at' => 'datetime',
        'ends_at' => 'datetime',
        'usage_reset_at' => 'datetime',
        'metadata' => 'array',
        'current_restaurants' => 'integer',
        'current_branches' => 'integer',
        'current_menu_items' => 'integer',
        'current_users' => 'integer',
        'monthly_qr_scans' => 'integer',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeTrialing(Builder $query): Builder
    {
        return $query->where('status', 'trialing');
    }

    public function scopeCanceled(Builder $query): Builder
    {
        return $query->where('status', 'canceled');
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('ends_at', '<', now());
    }

    public function scopeForGateway(Builder $query, string $gateway): Builder
    {
        return $query->where('gateway', $gateway);
    }

    // Status checks
    public function isActive(): bool
    {
        return $this->status === 'active' && 
               (!$this->ends_at || $this->ends_at->isFuture());
    }

    public function isTrialing(): bool
    {
        return $this->status === 'trialing' && 
               $this->trial_ends_at && 
               $this->trial_ends_at->isFuture();
    }

    public function isCanceled(): bool
    {
        return !is_null($this->canceled_at);
    }

    public function hasExpired(): bool
    {
        return $this->ends_at && $this->ends_at->isPast();
    }

    public function onGracePeriod(): bool
    {
        return $this->isCanceled() && !$this->hasExpired();
    }

    // Usage limit checks
    public function canAddRestaurant(): bool
    {
        $limit = $this->subscriptionPlan->max_restaurants;
        return is_null($limit) || $this->current_restaurants < $limit;
    }

    public function canAddBranch(): bool
    {
        $limit = $this->subscriptionPlan->max_branches;
        return is_null($limit) || $this->current_branches < $limit;
    }

    public function canAddMenuItem(): bool
    {
        $limit = $this->subscriptionPlan->max_menu_items;
        return is_null($limit) || $this->current_menu_items < $limit;
    }

    public function canAddUser(): bool
    {
        $limit = $this->subscriptionPlan->max_users;
        return is_null($limit) || $this->current_users < $limit;
    }

    public function canScanQR(): bool
    {
        $limit = $this->subscriptionPlan->max_qr_scans_monthly;
        return is_null($limit) || $this->monthly_qr_scans < $limit;
    }

    // Usage management
    public function incrementUsage(string $type, int $amount = 1): void
    {
        $field = "current_{$type}";
        if (isset($this->attributes[$field])) {
            $this->increment($field, $amount);
        }
    }

    public function decrementUsage(string $type, int $amount = 1): void
    {
        $field = "current_{$type}";
        if (isset($this->attributes[$field])) {
            $this->decrement($field, $amount);
        }
    }

    public function resetMonthlyUsage(): void
    {
        $this->update([
            'monthly_qr_scans' => 0,
            'usage_reset_at' => now(),
        ]);
    }

    // Gateway methods
    public function isStripe(): bool
    {
        return $this->gateway === 'stripe';
    }

    public function isIyzico(): bool
    {
        return $this->gateway === 'iyzico';
    }

    // Period calculations
    public function daysUntilExpiration(): int
    {
        if (!$this->ends_at) {
            return -1; // Never expires
        }

        return max(0, now()->diffInDays($this->ends_at, false));
    }

    public function getRemainingTrialDaysAttribute(): int
    {
        if (!$this->trial_ends_at) {
            return 0;
        }

        return max(0, now()->diffInDays($this->trial_ends_at, false));
    }

    // Cancel subscription
    public function cancel(): void
    {
        $this->update([
            'status' => 'canceled',
            'canceled_at' => now(),
            'ends_at' => $this->current_period_end,
        ]);
    }

    // Resume subscription
    public function resume(): void
    {
        $this->update([
            'status' => 'active',
            'canceled_at' => null,
            'ends_at' => null,
        ]);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}