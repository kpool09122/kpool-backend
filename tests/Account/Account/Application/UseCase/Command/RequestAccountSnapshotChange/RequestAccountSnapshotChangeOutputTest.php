<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\RequestAccountSnapshotChange;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\UseCase\Command\RequestAccountSnapshotChange\RequestAccountSnapshotChangeOutput;
use Tests\Helper\CreateAccountSnapshotChangeRequest;

class RequestAccountSnapshotChangeOutputTest extends TestCase
{
    public function testEmptyOutputAndCompleteSerialization(): void
    {
        $output = new RequestAccountSnapshotChangeOutput();
        self::assertSame([], $output->toArray());
        $entity = CreateAccountSnapshotChangeRequest::entity();
        $output->setRequest($entity);
        self::assertSame([
            'requestIdentifier' => (string) $entity->requestIdentifier(), 'snapshotIdentifier' => (string) $entity->snapshotIdentifier(), 'accountIdentifier' => (string) $entity->accountIdentifier(), 'changeReason' => '住所変更', 'reviewedSnapshotIdentifier' => null, 'status' => 'pending', 'requestedAt' => $entity->requestedAt()->format('c'), 'reviewedBy' => null, 'reviewedAt' => null, 'rejectionReason' => null,
        ], $output->toArray());
    }
}
