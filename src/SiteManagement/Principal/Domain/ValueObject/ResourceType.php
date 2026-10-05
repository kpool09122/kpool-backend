<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Domain\ValueObject;

enum ResourceType: string
{
    case ANNOUNCEMENT = 'announcement';
    case CONTACT = 'contact';
}
