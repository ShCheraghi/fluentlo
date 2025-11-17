<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class BaseController extends Controller
{
    /**
     * Standard response structure for success.
     */
    protected function sendResponse(
        mixed  $data = null,
        string $messageKey = 'success.general',
        int    $status = Response::HTTP_OK
    ): JsonResponse
    {
        return $this->sendJsonResponse(true, $messageKey, $data, $status);
    }

    /**
     * Standard response structure for errors.
     */
    protected function sendError(
        string $messageKey,
        array  $data = [],
        int    $status = Response::HTTP_BAD_REQUEST
    ): JsonResponse
    {
        return $this->sendJsonResponse(false, $messageKey, null, $status, $data);
    }

    /**
     * Standard response structure for validation errors.
     */
    protected function sendValidationError(array $errors): JsonResponse
    {
        return $this->sendJsonResponse(false, __('validation.failed'), null, Response::HTTP_UNPROCESSABLE_ENTITY, $errors);
    }

    /**
     * Helper method to send JSON responses.
     */
    private function sendJsonResponse(
        bool   $success,
        string $messageKey,
        mixed  $data = null,
        int    $status = Response::HTTP_OK,
        array  $extraData = []
    ): JsonResponse
    {
        $response = array_merge([
            'success' => $success,
            'message' => __($messageKey),
            'data' => $data,
            'timestamp' => now()->toDateTimeString(),
        ], $extraData);

        return response()->json($response, $status)
            ->header('Content-Type', 'application/json');
    }
}
