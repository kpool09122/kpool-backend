<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\RequestAccountCategoryChange;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\UseCase\Command\RequestAccountCategoryChange\RequestAccountCategoryChangeInput;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;

class RequestAccountCategoryChangeInputTest extends TestCase
{
    public function test__construct(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            $accountIdentifier,
        );
        $requestedAccountCategory = AccountCategory::AGENCY;

        $input = new RequestAccountCategoryChangeInput(
            $accountIdentifier,
            $principal,
            $requestedAccountCategory,
        );

        $this->assertSame($accountIdentifier, $input->accountIdentifier());
        $this->assertSame($principal, $input->principal());
        $this->assertSame($requestedAccountCategory, $input->requestedAccountCategory());
    }
}
