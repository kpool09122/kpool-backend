<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\StartPasskeyRecoveryWithSocial;

use Source\Identity\Application\UseCase\Command\StartPasskeyRecoveryWithSocial\StartPasskeyRecoveryWithSocialInput;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\TestCase;

class StartPasskeyRecoveryWithSocialInputTest extends TestCase
{
    public function testItExposesConstructorValues(): void
    {
        $id = new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174000');
        $input = new StartPasskeyRecoveryWithSocialInput($id, SocialProvider::GOOGLE);
        $this->assertSame($id, $input->identityIdentifier());
        $this->assertSame(SocialProvider::GOOGLE, $input->provider());
    }
}
