<?php

declare(strict_types=1);

namespace Tests\Analyze\Principal\Application\UseCase\Command\CreatePrincipal;

use Source\Analyze\Principal\Application\UseCase\Command\CreatePrincipal\CreatePrincipalInput;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class CreatePrincipalInputTest extends TestCase
{
    public function testPublicApi(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $account = new AccountIdentifier('00000000-0000-7000-8000-000000000009');
        $input = new CreatePrincipalInput($identity, $account);
        $this->assertSame($account, $input->accountIdentifier());
        $this->assertSame($identity, $input->identityIdentifier());
    }
}
