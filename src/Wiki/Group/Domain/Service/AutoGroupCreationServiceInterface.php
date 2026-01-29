<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Domain\Service;

use Source\Wiki\Group\Application\UseCase\Command\AutoCreateGroup\GeneratedGroupData;
use Source\Wiki\Group\Domain\ValueObject\AutoGroupCreationPayload;

interface AutoGroupCreationServiceInterface
{
    /**
     * Gemini APIを使用してグループ情報を自動生成する
     *
     * @param AutoGroupCreationPayload $payload
     * @return GeneratedGroupData
     */
    public function generate(
        AutoGroupCreationPayload $payload,
    ): GeneratedGroupData;
}
