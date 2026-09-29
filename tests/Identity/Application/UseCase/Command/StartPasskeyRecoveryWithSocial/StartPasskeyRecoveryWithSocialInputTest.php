<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\StartPasskeyRecoveryWithSocial;

use Source\Identity\Application\UseCase\Command\StartPasskeyRecoveryWithSocial\StartPasskeyRecoveryWithSocialInput;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Tests\TestCase;

class StartPasskeyRecoveryWithSocialInputTest extends TestCase
{
    public function testItExposesConstructorValues(): void
    {
        $input = new StartPasskeyRecoveryWithSocialInput(SocialProvider::GOOGLE);
        $this->assertSame(SocialProvider::GOOGLE, $input->provider());
    }
}
