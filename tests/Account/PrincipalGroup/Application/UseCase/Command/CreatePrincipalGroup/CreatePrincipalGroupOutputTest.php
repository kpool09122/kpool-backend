<?php

declare(strict_types=1);

namespace Tests\Account\PrincipalGroup\Application\UseCase\Command\CreatePrincipalGroup;

use DateTimeImmutable;
use DateTimeInterface;
use Source\Account\Principal\Application\UseCase\Command\CreatePrincipalGroup\CreatePrincipalGroupOutput;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Account\Principal\Domain\ValueObject\AccountRole;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class CreatePrincipalGroupOutputTest extends TestCase
{
    /**
     * 正常系: PrincipalGroupがセットされるとtoArrayが正しい値を返すこと.
     */
    public function testToArrayWithPrincipalGroup(): void
    {
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $createdAt = new DateTimeImmutable('2024-01-15T10:00:00+00:00');

        $principalGroup = new PrincipalGroup(
            $principalGroupIdentifier,
            $accountIdentifier,
            'Test Group',
            AccountRole::MEMBER,
            false,
            $createdAt,
        );

        $output = new CreatePrincipalGroupOutput();
        $output->setPrincipalGroup($principalGroup);

        $result = $output->toArray();

        $this->assertSame((string) $principalGroupIdentifier, $result['principalGroupIdentifier']);
        $this->assertSame((string) $accountIdentifier, $result['accountIdentifier']);
        $this->assertSame('Test Group', $result['name']);
        $this->assertSame('member', $result['role']);
        $this->assertFalse($result['isDefault']);
        $this->assertSame($createdAt->format(DateTimeInterface::ATOM), $result['createdAt']);
    }

    /**
     * 正常系: PrincipalGroupが未セットの場合toArrayが空配列を返すこと.
     */
    public function testToArrayWithoutPrincipalGroup(): void
    {
        $output = new CreatePrincipalGroupOutput();
        $this->assertSame([], $output->toArray());
    }
}
