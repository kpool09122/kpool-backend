<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use Source\Account\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class PrincipalStatusUpdate
{
    public function __construct(
        public PrincipalIdentifier $principalIdentifier,
        public PrincipalStatus $status,
    ) {
    }
}
