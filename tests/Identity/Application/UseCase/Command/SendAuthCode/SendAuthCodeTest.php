<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\SendAuthCode;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Identity\Application\Service\AuthCodeSessionStorageServiceInterface;
use Source\Identity\Application\UseCase\Command\SendAuthCode\SendAuthCode;
use Source\Identity\Application\UseCase\Command\SendAuthCode\SendAuthCodeInput;
use Source\Identity\Application\UseCase\Command\SendAuthCode\SendAuthCodeInterface;
use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Repository\IdentityRepositoryInterface;
use Source\Identity\Domain\Service\AuthCodeServiceInterface;
use Source\Identity\Domain\ValueObject\AuthCode;
use Source\Identity\Domain\ValueObject\AuthCodeSession;
use Source\Identity\Domain\ValueObject\IdentityName;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Shared\Domain\ValueObject\Language;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SendAuthCodeTest extends TestCase
{
    /**
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $this->app->instance(AuthCodeServiceInterface::class, Mockery::mock(AuthCodeServiceInterface::class));
        $this->app->instance(IdentityRepositoryInterface::class, Mockery::mock(IdentityRepositoryInterface::class));
        $this->app->instance(AuthCodeSessionStorageServiceInterface::class, Mockery::mock(AuthCodeSessionStorageServiceInterface::class));

        $this->assertInstanceOf(SendAuthCode::class, $this->app->make(SendAuthCodeInterface::class));
    }

    /**
     * @throws BindingResolutionException
     */
    public function testProcess(): void
    {
        $email = new Email('user@example.com');
        $language = Language::KOREAN;
        $authCode = new AuthCode('123456');
        $before = new DateTimeImmutable('now');
        $savedSession = null;

        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findByEmail')->once()->with($email)->andReturnNull();

        $storageService = Mockery::mock(AuthCodeSessionStorageServiceInterface::class);
        $storageService->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (AuthCodeSession $session) use ($email, $authCode, $before, &$savedSession): bool {
                $this->assertSame($email, $session->email());
                $this->assertSame($authCode, $session->authCode());
                $this->assertGreaterThanOrEqual($before->getTimestamp(), $session->generatedAt()->getTimestamp());
                $this->assertLessThanOrEqual((new DateTimeImmutable('now'))->getTimestamp(), $session->generatedAt()->getTimestamp());
                $this->assertSame($session->generatedAt()->modify('+15 minutes')->getTimestamp(), $session->expiresAt()->getTimestamp());
                $this->assertSame($session->generatedAt()->modify('+1 minute')->getTimestamp(), $session->retryableAt()->getTimestamp());
                $this->assertNull($session->verifiedAt());
                $savedSession = $session;

                return true;
            }));

        $authCodeService = Mockery::mock(AuthCodeServiceInterface::class);
        $authCodeService->shouldReceive('generateCode')->once()->with($email)->andReturn($authCode);
        $authCodeService->shouldReceive('send')
            ->once()
            ->with($email, $language, Mockery::on(function (AuthCodeSession $session) use (&$savedSession): bool {
                return $session === $savedSession;
            }));

        $this->app->instance(AuthCodeServiceInterface::class, $authCodeService);
        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
        $this->app->instance(AuthCodeSessionStorageServiceInterface::class, $storageService);

        $this->app->make(SendAuthCodeInterface::class)->process(new SendAuthCodeInput($email, $language));
    }

    /**
     * @throws BindingResolutionException
     */
    public function testWhenEmailAlreadyExists(): void
    {
        $email = new Email('user@example.com');
        $language = Language::JAPANESE;
        $identity = new Identity(
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            new IdentityName('test-user'),
            $email,
            $language,
            new ImagePath('/resources/path/test.png'),
            new DateTimeImmutable(),
        );

        $identityRepository = Mockery::mock(IdentityRepositoryInterface::class);
        $identityRepository->shouldReceive('findByEmail')->once()->with($email)->andReturn($identity);

        $authCodeService = Mockery::mock(AuthCodeServiceInterface::class);
        $authCodeService->shouldReceive('notifyConflict')->once()->with($email, $language);
        $authCodeService->shouldNotReceive('generateCode');
        $authCodeService->shouldNotReceive('send');

        $storageService = Mockery::mock(AuthCodeSessionStorageServiceInterface::class);
        $storageService->shouldNotReceive('save');

        $this->app->instance(AuthCodeServiceInterface::class, $authCodeService);
        $this->app->instance(IdentityRepositoryInterface::class, $identityRepository);
        $this->app->instance(AuthCodeSessionStorageServiceInterface::class, $storageService);

        $this->app->make(SendAuthCodeInterface::class)->process(new SendAuthCodeInput($email, $language));
    }
}
