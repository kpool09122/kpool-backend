<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\Service\IdentityWithdrawalSessionServiceInterface;
use Source\Identity\Domain\Service\AuthServiceInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Throwable;

readonly class IdentityWithdrawalSessionService implements IdentityWithdrawalSessionServiceInterface
{
    public function __construct(
        private AuthServiceInterface $authService,
        private LoggerInterface $logger,
    ) {
    }

    public function terminate(IdentityIdentifier $identityIdentifier): void
    {
        $terminate = function () use ($identityIdentifier): void {
            try {
                $this->authService->invalidateAllSessions($identityIdentifier);
            } catch (Throwable $exception) {
                $this->logger->error('Withdrawn identity session invalidation failed.', ['exception' => $exception]);
            }

            try {
                $this->authService->logout();
            } catch (Throwable $exception) {
                $this->logger->error('Withdrawn identity logout failed.', ['exception' => $exception]);
            }
        };

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($terminate);

            return;
        }

        $terminate();
    }
}
