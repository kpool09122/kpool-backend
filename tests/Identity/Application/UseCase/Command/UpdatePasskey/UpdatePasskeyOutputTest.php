<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\UpdatePasskey;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\UseCase\Command\UpdatePasskey\UpdatePasskeyOutput;

class UpdatePasskeyOutputTest extends TestCase
{
    public function testItReturnsAnEmptyArray(): void
    {
        $this->assertSame([], (new UpdatePasskeyOutput())->toArray());
    }
}
