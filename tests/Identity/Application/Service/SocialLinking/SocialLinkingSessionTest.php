<?php

declare(strict_types=1);

namespace Tests\Identity\Application\Service\SocialLinking;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Identity\Application\Service\SocialLinking\SocialLinkingSession;
use Source\Identity\Domain\ValueObject\SocialConnection;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class SocialLinkingSessionTest extends TestCase
{
    public function testItPreservesAllPendingValues(): void
    {
        $identity = new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174001');
        $email = new Email('target@example.com');
        $connection = new SocialConnection(SocialProvider::GOOGLE, 'provider-user');
        $expiry = new DateTimeImmutable('+10 minutes');
        $session = new SocialLinkingSession($identity, $email, $connection, '/settings', $expiry);

        $this->assertSame($identity, $session->identityIdentifier);
        $this->assertSame($email, $session->email);
        $this->assertSame($connection, $session->connection);
        $this->assertSame('/settings', $session->returnTo);
        $this->assertSame($expiry, $session->expiresAt);
    }
}
