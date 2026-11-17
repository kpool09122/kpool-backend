<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

use Source\Identity\Application\Exception\InvalidAuthorizationRequestException;

interface AuthorizationRequestValidatorInterface
{
    /** @throws InvalidAuthorizationRequestException */
    public function validate(AuthorizationCodeParameters $authorizationCodeParameters): string;
}
