<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Domain\ValueObject;

enum Action: string
{
    case CREATE = 'create';
    case EDIT = 'edit';
    case SUBMIT = 'submit';
    case APPROVE = 'approve';
    case TRANSLATE = 'translate';
}
