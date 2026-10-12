<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Query\GetLatestAccountCategoryChangeRequest;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\UseCase\Query\AccountCategoryChangeRequestReadModel;
use Source\Account\Account\Application\UseCase\Query\GetLatestAccountCategoryChangeRequest\GetLatestAccountCategoryChangeRequestOutput;

class GetLatestAccountCategoryChangeRequestOutputTest extends TestCase
{
    public function testNoRequestIsExplicitNull(): void
    {
        $output = new GetLatestAccountCategoryChangeRequestOutput();
        $output->output(null);
        $this->assertSame(['request' => null], $output->toArray());
    }

    public function testRequestIsSerializedAsSummary(): void
    {
        $request = new AccountCategoryChangeRequestReadModel('request-id', 'account-id', 'general', 'agency', 'pending', '2026-08-11T10:00:00+00:00', null, null, null);
        $output = new GetLatestAccountCategoryChangeRequestOutput();
        $output->output($request);
        $this->assertSame(['request' => $request->toArray()], $output->toArray());
        $output->output(null);
        $this->assertSame(['request' => null], $output->toArray());
    }
}
