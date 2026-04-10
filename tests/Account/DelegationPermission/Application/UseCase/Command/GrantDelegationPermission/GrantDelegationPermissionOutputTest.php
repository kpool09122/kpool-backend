<?php

declare(strict_types=1);

namespace Tests\Account\DelegationPermission\Application\UseCase\Command\GrantDelegationPermission;

use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use Source\Account\DelegationPermission\Application\UseCase\Command\GrantDelegationPermission\GrantDelegationPermissionOutput;
use Source\Account\DelegationPermission\Domain\Entity\DelegationPermission;
use Source\Account\DelegationPermission\Domain\ValueObject\DelegationPermissionIdentifier;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Account\Shared\Domain\ValueObject\IdentityGroupIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;

class GrantDelegationPermissionOutputTest extends TestCase
{
    public function testToArrayWithDelegationPermission(): void
    {
        $delegationPermissionIdentifier = new DelegationPermissionIdentifier(StrTestHelper::generateUuid());
        $identityGroupIdentifier = new IdentityGroupIdentifier(StrTestHelper::generateUuid());
        $targetAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $createdAt = new DateTimeImmutable();

        $delegationPermission = new DelegationPermission(
            $delegationPermissionIdentifier,
            $identityGroupIdentifier,
            $targetAccountIdentifier,
            $affiliationIdentifier,
            $createdAt,
        );

        $output = new GrantDelegationPermissionOutput();
        $output->setDelegationPermission($delegationPermission);

        $result = $output->toArray();

        $this->assertSame((string) $delegationPermissionIdentifier, $result['delegationPermissionIdentifier']);
        $this->assertSame((string) $identityGroupIdentifier, $result['identityGroupIdentifier']);
        $this->assertSame((string) $targetAccountIdentifier, $result['targetAccountIdentifier']);
        $this->assertSame((string) $affiliationIdentifier, $result['affiliationIdentifier']);
        $this->assertSame($createdAt->format(DateTimeInterface::ATOM), $result['createdAt']);
    }

    public function testToArrayWithoutDelegationPermission(): void
    {
        $output = new GrantDelegationPermissionOutput();
        $this->assertSame([], $output->toArray());
    }
}
