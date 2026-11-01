<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use RuntimeException;
use Source\Wiki\Principal\Domain\Entity\Policy;
use Source\Wiki\Principal\Domain\Entity\Role;
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

        $this->createOperatorRole();
        $this->createSeniorCollaboratorRole();
        $this->createAgencyActorRole();
        $this->createTalentActorRole();
        $this->createAdministratorRole();
        $this->createCollaboratorRole();
        $this->createNoneRole();
    }

    private function loadPolicies(): void
    {
        $this->policyMap = [];
        $policies = $this->policyRepository->findAll();

        foreach ($policies as $policy) {
            if (! $policy->isSystemPolicy()) {
                continue;
            }

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
                throw new RuntimeException("Policy '{$name}' not found. Please run SystemPolicySeeder first.");
            }

            $identifiers[] = $this->policyMap[$name]->policyIdentifier();
        }

        return $identifiers;
    }

    private function createOperatorRole(): void
    {
        $this->saveRole(
            name: 'Operator',
            policies: $this->getPolicyIdentifiers([
                ...$this->basicGlobalPolicyNames(),
                ...$this->seniorCollaboratorPolicyNames(),
                ...$this->operatorOnlyPolicyNames(),
            ]),
        );
    }

    private function createSeniorCollaboratorRole(): void
    {
        $this->saveRole(
            name: 'SeniorCollaborator',
            policies: $this->getPolicyIdentifiers([
                ...$this->seniorCollaboratorPolicyNames(),
                'DENY_ROLLBACK',
            ]),
        );
    }

    private function createAgencyActorRole(): void
    {
        $this->saveRole(
            name: 'AgencyActor',
            policies: $this->getPolicyIdentifiers($this->agencyManagementPolicyNames()),
        );
    }

    private function createTalentActorRole(): void
    {
        $this->saveRole(
            name: 'TalentActor',
            policies: $this->getPolicyIdentifiers([...$this->talentManagementPolicyNames(), ...$this->denyAgencyApprovalPolicyNames()]),
        );
    }

    private function createAdministratorRole(): void
    {
        $this->saveRole(
            name: 'Administrator',
            policies: $this->getPolicyIdentifiers(['GLOBAL_PRINCIPAL_GROUP_MANAGE']),
        );
    }

    private function createCollaboratorRole(): void
    {
        $this->saveRole(
            name: 'Collaborator',
            policies: $this->getPolicyIdentifiers($this->basicEditingPolicyNames()),
        );
    }

    private function createNoneRole(): void
    {
        $this->saveRole(
            name: 'None',
            policies: [],
        );
    }

    /**
     * @param PolicyIdentifier[] $policies
     */
    private function saveRole(string $name, array $policies): void
    {
        $existing = $this->roleRepository->findSystemByName($name);
        $role = $existing === null
            ? $this->roleFactory->create($name, $policies, null)
            : new Role($existing->roleIdentifier(), $name, $policies, null, $existing->createdAt());

        $this->roleRepository->save($role);
    }

    /**
     * @return string[]
     */
    private function seniorCollaboratorPolicyNames(): array
    {
        return [
            'GLOBAL_READ',
            'GLOBAL_WITHDRAW',
            'GLOBAL_APPROVE',
            'GLOBAL_REJECT',
            'GLOBAL_TRANSLATE',
            'GLOBAL_PUBLISH',
            'GLOBAL_MERGE',
            'GLOBAL_AUTOMATIC_CREATE',
            'GLOBAL_SEARCH_FREE_MATERIALS',
            'GLOBAL_DELETE',
            'GLOBAL_HIDE',
            'GLOBAL_UNHIDE',
        ];
    }

    /**
     * @return string[]
     */
    private function operatorOnlyPolicyNames(): array
    {
        return [
            'GLOBAL_ROLLBACK',
            'GLOBAL_OFFICIAL_CERTIFICATION_READ',
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
            ...$this->basicGlobalPolicyNames(),
            'OWN_WIKI_DELETE',
            'OWN_WIKI_WITHDRAW',
        ];
    }

    /**
     * @return string[]
     */
    private function basicGlobalPolicyNames(): array
    {
        return [
            'GLOBAL_CREATE',
            'GLOBAL_EDIT',
            'GLOBAL_SUBMIT',
            'GLOBAL_SAVE_VIDEO_LINKS',
            'GLOBAL_DELETE_VIDEO_LINK',
            'GLOBAL_CREATE_PLAYLIST',
            'GLOBAL_LIST_MY_PLAYLISTS',
            'GLOBAL_GET_MY_PLAYLIST',
            'GLOBAL_DELETE_PLAYLIST',
            'GLOBAL_SAVE_PLAYLIST',
            'GLOBAL_LIKE_VIDEO_LINK',
            'GLOBAL_RECORD_VIDEO_LINK_WATCH',
            'GLOBAL_UNLIKE_VIDEO_LINK',
            'GLOBAL_SEND_VIDEO_WATCH_REQUEST',
            'GLOBAL_LIST_MY_VIDEO_WATCH_REQUESTS',
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
            'AGENCY_SCOPE_IMAGE_APPROVE',
            'AGENCY_SCOPE_IMAGE_REJECT',
            'AGENCY_SCOPE_IMAGE_DELETE',
            'AGENCY_SCOPE_OFFICIAL_CERTIFICATION_REQUEST',
            'AGENCY_SCOPE_OFFICIAL_CERTIFICATION_MY_READ',
            'AGENCY_SCOPE_OFFICIAL_CERTIFICATION_OWNED_WIKI_SYNC',
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
            'TALENT_SCOPE_APPROVE',
            'TALENT_SCOPE_REJECT',
            'TALENT_SCOPE_TRANSLATE',
            'TALENT_SCOPE_PUBLISH',
            'TALENT_SCOPE_MERGE',
            'TALENT_SCOPE_AUTOMATIC_CREATE',
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
