<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Application\UseCase\Query\GetContactDetail;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetContactDetail\GetContactDetailInput;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Tests\Helper\StrTestHelper;

class GetContactDetailInputTest extends TestCase
{
    public function test__construct(): void
    {
        $requesterIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $targetIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $contactIdentifier = new ContactIdentifier(StrTestHelper::generateUuid());

        $input = new GetContactDetailInput($requesterIdentityIdentifier, $targetIdentityIdentifier, $contactIdentifier);

        $this->assertSame($requesterIdentityIdentifier, $input->requesterIdentityIdentifier());
        $this->assertSame($targetIdentityIdentifier, $input->targetIdentityIdentifier());
        $this->assertSame($contactIdentifier, $input->contactIdentifier());
    }
}
