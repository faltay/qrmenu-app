<?php
// app/Models/QRCode.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use SimpleSoftwareIO\QrCode\Facades\QrCode as QrCodeGenerator;
use Illuminate\Support\Facades\Storage;

class QRCode extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, LogsActivity;

    protected $table = 'qr_codes';

    protected $fillable = [
        'code',
        'qrcodeable_type',
        'qrcodeable_id',
        'type',
        'url',
        'file_path',
        'design_options',
        'has_logo',
        'format',
        'size',
        'scan_count',
        'last_scanned_at',
        'scan_sources',
        'is_active',
        'expires_at',
        'version',
        'access_token',
        'max_scans',
    ];

    protected $casts = [
        'design_options' => 'array',
        'scan_sources' => 'array',
        'has_logo' => 'boolean',
        'size' => 'integer',
        'scan_count' => 'integer',
        'last_scanned_at' => 'datetime',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'max_scans' => 'integer',
    ];

    const TYPE_RESTAURANT = 'restaurant';
    const TYPE_BRANCH = 'branch';
    const TYPE_TABLE = 'table';
    const TYPE_CATEGORY = 'category';
    const TYPE_ITEM = 'item';

    const FORMAT_PNG = 'png';
    const FORMAT_SVG = 'svg';
    const FORMAT_PDF = 'pdf';

    // Relationships
    public function qrcodeable(): MorphTo
    {
        return $this->morphTo();
    }


    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where(function($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeForRestaurant(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_RESTAURANT);
    }

    public function scopeForBranch(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_BRANCH);
    }

    public function scopeForTable(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_TABLE);
    }

    public function scopeForCategory(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_CATEGORY);
    }

    public function scopeForItem(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_ITEM);
    }

    public function scopeRecentlyScanned(Builder $query, int $days = 7): Builder
    {
        return $query->where('last_scanned_at', '>=', now()->subDays($days));
    }

    public function scopePopular(Builder $query, int $minScans = 10): Builder
    {
        return $query->where('scan_count', '>=', $minScans);
    }

    // Accessors
    public function getQrImageUrlAttribute(): ?string
    {
        if ($this->file_path && Storage::disk('public')->exists($this->file_path)) {
            return Storage::disk('public')->url($this->file_path);
        }

        return null;
    }

    public function getDownloadUrlAttribute(): string
    {
        return route('qr.download', $this->code);
    }

    public function getScannableUrlAttribute(): string
    {
        return route('qr.scan', $this->code);
    }

    public function getTypeDisplayAttribute(): string
    {
        return match($this->type) {
            self::TYPE_RESTAURANT => 'Restaurant Menu',
            self::TYPE_BRANCH => 'Branch Menu',
            self::TYPE_TABLE => 'Table Menu',
            self::TYPE_CATEGORY => 'Category Menu',
            self::TYPE_ITEM => 'Menu Item',
            default => 'QR Code',
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        if (!$this->is_active) {
            return '🔴 Inactive';
        }

        if ($this->isExpired()) {
            return '⚠️ Expired';
        }

        if ($this->isLimitReached()) {
            return '🛑 Limit Reached';
        }

        return '🟢 Active';
    }

    public function getDesignSettingsAttribute(): array
    {
        $defaults = [
            'size' => 300,
            'margin' => 10,
            'foreground_color' => '#000000',
            'background_color' => '#FFFFFF',
            'error_correction' => 'M',
            'format' => 'png',
        ];

        return array_merge($defaults, $this->design_options ?? []);
    }

    // Status checks
    public function isActive(): bool
    {
        return $this->is_active && !$this->isExpired() && !$this->isLimitReached();
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isLimitReached(): bool
    {
        return $this->max_scans && $this->scan_count >= $this->max_scans;
    }

    public function canBeScanned(): bool
    {
        return $this->isActive();
    }

    // QR Code generation
    public function generateQRImage(): string
    {
        $settings = $this->design_settings;
        
        $qrCode = QrCodeGenerator::format($settings['format'])
            ->size($settings['size'])
            ->margin($settings['margin'])
            ->color(
                ...array_values($this->hexToRgb($settings['foreground_color']))
            )
            ->backgroundColor(
                ...array_values($this->hexToRgb($settings['background_color']))
            )
            ->errorCorrection($settings['error_correction']);

        // Add logo if enabled
        if ($this->has_logo && $this->hasLogo()) {
            $logoPath = $this->getLogoPath();
            if ($logoPath) {
                $qrCode->merge($logoPath, 0.3, true);
            }
        }

        $qrContent = $qrCode->generate($this->scannable_url);
        
        // Save to storage
        $filename = "qr_codes/{$this->code}.{$settings['format']}";
        Storage::disk('public')->put($filename, $qrContent);
        
        $this->update(['file_path' => $filename]);
        
        return $filename;
    }

    public function regenerateQRImage(): string
    {
        // Delete old file
        if ($this->file_path) {
            Storage::disk('public')->delete($this->file_path);
        }

        return $this->generateQRImage();
    }

    // Logo management
    public function hasLogo(): bool
    {
        return $this->getFirstMedia('logo') !== null;
    }

    public function getLogoPath(): ?string
    {
        $logo = $this->getFirstMedia('logo');
        return $logo ? $logo->getPath() : null;
    }

    public function getLogoUrl(): ?string
    {
        return $this->getFirstMediaUrl('logo', 'qr_logo');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->singleFile()
            ->acceptsMimeTypes(['image/png', 'image/jpeg', 'image/svg+xml']);
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('qr_logo')
            ->width(60)
            ->height(60)
            ->sharpen(10);
    }

    public function scans(): HasMany
    {
        return $this->hasMany(QRCodeScan::class, 'qr_code_id'); // ← Foreign key belirtin
    }

    public function recordScan(array $scanData = []): QRCodeScan
    {
        $this->increment('scan_count');
        $this->update(['last_scanned_at' => now()]);

        // Update scan sources
        $sources = $this->scan_sources ?? [];
        $deviceType = $scanData['device_type'] ?? 'unknown';
        $sources[$deviceType] = ($sources[$deviceType] ?? 0) + 1;
        $this->update(['scan_sources' => $sources]);

        return $this->scans()->create($scanData);
    }

    // Analytics
    public function getScansToday(): int
    {
        return $this->scans()->whereDate('scanned_at', today())->count();
    }

    public function getScansThisWeek(): int
    {
        return $this->scans()->whereBetween('scanned_at', [
            now()->startOfWeek(), 
            now()->endOfWeek()
        ])->count();
    }

    public function getScansThisMonth(): int
    {
        return $this->scans()->whereMonth('scanned_at', now()->month)
                            ->whereYear('scanned_at', now()->year)
                            ->count();
    }

    public function getUniqueScansCount(): int
    {
        return $this->scans()->where('is_unique_visitor', true)->count();
    }

    public function getTopScanSources(): array
    {
        return $this->scan_sources ?? [];
    }

    public function getAverageScansPerDay(): float
    {
        $firstScan = $this->scans()->oldest('scanned_at')->first();
        
        if (!$firstScan) {
            return 0;
        }

        $daysSinceFirstScan = $firstScan->scanned_at->diffInDays(now()) + 1;
        
        return round($this->scan_count / $daysSinceFirstScan, 2);
    }

    // Customization
    public function updateDesign(array $options): void
    {
        $this->update([
            'design_options' => array_merge($this->design_options ?? [], $options),
        ]);

        $this->regenerateQRImage();
    }

    public function setLogo(string $logoPath): void
    {
        $this->addMediaFromUrl($logoPath)
            ->toMediaCollection('logo');
            
        $this->update(['has_logo' => true]);
        $this->regenerateQRImage();
    }

    public function removeLogo(): void
    {
        $this->clearMediaCollection('logo');
        $this->update(['has_logo' => false]);
        $this->regenerateQRImage();
    }

    // Utility methods
    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        
        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    // Activity Logging
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['type', 'url', 'is_active', 'scan_count'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Route binding
    public function getRouteKeyName(): string
    {
        return 'code';
    }

    // Static methods
    public static function generateUniqueCode(string $prefix = ''): string
    {
        do {
            $code = $prefix . \Str::random(12);
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public static function getValidTypes(): array
    {
        return [
            self::TYPE_RESTAURANT,
            self::TYPE_BRANCH,
            self::TYPE_TABLE,
            self::TYPE_CATEGORY,
            self::TYPE_ITEM,
        ];
    }

    public static function getValidFormats(): array
    {
        return [
            self::FORMAT_PNG,
            self::FORMAT_SVG,
            self::FORMAT_PDF,
        ];
    }

    // Bulk operations
    public static function deactivateExpired(): int
    {
        return static::where('expires_at', '<', now())
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }

    public static function cleanupOldFiles(int $days = 30): int
    {
        $oldQrCodes = static::where('created_at', '<', now()->subDays($days))
            ->where('is_active', false)
            ->get();

        $deletedCount = 0;

        foreach ($oldQrCodes as $qrCode) {
            if ($qrCode->file_path) {
                Storage::disk('public')->delete($qrCode->file_path);
                $deletedCount++;
            }
        }

        return $deletedCount;
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

    public function extend(int $days): void
    {
        $expiresAt = $this->expires_at ? $this->expires_at->addDays($days) : now()->addDays($days);
        $this->update(['expires_at' => $expiresAt]);
    }

    public function setExpiration(?\Carbon\Carbon $date): void
    {
        $this->update(['expires_at' => $date]);
    }
}