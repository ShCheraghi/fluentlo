<?php
declare(strict_types=1);

namespace App\Services\AI\Contracts;

interface AIDriverInterface
{
    public function transcribe(array $data): array;
    public function chat(array $data): array;
}
