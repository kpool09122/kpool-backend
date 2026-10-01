<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\WithdrawFromService;

use Source\Identity\Application\Service\ActorContextServiceInterface;
use Source\Identity\Application\Service\IdentityWithdrawalSessionServiceInterface;
use Source\Identity\Application\Service\StepUpAuthenticationStorageServiceInterface;
use Source\Identity\Domain\Event\IdentityWithdrawing;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Identity\Domain\Factory\ArchivedIdentityFactoryInterface;
use Source\Identity\Domain\Repository\ArchivedIdentityRepositoryInterface;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationScope;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;
use Source\Shared\Application\Service\ImageServiceInterface;

readonly class WithdrawFromService implements WithdrawFromServiceInterface
{
    public function __construct(
        private StepUpAuthenticationStorageServiceInterface $stepUpAuthenticationStorageService,
        private ActorContextServiceInterface $actorContextService,
        private EventDispatcherInterface $eventDispatcher,
        private IdentityWithdrawalSessionServiceInterface $identityWithdrawalSessionService,
        private IdentityRepositoryInterface $identityRepository,
        private ArchivedIdentityFactoryInterface $archivedIdentityFactory,
        private ArchivedIdentityRepositoryInterface $archivedIdentityRepository,
        private ImageServiceInterface $imageService,
    ) {
    }

    public function process(WithdrawFromServiceInputPort $input, WithdrawFromServiceOutputPort $output): void
    {
        $identityIdentifier = $input->identityIdentifier();
        $this->stepUpAuthenticationStorageService->requireValid($identityIdentifier, StepUpAuthenticationScope::RECENT_AUTHENTICATION);
        $identity = $this->identityRepository->findById($identityIdentifier);
        if ($identity === null) {
            throw new IdentityNotFoundException();
        }
        $archivedIdentity = $this->archivedIdentityFactory->create(
            $identityIdentifier,
            $identity->language(),
            $identity->createdAt(),
        );
        $this->archivedIdentityRepository->save($archivedIdentity);
        $this->eventDispatcher->dispatch(new IdentityWithdrawing($identityIdentifier));
        $profileImage = $identity->profileImage();
        $this->identityRepository->delete($identityIdentifier);
        $this->actorContextService->forget($identityIdentifier);
        $this->identityWithdrawalSessionService->terminate($identityIdentifier);

        if ($profileImage !== null) {
            $this->imageService->delete($profileImage);
        }
    }
}
