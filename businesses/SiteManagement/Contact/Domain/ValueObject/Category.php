<?php

declare(strict_types=1);

namespace Businesses\SiteManagement\Contact\Domain\ValueObject;

enum Category: int
{
    /** 要望 */
    case SUGGESTIONS = 1;

    /** バグ報告 */
    case ISSUES = 2;

    /** コンテンツ修正依頼 */
    case CORRECTION = 3;

    /** その他 */
    case OTHERS = 99;
}
