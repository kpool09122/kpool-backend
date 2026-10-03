<?php

declare(strict_types=1);

namespace Application\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies;
use Override;

class EncryptXsrfTokenCookie extends EncryptCookies
{
    /** @param string $name */
    #[Override]
    public function isDisabled($name): bool
    {
        // The existing API session cookie contract stays unchanged.
        return $name !== 'XSRF-TOKEN';
    }
}
