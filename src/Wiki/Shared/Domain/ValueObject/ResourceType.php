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

    public function slugPrefix(): string
    {
        return match ($this) {
            self::AGENCY => 'ag',
            self::GROUP => 'gr',
            self::SONG => 'sg',
            self::TALENT => 'tl',
            self::IMAGE => throw new InvalidArgumentException('IMAGE resource type does not support wiki slug prefixes.'),
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
