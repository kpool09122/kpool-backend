<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Command\UnlikeVideoLink;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkIdentifier;

interface UnlikeVideoLinkInputPort
{
    public function videoLinkIdentifier(): VideoLinkIdentifier;

    public function principalIdentifier(): PrincipalIdentifier;
}
