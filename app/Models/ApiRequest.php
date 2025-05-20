<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'endpoint',
        'method',
        'sport_type',
        'status_code',
        'success',
        'error_message',
        'response_time_ms',
        'request_params',
        'response_headers',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status_code' => 'integer',
        'success' => 'boolean',
        'response_time_ms' => 'integer',
        'request_params' => 'array',
        'response_headers' => 'array',
    ];
}
