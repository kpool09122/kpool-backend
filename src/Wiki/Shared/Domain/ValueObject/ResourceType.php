<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Domain\ValueObject;

use InvalidArgumentException;

enum ResourceType: string
{
    case AGENCY = 'agency';
    case GROUP = 'group';
    case TALENT = 'talent';
    case SONG = 'song';
    case IMAGE = 'image';
    case PRINCIPAL_GROUP = 'principal-group';
    case PLAYLIST = 'playlist';

    public function slugPrefix(): string
    {
        return match ($this) {
            self::AGENCY => 'ag',
            self::GROUP => 'gr',
            self::SONG => 'sg',
            self::TALENT => 'tl',
            self::IMAGE, self::PRINCIPAL_GROUP, self::PLAYLIST => throw new InvalidArgumentException($this->name . ' resource type does not support wiki slug prefixes.'),
        };
    }

    public static function fromSlug(Slug $slug): self
    {
        $prefix = explode('-', (string) $slug, 2)[0];

        return match ($prefix) {
            'ag' => self::AGENCY,
            'gr' => self::GROUP,
            'sg' => self::SONG,
            'tl' => self::TALENT,
            default => throw new InvalidArgumentException('Unknown slug prefix: ' . $prefix),
        };
    }
}
