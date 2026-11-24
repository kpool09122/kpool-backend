<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\ValueObject;

enum AccountSnapshotChangeRequestStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
}
