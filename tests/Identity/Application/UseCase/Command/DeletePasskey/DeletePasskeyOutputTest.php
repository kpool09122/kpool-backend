<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\DeletePasskey;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\UseCase\Command\DeletePasskey\DeletePasskeyOutput;

class DeletePasskeyOutputTest extends TestCase
{
    public function testItReturnsAnEmptyArray(): void
    {
        $this->assertSame([], (new DeletePasskeyOutput())->toArray());
    }
}
