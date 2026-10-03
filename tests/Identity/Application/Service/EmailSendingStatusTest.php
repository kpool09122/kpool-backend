<?php

declare(strict_types=1);

namespace Tests\Identity\Application\Service;

use Source\Identity\Application\Service\EmailSendingStatus;
use Tests\TestCase;

class EmailSendingStatusTest extends TestCase
{
    public function testItPreservesSendingState(): void
    {
        $status = new EmailSendingStatus(false, 3, null);
        $this->assertFalse($status->sendingAllowed);
        $this->assertSame(3, $status->remainingSends);
        $this->assertNull($status->retryAfterSeconds);
    }
}
