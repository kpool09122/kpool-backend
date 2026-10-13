<?php

declare(strict_types=1);

namespace Tests\Account\Notification\Application\Service;

use DateTimeImmutable;
use Mockery;
use Source\Account\Account\Application\Exception\AccountUpdateForbiddenException;
use Source\Account\Notification\Application\Service\NotificationSettingsAuthorization;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class NotificationSettingsAuthorizationTest extends TestCase
{
    public function testOnlyOwnersMembershipGrantsAccessRegardlessOfRoles(): void
    {
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), new IdentityIdentifier(StrTestHelper::generateUuid()), $account);
        foreach (['Owners', 'Owner', 'Editors'] as $name) {
            $group = new PrincipalGroup(new PrincipalGroupIdentifier(StrTestHelper::generateUuid()), $account, $name, false, new DateTimeImmutable(), [new RoleIdentifier(StrTestHelper::generateUuid())]);
            $group->addMember($principal->principalIdentifier());
            /** @var PrincipalGroupRepositoryInterface&Mockery\MockInterface $repository */
            $repository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
            $repository->shouldReceive('findByAccountIdAndPrincipal')->once()->with($account, $principal->principalIdentifier())->andReturn([$group]);
            $this->assertSame($name === 'Owners', (new NotificationSettingsAuthorization($repository))->canManage($account, $principal));
        }
    }

    public function testDifferentAccountAndDelegatedOwnersAreForbidden(): void
    {
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), new IdentityIdentifier(StrTestHelper::generateUuid()), $account);
        $group = new PrincipalGroup(new PrincipalGroupIdentifier(StrTestHelper::generateUuid()), $account, 'Owners', false, new DateTimeImmutable(), [], new DelegationIdentifier(StrTestHelper::generateUuid()));
        $group->addMember($principal->principalIdentifier());
        /** @var PrincipalGroupRepositoryInterface&Mockery\MockInterface $repository */
        $repository = Mockery::mock(PrincipalGroupRepositoryInterface::class);
        $repository->shouldReceive('findByAccountIdAndPrincipal')->once()->with($account, $principal->principalIdentifier())->andReturn([$group]);
        $authorization = new NotificationSettingsAuthorization($repository);
        $this->assertFalse($authorization->canManage($account, $principal));
        $this->expectException(AccountUpdateForbiddenException::class);
        $authorization->assertAllowed(new AccountIdentifier(StrTestHelper::generateUuid()), $principal);
    }
}
