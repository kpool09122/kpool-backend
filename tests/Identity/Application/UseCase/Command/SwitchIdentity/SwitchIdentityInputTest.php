<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\SwitchIdentity;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\UseCase\Command\SwitchIdentity\SwitchIdentityInput;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;

class SwitchIdentityInputTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスが作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $currentIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $targetDelegationIdentifier = new DelegationIdentifier(StrTestHelper::generateUuid());

        $input = new SwitchIdentityInput($currentIdentityIdentifier, $targetDelegationIdentifier);

        $this->assertSame($currentIdentityIdentifier, $input->currentIdentityIdentifier());
        $this->assertSame($targetDelegationIdentifier, $input->targetDelegationIdentifier());

    }

    /**
     * 正常系: targetDelegationIdentifierがnullの場合も正しく取得できること.
     *
     * @return void
     */
    public function testTargetDelegationIdentifierWithNull(): void
    {
        $currentIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $input = new SwitchIdentityInput($currentIdentityIdentifier, null);

        $this->assertNull($input->targetDelegationIdentifier());
    }
}
