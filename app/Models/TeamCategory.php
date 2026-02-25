<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeamCategory extends Model
{
    protected $fillable = [
        'category_name',
        'age_group',
        'min_age',
        'max_age',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function players(): HasMany
    {
        return $this->hasMany(Player::class, 'team_category_id');
    }

    public function games(): HasMany
    {
        return $this->hasMany(Game::class, 'category_id');
    }

    // Helper untuk display name lengkap
    public function getFullNameAttribute(): string
    {
        return $this->category_name . ' (' . $this->age_group . ')';
    }
}