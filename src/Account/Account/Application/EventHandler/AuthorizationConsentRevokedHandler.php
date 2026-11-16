<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\EventHandler;

use Source\Account\Account\Application\Service\AccountContextServiceInterface;
use Source\Account\Account\Application\Service\CurrentAccountServiceInterface;
use Source\Identity\Domain\Event\AuthorizationConsentRevoked;

readonly class AuthorizationConsentRevokedHandler
{
    public function __construct(
        private CurrentAccountServiceInterface $currentAccountService,
        private AccountContextServiceInterface $accountContextService,
    ) {
    }

    public function handle(AuthorizationConsentRevoked $event): void
    {
        $this->currentAccountService->forgetForAuthorizationConsent($event->identityIdentifier, $event->consentIdentifier);
        $this->accountContextService->forgetForAuthorizationConsent($event->identityIdentifier, $event->consentIdentifier);
    }
}
