<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Application\UseCase\Query\ListContacts;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\SiteManagement\Contact\Application\UseCase\Query\ListContacts\ListContactsInput;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;

class ListContactsInputTest extends TestCase
{
    public function testConstruct(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetPrincipalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new ListContactsInput($principalIdentifier, $targetPrincipalIdentifier, true);

        $this->assertSame($principalIdentifier, $input->principalIdentifier());
        $this->assertSame($targetPrincipalIdentifier, $input->targetPrincipalIdentifier());
        $this->assertTrue($input->hasReply());
        $this->assertSame(50, $input->perPage());
        $this->assertSame(1, $input->page());
    }

    public function testConstructAllowsNullTargetPrincipalIdentifier(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new ListContactsInput($principalIdentifier, null, null);

        $this->assertNull($input->targetPrincipalIdentifier());
        $this->assertNull($input->hasReply());
    }

    public function testConstructAcceptsPaginationValues(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $input = new ListContactsInput($principalIdentifier, null, null, 20, 3);

        $this->assertSame(20, $input->perPage());
        $this->assertSame(3, $input->page());
    }

    public function testConstructRejectsPerPageOutsideAllowedRange(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $this->expectException(InvalidArgumentException::class);

        new ListContactsInput($principalIdentifier, null, null, 101);
    }

    public function testConstructRejectsPageLessThanOne(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $this->expectException(InvalidArgumentException::class);

        new ListContactsInput($principalIdentifier, null, null, null, 0);
    }
}
