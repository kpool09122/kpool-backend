<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Domain\ValueObject;

enum Action: string
{
    case PRINCIPAL_GROUP_MANAGE = 'principal-group:manage';
    case ANNOUNCEMENT_VIEW_DRAFT = 'announcement:view-draft';
    case ANNOUNCEMENT_CREATE = 'announcement:create';
    case ANNOUNCEMENT_UPDATE = 'announcement:update';
    case ANNOUNCEMENT_PUBLISH = 'announcement:publish';
    case ANNOUNCEMENT_TRANSLATE = 'announcement:translate';
    case ANNOUNCEMENT_DELETE = 'announcement:delete';
    case CONTACT_VIEW = 'contact:view';
    case CONTACT_REPLY = 'contact:reply';
}
