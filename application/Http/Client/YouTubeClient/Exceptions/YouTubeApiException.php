<?php

declare(strict_types=1);

namespace Application\Http\Client\YouTubeClient\Exceptions;

use Application\Http\Client\Foundation\Exceptions\ClientException;
use RuntimeException;

final class YouTubeApiException extends RuntimeException implements ClientException
{
}
