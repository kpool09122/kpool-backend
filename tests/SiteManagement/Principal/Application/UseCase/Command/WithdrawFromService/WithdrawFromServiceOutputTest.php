<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Application\UseCase\Command\WithdrawFromService;

use Source\SiteManagement\Principal\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceOutput;
use Tests\TestCase;

class WithdrawFromServiceOutputTest extends TestCase
{
    public function testPublicApi(): void
    {
        $output = new WithdrawFromServiceOutput();
        $this->assertSame([], $output->toArray());
    }
}
