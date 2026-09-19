<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Repository;

use Application\Models\Identity\PasskeyUser as PasskeyUserEloquent;
use Source\Identity\Domain\Entity\PasskeyUser;
use Source\Identity\Domain\Repository\PasskeyUserRepositoryInterface;
use Source\Identity\Domain\ValueObject\PasskeyUserIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class PasskeyUserRepository implements PasskeyUserRepositoryInterface
{
    public function save(PasskeyUser $user): void
    {
        PasskeyUserEloquent::query()->updateOrCreate(
            ['id' => (string) $user->identifier()],
            ['identity_id' => $user->identityIdentifier() !== null ? (string) $user->identityIdentifier() : null],
        );
    }

    public function findByIdentifier(PasskeyUserIdentifier $identifier): ?PasskeyUser
    {
        $model = PasskeyUserEloquent::query()->find((string) $identifier);

        return $model === null ? null : $this->toDomainEntity($model);
    }

    public function findByIdentityIdentifier(IdentityIdentifier $identityIdentifier): ?PasskeyUser
    {
        $model = PasskeyUserEloquent::query()
            ->where('identity_id', (string) $identityIdentifier)
            ->first();

        return $model === null ? null : $this->toDomainEntity($model);
    }

    private function toDomainEntity(PasskeyUserEloquent $model): PasskeyUser
    {
        return new PasskeyUser(
            new PasskeyUserIdentifier($model->id),
            $model->identity_id !== null ? new IdentityIdentifier($model->identity_id) : null,
        );
    }
}
