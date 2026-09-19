<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Repository;

use Application\Models\Identity\PasskeyCredential as PasskeyCredentialEloquent;
use Illuminate\Support\Carbon;
use JsonException;
use Source\Identity\Domain\Entity\PasskeyCredential;
use Source\Identity\Domain\Repository\PasskeyCredentialRepositoryInterface;
use Source\Identity\Domain\ValueObject\CredentialSource;
use Source\Identity\Domain\ValueObject\PasskeyCredentialIdentifier;
use Source\Identity\Domain\ValueObject\PasskeyDisplayName;
use Source\Identity\Domain\ValueObject\WebAuthnCredentialId;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class PasskeyCredentialRepository implements PasskeyCredentialRepositoryInterface
{
    public function save(PasskeyCredential $credential): void
    {
        PasskeyCredentialEloquent::query()->updateOrCreate(
            ['id' => (string) $credential->identifier()],
            [
                'identity_id' => (string) $credential->identityIdentifier(),
                'credential_id' => (string) $credential->credentialId(),
                'credential_source' => json_decode((string) $credential->credentialSource(), true, flags: JSON_THROW_ON_ERROR),
                'sign_count' => $credential->signCount(),
                'backup_eligible' => $credential->backupEligible(),
                'backup_state' => $credential->backupState(),
                'transports' => $credential->transports(),
                'display_name' => (string) $credential->displayName(),
                'last_used_at' => $credential->lastUsedAt() !== null
                    ? Carbon::createFromImmutable($credential->lastUsedAt())
                    : null,
            ],
        );
    }

    public function findByIdentifier(PasskeyCredentialIdentifier $identifier): ?PasskeyCredential
    {
        $model = PasskeyCredentialEloquent::query()->find((string) $identifier);

        return $model === null ? null : $this->toDomainEntity($model);
    }

    public function findByCredentialId(WebAuthnCredentialId $credentialId): ?PasskeyCredential
    {
        $model = PasskeyCredentialEloquent::query()->where('credential_id', (string) $credentialId)->first();

        return $model === null ? null : $this->toDomainEntity($model);
    }

    public function findByIdentityIdentifier(IdentityIdentifier $identityIdentifier): array
    {
        return PasskeyCredentialEloquent::query()
            ->where('identity_id', (string) $identityIdentifier)
            ->orderBy('created_at')
            ->get()
            ->map(fn (PasskeyCredentialEloquent $model): PasskeyCredential => $this->toDomainEntity($model))
            ->all();
    }

    public function delete(PasskeyCredentialIdentifier $identifier): void
    {
        PasskeyCredentialEloquent::query()->whereKey((string) $identifier)->delete();
    }

    /** @throws JsonException */
    private function toDomainEntity(PasskeyCredentialEloquent $model): PasskeyCredential
    {
        return new PasskeyCredential(
            new PasskeyCredentialIdentifier($model->id),
            new IdentityIdentifier($model->identity_id),
            new WebAuthnCredentialId($model->credential_id),
            new CredentialSource(json_encode($model->credential_source, JSON_THROW_ON_ERROR)),
            $model->sign_count,
            $model->backup_eligible,
            $model->backup_state,
            $model->transports,
            new PasskeyDisplayName($model->display_name),
            $model->last_used_at?->toDateTimeImmutable(),
        );
    }
}
