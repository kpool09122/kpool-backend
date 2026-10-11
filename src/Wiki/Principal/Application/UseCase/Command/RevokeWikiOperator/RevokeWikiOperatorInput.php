<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\RevokeWikiOperator;

use Source\Shared\Domain\ValueObject\Email;

readonly class RevokeWikiOperatorInput implements RevokeWikiOperatorInputPort
{
    public function __construct(private Email $email)
    {
    }

    public function email(): Email
    {
        return $this->email;
    }
}
