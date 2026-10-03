<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Application\UseCase\Query\ListContacts;

use InvalidArgumentException;
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

        $input = new ListContactsInput($requesterIdentityIdentifier, $targetIdentityIdentifier, true);

        $this->assertSame($requesterIdentityIdentifier, $input->requesterIdentityIdentifier());
        $this->assertSame($targetIdentityIdentifier, $input->targetIdentityIdentifier());
        $this->assertTrue($input->hasReply());
        $this->assertSame(50, $input->perPage());
        $this->assertSame(1, $input->page());
    }

    public function testConstructAllowsNullTargetIdentityIdentifier(): void
    {
        $requesterIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $input = new ListContactsInput($requesterIdentityIdentifier, null, null);

        $this->assertNull($input->targetIdentityIdentifier());
        $this->assertNull($input->hasReply());
    }

    public function testConstructAcceptsPaginationValues(): void
    {
        $requesterIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $input = new ListContactsInput($requesterIdentityIdentifier, null, null, 20, 3);

        $this->assertSame(20, $input->perPage());
        $this->assertSame(3, $input->page());
    }

    public function testConstructRejectsPerPageOutsideAllowedRange(): void
    {
        $requesterIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $this->expectException(InvalidArgumentException::class);

        new ListContactsInput($requesterIdentityIdentifier, null, null, 101);
    }

    public function testConstructRejectsPageLessThanOne(): void
    {
        $requesterIdentityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $this->expectException(InvalidArgumentException::class);

        new ListContactsInput($requesterIdentityIdentifier, null, null, null, 0);
    }
}
