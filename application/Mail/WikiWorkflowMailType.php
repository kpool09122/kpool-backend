<?php

declare(strict_types=1);

namespace Application\Mail;

enum WikiWorkflowMailType: string
{
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case PUBLISHED = 'published';
}
