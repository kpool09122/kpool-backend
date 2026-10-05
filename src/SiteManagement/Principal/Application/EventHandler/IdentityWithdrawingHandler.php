<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Application\EventHandler;

use Source\Identity\Domain\Event\IdentityWithdrawing;
use Source\SiteManagement\Principal\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceInput;
use Source\SiteManagement\Principal\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceInterface;
use Source\SiteManagement\Principal\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceOutput;

readonly class IdentityWithdrawingHandler
{
    public function __construct(private WithdrawFromServiceInterface $withdrawFromService)
    {
    }

    public function handle(IdentityWithdrawing $event): void
    {
        $this->withdrawFromService->process(new WithdrawFromServiceInput($event->identityIdentifier), new WithdrawFromServiceOutput());
    }
}
