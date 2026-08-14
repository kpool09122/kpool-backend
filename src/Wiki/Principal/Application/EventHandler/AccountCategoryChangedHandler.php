<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\EventHandler;

use RuntimeException;
use Source\Account\Account\Domain\Event\AccountCategoryChanged;
use Source\Account\Shared\Domain\ValueObject\AccountCategory;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Wiki\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;

readonly class AccountCategoryChangedHandler
{
    private const string AGENCY_ACTOR_GROUP_NAME = 'Agency Actor';
    private const string TALENT_ACTOR_GROUP_NAME = 'Talent Actor';
    private const string AGENCY_ACTOR_ROLE = 'AGENCY_ACTOR';
    private const string TALENT_ACTOR_ROLE = 'TALENT_ACTOR';

    public function __construct(
        private PrincipalGroupFactoryInterface $principalGroupFactory,
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private RoleRepositoryInterface $roleRepository,
    ) {
    }

    public function handle(AccountCategoryChanged $event): void
    {
        $groupName = $this->groupNameForCategory($event->newAccountCategory());
        $roleName = $this->roleNameForCategory($event->newAccountCategory());

        if ($groupName === null || $roleName === null) {
            return;
        }

        $role = $this->roleRepository->findByName($roleName);
        if ($role === null) {
            throw new RuntimeException("Wiki system role {$roleName} is not found.");
        }

        $principalGroup = $this->principalGroupRepository->findByAccountIdAndName(
            $event->accountIdentifier(),
            $groupName,
        );

        if ($principalGroup === null) {
            $principalGroup = $this->principalGroupFactory->create(
                $event->accountIdentifier(),
                $groupName,
                false,
            );
        }

        $principalGroup->addRole($role->roleIdentifier());

        if ($event->accountType() === AccountType::CORPORATION) {
            $this->principalGroupRepository->save($principalGroup);

            return;
        }

        $principals = $this->principalRepository->findByAccountId($event->accountIdentifier());
        $defaultPrincipalGroup = empty($principals)
            ? null
            : $this->principalGroupRepository->findDefaultByAccountId($event->accountIdentifier());
        $defaultPrincipalGroupChanged = false;

        foreach ($principals as $principal) {
            if (! $principalGroup->hasMember($principal->principalIdentifier())) {
                $principalGroup->addMember($principal->principalIdentifier());
            }

            if ($defaultPrincipalGroup !== null && $defaultPrincipalGroup->hasMember($principal->principalIdentifier())) {
                $defaultPrincipalGroup->removeMember($principal->principalIdentifier());
                $defaultPrincipalGroupChanged = true;
            }
        }

        if ($defaultPrincipalGroupChanged) {
            $this->principalGroupRepository->save($defaultPrincipalGroup);
        }

        $this->principalGroupRepository->save($principalGroup);
    }

    private function groupNameForCategory(AccountCategory $category): ?string
    {
        return match ($category) {
            AccountCategory::AGENCY => self::AGENCY_ACTOR_GROUP_NAME,
            AccountCategory::TALENT => self::TALENT_ACTOR_GROUP_NAME,
            AccountCategory::GENERAL => null,
        };
    }

    private function roleNameForCategory(AccountCategory $category): ?string
    {
        return match ($category) {
            AccountCategory::AGENCY => self::AGENCY_ACTOR_ROLE,
            AccountCategory::TALENT => self::TALENT_ACTOR_ROLE,
            AccountCategory::GENERAL => null,
        };
    }
}
