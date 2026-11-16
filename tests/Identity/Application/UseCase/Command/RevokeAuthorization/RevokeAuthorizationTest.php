<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\RevokeAuthorization;

use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Identity\Application\Exception\AuthorizationConnectionNotFoundException;
use Source\Identity\Application\Service\AuthorizationRevocationService;
use Source\Identity\Application\Service\AuthorizationTokenLifecycleServiceInterface;
use Source\Identity\Application\UseCase\Command\RevokeAuthorization\RevokeAuthorization;
use Source\Identity\Application\UseCase\Command\RevokeAuthorization\RevokeAuthorizationInput;
use Source\Identity\Application\UseCase\Command\RevokeAuthorization\RevokeAuthorizationOutput;
use Source\Identity\Domain\Entity\AuthorizationConsent;
use Source\Identity\Domain\Event\AuthorizationConsentRevoked;
use Source\Identity\Domain\Repository\AuthorizationConsentRepositoryInterface;
use Source\Identity\Domain\ValueObject\AccountPrincipalIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationConsentIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\OAuthClientIdentifier;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;

class RevokeAuthorizationTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @return array<string, array{string}> */
    public static function outcomes(): array
    {
        return ['owned' => ['owned'], 'foreign' => ['foreign'], 'unknown' => ['unknown']];
    }

    #[DataProvider('outcomes')]
    public function testOwnershipIsCheckedBeforeAnyMutation(string $outcome): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $id = new AuthorizationConsentIdentifier(StrTestHelper::generateUuid());
        $consent = new AuthorizationConsent(
            $id,
            $outcome === 'foreign' ? new IdentityIdentifier(StrTestHelper::generateUuid()) : $identity,
            new OAuthClientIdentifier(StrTestHelper::generateUuid()),
            [AuthorizationScope::WikiRead],
            new AccountIdentifier(StrTestHelper::generateUuid()),
            new AccountPrincipalIdentifier(StrTestHelper::generateUuid()),
            'https://mcp.example.test/mcp',
            new DateTimeImmutable(),
        );
        /** @var AuthorizationConsentRepositoryInterface&MockInterface $authorizationConsentRepository */
        $authorizationConsentRepository = Mockery::mock(AuthorizationConsentRepositoryInterface::class);
        $authorizationConsentRepository->shouldReceive('find')->once()->with($id)->andReturn($outcome === 'unknown' ? null : $consent);
        /** @var AuthorizationTokenLifecycleServiceInterface&MockInterface $authorizationTokenLifecycleService */
        $authorizationTokenLifecycleService = Mockery::mock(AuthorizationTokenLifecycleServiceInterface::class);
        /** @var EventDispatcherInterface&MockInterface $eventDispatcher */
        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
        if ($outcome === 'owned') {
            $authorizationConsentRepository->shouldReceive('save')->once()->with($consent);
            $authorizationTokenLifecycleService->shouldReceive('revoke')->once()->with($id);
            $eventDispatcher->shouldReceive('dispatch')->once()->with(Mockery::on(static fn (object $event): bool => $event instanceof AuthorizationConsentRevoked
                && $event->identityIdentifier === $identity && $event->consentIdentifier === $id));
        } else {
            $authorizationConsentRepository->shouldNotReceive('save');
            $authorizationTokenLifecycleService->shouldNotReceive('revoke');
            $eventDispatcher->shouldNotReceive('dispatch');
            $this->expectException(AuthorizationConnectionNotFoundException::class);
        }
        $authorizationRevocationService = new AuthorizationRevocationService($authorizationConsentRepository, $authorizationTokenLifecycleService, $eventDispatcher);
        (new RevokeAuthorization($authorizationConsentRepository, $authorizationRevocationService))
            ->process(new RevokeAuthorizationInput($identity, $id), new RevokeAuthorizationOutput());
        self::assertNotNull($consent->revokedAt());
    }
}
