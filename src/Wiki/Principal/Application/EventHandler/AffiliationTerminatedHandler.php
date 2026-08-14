<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\EventHandler;

use Source\Account\Affiliation\Domain\Event\AffiliationTerminated;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;
use Source\Wiki\Principal\Domain\Repository\AffiliationGrantRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;

readonly class AffiliationTerminatedHandler
{
    public function __construct(
        private AffiliationGrantRepositoryInterface $affiliationGrantRepository,
        private RoleRepositoryInterface $roleRepository,
        private PolicyRepositoryInterface $policyRepository,
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
    ) {
    }

    public function handle(AffiliationTerminated $event): void
    {
        $affiliationGrants = $this->affiliationGrantRepository->findByAffiliationId(
            $event->affiliationIdentifier()
        );

        foreach ($affiliationGrants as $affiliationGrant) {
            $principalGroup = $this->principalGroupRepository->findById(
                $affiliationGrant->principalGroupIdentifier()
            );

            if ($principalGroup !== null && $this->isAffiliationPrincipalGroup($principalGroup)) {
                $this->restoreMembersToDefaultGroup($principalGroup);
                $principalGroup->removeRole($affiliationGrant->roleIdentifier());
                $this->principalGroupRepository->save($principalGroup);
                $this->principalGroupRepository->delete($principalGroup);
            }

            $role = $this->roleRepository->findById($affiliationGrant->roleIdentifier());
            if ($role !== null && ! $role->isSystemRole()) {
                $this->roleRepository->delete($role);
            }

            $policy = $this->policyRepository->findById($affiliationGrant->policyIdentifier());
            if ($policy !== null && ! $policy->isSystemPolicy()) {
                $this->policyRepository->delete($policy);
            }

            $this->affiliationGrantRepository->delete($affiliationGrant);
        }
    }

    private function isAffiliationPrincipalGroup(PrincipalGroup $principalGroup): bool
    {
        return ! $principalGroup->isDefault()
            && str_starts_with($principalGroup->name(), 'Affiliation - ');
    }

    private function restoreMembersToDefaultGroup(PrincipalGroup $principalGroup): void
    {
        $defaultPrincipalGroup = $this->principalGroupRepository->findDefaultByAccountId(
            $principalGroup->accountIdentifier()
        );

        if ($defaultPrincipalGroup === null) {
            return;
        }

        $defaultPrincipalGroupChanged = false;
        foreach ($principalGroup->members() as $principalIdentifier) {
            if (! $defaultPrincipalGroup->hasMember($principalIdentifier)) {
                $defaultPrincipalGroup->addMember($principalIdentifier);
                $defaultPrincipalGroupChanged = true;
            }
        }

        if ($defaultPrincipalGroupChanged) {
            $this->principalGroupRepository->save($defaultPrincipalGroup);
        }
    }
}
