<?php

declare(strict_types=1);

namespace Tests\Analyze\Principal\Application\UseCase\Command\WithdrawFromService;

use Source\Analyze\Principal\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceOutput;
use Tests\TestCase;

class WithdrawFromServiceOutputTest extends TestCase
{
    public function testPublicApi(): void
    {
        $output = new WithdrawFromServiceOutput();
        $this->assertSame([], $output->toArray());
    }
}
