<?php

declare(strict_types=1);

namespace Application\Http\Exceptions;

use Exception;

/**
 * 409 Conflict例外
 * リクエストがリソースの現在の状態と競合する場合に使用
 */
class ConflictHttpException extends HttpException
{
    /**
     * @param array<string, mixed> $extensions
     */
    public function __construct(
        ?string $detail = null,
        ?string $instance = null,
        array $extensions = [],
        ?Exception $previous = null
    ) {
        parent::__construct(
            httpStatus: 409,
            type: 'https://datatracker.ietf.org/doc/html/rfc9110#section-15.5.10',
            title: 'Conflict',
            detail: $detail,
            instance: $instance,
            extensions: $extensions,
            previous: $previous
        );
    }
}
