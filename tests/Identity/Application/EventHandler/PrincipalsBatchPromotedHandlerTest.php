<?php

declare(strict_types=1);

namespace Tests\Identity\Application\EventHandler;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Identity\Application\EventHandler\PrincipalsBatchPromotedHandler;
use Source\Identity\Application\Service\CollaboratorNotificationServiceInterface;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\ValueObject\HashedPassword;
use Source\Identity\Domain\ValueObject\PlainPassword;
use Source\Identity\Domain\ValueObject\UserName;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Principal\Domain\Event\PrincipalsBatchPromoted;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PrincipalsBatchPromotedHandlerTest extends TestCase
{
    /**
     * 正しくDIが動作していること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $notificationService = Mockery::mock(CollaboratorNotificationServiceInterface::class);

        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
        $this->app->instance(CollaboratorNotificationServiceInterface::class, $notificationService);

        $handler = $this->app->make(PrincipalsBatchPromotedHandler::class);

        $this->assertInstanceOf(PrincipalsBatchPromotedHandler::class, $handler);
    }

    /**
     * 正常系: 複数のidentityに対して昇格通知メールが送信されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testHandleSendsPromotionNotificationToMultipleIdentities(): void
    {
        $identityId1 = new IdentityIdentifier(StrTestHelper::generateUuid());
        $identityId2 = new IdentityIdentifier(StrTestHelper::generateUuid());

        $event = new PrincipalsBatchPromoted(
            [$identityId1, $identityId2],
        );

        $identity1 = $this->createIdentity($identityId1, 'user1@example.com', Language::JAPANESE);
        $identity2 = $this->createIdentity($identityId2, 'user2@example.com', Language::ENGLISH);

        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findByIds')
            ->with([$identityId1, $identityId2])
            ->once()
            ->andReturn([$identity1, $identity2]);

        $notificationService = Mockery::mock(CollaboratorNotificationServiceInterface::class);
        $notificationService->shouldReceive('sendPromotionNotification')
            ->with($identity1->email(), Language::JAPANESE)
            ->once();
        $notificationService->shouldReceive('sendPromotionNotification')
            ->with($identity2->email(), Language::ENGLISH)
            ->once();

        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
        $this->app->instance(CollaboratorNotificationServiceInterface::class, $notificationService);

        $handler = $this->app->make(PrincipalsBatchPromotedHandler::class);

        $handler->handle($event);
    }

    /**
     * 正常系: 空のリストの場合は何もしないこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testHandleDoesNothingWhenListIsEmpty(): void
    {
        $event = new PrincipalsBatchPromoted(
            [],
        );

        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findByIds')
            ->with([])
            ->once()
            ->andReturn([]);

        $notificationService = Mockery::mock(CollaboratorNotificationServiceInterface::class);
        $notificationService->shouldNotReceive('sendPromotionNotification');

        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
        $this->app->instance(CollaboratorNotificationServiceInterface::class, $notificationService);

        $handler = $this->app->make(PrincipalsBatchPromotedHandler::class);

        $handler->handle($event);
    }

    private function createIdentity(
        IdentityIdentifier $identityIdentifier,
        string $email,
        Language $language,
    ): Identity {
        return new Identity(
            $identityIdentifier,
            new UserName('test-user'),
            new Email($email),
            $language,
            null,
            HashedPassword::fromPlain(new PlainPassword('Password1!')),
            new DateTimeImmutable(),
        );
    }
}
