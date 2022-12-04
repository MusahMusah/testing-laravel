<?php

namespace App\Traits;

use App\Http\Resources\Ghost\EmptyResource;
use App\Http\Resources\Ghost\EmptyResourceCollection;
use Error;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait ApiResponseTrait
{
    /**
     * @param JsonResource $resource
     * @param null $message
     * @param int $statusCode
     * @param array $headers
     * @return JsonResponse
     */
    protected function respondWithResource(JsonResource $resource, $message = null, int $statusCode = 200, array $headers = []): JsonResponse
    {
        // https://laracasts.com/discuss/channels/laravel/pagination-data-missing-from-api-resource

        return $this->apiResponse(
            [
                'success' => true,
                'data' => $resource,
                'message' => $message
            ], $statusCode, $headers
        );
    }

    /**
     * @param array $data
     * @param int $statusCode
     * @param array $headers
     * @return array
     */
    public function parseGivenData(array $data = [], int $statusCode = 200, array $headers = []): array
    {
        $responseStructure = [
            'success' => $data['success'],
            'message' => $data['message'] ?? null,
            'data' => $data['data'] ?? null,
        ];
        if (isset($data['errors'])) {
            $responseStructure['errors'] = $data['errors'];
        }
        if (isset($data['status'])) {
            $statusCode = $data['status'];
        }


        if (isset($data['exception']) && ($data['exception'] instanceof Error || $data['exception'] instanceof Exception)) {
            if (config('app.env') !== 'production') {
                $responseStructure['exception'] = [
                    'message' => $data['exception']->getMessage(),
                    'file' => $data['exception']->getFile(),
                    'line' => $data['exception']->getLine(),
                    'code' => $data['exception']->getCode(),
                    'trace' => $data['exception']->getTrace(),
                ];
            }

            if ($statusCode === 200) {
                $statusCode = 500;
            }
        }
        if ($data['success'] === false) {
            if (isset($data['error_code'])) {
                $responseStructure['error_code'] = $data['error_code'];
            } else {
                $responseStructure['error_code'] = 1;
            }
        }
        return ["content" => $responseStructure, "statusCode" => $statusCode, "headers" => $headers];
    }


    /*
     *
     * Just a wrapper to facilitate abstract
     */

    /**
     * Return generic json response with the given data.
     *
     * @param array $data
     * @param int $statusCode
     * @param array $headers
     *
     * @return JsonResponse
     */
    protected function apiResponse(array $data = [], int $statusCode = 200, array $headers = [])
    {
        // https://laracasts.com/discuss/channels/laravel/pagination-data-missing-from-api-resource

        $result = $this->parseGivenData($data, $statusCode, $headers);


        return response()->json(
            $result['content'], $result['statusCode'], $result['headers']
        );
    }

    /*
     *
     * Just a wrapper to facilitate abstract
     */

    /**
     * @param ResourceCollection $resourceCollection
     * @param null $message
     * @param int $statusCode
     * @param array $headers
     * @return JsonResponse
     */
    protected function respondWithResourceCollection(ResourceCollection $resourceCollection, $message = null, int $statusCode = 200, array $headers = [])
    {

        // https://laracasts.com/discuss/channels/laravel/pagination-data-missing-from-api-resource

        return $this->apiResponse(
            [
                'success' => true,
                'data' => $resourceCollection->response()->getData()
            ], $statusCode, $headers
        );
    }

    /**
     * Respond with success data.
     * @param JsonResource|Collection|LengthAwarePaginator $resource
     * @param null $message
     * @param int $statusCode
     * @param array $headers
     * @return JsonResponse
     */
    protected function respondWithSuccess(JsonResource|Collection|LengthAwarePaginator $resource, $message = null, int $statusCode = 200, array $headers = []): JsonResponse
    {
        return $this->apiResponse(
            [
                'success' => true,
                'data' => $resource,
                'message' => $message
            ], $statusCode, $headers
        );
    }

    /**
     * Respond with created.
     *
     * @param $data
     *
     * @return JsonResponse
     */
    protected function respondCreated($data)
    {
        return $this->apiResponse($data, 201);
    }

    /**
     * Respond with no content.
     *
     * @param string $message
     *
     * @return JsonResponse
     */
    protected function respondNoContent(string $message = 'No Content Found')
    {
        return $this->apiResponse(['success' => false, 'message' => $message]);
    }

    /**
     * Respond with no content.
     *
     * @param string $message
     *
     * @return JsonResponse
     */
    protected function respondNoContentResource(string $message = 'No Content Found')
    {
        return $this->respondWithResource(new EmptyResource([]), $message);
    }
    /**
     * Respond with no content.
     *
     * @param string $message
     *
     * @return JsonResponse
     */
    protected function respondNoContentResourceCollection(string $message = 'No Content Found')
    {
        return $this->respondWithResourceCollection(new EmptyResourceCollection([]), $message);
    }

    /**
     * Respond with unauthorized.
     *
     * @param string $message
     *
     * @return JsonResponse
     */
    protected function respondUnAuthorized(string $message = 'Unauthorized')
    {
        return $this->respondWithError($message, 401);
    }

    public function respondUnauthenticated(string $message = 'Unauthenticated or Token Expired, Please Login'): JsonResponse
    {
        return $this->respondWithError($message, 401);
    }

    protected function respondTooManyRequests(string $message = 'Too Many Requests,Please Slow Down'): JsonResponse
    {
        return $this->respondWithError($message, 429);
    }

    protected function respondHttpError(HttpException $exception): JsonResponse
    {
        return $this->respondWithError(
            message: $exception->getMessage(),
            statusCode: $exception->getStatusCode(),
            exception: $exception,
        );
    }

    protected function respondQueryError(string $message = 'There was Issue with the Query'): JsonResponse
    {
        return $this->respondWithError($message, 500);
    }

    protected function respondPayloadTooLarge(): JsonResponse
    {
        return $this->respondWithError("Size of attached file should be less " . ini_get("upload_max_filesize") . "B", 413);
    }

    /**
     * Respond with error.
     *
     * @param string $message
     * @param int $statusCode
     *
     * @param Exception|Error|null $exception
     * @param int $error_code
     * @return JsonResponse
     */
    protected function respondWithError(string $message, int $statusCode = 400, Exception | Error $exception = null, int $error_code = 1): JsonResponse
    {
        return $this->apiResponse(
            [
                'success' => false,
                'message' => $message ?? 'There was an internal error, Pls try again later',
                'exception' => $exception,
                'error_code' => $error_code
            ], $statusCode
        );
    }

    /**
     * Respond with forbidden.
     *
     * @param string $message
     *
     * @return JsonResponse
     */
    protected function respondForbidden(string $message = 'Forbidden')
    {
        return $this->respondWithError($message, 403);
    }

    /**
     * Respond with not found.
     *
     * @param string $message
     *
     * @return JsonResponse
     */
    protected function respondNotFound(string $message = 'The specified URL cannot be found'): JsonResponse
    {
        return $this->respondWithError($message, 404);
    }

    protected function respondInternalError(\Error $exception): JsonResponse
    {
        return $this->respondWithError(
            message: $exception->getMessage(),
            statusCode: 500,
            exception: $exception,
        );
    }

    protected function respondValidationErrors(ValidationException $exception): JsonResponse
    {
        return $this->apiResponse(
            [
                'success' => false,
                'message' => $exception->getMessage(),
                'errors' => $exception->errors()
            ],
            422
        );
    }

    /**
     * Respond with model not found.
     * @param ModelNotFoundException $exception
     *
     * @return JsonResponse
     */
    protected function respondModelNotFound(ModelNotFoundException $exception): JsonResponse
    {
        return $this->respondNotFound(message: 'Entry for ' . str_replace('App\\', '', $exception->getModel()) . ' not found');
    }
}
