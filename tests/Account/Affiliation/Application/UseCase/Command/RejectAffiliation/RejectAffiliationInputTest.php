<?php

declare(strict_types=1);

namespace Tests\Account\Affiliation\Application\UseCase\Command\RejectAffiliation;

use PHPUnit\Framework\TestCase;
use Source\Account\Affiliation\Application\UseCase\Command\RejectAffiliation\RejectAffiliationInput;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;

class RejectAffiliationInputTest extends TestCase
{
    public function test__construct(): void
    {
        $affiliationIdentifier = new AffiliationIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
        );

        $input = new RejectAffiliationInput($affiliationIdentifier, $principal);

        $this->assertSame($affiliationIdentifier, $input->affiliationIdentifier());
        $this->assertSame($principal, $input->principal());
    }
}
