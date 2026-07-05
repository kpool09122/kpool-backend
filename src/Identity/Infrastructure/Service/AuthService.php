<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use Application\Models\Identity\Identity as IdentityEloquent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Service\AuthServiceInterface;

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
}
