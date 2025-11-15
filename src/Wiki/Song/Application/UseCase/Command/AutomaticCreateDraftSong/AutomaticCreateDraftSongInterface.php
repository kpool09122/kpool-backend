<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\AutomaticCreateDraftSong;

use Source\Wiki\Song\Domain\Entity\DraftSong;

interface AutomaticCreateDraftSongInterface
{
    public function process(AutomaticCreateDraftSongInputPort $input): DraftSong;
}
