<?php

declare(strict_types=1);

namespace Tests\Monetization\Principal\Domain\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Monetization\Principal\Domain\Entity\Principal;
use Source\Monetization\Principal\Domain\Entity\PrincipalGroup;
use Source\Monetization\Principal\Domain\Entity\Role;
use Source\Monetization\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Monetization\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Monetization\Principal\Domain\Service\PrincipalGroupManagerContinuity;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Monetization\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class PrincipalGroupManagerContinuityTest extends TestCase
{
    /** @return array<string, array{PrincipalStatus, bool, bool, bool}> */
    public static function candidates(): array
    {
        return [
            'active allowed reachable' => [PrincipalStatus::ACTIVE, true, true, true],
            'deny overrides owner' => [PrincipalStatus::ACTIVE, false, true, false],
            'unreachable' => [PrincipalStatus::ACTIVE, true, false, false],
            'suspended' => [PrincipalStatus::SUSPENDED, true, true, false],
            'forced stop' => [PrincipalStatus::FORCED_STOP, true, true, false],
        ];
    }

    #[DataProvider('candidates')]
    public function testEvaluatesFinalGroupsStatusPermissionAndReachability(PrincipalStatus $status, bool $allowed, bool $reachable, bool $expected): void
    {
        $account = new AccountIdentifier('019c9b4c-0000-7000-8000-000000000001');
        $id = new PrincipalIdentifier('019c9b4c-0000-7000-8000-000000000002');
        $roleId = new RoleIdentifier('019c9b4c-0000-7000-8000-000000000003');
        $principal = new Principal($id, new IdentityIdentifier('019c9b4c-0000-7000-8000-000000000004'), $account, $status);
        $group = new PrincipalGroup(new PrincipalGroupIdentifier('019c9b4c-0000-7000-8000-000000000005'), 'Arbitrary name', [$roleId], $account, false, null, [$id]);
        $roles = $this->createStub(RoleRepositoryInterface::class);
        $roles->method('findSystemByName')->willReturn(new Role($roleId, 'Owner', [], null));
        $evaluator = $this->createStub(PolicyEvaluatorInterface::class);
        $evaluator->method('evaluate')->willReturn($allowed);
        $subject = new PrincipalGroupManagerContinuity($roles, $evaluator);
        self::assertSame($expected, $subject->hasManager([$group], [(string) $id => $principal], static fn (): bool => $reachable));
        self::assertFalse($subject->hasManager([$group], [], static fn (): bool => true));
        $group->removeRole($roleId);
        self::assertFalse($subject->hasManager([$group], [(string) $id => $principal], static fn (): bool => true));
    }
}
