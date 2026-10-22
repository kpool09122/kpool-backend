<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\CompletePasskeyRecoveryWithSocial;

use Source\Identity\Application\UseCase\Command\CompletePasskeyRecoveryWithSocial\CompletePasskeyRecoveryWithSocialInput;
use Source\Identity\Domain\ValueObject\OAuthCode;
use Source\Identity\Domain\ValueObject\OAuthState;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Tests\TestCase;

class CompletePasskeyRecoveryWithSocialInputTest extends TestCase
{
    public function testItExposesConstructorValues(): void
    {
        $code = new OAuthCode('code');
        $state = new OAuthState('state');
        $input = new CompletePasskeyRecoveryWithSocialInput(SocialProvider::GOOGLE, $code, $state);
        $this->assertSame(SocialProvider::GOOGLE, $input->provider());
        $this->assertSame($code, $input->code());
        $this->assertSame($state, $input->state());
    }
}
