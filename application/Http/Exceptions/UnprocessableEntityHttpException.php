<?php

declare(strict_types=1);

namespace Application\Http\Exceptions;

use Exception;

/**
 * 422 Unprocessable Entity例外
 * リクエストの形式は正しいがセマンティックエラーがある場合に使用
 */
class UnprocessableEntityHttpException extends HttpException
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
            httpStatus: 422,
            type: 'https://datatracker.ietf.org/doc/html/rfc4918#section-11.2',
            title: 'Unprocessable Entity',
            detail: $detail,
            instance: $instance,
            extensions: $extensions,
            previous: $previous
        );
    }
}
