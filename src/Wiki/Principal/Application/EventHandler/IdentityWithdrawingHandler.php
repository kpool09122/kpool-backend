<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\EventHandler;

use Source\Identity\Domain\Event\IdentityWithdrawing;
use Source\Wiki\Principal\Application\Service\IdentityWithdrawalServiceInterface;

readonly class IdentityWithdrawingHandler
{
    public function __construct(private IdentityWithdrawalServiceInterface $identityWithdrawalService)
    {
    }

    public function handle(IdentityWithdrawing $event): void
    {
        $this->identityWithdrawalService->withdraw($event->identityIdentifier, $event->archivedAt);
    }
}
