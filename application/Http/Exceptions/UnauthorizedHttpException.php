<?php

declare(strict_types=1);

namespace Application\Http\Exceptions;

use Exception;

/**
 * 401 Unauthorized例外
 * 認証が必要または認証情報が無効な場合に使用
 */
class UnauthorizedHttpException extends HttpException
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
            httpStatus: 401,
            type: 'https://datatracker.ietf.org/doc/html/rfc9110#section-15.5.2',
            title: 'Unauthorized',
            detail: $detail,
            instance: $instance,
            extensions: $extensions,
            previous: $previous
        );
    }
}
