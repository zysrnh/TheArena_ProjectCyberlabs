<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageVisit extends Model
{
    public $timestamps = true; // Pake created_at & updated_at
    
    protected $fillable = [
        'visit_date',
        'total_visits',
        'ip_address', // Optional, bisa null
    ];

    protected $casts = [
        'visit_date' => 'date',
    ];
}