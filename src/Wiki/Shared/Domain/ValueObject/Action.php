<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Domain\ValueObject;

enum Action: string
{
    case CREATE = 'create';
    case EDIT = 'edit';
    case SUBMIT = 'submit';
    case APPROVE = 'approve';
    case REJECT = 'reject';
    case TRANSLATE = 'translate';
    case PUBLISH = 'publish';
    case ROLLBACK = 'rollback';
    case MERGE = 'merge';
    case AUTOMATIC_CREATE = 'automatic_create';
    case SAVE_VIDEO_LINKS = 'save_video_links';
    case DELETE = 'delete';
    case HIDE = 'hide';
    case UNHIDE = 'unhide';
}
