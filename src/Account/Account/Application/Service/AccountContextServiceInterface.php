<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\Service;

use Source\Identity\Domain\ValueObject\AuthorizationConsentIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface AccountContextServiceInterface
{
    public function forgetByIdentityIdentifier(IdentityIdentifier $identityIdentifier): void;

    public function forgetByAccountIdentifier(AccountIdentifier $accountIdentifier): void;

    public function forgetForAuthorizationConsent(IdentityIdentifier $identityIdentifier, AuthorizationConsentIdentifier $consentIdentifier): void;
}
