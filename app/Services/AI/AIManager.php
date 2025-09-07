<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\AIDriverInterface;
use App\Services\AI\Drivers\AzureOpenAIDriver;
use App\Services\AI\Drivers\OpenAIDriver;
use App\Services\AI\Drivers\RapidAPIDriver;
use InvalidArgumentException;

class AIManager
{
    protected array $drivers = [];
    protected array $config = [];

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? (config('ai') ?? []);
    }

    public function driver(?string $name = null): AIDriverInterface
    {
        if (empty($this->config)) {
            throw new InvalidArgumentException("AI config is empty. Create config/ai.php and clear config cache.");
        }

        $name = $name ?: ($this->config['default'] ?? null);
        if (!$name) throw new InvalidArgumentException("AI default driver is not set.");

        if (!isset($this->drivers[$name])) {
            $this->drivers[$name] = $this->resolve($name);
        }
        return $this->drivers[$name];
    }

    protected function resolve(string $name): AIDriverInterface
    {
        $drivers = $this->config['drivers'] ?? [];
        if (!isset($drivers[$name])) throw new InvalidArgumentException("AI driver '{$name}' not configured.");
        $cfg = $drivers[$name];

        return match ($name) {
            'openai' => new OpenAIDriver($cfg),
            'azure' => new AzureOpenAIDriver($cfg),
            'rapidapi' => new RapidAPIDriver($cfg),
            default => throw new InvalidArgumentException("AI driver '{$name}' not supported."),
        };
    }
}
