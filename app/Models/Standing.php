<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Standing extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sport_type',
        'league_id',
        'team_id',
        'season',
        'rank',
        'points',
        'goals_diff',
        'group',
        'form',
        'status',
        'description',
        'all_stats',
        'home_stats',
        'away_stats',
        'additional_data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'season' => 'integer',
        'rank' => 'integer',
        'points' => 'integer',
        'goals_diff' => 'integer',
        'all_stats' => 'array',
        'home_stats' => 'array',
        'away_stats' => 'array',
        'additional_data' => 'array',
    ];

    /**
     * Get the league that the standing belongs to.
     */
    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    /**
     * Get the team that the standing belongs to.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
