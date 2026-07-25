<?php

declare(strict_types=1);

namespace Tests\Account\PrincipalGroup\Application\UseCase\Command\RemovePrincipalFromPrincipalGroup;

use DateTimeImmutable;
use Source\Account\Principal\Application\UseCase\Command\RemovePrincipalFromPrincipalGroup\RemovePrincipalFromPrincipalGroupOutput;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Account\Principal\Domain\ValueObject\AccountRole;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RemovePrincipalFromPrincipalGroupOutputTest extends TestCase
{
    /**
     * 正常系: メンバー削除後のPrincipalGroupがtoArrayで正しい値を返すこと.
     */
    public function testToArrayWithPrincipalGroup(): void
    {
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $remainingMember = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $principalGroup = new PrincipalGroup(
            $principalGroupIdentifier,
            $accountIdentifier,
            'Test Group',
            AccountRole::BASIC,
            false,
            new DateTimeImmutable(),
        );
        $principalGroup->addMember($remainingMember);

        $output = new RemovePrincipalFromPrincipalGroupOutput();
        $output->setPrincipalGroup($principalGroup);

        $result = $output->toArray();

        $this->assertSame((string) $principalGroupIdentifier, $result['principalGroupIdentifier']);
        $this->assertSame((string) $accountIdentifier, $result['accountIdentifier']);
        $this->assertSame('Test Group', $result['name']);
        $this->assertSame('basic', $result['role']);
        $this->assertFalse($result['isDefault']);
        $this->assertCount(1, $result['members']);
        $this->assertSame((string) $remainingMember, $result['members'][0]);
    }

    /**
     * 正常系: PrincipalGroupが未セットの場合toArrayが空配列を返すこと.
     */
    public function testToArrayWithoutPrincipalGroup(): void
    {
        $output = new RemovePrincipalFromPrincipalGroupOutput();
        $this->assertSame([], $output->toArray());
    }
}
