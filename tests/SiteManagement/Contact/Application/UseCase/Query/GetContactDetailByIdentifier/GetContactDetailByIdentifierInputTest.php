<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Application\UseCase\Query\GetContactDetailByIdentifier;

use PHPUnit\Framework\TestCase;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetContactDetailByIdentifier\GetContactDetailByIdentifierInput;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;

class GetContactDetailByIdentifierInputTest extends TestCase
{
    public function testInputContainsRequesterPrincipalAndContactOnly(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $contactIdentifier = new ContactIdentifier(StrTestHelper::generateUuid());
        $input = new GetContactDetailByIdentifierInput($principalIdentifier, $contactIdentifier);

        $this->assertSame($principalIdentifier, $input->principalIdentifier());
        $this->assertSame($contactIdentifier, $input->contactIdentifier());
    }
}
