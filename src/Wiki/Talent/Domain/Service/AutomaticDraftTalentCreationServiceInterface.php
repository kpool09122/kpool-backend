<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Domain\Service;

use Source\Wiki\Talent\Application\UseCase\Command\AutomaticCreateDraftTalent\GeneratedTalentData;
use Source\Wiki\Talent\Domain\ValueObject\AutomaticDraftTalentCreationPayload;

interface AutomaticDraftTalentCreationServiceInterface
{
    /**
     * Gemini APIを使用してタレント情報を自動生成する
     *
     * @param AutomaticDraftTalentCreationPayload $payload
     * @return GeneratedTalentData
     */
    public function generate(
        AutomaticDraftTalentCreationPayload $payload,
    ): GeneratedTalentData;
}
