<?php

declare(strict_types=1);

namespace Application\Http\Client\GeminiClient\Exceptions;

use Application\Http\Client\Foundation\Exceptions\ClientException;
use RuntimeException;

final class GeminiException extends RuntimeException implements ClientException
{
}
