<?php

declare(strict_types=1);

namespace Tests\Account\Application\UseCase\Command\RevokeDelegation;

use PHPUnit\Framework\TestCase;
use Source\Account\Application\UseCase\Command\RevokeDelegation\RevokeDelegationInput;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;

class RevokeDelegationInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが正しく作成できること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $delegationIdentifier = new DelegationIdentifier(StrTestHelper::generateUuid());
        $revokerIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $input = new RevokeDelegationInput(
            $delegationIdentifier,
            $revokerIdentifier,
        );

        $this->assertSame($delegationIdentifier, $input->delegationIdentifier());
        $this->assertSame($revokerIdentifier, $input->revokerIdentifier());
    }
}
