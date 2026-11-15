<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\ApproveAuthorization;

interface ApproveAuthorizationOutputPort
{
    public function setRedirectUri(string $redirectUri): void;
}
