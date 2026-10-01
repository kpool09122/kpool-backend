<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\Service;

use DateTimeImmutable;
use Source\Account\Account\Domain\Exception\IdentityWithdrawalNotAllowedException;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface IdentityWithdrawalServiceInterface
{
    /** @throws IdentityWithdrawalNotAllowedException */
    public function withdraw(IdentityIdentifier $identityIdentifier, DateTimeImmutable $archivedAt): void;
}
