<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\AddPasskey;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\UseCase\Command\AddPasskey\AddPasskeyOutput;

class AddPasskeyOutputTest extends TestCase
{
    public function testReturnsEmptyPayload(): void
    {
        $this->assertSame([], (new AddPasskeyOutput())->toArray());
    }
}
