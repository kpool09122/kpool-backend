<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\RevokeWikiOperator;

use Source\Shared\Domain\ValueObject\Email;

interface RevokeWikiOperatorInputPort
{
    public function email(): Email;
}
