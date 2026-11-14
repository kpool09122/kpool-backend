<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Passport;

use Application\Models\Identity\McpAccessToken;
use Illuminate\Contracts\Events\Dispatcher;
use Laravel\Passport\Bridge\RefreshTokenRepository as PassportRefreshTokenRepository;
use Laravel\Passport\Passport;

class RefreshTokenRepository extends PassportRefreshTokenRepository
{
    public function __construct(Dispatcher $events, private readonly IssuanceContext $issuanceContext)
    {
        parent::__construct($events);
    }

    public function isRefreshTokenRevoked(string $tokenId): bool
    {
        $refresh = Passport::refreshToken()->newQuery()->find($tokenId);
        if ($refresh === null || $refresh->revoked || $refresh->expires_at === null || $refresh->expires_at->isPast()) {
            return true;
        }
        $token = McpAccessToken::query()->find($refresh->access_token_id);

        return $token === null || $token->revoked
            || ! $this->issuanceContext->load($token->authorization_connection_id);
    }
}
