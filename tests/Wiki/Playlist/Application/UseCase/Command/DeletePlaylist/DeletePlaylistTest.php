<?php

declare(strict_types=1);

namespace Tests\Wiki\Playlist\Application\UseCase\Command\DeletePlaylist;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Playlist\Application\UseCase\Command\DeletePlaylist\DeletePlaylist;
use Source\Wiki\Playlist\Application\UseCase\Command\DeletePlaylist\DeletePlaylistInput;
use Source\Wiki\Playlist\Domain\Exception\PlaylistNotFoundException;
use Source\Wiki\Playlist\Domain\Repository\PlaylistRepositoryInterface;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Tests\Helper\PlaylistTestHelper;
use Tests\Helper\StrTestHelper;

class DeletePlaylistTest extends MockeryTestCase
{
    /** @return array<string, array{bool, bool, bool}> */
    public static function scenarios(): array
    {
        return [
            'owned' => [true, true, true],
            'foreign or missing' => [true, true, false],
            'permission denied' => [true, false, true],
            'principal missing' => [false, false, true],
        ];
    }

    #[DataProvider('scenarios')]
    public function testDeletesOnlyAnAuthorizedOwnersPlaylist(bool $exists, bool $allowed, bool $owned): void
    {
        $owner = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $playlist = PlaylistTestHelper::playlist(owner: $owner);
        $principal = $exists ? new Principal($owner, new IdentityIdentifier(StrTestHelper::generateUuid()), new AccountIdentifier(StrTestHelper::generateUuid())) : null;
        /** @var PrincipalRepositoryInterface&Mockery\MockInterface $principalRepository */
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')->once()->with($owner)->andReturn($principal);
        /** @var PolicyEvaluatorInterface&Mockery\MockInterface $policyEvaluator */
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        if ($exists) {
            $policyEvaluator->shouldReceive('evaluate')->once()->with($principal, Action::DELETE_PLAYLIST, Mockery::on(static fn (Resource $resource): bool => $resource->type() === ResourceType::PLAYLIST))->andReturn($allowed);
        } else {
            $policyEvaluator->shouldNotReceive('evaluate');
        }
        /** @var PlaylistRepositoryInterface&Mockery\MockInterface $playlistRepository */
        $playlistRepository = Mockery::mock(PlaylistRepositoryInterface::class);
        if ($exists && $allowed) {
            $playlistRepository->shouldReceive('findByIdAndPrincipal')->once()->with($playlist->playlistIdentifier(), $owner)->andReturn($owned ? $playlist : null);
        } else {
            $playlistRepository->shouldNotReceive('findByIdAndPrincipal');
        }
        if ($exists && $allowed && $owned) {
            $playlistRepository->shouldReceive('deleteByIdAndPrincipal')->once()->with($playlist->playlistIdentifier(), $owner);
        } else {
            $playlistRepository->shouldNotReceive('deleteByIdAndPrincipal');
            $this->expectException(! $exists ? PrincipalNotFoundException::class : (! $allowed ? DisallowedException::class : PlaylistNotFoundException::class));
        }
        (new DeletePlaylist($principalRepository, $policyEvaluator, $playlistRepository))->process(new DeletePlaylistInput($owner, $playlist->playlistIdentifier()));
    }
}
