<?php

namespace GustavoSantarosa\HandlerBasicsExtension\Traits;

use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use GustavoSantarosa\HandlerBasicsExtension\Exceptions\ApiResponseException;

trait ApiResponseTrait
{
    /**
     * OkResponse function.
     */
    public function okResponse(
        array|object $data = [],
        string $message = null,
        array $arrayToAppend = [],
        bool $allowedInclude = false
    ): JsonResponse {
        return response()->json(
            $this->customResponse(
                success: true,
                message: $message ?? __('messages.successfully.show'),
                data: (object) $data,
                arrayToAppend: $arrayToAppend,
                allowedInclude: $allowedInclude
            ),
            Response::HTTP_OK,
        );
    }

    /**
     * BadRequestResponse function.
     */
    public function badRequestResponse(string $message = null): void
    {
        $this->exceptionResponse(
            Response::HTTP_BAD_REQUEST,
            $this->customResponse(
                success: false,
                message: $message ?? __('Bad Request')
            ),
        );
    }

    /**
     * ForbiddenResponse function.
     */
    public function forbiddenResponse(string $message = null): void
    {
        $this->exceptionResponse(
            Response::HTTP_FORBIDDEN,
            $this->customResponse(
                success: false,
                message: $message ?? __('Forbidden')
            ),
        );
    }

    /**
     * UnauthorizedResponse function.
     */
    public function unauthorizedResponse(string $message = null): void
    {
        $this->exceptionResponse(
            Response::HTTP_UNAUTHORIZED,
            $this->customResponse(
                success: false,
                message: $message ?? __('messages.successfully.show')
            ),
        );
    }

    /**
     * NotFoundResponse function.
     */
    public function notFoundResponse(string $message = null, array $data = [], array $arrayToAppend = []): void
    {
        $this->exceptionResponse(
            Response::HTTP_NOT_FOUND,
            $this->customResponse(
                success: false,
                message: $message ?? __('messages.errors.notfound'),
                data: (object) $data,
                arrayToAppend: $arrayToAppend
            ),
        );
    }

    /**
     * UnprocessableEntityResponse function.
     */
    public function unprocessableEntityResponse(
        string $message = null,
        array $data = [],
        array $arrayToAppend = []
    ): void {
        $this->exceptionResponse(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $this->customResponse(
                success: false,
                message: $message ?? __('messages.errors.validation'),
                data: (object) $data,
                arrayToAppend: $arrayToAppend
            )
        );
    }

    /**
     * InternalServerErrorResponse function
     */
    public function internalServerErrorResponse(
        string $message = null,
        array $data = [],
        array $arrayToAppend = []
    ): void {
        $this->exceptionResponse(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $this->customResponse(
                success: false,
                message: $message ?? __('A API está temporariamente em manutenção, tente novamente mais tarde!'),
                data: (object) $data,
                arrayToAppend: $arrayToAppend
            )
        );
    }

    /**
     * AbortResponse function.
     */
    public function abortResponse(int $code = 0, string $message = null): void
    {
        $this->exceptionResponse(
            $code,
            $this->customResponse(
                success: false,
                message: $message,
            )
        );
    }

    /**
     * CustomResponse function.
     */
    public function customResponse(
        bool $success,
        string $message = null,
        object $data = null,
        array $arrayToAppend = [],
        bool $allowedInclude = false
    ): array {
        $content = [
            'success' => $success,
            'message' => $message,
        ];

        if ($allowedInclude) {
            $content['allowed_includes'] = [];
        }

        $content += $arrayToAppend;

        if ($data) {
            $content += [
                'data' => $data,
            ];

            if (isset($data->resource) && $data->resource instanceof LengthAwarePaginator) {
                $content['pagination'] = [
                    'total'        => $data->total(),
                    'current_page' => $data->currentPage(),
                    'next_page'    => $data->hasMorePages() ? $data->currentPage() + 1 : null,
                    'last_page'    => $data->lastPage(),
                    'per_page'     => $data->perPage(),
                    'is_last_page' => !$data->hasMorePages(),
                ];
            }

            if (isset($this->defaultService) && $this->defaultService->getModel()) {
                if ($allowedInclude) {
                    $content['allowed_includes'] = $this->defaultService->getModel()->allowedIncludes;
                }
            }
        }

        return $content;
    }

    /**
     * ExceptionResponse function.
     *
     * throw new ApiResponseException(code: $code, apiResponse: $content);
     */
    public function exceptionResponse(int $code, array $content): void
    {
        throw new ApiResponseException(code: $code, apiResponse: $content);
    }
}
