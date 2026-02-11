<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Section;

use InvalidArgumentException;
use Source\Wiki\Wiki\Domain\ValueObject\Block\BlockInterface;

final readonly class SectionContentCollection
{
    /**
     * @param array<SectionContentInterface> $contents
     */
    public function __construct(
        private array $contents = [],
        private bool $allowBlocks = true,
    ) {
        $this->validateContents($contents);
    }

    /**
     * @param array<SectionContentInterface> $contents
     */
    private function validateContents(array $contents): void
    {
        foreach ($contents as $content) {
            if (! $this->allowBlocks && $content instanceof BlockInterface) {
                throw new InvalidArgumentException('Blocks are not allowed at the top level.');
            }
        }
    }

    /**
     * @return array<SectionContentInterface>
     */
    public function all(): array
    {
        return $this->contents;
    }

    /**
     * @return array<SectionContentInterface>
     */
    public function sorted(): array
    {
        $sorted = $this->contents;
        usort($sorted, static fn (SectionContentInterface $a, SectionContentInterface $b) => $a->displayOrder() <=> $b->displayOrder());

        return $sorted;
    }

    public function count(): int
    {
        return count($this->contents);
    }

    public function isEmpty(): bool
    {
        return empty($this->contents);
    }

    public function add(SectionContentInterface $content): self
    {
        return new self([...$this->contents, $content], $this->allowBlocks);
    }

    /**
     * @return array<BlockInterface>
     */
    public function blocks(): array
    {
        return array_values(array_filter(
            $this->contents,
            static fn (SectionContentInterface $content) => $content instanceof BlockInterface
        ));
    }

    /**
     * @return array<Section>
     */
    public function sections(): array
    {
        return array_values(array_filter(
            $this->contents,
            static fn (SectionContentInterface $content) => $content instanceof Section
        ));
    }
}
