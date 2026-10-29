<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Domain\Repository;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Domain\Entity\VideoLinkWatch;

interface VideoLinkWatchRepositoryInterface
{
    public function findByVideoLinkAndOwnerAccount(VideoLinkIdentifier $videoLinkIdentifier, AccountIdentifier $ownerAccountIdentifier): ?VideoLinkWatch;

    public function save(VideoLinkWatch $videoLinkWatch): void;
}
