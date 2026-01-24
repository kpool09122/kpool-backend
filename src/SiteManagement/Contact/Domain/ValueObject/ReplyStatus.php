<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Domain\ValueObject;

enum ReplyStatus: int
{
    /** 送信済み */
    case SENT = 1;

    /** 送信失敗 */
    case FAILED = 2;
}
