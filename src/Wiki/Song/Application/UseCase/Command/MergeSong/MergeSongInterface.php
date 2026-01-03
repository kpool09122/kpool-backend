<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\MergeSong;

use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Song\Application\Exception\SongNotFoundException;
use Source\Wiki\Song\Domain\Entity\DraftSong;

interface MergeSongInterface
{
    /**
     * @param MergeSongInputPort $input
     * @return DraftSong
     * @throws SongNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(MergeSongInputPort $input): DraftSong;
}
