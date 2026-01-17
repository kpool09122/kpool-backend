<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Domain\ValueObject;

enum ConnectAccountStatus: string
{
    case PENDING = 'pending';
    case RESTRICTED = 'restricted';
    case ENABLED = 'enabled';

    public function canReceivePayouts(): bool
    {
        return $this === self::ENABLED;
    }
}
