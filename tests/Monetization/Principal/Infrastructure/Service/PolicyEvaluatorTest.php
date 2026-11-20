<?php

declare(strict_types=1);

namespace Tests\Monetization\Principal\Infrastructure\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Monetization\Principal\Domain\Entity\Policy;
use Source\Monetization\Principal\Domain\Entity\Principal;
use Source\Monetization\Principal\Domain\Entity\PrincipalGroup;
use Source\Monetization\Principal\Domain\Entity\Role;
use Source\Monetization\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Monetization\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Monetization\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Monetization\Principal\Domain\ValueObject\Action;
use Source\Monetization\Principal\Domain\ValueObject\Effect;
use Source\Monetization\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Monetization\Principal\Domain\ValueObject\Resource;
use Source\Monetization\Principal\Domain\ValueObject\ResourceType;
use Source\Monetization\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\Statement;
use Source\Monetization\Principal\Infrastructure\Service\PolicyEvaluator;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\MonetizationPrincipalTestData;

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
        ))->evaluate($principal, Action::PRINCIPAL_GROUP_READ, new Resource(ResourceType::PRINCIPAL_GROUP, new AccountIdentifier('00000000-0000-7000-8000-000000000009'))));
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

        self::assertSame($expected, (new PolicyEvaluator($groups, $roles, $policies))->evaluate($principal, Action::PRINCIPAL_GROUP_READ, $resource));
    }

    /** @return iterable<string, array{Statement[], Resource, bool}> */
    public static function policyDecisions(): iterable
    {
        $resource = new Resource(ResourceType::PRINCIPAL_GROUP, new AccountIdentifier('00000000-0000-7000-8000-000000000009'));
        $allow = new Statement(Effect::ALLOW, [Action::PRINCIPAL_GROUP_READ], [ResourceType::PRINCIPAL_GROUP]);
        $deny = new Statement(Effect::DENY, [Action::PRINCIPAL_GROUP_READ], [ResourceType::PRINCIPAL_GROUP]);
        yield 'implicit deny' => [[], $resource, false];
        yield 'allow group reads' => [[$allow], $resource, true];
        yield 'deny only' => [[$deny], $resource, false];
        yield 'deny after allow' => [[$allow, $deny], $resource, false];
        yield 'deny before allow' => [[$deny, $allow], $resource, false];
        yield 'no matching action' => [[new Statement(Effect::ALLOW, [], [ResourceType::PRINCIPAL_GROUP])], $resource, false];
        yield 'no matching resource' => [[new Statement(Effect::ALLOW, [Action::PRINCIPAL_GROUP_READ], [])], $resource, false];
    }

    public function testGroupReadHonorsDenyAndAccountAttachmentBoundaries(): void
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
            $statements = [new Statement(Effect::ALLOW, [Action::PRINCIPAL_GROUP_READ], [ResourceType::PRINCIPAL_GROUP])];
            if ($scenario === 'deny') {
                $statements[] = new Statement(Effect::DENY, [Action::PRINCIPAL_GROUP_READ], [ResourceType::PRINCIPAL_GROUP]);
            }
            $policies = $this->createMock(PolicyRepositoryInterface::class);
            $policies->expects(self::exactly(in_array($scenario, ['group', 'role'], true) ? 0 : 1))->method('findByIds')->willReturn([new Policy($policyId, 'group_read', $statements, $scenario === 'policy' ? $otherAccount : null)]);
            $resource = new Resource(ResourceType::PRINCIPAL_GROUP, new AccountIdentifier('00000000-0000-7000-8000-000000000009'));
            self::assertSame($scenario === 'allow', (new PolicyEvaluator($groups, $roles, $policies))->evaluate($principal, Action::PRINCIPAL_GROUP_READ, $resource), $scenario);
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
                    new Policy($policyId, 'any', [new Statement(Effect::ALLOW, [Action::PRINCIPAL_GROUP_READ], [ResourceType::PRINCIPAL_GROUP])], $otherAccount),
                ]);
            }
            self::assertFalse((new PolicyEvaluator($groups, $roles, $policies))->evaluate($principal, Action::PRINCIPAL_GROUP_READ, new Resource(ResourceType::PRINCIPAL_GROUP, new AccountIdentifier('00000000-0000-7000-8000-000000000009'))), $foreignOwner);
        }
    }

    public function testSuppliedGroupsCannotBypassMembershipOrAccountBoundaries(): void
    {
        $data = MonetizationPrincipalTestData::create();
        $foreignAccount = new AccountIdentifier('00000000-0000-7000-8000-000000000010');
        $principalGroupRepository = $this->createMock(PrincipalGroupRepositoryInterface::class);
        $principalGroupRepository->expects(self::never())->method('findByPrincipalId');
        $roleRepository = $this->createMock(RoleRepositoryInterface::class);
        $roleRepository->expects(self::never())->method('findByIds');
        $policyRepository = $this->createMock(PolicyRepositoryInterface::class);
        $policyRepository->expects(self::never())->method('findByIds');
        $evaluator = new PolicyEvaluator($principalGroupRepository, $roleRepository, $policyRepository);
        $group = new PrincipalGroup($data->group->principalGroupIdentifier(), 'owners', [$data->role->roleIdentifier()], $data->principal->accountIdentifier(), false, null, []);
        $resource = new Resource(ResourceType::PRINCIPAL_GROUP, $data->principal->accountIdentifier());
        self::assertFalse($evaluator->evaluate($data->principal, Action::PRINCIPAL_GROUP_READ, $resource, [$group]));
        $foreignGroup = new PrincipalGroup($data->group->principalGroupIdentifier(), 'owners', [$data->role->roleIdentifier()], $foreignAccount, false, null, [$data->principal->principalIdentifier()]);
        self::assertFalse($evaluator->evaluate($data->principal, Action::PRINCIPAL_GROUP_READ, $resource, [$foreignGroup]));
        $group->addMember($data->principal->principalIdentifier());
        self::assertFalse($evaluator->evaluate($data->principal, Action::PRINCIPAL_GROUP_READ, new Resource(ResourceType::PRINCIPAL_GROUP, $foreignAccount), [$group]));
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
        self::assertFalse((new PolicyEvaluator($groups, $roles, $policies))->evaluate($principal, Action::PRINCIPAL_GROUP_READ, new Resource(ResourceType::PRINCIPAL_GROUP, new AccountIdentifier('00000000-0000-7000-8000-000000000009'))));
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
                $statements = [new Statement(Effect::ALLOW, [Action::PRINCIPAL_GROUP_READ], [ResourceType::PRINCIPAL_GROUP])];
                if ($scenario === 'deny') {
                    $statements[] = new Statement(Effect::DENY, [Action::PRINCIPAL_GROUP_READ], [ResourceType::PRINCIPAL_GROUP]);
                }
                if ($scenario === 'no_allow') {
                    $statements = [];
                }
                $policies->expects(self::once())->method('findByIds')->with([$policyId])->willReturn($scenario === 'missing_policy' ? [] : [new Policy($policyId, 'any', $statements, null)]);
            }
            self::assertSame($scenario === 'allow', (new PolicyEvaluator($groups, $roles, $policies))->evaluate($principal, Action::PRINCIPAL_GROUP_READ, new Resource(ResourceType::PRINCIPAL_GROUP, new AccountIdentifier('00000000-0000-7000-8000-000000000009'))), $scenario);
        }
    }
}
