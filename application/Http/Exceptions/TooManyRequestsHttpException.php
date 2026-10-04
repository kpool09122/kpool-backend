<?php

declare(strict_types=1);

namespace Application\Http\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class TooManyRequestsHttpException extends HttpException
{
    public function __construct(public readonly int $retryAfter)
    {
        parent::__construct(
            httpStatus: Response::HTTP_TOO_MANY_REQUESTS,
            title: 'Too Many Requests',
            detail: 'Too many requests. Please retry later.',
            extensions: ['code' => 'rate_limit_exceeded'],
        );
    }

    /** @return array<string, string> */
    public function getHeaders(): array
    {
        return ['Retry-After' => (string) $this->retryAfter];
    }
}
