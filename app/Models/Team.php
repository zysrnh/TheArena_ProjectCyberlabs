<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'division',
        'logo',
        'region',
        'city',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    public function homeGames(): HasMany
    {
        return $this->hasMany(Game::class, 'team1_id');
    }

    public function awayGames(): HasMany
    {
        return $this->hasMany(Game::class, 'team2_id');
    }

    public function allGames()
    {
        return Game::where('team1_id', $this->id)
            ->orWhere('team2_id', $this->id)
            ->orderBy('date', 'desc')
            ->get();
    }
}