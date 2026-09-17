<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Domain\Exception;

use DomainException;
use Throwable;

class DelegationPrincipalGroupCreationException extends DomainException
{
    public static function delegatorAccountNotFound(?Throwable $previous = null): self
    {
        return new self('Delegator account is not found.', $previous);
    }

    public function __construct(
        string $message = 'Delegation principal group cannot be created.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
