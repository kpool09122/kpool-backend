<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Command\RecordVideoLinkWatch;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;

interface RecordVideoLinkWatchInputPort
{
    public function videoLinkIdentifier(): VideoLinkIdentifier;

    public function principalIdentifier(): PrincipalIdentifier;
}
