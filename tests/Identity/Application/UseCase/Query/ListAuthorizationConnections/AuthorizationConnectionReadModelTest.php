<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Query\ListAuthorizationConnections;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\UseCase\Query\ListAuthorizationConnections\AuthorizationConnectionReadModel;

class AuthorizationConnectionReadModelTest extends TestCase
{
    public function testSerializationIncludesScopesAndNullableRevocationWithoutSecrets(): void
    {
        foreach ([null, '2026-11-17T01:00:00+00:00'] as $revokedAt) {
            $payload = ['connectionIdentifier' => 'connection', 'clientIdentifier' => 'client', 'clientName' => 'ChatGPT',
                'scopes' => ['wiki:read'], 'createdAt' => '2026-11-16T01:00:00+00:00', 'revokedAt' => $revokedAt];
            self::assertSame($payload, (new AuthorizationConnectionReadModel(...$payload))->toArray());
        }
    }
}
