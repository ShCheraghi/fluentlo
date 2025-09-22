<?php

namespace App\Services\AI\Exceptions;

use Exception;

class AIException extends Exception
{
    protected $details;

    public function __construct(string $message = "", int $code = 0, \Throwable $previous = null, array $details = [])
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

    /**
     * برای لاگ کردن جزئیات بیشتر
     */
    public function getContext(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'details' => $this->details,
            'trace' => $this->getTraceAsString()
        ];
    }

    /**
     * برای نمایش خطا به کاربر
     */
    public function getUserMessage(): string
    {
        switch ($this->getCode()) {
            case 400:
                return 'درخواست نامعتبر';
            case 401:
                return 'خطا در احراز هویت';
            case 403:
                return 'دسترسی غیرمجاز';
            case 429:
                return 'تعداد درخواست‌ها بیش از حد مجاز';
            case 500:
                return 'خطای سرور';
            case 28: // timeout
                return 'زمان انتظار تمام شد، لطفاً دوباره تلاش کنید';
            default:
                return 'خطا در ارتباط با سرویس هوش مصنوعی';
        }
    }
}
