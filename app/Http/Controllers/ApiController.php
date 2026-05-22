<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiController extends Controller
{
    use AuthorizesRequests;

    public function sendResponse(?array $result = [], string $message = 'success', int $code = Response::HTTP_OK): JsonResponse
    {
        $metadata = $result['meta'] ?? [];
        unset($result['meta']);

        $response = array_filter([
            'success' => true,
            'meta' => $metadata,
            'message' => $message,
        ]);
        $response['data'] = $result;

        return response()->json($response, $code);
    }

    /**
     * @param int $code
     */
    public function sendError(?string $message = 'error', int $code = Response::HTTP_NOT_FOUND, $errors = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (! empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }
}
