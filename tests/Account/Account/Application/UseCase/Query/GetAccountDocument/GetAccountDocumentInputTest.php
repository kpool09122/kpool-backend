<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Query\GetAccountDocument;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\UseCase\Query\GetAccountDocument\GetAccountDocumentInput;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;

class GetAccountDocumentInputTest extends TestCase
{
    public function test__construct(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            $accountIdentifier,
        );

        $input = new GetAccountDocumentInput(
            accountIdentifier: $accountIdentifier,
            documentType: 'business_registration',
            principal: $principal,
        );

        $this->assertSame($accountIdentifier, $input->accountIdentifier());
        $this->assertSame('business_registration', $input->documentType());
        $this->assertSame($principal, $input->principal());
    }
}
