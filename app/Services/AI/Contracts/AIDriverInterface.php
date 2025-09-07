<?php

namespace App\Services\AI\Contracts;

use App\Services\AI\DTO\AIResult;

interface AIDriverInterface
{
    public function get(string $operation, array $query = []): AIResult;
    public function postJson(string $operation, array $json = [], array $query = []): AIResult;
    public function postNoBody(string $operation, array $query = []): AIResult;
}
