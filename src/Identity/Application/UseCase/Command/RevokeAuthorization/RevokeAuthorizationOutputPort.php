<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\RevokeAuthorization;

interface RevokeAuthorizationOutputPort
{
    /** @return array{} */
    public function toArray(): array;
}
