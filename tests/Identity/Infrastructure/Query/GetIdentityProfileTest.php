<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Query;

use PHPUnit\Framework\Attributes\Group;
use Source\Identity\Application\UseCase\Query\GetIdentityProfile\GetIdentityProfileInput;
use Source\Identity\Application\UseCase\Query\GetIdentityProfile\GetIdentityProfileInterface;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateIdentity;
use Tests\TestCase;

class GetIdentityProfileTest extends TestCase
{
    #[Group('useDb')]
    public function testProcessReturnsRequestedIdentityProfile(): void
    {
        $sessionIdentityIdentifier = new IdentityIdentifier('019de7f3-78f3-7b55-9ed5-17f63e14d5aa');
        $requestedIdentityIdentifier = new IdentityIdentifier('019de7f3-78f3-7b55-9ed5-17f63e14d5fe');
        CreateIdentity::create($sessionIdentityIdentifier, [
            'identity_name' => 'session-identity',
            'email' => 'session@example.com',
        ]);
        CreateIdentity::create($requestedIdentityIdentifier, [
            'identity_name' => 'profile-owner',
            'email' => 'profile-owner@example.com',
            'language' => 'en',
            'profile_image' => 'profile/requested.png',
        ]);
        CreateIdentity::createSocialConnection($requestedIdentityIdentifier, SocialProvider::GOOGLE, 'google-user-id');

        $useCase = $this->app->make(GetIdentityProfileInterface::class);
        $readModel = $useCase->process(new GetIdentityProfileInput($requestedIdentityIdentifier));
        $payload = $readModel->toArray();

        $this->assertSame('019de7f3-78f3-7b55-9ed5-17f63e14d5fe', $readModel->identityIdentifier());
        $this->assertSame('profile-owner', $readModel->identityName());
        $this->assertSame('en', $readModel->language());
        $this->assertSame('http://127.0.0.1:8080/storage/profile/requested.png', $readModel->profileImage());
        $this->assertArrayNotHasKey('email', $payload);
        $this->assertArrayNotHasKey('accountIdentifier', $payload);
        $this->assertArrayNotHasKey('socialConnections', $payload);
    }

    #[Group('useDb')]
    public function testProcessThrowsWhenIdentityDoesNotExist(): void
    {
        $useCase = $this->app->make(GetIdentityProfileInterface::class);

        $this->expectException(IdentityNotFoundException::class);

        $useCase->process(new GetIdentityProfileInput(
            new IdentityIdentifier('019de7f3-78f3-7b55-9ed5-17f63e14d5ff'),
        ));
    }
}
