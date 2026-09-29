<?php

declare(strict_types=1);

namespace Tests\Identity\Domain\ValueObject;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationScope;
use Source\Identity\Domain\ValueObject\StepUpOAuthSession;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class StepUpOAuthSessionTest extends TestCase
{
    public function testItPreservesValues(): void
    {
        $identityIdentifier = new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174001');
        $provider = SocialProvider::KAKAO;
        $scope = StepUpAuthenticationScope::PASSKEY_MANAGE;
        $expiresAt = new DateTimeImmutable('2026-09-26T00:10:00+00:00');
        $returnTo = '/settings/passkeys?stepUp=complete';

        $session = new StepUpOAuthSession(
            $identityIdentifier,
            $provider,
            $scope,
            $expiresAt,
            $returnTo,
        );

        $this->assertSame($identityIdentifier, $session->identityIdentifier);
        $this->assertSame($provider, $session->provider);
        $this->assertSame($scope, $session->scope);
        $this->assertSame($expiresAt, $session->expiresAt);
        $this->assertSame($returnTo, $session->returnTo);
    }
}
