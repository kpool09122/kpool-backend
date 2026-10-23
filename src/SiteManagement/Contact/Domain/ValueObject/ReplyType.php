<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Domain\ValueObject;

enum ReplyType: string
{
    case OPERATOR = 'operator';

    case SUBMITTER = 'submitter';
}
