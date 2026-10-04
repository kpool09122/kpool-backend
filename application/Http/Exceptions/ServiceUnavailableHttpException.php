<?php

declare(strict_types=1);

namespace Application\Http\Exceptions;

use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ServiceUnavailableHttpException extends HttpException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct(
            httpStatus: Response::HTTP_SERVICE_UNAVAILABLE,
            title: 'Service Unavailable',
            detail: 'The service is temporarily unavailable.',
            extensions: ['code' => 'service_unavailable'],
            previous: $previous,
        );
    }
}
