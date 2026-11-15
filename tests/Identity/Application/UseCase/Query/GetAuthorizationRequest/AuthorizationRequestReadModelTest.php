<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Query\GetAuthorizationRequest;

use DateTimeImmutable;
use Illuminate\Support\Str;
use Source\Identity\Application\Service\AuthorizationCodeParameters;
use Source\Identity\Application\Service\PendingAuthorization;
use Source\Identity\Application\UseCase\Query\GetAuthorizationRequest\AuthorizationRequestReadModel;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\OAuthClientIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\TestCase;

class AuthorizationRequestReadModelTest extends TestCase
{
    public function testContract(): void
    {
        $identity = new IdentityIdentifier((string) Str::uuid7());
        $account = new AccountIdentifier((string) Str::uuid7());
        $parameters = new AuthorizationCodeParameters(new OAuthClientIdentifier((string) Str::uuid7()), 'https://client.test/cb', [AuthorizationScope::DraftWrite], 'https://mcp.test', str_repeat('a', 43), 'state');
        $pending = new PendingAuthorization('request', $parameters, new DateTimeImmutable('2030-01-01T00:00:00+00:00'), $identity);
        $accounts = [['accountIdentifier' => (string) $account, 'name' => 'Original', 'delegationIdentifier' => null]];
        $model = new AuthorizationRequestReadModel($pending, 'Client', (string) $account, $accounts);
        $this->assertSame([
        'requestIdentifier' => 'request', 'expiresAt' => '2030-01-01T00:00:00+00:00', 'clientName' => 'Client',
        'scopes' => [['scope' => 'wiki-drafts:write', 'description' => 'Draft Wiki の作成・編集']],
        'originalAccountIdentifier' => (string) $account,
        'accounts' => [['accountIdentifier' => (string) $account, 'name' => 'Original', 'delegationIdentifier' => null]],
        ], $model->toArray());
    }
}
