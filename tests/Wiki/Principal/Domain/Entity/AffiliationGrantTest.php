<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Wiki\Principal\Domain\Entity\AffiliationGrant;
use Source\Wiki\Principal\Domain\ValueObject\AffiliationGrantIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\AffiliationGrantType;
use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;
use Tests\Helper\StrTestHelper;

class AffiliationGrantTest extends TestCase
{
    /**
     * 正常系: インスタンスが正しく作成できること.
     */
    public function test__construct(): void
    {
        $affiliationGrantIdentifier = new AffiliationGrantIdentifier(StrTestHelper::generateUuid());
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $policyIdentifier = new PolicyIdentifier(StrTestHelper::generateUuid());
        $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $type = AffiliationGrantType::TALENT_SIDE;
        $createdAt = new DateTimeImmutable();

        $affiliationGrant = new AffiliationGrant(
            $affiliationGrantIdentifier,
            $affiliationIdentifier,
            $policyIdentifier,
            $roleIdentifier,
            $principalGroupIdentifier,
            $type,
            $createdAt,
        );

        $this->assertSame($affiliationGrantIdentifier, $affiliationGrant->affiliationGrantIdentifier());
        $this->assertSame($affiliationIdentifier, $affiliationGrant->affiliationIdentifier());
        $this->assertSame($policyIdentifier, $affiliationGrant->policyIdentifier());
        $this->assertSame($roleIdentifier, $affiliationGrant->roleIdentifier());
        $this->assertSame($principalGroupIdentifier, $affiliationGrant->principalGroupIdentifier());
        $this->assertSame($type, $affiliationGrant->type());
        $this->assertSame($createdAt, $affiliationGrant->createdAt());
    }
}
