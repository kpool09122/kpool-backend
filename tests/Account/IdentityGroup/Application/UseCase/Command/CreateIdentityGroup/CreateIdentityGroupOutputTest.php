<?php

declare(strict_types=1);

namespace Tests\Account\IdentityGroup\Application\UseCase\Command\CreateIdentityGroup;

use DateTimeImmutable;
use DateTimeInterface;
use Source\Account\IdentityGroup\Application\UseCase\Command\CreateIdentityGroup\CreateIdentityGroupOutput;
use Source\Account\IdentityGroup\Domain\Entity\IdentityGroup;
use Source\Account\IdentityGroup\Domain\ValueObject\AccountRole;
use Source\Account\Shared\Domain\ValueObject\IdentityGroupIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class CreateIdentityGroupOutputTest extends TestCase
{
    /**
     * 正常系: IdentityGroupがセットされるとtoArrayが正しい値を返すこと.
     */
    public function testToArrayWithIdentityGroup(): void
    {
        $identityGroupIdentifier = new IdentityGroupIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $createdAt = new DateTimeImmutable('2024-01-15T10:00:00+00:00');

        $identityGroup = new IdentityGroup(
            $identityGroupIdentifier,
            $accountIdentifier,
            'Test Group',
            AccountRole::MEMBER,
            false,
            $createdAt,
        );

        $output = new CreateIdentityGroupOutput();
        $output->setIdentityGroup($identityGroup);

        $result = $output->toArray();

        $this->assertSame((string) $identityGroupIdentifier, $result['identityGroupIdentifier']);
        $this->assertSame((string) $accountIdentifier, $result['accountIdentifier']);
        $this->assertSame('Test Group', $result['name']);
        $this->assertSame('member', $result['role']);
        $this->assertFalse($result['isDefault']);
        $this->assertSame($createdAt->format(DateTimeInterface::ATOM), $result['createdAt']);
    }

    /**
     * 正常系: IdentityGroupが未セットの場合toArrayが空配列を返すこと.
     */
    public function testToArrayWithoutIdentityGroup(): void
    {
        $output = new CreateIdentityGroupOutput();
        $this->assertSame([], $output->toArray());
    }
}
