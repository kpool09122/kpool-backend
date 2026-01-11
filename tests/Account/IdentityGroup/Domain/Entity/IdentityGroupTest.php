<?php

declare(strict_types=1);

namespace Tests\Account\IdentityGroup\Domain\Entity;

use DateTimeImmutable;
use DomainException;
use PHPUnit\Framework\TestCase;
use Source\Account\IdentityGroup\Domain\Entity\IdentityGroup;
use Source\Account\IdentityGroup\Domain\ValueObject\AccountRole;
use Source\Account\Shared\Domain\ValueObject\IdentityGroupIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;

class IdentityGroupTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     */
    public function test__construct(): void
    {
        $identityGroupIdentifier = new IdentityGroupIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $createdAt = new DateTimeImmutable('2024-01-01T00:00:00+00:00');

        $identityGroup = new IdentityGroup(
            identityGroupIdentifier: $identityGroupIdentifier,
            accountIdentifier: $accountIdentifier,
            name: 'オーナーグループ',
            role: AccountRole::OWNER,
            isDefault: true,
            createdAt: $createdAt,
        );

        $this->assertSame($identityGroupIdentifier, $identityGroup->identityGroupIdentifier());
        $this->assertSame($accountIdentifier, $identityGroup->accountIdentifier());
        $this->assertSame('オーナーグループ', $identityGroup->name());
        $this->assertSame(AccountRole::OWNER, $identityGroup->role());
        $this->assertTrue($identityGroup->isDefault());
        $this->assertSame($createdAt, $identityGroup->createdAt());
        $this->assertEmpty($identityGroup->members());
    }

    /**
     * 正常系: メンバーを追加できること
     */
    public function testAddMember(): void
    {
        $identityGroup = $this->createIdentityGroup();
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $identityGroup->addMember($identityIdentifier);

        $this->assertCount(1, $identityGroup->members());
        $this->assertTrue($identityGroup->hasMember($identityIdentifier));
    }

    /**
     * 異常系: 同じメンバーを二重に追加しようとすると例外がスローされること
     */
    public function testAddMemberThrowsExceptionWhenAlreadyMember(): void
    {
        $identityGroup = $this->createIdentityGroup();
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $identityGroup->addMember($identityIdentifier);

        $this->expectException(DomainException::class);
        $identityGroup->addMember($identityIdentifier);
    }

    /**
     * 正常系: メンバーを削除できること
     */
    public function testRemoveMember(): void
    {
        $identityGroup = $this->createIdentityGroup();
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $identityGroup->addMember($identityIdentifier);
        $identityGroup->removeMember($identityIdentifier);

        $this->assertCount(0, $identityGroup->members());
        $this->assertFalse($identityGroup->hasMember($identityIdentifier));
    }

    /**
     * 異常系: 存在しないメンバーを削除しようとすると例外がスローされること
     */
    public function testRemoveMemberThrowsExceptionWhenNotMember(): void
    {
        $identityGroup = $this->createIdentityGroup();
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $this->expectException(DomainException::class);
        $identityGroup->removeMember($identityIdentifier);
    }

    /**
     * 正常系: メンバー数を取得できること
     */
    public function testMemberCount(): void
    {
        $identityGroup = $this->createIdentityGroup();

        $this->assertSame(0, $identityGroup->memberCount());

        $identityGroup->addMember(new IdentityIdentifier(StrTestHelper::generateUuid()));
        $identityGroup->addMember(new IdentityIdentifier(StrTestHelper::generateUuid()));

        $this->assertSame(2, $identityGroup->memberCount());
    }

    private function createIdentityGroup(): IdentityGroup
    {
        return new IdentityGroup(
            identityGroupIdentifier: new IdentityGroupIdentifier(StrTestHelper::generateUuid()),
            accountIdentifier: new AccountIdentifier(StrTestHelper::generateUuid()),
            name: 'テストグループ',
            role: AccountRole::MEMBER,
            isDefault: false,
            createdAt: new DateTimeImmutable(),
        );
    }
}
