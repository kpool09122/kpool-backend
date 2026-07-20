<?php

declare(strict_types=1);

namespace Tests\Account\PrincipalGroup\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Account\Principal\Domain\Exception\PrincipalAlreadyMemberException;
use Source\Account\Principal\Domain\Exception\PrincipalNotMemberException;
use Source\Account\Principal\Domain\ValueObject\AccountRole;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
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
        $createdAt = new DateTimeImmutable('2024-01-01T00:00:00+00:00');

        $principalGroup = new PrincipalGroup(
            principalGroupIdentifier: $principalGroupIdentifier,
            accountIdentifier: $accountIdentifier,
            name: 'オーナーグループ',
            role: AccountRole::OWNER,
            isDefault: true,
            createdAt: $createdAt,
        );

        $this->assertSame($principalGroupIdentifier, $principalGroup->principalGroupIdentifier());
        $this->assertSame($accountIdentifier, $principalGroup->accountIdentifier());
        $this->assertSame('オーナーグループ', $principalGroup->name());
        $this->assertSame(AccountRole::OWNER, $principalGroup->role());
        $this->assertTrue($principalGroup->isDefault());
        $this->assertSame($createdAt, $principalGroup->createdAt());
        $this->assertEmpty($principalGroup->members());
    }

    /**
     * 正常系: メンバーを追加できること
     */
    public function testAddMember(): void
    {
        $principalGroup = $this->createPrincipalGroup();
        $principalIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $principalGroup->addMember($principalIdentifier);

        $this->assertCount(1, $principalGroup->members());
        $this->assertTrue($principalGroup->hasMember($principalIdentifier));
    }

    /**
     * 異常系: 同じメンバーを二重に追加しようとすると例外がスローされること
     */
    public function testAddMemberThrowsExceptionWhenAlreadyMember(): void
    {
        $principalGroup = $this->createPrincipalGroup();
        $principalIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $principalGroup->addMember($principalIdentifier);

        $this->expectException(PrincipalAlreadyMemberException::class);
        $principalGroup->addMember($principalIdentifier);
    }

    /**
     * 正常系: メンバーを削除できること
     */
    public function testRemoveMember(): void
    {
        $principalGroup = $this->createPrincipalGroup();
        $principalIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $principalGroup->addMember($principalIdentifier);
        $principalGroup->removeMember($principalIdentifier);

        $this->assertCount(0, $principalGroup->members());
        $this->assertFalse($principalGroup->hasMember($principalIdentifier));
    }

    /**
     * 異常系: 存在しないメンバーを削除しようとすると例外がスローされること
     */
    public function testRemoveMemberThrowsExceptionWhenNotMember(): void
    {
        $principalGroup = $this->createPrincipalGroup();
        $principalIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $this->expectException(PrincipalNotMemberException::class);
        $principalGroup->removeMember($principalIdentifier);
    }

    /**
     * 正常系: メンバー数を取得できること
     */
    public function testMemberCount(): void
    {
        $principalGroup = $this->createPrincipalGroup();

        $this->assertSame(0, $principalGroup->memberCount());

        $principalGroup->addMember(new IdentityIdentifier(StrTestHelper::generateUuid()));
        $principalGroup->addMember(new IdentityIdentifier(StrTestHelper::generateUuid()));

        $this->assertSame(2, $principalGroup->memberCount());
    }

    private function createPrincipalGroup(): PrincipalGroup
    {
        return new PrincipalGroup(
            principalGroupIdentifier: new PrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            accountIdentifier: new AccountIdentifier(StrTestHelper::generateUuid()),
            name: 'テストグループ',
            role: AccountRole::MEMBER,
            isDefault: false,
            createdAt: new DateTimeImmutable(),
        );
    }
}
