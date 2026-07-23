<?php

declare(strict_types=1);

namespace Tests\Database\Seeders;

use Database\Seeders\AccountAuthorizationSeeder;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\AccountRole;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Effect;
use Tests\TestCase;

class AccountAuthorizationSeederTest extends TestCase
{
    #[Group('useDb')]
    public function testRunCreatesInitialPoliciesAndRoleAttachments(): void
    {
        $seeder = $this->app->make(AccountAuthorizationSeeder::class);
        $seeder->run();
        $seeder->run();

        $this->assertDatabaseHas('account_policies', ['name' => 'ACCOUNT_OWNER_BASIC']);
        $this->assertDatabaseHas('account_policies', ['name' => 'ACCOUNT_ADMIN_BASIC']);
        $this->assertDatabaseHas('account_policies', ['name' => 'ACCOUNT_MEMBER_BASIC']);

        $policyRepository = $this->app->make(PolicyRepositoryInterface::class);
        $roleRepository = $this->app->make(RoleRepositoryInterface::class);
        $roles = $roleRepository->findByRoles([AccountRole::OWNER, AccountRole::ADMIN, AccountRole::MEMBER]);
        $ownerPolicies = $policyRepository->findByIds($roles[AccountRole::OWNER->value]->policies());
        $adminPolicies = $policyRepository->findByIds($roles[AccountRole::ADMIN->value]->policies());
        $memberPolicies = $policyRepository->findByIds($roles[AccountRole::MEMBER->value]->policies());

        $this->assertTrue($this->hasAction($ownerPolicies, Action::INVITATION_CREATE));
        $this->assertTrue($this->hasAction($adminPolicies, Action::INVITATION_CREATE));
        $this->assertFalse($this->hasAction($memberPolicies, Action::INVITATION_CREATE));
    }

    /**
     * @param array<\Source\Account\Principal\Domain\Entity\Policy> $policies
     */
    private function hasAction(array $policies, Action $action): bool
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
