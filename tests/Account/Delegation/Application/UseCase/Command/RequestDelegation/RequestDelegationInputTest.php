<?php

declare(strict_types=1);

namespace Tests\Account\Delegation\Application\UseCase\Command\RequestDelegation;

use PHPUnit\Framework\TestCase;
use Source\Account\Delegation\Application\UseCase\Command\RequestDelegation\RequestDelegationInput;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;

class RequestDelegationInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが正しく作成できること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $delegateIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $delegatorIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $input = new RequestDelegationInput(
            $affiliationIdentifier,
            $delegateIdentifier,
            $delegatorIdentifier,
        );

        $this->assertSame($affiliationIdentifier, $input->affiliationIdentifier());
        $this->assertSame($delegateIdentifier, $input->delegateIdentifier());
        $this->assertSame($delegatorIdentifier, $input->delegatorIdentifier());
    }
}
