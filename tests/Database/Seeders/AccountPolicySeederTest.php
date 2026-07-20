<?php

declare(strict_types=1);

namespace Tests\Database\Seeders;

use Database\Seeders\AccountPolicySeeder;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\IdentityGroup\Domain\ValueObject\AccountRole;
use Source\Account\Policy\Domain\Repository\AccountPolicyRepositoryInterface;
use Source\Account\Policy\Domain\ValueObject\AccountAction;
use Source\Account\Policy\Domain\ValueObject\Effect;
use Tests\TestCase;

class AccountPolicySeederTest extends TestCase
{
    #[Group('useDb')]
    public function testRunCreatesInitialPoliciesAndRoleAttachments(): void
    {
        $seeder = $this->app->make(AccountPolicySeeder::class);
        $seeder->run();
        $seeder->run();

        $this->assertDatabaseHas('account_policies', ['name' => 'ACCOUNT_OWNER_BASIC']);
        $this->assertDatabaseHas('account_policies', ['name' => 'ACCOUNT_ADMIN_BASIC']);
        $this->assertDatabaseHas('account_policies', ['name' => 'ACCOUNT_MEMBER_BASIC']);

        $repository = $this->app->make(AccountPolicyRepositoryInterface::class);
        $ownerPolicies = $repository->findByRoles([AccountRole::OWNER]);
        $adminPolicies = $repository->findByRoles([AccountRole::ADMIN]);
        $memberPolicies = $repository->findByRoles([AccountRole::MEMBER]);

        $this->assertTrue($this->hasAction($ownerPolicies, AccountAction::INVITATION_CREATE));
        $this->assertTrue($this->hasAction($adminPolicies, AccountAction::INVITATION_CREATE));
        $this->assertFalse($this->hasAction($memberPolicies, AccountAction::INVITATION_CREATE));
    }

    /**
     * @param array<\Source\Account\Policy\Domain\Entity\AccountPolicy> $policies
     */
    private function hasAction(array $policies, AccountAction $action): bool
    {
        foreach ($policies as $policy) {
            foreach ($policy->statements() as $statement) {
                if ($statement->effect() !== Effect::ALLOW) {
                    continue;
                }

                if (in_array($action, $statement->actions(), true)) {
                    return true;
                }
            }
        }

        return false;
    }
}
