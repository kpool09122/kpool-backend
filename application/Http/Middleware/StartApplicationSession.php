<?php

declare(strict_types=1);

namespace Application\Http\Middleware;

use Application\Http\Context\ServiceWithdrawalContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Session\SessionManager;
use Override;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\Service\ApiAuthenticationContext;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class StartApplicationSession extends StartSession
{
    public function __construct(
        SessionManager $manager,
        private readonly LoggerInterface $logger,
        ?callable $cacheFactoryResolver = null,
    ) {
        parent::__construct($manager, $cacheFactoryResolver);
    }

    /** @param Request $request */
    #[Override]
    public function handle($request, Closure $next): Response
    {
        if ($request->attributes->get(ApiAuthenticationContext::class) instanceof ApiAuthenticationContext) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }

    /** @param Request $request */
    #[Override]
    protected function saveSession($request): void
    {
        try {
            parent::saveSession($request);
        } catch (Throwable $exception) {
            if (! ServiceWithdrawalContext::isCommitted($request)) {
                throw $exception;
            }
            $this->logger->error('Failed to save session after committed identity withdrawal.', ['exception' => $exception]);
        }
    }
}
