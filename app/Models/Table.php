<?php
// app/Models/Table.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Carbon\Carbon;

class Table extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'restaurant_id',
        'branch_id',
        'number',
        'name',
        'description',
        'capacity',
        'shape',
        'location',
        'position',
        'features',
        'is_smoking_allowed',
        'is_outdoor',
        'is_private',
        'is_active',
        'status',
        'status_updated_at',
        'accepts_reservations',
        'min_reservation_duration',
        'max_reservation_duration',
        'service_charge',
        'minimum_order',
    ];

    protected $casts = [
        'description' => 'array',
        'position' => 'array',
        'features' => 'array',
        'capacity' => 'integer',
        'is_smoking_allowed' => 'boolean',
        'is_outdoor' => 'boolean',
        'is_private' => 'boolean',
        'is_active' => 'boolean',
        'status_updated_at' => 'datetime',
        'accepts_reservations' => 'boolean',
        'min_reservation_duration' => 'integer',
        'max_reservation_duration' => 'integer',
        'service_charge' => 'decimal:2',
        'minimum_order' => 'decimal:2',
    ];

    const STATUS_AVAILABLE = 'available';
    const STATUS_OCCUPIED = 'occupied';
    const STATUS_RESERVED = 'reserved';
    const STATUS_CLEANING = 'cleaning';
    const STATUS_OUT_OF_ORDER = 'out_of_order';

    const SHAPE_ROUND = 'round';
    const SHAPE_SQUARE = 'square';
    const SHAPE_RECTANGLE = 'rectangle';
    const SHAPE_OVAL = 'oval';

    // Relationships
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
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

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    public function scopeOccupied(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OCCUPIED);
    }

    public function scopeReserved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_RESERVED);
    }

    public function scopeOutOfOrder(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OUT_OF_ORDER);
    }

    public function scopeForCapacity(Builder $query, int $capacity): Builder
    {
        return $query->where('capacity', '>=', $capacity);
    }

    public function scopeIndoor(Builder $query): Builder
    {
        return $query->where('is_outdoor', false);
    }

    public function scopeOutdoor(Builder $query): Builder
    {
        return $query->where('is_outdoor', true);
    }

    public function scopeSmokingAllowed(Builder $query): Builder
    {
        return $query->where('is_smoking_allowed', true);
    }

    public function scopeNonSmoking(Builder $query): Builder
    {
        return $query->where('is_smoking_allowed', false);
    }

    public function scopePrivate(Builder $query): Builder
    {
        return $query->where('is_private', true);
    }

    public function scopeAcceptingReservations(Builder $query): Builder
    {
        return $query->where('accepts_reservations', true);
    }

    public function scopeForBranch(Builder $query, int $branchId): Builder
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByLocation(Builder $query, string $location): Builder
    {
        return $query->where('location', $location);
    }

    // Accessors
    public function getLocalizedDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $this->description[$locale] ?? $this->description['en'] ?? null;
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->name) {
            return $this->name;
        }

        return "Table {$this->number}";
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            self::STATUS_AVAILABLE => '🟢',
            self::STATUS_OCCUPIED => '🔴',
            self::STATUS_RESERVED => '🟡',
            self::STATUS_CLEANING => '🧹',
            self::STATUS_OUT_OF_ORDER => '⚠️',
            default => '❓',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_AVAILABLE => 'success',
            self::STATUS_OCCUPIED => 'danger',
            self::STATUS_RESERVED => 'warning',
            self::STATUS_CLEANING => 'info',
            self::STATUS_OUT_OF_ORDER => 'secondary',
            default => 'light',
        };
    }

    public function getFormattedServiceChargeAttribute(): string
    {
        if (!$this->service_charge) {
            return '';
        }

        return number_format($this->service_charge, 2) . '%';
    }

    public function getFormattedMinimumOrderAttribute(): string
    {
        if (!$this->minimum_order) {
            return '';
        }

        $currency = $this->restaurant->currency ?? 'USD';
        $symbol = match($currency) {
            'TRY' => '₺',
            'EUR' => '€',
            'GBP' => '£',
            default => '$',
        };

        return $symbol . number_format($this->minimum_order, 2);
    }

    public function getFeaturesListAttribute(): array
    {
        return $this->features ?? [];
    }

    public function getPositionCoordinatesAttribute(): array
    {
        return [
            'x' => $this->position['x'] ?? 0,
            'y' => $this->position['y'] ?? 0,
        ];
    }

    // Status checks
    public function isAvailable(): bool
    {
        return $this->is_active && $this->status === self::STATUS_AVAILABLE;
    }

    public function isOccupied(): bool
    {
        return $this->status === self::STATUS_OCCUPIED;
    }

    public function isReserved(): bool
    {
        return $this->status === self::STATUS_RESERVED;
    }

    public function isOutOfOrder(): bool
    {
        return $this->status === self::STATUS_OUT_OF_ORDER;
    }

    public function isCleaning(): bool
    {
        return $this->status === self::STATUS_CLEANING;
    }

    // Feature checks
    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features_list);
    }

    public function hasWindowView(): bool
    {
        return $this->hasFeature('window_view');
    }

    public function hasPowerOutlet(): bool
    {
        return $this->hasFeature('power_outlet');
    }

    public function isWheelchairAccessible(): bool
    {
        return $this->hasFeature('wheelchair_accessible');
    }

    public function hasHighChair(): bool
    {
        return $this->hasFeature('high_chair');
    }

    // Status management
    public function markAsAvailable(): void
    {
        $this->updateStatus(self::STATUS_AVAILABLE);
    }

    public function markAsOccupied(): void
    {
        $this->updateStatus(self::STATUS_OCCUPIED);
    }

    public function markAsReserved(): void
    {
        $this->updateStatus(self::STATUS_RESERVED);
    }

    public function markAsCleaning(): void
    {
        $this->updateStatus(self::STATUS_CLEANING);
    }

    public function markAsOutOfOrder(): void
    {
        $this->updateStatus(self::STATUS_OUT_OF_ORDER);
    }

    private function updateStatus(string $status): void
    {
        $this->update([
            'status' => $status,
            'status_updated_at' => now(),
        ]);
    }

    // Reservation checks
    public function canBeReserved(): bool
    {
        return $this->is_active && 
               $this->accepts_reservations && 
               $this->isAvailable();
    }

    public function canAccommodate(int $partySize): bool
    {
        return $this->capacity >= $partySize;
    }

    public function getReservationDurationRange(): array
    {
        return [
            'min' => $this->min_reservation_duration,
            'max' => $this->max_reservation_duration,
        ];
    }

    // QR Code generation
    public function generateQRCode(): QRCode
    {
        $restaurant = $this->restaurant;
        $branch = $this->branch;

        return $this->qrCodes()->create([
            'code' => 'table_' . $restaurant->slug . '_' . $branch->slug . '_' . $this->number . '_' . time(),
            'type' => 'table',
            'url' => route('menu.table', [$restaurant->slug, $branch->slug, $this->number]),
            'is_active' => true,
        ]);
    }

    public function getQRCodeUrl(): ?string
    {
        $qrCode = $this->qrCodes()->where('is_active', true)->first();
        return $qrCode?->url;
    }

    // Analytics
    public function getUtilizationToday(): float
    {
        // Bu method daha sonra reservation/order data ile implement edilecek
        // Şimdilik mock data
        return 0.0;
    }

    public function getAverageOccupancyTime(): int
    {
        // Average time table is occupied (minutes)
        // Mock data - gerçek implementation reservation/order data gerektirir
        return 90;
    }

    public function getTodayReservationCount(): int
    {
        // Mock data - gerçek reservation system ile implement edilecek
        return 0;
    }

    // Floor plan management
    public function moveToPosition(int $x, int $y): void
    {
        $this->update([
            'position' => ['x' => $x, 'y' => $y],
        ]);
    }

    public function getDistanceFrom(int $x, int $y): float
    {
        $coords = $this->position_coordinates;
        $deltaX = $coords['x'] - $x;
        $deltaY = $coords['y'] - $y;
        
        return sqrt(($deltaX * $deltaX) + ($deltaY * $deltaY));
    }

    // Activity Logging
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['number', 'name', 'capacity', 'status', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Route binding
    public function getRouteKeyName(): string
    {
        return 'number';
    }

    // Validation
    public static function getValidStatuses(): array
    {
        return [
            self::STATUS_AVAILABLE,
            self::STATUS_OCCUPIED,
            self::STATUS_RESERVED,
            self::STATUS_CLEANING,
            self::STATUS_OUT_OF_ORDER,
        ];
    }

    public static function getValidShapes(): array
    {
        return [
            self::SHAPE_ROUND,
            self::SHAPE_SQUARE,
            self::SHAPE_RECTANGLE,
            self::SHAPE_OVAL,
        ];
    }

    public static function getCommonFeatures(): array
    {
        return [
            'window_view',
            'power_outlet',
            'wheelchair_accessible',
            'high_chair',
            'booth_seating',
            'bar_height',
            'quiet_area',
            'near_kitchen',
            'near_entrance',
            'corner_table',
        ];
    }

    // Bulk operations
    public static function bulkUpdateStatus(array $tableIds, string $status): int
    {
        return static::whereIn('id', $tableIds)->update([
            'status' => $status,
            'status_updated_at' => now(),
        ]);
    }

    public static function getAvailableTablesForCapacity(int $branchId, int $capacity): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('branch_id', $branchId)
                    ->active()
                    ->available()
                    ->forCapacity($capacity)
                    ->orderBy('capacity')
                    ->get();
    }
}