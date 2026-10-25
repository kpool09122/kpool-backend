<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Infrastructure\Factory;

use DateTimeImmutable;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Wiki\Playlist\Domain\Entity\Playlist;
use Source\Wiki\Playlist\Domain\Factory\PlaylistFactoryInterface;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistIdentifier;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistName;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class PlaylistFactory implements PlaylistFactoryInterface
{
    public function __construct(private UuidGeneratorInterface $uuidGenerator)
    {
    }

    public function create(PrincipalIdentifier $principalIdentifier, PlaylistName $name): Playlist
    {
        $at = new DateTimeImmutable();

        return new Playlist(new PlaylistIdentifier($this->uuidGenerator->generate()), $principalIdentifier, $name, $at, $at);
    }
}
