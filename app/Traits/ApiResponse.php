<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Return a success JSON response.
     */
    protected function success(
        $data = null,
        string $message = null,
        int $code = 200,
    ): JsonResponse {
        $response = [];

        if ($data !== null) {
            $response["data"] = $data;
        }

        if ($message) {
            $response["message"] = $message;
        }

        return response()->json($response, $code);
    }

    /**
     * Return a created (201) JSON response.
     */
    protected function created(
        $data = null,
        string $message = "Created successfully",
    ): JsonResponse {
        return $this->success($data, $message, 201);
    }

    /**
     * Return a no content (204) JSON response.
     */
    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Return an error JSON response.
     */
    protected function error(
        string $message,
        int $code = 400,
        array $errors = null,
    ): JsonResponse {
        $response = ["message" => $message];

        if ($errors) {
            $response["errors"] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Return a not found (404) JSON response.
     */
    protected function notFound(
        string $message = "Resource not found",
    ): JsonResponse {
        return $this->error($message, 404);
    }

    /**
     * Return an unauthorized (401) JSON response.
     */
    protected function unauthorized(
        string $message = "Unauthorized",
    ): JsonResponse {
        return $this->error($message, 401);
    }

    /**
     * Return a forbidden (403) JSON response.
     */
    protected function forbidden(string $message = "Forbidden"): JsonResponse
    {
        return $this->error($message, 403);
    }

    /**
     * Return a validation error (422) JSON response.
     */
    protected function validationError(
        array $errors,
        string $message = "Validation failed",
    ): JsonResponse {
        return $this->error($message, 422, $errors);
    }

    /**
     * Return a collection JSON response (for paginated resources).
     * This method directly returns the resource collection without wrapping in data key
     * as Laravel's ResourceCollection handles pagination metadata automatically.
     */
    protected function collection($collection): JsonResponse
    {
        return $collection->response();
    }

    /**
     * Return a bulk operation JSON response.
     */
    protected function bulkResponse(
        array $results,
        string $message = "Bulk operation completed",
    ): JsonResponse {
        return $this->success(
            $results,
            $message,
        );
    }
}
