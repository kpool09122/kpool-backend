<?php

namespace Businesses\Shared\ValueObject;

enum Translation: string
{
    case JAPANESE = 'ja';
    case KOREAN = 'ko';
    case TAIWANESE = 'zh-tw';
    case ENGLISH = 'en';
}
