<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\RevokeOperations;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\UseCase\Command\RevokeOperations\RevokeOperationsOutput;

class RevokeOperationsOutputTest extends TestCase
{
    public function testToArrayReturnsEmptyArray(): void
    {
        $output = new RevokeOperationsOutput();

        $this->assertSame([], $output->toArray());
    }
}
