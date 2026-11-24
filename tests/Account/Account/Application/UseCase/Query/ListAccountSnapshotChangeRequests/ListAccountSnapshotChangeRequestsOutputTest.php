<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Query\ListAccountSnapshotChangeRequests;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\UseCase\Query\ListAccountSnapshotChangeRequests\ListAccountSnapshotChangeRequestsOutput;
use Tests\Helper\CreateAccountSnapshotChangeRequest;

class ListAccountSnapshotChangeRequestsOutputTest extends TestCase
{
    public function testOutputContract(): void
    {
        $output = new ListAccountSnapshotChangeRequestsOutput();
        $model = CreateAccountSnapshotChangeRequest::readModel();
        $output->output([$model], 2, 3, 5, 2);
        self::assertSame(['requests' => [$model->summary()], 'pagination' => ['currentPage' => 2, 'lastPage' => 3, 'total' => 5, 'perPage' => 2]], $output->toArray());
    }
}
