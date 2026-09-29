<?php

declare(strict_types=1);

namespace Tests\Account\Delegation\Application\UseCase\Query\ListDelegations;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Account\Delegation\Application\UseCase\Query\ListDelegations\ListDelegationsInput;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;

class ListDelegationsInputTest extends TestCase
{
    public function testConstructWithDefaults(): void
    {
        $input = new ListDelegationsInput($this->principal());
        $this->assertNull($input->status());
        $this->assertNull($input->viewerRole());
        $this->assertSame(50, $input->perPage());
        $this->assertSame(1, $input->page());
    }

    public function testConstructWithFiltersAndPagination(): void
    {
        $input = new ListDelegationsInput($this->principal(), 'approved', 'approver', 20, 3);
        $this->assertSame('approved', $input->status());
        $this->assertSame('approver', $input->viewerRole());
        $this->assertSame(20, $input->perPage());
        $this->assertSame(3, $input->page());
    }

    #[DataProvider('invalidArguments')]
    public function testInvalidArgumentsThrowException(?string $status, ?string $viewerRole, ?int $perPage, int $page): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ListDelegationsInput($this->principal(), $status, $viewerRole, $perPage, $page);
    }

    /** @return array<string, array{string|null, string|null, int|null, int}> */
    public static function invalidArguments(): array
    {
        return [
            'status' => ['unknown', null, null, 1],
            'viewer role' => [null, 'owner', null, 1],
            'per page zero' => [null, null, 0, 1],
            'per page over max' => [null, null, 101, 1],
            'page zero' => [null, null, null, 0],
        ];
    }

    private function principal(): Principal
    {
        return new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), new IdentityIdentifier(StrTestHelper::generateUuid()), new AccountIdentifier(StrTestHelper::generateUuid()));
    }
}
