<?php

declare(strict_types=1);

namespace Tests\Analyze\Principal\Application\UseCase\Query\GetCurrentPrincipal;

use PHPUnit\Framework\TestCase;
use Source\Analyze\Principal\Application\UseCase\Query\GetCurrentPrincipal\GetCurrentPrincipalInput;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;

class GetCurrentPrincipalInputTest extends TestCase
{
    public function testPreservesIdentifiers(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        $input = new GetCurrentPrincipalInput($identity, $account);
        self::assertSame($identity, $input->identityIdentifier());
        self::assertSame($account, $input->accountIdentifier());
    }
}
