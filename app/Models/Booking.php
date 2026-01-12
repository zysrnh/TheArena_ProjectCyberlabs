<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'venue_id',
        'booking_date',
        'venue_type',
        'time_slots',
        'time_slot',
        'total_price',
        'status',
        'is_paid',           // ✅ Tetap ada
        'notes',
        'bill_no',
        'trx_id',
        'payment_method',
        'payment_status',
        'paid_at',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'time_slots' => 'array',
        'time_slot' => 'array',
        'total_price' => 'decimal:2',
        'is_paid' => 'boolean',  // ✅ Tetap ada, Laravel akan handle 0/1 conversion
        'paid_at' => 'datetime',
    ];

    /**
     * ✅ Cek apakah booking sudah dibayar
     */
    public function isPaid()
    {
        // ✅ Cek keduanya untuk backward compatibility
        return $this->payment_status === 'paid' || $this->is_paid === true;
    }

    /**
     * ✅ Cek apakah booking masih pending
     */
    public function isPending()
    {
        return $this->payment_status === 'pending';
    }

    /**
     * ✅ Cek apakah booking expired
     */
    public function isExpired()
    {
        return $this->payment_status === 'expired';
    }

    /**
     * ✅ Cek apakah booking failed
     */
    public function isFailed()
    {
        return $this->payment_status === 'failed';
    }

    /**
     * Relasi ke Client
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Relasi ke BookedTimeSlot
     */
    public function bookedTimeSlots()
    {
        return $this->hasMany(BookedTimeSlot::class);
    }

    /**
     * Relasi ke Reviews
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get single review
     */
    public function review()
    {
        return $this->hasOne(Review::class);
    }

    /**
     * Cek apakah booking sudah direview
     */
    public function hasReview()
    {
        return $this->reviews()->exists();
    }

    /**
     * Cek apakah booking bisa direview
     */
    public function canBeReviewed()
    {
        return $this->status === 'completed'
            && $this->isPaid()  // ✅ Gunakan method isPaid()
            && $this->booking_date < now()->toDateString()
            && !$this->hasReview();
    }

    /**
     * ✅ Scope untuk query booking yang bisa direview
     */
    public function scopeCompletedWithoutReview($query)
    {
        return $query->where('status', 'completed')
            ->where(function($q) {
                $q->where('payment_status', 'paid')
                  ->orWhere('is_paid', true);
            })
            ->where('booking_date', '<', now()->toDateString())
            ->whereDoesntHave('review');
    }

    /**
     * Get formatted time slots string
     */
    public function getTimeSlotsStringAttribute()
    {
        $slots = $this->time_slots;

        if (!is_array($slots) || empty($slots)) {
            return '-';
        }

        $times = [];
        foreach ($slots as $slot) {
            if (isset($slot['time'])) {
                $times[] = $slot['time'];
            }
        }

        if (empty($times)) {
            return '-';
        }

        return implode(', ', $times);
    }

    /**
     * Get time slot accessor
     */
    public function getTimeSlotAttribute($value)
    {
        if ($value) {
            if (is_array($value)) {
                return $value;
            }

            if (is_string($value)) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $decoded;
                }
                return $value;
            }
        }

        $timeSlots = $this->attributes['time_slots'] ?? null;
        if ($timeSlots) {
            if (is_string($timeSlots)) {
                $decoded = json_decode($timeSlots, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded[0] ?? null;
                }
            }
            if (is_array($timeSlots)) {
                return $timeSlots[0] ?? null;
            }
        }

        return null;
    }

    /**
     * Get formatted time range
     */
    public function getTimeRangeAttribute()
    {
        $slots = $this->time_slots;

        if (!is_array($slots) || empty($slots)) {
            return '-';
        }

        $times = [];
        foreach ($slots as $slot) {
            if (isset($slot['time'])) {
                $times[] = $slot['time'];
            }
        }

        if (empty($times)) {
            return '-';
        }

        usort($times, function ($a, $b) {
            $aStart = explode(' - ', $a)[0];
            $bStart = explode(' - ', $b)[0];
            return strcmp($aStart, $bStart);
        });

        if (count($times) === 1) {
            return $times[0];
        }

        $firstStart = explode(' - ', $times[0])[0];
        $lastEnd = explode(' - ', $times[count($times) - 1])[1];

        return $firstStart . ' - ' . $lastEnd;
    }

    /**
     * ✅ Get payment status badge color
     */
    public function getPaymentStatusColorAttribute()
    {
        return match ($this->payment_status) {
            'paid'      => 'green',
            'pending'   => 'yellow',
            'failed'    => 'red',
            'expired'   => 'gray',
            'cancelled' => 'gray',
            default     => 'gray',
        };
    }

    /**
     * ✅ Get payment status label
     */
    public function getPaymentStatusLabelAttribute()
    {
        return match ($this->payment_status) {
            'paid'      => 'Lunas',
            'pending'   => 'Menunggu Pembayaran',
            'failed'    => 'Gagal',
            'expired'   => 'Expired',
            'cancelled' => 'Dibatalkan',
            default     => 'Unknown',
        };
    }
}