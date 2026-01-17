<?php

declare(strict_types=1);

namespace Application\Http\Exceptions;

use Exception;
use Throwable;

class InternalServerErrorHttpException extends HttpException
{
    /**
     * @param array<string, mixed> $extensions
     */
    public function __construct(
        ?string $detail = null,
        ?string $instance = null,
        array $extensions = [],
        Exception|Throwable|null $previous = null
    ) {
        parent::__construct(
            httpStatus: 500,
            title: 'Internal Server Error',
            detail: $detail,
            instance: $instance,
            extensions: $extensions,
            previous: $previous
        );
    }
}
