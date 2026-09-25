<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\StartStepUpWithSocial;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\UseCase\Command\StartStepUpWithSocial\StartStepUpWithSocialInput;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class StartStepUpWithSocialInputTest extends TestCase
{
    public function testItPreservesIdentityAndProviderInstances(): void
    {
        $identityIdentifier = new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174001');
        $provider = SocialProvider::GOOGLE;

        $input = new StartStepUpWithSocialInput($identityIdentifier, $provider);

        $this->assertSame($identityIdentifier, $input->identityIdentifier());
        $this->assertSame($provider, $input->provider());
    }
}
