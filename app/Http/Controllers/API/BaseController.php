<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class BaseController extends Controller
{
    public function sendResponse(
        mixed  $data = null,
        string $messageKey = 'success.general',
        int    $status = Response::HTTP_OK
    ): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => __($messageKey),
            'data' => $data,
        ];

        return response()->json($response, $status)
            ->header('Content-Type', 'application/json');
    }

    public function sendError(
        string $messageKey,
        array  $data = [],
        int    $status = Response::HTTP_BAD_REQUEST
    ): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => __($messageKey, $data),
            'data' => null,
        ];

        return response()->json($response, $status)
            ->header('Content-Type', 'application/json');
    }

    public function sendValidationError(array $errors): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => __('validation.failed'),
            'errors' => $errors,
        ];

        return response()->json($response, Response::HTTP_UNPROCESSABLE_ENTITY)
            ->header('Content-Type', 'application/json');
    }
}
