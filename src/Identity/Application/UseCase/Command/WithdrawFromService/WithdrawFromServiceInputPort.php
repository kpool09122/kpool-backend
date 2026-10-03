<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\WithdrawFromService;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface WithdrawFromServiceInputPort
{
    public function identityIdentifier(): IdentityIdentifier;
}
