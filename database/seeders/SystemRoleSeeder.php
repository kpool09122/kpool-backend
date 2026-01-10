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
            policies: $this->getPolicyIdentifiers(['FULL_ACCESS']),
            isSystemRole: true,
        );

        $this->roleRepository->save($role);
    }

    private function createSeniorCollaboratorRole(): void
    {
        $role = $this->roleFactory->create(
            name: 'SENIOR_COLLABORATOR',
            policies: $this->getPolicyIdentifiers(['FULL_ACCESS', 'DENY_ROLLBACK']),
            isSystemRole: true,
        );

        $this->roleRepository->save($role);
    }

    private function createAgencyActorRole(): void
    {
        $role = $this->roleFactory->create(
            name: 'AGENCY_ACTOR',
            policies: $this->getPolicyIdentifiers(['BASIC_EDITING', 'AGENCY_MANAGEMENT']),
            isSystemRole: true,
        );

        $this->roleRepository->save($role);
    }

    private function createTalentActorRole(): void
    {
        $role = $this->roleFactory->create(
            name: 'TALENT_ACTOR',
            policies: $this->getPolicyIdentifiers(['BASIC_EDITING', 'TALENT_MANAGEMENT', 'DENY_AGENCY_APPROVAL']),
            isSystemRole: true,
        );

        $this->roleRepository->save($role);
    }

    private function createCollaboratorRole(): void
    {
        $role = $this->roleFactory->create(
            name: 'COLLABORATOR',
            policies: $this->getPolicyIdentifiers(['BASIC_EDITING']),
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
}
