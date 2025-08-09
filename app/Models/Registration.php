<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Registration extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'unique_code',
        'has_attended',
        'is_approved',
        'attended_at',
        'last_blasted_at',
        'last_successful_sent_at',
        'whatsapp_send_attempts',
    ];

    protected $appends = [
        'qr_path',
        'qr_full_path',
    ];

    protected static function booted(): void
    {
        static::creating(function (Registration $registration) {
            if (empty($registration->unique_code)) {
                $registration->unique_code = static::generateUniqueCode();
            }
        });

        static::deleted(function (Registration $registration) {
            $registration->seat->update(
                ['registration_id' => null]
            );
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'extras' => 'array',
        ];
    }

    protected function qrPath(): Attribute
    {
        return Attribute::make(
            get: fn() => asset('storage/qr_codes/' . $this->unique_code . '.png')
        );
    }

    protected function qrFullPath(): Attribute
    {
        return Attribute::make(
            get: fn() => 'storage/qr_codes/' . $this->unique_code . '.png'
        );
    }

    public function getMessageStatusAttribute()
    {
        return $this->getWhatsappDeliveryStatus();
    }

    public function getWhatsappDeliveryStatus()
    {
        $latestLog = $this->latestTwilioLog;

        if (!$latestLog) {
            return 'not_sent';
        }

        return $latestLog->status;
    }

    public function hasSuccessfulWhatsappDelivery()
    {
        return $this->twilioLogs()
            ->whereIn('status', ['delivered', 'sent'])
            ->exists();
    }

    public function getFailedWhatsappAttempts()
    {
        return $this->twilioLogs()
            ->whereIn('status', ['failed', 'undelivered', 'rejected'])
            ->count();
    }

    /**
     * Generate a unique code.
     *
     * @return string
     */
    private static function generateUniqueCode(): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $codeLength = 6;

        do {
            $code = '';
            for ($i = 0; $i < $codeLength; $i++) {
                $code .= $characters[rand(0, strlen($characters) - 1)];
            }
        } while (static::where('unique_code', $code)->exists());

        return $code;
    }

    public function seat()
    {
        return $this->hasOne(Seat::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
