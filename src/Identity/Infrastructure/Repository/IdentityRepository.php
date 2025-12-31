<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Repository;

use Application\Models\Identity\Identity as IdentityEloquent;
use Application\Models\Identity\IdentitySocialConnection as IdentitySocialConnectionEloquent;
use Illuminate\Support\Carbon;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\ValueObject\HashedPassword;
use Source\Identity\Domain\ValueObject\SocialConnection;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Source\Identity\Domain\ValueObject\UserName;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;

class IdentityRepository implements IdentityRepositoryInterface
{
    public function __construct(
        private readonly UuidGeneratorInterface $uuidGenerator,
    ) {
    }

    public function findByEmail(Email $email): ?Identity
    {
        $eloquent = IdentityEloquent::query()
            ->with(['socialConnections'])
            ->where('email', (string) $email)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    public function findBySocialConnection(SocialProvider $provider, string $providerUserId): ?Identity
    {
        $eloquent = IdentityEloquent::query()
            ->with(['socialConnections'])
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

    public function save(Identity $identity): void
    {
        $eloquent = IdentityEloquent::query()->updateOrCreate(
            ['id' => (string) $identity->identityIdentifier()],
            [
                'username' => (string) $identity->username(),
                'email' => (string) $identity->email(),
                'language' => $identity->language()->value,
                'profile_image' => $identity->profileImage() !== null ? (string) $identity->profileImage() : null,
                'password' => (string) $identity->hashedPassword(),
                'email_verified_at' => $identity->emailVerifiedAt() !== null
                    ? Carbon::createFromImmutable($identity->emailVerifiedAt())
                    : null,
            ]
        );

        $this->syncSocialConnections($eloquent, $identity->socialConnections());
    }

    /**
     * @param SocialConnection[] $socialConnections
     */
    private function syncSocialConnections(IdentityEloquent $eloquent, array $socialConnections): void
    {
        $eloquent->socialConnections()->delete();

        foreach ($socialConnections as $socialConnection) {
            $eloquent->socialConnections()->create([
                'id' => $this->uuidGenerator->generate(),
                'provider' => $socialConnection->provider()->value,
                'provider_user_id' => $socialConnection->providerUserId(),
            ]);
        }
    }

    private function toDomainEntity(IdentityEloquent $eloquent): Identity
    {
        $socialConnections = $eloquent->socialConnections
            ->map(fn (IdentitySocialConnectionEloquent $connection) => new SocialConnection(
                SocialProvider::fromString($connection->provider),
                $connection->provider_user_id
            ))
            ->toArray();

        return new Identity(
            new IdentityIdentifier($eloquent->id),
            new UserName($eloquent->username),
            new Email($eloquent->email),
            Language::from($eloquent->language),
            $eloquent->profile_image !== null ? new ImagePath($eloquent->profile_image) : null,
            new HashedPassword($eloquent->password),
            $eloquent->email_verified_at !== null
                ? $eloquent->email_verified_at->toDateTimeImmutable()
                : null,
            $socialConnections
        );
    }
}
