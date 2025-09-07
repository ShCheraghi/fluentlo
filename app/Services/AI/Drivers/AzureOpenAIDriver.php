<?php

namespace App\Services\AI\Drivers;

class AzureOpenAIDriver extends BaseDriver
{
    protected function resolveUrl(string $operation): string
    {
        $endpoint     = rtrim($this->config['endpoint'] ?? '', '/');
        $deploymentId = $this->config['deployment_id'] ?? '';
        $apiVersion   = $this->config['api_version']   ?? '2023-12-01-preview';

        $path = "/openai/deployments/{$deploymentId}/" . str_replace('.', '/', $operation);
        $sep  = (str_contains($endpoint . $path, '?') ? '&' : '?');

        return $endpoint . $path . $sep . 'api-version=' . $apiVersion;
    }
}
