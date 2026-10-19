<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\Service;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;

interface PrincipalGroupChangeGuardInterface
{
    /** @param PrincipalGroup[] $groups */
    public function assertPreserved(AccountIdentifier $accountIdentifier, array $groups): void;
}
