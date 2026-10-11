<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\GrantWikiOperator;

class GrantWikiOperatorOutput implements GrantWikiOperatorOutputPort
{
    /** @return array{} */
    public function toArray(): array
    {
        return [];
    }
}
