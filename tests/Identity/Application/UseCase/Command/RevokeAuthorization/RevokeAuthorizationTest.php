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
use Source\Identity\Domain\Entity\AuthorizationConnection;
use Source\Identity\Domain\Event\AuthorizationConnectionRevoked;
use Source\Identity\Domain\Repository\AuthorizationConnectionRepositoryInterface;
use Source\Identity\Domain\ValueObject\AccountPrincipalIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationConnectionIdentifier;
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
        $id = new AuthorizationConnectionIdentifier(StrTestHelper::generateUuid());
        $connection = new AuthorizationConnection(
            $id,
            $outcome === 'foreign' ? new IdentityIdentifier(StrTestHelper::generateUuid()) : $identity,
            new OAuthClientIdentifier(StrTestHelper::generateUuid()),
            [AuthorizationScope::WikiRead],
            new AccountIdentifier(StrTestHelper::generateUuid()),
            new AccountPrincipalIdentifier(StrTestHelper::generateUuid()),
            'https://mcp.example.test/mcp',
            new DateTimeImmutable(),
        );
        /** @var AuthorizationConnectionRepositoryInterface&MockInterface $authorizationConnectionRepository */
        $authorizationConnectionRepository = Mockery::mock(AuthorizationConnectionRepositoryInterface::class);
        $authorizationConnectionRepository->shouldReceive('find')->once()->with($id)->andReturn($outcome === 'unknown' ? null : $connection);
        /** @var AuthorizationTokenLifecycleServiceInterface&MockInterface $authorizationTokenLifecycleService */
        $authorizationTokenLifecycleService = Mockery::mock(AuthorizationTokenLifecycleServiceInterface::class);
        /** @var EventDispatcherInterface&MockInterface $eventDispatcher */
        $eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
        if ($outcome === 'owned') {
            $authorizationConnectionRepository->shouldReceive('save')->once()->with($connection);
            $authorizationTokenLifecycleService->shouldReceive('revoke')->once()->with($id);
            $eventDispatcher->shouldReceive('dispatch')->once()->with(Mockery::on(static fn (object $event): bool => $event instanceof AuthorizationConnectionRevoked
                && $event->identityIdentifier === $identity && $event->connectionIdentifier === $id));
        } else {
            $authorizationConnectionRepository->shouldNotReceive('save');
            $authorizationTokenLifecycleService->shouldNotReceive('revoke');
            $eventDispatcher->shouldNotReceive('dispatch');
            $this->expectException(AuthorizationConnectionNotFoundException::class);
        }
        $authorizationRevocationService = new AuthorizationRevocationService($authorizationConnectionRepository, $authorizationTokenLifecycleService, $eventDispatcher);
        (new RevokeAuthorization($authorizationConnectionRepository, $authorizationRevocationService))
            ->process(new RevokeAuthorizationInput($identity, $id), new RevokeAuthorizationOutput());
        self::assertNotNull($connection->revokedAt());
    }
}
