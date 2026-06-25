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
    protected function success(mixed $data = null, string $message = 'Success.', int $code = Response::HTTP_OK): JsonResponse
    {
        $response = ['message' => $message];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }

    /**
     * Return a paginated response using a ResourceCollection.
     * Envelope: { message, data, meta, links }
     */
    protected function paginated(ResourceCollection $collection, string $message = 'Resources retrieved.'): JsonResponse
    {
        $paginated = $collection->response()->getData(true);

        return response()->json([
            'message' => $message,
            'data' => $paginated['data'],
            'meta' => $paginated['meta'] ?? null,
            'links' => $paginated['links'] ?? null,
        ]);
    }

    /**
     * Return a created response.
     */
    protected function created(mixed $data = null, string $message = 'Resource created successfully.'): JsonResponse
    {
        return $this->success($data, $message, Response::HTTP_CREATED);
    }

    /**
     * Return a no-content response.
     */
    protected function noContent(string $message = 'Resource deleted successfully.'): JsonResponse
    {
        return response()->json(['message' => $message], Response::HTTP_NO_CONTENT);
    }

    /**
     * Return an error response.
     */
    protected function error(string $message = 'An error occurred.', int $code = Response::HTTP_BAD_REQUEST, ?array $errors = null): JsonResponse
    {
        $response = ['message' => $message];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Return a not found response.
     */
    protected function notFound(string $message = 'Resource not found.'): JsonResponse
    {
        return $this->error($message, Response::HTTP_NOT_FOUND);
    }

    /**
     * Return an unauthorized response.
     */
    protected function unauthorized(string $message = 'Unauthorized.'): JsonResponse
    {
        return $this->error($message, Response::HTTP_UNAUTHORIZED);
    }
}
