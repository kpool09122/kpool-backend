<?php

declare(strict_types=1);

namespace Application\Http\Middleware;

use Application\Http\Exceptions\UnauthorizedHttpException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Source\Identity\Domain\Service\AuthServiceInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Symfony\Component\HttpFoundation\Response;

class EnsureAuthenticated
{
    public function __construct(private readonly AuthServiceInterface $authService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $isAuthenticated = Auth::check();
        if (! $isAuthenticated
            || ! $this->authService->isCurrentSessionValid(new IdentityIdentifier((string) Auth::id()))) {
            if ($isAuthenticated) {
                $this->authService->logout();
            }

            throw new UnauthorizedHttpException(
                detail: error_message('unauthorized', $request->header('Accept-Language', 'en')),
            );
        }

        return $next($request);
    }
}
