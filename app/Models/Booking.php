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
        'is_paid',
        'notes',
        'bill_no',
        'trx_id',
        'payment_method',
        'payment_status',
        'paid_at',
        'booking_type', // ✅ PENTING: Kolom ini harus ada di fillable
        'original_price',
        'voucher_code',
        'voucher_discount',
        'payment_reff',
        'payment_date',
        'payment_status_code',
        'payment_status_desc',
        'payment_channel_uid',
        'payment_channel',
        'discount_amount',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'time_slots' => 'array',
        'time_slot' => 'array',
        'total_price' => 'decimal:2',
        'is_paid' => 'boolean',
        'paid_at' => 'datetime',
        'payment_date' => 'datetime',
    ];

    // ... (semua method lain tetap sama, tidak berubah)

    /**
     * ✅ UPDATED: Get tipe booking untuk kalender
     * Priority: 
     * 1. Cek kolom booking_type dari database (jika ada)
     * 2. Generate dari kondisi (fallback untuk data lama)
     */
    public function getBookingTypeAttribute($value)
    {
        // ✅ Priority 1: Kalau kolom booking_type sudah ada di database, pakai itu
        if (!empty($value)) {
            return $value;
        }

        // ✅ Priority 2: Kalau tidak ada, generate dari kondisi (untuk backward compatibility)
        if ($this->payment_status === 'pending') {
            return 'pending';
        }
        
        if ($this->isPaid()) {
            if ($this->isManualBooking()) {
                return $this->client && $this->client->is_member ? 'member_manual' : 'manual';
            }
            return 'paid';
        }
        
        return 'unknown';
    }

    /**
     * ✅ Cek apakah booking sudah dibayar
     */
    public function isPaid()
    {
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
     * Relasi ke Venue
     */
    public function venue()
    {
        return $this->belongsTo(Venue::class);
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
            && $this->isPaid()
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

    /**
     * ✅ Cek apakah booking manual (input admin)
     */
    public function isManualBooking()
    {
        return $this->payment_method === null || $this->payment_method === 'manual';
    }

    /**
     * ✅ UPDATED: Get warna untuk kalender
     */
    public function getCalendarColorAttribute()
    {
        // ✅ Gunakan booking_type yang sudah di-resolve di getBookingTypeAttribute
        $type = $this->booking_type;
        
        return match($type) {
            'paid' => 'bg-green-200 border-green-400 text-green-900',
            'pending' => 'bg-pink-200 border-pink-400 text-pink-900',
            'manual' => 'bg-yellow-200 border-yellow-400 text-yellow-900',
            'member_manual' => 'bg-orange-200 border-orange-400 text-orange-900',
            'recurring' => 'bg-orange-600 border-orange-700 text-white',
            default => 'bg-gray-200 border-gray-400 text-gray-900',
        };
    }

    /**
     * ✅ UPDATED: Get icon untuk kalender
     */
    public function getCalendarIconAttribute()
    {
        $type = $this->booking_type;
        
        return match($type) {
            'paid' => '✓',
            'pending' => '⏱',
            'manual' => '📝',
            'member_manual' => '⭐',
            'recurring' => '🔄',
            default => '',
        };
    }

    /**
     * ✅ AUTO DELETE booked_time_slots saat booking dihapus
     * Serta kirim notifikasi Fonnte otomatis saat booking dibayar
     */
    protected static function booted()
    {
        static::deleting(function ($booking) {
            $booking->bookedTimeSlots()->delete();
        });

        static::updated(function ($booking) {
            if ($booking->isDirty('payment_status') && $booking->payment_status === 'paid') {
                if (!app()->runningInConsole()) {
                    try {
                        app(\App\Services\FonnteService::class)->sendBookingNotifications($booking);
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Fonnte notification updated hook failed: ' . $e->getMessage());
                    }
                }
            }
        });

        static::created(function ($booking) {
            if ($booking->payment_status === 'paid') {
                if (!app()->runningInConsole()) {
                    try {
                        app(\App\Services\FonnteService::class)->sendBookingNotifications($booking);
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Fonnte notification created hook failed: ' . $e->getMessage());
                    }
                }
            }
        });
    }

}