<?php

declare(strict_types=1);

namespace Tests\Account\Affiliation\Application\UseCase\Query\ListAffiliations;

use InvalidArgumentException;
use Source\Account\Affiliation\Application\UseCase\Query\ListAffiliations\ListAffiliationsInput;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ListAffiliationsInputTest extends TestCase
{
    public function testConstructWithDefaults(): void
    {
        $input = new ListAffiliationsInput($this->principal());

        $this->assertNull($input->status());
        $this->assertNull($input->viewerRole());
        $this->assertSame(50, $input->perPage());
        $this->assertSame(1, $input->page());
    }

    public function testConstructWithFiltersAndPagination(): void
    {
        $input = new ListAffiliationsInput(
            principal: $this->principal(),
            status: 'active',
            viewerRole: 'approver',
            perPage: 20,
            page: 3,
        );

        $this->assertSame('active', $input->status());
        $this->assertSame('approver', $input->viewerRole());
        $this->assertSame(20, $input->perPage());
        $this->assertSame(3, $input->page());
    }

    public function testInvalidStatusThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ListAffiliationsInput($this->principal(), status: 'unknown');
    }

    public function testInvalidViewerRoleThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ListAffiliationsInput($this->principal(), viewerRole: 'owner');
    }

    public function testInvalidPerPageThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ListAffiliationsInput($this->principal(), perPage: 101);
    }

    public function testInvalidPageThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ListAffiliationsInput($this->principal(), page: 0);
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
