<?php

declare(strict_types=1);

namespace Source\Account\DelegationPermission\Application\UseCase\Command\GrantDelegationPermission;

use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface GrantDelegationPermissionInputPort
{
    public function principalGroupIdentifier(): PrincipalGroupIdentifier;

    public function targetAccountIdentifier(): AccountIdentifier;

    public function affiliationIdentifier(): AffiliationIdentifier;
}
