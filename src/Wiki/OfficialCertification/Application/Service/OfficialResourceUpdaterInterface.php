<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\Service;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

interface OfficialResourceUpdaterInterface
{
    public function markOfficial(
        ResourceType $type,
        TranslationSetIdentifier $id,
        AccountIdentifier $owner,
    ): void;

    public function unmarkOfficial(
        ResourceType $type,
        TranslationSetIdentifier $id,
        AccountIdentifier $owner,
    ): void;
}
