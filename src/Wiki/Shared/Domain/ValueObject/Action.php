<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Domain\ValueObject;

enum Action: string
{
    case CREATE = 'create';
    case READ = 'read';
    case EDIT = 'edit';
    case SUBMIT = 'submit';
    case WITHDRAW = 'withdraw';
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
    case OFFICIAL_CERTIFICATION_REQUEST = 'official_certification_request';
    case OFFICIAL_CERTIFICATION_READ = 'official_certification_read';
    case OFFICIAL_CERTIFICATION_APPROVE = 'official_certification_approve';
    case OFFICIAL_CERTIFICATION_REJECT = 'official_certification_reject';
    case PRINCIPAL_GROUP_MANAGE = 'principal-group-manage';
}
