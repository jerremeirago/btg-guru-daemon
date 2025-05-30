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
        'response'
    ];

    protected $casts = [
        'response' => 'array'
    ];

    public function scopeGetLatestData($query)
    {
        return $query->orderBy('updated_at', 'desc')->first();
    }
}
