<?php

declare(strict_types=1);

namespace Database\Seeders;

use DateTimeImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Source\Account\Principal\Domain\Entity\Policy;
use Source\Account\Principal\Domain\Entity\Role;
use Source\Account\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Account\Principal\Domain\ValueObject\ResourceType;
use Source\Account\Principal\Domain\ValueObject\Effect;
use Source\Account\Principal\Domain\ValueObject\Statement;
use Symfony\Component\Uid\Uuid;


class AccountAuthorizationSeeder extends Seeder
{
    public function __construct(
        private readonly PolicyRepositoryInterface $policyRepository,
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
                    Action::PRINCIPAL_GROUP_MANAGE,
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
                    Action::PRINCIPAL_GROUP_MANAGE,
                ],
                resourceTypes: [ResourceType::ACCOUNT],
            ),
        ]);

        $this->saveRole(Role::OWNER, [$ownerPolicy->policyIdentifier()]);
        $this->saveRole(Role::ADMIN, [$adminPolicy->policyIdentifier()]);
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

    /**
     * @param PolicyIdentifier[] $policyIdentifiers
     */
    private function saveRole(string $name, array $policyIdentifiers): void
    {
        $roleId = DB::table('account_roles')
            ->where('name', $name)
            ->value('id');

        if (! is_string($roleId)) {
            $roleId = (string) Uuid::v7();
        }

        DB::table('account_roles')->updateOrInsert(
            ['name' => $name],
            [
                'id' => $roleId,
                'is_system_role' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        DB::table('account_role_policy_attachments')
            ->where('role_id', $roleId)
            ->delete();

        $records = array_map(
            static fn (PolicyIdentifier $policyIdentifier): array => [
                'role_id' => $roleId,
                'policy_id' => (string) $policyIdentifier,
            ],
            $policyIdentifiers,
        );

        if (! empty($records)) {
            DB::table('account_role_policy_attachments')->insert($records);
        }
    }
}
