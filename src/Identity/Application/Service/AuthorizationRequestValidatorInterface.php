<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

use Source\Identity\Application\Exception\InvalidAuthorizationConsentException;

interface AuthorizationRequestValidatorInterface
{
    /** @throws InvalidAuthorizationConsentException */
    public function validate(AuthorizationCodeParameters $authorizationCodeParameters): string;
}
