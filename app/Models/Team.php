<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'rapidapi_id',
        'sport_type',
        'name',
        'code',
        'country',
        'logo_url',
        'founded',
        'is_national',
        'venue',
        'additional_data',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'founded' => 'integer',
        'is_national' => 'boolean',
        'venue' => 'array',
        'additional_data' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the league that the team belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function league()
    {
        return $this->belongsTo(League::class);
    }

    /**
     * Get the home matches for the team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function homeMatches()
    {
        return $this->hasMany(SportMatch::class, 'home_team_id');
    }

    /**
     * Get the away matches for the team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function awayMatches()
    {
        return $this->hasMany(SportMatch::class, 'away_team_id');
    }

    /**
     * Get the players for the team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function players()
    {
        return $this->hasMany(Player::class, 'team_id');
    }

    /**
     * Get the standings for the team.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function standings()
    {
        return $this->hasMany(Standing::class, 'team_id');
    }
}
