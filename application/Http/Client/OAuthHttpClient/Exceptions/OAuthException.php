<?php

declare(strict_types=1);

namespace Application\Http\Client\OAuthHttpClient\Exceptions;

use Application\Http\Client\Foundation\Exceptions\ClientException;
use RuntimeException;

final class OAuthException extends RuntimeException implements ClientException
{
}
