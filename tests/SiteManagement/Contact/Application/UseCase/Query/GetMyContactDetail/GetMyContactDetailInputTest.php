<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Application\UseCase\Query\GetMyContactDetail;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetMyContactDetail\GetMyContactDetailInput;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Tests\Helper\StrTestHelper;

class GetMyContactDetailInputTest extends TestCase
{
    public function test__construct(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $contactIdentifier = new ContactIdentifier(StrTestHelper::generateUuid());

        $input = new GetMyContactDetailInput($identityIdentifier, $contactIdentifier);

        $this->assertSame($identityIdentifier, $input->identityIdentifier());
        $this->assertSame($contactIdentifier, $input->contactIdentifier());
    }
}
