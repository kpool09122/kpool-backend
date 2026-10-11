<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\RevokeWikiOperator;

class RevokeWikiOperatorOutput implements RevokeWikiOperatorOutputPort
{
    /** @return array{} */
    public function toArray(): array
    {
        return [];
    }
}
