<?php

declare(strict_types=1);

namespace Application\Passport;

use Laravel\Passport\Passport;
use League\OAuth2\Server\CryptKey;

final class SigningKeys
{
    /** @param 'private'|'public' $type */
    public static function load(string $type): CryptKey
    {
        $configured = config('passport.' . $type . '_key');
        $key = is_string($configured) && $configured !== ''
            ? str_replace('\\n', "\n", $configured)
            : 'file://' . Passport::keyPath('oauth-' . $type . '.key');

        return new CryptKey($key, null, Passport::$validateKeyPermissions);
    }
}
