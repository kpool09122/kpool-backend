<?php

declare(strict_types=1);

namespace Source\Account\Application\UseCase\Command\GrantDelegationPermission;

use Source\Account\Domain\ValueObject\AffiliationIdentifier;
use Source\Account\Domain\ValueObject\IdentityGroupIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface GrantDelegationPermissionInputPort
{
    public function identityGroupIdentifier(): IdentityGroupIdentifier;

    public function targetAccountIdentifier(): AccountIdentifier;

    public function affiliationIdentifier(): AffiliationIdentifier;
}
