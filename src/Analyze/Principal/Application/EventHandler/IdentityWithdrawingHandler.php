<?php

declare(strict_types=1);

namespace Source\Analyze\Principal\Application\EventHandler;

use Illuminate\Support\Facades\DB;
use Source\Analyze\Principal\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceInput;
use Source\Analyze\Principal\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceInterface;
use Source\Analyze\Principal\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceOutput;
use Source\Identity\Domain\Event\IdentityWithdrawing;

readonly class IdentityWithdrawingHandler
{
    public function __construct(private WithdrawFromServiceInterface $withdrawFromService)
    {
    }

    public function handle(IdentityWithdrawing $event): void
    {
        DB::transaction(function () use ($event): void {
            $this->withdrawFromService->process(new WithdrawFromServiceInput($event->identityIdentifier), new WithdrawFromServiceOutput());
        });
    }
}
