<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Player extends Model
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
        'team_id',
        'name',
        'firstname',
        'lastname',
        'age',
        'birth_date',
        'nationality',
        'height',
        'weight',
        'injured',
        'photo_url',
        'birth_details',
        'statistics',
        'additional_data',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'age' => 'integer',
        'birth_date' => 'date',
        'injured' => 'boolean',
        'birth_details' => 'array',
        'statistics' => 'array',
        'additional_data' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the team that the player belongs to.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
