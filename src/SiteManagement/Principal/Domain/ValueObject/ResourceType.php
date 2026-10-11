<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Domain\ValueObject;

enum ResourceType: string
{
    case PRINCIPAL_GROUP = 'principal_group';
    case ANNOUNCEMENT = 'announcement';
    case CONTACT = 'contact';
}
