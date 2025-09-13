<?php

namespace App\Services\AI;

use Illuminate\Support\Manager;

class AIManager extends Manager implements \App\Services\AI\Contracts\AIServiceInterface
{
    public function getDefaultDriver(): string
    {
        return $this->config->get('ai.default');
    }

    protected function createRapidApiDriver(): \App\Services\AI\Drivers\RapidApiDriver
    {
        return new \App\Services\AI\Drivers\RapidApiDriver(
            $this->config->get('ai.drivers.rapidapi')
        );
    }

    protected function createOpenaiDriver(): \App\Services\AI\Drivers\OpenAiDriver
    {
        return new \App\Services\AI\Drivers\OpenAiDriver(
            $this->config->get('ai.drivers.openai')
        );
    }


    public function driver($driver = null): \App\Services\AI\Contracts\AIDriverInterface
    {
        return parent::driver($driver);
    }
}
