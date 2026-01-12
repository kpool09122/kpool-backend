<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\CreatePrincipal;

use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Exception\PrincipalAlreadyExistsException;
use Source\Wiki\Principal\Domain\Factory\PrincipalFactoryInterface;
use Source\Wiki\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;

readonly class CreatePrincipal implements CreatePrincipalInterface
{
    private const string DEFAULT_PRINCIPAL_GROUP_NAME = 'Default';
    private const string AGENCY_ACTOR_ROLE = 'AGENCY_ACTOR';
    private const string TALENT_ACTOR_ROLE = 'TALENT_ACTOR';
    private const string COLLABORATOR_ROLE = 'COLLABORATOR';

    public function __construct(
        private PrincipalRepositoryInterface $principalRepository,
        private PrincipalFactoryInterface $principalFactory,
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private PrincipalGroupFactoryInterface $principalGroupFactory,
        private AccountRepositoryInterface $accountRepository,
        private RoleRepositoryInterface $roleRepository,
    ) {
    }

    /**
     * @param CreatePrincipalInputPort $input
     * @return Principal
     * @throws PrincipalAlreadyExistsException
     */
    public function process(CreatePrincipalInputPort $input): Principal
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

            // AccountCategoryに応じたRoleを付与
            $roleName = $this->determineRoleName($input->accountIdentifier());
            $role = $this->roleRepository->findByName($roleName);
            if ($role !== null) {
                $defaultPrincipalGroup->addRole($role->roleIdentifier());
            }

            $this->principalGroupRepository->save($defaultPrincipalGroup);
        }

        // Principal を Default PrincipalGroup に追加
        $defaultPrincipalGroup->addMember($principal->principalIdentifier());
        $this->principalGroupRepository->save($defaultPrincipalGroup);

        return $principal;
    }

    private function determineRoleName(AccountIdentifier $accountIdentifier): string
    {
        $account = $this->accountRepository->findById($accountIdentifier);

        if ($account === null) {
            return self::COLLABORATOR_ROLE;
        }

        return match ($account->accountCategory()) {
            AccountCategory::AGENCY => self::AGENCY_ACTOR_ROLE,
            AccountCategory::TALENT => self::TALENT_ACTOR_ROLE,
            AccountCategory::GENERAL => self::COLLABORATOR_ROLE,
        };
    }
}
