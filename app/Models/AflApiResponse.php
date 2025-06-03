<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AflApiResponse extends Model
{
    use HasUuids;

    protected $table = 'afl_api_responses';

    protected $fillable = [
        'uri',
        'response',
        'response_code',
        'response_time',
        'request_id',
        'round',
        'match_date',
    ];

    protected $casts = [
        'response' => 'array',
        'response_code' => 'integer',
        'response_time' => 'integer',
        'round' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (AflApiResponse $model) {
            $model->round = 12;
            $model->match_date = '29.05.2025';
        });

        static::updating(function (AflApiResponse $model) {
            $model->round = 12;
            $model->match_date = '29.05.2025';
        });
    }

    public function scopeGetLatestData($query)
    {
        return $query->orderBy('updated_at', 'desc')->first();
    }
}
