<?php

namespace App\Services\Afl;

use App\Services\ApiDriverHandler;
use App\Services\Facade\ApiInterface;
use App\Services\ApiDrivers\GoalServeApiDriver;

class AflService
{
    private $api;

    public function __construct(GoalServeApiDriver $driver)
    {
        $this->api = new ApiDriverHandler($driver);
    }

    /**
     * Undocumented function
     *
     * @return array<string, string<json>>
     */
    public function getData(): array
    {
        $uri = '/afl/home?json=1';
        if (!$this->api instanceof ApiInterface) {
            return [];
        }

        $response = $this->api->get()->uri($uri)->send();

        return [
            'response_code' => $response->getResponse()->getStatusCode(),
            'response' => $response->getResponse()->json(),
            'uri' => $uri
        ];
    }
}
