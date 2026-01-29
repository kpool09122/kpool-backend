<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Domain\Service;

use Source\Wiki\Agency\Application\UseCase\Command\AutoCreateAgency\GeneratedAgencyData;
use Source\Wiki\Agency\Domain\ValueObject\AutoAgencyCreationPayload;

interface AutoAgencyCreationServiceInterface
{
    /**
     * Gemini APIを使用して事務所情報を自動生成する
     *
     * @param AutoAgencyCreationPayload $payload
     * @return GeneratedAgencyData
     */
    public function generate(
        AutoAgencyCreationPayload $payload,
    ): GeneratedAgencyData;
}
