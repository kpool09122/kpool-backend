<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Infrastructure\Service;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Principal\Domain\Entity\Policy;
use Source\SiteManagement\Principal\Domain\Entity\Principal;
use Source\SiteManagement\Principal\Domain\Entity\PrincipalGroup;
use Source\SiteManagement\Principal\Domain\Entity\Role;
use Source\SiteManagement\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\Action;
use Source\SiteManagement\Principal\Domain\ValueObject\Effect;
use Source\SiteManagement\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\Resource;
use Source\SiteManagement\Principal\Domain\ValueObject\ResourceType;
use Source\SiteManagement\Principal\Domain\ValueObject\RoleIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\Statement;
use Source\SiteManagement\Principal\Infrastructure\Service\PolicyEvaluator;

class PolicyEvaluatorTest extends TestCase
{
    public function testAbsentGroupsDeny(): void
    {
        $groups = $this->createMock(PrincipalGroupRepositoryInterface::class);
        $groups->expects(self::once())->method('findByPrincipalId')->willReturn([]);
        $roles = $this->createMock(RoleRepositoryInterface::class);
        $roles->expects(self::never())->method('findByIds');
        $policies = $this->createMock(PolicyRepositoryInterface::class);
        $policies->expects(self::never())->method('findByIds');
        $principal = new Principal(new PrincipalIdentifier('00000000-0000-7000-8000-000000000001'), new IdentityIdentifier('00000000-0000-7000-8000-000000000002'));
        self::assertFalse((new PolicyEvaluator($groups, $roles, $policies))->evaluate($principal, Action::CONTACT_VIEW, new Resource(ResourceType::CONTACT)));
    }

    public function testBatchedReadsExplicitDenyAndMissingAttachments(): void
    {
        $principal = new Principal(new PrincipalIdentifier('00000000-0000-7000-8000-000000000001'), new IdentityIdentifier('00000000-0000-7000-8000-000000000002'));
        $roleId = new RoleIdentifier('00000000-0000-7000-8000-000000000003');
        $policyId = new PolicyIdentifier('00000000-0000-7000-8000-000000000004');
        foreach (['allow','deny','no_roles','missing_role','no_policies','missing_policy','no_allow'] as $scenario) {
            $groups = $this->createMock(PrincipalGroupRepositoryInterface::class);
            $group = new PrincipalGroup(new PrincipalGroupIdentifier('00000000-0000-7000-8000-000000000005'), 'any', $scenario === 'no_roles' ? [] : [$roleId,$roleId]);
            $groups->expects(self::once())->method('findByPrincipalId')->with($principal->principalIdentifier())->willReturn([$group,$group]);
            $roles = $this->createMock(RoleRepositoryInterface::class);
            if ($scenario === 'no_roles') {
                $roles->expects(self::never())->method('findByIds');
            } else {
                $roles->expects(self::once())->method('findByIds')->with([$roleId])->willReturn($scenario === 'missing_role' ? [] : [new Role($roleId, 'any', $scenario === 'no_policies' ? [] : [$policyId,$policyId])]);
            }
            $policies = $this->createMock(PolicyRepositoryInterface::class);
            if (in_array($scenario, ['no_roles','missing_role','no_policies'], true)) {
                $policies->expects(self::never())->method('findByIds');
            } else {
                $statements = [new Statement(Effect::ALLOW, [Action::CONTACT_VIEW], [ResourceType::CONTACT])];
                if ($scenario === 'deny') {
                    $statements[] = new Statement(Effect::DENY, [Action::CONTACT_VIEW], [ResourceType::CONTACT]);
                }
                if ($scenario === 'no_allow') {
                    $statements = [];
                }
                $policies->expects(self::once())->method('findByIds')->with([$policyId])->willReturn($scenario === 'missing_policy' ? [] : [new Policy($policyId, 'any', $statements)]);
            }
            self::assertSame($scenario === 'allow', (new PolicyEvaluator($groups, $roles, $policies))->evaluate($principal, Action::CONTACT_VIEW, new Resource(ResourceType::CONTACT)), $scenario);
        }
    }
}
