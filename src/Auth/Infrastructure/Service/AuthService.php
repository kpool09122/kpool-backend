<?php

declare(strict_types=1);

namespace Source\Auth\Infrastructure\Service;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Source\Auth\Domain\Entity\User;
use Source\Auth\Domain\Service\AuthServiceInterface;

readonly class AuthService implements AuthServiceInterface
{
    public function __construct(
        private Request $request,
    ) {
    }

    public function login(User $user): User
    {
        Auth::loginUsingId((string) $user->userIdentifier());

        $this->request->session()->regenerate();

        return $user;
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
}
