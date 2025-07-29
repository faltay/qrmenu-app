<?php
// app/Models/Language.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Language extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'native_name',
        'flag_icon',
        'direction',
        'is_active',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    // Accessors
    public function getDisplayNameAttribute(): string
    {
        return $this->native_name ?? $this->name;
    }

    public function getFlagEmojiAttribute(): string
    {
        return $this->flag_icon ?? '🌐';
    }

    // Static methods
    public static function getDefault(): ?self
    {
        return static::default()->first();
    }

    public static function getActive(): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()->ordered()->get();
    }

    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    // Mutators
    public function setCodeAttribute($value): void
    {
        $this->attributes['code'] = strtolower($value);
    }
}