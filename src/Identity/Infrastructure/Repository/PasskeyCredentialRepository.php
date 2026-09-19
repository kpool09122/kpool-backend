<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Repository;

use Application\Models\Identity\PasskeyCredential as PasskeyCredentialEloquent;
use Source\Identity\Domain\Entity\PasskeyCredential;
use Source\Identity\Domain\Exception\InvalidPasskeyException;
use Source\Identity\Domain\Repository\PasskeyCredentialRepositoryInterface;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class PasskeyCredentialRepository implements PasskeyCredentialRepositoryInterface
{
    public function findByCredentialId(string $credentialId): ?PasskeyCredential
    {
        $model = PasskeyCredentialEloquent::query()->where('credential_id', $this->encodeCredentialId($credentialId))->first();

        return $model === null ? null : $this->toDomain($model);
    }

    public function findByIdentifier(string $identifier): ?PasskeyCredential
    {
        $model = PasskeyCredentialEloquent::query()->find($identifier);

        return $model === null ? null : $this->toDomain($model);
    }

    public function findByIdentity(IdentityIdentifier $identityIdentifier): array
    {
        return $this->findByIdentityQuery($identityIdentifier, false);
    }

    public function findByIdentityForUpdate(IdentityIdentifier $identityIdentifier): array
    {
        return $this->findByIdentityQuery($identityIdentifier, true);
    }

    /** @return PasskeyCredential[] */
    private function findByIdentityQuery(IdentityIdentifier $identityIdentifier, bool $forUpdate): array
    {
        $query = PasskeyCredentialEloquent::query()->where('identity_id', (string) $identityIdentifier);
        if ($forUpdate) {
            $query->lockForUpdate();
        }

        return $query
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(fn (PasskeyCredentialEloquent $model): PasskeyCredential => $this->toDomain($model))
            ->all();
    }

    public function save(PasskeyCredential $credential): void
    {
        PasskeyCredentialEloquent::query()->updateOrCreate(
            ['id' => $credential->identifier()],
            [
                'identity_id' => (string) $credential->identityIdentifier(),
                'credential_id' => $this->encodeCredentialId($credential->credentialId()),
                'credential_source' => $credential->credentialSource(),
                'sign_count' => $credential->signCount(),
                'backup_eligible' => $credential->backupEligible(),
                'backup_state' => $credential->backupState(),
                'transports' => $credential->transports(),
                'display_name' => $credential->displayName(),
                'last_used_at' => $credential->lastUsedAt(),
            ],
        );
    }

    public function delete(PasskeyCredential $credential): void
    {
        PasskeyCredentialEloquent::query()->where('id', $credential->identifier())->delete();
    }

    private function toDomain(PasskeyCredentialEloquent $model): PasskeyCredential
    {
        return new PasskeyCredential(
            $model->id,
            new IdentityIdentifier($model->identity_id),
            $this->decodeCredentialId($model->credential_id),
            $model->credential_source,
            $model->sign_count,
            $model->backup_eligible,
            $model->backup_state,
            $model->transports,
            $model->display_name,
            $model->last_used_at?->toDateTimeImmutable(),
            $model->created_at?->toDateTimeImmutable(),
            $model->updated_at?->toDateTimeImmutable(),
        );
    }

    private function encodeCredentialId(string $credentialId): string
    {
        return rtrim(strtr(base64_encode($credentialId), '+/', '-_'), '=');
    }

    private function decodeCredentialId(string $credentialId): string
    {
        $decoded = base64_decode(strtr($credentialId, '-_', '+/'), true);
        if ($decoded === false) {
            throw new InvalidPasskeyException('保存されたcredential IDが不正です');
        }

        return $decoded;
    }
}
