<?php

namespace App\Services\AI\Drivers;

class OpenAIDriver extends BaseDriver
{
    protected function resolveUrl(string $operation): string
    {
        $base = rtrim($this->config['base_url'] ?? '', '/');
        return $base . '/' . ltrim(str_replace('.', '/', $operation), '/');
    }
}
