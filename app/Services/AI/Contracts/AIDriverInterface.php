<?php
namespace App\Services\AI\Contracts;

interface AIDriverInterface
{
    public function transcribe(array $data): array;
    public function chat(array $data): array;
}
