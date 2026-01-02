<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Domain\ValueObject;

enum HistoryActionType: string
{
    case DraftStatusChange = 'draft_status_change';
    case Publish = 'publish';
    case Rollback = 'rollback';
}
