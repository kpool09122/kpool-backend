<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Source\Identity\Application\Service\StepUpAuthenticationStorageServiceInterface;
use Source\Identity\Domain\Exception\StepUpAuthenticationRequiredException;
use Source\Identity\Domain\ValueObject\StepUpAuthentication;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationMethod;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationScope;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class StepUpAuthenticationStorageService implements StepUpAuthenticationStorageServiceInterface
{
    private const string KEY_PREFIX = 'step_up_authentication:';

    public function __construct(private readonly Request $request)
    {
    }

    public function store(StepUpAuthentication $authentication): void
    {
        $ttl = $authentication->expiresAt->getTimestamp() - time();
        if ($ttl <= 0) {
            throw new StepUpAuthenticationRequiredException('Step-up authentication has already expired.');
        }

        Redis::setex($this->key($authentication->identityIdentifier, $authentication->scope), $ttl, json_encode([
            'identity_id' => (string) $authentication->identityIdentifier,
            'method' => $authentication->method->value,
            'verified_at' => $authentication->verifiedAt->format(DATE_ATOM),
            'scope' => $authentication->scope->value,
            'expires_at' => $authentication->expiresAt->format(DATE_ATOM),
        ], JSON_THROW_ON_ERROR));
    }

    public function requireValid(
        IdentityIdentifier $expectedIdentityIdentifier,
        StepUpAuthenticationScope $expectedScope,
    ): StepUpAuthentication {
        $raw = Redis::get($this->key($expectedIdentityIdentifier, $expectedScope));
        if (! is_string($raw)) {
            throw new StepUpAuthenticationRequiredException();
        }

        $data = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($data)
            || ! isset($data['identity_id'], $data['method'], $data['verified_at'], $data['scope'], $data['expires_at'])) {
            throw new StepUpAuthenticationRequiredException();
        }

        $authentication = new StepUpAuthentication(
            new IdentityIdentifier((string) $data['identity_id']),
            StepUpAuthenticationMethod::from((string) $data['method']),
            new DateTimeImmutable((string) $data['verified_at']),
            StepUpAuthenticationScope::from((string) $data['scope']),
            new DateTimeImmutable((string) $data['expires_at']),
        );
        if ((string) $authentication->identityIdentifier !== (string) $expectedIdentityIdentifier
            || $authentication->scope->value !== $expectedScope->value
            || $authentication->isExpired(new DateTimeImmutable())) {
            throw new StepUpAuthenticationRequiredException();
        }

        return $authentication;
    }

    private function key(IdentityIdentifier $identityIdentifier, StepUpAuthenticationScope $scope): string
    {
        if (! $this->request->hasSession()) {
            throw new StepUpAuthenticationRequiredException('An authenticated session is required.');
        }

        return self::KEY_PREFIX . $identityIdentifier . ':' . $scope->value . ':' . $this->request->session()->getId();
    }
}
