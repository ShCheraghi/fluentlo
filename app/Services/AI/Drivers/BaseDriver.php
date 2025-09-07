<?php

namespace App\Services\AI\Drivers;

use App\Services\AI\Contracts\AIDriverInterface;
use App\Services\AI\DTO\AIResult;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

abstract class BaseDriver implements AIDriverInterface
{
    protected array $config;

    public function __construct(array $driverConfig)
    {
        $this->config = $driverConfig;
    }

    public function get(string $operation, array $query = []): AIResult
    {
        try {
            $res = $this->http()->get($this->resolveUrl($operation), $query);
            return $res->successful()
                ? new AIResult(true, $res->json(), null, $res->status(), $res->headers())
                : new AIResult(false, null, $res->body(), $res->status(), $res->headers());
        } catch (\Throwable $e) {
            return new AIResult(false, null, $e->getMessage());
        }
    }

    protected function http(): PendingRequest
    {
        $headers = $this->config['headers'] ?? [];
        $timeout = $this->config['timeout'] ?? 30;

        $req = Http::withHeaders($headers)
            ->timeout($timeout)
            ->connectTimeout(10);

        if (!empty($this->config['retry']['times']) && !empty($this->config['retry']['sleep'])) {
            $req = $req->retry($this->config['retry']['times'], $this->config['retry']['sleep'], throw: false);
        }

        return $req;
    }

    abstract protected function resolveUrl(string $operation): string;

    public function postJson(string $operation, array $json = [], array $query = []): AIResult
    {
        try {
            $res = $this->http()->withQueryParameters($query)
                ->post($this->resolveUrl($operation), $json);

            return $res->successful()
                ? new AIResult(true, $res->json(), null, $res->status(), $res->headers())
                : new AIResult(false, null, $res->body(), $res->status(), $res->headers());
        } catch (\Throwable $e) {
            return new AIResult(false, null, $e->getMessage());
        }
    }

    public function postNoBody(string $operation, array $query = []): AIResult
    {
        try {
            $res = $this->http()->withQueryParameters($query)
                ->send('POST', $this->resolveUrl($operation)); // بدون body

            return $res->successful()
                ? new AIResult(true, $res->json(), null, $res->status(), $res->headers())
                : new AIResult(false, null, $res->body(), $res->status(), $res->headers());
        } catch (\Throwable $e) {
            return new AIResult(false, null, $e->getMessage());
        }
    }
}
