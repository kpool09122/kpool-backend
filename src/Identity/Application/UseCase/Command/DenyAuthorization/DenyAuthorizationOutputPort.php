<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\DenyAuthorization;

interface DenyAuthorizationOutputPort
{
    public function setRedirectUri(string $redirectUri): void;
}
