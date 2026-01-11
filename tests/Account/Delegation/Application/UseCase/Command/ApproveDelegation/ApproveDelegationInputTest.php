<?php

declare(strict_types=1);

namespace Tests\Account\Delegation\Application\UseCase\Command\ApproveDelegation;

use PHPUnit\Framework\TestCase;
use Source\Account\Delegation\Application\UseCase\Command\ApproveDelegation\ApproveDelegationInput;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;

class ApproveDelegationInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが正しく作成できること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $delegationIdentifier = new DelegationIdentifier(StrTestHelper::generateUuid());
        $approverIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $input = new ApproveDelegationInput(
            $delegationIdentifier,
            $approverIdentifier,
        );

        $this->assertSame($delegationIdentifier, $input->delegationIdentifier());
        $this->assertSame($approverIdentifier, $input->approverIdentifier());
    }
}
