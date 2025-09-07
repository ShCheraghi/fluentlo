<?php

namespace App\Services\AI\DTO;

class AIResult
{
    public function __construct(
        public bool    $success,
        public ?array  $data     = null,
        public ?string $error    = null,
        public int     $status   = 0,
        public array   $headers  = [],
        public array   $meta     = [],
    ) {}

    public function ok(): bool { return $this->success; }
}
