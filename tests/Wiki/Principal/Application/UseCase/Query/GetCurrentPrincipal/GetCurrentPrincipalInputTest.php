<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Query\GetCurrentPrincipal;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Application\UseCase\Query\GetCurrentPrincipal\GetCurrentPrincipalInput;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class GetCurrentPrincipalInputTest extends TestCase
{
    public function test__construct(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $input = new GetCurrentPrincipalInput($identityIdentifier, $accountIdentifier);

        $this->assertSame((string) $identityIdentifier, (string) $input->identityIdentifier());
        $this->assertSame((string) $accountIdentifier, (string) $input->accountIdentifier());
    }
}
