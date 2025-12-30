<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\CreateSong;

use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Song\Domain\Entity\DraftSong;

interface CreateSongInterface
{
    /**
     * @param CreateSongInputPort $input
     * @return DraftSong
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(CreateSongInputPort $input): DraftSong;
}
