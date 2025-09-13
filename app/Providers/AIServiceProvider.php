<?php
namespace App\Providers;

use App\Services\AI\AIManager;
use Illuminate\Support\ServiceProvider;

class AIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('ai', function ($app) {
            return new AIManager($app);
        });
    }

    public function provides(): array
    {
        return ['ai'];
    }
}
