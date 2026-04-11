<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\CreatePrincipalGroup;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Principal\Application\UseCase\Command\CreatePrincipalGroup\CreatePrincipalGroupOutput;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
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
        $name = 'Test Group';
        $isDefault = false;
        $createdAt = new DateTimeImmutable();

        $principalGroup = new PrincipalGroup(
            $principalGroupIdentifier,
            $accountIdentifier,
            $name,
            $isDefault,
            $createdAt,
        );

        $output = new CreatePrincipalGroupOutput();
        $output->setPrincipalGroup($principalGroup);

        $result = $output->toArray();

        $this->assertSame((string) $principalGroupIdentifier, $result['principalGroupIdentifier']);
        $this->assertSame((string) $accountIdentifier, $result['accountIdentifier']);
        $this->assertSame($name, $result['name']);
        $this->assertSame($isDefault, $result['isDefault']);
        $this->assertSame(0, $result['memberCount']);
        $this->assertSame($createdAt->format('Y-m-d\TH:i:sP'), $result['createdAt']);
    }

    /**
     * 正常系: PrincipalGroupが未セットの場合toArrayがnull値の配列を返すこと.
     */
    public function testToArrayWithoutPrincipalGroup(): void
    {
        $output = new CreatePrincipalGroupOutput();

        $result = $output->toArray();

        $this->assertNull($result['principalGroupIdentifier']);
        $this->assertNull($result['accountIdentifier']);
        $this->assertNull($result['name']);
        $this->assertNull($result['isDefault']);
        $this->assertNull($result['memberCount']);
        $this->assertNull($result['createdAt']);
    }
}
