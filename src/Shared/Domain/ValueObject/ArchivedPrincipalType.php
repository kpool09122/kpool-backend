<?php

declare(strict_types=1);

namespace Source\Shared\Domain\ValueObject;

enum ArchivedPrincipalType: string
{
    case ACCOUNT = 'account';
    case WIKI = 'wiki';
}
