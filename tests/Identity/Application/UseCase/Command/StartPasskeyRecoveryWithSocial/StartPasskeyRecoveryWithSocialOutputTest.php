<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\StartPasskeyRecoveryWithSocial;

use Source\Identity\Application\UseCase\Command\StartPasskeyRecoveryWithSocial\StartPasskeyRecoveryWithSocialOutput;
use Tests\TestCase;

class StartPasskeyRecoveryWithSocialOutputTest extends TestCase
{
    public function testItSerializesOnlyAfterRedirectIsSet(): void
    {
        $output = new StartPasskeyRecoveryWithSocialOutput();
        $this->assertSame([], $output->toArray());
        $output->setRedirectUrl('https://example.com/oauth');
        $this->assertSame(['redirectUrl' => 'https://example.com/oauth'], $output->toArray());
    }
}
