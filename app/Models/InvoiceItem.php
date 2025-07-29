<?php
// app/Models/InvoiceItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'description',
        'item_code',
        'item_type',
        'quantity',
        'unit_price',
        'total_price',
        'discount_amount',
        'period_start',
        'period_end',
        'is_prorated',
        'tax_rate',
        'tax_amount',
        'is_tax_exempt',
        'metadata',
        'external_id',
        'sort_order',
    ];

    protected $casts = [
        'description' => 'array',
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
        'is_prorated' => 'boolean',
        'tax_rate' => 'decimal:4',
        'tax_amount' => 'decimal:2',
        'is_tax_exempt' => 'boolean',
        'metadata' => 'array',
        'sort_order' => 'integer',
    ];

    const TYPE_SUBSCRIPTION = 'subscription';
    const TYPE_SETUP_FEE = 'setup_fee';
    const TYPE_ADDON = 'addon';
    const TYPE_OVERAGE = 'overage';
    const TYPE_DISCOUNT = 'discount';
    const TYPE_TAX = 'tax';
    const TYPE_REFUND = 'refund';
    const TYPE_CUSTOM = 'custom';

    // Relationships
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    // Scopes
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('item_type', $type);
    }

    public function scopeSubscriptions(Builder $query): Builder
    {
        return $query->where('item_type', self::TYPE_SUBSCRIPTION);
    }

    public function scopeAddons(Builder $query): Builder
    {
        return $query->where('item_type', self::TYPE_ADDON);
    }

    public function scopeDiscounts(Builder $query): Builder
    {
        return $query->where('item_type', self::TYPE_DISCOUNT);
    }

    public function scopeTaxExempt(Builder $query): Builder
    {
        return $query->where('is_tax_exempt', true);
    }

    public function scopeProrated(Builder $query): Builder
    {
        return $query->where('is_prorated', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    // Accessors
    public function getLocalizedDescriptionAttribute(): string
    {
        $locale = app()->getLocale();
        return $this->description[$locale] ?? $this->description['en'] ?? 'Invoice Item';
    }

    public function getFormattedUnitPriceAttribute(): string
    {
        $currency = $this->invoice->currency ?? 'USD';
        $symbol = $this->getCurrencySymbol($currency);
        return $symbol . number_format($this->unit_price, 2);
    }

    public function getFormattedTotalPriceAttribute(): string
    {
        $currency = $this->invoice->currency ?? 'USD';
        $symbol = $this->getCurrencySymbol($currency);
        return $symbol . number_format($this->total_price, 2);
    }

    public function getFormattedDiscountAmountAttribute(): string
    {
        if (!$this->discount_amount) {
            return '';
        }
        
        $currency = $this->invoice->currency ?? 'USD';
        $symbol = $this->getCurrencySymbol($currency);
        return $symbol . number_format($this->discount_amount, 2);
    }

    public function getFormattedTaxAmountAttribute(): string
    {
        if (!$this->tax_amount) {
            return '';
        }
        
        $currency = $this->invoice->currency ?? 'USD';
        $symbol = $this->getCurrencySymbol($currency);
        return $symbol . number_format($this->tax_amount, 2);
    }

    public function getTaxPercentageAttribute(): string
    {
        return number_format($this->tax_rate * 100, 2) . '%';
    }

    public function getNetAmountAttribute(): float
    {
        return $this->total_price - $this->discount_amount;
    }

    public function getFormattedNetAmountAttribute(): string
    {
        $currency = $this->invoice->currency ?? 'USD';
        $symbol = $this->getCurrencySymbol($currency);
        return $symbol . number_format($this->net_amount, 2);
    }

    public function getTypeDisplayAttribute(): string
    {
        return match($this->item_type) {
            self::TYPE_SUBSCRIPTION => 'Subscription',
            self::TYPE_SETUP_FEE => 'Setup Fee',
            self::TYPE_ADDON => 'Add-on',
            self::TYPE_OVERAGE => 'Overage',
            self::TYPE_DISCOUNT => 'Discount',
            self::TYPE_TAX => 'Tax',
            self::TYPE_REFUND => 'Refund',
            self::TYPE_CUSTOM => 'Custom',
            default => 'Item',
        };
    }

    public function getTypeIconAttribute(): string
    {
        return match($this->item_type) {
            self::TYPE_SUBSCRIPTION => '🔄',
            self::TYPE_SETUP_FEE => '⚡',
            self::TYPE_ADDON => '➕',
            self::TYPE_OVERAGE => '📈',
            self::TYPE_DISCOUNT => '💰',
            self::TYPE_TAX => '🏛️',
            self::TYPE_REFUND => '↩️',
            self::TYPE_CUSTOM => '🔧',
            default => '📄',
        };
    }

    public function getPeriodDisplayAttribute(): string
    {
        if (!$this->period_start || !$this->period_end) {
            return '';
        }

        $start = $this->period_start->format('M j, Y');
        $end = $this->period_end->format('M j, Y');
        
        return "({$start} - {$end})";
    }

    public function getPeriodDurationAttribute(): int
    {
        if (!$this->period_start || !$this->period_end) {
            return 0;
        }

        return $this->period_start->diffInDays($this->period_end) + 1;
    }

    // Business logic
    public function isSubscription(): bool
    {
        return $this->item_type === self::TYPE_SUBSCRIPTION;
    }

    public function isDiscount(): bool
    {
        return $this->item_type === self::TYPE_DISCOUNT;
    }

    public function isRefund(): bool
    {
        return $this->item_type === self::TYPE_REFUND;
    }

    public function hasDiscount(): bool
    {
        return $this->discount_amount > 0;
    }

    public function hasTax(): bool
    {
        return !$this->is_tax_exempt && $this->tax_amount > 0;
    }

    public function isProrated(): bool
    {
        return $this->is_prorated;
    }

    // Calculations
    public function calculateTotal(): void
    {
        $total = $this->quantity * $this->unit_price;
        $this->update(['total_price' => $total]);
    }

    public function calculateTax(): void
    {
        if ($this->is_tax_exempt) {
            $this->update(['tax_amount' => 0]);
            return;
        }

        $taxableAmount = $this->total_price - $this->discount_amount;
        $taxAmount = $taxableAmount * $this->tax_rate;
        
        $this->update(['tax_amount' => $taxAmount]);
    }

    public function applyDiscount(float $amount): void
    {
        $this->update(['discount_amount' => min($amount, $this->total_price)]);
        $this->calculateTax(); // Recalculate tax after discount
    }

    public function applyPercentageDiscount(float $percentage): void
    {
        $discountAmount = $this->total_price * ($percentage / 100);
        $this->applyDiscount($discountAmount);
    }

    // Currency helper
    private function getCurrencySymbol(string $currency): string
    {
        return match($currency) {
            'TRY' => '₺',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            default => '$',
        };
    }

    // Static methods
    public static function getValidTypes(): array
    {
        return [
            self::TYPE_SUBSCRIPTION,
            self::TYPE_SETUP_FEE,
            self::TYPE_ADDON,
            self::TYPE_OVERAGE,
            self::TYPE_DISCOUNT,
            self::TYPE_TAX,
            self::TYPE_REFUND,
            self::TYPE_CUSTOM,
        ];
    }

    // Factory methods
    public static function createSubscriptionItem(Invoice $invoice, array $data): self
    {
        return $invoice->invoiceItems()->create(array_merge($data, [
            'item_type' => self::TYPE_SUBSCRIPTION,
        ]));
    }

    public static function createSetupFee(Invoice $invoice, float $amount, array|string|null $description = null): self
    {
        // Handle different description types
        if (is_string($description)) {
            $description = ['en' => $description];
        } elseif (is_null($description)) {
            $description = ['en' => 'Setup Fee'];
        }
        
        return $invoice->invoiceItems()->create([
            'item_type' => self::TYPE_SETUP_FEE,
            'description' => $description,
            'quantity' => 1,
            'unit_price' => $amount,
            'total_price' => $amount,
        ]);
    }

    public static function createDiscount(Invoice $invoice, float $amount, array|string|null $description = null): self
    {
        // Handle different description types
        if (is_string($description)) {
            $description = ['en' => $description];
        } elseif (is_null($description)) {
            $description = ['en' => 'Discount'];
        }
        
        return $invoice->invoiceItems()->create([
            'item_type' => self::TYPE_DISCOUNT,
            'description' => $description,
            'quantity' => 1,
            'unit_price' => -$amount,
            'total_price' => -$amount,
        ]);
    }

    

    // Mutators
    public function setQuantityAttribute($value): void
    {
        $this->attributes['quantity'] = max(1, intval($value));
    }

    public function setUnitPriceAttribute($value): void
    {
        $this->attributes['unit_price'] = round(floatval($value), 2);
    }

    // Model events
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            // Auto-calculate total price if not set
            if (!$item->total_price) {
                $item->total_price = $item->quantity * $item->unit_price;
            }
        });

        static::saved(function ($item) {
            // Recalculate invoice totals when item changes
            $item->invoice->calculateTotals();
        });

        static::deleted(function ($item) {
            // Recalculate invoice totals when item is deleted
            $item->invoice->calculateTotals();
        });
    }

    // Ordering
    public function moveUp(): void
    {
        $previousItem = static::where('invoice_id', $this->invoice_id)
            ->where('sort_order', '<', $this->sort_order)
            ->orderByDesc('sort_order')
            ->first();

        if ($previousItem) {
            $temp = $this->sort_order;
            $this->update(['sort_order' => $previousItem->sort_order]);
            $previousItem->update(['sort_order' => $temp]);
        }
    }

    public function moveDown(): void
    {
        $nextItem = static::where('invoice_id', $this->invoice_id)
            ->where('sort_order', '>', $this->sort_order)
            ->orderBy('sort_order')
            ->first();

        if ($nextItem) {
            $temp = $this->sort_order;
            $this->update(['sort_order' => $nextItem->sort_order]);
            $nextItem->update(['sort_order' => $temp]);
        }
    }

    // Validation
    public function getValidationRules(): array
    {
        return [
            'invoice_id' => 'required|exists:invoices,id',
            'description' => 'required|array',
            'description.en' => 'required|string|max:255',
            'item_type' => 'required|in:' . implode(',', self::getValidTypes()),
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:1',
        ];
    }
}