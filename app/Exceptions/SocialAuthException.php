<?php

namespace App\Exceptions;

use Exception;

class SocialAuthException extends Exception
{
    public function __construct(string $message = 'Social authentication failed', int $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Report the exception.
     */
    public function report(): bool
    {
        // Log the exception but don't send to external services
        return false;
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $this->getMessage(),
                'data' => []
            ], 422);
        }

        // For web requests, redirect back with error
        return redirect()->back()->withErrors(['social_auth' => $this->getMessage()]);
    }
}
