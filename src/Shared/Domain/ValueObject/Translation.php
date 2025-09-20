<?php

declare(strict_types=1);

namespace Source\Shared\Domain\ValueObject;

enum Translation: string
{
    case JAPANESE = 'ja';
    case KOREAN = 'ko';
    case TAIWANESE = 'zh-tw';
    case ENGLISH = 'en';
}
