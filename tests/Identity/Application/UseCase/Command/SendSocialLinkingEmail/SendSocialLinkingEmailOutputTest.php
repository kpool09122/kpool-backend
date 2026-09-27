<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\SendSocialLinkingEmail;

use LogicException;
use Source\Identity\Application\UseCase\Command\SendSocialLinkingEmail\SendSocialLinkingEmailOutput;
use Tests\TestCase;

class SendSocialLinkingEmailOutputTest extends TestCase
{
    public function testOutput(): void
    {
        $output = new SendSocialLinkingEmailOutput();
        $output->setAccepted(false);
        $this->assertFalse($output->accepted());
        $this->assertSame(['accepted' => false], $output->toArray());
    }

    public function testUnsetOutputFails(): void
    {
        $this->expectException(LogicException::class);
        (new SendSocialLinkingEmailOutput())->toArray();
    }
}
