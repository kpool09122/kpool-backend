<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\CreatePrincipal;

use Source\Wiki\Principal\Domain\Exception\PrincipalAlreadyExistsException;
use Source\Wiki\Principal\Domain\Factory\PrincipalFactoryInterface;
use Source\Wiki\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;

readonly class CreatePrincipal implements CreatePrincipalInterface
{
    private const string DEFAULT_PRINCIPAL_GROUP_NAME = 'Default';
    private const string COLLABORATOR_ROLE = 'COLLABORATOR';

    public function __construct(
        private PrincipalRepositoryInterface $principalRepository,
        private PrincipalFactoryInterface $principalFactory,
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private PrincipalGroupFactoryInterface $principalGroupFactory,
        private RoleRepositoryInterface $roleRepository,
    ) {
    }

    /**
     * @param CreatePrincipalInputPort $input
     * @param CreatePrincipalOutputPort $output
     * @throws PrincipalAlreadyExistsException
     */
    public function process(CreatePrincipalInputPort $input, CreatePrincipalOutputPort $output): void
    {
        $existingPrincipal = $this->principalRepository->findByIdentityIdentifier(
            $input->identityIdentifier()
        );

        if ($existingPrincipal !== null) {
            throw new PrincipalAlreadyExistsException();
        }

        $principal = $this->principalFactory->create(
            $input->identityIdentifier(),
        );

        $this->principalRepository->save($principal);

        // Default PrincipalGroup の取得または作成
        $defaultPrincipalGroup = $this->principalGroupRepository->findDefaultByAccountId(
            $input->accountIdentifier()
        );

        if ($defaultPrincipalGroup === null) {
            $defaultPrincipalGroup = $this->principalGroupFactory->create(
                $input->accountIdentifier(),
                self::DEFAULT_PRINCIPAL_GROUP_NAME,
                true,
            );

            $role = $this->roleRepository->findByName(self::COLLABORATOR_ROLE);
            if ($role !== null) {
                $defaultPrincipalGroup->addRole($role->roleIdentifier());
            }

            $this->principalGroupRepository->save($defaultPrincipalGroup);
        }

        // Principal を Default PrincipalGroup に追加
        $defaultPrincipalGroup->addMember($principal->principalIdentifier());
        $this->principalGroupRepository->save($defaultPrincipalGroup);

        $output->setPrincipal($principal);
    }
}
