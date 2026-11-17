<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use Application\Models\Identity\AuthorizationConnection;
use Application\Models\Identity\McpAccessToken;
use Application\Models\Identity\McpAuthCode;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;
use Source\Identity\Application\Service\AuthorizationTokenLifecycleServiceInterface;
use Source\Identity\Application\Service\InternalApiTokenServiceInterface;
use Source\Identity\Domain\ValueObject\AuthorizationConnectionIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class AuthorizationTokenLifecycleService implements AuthorizationTokenLifecycleServiceInterface
{
    public function __construct(private InternalApiTokenServiceInterface $internalApiTokenService)
    {
    }

    public function revoke(AuthorizationConnectionIdentifier $connectionIdentifier): void
    {
        $identifiers = McpAccessToken::query()->where('authorization_connection_id', (string) $connectionIdentifier)
            ->get(['id'])->map(static fn (McpAccessToken $token): string => $token->id)->all();
        Passport::refreshToken()->newQuery()->whereIn('access_token_id', $identifiers)->update(['revoked' => true]);
        McpAccessToken::query()->where('authorization_connection_id', (string) $connectionIdentifier)->update(['revoked' => true]);
        McpAuthCode::query()->where('authorization_connection_id', (string) $connectionIdentifier)->update(['revoked' => true]);
        DB::afterCommit(function () use ($identifiers): void {
            foreach ($identifiers as $identifier) {
                $this->internalApiTokenService->forgetCachedAuthorization($identifier);
            }
        });
    }

    public function deleteForIdentity(IdentityIdentifier $identityIdentifier): void
    {
        $connections = AuthorizationConnection::query()->where('identity_id', (string) $identityIdentifier)->select('id');
        $tokens = McpAccessToken::query()->whereIn('authorization_connection_id', $connections)->select('id');
        Passport::refreshToken()->newQuery()->whereIn('access_token_id', $tokens)->delete();
        McpAccessToken::query()->whereIn('authorization_connection_id', $connections)->delete();
        McpAuthCode::query()->whereIn('authorization_connection_id', $connections)->delete();
        AuthorizationConnection::query()->where('identity_id', (string) $identityIdentifier)->delete();
    }
}
