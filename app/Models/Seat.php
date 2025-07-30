<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{
    protected $fillable = [
        'label',
        'type',
        'group_name',
        'row',
        'column',
        'registration_id',
    ];

    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }
}
