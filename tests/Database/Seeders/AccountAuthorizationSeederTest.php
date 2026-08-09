<?php

declare(strict_types=1);

namespace Tests\Database\Seeders;

use Database\Seeders\AccountAuthorizationSeeder;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Principal\Domain\Entity\Role;
use Source\Account\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;
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
        $this->assertDatabaseMissing('account_policies', ['name' => 'ACCOUNT_BASIC']);

        $policyRepository = $this->app->make(PolicyRepositoryInterface::class);
        $roleRepository = $this->app->make(RoleRepositoryInterface::class);
        $ownerRole = $roleRepository->findByName(Role::OWNER);
        $adminRole = $roleRepository->findByName(Role::ADMIN);

        $this->assertNotNull($ownerRole);
        $this->assertNotNull($adminRole);
        $this->assertDatabaseMissing('account_roles', ['name' => 'Basic']);

        $ownerPolicies = $policyRepository->findByIds($ownerRole->policies());
        $adminPolicies = $policyRepository->findByIds($adminRole->policies());

        $this->assertTrue($this->hasAction($ownerPolicies, Action::READ));
        $this->assertTrue($this->hasAction($ownerPolicies, Action::INVITE_MEMBER));
        $this->assertTrue($this->hasAction($ownerPolicies, Action::UPDATE));
        $this->assertTrue($this->hasAction($adminPolicies, Action::READ));
        $this->assertTrue($this->hasAction($adminPolicies, Action::INVITE_MEMBER));
        $this->assertTrue($this->hasAction($adminPolicies, Action::UPDATE));
        $this->assertFalse($this->hasCondition($ownerPolicies, Action::READ));
        $this->assertFalse($this->hasCondition($adminPolicies, Action::READ));
        $this->assertTrue($this->hasCondition($ownerPolicies, Action::UPDATE));
        $this->assertTrue($this->hasCondition($adminPolicies, Action::UPDATE));
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

    /**
     * @param array<\Source\Account\Principal\Domain\Entity\Policy> $policies
     */
    private function hasCondition(array $policies, Action $action): bool
    {
        foreach ($policies as $policy) {
            foreach ($policy->statements() as $statement) {
                if (! in_array($action, $statement->actions(), true)) {
                    continue;
                }

                if ($statement->condition() !== null) {
                    return true;
                }
            }
        }

        return false;
    }
}
