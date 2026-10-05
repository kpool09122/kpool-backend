<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal;

use LogicException;
use Source\SiteManagement\Principal\Domain\Entity\PrincipalGroup;
use Source\SiteManagement\Principal\Domain\Factory\PrincipalFactoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalGroupIdentifier;

readonly class ProvisionPrincipal implements ProvisionPrincipalInterface
{
    public function __construct(private PrincipalRepositoryInterface $principalRepository, private PrincipalFactoryInterface $principalFactory, private PrincipalGroupRepositoryInterface $principalGroupRepository)
    {
    }

    public function process(ProvisionPrincipalInputPort $inputPort, ProvisionPrincipalOutputPort $output): void
    {
        $principal = $this->principalRepository->findByIdentityId($inputPort->identityIdentifier());
        if ($principal === null) {
            $principal = $this->principalFactory->create($inputPort->identityIdentifier());
            $group = $this->principalGroupRepository->findById(new PrincipalGroupIdentifier(PrincipalGroup::GENERAL)) ?? throw new LogicException('Site management general group is missing.');
            $this->principalRepository->save($principal);
            $group->addMember($principal->principalIdentifier());
            $this->principalGroupRepository->save($group);
        }
        $output->setPrincipal($principal);
    }
}
