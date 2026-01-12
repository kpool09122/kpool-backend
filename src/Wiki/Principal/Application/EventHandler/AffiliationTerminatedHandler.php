<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\EventHandler;

use Source\Account\Affiliation\Domain\Event\AffiliationTerminated;
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
            // Role を削除
            $role = $this->roleRepository->findById($affiliationGrant->roleIdentifier());
            if ($role !== null) {
                $this->roleRepository->delete($role);
            }

            // Policy を削除
            $policy = $this->policyRepository->findById($affiliationGrant->policyIdentifier());
            if ($policy !== null) {
                $this->policyRepository->delete($policy);
            }

            // PrincipalGroup を削除（Default でない場合のみ）
            $principalGroup = $this->principalGroupRepository->findById(
                $affiliationGrant->principalGroupIdentifier()
            );
            if ($principalGroup !== null && ! $principalGroup->isDefault()) {
                $this->principalGroupRepository->delete($principalGroup);
            }

            // AffiliationGrant 記録を削除
            $this->affiliationGrantRepository->delete($affiliationGrant);
        }
    }
}
