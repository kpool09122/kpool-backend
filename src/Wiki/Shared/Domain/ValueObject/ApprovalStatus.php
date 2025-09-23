<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Domain\ValueObject;

enum ApprovalStatus: string
{
    case Approved = 'approved';
    case Pending = 'pending';
    case Rejected = 'rejected';
    case UnderReview = 'under_review';
}
