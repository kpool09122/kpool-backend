<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Domain\ValueObject;

enum TransferStatus: string
{
    case PENDING = 'pending';
    case SENT = 'sent';
    case FAILED = 'failed';
}
