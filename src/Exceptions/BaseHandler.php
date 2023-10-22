<?php

namespace GustavoSantarosa\HandlerBasicsExtension\Exceptions;

use GustavoSantarosa\HandlerBasicsExtension\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BaseHandler extends ExceptionHandler
{
    use ApiResponseTrait;

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        ApiResponseException::class,
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
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
        if (!config('app.debug')) {
            $this->reportable(function (\Throwable $e) {
                if (app()->bound('sentry')) {
                    app('sentry')->captureException($e);
                }
            });
        }
    }

    protected function renderExceptionContent(\Throwable $e)
    {
        return $this->renderExceptionWithSymfony($e, config('app.debug'));
    }

    public function render($request, \Throwable $e)
    {
        if ($e instanceof ApiResponseException) {
            return response()->json($e->getApiResponse(), $e->getCode());
        }

        match (true) {
            $e instanceof ValidationException           => $this->unprocessableEntityResponse(data: $e->errors()),
            $e instanceof MethodNotAllowedHttpException => $this->badRequestResponse(),
            $e instanceof ModelNotFoundException        => $this->notFoundResponse(),
            $e instanceof NotFoundHttpException         => $this->notFoundResponse(),
            $e instanceof HttpException                 => $this->abortResponse($e->getStatusCode(), $e->getMessage()),
            default                                     => false,
        };

        if (!config('app.debug')) {
            $this->internalServerErrorResponse();
        }

        return parent::render($request, $e);
    }
}
