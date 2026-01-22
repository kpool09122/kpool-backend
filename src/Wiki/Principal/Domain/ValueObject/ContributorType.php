<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\ValueObject;

enum ContributorType: string
{
    case EDITOR = 'editor';
    case APPROVER = 'approver';
    case MERGER = 'merger';
}
