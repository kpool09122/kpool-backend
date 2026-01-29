<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Domain\Service;

use Source\Wiki\Agency\Application\UseCase\Command\AutomaticCreateDraftAgency\GeneratedAgencyData;
use Source\Wiki\Agency\Domain\ValueObject\AutomaticDraftAgencyCreationPayload;

interface AutomaticDraftAgencyCreationServiceInterface
{
    /**
     * Gemini APIを使用して事務所情報を自動生成する
     *
     * @param AutomaticDraftAgencyCreationPayload $payload
     * @return GeneratedAgencyData
     */
    public function generate(
        AutomaticDraftAgencyCreationPayload $payload,
    ): GeneratedAgencyData;
}
