<?php

namespace App\Services\AI\Drivers;

use App\Services\AI\Exceptions\AIException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

abstract class BaseDriver
{
    protected array $config;
    protected Client $client;

    public function __construct(array $config = [])
    {
        $this->config = $config;

        $this->client = new Client([
            'timeout' => $this->config['timeout'] ?? 30,
            'connect_timeout' => 10,
            'http_errors' => false,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'FluentLo-App/1.0',
            ],
        ]);
    }

    protected function makeRequest(string $method, string $url, array $options = []): array
    {
        try {
            $options['timeout'] = $options['timeout'] ?? $this->config['timeout'] ?? 30;
            $options['headers'] = array_merge([
                'Accept'     => 'application/json',
                'User-Agent' => 'FluentLo-App/1.0',
            ], $options['headers'] ?? []);


            $response = $this->client->request($method, $url, $options);
            $status = $response->getStatusCode();
            $body = (string)$response->getBody();

            if ($status >= 400) {
                $json = json_decode($body, true);
                $message = $json['error']['message'] ?? $json['message'] ?? $body;
                throw new AIException("API Error ({$status}): {$message}", $status);
            }

            $json = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new AIException("Invalid JSON response: " . json_last_error_msg());
            }

            return is_array($json) ? $json : ['raw' => $body];

        } catch (RequestException $e) {
            $status = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
            $body = $e->hasResponse() ? (string)$e->getResponse()->getBody() : $e->getMessage();
            throw new AIException("Request failed ({$status}): {$body}", $status, $e);
        }
    }
}
