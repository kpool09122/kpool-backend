<?php

declare(strict_types=1);

namespace Tests\Identity\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Identity\Domain\ValueObject\SocialProfile;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Source\Shared\Domain\ValueObject\Email;

class SocialProfileTest extends TestCase
{
    public function test__construct(): void
    {
        $provider = SocialProvider::INSTAGRAM;
        $providerUserId = 'instagram-user-1';
        $email = new Email('user@example.com');
        $name = 'Test User';
        $avatarUrl = 'https://example.com/avatar.png';

        $profile = new SocialProfile($provider, $providerUserId, $email, $name, $avatarUrl);

        $this->assertSame($provider, $profile->provider());
        $this->assertSame($providerUserId, $profile->providerUserId());
        $this->assertSame($email, $profile->email());
        $this->assertSame($name, $profile->name());
        $this->assertSame($avatarUrl, $profile->avatarUrl());
    }
}
