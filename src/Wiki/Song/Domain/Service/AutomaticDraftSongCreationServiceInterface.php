<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Domain\Service;

use Source\Wiki\Song\Application\UseCase\Command\AutomaticCreateDraftSong\GeneratedSongData;
use Source\Wiki\Song\Domain\ValueObject\AutomaticDraftSongCreationPayload;

interface AutomaticDraftSongCreationServiceInterface
{
    /**
     * Gemini APIを使用して曲情報を自動生成する
     *
     * @param AutomaticDraftSongCreationPayload $payload
     * @return GeneratedSongData
     */
    public function generate(
        AutomaticDraftSongCreationPayload $payload,
    ): GeneratedSongData;
}
