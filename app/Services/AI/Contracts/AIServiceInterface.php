<?php
namespace App\Services\AI\Contracts;

interface AIServiceInterface
{
    /**
     * @param string|null $driver
     * @return AIDriverInterface
     */
    public function driver($driver = null);
}
