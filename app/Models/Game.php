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
        'team1_division',
        'team2_id',
        'team2_division',
        'category_id',
        'score',
        'status',
        'quarters',
        'stats',
        'year',
        'series',
        'region',
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
        'date'            => 'date',
        'time'            => 'datetime:H:i',
        'stats'           => 'array',
        'box_score_team1' => 'array',
        'box_score_team2' => 'array',
        'quarters'        => 'array',
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(TeamCategory::class, 'category_id');
    }

    public function playerStats(): HasMany
    {
        return $this->hasMany(PlayerStat::class);
    }

    public function matchHighlights(): HasMany
    {
        return $this->hasMany(MatchHighlight::class);
    }

    public function getMatchTitleAttribute(): string
    {
        $team1Name = $this->team1->name . ($this->team1_division ? ' ' . $this->team1_division : '');
        $team2Name = $this->team2->name . ($this->team2_division ? ' ' . $this->team2_division : '');

        if ($this->category) {
            $cat = ' ' . $this->category->category_name;
            $team1Name .= $cat;
            $team2Name .= $cat;
        }

        return "{$team1Name} vs {$team2Name}";
    }

    public function boxScoreTeam1()
    {
        $statsQuery = $this->playerStats()->where('team_id', $this->team1_id);

        if ($statsQuery->exists()) {
            return $statsQuery->with('player')->orderBy('player_id')->get()->map(function ($stat) {
                return [
                    'id'       => $stat->player->id,
                    'no'       => $stat->player->jersey_no ?? '-',
                    'name'     => $stat->player->name,
                    'photo'    => $stat->player->photo ? asset('storage/' . $stat->player->photo) : null,
                    'position' => $stat->player->position ?? '-',
                    'minutes'  => $stat->minutes ?? 0,
                    'points'   => $stat->points ?? 0,
                    'assists'  => $stat->assists ?? 0,
                    'rebounds' => $stat->rebounds ?? 0,
                    'isMVP'    => $stat->is_mvp ?? false,
                ];
            });
        }

        return collect([]);
    }

    public function boxScoreTeam2()
    {
        $statsQuery = $this->playerStats()->where('team_id', $this->team2_id);

        if ($statsQuery->exists()) {
            return $statsQuery->with('player')->orderBy('player_id')->get()->map(function ($stat) {
                return [
                    'id'       => $stat->player->id,
                    'no'       => $stat->player->jersey_no ?? '-',
                    'name'     => $stat->player->name,
                    'photo'    => $stat->player->photo ? asset('storage/' . $stat->player->photo) : null,
                    'position' => $stat->player->position ?? '-',
                    'minutes'  => $stat->minutes ?? 0,
                    'points'   => $stat->points ?? 0,
                    'assists'  => $stat->assists ?? 0,
                    'rebounds' => $stat->rebounds ?? 0,
                    'isMVP'    => $stat->is_mvp ?? false,
                ];
            });
        }

        return collect([]);
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
        if ($series === 'Semua Series') return $query;
        return $query->where('series', $series);
    }

    public function scopeByRegion($query, $region)
    {
        if ($region === 'Regional') return $query;
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