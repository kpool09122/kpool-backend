<?php

declare(strict_types=1);

namespace Tests\Account\Affiliation\Application\UseCase\Command\RequestAffiliation;

use PHPUnit\Framework\TestCase;
use Source\Account\Affiliation\Application\UseCase\Command\RequestAffiliation\RequestAffiliationInput;
use Source\Account\Affiliation\Domain\ValueObject\AffiliationTerms;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Monetization\Shared\ValueObject\Percentage;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;

class RequestAffiliationInputTest extends TestCase
{
    public function test__construct(): void
    {
        $principal = new Principal(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
        );
        $targetEmail = new Email('target@example.com');
        $terms = new AffiliationTerms(new Percentage(30), 'Contract notes');

        $input = new RequestAffiliationInput($principal, $targetEmail, $terms);

        $this->assertSame($principal, $input->principal());
        $this->assertSame($targetEmail, $input->targetEmail());
        $this->assertSame($terms, $input->terms());
    }

    public function test__constructWithNullTerms(): void
    {
        $principal = new Principal(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
        );
        $targetEmail = new Email('target@example.com');

        $input = new RequestAffiliationInput($principal, $targetEmail, null);

        $this->assertSame($principal, $input->principal());
        $this->assertSame($targetEmail, $input->targetEmail());
        $this->assertNull($input->terms());
    }
}
