<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Service;

use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;
use Source\Identity\Application\Service\PasskeyRecovery\SecurityEventRecorderInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class LogSecurityEventRecorder implements SecurityEventRecorderInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function record(string $event, IdentityIdentifier $identityIdentifier, array $context = []): void
    {
        $record = fn () => $this->logger->notice('Identity security event.', [
            'security_event' => $event,
            'identity_id' => (string) $identityIdentifier,
            ...$context,
        ]);
        if (DB::transactionLevel() > 0) {
            DB::afterCommit($record);

            return;
        }

        $record();
    }
}
