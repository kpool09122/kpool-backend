<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\CreatePrincipal;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Application\UseCase\Command\CreatePrincipal\CreatePrincipalInput;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class CreatePrincipalInputTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスが作成されること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $input = new CreatePrincipalInput(
            $identityIdentifier,
            $accountIdentifier,
        );

        $this->assertSame((string) $identityIdentifier, (string) $input->identityIdentifier());
        $this->assertSame((string) $accountIdentifier, (string) $input->accountIdentifier());
    }
}
