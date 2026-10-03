<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\Service;

use Source\Account\Account\Application\UseCase\Query\WithdrawalMemberships;
use Source\Account\Account\Domain\Exception\IdentityWithdrawalNotAllowedException;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface WithdrawalEligibilityServiceInterface
{
    public function canWithdraw(IdentityIdentifier $identityIdentifier): bool;

    /** @throws IdentityWithdrawalNotAllowedException */
    public function resolve(IdentityIdentifier $identityIdentifier): WithdrawalMemberships;
}
