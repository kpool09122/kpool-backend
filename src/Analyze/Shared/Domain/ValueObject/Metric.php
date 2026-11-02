<?php

declare(strict_types=1);

namespace Source\Analyze\Shared\Domain\ValueObject;

enum Metric: string
{
    case APPLICATION_USERS = 'application_users';
    case REGISTERED_USERS = 'registered_users';
    case VIEWS = 'wiki_views';
    case VIEWERS = 'wiki_viewers';
    case ENGAGEMENT_SECONDS = 'wiki_engagement_seconds';
    case SUBMISSIONS = 'wiki_submissions';
    case PUBLICATIONS = 'wiki_publications';

    public function grain(): string
    {
        return match ($this) {
            self::APPLICATION_USERS, self::REGISTERED_USERS => 'application_day',
            self::VIEWS, self::VIEWERS, self::ENGAGEMENT_SECONDS, self::SUBMISSIONS, self::PUBLICATIONS => 'wiki_language_country',
        };
    }

    public function isSnapshot(): bool
    {
        return $this === self::REGISTERED_USERS;
    }
}
