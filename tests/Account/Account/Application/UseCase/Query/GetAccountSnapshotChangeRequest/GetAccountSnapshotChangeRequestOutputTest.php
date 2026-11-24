<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Query\GetAccountSnapshotChangeRequest;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\UseCase\Query\GetAccountSnapshotChangeRequest\GetAccountSnapshotChangeRequestOutput;
use Tests\Helper\CreateAccountSnapshotChangeRequest;

class GetAccountSnapshotChangeRequestOutputTest extends TestCase
{
    public function testOutputContract(): void
    {
        $output = new GetAccountSnapshotChangeRequestOutput();
        $model = CreateAccountSnapshotChangeRequest::readModel();
        self::assertSame([], $output->toArray());
        self::assertNull($output->request());
        $output->output($model);
        self::assertSame($model, $output->request());
        self::assertSame($model->toArray(), $output->toArray());
    }
}
