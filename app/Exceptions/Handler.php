<?php

namespace App\Exceptions;

use App\Traits\ApiResponseTrait;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    use ApiResponseTrait;
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
//            $ignoreable_exception_messages = ['Unauthenticated or Token Expired, Please Login'];
//            $ignoreable_exception_messages[] = 'The refresh token is invalid.';
//            $ignoreable_exception_messages[] = 'The resource owner or authorization server denied the request.';
//            if (app()->bound('sentry') && $this->shouldReport($e)) {
//                if (!in_array($e->getMessage(), $ignoreable_exception_messages)) {
//                    app('sentry')->captureException($e);
//                }
//            }

        });

        $this->renderable(fn (NotFoundHttpException $e, $request) => $this->respondNotFound());

        $this->renderable(fn (ValidationException $e, $request) => $this->respondValidationErrors($e));

        $this->renderable(fn (ModelNotFoundException $e, $request) => $this->respondModelNotFound($e));

        $this->renderable(fn (AuthenticationException $e, $request) => $this->respondUnauthenticated());

        $this->renderable(fn (ThrottleRequestsException $e, $request) => $this->respondTooManyRequests());

        $this->renderable(fn (PostTooLargeException $e, $request) => $this->respondPayloadTooLarge());

        $this->renderable(fn (HttpException $e, $request) => $this->respondHttpError($e));

        $this->renderable(fn (QueryException $e, $request) => $this->respondQueryError($e));

        $this->renderable(fn (\Error $e, $request) => $this->respondInternalError($e));
    }

    public function rbender($request, Throwable $exception)
    {
//        dd(get_class($exception));
        if ($request->expectsJson()) {
            if ($exception instanceof PostTooLargeException) {
                return $this->apiResponse(
                    [
                        'success' => false,
                        'message' => "Size of attached file should be less " . ini_get("upload_max_filesize") . "B"
                    ],
                    400
                );
            }
            if ($exception instanceof AuthenticationException) {
                return $this->apiResponse(
                    [
                        'success' => false,
                        'message' => 'Unauthenticated or Token Expired, Please Login'
                    ],
                    401
                );
            }
            if ($exception instanceof ThrottleRequestsException) {
                return $this->apiResponse(
                    [
                        'success' => false,
                        'message' => 'Too Many Requests,Please Slow Down'
                    ],
                    429
                );
            }
            if ($exception instanceof ModelNotFoundException) {
                return $this->apiResponse(
                    [
                        'success' => false,
                        'message' => 'Entry for ' . str_replace('App\\', '', $exception->getModel()) . ' not found'
                    ],
                    404
                );
            }
            if ($exception instanceof QueryException) {

                return $this->apiResponse(
                    [
                        'success' => false,
                        'message' => 'There was Issue with the Query',
                        'exception' => $exception

                    ],
                    500
                );
            }
            if ($exception instanceof HttpException) {
                 // $exception = $exception->getResponse();
                 return $this->apiResponse(
                     [
                         'success' => false,
                         'message' => $exception->getMessage(),
                         'exception'  => $exception
                     ],
                     $exception->getStatusCode()
                 );
             }
            if ($exception instanceof \Error) {
                // $exception = $exception->getResponse();
                return $this->apiResponse(
                    [
                        'success' => false,
                        'message' => $exception->getMessage(),
                        'exception' => $exception
                    ],
                    500
                );
            }
        }


        return parent::render($request, $exception);
    }
}
