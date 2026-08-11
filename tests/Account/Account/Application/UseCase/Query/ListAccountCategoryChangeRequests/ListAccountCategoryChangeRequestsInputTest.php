<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Query\ListAccountCategoryChangeRequests;

use InvalidArgumentException;
use Source\Account\Account\Application\UseCase\Query\ListAccountCategoryChangeRequests\ListAccountCategoryChangeRequestsInput;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ListAccountCategoryChangeRequestsInputTest extends TestCase
{
    public function testConstructWithDefaults(): void
    {
        $input = new ListAccountCategoryChangeRequestsInput($this->principal());

        $this->assertNull($input->status());
        $this->assertNull($input->requestedAccountCategory());
        $this->assertSame(50, $input->perPage());
        $this->assertSame(1, $input->page());
    }

    public function testConstructWithFiltersAndPagination(): void
    {
        $input = new ListAccountCategoryChangeRequestsInput(
            principal: $this->principal(),
            status: 'pending',
            requestedAccountCategory: 'agency',
            perPage: 20,
            page: 3,
        );

        $this->assertSame('pending', $input->status());
        $this->assertSame('agency', $input->requestedAccountCategory());
        $this->assertSame(20, $input->perPage());
        $this->assertSame(3, $input->page());
    }

    public function testInvalidStatusThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ListAccountCategoryChangeRequestsInput($this->principal(), status: 'unknown');
    }

    public function testInvalidRequestedAccountCategoryThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ListAccountCategoryChangeRequestsInput($this->principal(), requestedAccountCategory: 'company');
    }

    public function testInvalidPerPageThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ListAccountCategoryChangeRequestsInput($this->principal(), perPage: 101);
    }

    public function testInvalidPageThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ListAccountCategoryChangeRequestsInput($this->principal(), page: 0);
    }

    private function principal(): Principal
    {
        return new Principal(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
        );
    }
}
