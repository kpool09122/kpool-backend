<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\RevokeAuthorization;

class RevokeAuthorizationOutput implements RevokeAuthorizationOutputPort
{
    public function toArray(): array
    {
        return [];
    }
}
