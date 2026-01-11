<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\EventHandler;

use Source\Identity\Domain\Event\DelegatedIdentityCreated;
use Source\Wiki\Principal\Domain\Factory\PrincipalFactoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;

readonly class DelegatedIdentityCreatedHandler
{
    public function __construct(
        private PrincipalRepositoryInterface $principalRepository,
        private PrincipalFactoryInterface $principalFactory,
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
    ) {
    }

    public function handle(DelegatedIdentityCreated $event): void
    {
        $originalPrincipal = $this->principalRepository->findByIdentityIdentifier(
            $event->originalIdentityIdentifier()
        );

        if ($originalPrincipal === null) {
            return;
        }

        $delegatedPrincipal = $this->principalFactory->createDelegatedPrincipal(
            $originalPrincipal,
            $event->delegationIdentifier(),
            $event->delegatedIdentityIdentifier(),
        );

        $this->principalRepository->save($delegatedPrincipal);

        // 委譲 Principal を元の Principal と同じ PrincipalGroup(s) に追加
        $principalGroups = $this->principalGroupRepository->findByPrincipalId(
            $originalPrincipal->principalIdentifier()
        );

        foreach ($principalGroups as $principalGroup) {
            $principalGroup->addMember($delegatedPrincipal->principalIdentifier());
            $this->principalGroupRepository->save($principalGroup);
        }
    }
}
