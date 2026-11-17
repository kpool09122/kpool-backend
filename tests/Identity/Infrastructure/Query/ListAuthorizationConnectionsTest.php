<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Query;

use Application\Models\Identity\AuthorizationConnection as ConnectionModel;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;
use Source\Identity\Application\UseCase\Query\ListAuthorizationConnections\ListAuthorizationConnectionsInput;
use Source\Identity\Application\UseCase\Query\ListAuthorizationConnections\ListAuthorizationConnectionsInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateAuthorizationConnection;
use Tests\TestCase;

#[Group('useDb')]
class ListAuthorizationConnectionsTest extends TestCase
{
    public function testOnlyOwnConnectionsAreReturnedInStableOrderWithoutClientSecrets(): void
    {
        $owner = CreateAuthorizationConnection::create();
        $other = CreateAuthorizationConnection::create();
        $secondId = (string) Str::uuid7();
        $model = ConnectionModel::query()->findOrFail((string) $owner->identifier());
        $second = $model->replicate()->forceFill(['id' => $secondId, 'revoked_at' => now()]);
        $second->save();
        $query = $this->app()->make(ListAuthorizationConnectionsInterface::class);
        $items = $query->process(new ListAuthorizationConnectionsInput($owner->identityIdentifier()));
        self::assertCount(2, $items);
        self::assertSame([
            'connectionIdentifier' => (string) $owner->identifier(),
            'clientIdentifier' => (string) $owner->clientIdentifier(),
            'clientName' => 'ChatGPT',
            'scopes' => ['wiki:read', 'wiki-drafts:read'],
            'createdAt' => $model->consented_at->toIso8601String(),
            'revokedAt' => null,
        ], $items[0]->toArray());
        self::assertSame($secondId, $items[1]->toArray()['connectionIdentifier']);
        self::assertNotNull($items[1]->toArray()['revokedAt']);
        self::assertCount(1, $query->process(new ListAuthorizationConnectionsInput($other->identityIdentifier())));
        self::assertSame([], $query->process(new ListAuthorizationConnectionsInput(new IdentityIdentifier((string) Str::uuid7()))));
    }
}
