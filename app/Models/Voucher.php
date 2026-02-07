<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'discount_type',
        'discount_value',
        'min_purchase',
        'max_discount',
        'valid_from',
        'valid_until',
        'usage_limit',
        'used_count',
        'is_active',
        'description',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'min_purchase' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Relationship: Voucher usages
     */
    public function usages()
    {
        return $this->hasMany(VoucherUsage::class);
    }

    /**
     * Check if voucher is valid
     */
    public function isValid(): bool
    {
        // Check if active
        if (!$this->is_active) {
            return false;
        }

        // Check date validity
        $now = Carbon::now();
        
        if ($this->valid_from && $now->lt($this->valid_from)) {
            return false;
        }

        if ($this->valid_until && $now->gt($this->valid_until)) {
            return false;
        }

        // Check usage limit
        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Check if user can use this voucher
     */
    public function canBeUsedBy($clientId): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        // Check if user already used this voucher
        $hasUsed = VoucherUsage::where('voucher_id', $this->id)
            ->where('client_id', $clientId)
            ->exists();

        return !$hasUsed;
    }

    /**
     * Calculate discount amount
     */
    public function calculateDiscount(float $totalAmount): float
    {
        // Check minimum purchase
        if ($totalAmount < $this->min_purchase) {
            return 0;
        }

        $discount = 0;

        if ($this->discount_type === 'percentage') {
            $discount = ($totalAmount * $this->discount_value) / 100;
            
            // Apply max discount if set
            if ($this->max_discount && $discount > $this->max_discount) {
                $discount = $this->max_discount;
            }
        } else {
            // Fixed discount
            $discount = min($this->discount_value, $totalAmount);
        }

        return round($discount, 2);
    }

    /**
     * Apply voucher and record usage
     */
    public function apply($clientId, $bookingId, $discountAmount)
    {
        // Record usage
        VoucherUsage::create([
            'voucher_id' => $this->id,
            'client_id' => $clientId,
            'booking_id' => $bookingId,
            'discount_amount' => $discountAmount,
        ]);

        // Increment used count
        $this->increment('used_count');
    }

    /**
     * Scope: Active vouchers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', Carbon::now());
            })
            ->where(function ($q) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', Carbon::now());
            });
    }
}