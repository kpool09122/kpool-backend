<?php

declare(strict_types=1);

namespace Application\Models\Identity;

use Illuminate\Support\Carbon;
use Laravel\Passport\Token;

/**
 * @property string $authorization_connection_id
 * @property list<string> $scopes
 * @property string $user_id
 * @property Carbon $expires_at
 */
class McpAccessToken extends Token
{
}
