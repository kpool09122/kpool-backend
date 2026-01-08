<?php

declare(strict_types=1);

namespace Tests\Account\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Account\Domain\Entity\DelegationPermission;
use Source\Account\Domain\ValueObject\AffiliationIdentifier;
use Source\Account\Domain\ValueObject\DelegationPermissionIdentifier;
use Source\Account\Domain\ValueObject\IdentityGroupIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

class DelegationPermissionTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     */
    public function test__construct(): void
    {
        $delegationPermissionIdentifier = new DelegationPermissionIdentifier('01945a3d-4c5b-7abc-8def-1234567890ab');
        $identityGroupIdentifier = new IdentityGroupIdentifier('01945a3d-4c5b-7abc-8def-1234567890ac');
        $targetAccountIdentifier = new AccountIdentifier('01945a3d-4c5b-7abc-8def-1234567890ad');
        $affiliationIdentifier = new AffiliationIdentifier('01945a3d-4c5b-7abc-8def-1234567890ae');
        $createdAt = new DateTimeImmutable('2024-01-01T00:00:00+00:00');

        $permission = new DelegationPermission(
            delegationPermissionIdentifier: $delegationPermissionIdentifier,
            identityGroupIdentifier: $identityGroupIdentifier,
            targetAccountIdentifier: $targetAccountIdentifier,
            affiliationIdentifier: $affiliationIdentifier,
            createdAt: $createdAt,
        );

        $this->assertSame($delegationPermissionIdentifier, $permission->delegationPermissionIdentifier());
        $this->assertSame($identityGroupIdentifier, $permission->identityGroupIdentifier());
        $this->assertSame($targetAccountIdentifier, $permission->targetAccountIdentifier());
        $this->assertSame($affiliationIdentifier, $permission->affiliationIdentifier());
        $this->assertSame($createdAt, $permission->createdAt());
    }
}
