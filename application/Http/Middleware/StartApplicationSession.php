<?php

declare(strict_types=1);

namespace Application\Http\Middleware;

use Application\Http\Context\ServiceWithdrawalContext;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Session\SessionManager;
use Override;
use Psr\Log\LoggerInterface;
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
