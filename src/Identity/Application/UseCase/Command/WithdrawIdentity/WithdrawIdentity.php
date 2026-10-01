<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\WithdrawIdentity;

use DateTimeImmutable;
use Source\Identity\Application\Service\IdentityWithdrawalServiceInterface;
use Source\Identity\Application\Service\StepUpAuthenticationStorageServiceInterface;
use Source\Identity\Domain\Event\IdentityWithdrawing;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationScope;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class WithdrawIdentity implements WithdrawIdentityInterface
{
    public function __construct(
        private StepUpAuthenticationStorageServiceInterface $stepUpAuthenticationStorageService,
        private IdentityWithdrawalServiceInterface $identityWithdrawalService,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function process(IdentityIdentifier $identityIdentifier): void
    {
        $this->stepUpAuthenticationStorageService->requireValid($identityIdentifier, StepUpAuthenticationScope::RECENT_AUTHENTICATION);
        $archivedAt = new DateTimeImmutable();
        $this->identityWithdrawalService->archive($identityIdentifier, $archivedAt);
        $this->eventDispatcher->dispatch(new IdentityWithdrawing($identityIdentifier, $archivedAt));
        $this->identityWithdrawalService->delete($identityIdentifier);
    }
}
