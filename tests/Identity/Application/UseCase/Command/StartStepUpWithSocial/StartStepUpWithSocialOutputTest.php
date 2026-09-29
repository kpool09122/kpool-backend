<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\StartStepUpWithSocial;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\UseCase\Command\StartStepUpWithSocial\StartStepUpWithSocialOutput;

class StartStepUpWithSocialOutputTest extends TestCase
{
    public function testItSerializesRedirectUrlAndDefaultsToEmptyArray(): void
    {
        $output = new StartStepUpWithSocialOutput();
        $this->assertSame([], $output->toArray());

        $output->setRedirectUrl('https://accounts.example.com/oauth/authorize');

        $this->assertSame([
            'redirectUrl' => 'https://accounts.example.com/oauth/authorize',
        ], $output->toArray());
    }
}
