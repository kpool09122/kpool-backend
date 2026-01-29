<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Domain\Service;

use Source\Wiki\Song\Application\UseCase\Command\AutoCreateSong\GeneratedSongData;
use Source\Wiki\Song\Domain\ValueObject\AutoSongCreationPayload;

interface AutoSongCreationServiceInterface
{
    /**
     * Gemini APIを使用して曲情報を自動生成する
     *
     * @param AutoSongCreationPayload $payload
     * @return GeneratedSongData
     */
    public function generate(
        AutoSongCreationPayload $payload,
    ): GeneratedSongData;
}
