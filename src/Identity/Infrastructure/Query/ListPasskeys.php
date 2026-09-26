<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Query;

use Application\Models\Identity\PasskeyCredential as PasskeyCredentialModel;
use Source\Identity\Application\Service\StepUpAuthenticationStorageServiceInterface;
use Source\Identity\Application\UseCase\Query\ListPasskeys\ListPasskeysInputPort;
use Source\Identity\Application\UseCase\Query\ListPasskeys\ListPasskeysInterface;
use Source\Identity\Application\UseCase\Query\PasskeyReadModel;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationScope;

readonly class ListPasskeys implements ListPasskeysInterface
{
    public function __construct(
        private StepUpAuthenticationStorageServiceInterface $stepUpAuthenticationStorage,
    ) {
    }

    public function process(ListPasskeysInputPort $input): array
    {
        $this->stepUpAuthenticationStorage->requireValid(
            $input->identityIdentifier(),
            StepUpAuthenticationScope::PASSKEY_MANAGE,
        );

        return PasskeyCredentialModel::query()
            ->select([
                'id',
                'display_name',
                'transports',
                'backup_eligible',
                'backup_state',
                'last_used_at',
                'created_at',
            ])
            ->whereRelation('passkeyUser', 'identity_id', (string) $input->identityIdentifier())
            ->orderBy('created_at')
            ->get()
            ->map(static fn (PasskeyCredentialModel $credential): PasskeyReadModel => new PasskeyReadModel(
                passkeyIdentifier: $credential->id,
                displayName: $credential->display_name,
                transports: $credential->transports,
                backupEligible: $credential->backup_eligible,
                backupState: $credential->backup_state,
                lastUsedAt: $credential->last_used_at?->toIso8601String(),
                createdAt: $credential->created_at->toIso8601String(),
            ))
            ->all();
    }
}
