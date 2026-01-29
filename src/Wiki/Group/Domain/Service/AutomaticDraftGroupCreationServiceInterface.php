<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Domain\Service;

use Source\Wiki\Group\Application\UseCase\Command\AutomaticCreateDraftGroup\GeneratedGroupData;
use Source\Wiki\Group\Domain\ValueObject\AutomaticDraftGroupCreationPayload;

interface AutomaticDraftGroupCreationServiceInterface
{
    /**
     * Gemini APIを使用してグループ情報を自動生成する
     *
     * @param AutomaticDraftGroupCreationPayload $payload
     * @return GeneratedGroupData
     */
    public function generate(
        AutomaticDraftGroupCreationPayload $payload,
    ): GeneratedGroupData;
}
