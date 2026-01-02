<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Domain\ValueObject;

use Source\Wiki\Principal\Domain\ValueObject\Policy;
use Source\Wiki\Principal\Domain\ValueObject\Role;
use Tests\TestCase;

class RoleTest extends TestCase
{
    public function testAdministratorPolicies(): void
    {
        $policies = Role::ADMINISTRATOR->policies();
        $this->assertCount(1, $policies);
        $this->assertSame(Policy::FULL_ACCESS, $policies[0]);
    }

    public function testSeniorCollaboratorPolicies(): void
    {
        $policies = Role::SENIOR_COLLABORATOR->policies();
        $this->assertCount(1, $policies);
        $this->assertSame(Policy::FULL_ACCESS, $policies[0]);
    }

    public function testAgencyActorPolicies(): void
    {
        $policies = Role::AGENCY_ACTOR->policies();
        $this->assertCount(2, $policies);
        $this->assertContains(Policy::BASIC_EDITING, $policies);
        $this->assertContains(Policy::AGENCY_MANAGEMENT, $policies);
    }

    public function testGroupActorPolicies(): void
    {
        $policies = Role::GROUP_ACTOR->policies();
        $this->assertCount(3, $policies);
        $this->assertContains(Policy::BASIC_EDITING, $policies);
        $this->assertContains(Policy::GROUP_MANAGEMENT, $policies);
        $this->assertContains(Policy::DENY_AGENCY_APPROVAL, $policies);
    }

    public function testTalentActorPolicies(): void
    {
        $policies = Role::TALENT_ACTOR->policies();
        $this->assertCount(3, $policies);
        $this->assertContains(Policy::BASIC_EDITING, $policies);
        $this->assertContains(Policy::TALENT_MANAGEMENT, $policies);
        $this->assertContains(Policy::DENY_AGENCY_APPROVAL, $policies);
    }

    public function testCollaboratorPolicies(): void
    {
        $policies = Role::COLLABORATOR->policies();
        $this->assertCount(1, $policies);
        $this->assertSame(Policy::BASIC_EDITING, $policies[0]);
    }

    public function testNonePolicies(): void
    {
        $policies = Role::NONE->policies();
        $this->assertEmpty($policies);
    }
}
