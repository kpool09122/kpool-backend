<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\DeleteAccountData;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Principal\Application\UseCase\Command\DeleteAccountData\DeleteAccountDataOutput;

class DeleteAccountDataOutputTest extends TestCase
{
    public function testReturnsEmptyPayload(): void
    {
        $this->assertSame([], (new DeleteAccountDataOutput())->toArray());
    }
}
