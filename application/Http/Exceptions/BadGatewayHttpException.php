<?php

declare(strict_types=1);

namespace Application\Http\Exceptions;

use Symfony\Component\HttpFoundation\Response;
use Throwable;

class BadGatewayHttpException extends HttpException
{
    public function __construct(?string $detail = null, ?Throwable $previous = null)
    {
        parent::__construct(httpStatus: Response::HTTP_BAD_GATEWAY, title: 'Bad Gateway', detail: $detail, extensions: ['code' => 'video_metadata_unavailable'], previous: $previous);
    }
}
