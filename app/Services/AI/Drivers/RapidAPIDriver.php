<?php

namespace App\Services\AI\Drivers;

class RapidAPIDriver extends BaseDriver
{
    protected function resolveUrl(string $operation): string
    {
        $host = $this->config['host'] ?? '';
        return 'https://' . $host . '/' . ltrim(str_replace('.', '/', $operation), '/');
    }
}
