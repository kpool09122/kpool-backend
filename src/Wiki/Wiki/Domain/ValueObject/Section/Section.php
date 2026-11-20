<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Section;

use InvalidArgumentException;

final readonly class Section implements SectionContentInterface
{
    public const int MAX_DEPTH = 3;

    public function __construct(
        private string $title,
        private int $displayOrder,
        private SectionContentCollection $contents,
        private int $depth = 1,
        private ?string $key = null,
    ) {
        $this->validateDepth($depth);
    }

    private function validateDepth(int $depth): void
    {
        if ($depth > self::MAX_DEPTH) {
            throw new InvalidArgumentException(
                sprintf('Section nesting depth cannot exceed %d levels.', self::MAX_DEPTH)
            );
        }
    }

    public function title(): string
    {
        return $this->title;
    }

    public function withKey(string $key): self
    {
        return new self(
            title: $this->title,
            displayOrder: $this->displayOrder,
            contents: $this->contents,
            depth: $this->depth,
            key: $key,
        );
    }

    public function key(): ?string
    {
        return $this->key;
    }

    public function displayOrder(): int
    {
        return $this->displayOrder;
    }

    public function withContents(SectionContentCollection $contents): self
    {
        return new self($this->title, $this->displayOrder, $contents, $this->depth, $this->key);
    }

    public function contents(): SectionContentCollection
    {
        return $this->contents;
    }

    public function depth(): int
    {
        return $this->depth;
    }
}
