<?php

declare(strict_types=1);

namespace Source\Identity\Domain\ValueObject;

enum AuthorizationScope: string
{
    case WikiRead = 'wiki:read';
    case DraftRead = 'wiki-drafts:read';
    case DraftWrite = 'wiki-drafts:write';
    case DraftSubmit = 'wiki-drafts:submit';
    case ImageRead = 'wiki-images:read';
    case ImageWrite = 'wiki-images:write';
}
