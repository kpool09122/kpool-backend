<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal;

use RuntimeException;
use Source\SiteManagement\Principal\Domain\Factory\PrincipalFactoryInterface;
use Source\SiteManagement\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\RoleRepositoryInterface;

readonly class ProvisionPrincipal implements ProvisionPrincipalInterface
{
    private const string DEFAULT_PRINCIPAL_GROUP_NAME = 'General';
    private const string GENERAL_ROLE_NAME = 'General';

    public function __construct(
        private PrincipalRepositoryInterface $principalRepository,
        private PrincipalFactoryInterface $principalFactory,
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private PrincipalGroupFactoryInterface $principalGroupFactory,
        private RoleRepositoryInterface $roleRepository,
    ) {
    }

    public function process(ProvisionPrincipalInputPort $inputPort, ProvisionPrincipalOutputPort $output): void
    {
        $principal = $this->principalRepository->findByIdentityIdentifierAndAccountIdentifier($inputPort->identityIdentifier(), $inputPort->accountIdentifier());
        if ($principal === null) {
            $principal = $this->principalFactory->create($inputPort->identityIdentifier(), $inputPort->accountIdentifier());
            $group = $this->principalGroupRepository->findDefaultByAccountIdentifier($inputPort->accountIdentifier());
            if ($group === null) {
                $role = $this->roleRepository->findSystemByName(self::GENERAL_ROLE_NAME)
                    ?? throw new RuntimeException('General system role not found. Please run SiteManagementAuthorizationSeeder first.');
                $group = $this->principalGroupFactory->create(self::DEFAULT_PRINCIPAL_GROUP_NAME, [$role->roleIdentifier()], $inputPort->accountIdentifier(), true);
            }
            $this->principalRepository->save($principal);
            $group->addMember($principal->principalIdentifier());
            $this->principalGroupRepository->save($group);
        }
        $output->setPrincipal($principal);
    }
}
