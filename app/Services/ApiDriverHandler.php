<?php

namespace App\Services;

use App\Services\Facade\ApiDriverInterface;
use App\Services\Facade\ApiInterface;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

use function http_build_query;

final class ApiDriverHandler implements ApiInterface
{
    private const REQUEST_TIMEOUT = 30;

    protected string $method = 'GET';

    protected array $payload = [];

    protected string $uri = '';

    protected $headers = [];

    protected Response $response;

    public function __construct(
        protected ApiDriverInterface $driver
    ) {}

    public function get(): self
    {
        $this->method = 'GET';

        return $this;
    }

    public function post(): self
    {
        $this->method = 'POST';

        return $this;
    }

    public function payload(array $data = []): self
    {
        $this->payload = $data;

        return $this;
    }


    public function send(): self
    {
        $url = $this->prepareUrl();
        $method = $this->method;
        $this->response = Http::withHeaders($this->driver->getRequestHeaders())
            ->timeout(self::REQUEST_TIMEOUT)
            ->$method($url);

        return $this;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getDriver(): ApiDriverInterface
    {
        return $this->driver;
    }

    public function setHeader(array $headers = []): self
    {
        $this->headers = $headers;

        return $this;
    }

    public function uri(string $uri): self
    {
        $this->uri = $uri;
        return $this;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    private function prepareUrl(): string
    {
        $url = $this->driver->getHost();

        if (!empty($payload) && is_array($payload)) {
            $url .= '?' . http_build_query($payload);
        }

        return $url . $this->uri . '?json=1';
    }
}
