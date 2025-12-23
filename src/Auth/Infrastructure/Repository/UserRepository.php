<?php

declare(strict_types=1);

namespace Source\Auth\Infrastructure\Repository;

use Application\Models\Auth\User as UserEloquent;
use Application\Models\Auth\UserServiceRole as UserServiceRoleEloquent;
use Application\Models\Auth\UserSocialConnection as UserSocialConnectionEloquent;
use Illuminate\Support\Carbon;
use Source\Auth\Domain\Entity\User;
use Source\Auth\Domain\Repository\UserRepositoryInterface;
use Source\Auth\Domain\ValueObject\HashedPassword;
use Source\Auth\Domain\ValueObject\ServiceRole;
use Source\Auth\Domain\ValueObject\SocialConnection;
use Source\Auth\Domain\ValueObject\SocialProvider;
use Source\Auth\Domain\ValueObject\UserName;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\UserIdentifier;

class UserRepository implements UserRepositoryInterface
{
    public function findByEmail(Email $email): ?User
    {
        $eloquent = UserEloquent::query()
            ->with(['serviceRoles', 'socialConnections'])
            ->where('email', (string) $email)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    public function findBySocialConnection(SocialProvider $provider, string $providerUserId): ?User
    {
        $eloquent = UserEloquent::query()
            ->with(['serviceRoles', 'socialConnections'])
            ->whereHas('socialConnections', function ($query) use ($provider, $providerUserId): void {
                $query->where('provider', $provider->value)
                    ->where('provider_user_id', $providerUserId);
            })
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    public function save(User $user): void
    {
        $eloquent = UserEloquent::query()->updateOrCreate(
            ['id' => (string) $user->userIdentifier()],
            [
                'username' => (string) $user->username(),
                'email' => (string) $user->email(),
                'language' => $user->language()->value,
                'profile_image' => $user->profileImage() !== null ? (string) $user->profileImage() : null,
                'password' => (string) $user->hashedPassword(),
                'email_verified_at' => $user->emailVerifiedAt() !== null
                    ? Carbon::createFromImmutable($user->emailVerifiedAt())
                    : null,
            ]
        );

        $this->syncServiceRoles($eloquent, $user->serviceRoles());
        $this->syncSocialConnections($eloquent, $user->socialConnections());
    }

    /**
     * @param ServiceRole[] $serviceRoles
     */
    private function syncServiceRoles(UserEloquent $eloquent, array $serviceRoles): void
    {
        $eloquent->serviceRoles()->delete();

        foreach ($serviceRoles as $serviceRole) {
            $eloquent->serviceRoles()->create([
                'service' => $serviceRole->service(),
                'role' => $serviceRole->role(),
            ]);
        }
    }

    /**
     * @param SocialConnection[] $socialConnections
     */
    private function syncSocialConnections(UserEloquent $eloquent, array $socialConnections): void
    {
        $eloquent->socialConnections()->delete();

        foreach ($socialConnections as $socialConnection) {
            $eloquent->socialConnections()->create([
                'provider' => $socialConnection->provider()->value,
                'provider_user_id' => $socialConnection->providerUserId(),
            ]);
        }
    }

    private function toDomainEntity(UserEloquent $eloquent): User
    {
        $serviceRoles = $eloquent->serviceRoles
            ->map(fn (UserServiceRoleEloquent $role) => new ServiceRole(
                $role->service,
                $role->role
            ))
            ->toArray();

        $socialConnections = $eloquent->socialConnections
            ->map(fn (UserSocialConnectionEloquent $connection) => new SocialConnection(
                SocialProvider::fromString($connection->provider),
                $connection->provider_user_id
            ))
            ->toArray();

        return new User(
            new UserIdentifier($eloquent->id),
            new UserName($eloquent->username),
            new Email($eloquent->email),
            Language::from($eloquent->language),
            $eloquent->profile_image !== null ? new ImagePath($eloquent->profile_image) : null,
            new HashedPassword($eloquent->password),
            $serviceRoles,
            $eloquent->email_verified_at !== null
                ? $eloquent->email_verified_at->toDateTimeImmutable()
                : null,
            $socialConnections
        );
    }
}
