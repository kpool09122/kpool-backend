<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use Source\Wiki\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class PrincipalStatusUpdate
{
    public function __construct(
        public PrincipalIdentifier $principalIdentifier,
        public PrincipalStatus $status,
    ) {
    }
}
