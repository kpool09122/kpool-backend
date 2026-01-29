<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Domain\Service;

use Source\Wiki\Talent\Application\UseCase\Command\AutoCreateTalent\GeneratedTalentData;
use Source\Wiki\Talent\Domain\ValueObject\AutoTalentCreationPayload;

interface AutoTalentCreationServiceInterface
{
    /**
     * Gemini APIを使用してタレント情報を自動生成する
     *
     * @param AutoTalentCreationPayload $payload
     * @return GeneratedTalentData
     */
    public function generate(
        AutoTalentCreationPayload $payload,
    ): GeneratedTalentData;
}
