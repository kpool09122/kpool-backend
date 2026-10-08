<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Application\UseCase\Query\GetMyContactDetail;

use PHPUnit\Framework\TestCase;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetMyContactDetail\GetMyContactDetailInput;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;

class GetMyContactDetailInputTest extends TestCase
{
    public function test__construct(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $contactIdentifier = new ContactIdentifier(StrTestHelper::generateUuid());

        $input = new GetMyContactDetailInput($principalIdentifier, $contactIdentifier);

        $this->assertSame($principalIdentifier, $input->principalIdentifier());
        $this->assertSame($contactIdentifier, $input->contactIdentifier());
    }
}
