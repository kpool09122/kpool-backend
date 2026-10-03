<?php

declare(strict_types=1);

namespace Source\SiteManagement\User\Application\Service;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface IdentityWithdrawalServiceInterface
{
    public function withdraw(IdentityIdentifier $identityIdentifier): void;
}
