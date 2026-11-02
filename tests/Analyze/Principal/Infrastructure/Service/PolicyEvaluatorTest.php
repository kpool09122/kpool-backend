<?php

declare(strict_types=1);

namespace Tests\Analyze\Principal\Infrastructure\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Analyze\Principal\Domain\Entity\Policy;
use Source\Analyze\Principal\Domain\Entity\Principal;
use Source\Analyze\Principal\Domain\Entity\PrincipalGroup;
use Source\Analyze\Principal\Domain\Entity\Role;
use Source\Analyze\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Analyze\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Analyze\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Analyze\Principal\Domain\ValueObject\Action;
use Source\Analyze\Principal\Domain\ValueObject\ConditionInterface;
use Source\Analyze\Principal\Domain\ValueObject\Effect;
use Source\Analyze\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Analyze\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Analyze\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Analyze\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Analyze\Principal\Domain\ValueObject\Resource;
use Source\Analyze\Principal\Domain\ValueObject\ResourceType;
use Source\Analyze\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Analyze\Principal\Domain\ValueObject\Statement;
use Source\Analyze\Principal\Infrastructure\Service\PolicyEvaluator;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class PolicyEvaluatorTest extends TestCase
{
    /** @return iterable<string, array{PrincipalStatus}> */
    public static function stoppedStatuses(): iterable
    {
        yield 'suspended' => [PrincipalStatus::SUSPENDED];
        yield 'forced_stop' => [PrincipalStatus::FORCED_STOP];
    }

    #[DataProvider('stoppedStatuses')]
    public function testStoppedPrincipalIsRejectedBeforePolicyLookup(PrincipalStatus $status): void
    {
        $principal = new Principal(
            new PrincipalIdentifier('00000000-0000-7000-8000-000000000001'),
            new IdentityIdentifier('00000000-0000-7000-8000-000000000002'),
            new AccountIdentifier('00000000-0000-7000-8000-000000000009'),
            status: $status,
        );
        $principalGroupRepository = $this->createMock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->expects(self::never())->method('findByPrincipalId');
        $roleRepository = $this->createMock(RoleRepositoryInterface::class);
        $roleRepository->expects(self::never())->method('findByIds');
        $policyRepository = $this->createMock(PolicyRepositoryInterface::class);
        $policyRepository->expects(self::never())->method('findByIds');

        self::assertFalse((new PolicyEvaluator(
            $principalGroupRepository,
            $roleRepository,
            $policyRepository,
        ))->evaluate($principal, Action::ANALYSIS_VIEW, new Resource(ResourceType::ANALYSIS)));
    }

    /** @param Statement[] $statements */
    #[DataProvider('policyDecisions')]
    public function testPolicyDecision(array $statements, Resource $resource, bool $expected): void
    {
        $account = new AccountIdentifier('00000000-0000-7000-8000-000000000009');
        $principal = new Principal(new PrincipalIdentifier('00000000-0000-7000-8000-000000000001'), new IdentityIdentifier('00000000-0000-7000-8000-000000000002'), $account, PrincipalStatus::ACTIVE);
        $roleId = new RoleIdentifier('00000000-0000-7000-8000-000000000003');
        $policyId = new PolicyIdentifier('00000000-0000-7000-8000-000000000004');
        $groups = $this->createStub(PrincipalGroupRepositoryInterface::class);
        $groups->method('findByPrincipalId')->willReturn([
            new PrincipalGroup(new PrincipalGroupIdentifier('00000000-0000-7000-8000-000000000005'), 'General', [$roleId], $account, false, null, []),
        ]);
        $roles = $this->createStub(RoleRepositoryInterface::class);
        $roles->method('findByIds')->willReturn([new Role($roleId, 'General', [$policyId], null)]);
        $policies = $this->createStub(PolicyRepositoryInterface::class);
        $policies->method('findByIds')->willReturn([new Policy($policyId, 'contact', $statements, null)]);

        self::assertSame($expected, (new PolicyEvaluator($groups, $roles, $policies))->evaluate($principal, Action::ANALYSIS_VIEW, $resource));
    }

    /** @return iterable<string, array{Statement[], Resource, bool}> */
    public static function policyDecisions(): iterable
    {
        $resource = new Resource();
        $allow = new Statement(Effect::ALLOW, [Action::ANALYSIS_VIEW], [ResourceType::ANALYSIS]);
        $deny = new Statement(Effect::DENY, [Action::ANALYSIS_VIEW], [ResourceType::ANALYSIS]);
        yield 'implicit deny' => [[], $resource, false];
        yield 'allow all analyses' => [[$allow], $resource, true];
        yield 'deny only' => [[$deny], $resource, false];
        yield 'deny after allow' => [[$allow, $deny], $resource, false];
        yield 'deny before allow' => [[$deny, $allow], $resource, false];
        yield 'no matching action' => [[new Statement(Effect::ALLOW, [], [ResourceType::ANALYSIS])], $resource, false];
        yield 'no matching resource' => [[new Statement(Effect::ALLOW, [Action::ANALYSIS_VIEW], [])], $resource, false];
    }

    public function testConditionalStatementsUsePrincipalAndResourceAndHonorDenyPriority(): void
    {
        $account = new AccountIdentifier('00000000-0000-7000-8000-000000000009');
        $principal = new Principal(new PrincipalIdentifier('00000000-0000-7000-8000-000000000001'), new IdentityIdentifier('00000000-0000-7000-8000-000000000002'), $account, PrincipalStatus::ACTIVE);
        $resource = new Resource(ResourceType::ANALYSIS);
        $roleId = new RoleIdentifier('00000000-0000-7000-8000-000000000003');
        $policyId = new PolicyIdentifier('00000000-0000-7000-8000-000000000004');
        $principalGroupRepository = $this->createStub(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->method('findByPrincipalId')->willReturn([
            new PrincipalGroup(new PrincipalGroupIdentifier('00000000-0000-7000-8000-000000000005'), 'General', [$roleId], $account, false, null, []),
        ]);
        $roleRepository = $this->createStub(RoleRepositoryInterface::class);
        $roleRepository->method('findByIds')->willReturn([new Role($roleId, 'General', [$policyId], null)]);
        foreach ([false, true] as $allowMatches) {
            foreach ([false, true] as $denyMatches) {
                foreach ([false, true] as $denyFirst) {
                    $allowCondition = $this->createMock(ConditionInterface::class);
                    $allowCondition->expects($denyMatches ? self::never() : self::once())->method('matches')->with(self::identicalTo($principal), self::identicalTo($resource))->willReturn($allowMatches);
                    $denyCondition = $this->createMock(ConditionInterface::class);
                    $denyCondition->expects(self::once())->method('matches')->with(self::identicalTo($principal), self::identicalTo($resource))->willReturn($denyMatches);
                    $allow = new Statement(Effect::ALLOW, [Action::ANALYSIS_VIEW], [ResourceType::ANALYSIS], $allowCondition);
                    $deny = new Statement(Effect::DENY, [Action::ANALYSIS_VIEW], [ResourceType::ANALYSIS], $denyCondition);
                    $policyRepository = $this->createStub(PolicyRepositoryInterface::class);
                    $policyRepository->method('findByIds')->willReturn([new Policy($policyId, 'conditional', $denyFirst ? [$deny, $allow] : [$allow, $deny], null)]);

                    self::assertSame($allowMatches && ! $denyMatches, (new PolicyEvaluator($principalGroupRepository, $roleRepository, $policyRepository))->evaluate($principal, Action::ANALYSIS_VIEW, $resource));
                }
            }
        }
    }

    public function testConditionsAreNotEvaluatedForNonMatchingActionsOrResources(): void
    {
        $condition = $this->createMock(ConditionInterface::class);
        $condition->expects(self::never())->method('matches');
        $this->testPolicyDecision([new Statement(Effect::ALLOW, [], [ResourceType::ANALYSIS], $condition)], new Resource(), false);
        $this->testPolicyDecision([new Statement(Effect::DENY, [Action::ANALYSIS_VIEW], [], $condition)], new Resource(), false);
    }

    public function testAnalysisViewHonorsDenyAndAccountAttachmentBoundaries(): void
    {
        $account = new AccountIdentifier('00000000-0000-7000-8000-000000000009');
        $otherAccount = new AccountIdentifier('00000000-0000-7000-8000-000000000010');
        $principal = new Principal(new PrincipalIdentifier('00000000-0000-7000-8000-000000000001'), new IdentityIdentifier('00000000-0000-7000-8000-000000000002'), $account, PrincipalStatus::ACTIVE);
        $roleId = new RoleIdentifier('00000000-0000-7000-8000-000000000003');
        $policyId = new PolicyIdentifier('00000000-0000-7000-8000-000000000004');
        foreach (['allow', 'deny', 'group', 'role', 'policy'] as $scenario) {
            $groups = $this->createStub(PrincipalGroupRepositoryInterface::class);
            $groups->method('findByPrincipalId')->willReturn([
                new PrincipalGroup(new PrincipalGroupIdentifier('00000000-0000-7000-8000-000000000005'), 'Administrator', [$roleId], $scenario === 'group' ? $otherAccount : $account, false, null, []),
            ]);
            $roles = $this->createStub(RoleRepositoryInterface::class);
            $roles->method('findByIds')->willReturn([new Role($roleId, 'Administrator', [$policyId], $scenario === 'role' ? $otherAccount : null)]);
            $statements = [new Statement(Effect::ALLOW, [Action::ANALYSIS_VIEW], [ResourceType::ANALYSIS])];
            if ($scenario === 'deny') {
                $statements[] = new Statement(Effect::DENY, [Action::ANALYSIS_VIEW], [ResourceType::ANALYSIS]);
            }
            $policies = $this->createMock(PolicyRepositoryInterface::class);
            $policies->expects(self::exactly(in_array($scenario, ['group', 'role'], true) ? 0 : 1))->method('findByIds')->willReturn([new Policy($policyId, 'analysis_view', $statements, $scenario === 'policy' ? $otherAccount : null)]);
            $resource = new Resource(ResourceType::ANALYSIS);
            self::assertSame($scenario === 'allow', (new PolicyEvaluator($groups, $roles, $policies))->evaluate($principal, Action::ANALYSIS_VIEW, $resource), $scenario);
        }
    }

    public function testOtherAccountsRolesAndPoliciesDoNotGrantPermissions(): void
    {
        $account = new AccountIdentifier('00000000-0000-7000-8000-000000000009');
        $otherAccount = new AccountIdentifier('00000000-0000-7000-8000-000000000010');
        $principal = new Principal(new PrincipalIdentifier('00000000-0000-7000-8000-000000000001'), new IdentityIdentifier('00000000-0000-7000-8000-000000000002'), $account, PrincipalStatus::ACTIVE);
        $roleId = new RoleIdentifier('00000000-0000-7000-8000-000000000003');
        $policyId = new PolicyIdentifier('00000000-0000-7000-8000-000000000004');
        foreach (['role', 'policy'] as $foreignOwner) {
            $groups = $this->createStub(PrincipalGroupRepositoryInterface::class);
            $groups->method('findByPrincipalId')->willReturn([
                new PrincipalGroup(new PrincipalGroupIdentifier('00000000-0000-7000-8000-000000000005'), 'any', [$roleId], $account, false, null, []),
            ]);
            $roles = $this->createStub(RoleRepositoryInterface::class);
            $roles->method('findByIds')->willReturn([new Role($roleId, 'any', [$policyId], $foreignOwner === 'role' ? $otherAccount : $account)]);
            $policies = $this->createMock(PolicyRepositoryInterface::class);
            if ($foreignOwner === 'role') {
                $policies->expects(self::never())->method('findByIds');
            } else {
                $policies->expects(self::once())->method('findByIds')->willReturn([
                    new Policy($policyId, 'any', [new Statement(Effect::ALLOW, [Action::ANALYSIS_VIEW], [ResourceType::ANALYSIS])], $otherAccount),
                ]);
            }
            self::assertFalse((new PolicyEvaluator($groups, $roles, $policies))->evaluate($principal, Action::ANALYSIS_VIEW, new Resource(ResourceType::ANALYSIS)), $foreignOwner);
        }
    }

    public function testAbsentGroupsDeny(): void
    {
        $groups = $this->createMock(PrincipalGroupRepositoryInterface::class);
        $groups->expects(self::once())->method('findByPrincipalId')->willReturn([]);
        $roles = $this->createMock(RoleRepositoryInterface::class);
        $roles->expects(self::never())->method('findByIds');
        $policies = $this->createMock(PolicyRepositoryInterface::class);
        $policies->expects(self::never())->method('findByIds');
        $principal = new Principal(new PrincipalIdentifier('00000000-0000-7000-8000-000000000001'), new IdentityIdentifier('00000000-0000-7000-8000-000000000002'), new AccountIdentifier('00000000-0000-7000-8000-000000000009'), PrincipalStatus::ACTIVE);
        self::assertFalse((new PolicyEvaluator($groups, $roles, $policies))->evaluate($principal, Action::ANALYSIS_VIEW, new Resource(ResourceType::ANALYSIS)));
    }

    public function testBatchedReadsExplicitDenyAndMissingAttachments(): void
    {
        $principal = new Principal(new PrincipalIdentifier('00000000-0000-7000-8000-000000000001'), new IdentityIdentifier('00000000-0000-7000-8000-000000000002'), new AccountIdentifier('00000000-0000-7000-8000-000000000009'), PrincipalStatus::ACTIVE);
        $roleId = new RoleIdentifier('00000000-0000-7000-8000-000000000003');
        $policyId = new PolicyIdentifier('00000000-0000-7000-8000-000000000004');
        foreach (['allow','deny','no_roles','missing_role','no_policies','missing_policy','no_allow'] as $scenario) {
            $groups = $this->createMock(PrincipalGroupRepositoryInterface::class);
            $group = new PrincipalGroup(new PrincipalGroupIdentifier('00000000-0000-7000-8000-000000000005'), 'any', $scenario === 'no_roles' ? [] : [$roleId,$roleId], new AccountIdentifier('00000000-0000-7000-8000-000000000009'), false, null, []);
            $groups->expects(self::once())->method('findByPrincipalId')->with($principal->principalIdentifier())->willReturn([$group,$group]);
            $roles = $this->createMock(RoleRepositoryInterface::class);
            if ($scenario === 'no_roles') {
                $roles->expects(self::never())->method('findByIds');
            } else {
                $roles->expects(self::once())->method('findByIds')->with([$roleId])->willReturn($scenario === 'missing_role' ? [] : [new Role($roleId, 'any', $scenario === 'no_policies' ? [] : [$policyId,$policyId], null)]);
            }
            $policies = $this->createMock(PolicyRepositoryInterface::class);
            if (in_array($scenario, ['no_roles','missing_role','no_policies'], true)) {
                $policies->expects(self::never())->method('findByIds');
            } else {
                $statements = [new Statement(Effect::ALLOW, [Action::ANALYSIS_VIEW], [ResourceType::ANALYSIS])];
                if ($scenario === 'deny') {
                    $statements[] = new Statement(Effect::DENY, [Action::ANALYSIS_VIEW], [ResourceType::ANALYSIS]);
                }
                if ($scenario === 'no_allow') {
                    $statements = [];
                }
                $policies->expects(self::once())->method('findByIds')->with([$policyId])->willReturn($scenario === 'missing_policy' ? [] : [new Policy($policyId, 'any', $statements, null)]);
            }
            self::assertSame($scenario === 'allow', (new PolicyEvaluator($groups, $roles, $policies))->evaluate($principal, Action::ANALYSIS_VIEW, new Resource(ResourceType::ANALYSIS)), $scenario);
        }
    }
}
