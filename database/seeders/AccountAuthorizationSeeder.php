<?php

declare(strict_types=1);

namespace Database\Seeders;

use DateTimeImmutable;
use Illuminate\Database\Seeder;
use Source\Account\Principal\Domain\Entity\Role;
use Source\Account\Principal\Domain\ValueObject\AccountRole;
use Source\Account\Principal\Domain\Entity\Policy;
use Source\Account\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Account\Principal\Domain\ValueObject\ResourceType;
use Source\Account\Principal\Domain\ValueObject\Effect;
use Source\Account\Principal\Domain\ValueObject\Statement;


class AccountAuthorizationSeeder extends Seeder
{
    public function __construct(
        private readonly PolicyRepositoryInterface $policyRepository,
        private readonly RoleRepositoryInterface $roleRepository,
    ) {
    }

    public function run(): void
    {
        $ownerPolicy = $this->createPolicy('01982020-0456-7000-8000-000000000001', 'ACCOUNT_OWNER_BASIC', [
            new Statement(
                effect: Effect::ALLOW,
                actions: [
                    Action::INVITE_MEMBER,
                    Action::UPDATE,
                    Action::SETTINGS_UPDATE,
                    Action::DELETE,
                    Action::BILLING_MANAGE,
                    Action::DELEGATION_MANAGE,
                ],
                resourceTypes: [ResourceType::ACCOUNT],
            ),
        ]);

        $adminPolicy = $this->createPolicy('01982020-0456-7000-8000-000000000002', 'ACCOUNT_ADMIN_BASIC', [
            new Statement(
                effect: Effect::ALLOW,
                actions: [
                    Action::INVITE_MEMBER,
                    Action::UPDATE,
                    Action::SETTINGS_UPDATE,
                    Action::DELEGATION_MANAGE,
                ],
                resourceTypes: [ResourceType::ACCOUNT],
            ),
        ]);

        $basicPolicy = $this->createPolicy('01982020-0456-7000-8000-000000000003', 'ACCOUNT_BASIC', []);

        $this->roleRepository->save(new Role(AccountRole::OWNER, [$ownerPolicy->policyIdentifier()]));
        $this->roleRepository->save(new Role(AccountRole::ADMIN, [$adminPolicy->policyIdentifier()]));
        $this->roleRepository->save(new Role(AccountRole::BASIC, [$basicPolicy->policyIdentifier()]));
    }

    /**
     * @param Statement[] $statements
     */
    private function createPolicy(string $identifier, string $name, array $statements): Policy
    {
        $policy = new Policy(
            new PolicyIdentifier($identifier),
            $name,
            $statements,
            true,
            new DateTimeImmutable(),
        );

        $this->policyRepository->save($policy);

        return $policy;
    }
}
