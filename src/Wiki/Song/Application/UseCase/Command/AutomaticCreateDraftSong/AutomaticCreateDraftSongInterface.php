<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\AutomaticCreateDraftSong;

use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Song\Domain\Entity\DraftSong;

interface AutomaticCreateDraftSongInterface
{
    /**
     * @param AutomaticCreateDraftSongInputPort $input
     * @return DraftSong
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(AutomaticCreateDraftSongInputPort $input): DraftSong;
}
