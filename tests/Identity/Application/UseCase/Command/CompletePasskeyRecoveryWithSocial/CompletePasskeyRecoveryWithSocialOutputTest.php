<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\CompletePasskeyRecoveryWithSocial;

use Source\Identity\Application\UseCase\Command\CompletePasskeyRecoveryWithSocial\CompletePasskeyRecoveryWithSocialOutput;
use Tests\TestCase;

class CompletePasskeyRecoveryWithSocialOutputTest extends TestCase
{
    public function testItExposesTheRedirectUrl(): void
    {
        $output = new CompletePasskeyRecoveryWithSocialOutput();
        $this->assertNull($output->redirectUrl());
        $output->setRedirectUrl('/settings/passkeys/recovery');
        $this->assertSame('/settings/passkeys/recovery', $output->redirectUrl());
    }
}
