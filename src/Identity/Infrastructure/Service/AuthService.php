<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use Application\Models\Identity\Identity as IdentityEloquent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Service\AuthServiceInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use UnexpectedValueException;

readonly class AuthService implements AuthServiceInterface
{
    public function __construct(
        private Request $request,
    ) {
    }

    public function login(Identity $identity): Identity
    {
        Auth::loginUsingId((string) $identity->identityIdentifier());

        $this->request->session()->regenerate();
        $this->request->session()->put(
            'identity_session_generation',
            $this->generation($identity->identityIdentifier()),
        );

        return $identity;
    }

    public function logout(): void
    {
        Auth::logout();

        $this->request->session()->invalidate();
        $this->request->session()->regenerateToken();
    }

    public function isLoggedIn(): bool
    {
        return Auth::check();
    }

    public function refreshAuthenticatedIdentity(Identity $identity): void
    {
        $eloquent = IdentityEloquent::query()->find((string) $identity->identityIdentifier());
        if ($eloquent !== null) {
            Auth::setUser($eloquent);
            $this->request->setUserResolver(static fn () => $eloquent);
        }
    }

    public function invalidateAllSessions(IdentityIdentifier $identityIdentifier): void
    {
        $invalidate = fn (): mixed => Redis::incr($this->generationKey($identityIdentifier));
        if (DB::transactionLevel() > 0) {
            DB::afterCommit($invalidate);

            return;
        }

        $invalidate();
    }

    public function isCurrentSessionValid(IdentityIdentifier $identityIdentifier): bool
    {
        $generation = $this->request->session()->get('identity_session_generation', 0);

        return is_int($generation) && $generation
            === $this->generation($identityIdentifier);
    }

    private function generation(IdentityIdentifier $identityIdentifier): int
    {
        $generation = Redis::get($this->generationKey($identityIdentifier));
        if ($generation === null || $generation === false) {
            return 0;
        }
        if (! is_int($generation) && (! is_string($generation) || ! ctype_digit($generation))) {
            throw new UnexpectedValueException('Invalid stored session generation.');
        }

        return (int) $generation;
    }

    private function generationKey(IdentityIdentifier $identityIdentifier): string
    {
        return 'identity_session_generation:' . $identityIdentifier;
    }
}
