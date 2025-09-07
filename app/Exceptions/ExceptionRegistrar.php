<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\{HttpExceptionInterface,
    MethodNotAllowedHttpException,
    NotFoundHttpException};
use Throwable;

final class ExceptionRegistrar
{
    public static function register(Exceptions $exceptions): void
    {
        // هر درخواست API یا درخواست JSON → خروجی JSON
        $exceptions->shouldRenderJsonWhen(function ($request, Throwable $e) {
            // اگر همهٔ APIها زیر v1/... هستن، این کافیه
            // اگر ساختارت فرق داره، اینجا رو با الگوی خودت عوض کن.
            return $request->is('v1/*') || $request->expectsJson();
        });

        // 422: Validation
        $exceptions->render(function (ValidationException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => __('messages.validation_error'),
                'errors' => $e->errors(),
            ], 422);
        });

        // 401: Unauthenticated
        $exceptions->render(function (AuthenticationException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => __('auth.unauthenticated'),
            ], 401);
        });

        // 403: Forbidden
        $exceptions->render(function (AuthorizationException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => __('auth.forbidden'),
            ], 403);
        });

        // 404: Not Found (مدل/مسیر)
        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => __('messages.not_found'),
            ], 404);
        });

        // 405: Method Not Allowed
        $exceptions->render(function (MethodNotAllowedHttpException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => __('messages.method_not_allowed'),
            ], 405);
        });

        // 429: Too Many Requests (به همراه هدرهای Retry-After)
        $exceptions->render(function (ThrottleRequestsException $e, $request) {
            return response()->json([
                'success' => false,
                'message' => __('messages.too_many_requests'),
            ], 429, $e->getHeaders());
        });

        // سایر HttpExceptionها با status مشخص
        $exceptions->render(function (HttpExceptionInterface $e, $request) {
            $status = $e->getStatusCode();
            $message = $e->getMessage() ?: match ($status) {
                400 => __('messages.bad_request'),
                401 => __('auth.unauthenticated'),
                403 => __('auth.forbidden'),
                404 => __('messages.not_found'),
                405 => __('messages.method_not_allowed'),
                409 => __('messages.conflict'),
                422 => __('messages.validation_error'),
                429 => __('messages.too_many_requests'),
                default => __('messages.error'),
            };

            return response()->json([
                'success' => false,
                'message' => $message,
            ], $status, $e->getHeaders());
        });

        // 500: هر چیز دیگر
        $exceptions->render(function (Throwable $e, $request) {
            $payload = [
                'success' => false,
                'message' => __('messages.server_error'),
            ];

            if (config('app.debug')) {
                $payload['exception'] = class_basename($e);
                $payload['detail'] = $e->getMessage();
            }

            return response()->json($payload, 500);
        });
    }
}
