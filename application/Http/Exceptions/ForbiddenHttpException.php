<?php

declare(strict_types=1);

namespace Application\Http\Exceptions;

use Exception;

/**
 * 403 Forbidden例外
 * 認証済みだが権限不足でアクセスが拒否される場合に使用
 */
class ForbiddenHttpException extends HttpException
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
            httpStatus: 403,
            type: 'https://datatracker.ietf.org/doc/html/rfc9110#section-15.5.4',
            title: 'Forbidden',
            detail: $detail,
            instance: $instance,
            extensions: $extensions,
            previous: $previous
        );
    }
}
