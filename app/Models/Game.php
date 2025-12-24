<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'league',
        'date',
        'time',
        'venue',
        'team1_id',
        'team1_category_id', // ✅ BARU
        'team2_id',
        'team2_category_id', // ✅ BARU
        'score',
        'status',
        'quarters',
        'stats',
        'year',
        'series',
        'region',
        // Team Statistics Fields
        'stat_fg_team1',
        'stat_fg_team2',
        'stat_2pt_team1',
        'stat_2pt_team2',
        'stat_3pt_team1',
        'stat_3pt_team2',
        'stat_ft_team1',
        'stat_ft_team2',
        'stat_reb_team1',
        'stat_reb_team2',
        'stat_ast_team1',
        'stat_ast_team2',
        'stat_stl_team1',
        'stat_stl_team2',
        'stat_blk_team1',
        'stat_blk_team2',
        'stat_to_team1',
        'stat_to_team2',
        'stat_foul_team1',
        'stat_foul_team2',
        'stat_pot_team1',
        'stat_pot_team2',
        'box_score_team1',
        'box_score_team2',
    ];

    protected $casts = [
        'date' => 'date',
        'time' => 'datetime:H:i',
        'stats' => 'array',
        'box_score_team1' => 'array',
        'box_score_team2' => 'array',
        'quarters' => 'array',
    ];

    protected $appends = [
        'formatted_date',
        'formatted_time',
    ];

    public function team1(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team1_id');
    }

    public function team2(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team2_id');
    }

    // ✅ RELASI BARU UNTUK TEAM CATEGORIES
    public function team1Category(): BelongsTo
    {
        return $this->belongsTo(TeamCategory::class, 'team1_category_id');
    }

    public function team2Category(): BelongsTo
    {
        return $this->belongsTo(TeamCategory::class, 'team2_category_id');
    }

    public function playerStats(): HasMany
    {
        return $this->hasMany(PlayerStat::class);
    }

    public function matchHighlights()
    {
        return $this->hasMany(MatchHighlight::class);
    }

    // ✅ HELPER METHOD BARU - Get match title with categories
    public function getMatchTitleAttribute(): string
    {
        $team1Name = $this->team1->name;
        $team2Name = $this->team2->name;
        
        if ($this->team1Category) {
            $team1Name .= ' ' . $this->team1Category->category_name;
        }
        
        if ($this->team2Category) {
            $team2Name .= ' ' . $this->team2Category->category_name;
        }
        
        return "{$team1Name} vs {$team2Name}";
    }

    /**
     * Get box score untuk Team 1
     */
    public function boxScoreTeam1()
    {
        // Cek apakah box_score_team1 sudah diisi dari form Filament
        if (!empty($this->box_score_team1) && is_array($this->box_score_team1)) {
            return collect($this->box_score_team1)->map(function ($item) {
                $player = Player::find($item['player_id']);
                return [
                    'id' => $player->id ?? 0,
                    'no' => $player->jersey_no ?? '-',
                    'name' => $player->name ?? 'Unknown',
                    'photo' => $player && $player->photo ? asset('storage/' . $player->photo) : null,
                    'position' => $player->position ?? '-',
                    'minutes' => $item['minutes'] ?? 0,
                    'points' => $item['points'] ?? 0,
                    'assists' => $item['assists'] ?? 0,
                    'rebounds' => $item['rebounds'] ?? 0,
                    'isMVP' => $item['is_mvp'] ?? false,
                ];
            });
        }

        // Cek apakah sudah ada stats untuk team1 dari player_stats table
        $statsExist = $this->playerStats()
            ->where('team_id', $this->team1_id)
            ->exists();

        if ($statsExist) {
            return $this->playerStats()
                ->where('team_id', $this->team1_id)
                ->with('player')
                ->orderByDesc('points')
                ->limit(5)
                ->get()
                ->map(function ($stat, $index) {
                    return [
                        'id' => $stat->player->id,
                        'no' => $stat->player->jersey_no ?? ($index + 1),
                        'name' => $stat->player->name,
                        'photo' => $stat->player->photo ? asset('storage/' . $stat->player->photo) : null,
                        'position' => $stat->player->position ?? '-',
                        'minutes' => $stat->minutes ?? 0,
                        'points' => $stat->points ?? 0,
                        'assists' => $stat->assists ?? 0,
                        'rebounds' => $stat->rebounds ?? 0,
                        'isMVP' => $stat->is_mvp ?? false,
                    ];
                });
        }

        // Default: ambil 5 pemain aktif dari team1
        if (!$this->team1 || !$this->team1->players) {
            return collect([]);
        }

        return $this->team1->players()
            ->where('is_active', true)
            ->orderBy('jersey_no')
            ->limit(5)
            ->get()
            ->values()
            ->map(function ($player, $index) {
                return [
                    'id' => $player->id,
                    'no' => $player->jersey_no ?? ($index + 1),
                    'name' => $player->name,
                    'photo' => $player->photo ? asset('storage/' . $player->photo) : null,
                    'position' => $player->position ?? '-',
                    'minutes' => 0,
                    'points' => 0,
                    'assists' => 0,
                    'rebounds' => 0,
                    'isMVP' => false,
                ];
            });
    }

    /**
     * Get box score untuk Team 2
     */
    public function boxScoreTeam2()
    {
        // Cek apakah box_score_team2 sudah diisi dari form Filament
        if (!empty($this->box_score_team2) && is_array($this->box_score_team2)) {
            return collect($this->box_score_team2)->map(function ($item) {
                $player = Player::find($item['player_id']);
                return [
                    'id' => $player->id ?? 0,
                    'no' => $player->jersey_no ?? '-',
                    'name' => $player->name ?? 'Unknown',
                    'photo' => $player && $player->photo ? asset('storage/' . $player->photo) : null,
                    'position' => $player->position ?? '-',
                    'minutes' => $item['minutes'] ?? 0,
                    'points' => $item['points'] ?? 0,
                    'assists' => $item['assists'] ?? 0,
                    'rebounds' => $item['rebounds'] ?? 0,
                    'isMVP' => $item['is_mvp'] ?? false,
                ];
            });
        }

        // Cek apakah sudah ada stats untuk team2 dari player_stats table
        $statsExist = $this->playerStats()
            ->where('team_id', $this->team2_id)
            ->exists();

        if ($statsExist) {
            return $this->playerStats()
                ->where('team_id', $this->team2_id)
                ->with('player')
                ->orderByDesc('points')
                ->limit(5)
                ->get()
                ->map(function ($stat, $index) {
                    return [
                        'id' => $stat->player->id,
                        'no' => $stat->player->jersey_no ?? ($index + 1),
                        'name' => $stat->player->name,
                        'photo' => $stat->player->photo ? asset('storage/' . $stat->player->photo) : null,
                        'position' => $stat->player->position ?? '-',
                        'minutes' => $stat->minutes ?? 0,
                        'points' => $stat->points ?? 0,
                        'assists' => $stat->assists ?? 0,
                        'rebounds' => $stat->rebounds ?? 0,
                        'isMVP' => $stat->is_mvp ?? false,
                    ];
                });
        }

        // Default: ambil 5 pemain aktif dari team2
        if (!$this->team2 || !$this->team2->players) {
            return collect([]);
        }

        return $this->team2->players()
            ->where('is_active', true)
            ->orderBy('jersey_no')
            ->limit(5)
            ->get()
            ->values()
            ->map(function ($player, $index) {
                return [
                    'id' => $player->id,
                    'no' => $player->jersey_no ?? ($index + 1),
                    'name' => $player->name,
                    'photo' => $player->photo ? asset('storage/' . $player->photo) : null,
                    'position' => $player->position ?? '-',
                    'minutes' => 0,
                    'points' => 0,
                    'assists' => 0,
                    'rebounds' => 0,
                    'isMVP' => false,
                ];
            });
    }

    public function getFormattedDateAttribute()
    {
        Carbon::setLocale('id');
        return $this->date->isoFormat('dddd, D MMMM YYYY');
    }

    public function getFormattedTimeAttribute()
    {
        return $this->time->format('H:i');
    }

    public function scopeByDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByYear($query, $year)
    {
        return $query->where('year', $year);
    }

    public function scopeBySeries($query, $series)
    {
        if ($series === 'Semua Series') {
            return $query;
        }
        return $query->where('series', $series);
    }

    public function scopeByRegion($query, $region)
    {
        if ($region === 'Regional') {
            return $query;
        }
        return $query->where('region', $region);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->whereHas('team1', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })->orWhereHas('team2', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        });
    }
}