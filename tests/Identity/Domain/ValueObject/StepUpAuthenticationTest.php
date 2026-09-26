<?php

declare(strict_types=1);

namespace Tests\Identity\Domain\ValueObject;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Identity\Domain\ValueObject\StepUpAuthentication;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationMethod;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationScope;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class StepUpAuthenticationTest extends TestCase
{
    public function testItPreservesValuesAndExpiresAtTheBoundary(): void
    {
        $identityIdentifier = new IdentityIdentifier('123e4567-e89b-72d3-a456-426614174001');
        $method = StepUpAuthenticationMethod::PASSKEY;
        $verifiedAt = new DateTimeImmutable('2026-09-26T00:00:00+00:00');
        $scope = StepUpAuthenticationScope::PASSKEY_MANAGE;
        $expiresAt = new DateTimeImmutable('2026-09-26T00:10:00+00:00');

        $authentication = new StepUpAuthentication(
            $identityIdentifier,
            $method,
            $verifiedAt,
            $scope,
            $expiresAt,
        );

        $this->assertSame($identityIdentifier, $authentication->identityIdentifier);
        $this->assertSame($method, $authentication->method);
        $this->assertSame($verifiedAt, $authentication->verifiedAt);
        $this->assertSame($scope, $authentication->scope);
        $this->assertSame($expiresAt, $authentication->expiresAt);
        $this->assertFalse($authentication->isExpired(new DateTimeImmutable('2026-09-26T00:09:59+00:00')));
        $this->assertTrue($authentication->isExpired($expiresAt));
        $this->assertTrue($authentication->isExpired(new DateTimeImmutable('2026-09-26T00:10:01+00:00')));
    }
}
