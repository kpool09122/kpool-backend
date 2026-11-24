<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\ValueObject;

enum AccountSnapshotStatus: string
{
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
}
