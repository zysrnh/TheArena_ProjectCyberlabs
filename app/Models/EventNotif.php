<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class EventNotif extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'tagline',
        'image',
        'event_date',
        'event_end_date',
        'event_time',
        'location',
        'monthly_original_price',
        'monthly_price',
        'monthly_discount_percent',
        'weekly_price',
        'benefits_list',
        'whatsapp_number',
        'whatsapp_message',
        'is_active',
        'show_pricing',
    ];

    protected $casts = [
        'event_date' => 'date',
        'event_end_date' => 'date',
        'is_active' => 'boolean',
        'show_pricing' => 'boolean',
        'monthly_original_price' => 'decimal:0',
        'monthly_price' => 'decimal:0',
        'weekly_price' => 'decimal:0',
        'monthly_discount_percent' => 'integer',
        'benefits_list' => 'json',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getFormattedDateAttribute()
    {
        if ($this->event_end_date && $this->event_date->format('Y-m-d') !== $this->event_end_date->format('Y-m-d')) {
            return Carbon::parse($this->event_date)->locale('id')->isoFormat('dddd, D MMMM YYYY') . 
                   ' - ' . 
                   Carbon::parse($this->event_end_date)->locale('id')->isoFormat('dddd, D MMMM YYYY');
        }
        return Carbon::parse($this->event_date)->locale('id')->isoFormat('dddd, D MMMM YYYY');
    }

    public function getFormattedTimeAttribute()
    {
        if ($this->event_time) {
            return Carbon::parse($this->event_time)->format('H:i');
        }
        return null;
    }

    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return null;
        }

        if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
            return $this->image;
        }

        return asset('storage/' . $this->image);
    }

    public function getFormattedMonthlyOriginalPriceAttribute()
    {
        if (!$this->monthly_original_price) return null;
        return number_format($this->monthly_original_price, 0, ',', '.');
    }

    public function getFormattedMonthlyPriceAttribute()
    {
        if (!$this->monthly_price) return null;
        return number_format($this->monthly_price, 0, ',', '.');
    }

    public function getFormattedWeeklyPriceAttribute()
    {
        if (!$this->weekly_price) return null;
        return number_format($this->weekly_price, 0, ',', '.');
    }

    public function getBenefitsArrayAttribute()
    {
        if (!$this->benefits_list) {
            return [];
        }
        return $this->benefits_list;
    }

    public function getWhatsappUrlAttribute()
    {
        $number = preg_replace('/[^0-9]/', '', $this->whatsapp_number);
        if (substr($number, 0, 1) === '0') {
            $number = '62' . substr($number, 1);
        }
        
        $message = $this->whatsapp_message ?? "Halo, saya ingin mendaftar untuk event: {$this->title}";
        $encodedMessage = urlencode($message);
        
        return "https://wa.me/{$number}?text={$encodedMessage}";
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($eventNotif) {
            if ($eventNotif->is_active) {
                static::where('id', '!=', $eventNotif->id)->update(['is_active' => false]);
            }
        });
    }
}