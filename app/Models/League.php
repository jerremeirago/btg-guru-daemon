<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class League extends Model
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
        'country',
        'logo_url',
        'type',
        'seasons',
        'country_details',
        'additional_data',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'seasons' => 'array',
        'country_details' => 'array',
        'additional_data' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the teams for the league.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    /**
     * Get the matches for the league.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function matches()
    {
        return $this->hasMany(SportMatch::class, 'league_id');
    }

    /**
     * Get the standings for the league.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function standings()
    {
        return $this->hasMany(Standing::class, 'league_id');
    }
}
