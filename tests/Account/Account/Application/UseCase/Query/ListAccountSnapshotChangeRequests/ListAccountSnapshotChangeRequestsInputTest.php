<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Query\ListAccountSnapshotChangeRequests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\UseCase\Query\ListAccountSnapshotChangeRequests\ListAccountSnapshotChangeRequestsInput;
use Tests\Helper\CreateAccountSnapshot;
use Tests\Helper\CreateAccountSnapshotChangeRequest;

class ListAccountSnapshotChangeRequestsInputTest extends TestCase
{
    public function testTypedInputAndBoundedDefaults(): void
    {
        $principal = CreateAccountSnapshot::principal(CreateAccountSnapshotChangeRequest::entity()->accountIdentifier());
        $input = new ListAccountSnapshotChangeRequestsInput($principal);
        self::assertSame($principal, $input->principal());
        self::assertNull($input->status());
        self::assertSame(50, $input->perPage());
        self::assertSame(1, $input->page());
        $input = new ListAccountSnapshotChangeRequestsInput($principal, 'pending', 100, 2);
        self::assertSame('pending', $input->status());
        self::assertSame(100, $input->perPage());
        self::assertSame(2, $input->page());
    }

    public function testInvalidFiltersRejected(): void
    {
        $principal = CreateAccountSnapshot::principal(CreateAccountSnapshotChangeRequest::entity()->accountIdentifier());
        foreach ([['withdrawn', 1, 1], [null, 0, 1], [null, 101, 1], [null, 1, 0]] as [$status,$perPage,$page]) {
            try {
                new ListAccountSnapshotChangeRequestsInput($principal, $status, $perPage, $page);
                self::fail('Invalid input must be rejected.');
            } catch (InvalidArgumentException $exception) {
                self::assertNotEmpty($exception->getMessage());
            }
        }
    }
}
