<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Domain\ValueObject;

enum SettlementInterval: string
{
    // 月次締め・支払い
    case MONTHLY = 'monthly';
    // 2週間ごとの締め・支払い
    case BIWEEKLY = 'biweekly';
    // 残高がしきい値に達したら支払い（締め日を持たない）
    case THRESHOLD = 'threshold';
}
