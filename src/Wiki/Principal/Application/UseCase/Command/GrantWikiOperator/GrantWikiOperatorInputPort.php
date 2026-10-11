<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\GrantWikiOperator;

use Source\Shared\Domain\ValueObject\Email;

interface GrantWikiOperatorInputPort
{
    public function email(): Email;
}
