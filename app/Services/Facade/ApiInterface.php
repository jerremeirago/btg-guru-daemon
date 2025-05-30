<?php

namespace App\Services\Facade;

use App\Services\Facade\ApiDriverInterface;

/**
 * ApiInterface
 *
 * @category Api
 *
 * @method post
 * @method get
 * @method makeRequest
 * @method buildRequest
 */
interface ApiInterface
{
    public function post(): self;

    /**
     * Sets the method to "GET"
     */
    public function get(): self;

    /**
     * Triggers the call to the API
     *
     * @return self
     */
    public function send(): self;

    /**
     * Used to hydrate API with the dat provided
     *
     * @param  array<string, string>  $data
     * @return ApiIntercace
     */
    public function payload(array $data = []): self;

    public function uri(string $uri): self;

    public function getPayload(): array;

    public function getDriver(): ApiDriverInterface;

    public function setHeader(array $headers = []): self;
}
