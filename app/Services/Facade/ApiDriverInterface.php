<?php

namespace App\Services\Facade;

interface ApiDriverInterface
{
    public function getDriver(): string;

    public function getHost(): string;

    public function getApiKey(): string;

    public function getRequestHeaders(): array;
}
