<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\EventHandler;

use Source\Account\Account\Application\Service\IdentityWithdrawalServiceInterface;
use Source\Account\Account\Domain\Exception\IdentityWithdrawalNotAllowedException;
use Source\Identity\Domain\Event\IdentityWithdrawing;

readonly class IdentityWithdrawingHandler
{
    public function __construct(private IdentityWithdrawalServiceInterface $identityWithdrawalService)
    {
    }

    /** @throws IdentityWithdrawalNotAllowedException */
    public function handle(IdentityWithdrawing $event): void
    {
        $this->identityWithdrawalService->withdraw($event->identityIdentifier, $event->archivedAt);
    }
}
