<?php

declare(strict_types=1);

namespace Source\Monetization\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalStatus;

readonly class PrincipalStatusUpdate
{
    public function __construct(
        public PrincipalIdentifier $principalIdentifier,
        public PrincipalStatus $status,
    ) {
    }
}
