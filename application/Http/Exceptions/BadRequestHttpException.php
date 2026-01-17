<?php

declare(strict_types=1);

namespace Application\Http\Exceptions;

use Exception;

/**
 * 400 Bad Request例外
 * リクエストが無効な形式または不正なパラメータを含む場合に使用
 */
class BadRequestHttpException extends HttpException
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
            httpStatus: 400,
            type: 'https://datatracker.ietf.org/doc/html/rfc9110#section-15.5.1',
            title: 'Bad Request',
            detail: $detail,
            instance: $instance,
            extensions: $extensions,
            previous: $previous
        );
    }
}
