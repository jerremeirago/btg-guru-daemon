<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use App\Models\Types\AflRequestType;

class AflApiResponse extends Model
{
    use HasUuids;

    protected $table = 'afl_api_responses';

    public const URI_LIVE = '/afl/home?json=1';
    public const URI_SCHEDULE = '/afl/schedule?json=1';
    public const URI_STANDINGS = '/afl/standings?json=1';

    protected $fillable = [
        'uri',
        'response',
        'response_code',
        'response_time',
        'request_id',
        'round',
        'match_date',
        'request_type',
    ];

    protected $casts = [
        'response' => 'array',
        'response_code' => 'integer',
        'response_time' => 'integer',
        'round' => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->round = get_current_round()['round'];
            $model->match_date = get_current_round()['start'];
        });

        static::updating(function ($model) {
            $model->round = get_current_round()['round'];
            $model->match_date = get_current_round()['start'];
        });
    }

    public function scopeGetDataBy($query, string $uri, string $requestType)
    {
        return $query->where('uri', $uri)
            ->where('request_type', $requestType)
            ->orderBy('updated_at', 'desc')->first();
    }

    public function scopeGetLatestData($query)
    {
        return $this->scopeGetDataBy($query, self::URI_LIVE, AflRequestType::Live->name);
    }

    public function scopeGetLatestSchedule($query)
    {
        return $this->scopeGetDataBy($query, self::URI_SCHEDULE, AflRequestType::Schedules->name);
    }
}
