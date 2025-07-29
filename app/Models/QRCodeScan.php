<?php
// app/Models/QRCodeScan.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class QRCodeScan extends Model
{
    use HasFactory;

    protected $table = 'qr_code_scans';

    protected $fillable = [
        'qr_code_id',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'os',
        'country',
        'city',
        'referrer',
        'scanned_at',
        'is_unique_visitor',
        'session_id',
        'duration_on_site',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
        'is_unique_visitor' => 'boolean',
        'duration_on_site' => 'integer',
    ];

    const DEVICE_MOBILE = 'mobile';
    const DEVICE_DESKTOP = 'desktop';
    const DEVICE_TABLET = 'tablet';
    const DEVICE_UNKNOWN = 'unknown';

    // Relationships
    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(QRCode::class, 'qr_code_id'); // ← Foreign key belirtin
    }

    // Scopes
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('scanned_at', today());
    }

    public function scopeThisWeek(Builder $query): Builder
    {
        return $query->whereBetween('scanned_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereMonth('scanned_at', now()->month)
                    ->whereYear('scanned_at', now()->year);
    }

    public function scopeLastDays(Builder $query, int $days): Builder
    {
        return $query->where('scanned_at', '>=', now()->subDays($days));
    }

    public function scopeUniqueVisitors(Builder $query): Builder
    {
        return $query->where('is_unique_visitor', true);
    }

    public function scopeByDeviceType(Builder $query, string $deviceType): Builder
    {
        return $query->where('device_type', $deviceType);
    }

    public function scopeByCountry(Builder $query, string $country): Builder
    {
        return $query->where('country', $country);
    }

    public function scopeByCity(Builder $query, string $city): Builder
    {
        return $query->where('city', $city);
    }

    public function scopeMobile(Builder $query): Builder
    {
        return $query->where('device_type', self::DEVICE_MOBILE);
    }

    public function scopeDesktop(Builder $query): Builder
    {
        return $query->where('device_type', self::DEVICE_DESKTOP);
    }

    public function scopeTablet(Builder $query): Builder
    {
        return $query->where('device_type', self::DEVICE_TABLET);
    }

    public function scopeFromReferrer(Builder $query, string $referrer): Builder
    {
        return $query->where('referrer', 'LIKE', "%{$referrer}%");
    }

    public function scopeBetweenHours(Builder $query, int $startHour, int $endHour): Builder
    {
        return $query->whereRaw('HOUR(scanned_at) BETWEEN ? AND ?', [$startHour, $endHour]);
    }

    // Accessors
    public function getDeviceIconAttribute(): string
    {
        return match($this->device_type) {
            self::DEVICE_MOBILE => '📱',
            self::DEVICE_DESKTOP => '🖥️',
            self::DEVICE_TABLET => '📟',
            default => '❓',
        };
    }

    public function getCountryFlagAttribute(): string
    {
        // Bu method ileride country code'a göre flag emoji return edecek
        return match($this->country) {
            'US' => '🇺🇸',
            'TR' => '🇹🇷',
            'GB' => '🇬🇧',
            'DE' => '🇩🇪',
            'FR' => '🇫🇷',
            'CA' => '🇨🇦',
            default => '🌍',
        };
    }

    public function getBrowserNameAttribute(): string
    {
        if (!$this->browser) {
            return 'Unknown';
        }

        // Extract browser name from user agent
        if (str_contains($this->browser, 'Chrome')) return 'Chrome';
        if (str_contains($this->browser, 'Firefox')) return 'Firefox';
        if (str_contains($this->browser, 'Safari')) return 'Safari';
        if (str_contains($this->browser, 'Edge')) return 'Edge';
        if (str_contains($this->browser, 'Opera')) return 'Opera';
        
        return $this->browser;
    }

    public function getTimeOfDayAttribute(): string
    {
        $hour = $this->scanned_at->format('H');
        
        return match(true) {
            $hour >= 5 && $hour < 12 => 'Morning',
            $hour >= 12 && $hour < 17 => 'Afternoon', 
            $hour >= 17 && $hour < 21 => 'Evening',
            default => 'Night'
        };
    }

    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration_on_site) {
            return 'N/A';
        }

        $minutes = floor($this->duration_on_site / 60);
        $seconds = $this->duration_on_site % 60;

        if ($minutes > 0) {
            return "{$minutes}m {$seconds}s";
        }

        return "{$seconds}s";
    }

    public function getLocationDisplayAttribute(): string
    {
        $parts = array_filter([$this->city, $this->country]);
        return implode(', ', $parts) ?: 'Unknown';
    }

    // Static factory methods
    public static function createFromRequest($request, QRCode $qrCode): self
    {
        $userAgent = $request->userAgent();
        $ipAddress = $request->ip();

        return static::create([
            'qr_code_id' => $qrCode->id,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'device_type' => static::detectDeviceType($userAgent),
            'browser' => static::detectBrowser($userAgent),
            'os' => static::detectOS($userAgent),
            'country' => static::detectCountry($ipAddress),
            'city' => static::detectCity($ipAddress),
            'referrer' => $request->header('referer'),
            'scanned_at' => now(),
            'is_unique_visitor' => static::isUniqueVisitor($ipAddress, $qrCode->id),
            'session_id' => $request->session()->getId(),
        ]);
    }

    // Device detection
    public static function detectDeviceType(string $userAgent): string
    {
        $userAgent = strtolower($userAgent);

        if (preg_match('/mobile|android|iphone|ipod|blackberry|windows phone/i', $userAgent)) {
            return self::DEVICE_MOBILE;
        }

        if (preg_match('/tablet|ipad|kindle|silk/i', $userAgent)) {
            return self::DEVICE_TABLET;
        }

        if (preg_match('/desktop|windows|macintosh|linux/i', $userAgent)) {
            return self::DEVICE_DESKTOP;
        }

        return self::DEVICE_UNKNOWN;
    }

    public static function detectBrowser(string $userAgent): string
    {
        if (preg_match('/Chrome\/([0-9.]+)/i', $userAgent, $matches)) {
            return 'Chrome ' . $matches[1];
        }

        if (preg_match('/Firefox\/([0-9.]+)/i', $userAgent, $matches)) {
            return 'Firefox ' . $matches[1];
        }

        if (preg_match('/Safari\/([0-9.]+)/i', $userAgent, $matches)) {
            return 'Safari ' . $matches[1];
        }

        if (preg_match('/Edge\/([0-9.]+)/i', $userAgent, $matches)) {
            return 'Edge ' . $matches[1];
        }

        return 'Unknown';
    }

    public static function detectOS(string $userAgent): string
    {
        if (preg_match('/Windows NT ([0-9.]+)/i', $userAgent, $matches)) {
            return 'Windows ' . $matches[1];
        }

        if (preg_match('/Mac OS X ([0-9_]+)/i', $userAgent, $matches)) {
            return 'macOS ' . str_replace('_', '.', $matches[1]);
        }

        if (preg_match('/Android ([0-9.]+)/i', $userAgent, $matches)) {
            return 'Android ' . $matches[1];
        }

        if (preg_match('/iPhone OS ([0-9_]+)/i', $userAgent, $matches)) {
            return 'iOS ' . str_replace('_', '.', $matches[1]);
        }

        if (preg_match('/Linux/i', $userAgent)) {
            return 'Linux';
        }

        return 'Unknown';
    }

    // Geo-location detection (mock implementation)
    public static function detectCountry(string $ipAddress): ?string
    {
        // Mock implementation - gerçek projenizde GeoIP service kullanın
        $mockCountries = ['US', 'TR', 'GB', 'DE', 'FR', 'CA', 'AU', 'JP'];
        return $mockCountries[array_rand($mockCountries)];
    }

    public static function detectCity(string $ipAddress): ?string
    {
        // Mock implementation - gerçek projenizde GeoIP service kullanın
        $mockCities = ['New York', 'Istanbul', 'London', 'Berlin', 'Paris', 'Toronto', 'Sydney', 'Tokyo'];
        return $mockCities[array_rand($mockCities)];
    }

    // Unique visitor detection
    public static function isUniqueVisitor(string $ipAddress, int $qrCodeId): bool
    {
        return !static::where('ip_address', $ipAddress)
                     ->where('qr_code_id', $qrCodeId)
                     ->where('scanned_at', '>=', now()->subDays(30))
                     ->exists();
    }

    // Analytics methods
    public static function getDeviceTypeStats(int $qrCodeId, int $days = 30): array
    {
        return static::where('qr_code_id', $qrCodeId)
                    ->where('scanned_at', '>=', now()->subDays($days))
                    ->selectRaw('device_type, COUNT(*) as count')
                    ->groupBy('device_type')
                    ->pluck('count', 'device_type')
                    ->toArray();
    }

    public static function getCountryStats(int $qrCodeId, int $days = 30): array
    {
        return static::where('qr_code_id', $qrCodeId)
                    ->where('scanned_at', '>=', now()->subDays($days))
                    ->whereNotNull('country')
                    ->selectRaw('country, COUNT(*) as count')
                    ->groupBy('country')
                    ->orderByDesc('count')
                    ->limit(10)
                    ->pluck('count', 'country')
                    ->toArray();
    }

    public static function getHourlyStats(int $qrCodeId, int $days = 7): array
    {
        return static::where('qr_code_id', $qrCodeId)
                    ->where('scanned_at', '>=', now()->subDays($days))
                    ->selectRaw('HOUR(scanned_at) as hour, COUNT(*) as count')
                    ->groupBy('hour')
                    ->orderBy('hour')
                    ->pluck('count', 'hour')
                    ->toArray();
    }

    public static function getDailyStats(int $qrCodeId, int $days = 30): array
    {
        return static::where('qr_code_id', $qrCodeId)
                    ->where('scanned_at', '>=', now()->subDays($days))
                    ->selectRaw('DATE(scanned_at) as date, COUNT(*) as count')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->pluck('count', 'date')
                    ->toArray();
    }

    public static function getTopReferrers(int $qrCodeId, int $days = 30): array
    {
        return static::where('qr_code_id', $qrCodeId)
                    ->where('scanned_at', '>=', now()->subDays($days))
                    ->whereNotNull('referrer')
                    ->selectRaw('referrer, COUNT(*) as count')
                    ->groupBy('referrer')
                    ->orderByDesc('count')
                    ->limit(10)
                    ->pluck('count', 'referrer')
                    ->toArray();
    }

    // Validation
    public static function getValidDeviceTypes(): array
    {
        return [
            self::DEVICE_MOBILE,
            self::DEVICE_DESKTOP,
            self::DEVICE_TABLET,
            self::DEVICE_UNKNOWN,
        ];
    }

    // Duration tracking
    public function updateDuration(int $seconds): void
    {
        $this->update(['duration_on_site' => $seconds]);
    }

    // Bulk analytics
    public static function cleanupOldScans(int $days = 365): int
    {
        return static::where('scanned_at', '<', now()->subDays($days))->delete();
    }
}