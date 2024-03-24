<?php

namespace GustavoSantarosa\HandlerBasicsExtension\Exceptions;

use Exception;
use Throwable;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use GustavoSantarosa\HandlerBasicsExtension\Traits\ApiResponseTrait;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class BaseHandler extends ExceptionHandler
{
    use ApiResponseTrait;

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
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

    public function render($request, Throwable $e)
    {
        $callback = match(true) {
            $e instanceof ValidationException                            => $this->customResponse(status: Response::HTTP_UNPROCESSABLE_ENTITY, message: "Erro de validação!", data: $e->errors()),
            $e instanceof \TypeError && isset($e->getTrace()[0]['args']) => $this->customResponse(status: Response::HTTP_BAD_REQUEST, message: "Tipo inválido para o parâmetro ".$e->getTrace()[0]['args'][0]),
            $e instanceof ModelNotFoundException                         => $this->customResponse(status: Response::HTTP_NOT_FOUND, message: "Sem resultados para a sua pesquisa!"),
            $e instanceof NotFoundHttpException                          => $this->customResponse(status: Response::HTTP_NOT_FOUND, message: $e->getMessage()),
            $e instanceof HttpException                                  => $this->customResponse(status: $e->getStatusCode(), message: $e->getMessage()),
            !config('app.debug')                                         => $this->customResponse(status: Response::HTTP_SERVICE_UNAVAILABLE, message: "A API está temporariamente em manutenção, tente novamente mais tarde!"),
            default => false,
        };

        if($callback instanceof JsonResponse) {
            return $callback;
        }

        return parent::render($request, $e);
    }
}
