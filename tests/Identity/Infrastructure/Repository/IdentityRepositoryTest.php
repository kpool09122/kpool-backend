<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\Group;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\ValueObject\IdentityName;
use Source\Identity\Domain\ValueObject\SocialConnection;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Source\Identity\Infrastructure\Repository\IdentityRepository;
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
            new IdentityName('new-identity'),
            $email,
            Language::JAPANESE,
            new ImagePath('/images/profile.jpg'),
            $emailVerifiedAt,
            [new SocialConnection(SocialProvider::GOOGLE, 'google-new-identity')]
        );

        $repository = $this->app->make(IdentityRepositoryInterface::class);
        $repository->save($identity);

        $this->assertDatabaseHas('identities', [
            'id' => (string) $identityIdentifier,
            'identity_name' => 'new-identity',
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
            'identity_name' => 'original-identity',
        ]);

        $updatedIdentity = new Identity(
            $identityIdentifier,
            new IdentityName('updated-identity'),
            new Email('updated@example.com'),
            Language::KOREAN,
            null,
            new DateTimeImmutable('2024-06-01 00:00:00'),
            []
        );

        $repository = $this->app->make(IdentityRepositoryInterface::class);
        $repository->save($updatedIdentity);

        $this->assertDatabaseHas('identities', [
            'id' => (string) $identityIdentifier,
            'identity_name' => 'updated-identity',
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
            new IdentityName('unverified-identity'),
            $email,
            Language::ENGLISH,
            null,
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
            new IdentityName('multi-social-identity'),
            $email,
            Language::JAPANESE,
            null,
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
            'identity_name' => 'find-by-id-user',
        ]);

        $repository = $this->app->make(IdentityRepositoryInterface::class);
        $result = $repository->findById($identityIdentifier);

        $this->assertNotNull($result);
        $this->assertInstanceOf(Identity::class, $result);
        $this->assertSame((string) $identityIdentifier, (string) $result->identityIdentifier());
        $this->assertSame('findbyid@example.com', (string) $result->email());
        $this->assertSame('find-by-id-user', (string) $result->identityName());
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
     * 正常系: findByIdsで複数のIdentityが見つかること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdsReturnsMultipleIdentities(): void
    {
        $identityIdentifier1 = new IdentityIdentifier(StrTestHelper::generateUuid());
        $identityIdentifier2 = new IdentityIdentifier(StrTestHelper::generateUuid());
        $identityIdentifier3 = new IdentityIdentifier(StrTestHelper::generateUuid());

        CreateIdentity::create($identityIdentifier1, [
            'email' => 'findbyids1@example.com',
            'identity_name' => 'find-by-ids-user1',
        ]);
        CreateIdentity::create($identityIdentifier2, [
            'email' => 'findbyids2@example.com',
            'identity_name' => 'find-by-ids-user2',
        ]);
        CreateIdentity::create($identityIdentifier3, [
            'email' => 'findbyids3@example.com',
            'identity_name' => 'find-by-ids-user3',
        ]);
        CreateIdentity::createSocialConnection($identityIdentifier1, SocialProvider::GOOGLE, 'google-findbyids-1');

        $repository = $this->app->make(IdentityRepositoryInterface::class);
        $results = $repository->findByIds([$identityIdentifier1, $identityIdentifier2, $identityIdentifier3]);

        $this->assertCount(3, $results);
        $this->assertContainsOnlyInstancesOf(Identity::class, $results);

        $resultIds = array_map(
            fn (Identity $identity) => (string) $identity->identityIdentifier(),
            $results
        );
        $this->assertContains((string) $identityIdentifier1, $resultIds);
        $this->assertContains((string) $identityIdentifier2, $resultIds);
        $this->assertContains((string) $identityIdentifier3, $resultIds);
    }

    /**
     * 正常系: findByIdsで空の配列を渡した場合に空の配列を返すこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdsReturnsEmptyArrayWhenEmptyInput(): void
    {
        $repository = $this->app->make(IdentityRepositoryInterface::class);
        $results = $repository->findByIds([]);

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    /**
     * 正常系: findByIdsで存在しないIdentityは結果に含まれないこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    #[Group('useDb')]
    public function testFindByIdsReturnsOnlyExistingIdentities(): void
    {
        $existingIdentityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $nonExistingIdentityId = new IdentityIdentifier(StrTestHelper::generateUuid());

        CreateIdentity::create($existingIdentityId, [
            'email' => 'existing@example.com',
            'identity_name' => 'existing-user',
        ]);

        $repository = $this->app->make(IdentityRepositoryInterface::class);
        $results = $repository->findByIds([$existingIdentityId, $nonExistingIdentityId]);

        $this->assertCount(1, $results);
        $this->assertSame((string) $existingIdentityId, (string) $results[0]->identityIdentifier());
    }
}
