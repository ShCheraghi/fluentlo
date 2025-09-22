<?php
declare(strict_types=1);

namespace App\Services\AI\Exceptions;

use Exception;
use Throwable;

class AIException extends Exception
{
    protected array $details = [];

    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null, array $details = [])
    {
        parent::__construct($message, $code, $previous);
        $this->details = $details;
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    public function setDetails(array $details): self
    {
        $this->details = $details;
        return $this;
    }

    public function getContext(): array
    {
        return [
            'message' => $this->getMessage(),
            'code'    => $this->getCode(),
            'file'    => $this->getFile(),
            'line'    => $this->getLine(),
            'details' => $this->details,
            'trace'   => $this->getTraceAsString(),
        ];
    }

    public function getUserMessage(): string
    {
        return match ($this->getCode()) {
            400 => 'درخواست نامعتبر',
            401 => 'خطا در احراز هویت',
            403 => 'دسترسی غیرمجاز',
            429 => 'تعداد درخواست‌ها بیش از حد مجاز',
            500 => 'خطای سرور',
            28  => 'زمان انتظار تمام شد، لطفاً دوباره تلاش کنید',
            default => 'خطا در ارتباط با سرویس هوش مصنوعی',
        };
    }
}
