<?php

declare(strict_types=1);

namespace Source\Wiki\Grading\Application\Service;

use Source\Shared\Domain\ValueObject\PrincipalGroupDescription;
use Source\Wiki\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Wiki\Principal\Domain\Entity\Role;
use Source\Wiki\Principal\Domain\Factory\PrincipalGroupFactoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class CollaboratorRoleTransitionService implements CollaboratorRoleTransitionServiceInterface
{
    public function __construct(
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private PrincipalGroupFactoryInterface $principalGroupFactory,
    ) {
    }

    public function promote(PrincipalIdentifier $principalIdentifier, Role $seniorRole): bool
    {
        $groups = $this->principalGroupRepository->findByPrincipalId($principalIdentifier);
        foreach ($groups as $group) {
            if ($group->hasRole($seniorRole->roleIdentifier())) {
                return false;
            }
        }
        $defaultGroup = array_find($groups, static fn ($group): bool => $group->isDefault());
        if ($defaultGroup === null) {
            throw new PrincipalGroupNotFoundException('Collaboratorの基本グループへの所属が見つかりません。');
        }
        $seniorGroup = array_find(
            $this->principalGroupRepository->findByRole($seniorRole->roleIdentifier()),
            static fn ($group): bool => (string) $group->accountIdentifier() === (string) $defaultGroup->accountIdentifier(),
        );
        if ($seniorGroup === null) {
            $seniorGroup = $this->principalGroupFactory->create($defaultGroup->accountIdentifier(), 'Senior Collaborator', false);
            $seniorGroup->setDescription(new PrincipalGroupDescription('Reviews, translates, and publishes Wiki content. Rollback is not permitted.'));
            $seniorGroup->addRole($seniorRole);
        }
        $seniorGroup->addMember($principalIdentifier);
        $this->principalGroupRepository->save($seniorGroup);

        return true;
    }

    public function demote(PrincipalIdentifier $principalIdentifier, Role $seniorRole): bool
    {
        $changed = false;
        foreach ($this->principalGroupRepository->findByPrincipalId($principalIdentifier) as $group) {
            if ($group->hasRole($seniorRole->roleIdentifier())) {
                $group->removeMember($principalIdentifier);
                $this->principalGroupRepository->save($group);
                $changed = true;
            }
        }

        return $changed;
    }
}
