<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Tests\Helper\StrTestHelper;

class PrincipalGroupTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     */
    public function test__construct(): void
    {
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $name = 'Default Group';
        $isDefault = true;
        $createdAt = new DateTimeImmutable();

        $principalGroup = new PrincipalGroup(
            $principalGroupIdentifier,
            $accountIdentifier,
            $name,
            $isDefault,
            $createdAt,
        );

        $this->assertSame($principalGroupIdentifier, $principalGroup->principalGroupIdentifier());
        $this->assertSame($accountIdentifier, $principalGroup->accountIdentifier());
        $this->assertSame($name, $principalGroup->name());
        $this->assertTrue($principalGroup->isDefault());
        $this->assertSame($createdAt, $principalGroup->createdAt());
    }

    /**
     * 正常系: 非デフォルトのPrincipalGroupが作成できること
     */
    public function testNonDefaultPrincipalGroup(): void
    {
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $name = 'Custom Group';
        $isDefault = false;
        $createdAt = new DateTimeImmutable();

        $principalGroup = new PrincipalGroup(
            $principalGroupIdentifier,
            $accountIdentifier,
            $name,
            $isDefault,
            $createdAt,
        );

        $this->assertFalse($principalGroup->isDefault());
    }
}
