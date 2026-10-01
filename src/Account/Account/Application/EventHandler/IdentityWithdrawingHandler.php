<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\EventHandler;

use Source\Account\Account\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceInput;
use Source\Account\Account\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceInterface;
use Source\Account\Account\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceOutput;
use Source\Account\Account\Domain\Exception\IdentityWithdrawalNotAllowedException;
use Source\Identity\Domain\Event\IdentityWithdrawing;

readonly class IdentityWithdrawingHandler
{
    public function __construct(private WithdrawFromServiceInterface $withdrawFromService)
    {
    }

    /** @throws IdentityWithdrawalNotAllowedException */
    public function handle(IdentityWithdrawing $event): void
    {
        $input = new WithdrawFromServiceInput($event->identityIdentifier);
        $output = new WithdrawFromServiceOutput();
        $this->withdrawFromService->process($input, $output);
    }
}
