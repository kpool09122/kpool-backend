<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\WithdrawFromService;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Principal\Application\UseCase\Command\WithdrawFromService\WithdrawFromServiceOutput;

class WithdrawFromServiceOutputTest extends TestCase
{
    public function testReturnsEmptyPayload(): void
    {
        $this->assertSame([], (new WithdrawFromServiceOutput())->toArray());
    }
}
