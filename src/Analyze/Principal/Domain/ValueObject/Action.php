<?php

declare(strict_types=1);

namespace Source\Analyze\Principal\Domain\ValueObject;

enum Action: string
{
    case ANALYSIS_VIEW = 'analysis:view';
}
