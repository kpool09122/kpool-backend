<?php

declare(strict_types=1);

namespace Tests\Analyze\Principal\Application\UseCase\Command\WithdrawFromService;

use Source\Analyze\Principal\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceInput;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class WithdrawFromServiceInputTest extends TestCase
{
    public function testPublicApi(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $input = new WithdrawFromServiceInput($identity);
        $this->assertSame($identity, $input->identityIdentifier());
    }
}
