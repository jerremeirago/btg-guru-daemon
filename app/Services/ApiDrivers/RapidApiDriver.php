<?php

namespace App\Services\ApiDrivers;

use App\Services\Facade\ApiDriverInterface;

class RapidApiDriver implements ApiDriverInterface
{
    private const DRIVER = 'rapidapi';

    private function getConfig(string $key): array
    {
        return config('api.endpoints.'.self::DRIVER.".$key");
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
        return [
            'x-rapidapi-host' => $this->getConfig('endpoint'),
            'x-rapidapi-key' => $this > getApiKey(),
        ];
    }
}
