<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Command\GrantOperations;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\UseCase\Command\GrantOperations\GrantOperationsOutput;

class GrantOperationsOutputTest extends TestCase
{
    public function testToArrayReturnsEmptyArray(): void
    {
        $output = new GrantOperationsOutput();

        $this->assertSame([], $output->toArray());
    }
}
