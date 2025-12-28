<?php

declare(strict_types=1);

namespace Source\SiteManagement\User\Infrastructure\Repository;

use Application\Models\SiteManagement\User as UserEloquent;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\User\Domain\Entity\User;
use Source\SiteManagement\User\Domain\Repository\UserRepositoryInterface;
use Source\SiteManagement\User\Domain\ValueObject\Role;
use Source\SiteManagement\User\Domain\ValueObject\UserIdentifier;

class UserRepository implements UserRepositoryInterface
{
    public function findById(UserIdentifier $userIdentifier): ?User
    {
        $eloquent = UserEloquent::query()
            ->where('id', (string) $userIdentifier)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    public function findByIdentityIdentifier(IdentityIdentifier $identityIdentifier): ?User
    {
        $eloquent = UserEloquent::query()
            ->where('identity_id', (string) $identityIdentifier)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    public function save(User $user): void
    {
        UserEloquent::query()->updateOrCreate(
            ['id' => (string) $user->userIdentifier()],
            [
                'identity_id' => (string) $user->identityIdentifier(),
                'role' => $user->role()->value,
            ]
        );
    }

    private function toDomainEntity(UserEloquent $eloquent): User
    {
        return new User(
            new UserIdentifier($eloquent->id),
            new IdentityIdentifier($eloquent->identity_id),
            Role::from($eloquent->role),
        );
    }
}
