<?php
declare(strict_types=1);

namespace App\Services\AI\Contracts;

interface AIServiceInterface
{
    public function driver(?string $driver = null): AIDriverInterface;
}
