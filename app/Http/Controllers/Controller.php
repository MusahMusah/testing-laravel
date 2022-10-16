<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected int $statusCode = ResponseAlias::HTTP_OK;

    /**
     * Return JsonResponse
     * @param $data
     * @return JsonResponse
     */
    public function jsonResponse($data): JsonResponse
    {
        return response()->json(array_merge($data, ['status_code' => $this->statusCode]), $this->statusCode);
    }

    /**
     * Respond with a generic success message
     * @param $data
     * @param int $statusCode
     * @param string $message
     * @return JsonResponse
     */
    public function respondWithSuccess($data, int $statusCode = ResponseAlias::HTTP_OK, string $message = 'success'): JsonResponse
    {
        return $this->setStatusCode($statusCode)->jsonResponse(is_array($data) ?
            array_merge(['status_code' => $statusCode, 'status' => $message], $data) : ['message' => $data]);
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

    public function setStatusCode($statusCode): static
    {
        $this->statusCode = $statusCode;
        return $this;
    }
}
