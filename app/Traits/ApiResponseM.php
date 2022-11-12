<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

trait ApiResponseM
{
    protected int $statusCode = ResponseAlias::HTTP_OK;

    /**
     * Respond with a generic success message
     * @param $data
     * @param int $statusCode
     * @param string $message
     * @return JsonResponse
     */
    public function respondWithSuccess($data, string $message = 'success', int $statusCode = ResponseAlias::HTTP_OK): JsonResponse
    {
        return $this->setStatusCode($statusCode)->jsonResponse(is_array($data) ?
            array_merge(['status_code' => $statusCode, 'success' => true], $data) : ['data' => $data]);
    }

    /**
     * Respond with a generic error message
     * @param string $message
     * @param int $statusCode
     * @return mixed
     */
    public function respondWithError(string $message = 'There was an error', int $statusCode = ResponseAlias::HTTP_BAD_REQUEST): JsonResponse
    {
        return $this->setStatusCode($statusCode)->jsonResponse(['status_code' => $statusCode, 'message' => $message]);
    }

    /**
     * Return JsonResponse
     * @param $data
     * @return JsonResponse
     */
    private function jsonResponse($data): JsonResponse
    {
        return response()->json(array_merge($data, ['status_code' => $this->statusCode]), $this->statusCode);
    }

    /**
     * @param int $statusCode
     * @return $this
     */
    private function setStatusCode(int $statusCode): static
    {
        $this->statusCode = $statusCode;
        return $this;
    }
}
