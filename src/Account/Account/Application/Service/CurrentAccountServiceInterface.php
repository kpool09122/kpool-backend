<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\Service;

use Source\Identity\Domain\ValueObject\AuthorizationConsentIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Throwable;

interface CurrentAccountServiceInterface
{
    public function find(IdentityIdentifier $identityIdentifier): ?CurrentAccount;

    public function save(CurrentAccount $currentAccount): void;

    /** @throws Throwable */
    public function forget(IdentityIdentifier $identityIdentifier): void;

    public function forgetForAuthorizationConsent(IdentityIdentifier $identityIdentifier, AuthorizationConsentIdentifier $consentIdentifier): void;
}
