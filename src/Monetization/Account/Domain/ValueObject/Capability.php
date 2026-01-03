<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Domain\ValueObject;

enum Capability: string
{
    case PURCHASE = 'purchase';
    case SELL = 'sell';
    case RECEIVE_PAYOUT = 'receive_payout';
}
