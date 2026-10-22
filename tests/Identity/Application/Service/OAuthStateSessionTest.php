<?php

declare(strict_types=1);

namespace Tests\Identity\Application\Service;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Identity\Application\Service\OAuthStateSession;
use Source\Identity\Domain\ValueObject\OAuthState;

class OAuthStateSessionTest extends TestCase
{
    public function testItCarriesTheStateAndItsExpiration(): void
    {
        $state = new OAuthState('state-token');
        $expiresAt = new DateTimeImmutable('+10 minutes');

        $session = new OAuthStateSession($state, $expiresAt);

        $this->assertSame($state, $session->state);
        $this->assertSame($expiresAt, $session->expiresAt);
    }
}
