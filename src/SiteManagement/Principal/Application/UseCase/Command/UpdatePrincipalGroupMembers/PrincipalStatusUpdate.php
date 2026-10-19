<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalStatus;

readonly class PrincipalStatusUpdate
{
    public function __construct(
        public PrincipalIdentifier $principalIdentifier,
        public PrincipalStatus $status,
    ) {
    }
}
