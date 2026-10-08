<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal;

use Source\SiteManagement\Principal\Domain\Entity\PrincipalGroup;
use Source\SiteManagement\Principal\Domain\Entity\Role;
use Source\SiteManagement\Principal\Domain\Factory\PrincipalFactoryInterface;
use Source\SiteManagement\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\RoleIdentifier;

readonly class ProvisionPrincipal implements ProvisionPrincipalInterface
{
    public function __construct(
        private PrincipalRepositoryInterface $principalRepository,
        private PrincipalFactoryInterface $principalFactory,
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private PrincipalGroupFactoryInterface $principalGroupFactory,
    ) {
    }

    public function process(ProvisionPrincipalInputPort $inputPort, ProvisionPrincipalOutputPort $output): void
    {
        $principal = $this->principalRepository->findByIdentityIdentifierAndAccountIdentifier($inputPort->identityIdentifier(), $inputPort->accountIdentifier());
        if ($principal === null) {
            $principal = $this->principalFactory->create($inputPort->identityIdentifier(), $inputPort->accountIdentifier());
            $group = $this->principalGroupRepository->findDefaultByAccountIdentifier($inputPort->accountIdentifier())
                ?? $this->principalGroupFactory->create(PrincipalGroup::GENERAL, [new RoleIdentifier(Role::GENERAL)], $inputPort->accountIdentifier(), true);
            $this->principalRepository->save($principal);
            $group->addMember($principal->principalIdentifier());
            $this->principalGroupRepository->save($group);
        }
        $output->setPrincipal($principal);
    }
}
