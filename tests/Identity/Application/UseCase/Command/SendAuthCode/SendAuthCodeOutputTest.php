<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\SendAuthCode;

use Source\Identity\Application\Service\EmailSendingStatus;
use Source\Identity\Application\UseCase\Command\SendAuthCode\SendAuthCodeOutput;
use Source\Shared\Application\Exception\OutputNotInitializedException;
use Tests\TestCase;

class SendAuthCodeOutputTest extends TestCase
{
    public function testOutputContract(): void
    {
        $output = new SendAuthCodeOutput();
        $output->setStatus(new EmailSendingStatus(false, 0, null));
        $this->assertSame(['accepted' => true, 'remainingSends' => 0, 'retryAfterSeconds' => null], $output->toArray());
    }

    public function testUnsetOutputFails(): void
    {
        $this->expectException(OutputNotInitializedException::class);
        (new SendAuthCodeOutput())->toArray();
    }
}
