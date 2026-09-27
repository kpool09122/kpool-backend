<?php

namespace ImportedNameFixture;

use \DateTimeImmutable;
use \Source\Identity\Domain\Exception\{SocialLinkingSessionInvalidException as LinkingException};

/** @param list<\DateTimeImmutable> $dates */
function bad(array $dates): \DateTimeImmutable
{
    /** @var \DateTimeImmutable|null $date */
    $date = new \DateTimeImmutable();
    return $date;
}

/** @throws LinkingException */
function good(DateTimeImmutable $date): DateTimeImmutable
{
    // A string or comment containing \DateTimeImmutable is not a class reference.
    $example = '\\DateTimeImmutable';
    return $date;
}
