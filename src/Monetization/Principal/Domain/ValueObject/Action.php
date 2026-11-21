<?php

declare(strict_types=1);

namespace Source\Monetization\Principal\Domain\ValueObject;

enum Action: string
{
    case PRINCIPAL_GROUP_READ = 'principal_group_read';
    case PRINCIPAL_GROUP_MANAGE = 'principal_group_manage';
}
