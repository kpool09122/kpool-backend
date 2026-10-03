<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\SendSocialLinkingEmail;

use Source\Identity\Application\Service\EmailSendingStatus;
use Source\Identity\Application\UseCase\Command\SendSocialLinkingEmail\SendSocialLinkingEmailOutput;
use Source\Shared\Application\Exception\OutputNotInitializedException;
use Tests\TestCase;

class SendSocialLinkingEmailOutputTest extends TestCase
{
    public function testOutput(): void
    {
        $output = new SendSocialLinkingEmailOutput();
        $output->setStatus(new EmailSendingStatus(false, 3, 42));
        $this->assertSame(['accepted' => true, 'remainingSends' => 3, 'retryAfterSeconds' => 42], $output->toArray());
    }

    public function testUnsetOutputFails(): void
    {
        $this->expectException(OutputNotInitializedException::class);
        (new SendSocialLinkingEmailOutput())->toArray();
    }
}
