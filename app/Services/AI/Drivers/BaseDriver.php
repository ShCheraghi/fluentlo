<?php
namespace App\Services\AI\Drivers;

use App\Exceptions\AIException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlMultiHandler;

abstract class BaseDriver
{
    protected array $config;
    protected Client $client;
    protected static ?HandlerStack $sharedHandler = null;

    public function __construct(array $config)
    {
        $this->config = $config;

        if (!self::$sharedHandler) {
            $multi = new CurlMultiHandler(); // connection pooling
            self::$sharedHandler = HandlerStack::create($multi);
        }

        $this->client = new Client([
            'handler'          => self::$sharedHandler,
            'read_timeout'     => 10.0,
            'http_errors'      => false,
            'version'          => 2.0,
            'headers'          => [
                'Accept'           => 'application/json',
                'Accept-Encoding'  => 'gzip, deflate, br',
                'Connection'       => 'keep-alive',
            ],
            'decode_content'   => true,
        ]);
    }

    protected function makeRequest(string $method, string $url, array $options = []): array
    {
        try {
            $response = $this->client->request($method, $url, $options);

            $status = $response->getStatusCode();
            $body   = (string) $response->getBody();
            $json   = json_decode($body, true);

            if ($status >= 400) {
                throw new AIException("Upstream error ({$status}): " . ($json['error']['message'] ?? $body), $status);
            }

            return is_array($json) ? $json : ['raw' => $body];
        } catch (RequestException $e) {
            throw new AIException("API request failed: " . $e->getMessage(), $e->getCode(), $e);
        } catch (\Exception $e) {
            throw new AIException("Unexpected error: " . $e->getMessage(), $e->getCode(), $e);
        }
    }
}
