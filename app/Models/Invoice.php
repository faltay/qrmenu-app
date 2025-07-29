<?php
// app/Models/Invoice.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Carbon\Carbon;

class Invoice extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, LogsActivity;

    protected $fillable = [
        'invoice_number',
        'invoice_series',
        'user_id',
        'user_subscription_id',
        'gateway',
        'gateway_invoice_id',
        'gateway_payment_intent_id',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'currency',
        'invoice_date',
        'due_date',
        'paid_at',
        'payment_attempted_at',
        'payment_attempts',
        'status',
        'customer_data',
        'billing_address',
        'company_data',
        'notes',
        'payment_terms',
        'reference',
        'pdf_path',
        'pdf_generated_at',
        'pdf_hash',
        'last_reminder_sent_at',
        'reminder_count',
        'email_log',
        'metadata',
        'locale',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:4',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'invoice_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'payment_attempted_at' => 'datetime',
        'payment_attempts' => 'integer',
        'customer_data' => 'array',
        'billing_address' => 'array',
        'company_data' => 'array',
        'payment_terms' => 'array',
        'pdf_generated_at' => 'datetime',
        'reminder_count' => 'integer',
        'email_log' => 'array',
        'metadata' => 'array',
        'last_reminder_sent_at' => 'datetime',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_SENT = 'sent';
    const STATUS_VIEWED = 'viewed';
    const STATUS_PAID = 'paid';
    const STATUS_PARTIALLY_PAID = 'partially_paid';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function userSubscription(): BelongsTo
    {
        return $this->belongsTo(UserSubscription::class);
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    // Scopes
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SENT);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OVERDUE)
                    ->orWhere(function($q) {
                        $q->whereIn('status', [self::STATUS_SENT, self::STATUS_VIEWED])
                          ->where('due_date', '<', now());
                    });
    }

    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_SENT,
            self::STATUS_VIEWED,
            self::STATUS_OVERDUE,
            self::STATUS_FAILED
        ]);
    }

    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereMonth('invoice_date', now()->month)
                    ->whereYear('invoice_date', now()->year);
    }

    public function scopeLastMonth(Builder $query): Builder
    {
        $lastMonth = now()->subMonth();
        return $query->whereMonth('invoice_date', $lastMonth->month)
                    ->whereYear('invoice_date', $lastMonth->year);
    }

    public function scopeForGateway(Builder $query, string $gateway): Builder
    {
        return $query->where('gateway', $gateway);
    }

    public function scopeInCurrency(Builder $query, string $currency): Builder
    {
        return $query->where('currency', $currency);
    }

    // Accessors
    public function getFormattedTotalAttribute(): string
    {
        $symbol = $this->getCurrencySymbol();
        return $symbol . number_format($this->total_amount, 2);
    }

    public function getFormattedSubtotalAttribute(): string
    {
        $symbol = $this->getCurrencySymbol();
        return $symbol . number_format($this->subtotal, 2);
    }

    public function getFormattedTaxAmountAttribute(): string
    {
        $symbol = $this->getCurrencySymbol();
        return $symbol . number_format($this->tax_amount, 2);
    }

    public function getFormattedDiscountAmountAttribute(): string
    {
        if (!$this->discount_amount) {
            return '';
        }
        
        $symbol = $this->getCurrencySymbol();
        return $symbol . number_format($this->discount_amount, 2);
    }

    public function getTaxPercentageAttribute(): string
    {
        return number_format($this->tax_rate * 100, 2) . '%';
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => '📝 Draft',
            self::STATUS_SENT => '📤 Sent',
            self::STATUS_VIEWED => '👀 Viewed',
            self::STATUS_PAID => '✅ Paid',
            self::STATUS_PARTIALLY_PAID => '🟡 Partial',
            self::STATUS_OVERDUE => '⚠️ Overdue',
            self::STATUS_FAILED => '❌ Failed',
            self::STATUS_CANCELLED => '🚫 Cancelled',
            self::STATUS_REFUNDED => '🔄 Refunded',
            default => '❓ Unknown',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PAID => 'success',
            self::STATUS_DRAFT => 'secondary',
            self::STATUS_SENT, self::STATUS_VIEWED => 'info',
            self::STATUS_PARTIALLY_PAID => 'warning',
            self::STATUS_OVERDUE, self::STATUS_FAILED => 'danger',
            self::STATUS_CANCELLED, self::STATUS_REFUNDED => 'dark',
            default => 'light',
        };
    }

    public function getDaysOverdueAttribute(): int
    {
        if ($this->status === self::STATUS_PAID || !$this->due_date) {
            return 0;
        }

        return max(0, now()->diffInDays($this->due_date, false));
    }

    public function getDaysUntilDueAttribute(): int
    {
        if (!$this->due_date || $this->status === self::STATUS_PAID) {
            return 0;
        }

        return max(0, $this->due_date->diffInDays(now(), false));
    }

    public function getCustomerNameAttribute(): string
    {
        return $this->customer_data['name'] ?? $this->user->name ?? 'Unknown Customer';
    }

    public function getCustomerEmailAttribute(): string
    {
        return $this->customer_data['email'] ?? $this->user->email ?? '';
    }

    public function getPdfUrlAttribute(): ?string
    {
        if ($this->pdf_path && \Storage::disk('public')->exists($this->pdf_path)) {
            return \Storage::disk('public')->url($this->pdf_path);
        }

        return null;
    }

    // Status checks
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_OVERDUE || 
               (in_array($this->status, [self::STATUS_SENT, self::STATUS_VIEWED]) && 
                $this->due_date && $this->due_date->isPast());
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isRefunded(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    public function canBePaid(): bool
    {
        return in_array($this->status, [
            self::STATUS_SENT,
            self::STATUS_VIEWED,
            self::STATUS_OVERDUE,
            self::STATUS_FAILED,
            self::STATUS_PARTIALLY_PAID
        ]);
    }

    public function canBeCancelled(): bool
    {
        return !in_array($this->status, [
            self::STATUS_PAID,
            self::STATUS_CANCELLED,
            self::STATUS_REFUNDED
        ]);
    }

    // Currency management
    public function getCurrencySymbol(): string
    {
        return match($this->currency) {
            'TRY' => '₺',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            default => '$',
        };
    }

    // Status management
    public function markAsSent(): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'metadata' => array_merge($this->metadata ?? [], [
                'sent_at' => now()->toDateTimeString()
            ])
        ]);
    }

    public function markAsViewed(): void
    {
        if ($this->status === self::STATUS_SENT) {
            $this->update([
                'status' => self::STATUS_VIEWED,
                'metadata' => array_merge($this->metadata ?? [], [
                    'viewed_at' => now()->toDateTimeString()
                ])
            ]);
        }
    }

    public function markAsPaid(?\Carbon\Carbon $paidAt = null): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_at' => $paidAt ?? now(),
        ]);
    }

    public function markAsOverdue(): void
    {
        if (in_array($this->status, [self::STATUS_SENT, self::STATUS_VIEWED])) {
            $this->update(['status' => self::STATUS_OVERDUE]);
        }
    }

    public function cancel(string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'metadata' => array_merge($this->metadata ?? [], [
                'cancelled_at' => now()->toDateTimeString(),
                'cancellation_reason' => $reason
            ])
        ]);
    }

    // Payment tracking
    public function recordPaymentAttempt(): void
    {
        $this->increment('payment_attempts');
        $this->update(['payment_attempted_at' => now()]);
    }

    public function markPaymentFailed(string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'metadata' => array_merge($this->metadata ?? [], [
                'payment_failed_at' => now()->toDateTimeString(),
                'failure_reason' => $reason
            ])
        ]);
    }

    // Reminder management
    public function sendReminder(): void
    {
        $this->increment('reminder_count');
        $this->update(['last_reminder_sent_at' => now()]);
        
        // Add to email log
        $emailLog = $this->email_log ?? [];
        $emailLog[] = [
            'type' => 'reminder',
            'sent_at' => now()->toDateTimeString(),
            'reminder_number' => $this->reminder_count
        ];
        
        $this->update(['email_log' => $emailLog]);
    }

    public function canSendReminder(): bool
    {
        if (!$this->canBePaid()) {
            return false;
        }

        // Don't send more than 3 reminders
        if ($this->reminder_count >= 3) {
            return false;
        }

        // Don't send reminders more than once per day
        if ($this->last_reminder_sent_at && $this->last_reminder_sent_at->isToday()) {
            return false;
        }

        return true;
    }

    // PDF management
    public function generatePDF(): string
    {
        // Mock implementation - gerçek PDF generation
        $filename = "invoices/{$this->invoice_number}.pdf";
        
        $this->update([
            'pdf_path' => $filename,
            'pdf_generated_at' => now(),
            'pdf_hash' => hash('sha256', $this->invoice_number . $this->total_amount)
        ]);

        return $filename;
    }

    public function regeneratePDF(): string
    {
        if ($this->pdf_path) {
            \Storage::disk('public')->delete($this->pdf_path);
        }

        return $this->generatePDF();
    }

    // Invoice number generation
    public static function generateInvoiceNumber(string $series = 'QR'): string
    {
        $year = now()->year;
        $month = now()->format('m');
        
        // Son invoice'ı bul (tüm series'ler için)
        $lastInvoice = static::where('invoice_series', $series)
                        ->whereYear('invoice_date', $year)
                        ->whereMonth('invoice_date', now()->month)
                        ->orderBy('id', 'desc')  // ← ID'ye göre sırala
                        ->first();

        if ($lastInvoice && preg_match('/' . $series . $year . $month . '(\d+)/', $lastInvoice->invoice_number, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        } else {
            $nextNumber = 1;
        }

        // Unique check ile güvenli oluştur
        do {
            $invoiceNumber = $series . $year . $month . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            $exists = static::where('invoice_number', $invoiceNumber)->exists();
            if ($exists) {
                $nextNumber++;
            }
        } while ($exists);

        return $invoiceNumber;
    }

    // Calculations
    public function calculateTotals(): void
    {
        $subtotal = $this->invoiceItems()->sum(\DB::raw('quantity * unit_price - discount_amount'));
        $taxAmount = $subtotal * $this->tax_rate;
        $total = $subtotal + $taxAmount - $this->discount_amount;

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $total,
        ]);
    }

    public function addItem(array $itemData): InvoiceItem
    {
        $item = $this->invoiceItems()->create($itemData);
        $this->calculateTotals();
        return $item;
    }

    // Media Collections
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments')
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png']);
    }

    // Activity Logging
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'total_amount', 'paid_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Route binding
    public function getRouteKeyName(): string
    {
        return 'invoice_number';
    }

    // Static methods
    public static function getValidStatuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_SENT,
            self::STATUS_VIEWED,
            self::STATUS_PAID,
            self::STATUS_PARTIALLY_PAID,
            self::STATUS_OVERDUE,
            self::STATUS_FAILED,
            self::STATUS_CANCELLED,
            self::STATUS_REFUNDED,
        ];
    }

    public static function getSupportedCurrencies(): array
    {
        return ['USD', 'EUR', 'TRY', 'GBP', 'CAD', 'AUD', 'JPY'];
    }

    // Bulk operations
    public static function markOverdueInvoices(): int
    {
        return static::whereIn('status', [self::STATUS_SENT, self::STATUS_VIEWED])
                     ->where('due_date', '<', now())
                     ->update(['status' => self::STATUS_OVERDUE]);
    }

    public static function getTotalRevenue(string $currency = null, int $days = 30): float
    {
        $query = static::where('status', self::STATUS_PAID)
                       ->where('paid_at', '>=', now()->subDays($days));

        if ($currency) {
            $query->where('currency', $currency);
        }

        return $query->sum('total_amount');
    }
}