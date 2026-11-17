<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\EventHandler;

use Source\Account\Account\Application\Service\AccountContextServiceInterface;
use Source\Account\Account\Application\Service\CurrentAccountServiceInterface;
use Source\Identity\Domain\Event\AuthorizationConnectionRevoked;

readonly class AuthorizationConnectionRevokedHandler
{
    public function __construct(
        private CurrentAccountServiceInterface $currentAccountService,
        private AccountContextServiceInterface $accountContextService,
    ) {
    }

    public function handle(AuthorizationConnectionRevoked $event): void
    {
        $this->currentAccountService->forgetForAuthorizationConnection($event->identityIdentifier, $event->connectionIdentifier);
        $this->accountContextService->forgetForAuthorizationConnection($event->identityIdentifier, $event->connectionIdentifier);
    }
}
