<?php

namespace App\Services\ApiDrivers;

use App\Services\Facade\ApiDriverInterface;

class GoalServeApiDriver implements ApiDriverInterface
{
    private const DRIVER = 'goalserve';

    private function getConfig(string $key): string
    {
        $config = config('api.endpoints.' . self::DRIVER . '.' . $key);
        return $config;
    }

    public function getDriver(): string
    {
        return $this->getConfig('driver');
    }

    public function getHost(): string
    {
        return $this->getConfig('host');
    }

    public function getApiKey(): string
    {
        return $this->getConfig('api_key');
    }

    public function getRequestHeaders(): array
    {
        return [];
    }
}
