<?php
declare(strict_types=1);

namespace App\Services\AI;

use Illuminate\Support\Manager;

class AIManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return $this->config->get('ai.default_driver', 'chatgpt26');
    }

    protected function createRapidapiSttDriver(): \App\Services\AI\Drivers\RapidApiSTTDriver
    {
        return new \App\Services\AI\Drivers\RapidApiSTTDriver(
            $this->config->get('ai.drivers.rapidapi_stt', [])
        );
    }

    protected function createChatgpt26Driver(): \App\Services\AI\Drivers\ChatGpt26Driver
    {
        return new \App\Services\AI\Drivers\ChatGpt26Driver(
            $this->config->get('ai.drivers.chatgpt26', [])
        );
    }
}
