<?php

declare(strict_types=1);

namespace Tests\Wiki\Playlist\Application\UseCase\Command\CreatePlaylist;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Playlist\Application\UseCase\Command\CreatePlaylist\CreatePlaylist;
use Source\Wiki\Playlist\Application\UseCase\Command\CreatePlaylist\CreatePlaylistInput;
use Source\Wiki\Playlist\Application\UseCase\Command\CreatePlaylist\CreatePlaylistOutput;
use Source\Wiki\Playlist\Domain\Exception\PlaylistLimitExceededException;
use Source\Wiki\Playlist\Domain\Factory\PlaylistFactoryInterface;
use Source\Wiki\Playlist\Domain\Repository\PlaylistRepositoryInterface;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistName;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\PlaylistTestHelper;
use Tests\Helper\StrTestHelper;
use Throwable;

class CreatePlaylistTest extends MockeryTestCase
{
    /** @return array<string, array{int}> */
    public static function counts(): array
    {
        return ['zero' => [0], 'one' => [1], 'two' => [2], 'limit' => [3], 'over limit' => [4]];
    }

    #[DataProvider('counts')]
    public function testCreatesOnlyBelowLimit(int $count): void
    {
        $owner = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal($owner, new IdentityIdentifier(StrTestHelper::generateUuid()), new AccountIdentifier(StrTestHelper::generateUuid()));
        $name = new PlaylistName(' My playlist ');
        $playlist = PlaylistTestHelper::playlist(owner: $owner);
        /** @var PrincipalRepositoryInterface&Mockery\MockInterface $principalRepository */
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')->once()->with($owner)->andReturn($principal);
        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->with($principal, Action::CREATE_PLAYLIST, Mockery::on(static fn (Resource $resource): bool => $resource->type() === ResourceType::PLAYLIST))->andReturnTrue();
        /** @var PlaylistRepositoryInterface&Mockery\MockInterface $playlistRepository */
        $playlistRepository = Mockery::mock(PlaylistRepositoryInterface::class);
        $playlistRepository->shouldReceive('countByPrincipal')->once()->with($owner)->andReturn($count);
        /** @var PlaylistFactoryInterface&Mockery\MockInterface $playlistFactory */
        $playlistFactory = Mockery::mock(PlaylistFactoryInterface::class);
        if ($count >= 3) {
            $playlistFactory->shouldNotReceive('create');
            $playlistRepository->shouldNotReceive('save');
            $this->expectException(PlaylistLimitExceededException::class);
        } else {
            $playlistFactory->shouldReceive('create')->once()->with($owner, $name)->andReturn($playlist);
            $playlistRepository->shouldReceive('save')->once()->with($playlist);
        }
        $output = new CreatePlaylistOutput();
        (new CreatePlaylist($principalRepository, $policyEvaluator, $playlistRepository, $playlistFactory))->process(new CreatePlaylistInput($owner, $name), $output);
        $result = $output->toArray();
        self::assertSame([
            'playlistIdentifier' => (string) $playlist->playlistIdentifier(),
            'name' => 'My playlist',
            'videoCount' => 0,
            'createdAt' => '2026-10-26T00:00:00+00:00',
            'updatedAt' => '2026-10-26T00:00:00+00:00',
        ], $result);
    }

    /** @return array<string, array{?PrincipalStatus, bool, class-string}> */
    public static function deniedPrincipals(): array
    {
        return [
            'missing' => [null, false, PrincipalNotFoundException::class],
            'no permission' => [PrincipalStatus::ACTIVE, false, DisallowedException::class],
            'suspended' => [PrincipalStatus::SUSPENDED, false, DisallowedException::class],
            'forced stop' => [PrincipalStatus::FORCED_STOP, false, DisallowedException::class],
        ];
    }

    /** @param class-string<Throwable> $exception */
    #[DataProvider('deniedPrincipals')]
    public function testRejectsUnavailablePrincipalBeforeWriting(?PrincipalStatus $status, bool $allowed, string $exception): void
    {
        $owner = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principal = $status === null ? null : new Principal($owner, new IdentityIdentifier(StrTestHelper::generateUuid()), new AccountIdentifier(StrTestHelper::generateUuid()), status: $status);
        /** @var PrincipalRepositoryInterface&Mockery\MockInterface $principalRepository */
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')->once()->with($owner)->andReturn($principal);
        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        if ($principal !== null) {
            $policyEvaluator->shouldReceive('evaluate')->once()->andReturn($allowed);
        } else {
            $policyEvaluator->shouldNotReceive('evaluate');
        }
        /** @var PlaylistRepositoryInterface&Mockery\MockInterface $playlistRepository */
        $playlistRepository = Mockery::mock(PlaylistRepositoryInterface::class);
        $playlistRepository->shouldNotReceive('countByPrincipal');
        $playlistRepository->shouldNotReceive('save');
        /** @var PlaylistFactoryInterface&Mockery\MockInterface $playlistFactory */
        $playlistFactory = Mockery::mock(PlaylistFactoryInterface::class);
        $playlistFactory->shouldNotReceive('create');
        $this->expectException($exception);
        (new CreatePlaylist($principalRepository, $policyEvaluator, $playlistRepository, $playlistFactory))->process(new CreatePlaylistInput($owner, new PlaylistName('曲')), new CreatePlaylistOutput());
    }
}
