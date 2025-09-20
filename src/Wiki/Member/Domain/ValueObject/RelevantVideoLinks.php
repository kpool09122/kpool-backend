<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Domain\ValueObject;

use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Wiki\Member\Domain\Exception\ExceedMaxRelevantVideoLinksException;

class RelevantVideoLinks
{
    public const int MAX_COUNT = 10;

    /**
     * @param ExternalContentLink[] $links
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function __construct(
        private readonly array $links,
    ) {
        $this->validate($links);
    }

    /**
     * @return ExternalContentLink[]
     */
    public function links(): array
    {
        return $this->links;
    }

    /**
     * @return string[]
     */
    public function toStringArray(): array
    {
        if ($this->count() === 0) {
            return [];
        }

        return array_map(static fn ($link) => (string)$link, $this->links);
    }

    public function count(): int
    {
        return count($this->links);
    }

    /**
     * @param string[] $array
     * @return RelevantVideoLinks
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public static function formStringArray(array $array): RelevantVideoLinks
    {
        if (count($array) === 0) {
            return new RelevantVideoLinks([]);
        }

        return new RelevantVideoLinks(array_map(static fn ($link) => new ExternalContentLink($link), $array));
    }

    /**
     * @param ExternalContentLink[] $links
     * @throws ExceedMaxRelevantVideoLinksException
     */
    protected function validate(array $links): void
    {
        if (count($links) > self::MAX_COUNT) {
            throw new ExceedMaxRelevantVideoLinksException('Relevant video links cannot exceed ' . self::MAX_COUNT . ' items');
        }
    }
}
