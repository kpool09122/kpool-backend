<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\Service;

use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface PrincipalGroupChangeGuardInterface
{
    /** @param PrincipalGroup[] $groups */
    public function assertPreserved(AccountIdentifier $accountIdentifier, array $groups): void;
}
