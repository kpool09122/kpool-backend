<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Factory;

use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Wiki\Principal\Domain\Entity\AffiliationGrant;
use Source\Wiki\Principal\Domain\ValueObject\AffiliationGrantType;
use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;

interface AffiliationGrantFactoryInterface
{
    public function create(
        AffiliationIdentifier $affiliationIdentifier,
        PolicyIdentifier $policyIdentifier,
        RoleIdentifier $roleIdentifier,
        PrincipalGroupIdentifier $principalGroupIdentifier,
        AffiliationGrantType $type,
    ): AffiliationGrant;
}
