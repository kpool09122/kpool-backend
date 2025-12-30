<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\SubmitSong;

use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Song\Application\Exception\SongNotFoundException;
use Source\Wiki\Song\Domain\Entity\DraftSong;

interface SubmitSongInterface
{
    /**
     * @param SubmitSongInputPort $input
     * @return DraftSong
     * @throws SongNotFoundException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(SubmitSongInputPort $input): DraftSong;
}
