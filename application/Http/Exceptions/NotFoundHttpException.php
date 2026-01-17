<?php

declare(strict_types=1);

namespace Application\Http\Exceptions;

use Exception;

/**
 * 404 Not Found例外
 * 要求されたリソースが見つからない場合に使用
 */
class NotFoundHttpException extends HttpException
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
            httpStatus: 404,
            type: 'https://datatracker.ietf.org/doc/html/rfc9110#section-15.5.5',
            title: 'Not Found',
            detail: $detail,
            instance: $instance,
            extensions: $extensions,
            previous: $previous
        );
    }
}
