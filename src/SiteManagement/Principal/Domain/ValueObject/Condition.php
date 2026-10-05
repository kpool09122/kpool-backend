<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Domain\ValueObject;

enum Condition: string
{
    case OWN_CONTACT = 'own_contact';
}
