<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Domain\Factory;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Domain\Entity\VideoLinkWatch;

interface VideoLinkWatchFactoryInterface
{
    public function create(VideoLinkIdentifier $videoLinkIdentifier, AccountIdentifier $ownerAccountIdentifier, PrincipalIdentifier $principalIdentifier): VideoLinkWatch;
}
