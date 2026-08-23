<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Source\Wiki\Principal\Domain\Entity\Policy;
use Source\Wiki\Principal\Domain\Factory\RoleFactoryInterface;
use Source\Wiki\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;

class SystemRoleSeeder extends Seeder
{
    /** @var array<string, Policy> */
    private array $policyMap = [];

    public function __construct(
        private readonly RoleFactoryInterface $roleFactory,
        private readonly RoleRepositoryInterface $roleRepository,
        private readonly PolicyRepositoryInterface $policyRepository,
    ) {
    }

    public function run(): void
    {
        $this->loadPolicies();

        $this->createAdministratorRole();
        $this->createSeniorCollaboratorRole();
        $this->createAgencyActorRole();
        $this->createTalentActorRole();
        $this->createWikiAdministratorRole();
        $this->createCollaboratorRole();
        $this->createNoneRole();
    }

    private function loadPolicies(): void
    {
        $policies = $this->policyRepository->findAll();

        foreach ($policies as $policy) {
            $this->policyMap[$policy->name()] = $policy;
        }
    }

    /**
     * @param string[] $policyNames
     * @return PolicyIdentifier[]
     */
    private function getPolicyIdentifiers(array $policyNames): array
    {
        $identifiers = [];

        foreach ($policyNames as $name) {
            if (! isset($this->policyMap[$name])) {
                throw new \RuntimeException("Policy '{$name}' not found. Please run SystemPolicySeeder first.");
            }

            $identifiers[] = $this->policyMap[$name]->policyIdentifier();
        }

        return $identifiers;
    }

    private function createAdministratorRole(): void
    {
        $role = $this->roleFactory->create(
            name: 'ADMINISTRATOR',
            policies: $this->getPolicyIdentifiers([
                ...$this->globalPolicyNames(),
                'GLOBAL_OFFICIAL_CERTIFICATION_READ',
            ]),
            isSystemRole: true,
        );

        $this->roleRepository->save($role);
    }

    private function createSeniorCollaboratorRole(): void
    {
        $role = $this->roleFactory->create(
            name: 'SENIOR_COLLABORATOR',
            policies: $this->getPolicyIdentifiers([...$this->globalPolicyNames(), 'DENY_ROLLBACK']),
            isSystemRole: true,
        );

        $this->roleRepository->save($role);
    }

    private function createAgencyActorRole(): void
    {
        $role = $this->roleFactory->create(
            name: 'AGENCY_ACTOR',
            policies: $this->getPolicyIdentifiers([...$this->basicEditingPolicyNames(), ...$this->agencyManagementPolicyNames()]),
            isSystemRole: true,
        );

        $this->roleRepository->save($role);
    }

    private function createTalentActorRole(): void
    {
        $role = $this->roleFactory->create(
            name: 'TALENT_ACTOR',
            policies: $this->getPolicyIdentifiers([...$this->basicEditingPolicyNames(), ...$this->talentManagementPolicyNames(), ...$this->denyAgencyApprovalPolicyNames()]),
            isSystemRole: true,
        );

        $this->roleRepository->save($role);
    }

    private function createWikiAdministratorRole(): void
    {
        $role = $this->roleFactory->create(
            name: 'WIKI_ADMINISTRATOR',
            policies: $this->getPolicyIdentifiers(['GLOBAL_PRINCIPAL_GROUP_MANAGE']),
            isSystemRole: true,
        );

        $this->roleRepository->save($role);
    }

    private function createCollaboratorRole(): void
    {
        $role = $this->roleFactory->create(
            name: 'COLLABORATOR',
            policies: $this->getPolicyIdentifiers($this->basicEditingPolicyNames()),
            isSystemRole: true,
        );

        $this->roleRepository->save($role);
    }

    private function createNoneRole(): void
    {
        $role = $this->roleFactory->create(
            name: 'NONE',
            policies: [],
            isSystemRole: true,
        );

        $this->roleRepository->save($role);
    }

    /**
     * @return string[]
     */
    private function globalPolicyNames(): array
    {
        return [
            'GLOBAL_CREATE',
            'GLOBAL_READ',
            'GLOBAL_EDIT',
            'GLOBAL_SUBMIT',
            'GLOBAL_WITHDRAW',
            'GLOBAL_APPROVE',
            'GLOBAL_REJECT',
            'GLOBAL_TRANSLATE',
            'GLOBAL_PUBLISH',
            'GLOBAL_ROLLBACK',
            'GLOBAL_MERGE',
            'GLOBAL_AUTOMATIC_CREATE',
            'GLOBAL_SAVE_VIDEO_LINKS',
            'GLOBAL_DELETE',
            'GLOBAL_HIDE',
            'GLOBAL_UNHIDE',
            'GLOBAL_OFFICIAL_CERTIFICATION_REQUEST',
            'GLOBAL_OFFICIAL_CERTIFICATION_APPROVE',
            'GLOBAL_OFFICIAL_CERTIFICATION_REJECT',
        ];
    }

    /**
     * @return string[]
     */
    private function basicEditingPolicyNames(): array
    {
        return [
            'GLOBAL_CREATE',
            'GLOBAL_EDIT',
            'GLOBAL_SUBMIT',
            'OWN_WIKI_DELETE',
            'OWN_WIKI_WITHDRAW',
        ];
    }

    /**
     * @return string[]
     */
    private function agencyManagementPolicyNames(): array
    {
        return [
            'AGENCY_SCOPE_READ',
            'AGENCY_SCOPE_APPROVE',
            'AGENCY_SCOPE_REJECT',
            'AGENCY_SCOPE_TRANSLATE',
            'AGENCY_SCOPE_PUBLISH',
            'AGENCY_SCOPE_MERGE',
            'AGENCY_SCOPE_AUTOMATIC_CREATE',
            'AGENCY_SCOPE_SAVE_VIDEO_LINKS',
            'AGENCY_SCOPE_IMAGE_APPROVE',
            'AGENCY_SCOPE_IMAGE_REJECT',
            'AGENCY_SCOPE_IMAGE_DELETE',
            'AGENCY_SCOPE_OFFICIAL_CERTIFICATION_REQUEST',
            'AGENCY_SCOPE_OFFICIAL_CERTIFICATION_MY_READ',
            'GLOBAL_RELATED_WIKI_LIST',
        ];
    }

    /**
     * @return string[]
     */
    private function talentManagementPolicyNames(): array
    {
        return [
            'TALENT_SCOPE_READ',
            'TALENT_SCOPE_EDIT',
            'TALENT_SCOPE_APPROVE',
            'TALENT_SCOPE_REJECT',
            'TALENT_SCOPE_TRANSLATE',
            'TALENT_SCOPE_PUBLISH',
            'TALENT_SCOPE_MERGE',
            'TALENT_SCOPE_AUTOMATIC_CREATE',
            'TALENT_SCOPE_SAVE_VIDEO_LINKS',
            'TALENT_SCOPE_IMAGE_APPROVE',
            'TALENT_SCOPE_IMAGE_REJECT',
            'TALENT_SCOPE_IMAGE_DELETE',
            'TALENT_SCOPE_OFFICIAL_CERTIFICATION_REQUEST',
            'TALENT_SCOPE_OFFICIAL_CERTIFICATION_MY_READ',
            'GLOBAL_RELATED_WIKI_LIST',
        ];
    }

    /**
     * @return string[]
     */
    private function denyAgencyApprovalPolicyNames(): array
    {
        return [
            'DENY_AGENCY_APPROVE',
            'DENY_AGENCY_REJECT',
            'DENY_AGENCY_TRANSLATE',
            'DENY_AGENCY_PUBLISH',
        ];
    }
}
