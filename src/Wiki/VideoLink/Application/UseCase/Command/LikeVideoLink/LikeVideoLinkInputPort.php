<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Command\LikeVideoLink;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkIdentifier;

interface LikeVideoLinkInputPort
{
    public function videoLinkIdentifier(): VideoLinkIdentifier;

    public function principalIdentifier(): PrincipalIdentifier;
}
