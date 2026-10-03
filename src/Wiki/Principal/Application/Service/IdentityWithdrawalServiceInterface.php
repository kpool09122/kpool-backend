<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\Service;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface IdentityWithdrawalServiceInterface
{
    public function withdraw(IdentityIdentifier $identityIdentifier, DateTimeImmutable $archivedAt): void;

    public function deleteAccountData(AccountIdentifier $accountIdentifier): void;
}
