<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\Service;

use Source\Wiki\Wiki\Application\UseCase\Command\AutoCreateWiki\GeneratedWikiData;
use Source\Wiki\Wiki\Domain\ValueObject\AutoWikiCreationPayload;

interface AutoWikiCreationServiceInterface
{
    /**
     * Gemini APIを使用してWiki情報を自動生成する
     *
     * @param AutoWikiCreationPayload $payload
     * @return GeneratedWikiData
     */
    public function generate(
        AutoWikiCreationPayload $payload,
    ): GeneratedWikiData;
}
