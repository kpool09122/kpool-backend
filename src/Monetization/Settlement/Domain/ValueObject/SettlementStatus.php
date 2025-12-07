<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Domain\ValueObject;

enum SettlementStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case PAID = 'paid';
    case FAILED = 'failed';
}
