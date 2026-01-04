<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\ValueObject\HashedPassword;
use Source\Identity\Domain\ValueObject\PlainPassword;
use Source\Identity\Domain\ValueObject\SocialConnection;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Source\Identity\Domain\ValueObject\UserName;
use Source\Identity\Infrastructure\Repository\IdentityRepository;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class IdentityRepositoryTest extends TestCase
{
    /**
     * 正しくDIが動作していること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $repository = $this->app->make(IdentityRepositoryInterface::class);
        $this->assertInstanceOf(IdentityRepository::class, $repository);
    }

    /**
     * 正常系: findByEmailでIdentityが見つかること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByEmailReturnsIdentity(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $email = 'findbyemail@example.com';
        CreateIdentity::create($identityIdentifier, ['email' => $email]);
        CreateIdentity::createSocialConnection($identityIdentifier, SocialProvider::GOOGLE, 'google-user-123');

        $repository = $this->app->make(IdentityRepositoryInterface::class);
        $result = $repository->findByEmail(new Email($email));

        $this->assertNotNull($result);
        $this->assertInstanceOf(Identity::class, $result);
        $this->assertSame((string) $identityIdentifier, (string) $result->identityIdentifier());
        $this->assertSame($email, (string) $result->email());
        $this->assertCount(1, $result->socialConnections());
        $this->assertSame(SocialProvider::GOOGLE, $result->socialConnections()[0]->provider());
        $this->assertSame('google-user-123', $result->socialConnections()[0]->providerUserId());
    }

    /**
     * 正常系: findByEmailでIdentityが見つからない場合nullを返すこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByEmailReturnsNullWhenNotFound(): void
    {
        $repository = $this->app->make(IdentityRepositoryInterface::class);
        $result = $repository->findByEmail(new Email('nonexistent@example.com'));

        $this->assertNull($result);
    }

    /**
     * 正常系: findBySocialConnectionでIdentityが見つかること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindBySocialConnectionReturnsIdentity(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $providerUserId = 'line-user-456';
        CreateIdentity::create($identityIdentifier, ['email' => 'social@example.com']);
        CreateIdentity::createSocialConnection($identityIdentifier, SocialProvider::LINE, $providerUserId);

        $repository = $this->app->make(IdentityRepositoryInterface::class);
        $result = $repository->findBySocialConnection(SocialProvider::LINE, $providerUserId);

        $this->assertNotNull($result);
        $this->assertInstanceOf(Identity::class, $result);
        $this->assertSame((string) $identityIdentifier, (string) $result->identityIdentifier());
    }

    /**
     * 正常系: findBySocialConnectionでIdentityが見つからない場合nullを返すこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindBySocialConnectionReturnsNullWhenNotFound(): void
    {
        $repository = $this->app->make(IdentityRepositoryInterface::class);
        $result = $repository->findBySocialConnection(SocialProvider::KAKAO, 'nonexistent-id');

        $this->assertNull($result);
    }

    /**
     * 正常系: saveで新規Identityを保存できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveCreatesNewIdentity(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $email = new Email('newidentity@example.com');
        $emailVerifiedAt = new DateTimeImmutable('2024-01-01 12:00:00');

        $identity = new Identity(
            $identityIdentifier,
            new UserName('new-identity'),
            $email,
            Language::JAPANESE,
            new ImagePath('/images/profile.jpg'),
            HashedPassword::fromPlain(new PlainPassword('password123')),
            $emailVerifiedAt,
            [new SocialConnection(SocialProvider::GOOGLE, 'google-new-identity')]
        );

        $repository = $this->app->make(IdentityRepositoryInterface::class);
        $repository->save($identity);

        $this->assertDatabaseHas('identities', [
            'id' => (string) $identityIdentifier,
            'username' => 'new-identity',
            'email' => 'newidentity@example.com',
            'language' => 'ja',
            'profile_image' => '/images/profile.jpg',
        ]);

        $this->assertDatabaseHas('identity_social_connections', [
            'identity_id' => (string) $identityIdentifier,
            'provider' => 'google',
            'provider_user_id' => 'google-new-identity',
        ]);

        // toDomainEntityでemail_verified_atが正しく変換されることを確認
        $result = $repository->findByEmail($email);
        $this->assertNotNull($result);
        $this->assertNotNull($result->emailVerifiedAt());
        $this->assertSame(
            $emailVerifiedAt->format('Y-m-d H:i:s'),
            $result->emailVerifiedAt()->format('Y-m-d H:i:s')
        );
    }

    /**
     * 正常系: saveで既存Identityを更新できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveUpdatesExistingIdentity(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier, [
            'email' => 'original@example.com',
            'username' => 'original-identity',
        ]);

        $updatedIdentity = new Identity(
            $identityIdentifier,
            new UserName('updated-identity'),
            new Email('updated@example.com'),
            Language::KOREAN,
            null,
            HashedPassword::fromPlain(new PlainPassword('newpassword')),
            new DateTimeImmutable('2024-06-01 00:00:00'),
            []
        );

        $repository = $this->app->make(IdentityRepositoryInterface::class);
        $repository->save($updatedIdentity);

        $this->assertDatabaseHas('identities', [
            'id' => (string) $identityIdentifier,
            'username' => 'updated-identity',
            'email' => 'updated@example.com',
            'language' => 'ko',
            'profile_image' => null,
        ]);
    }

    /**
     * 正常系: email_verified_atがnullのIdentityを正しく保存・取得できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveAndFindIdentityWithNullEmailVerifiedAt(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $email = new Email('unverified@example.com');

        $identity = new Identity(
            $identityIdentifier,
            new UserName('unverified-identity'),
            $email,
            Language::ENGLISH,
            null,
            HashedPassword::fromPlain(new PlainPassword('password123')),
            null,
            []
        );

        $repository = $this->app->make(IdentityRepositoryInterface::class);
        $repository->save($identity);

        $result = $repository->findByEmail($email);

        $this->assertNotNull($result);
        $this->assertNull($result->emailVerifiedAt());
    }

    /**
     * 正常系: 複数のソーシャル接続を持つIdentityを正しく保存・取得できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveAndFindIdentityWithMultipleSocialConnections(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $email = new Email('multisocial@example.com');

        $identity = new Identity(
            $identityIdentifier,
            new UserName('multi-social-identity'),
            $email,
            Language::JAPANESE,
            null,
            HashedPassword::fromPlain(new PlainPassword('password123')),
            null,
            [
                new SocialConnection(SocialProvider::GOOGLE, 'google-id-1'),
                new SocialConnection(SocialProvider::LINE, 'line-id-1'),
                new SocialConnection(SocialProvider::KAKAO, 'kakao-id-1'),
            ]
        );

        $repository = $this->app->make(IdentityRepositoryInterface::class);
        $repository->save($identity);

        $result = $repository->findByEmail($email);

        $this->assertNotNull($result);
        $this->assertCount(3, $result->socialConnections());
    }

    /**
     * 正常系: findByIdでIdentityが見つかること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdReturnsIdentity(): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier, [
            'email' => 'findbyid@example.com',
            'username' => 'find-by-id-user',
        ]);

        $repository = $this->app->make(IdentityRepositoryInterface::class);
        $result = $repository->findById($identityIdentifier);

        $this->assertNotNull($result);
        $this->assertInstanceOf(Identity::class, $result);
        $this->assertSame((string) $identityIdentifier, (string) $result->identityIdentifier());
        $this->assertSame('findbyid@example.com', (string) $result->email());
        $this->assertSame('find-by-id-user', (string) $result->username());
    }

    /**
     * 正常系: findByIdでIdentityが見つからない場合nullを返すこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdReturnsNullWhenNotFound(): void
    {
        $repository = $this->app->make(IdentityRepositoryInterface::class);
        $result = $repository->findById(new IdentityIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($result);
    }

    /**
     * 正常系: findByIdで委譲Identityが正しく取得できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdReturnsDelegatedIdentity(): void
    {
        $originalIdentityId = StrTestHelper::generateUuid();
        $delegatedIdentityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $delegationId = StrTestHelper::generateUuid();

        CreateIdentity::create(new IdentityIdentifier($originalIdentityId), [
            'email' => 'original@example.com',
        ]);
        CreateIdentity::create($delegatedIdentityId, [
            'email' => 'delegated@example.com',
            'delegation_identifier' => $delegationId,
            'original_identity_identifier' => $originalIdentityId,
        ]);

        $repository = $this->app->make(IdentityRepositoryInterface::class);
        $result = $repository->findById($delegatedIdentityId);

        $this->assertNotNull($result);
        $this->assertTrue($result->isDelegatedIdentity());
        $this->assertSame($delegationId, (string) $result->delegationIdentifier());
        $this->assertSame($originalIdentityId, (string) $result->originalIdentityIdentifier());
    }

    /**
     * 正常系: findByDelegationで委譲Identityが見つかること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByDelegationReturnsIdentity(): void
    {
        $originalIdentityId = StrTestHelper::generateUuid();
        $delegatedIdentityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $delegationId = new DelegationIdentifier(StrTestHelper::generateUuid());

        CreateIdentity::create(new IdentityIdentifier($originalIdentityId), [
            'email' => 'original-delegation@example.com',
        ]);
        CreateIdentity::create($delegatedIdentityId, [
            'email' => 'delegated-delegation@example.com',
            'delegation_identifier' => (string) $delegationId,
            'original_identity_identifier' => $originalIdentityId,
        ]);

        $repository = $this->app->make(IdentityRepositoryInterface::class);
        $result = $repository->findByDelegation($delegationId);

        $this->assertNotNull($result);
        $this->assertInstanceOf(Identity::class, $result);
        $this->assertSame((string) $delegatedIdentityId, (string) $result->identityIdentifier());
        $this->assertSame((string) $delegationId, (string) $result->delegationIdentifier());
        $this->assertSame($originalIdentityId, (string) $result->originalIdentityIdentifier());
    }

    /**
     * 正常系: findByDelegationで委譲Identityが見つからない場合nullを返すこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByDelegationReturnsNullWhenNotFound(): void
    {
        $repository = $this->app->make(IdentityRepositoryInterface::class);
        $result = $repository->findByDelegation(new DelegationIdentifier(StrTestHelper::generateUuid()));

        $this->assertNull($result);
    }

    /**
     * 正常系: findDelegatedIdentitiesで複数の委譲Identityが取得できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindDelegatedIdentitiesReturnsMultipleIdentities(): void
    {
        $originalIdentityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $delegatedIdentityId1 = new IdentityIdentifier(StrTestHelper::generateUuid());
        $delegatedIdentityId2 = new IdentityIdentifier(StrTestHelper::generateUuid());
        $delegationId1 = StrTestHelper::generateUuid();
        $delegationId2 = StrTestHelper::generateUuid();

        CreateIdentity::create($originalIdentityId, [
            'email' => 'original-multi@example.com',
        ]);
        CreateIdentity::create($delegatedIdentityId1, [
            'email' => 'delegated1@example.com',
            'delegation_identifier' => $delegationId1,
            'original_identity_identifier' => (string) $originalIdentityId,
        ]);
        CreateIdentity::create($delegatedIdentityId2, [
            'email' => 'delegated2@example.com',
            'delegation_identifier' => $delegationId2,
            'original_identity_identifier' => (string) $originalIdentityId,
        ]);

        $repository = $this->app->make(IdentityRepositoryInterface::class);
        $results = $repository->findDelegatedIdentities($originalIdentityId);

        $this->assertCount(2, $results);
        $this->assertContainsOnlyInstancesOf(Identity::class, $results);

        $resultIds = array_map(
            fn (Identity $identity) => (string) $identity->identityIdentifier(),
            $results
        );
        $this->assertContains((string) $delegatedIdentityId1, $resultIds);
        $this->assertContains((string) $delegatedIdentityId2, $resultIds);
    }

    /**
     * 正常系: findDelegatedIdentitiesで委譲Identityがない場合は空配列を返すこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindDelegatedIdentitiesReturnsEmptyArrayWhenNoDelegations(): void
    {
        $originalIdentityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($originalIdentityId, [
            'email' => 'no-delegations@example.com',
        ]);

        $repository = $this->app->make(IdentityRepositoryInterface::class);
        $results = $repository->findDelegatedIdentities($originalIdentityId);

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    /**
     * 正常系: deleteByDelegationで委譲Identityが削除されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testDeleteByDelegationDeletesIdentity(): void
    {
        $originalIdentityId = StrTestHelper::generateUuid();
        $delegatedIdentityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $delegationId = new DelegationIdentifier(StrTestHelper::generateUuid());

        CreateIdentity::create(new IdentityIdentifier($originalIdentityId), [
            'email' => 'original-delete@example.com',
        ]);
        CreateIdentity::create($delegatedIdentityId, [
            'email' => 'to-be-deleted@example.com',
            'delegation_identifier' => (string) $delegationId,
            'original_identity_identifier' => $originalIdentityId,
        ]);

        $repository = $this->app->make(IdentityRepositoryInterface::class);

        // 削除前は存在することを確認
        $this->assertNotNull($repository->findByDelegation($delegationId));

        $repository->deleteByDelegation($delegationId);

        // 削除後は存在しないことを確認
        $this->assertNull($repository->findByDelegation($delegationId));
        $this->assertDatabaseMissing('identities', [
            'id' => (string) $delegatedIdentityId,
        ]);
    }

    /**
     * 正常系: saveで委譲Identityを保存できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testSaveCreatesDelegatedIdentity(): void
    {
        $originalIdentityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $delegatedIdentityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $delegationId = new DelegationIdentifier(StrTestHelper::generateUuid());

        CreateIdentity::create($originalIdentityId, [
            'email' => 'original-save@example.com',
        ]);

        $delegatedIdentity = new Identity(
            $delegatedIdentityId,
            new UserName('delegated-user'),
            new Email('delegated-save@example.com'),
            Language::JAPANESE,
            null,
            HashedPassword::fromPlain(new PlainPassword('password123')),
            null,
            [],
            $delegationId,
            $originalIdentityId,
        );

        $repository = $this->app->make(IdentityRepositoryInterface::class);
        $repository->save($delegatedIdentity);

        $this->assertDatabaseHas('identities', [
            'id' => (string) $delegatedIdentityId,
            'delegation_identifier' => (string) $delegationId,
            'original_identity_identifier' => (string) $originalIdentityId,
        ]);

        $result = $repository->findByDelegation($delegationId);
        $this->assertNotNull($result);
        $this->assertTrue($result->isDelegatedIdentity());
        $this->assertSame((string) $originalIdentityId, (string) $result->originalIdentityIdentifier());
    }
}
