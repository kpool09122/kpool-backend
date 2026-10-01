<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Source\Identity\Application\Service\StepUpOAuthSessionStorageServiceInterface;
use Source\Identity\Domain\Exception\InvalidOAuthStateException;
use Source\Identity\Domain\ValueObject\OAuthState;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationScope;
use Source\Identity\Domain\ValueObject\StepUpOAuthSession;
use Source\Shared\Domain\Support\TypedValue;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class StepUpOAuthSessionStorageService implements StepUpOAuthSessionStorageServiceInterface
{
    private const string KEY_PREFIX = 'step_up_oauth_session:';

    public function __construct(private readonly Request $request)
    {
    }

    public function store(OAuthState $state, StepUpOAuthSession $session): void
    {
        $ttl = $session->expiresAt->getTimestamp() - time();
        if ($ttl <= 0) {
            throw new InvalidOAuthStateException('Step-up OAuth session has already expired.');
        }
        Redis::setex($this->key($state), $ttl, json_encode([
            'identity_id' => (string) $session->identityIdentifier,
            'provider' => $session->provider->value,
            'scope' => $session->scope->value,
            'expires_at' => $session->expiresAt->format(DATE_ATOM),
            'return_to' => $session->returnTo,
        ], JSON_THROW_ON_ERROR));
    }

    public function consume(OAuthState $state): ?StepUpOAuthSession
    {
        $raw = Redis::command('GETDEL', [$this->key($state)]);
        if (! is_string($raw)) {
            return null;
        }
        $data = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($data)
            || ! isset($data['identity_id'], $data['provider'], $data['scope'], $data['expires_at'], $data['return_to'])) {
            return null;
        }
        $expiresAt = new DateTimeImmutable(TypedValue::string($data['expires_at']));
        if ($expiresAt <= new DateTimeImmutable()) {
            return null;
        }

        return new StepUpOAuthSession(
            new IdentityIdentifier(TypedValue::string($data['identity_id'])),
            SocialProvider::from(TypedValue::string($data['provider'])),
            StepUpAuthenticationScope::from(TypedValue::string($data['scope'])),
            $expiresAt,
            TypedValue::string($data['return_to']),
        );
    }

    private function key(OAuthState $state): string
    {
        if (! $this->request->hasSession()) {
            throw new InvalidOAuthStateException('An authenticated session is required.');
        }

        return self::KEY_PREFIX . $state . ':' . $this->request->session()->getId();
    }
}
