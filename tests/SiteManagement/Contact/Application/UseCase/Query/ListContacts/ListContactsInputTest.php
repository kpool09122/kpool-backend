<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Application\UseCase\Query\ListContacts;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContacts\ListContactsInput;
use Tests\Helper\StrTestHelper;

class ListContactsInputTest extends TestCase
{
    public function testConstruct(): void
    {
        $requesterIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $targetIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $input = new ListContactsInput($requesterIdentityIdentifier, $targetIdentityIdentifier);

        $this->assertSame($requesterIdentityIdentifier, $input->requesterIdentityIdentifier());
        $this->assertSame($targetIdentityIdentifier, $input->targetIdentityIdentifier());
    }

    public function testConstructAllowsNullTargetIdentityIdentifier(): void
    {
        $requesterIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $input = new ListContactsInput($requesterIdentityIdentifier, null);

        $this->assertNull($input->targetIdentityIdentifier());
    }
}
