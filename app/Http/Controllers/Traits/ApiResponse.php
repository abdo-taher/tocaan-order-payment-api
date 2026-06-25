<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Symfony\Component\HttpFoundation\Response;

trait ApiResponse
{
    /**
     * Return a success response with data.
     */
    protected function success(mixed $data = null, string $message = 'messages.success', int $code = Response::HTTP_OK): JsonResponse
    {
        $response = ['message' => __($message)];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }

    /**
     * Return a paginated response using a ResourceCollection.
     * Envelope: { message, data, meta, links }
     */
    protected function paginated(ResourceCollection $collection, string $message = 'messages.success'): JsonResponse
    {
        $paginated = $collection->response()->getData(true);

        return response()->json([
            'message' => __($message),
            'data' => $paginated['data'],
            'meta' => $paginated['meta'] ?? null,
            'links' => $paginated['links'] ?? null,
        ]);
    }

    /**
     * Return a created response.
     */
    protected function created(mixed $data = null, string $message = 'messages.created'): JsonResponse
    {
        return $this->success($data, $message, Response::HTTP_CREATED);
    }

    /**
     * Return a no-content response.
     */
    protected function noContent(string $message = 'messages.deleted'): JsonResponse
    {
        return response()->json(['message' => __($message)], Response::HTTP_NO_CONTENT);
    }

    /**
     * Return an error response.
     */
    protected function error(string $message = 'messages.error', int $code = Response::HTTP_BAD_REQUEST, ?array $errors = null): JsonResponse
    {
        $response = ['message' => __($message)];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Return a not found response.
     */
    protected function notFound(string $message = 'messages.not_found'): JsonResponse
    {
        return $this->error($message, Response::HTTP_NOT_FOUND);
    }

    /**
     * Return an unauthorized response.
     */
    protected function unauthorized(string $message = 'messages.unauthorized'): JsonResponse
    {
        return $this->error($message, Response::HTTP_UNAUTHORIZED);
    }
}
