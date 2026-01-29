<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\AutoCreateSong;

use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Song\Domain\Entity\DraftSong;

interface AutoCreateSongInterface
{
    /**
     * @param AutoCreateSongInputPort $input
     * @return DraftSong
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(AutoCreateSongInputPort $input): DraftSong;
}
