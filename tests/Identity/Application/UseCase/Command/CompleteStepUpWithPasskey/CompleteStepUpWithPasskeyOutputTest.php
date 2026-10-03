<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\CompleteStepUpWithPasskey;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\UseCase\Command\CompleteStepUpWithPasskey\CompleteStepUpWithPasskeyOutput;

class CompleteStepUpWithPasskeyOutputTest extends TestCase
{
    public function testItSerializesAsEmptyArray(): void
    {
        $this->assertSame([], (new CompleteStepUpWithPasskeyOutput())->toArray());
    }
}
