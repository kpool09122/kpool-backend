<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Query\GetLatestAccountSnapshotChangeRequest;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\UseCase\Query\GetLatestAccountSnapshotChangeRequest\GetLatestAccountSnapshotChangeRequestOutput;
use Tests\Helper\CreateAccountSnapshotChangeRequest;

class GetLatestAccountSnapshotChangeRequestOutputTest extends TestCase
{
    public function testOutputContract(): void
    {
        $output = new GetLatestAccountSnapshotChangeRequestOutput();
        $model = CreateAccountSnapshotChangeRequest::readModel();
        self::assertSame(['request' => null, 'changeRequiresApproval' => false], $output->toArray());
        $output->output($model, true);
        self::assertSame(['request' => $model->summary(), 'changeRequiresApproval' => true], $output->toArray());
    }
}
