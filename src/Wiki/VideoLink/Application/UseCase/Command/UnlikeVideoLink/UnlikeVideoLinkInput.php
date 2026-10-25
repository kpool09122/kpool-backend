<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Command\UnlikeVideoLink;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkIdentifier;

readonly class UnlikeVideoLinkInput implements UnlikeVideoLinkInputPort
{
    public function __construct(
        private VideoLinkIdentifier $videoLinkIdentifier,
        private PrincipalIdentifier $principalIdentifier,
    ) {
    }

    public function videoLinkIdentifier(): VideoLinkIdentifier
    {
        return $this->videoLinkIdentifier;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }
}
