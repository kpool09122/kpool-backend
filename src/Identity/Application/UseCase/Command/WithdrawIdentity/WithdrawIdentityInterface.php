<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\WithdrawIdentity;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface WithdrawIdentityInterface
{
    public function process(IdentityIdentifier $identityIdentifier): void;
}
