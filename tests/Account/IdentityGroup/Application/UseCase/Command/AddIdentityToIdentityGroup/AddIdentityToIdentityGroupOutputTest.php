<?php

declare(strict_types=1);

namespace Tests\Account\IdentityGroup\Application\UseCase\Command\AddIdentityToIdentityGroup;

use DateTimeImmutable;
use Source\Account\IdentityGroup\Application\UseCase\Command\AddIdentityToIdentityGroup\AddIdentityToIdentityGroupOutput;
use Source\Account\IdentityGroup\Domain\Entity\IdentityGroup;
use Source\Account\IdentityGroup\Domain\ValueObject\AccountRole;
use Source\Account\Shared\Domain\ValueObject\IdentityGroupIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AddIdentityToIdentityGroupOutputTest extends TestCase
{
    /**
     * 正常系: IdentityGroupがセットされるとtoArrayが正しい値を返すこと.
     */
    public function testToArrayWithIdentityGroup(): void
    {
        $identityGroupIdentifier = new IdentityGroupIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $identityGroup = new IdentityGroup(
            $identityGroupIdentifier,
            $accountIdentifier,
            'Test Group',
            AccountRole::MEMBER,
            false,
            new DateTimeImmutable(),
        );
        $identityGroup->addMember($identityIdentifier);

        $output = new AddIdentityToIdentityGroupOutput();
        $output->setIdentityGroup($identityGroup);

        $result = $output->toArray();

        $this->assertSame((string) $identityGroupIdentifier, $result['identityGroupIdentifier']);
        $this->assertSame((string) $accountIdentifier, $result['accountIdentifier']);
        $this->assertSame('Test Group', $result['name']);
        $this->assertSame('member', $result['role']);
        $this->assertFalse($result['isDefault']);
        $this->assertCount(1, $result['members']);
        $this->assertSame((string) $identityIdentifier, $result['members'][0]);
    }

    /**
     * 正常系: IdentityGroupが未セットの場合toArrayが空配列を返すこと.
     */
    public function testToArrayWithoutIdentityGroup(): void
    {
        $output = new AddIdentityToIdentityGroupOutput();
        $this->assertSame([], $output->toArray());
    }
}
