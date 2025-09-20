<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Domain\ValueObject;

enum Category: int
{
    /** お知らせ全般（キャンペーン、イベントの案内など） */
    case NEWS = 1;

    /** サービスの更新情報 */
    case UPDATES = 2;

    /** 技術的なお知らせ（障害情報、利用規約の改定、システムメンテナンス） */
    case MAINTENANCE = 3;
}
