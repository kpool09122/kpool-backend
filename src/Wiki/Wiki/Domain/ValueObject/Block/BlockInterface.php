<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Block;

use Source\Wiki\Wiki\Domain\ValueObject\Section\SectionContentInterface;

interface BlockInterface extends SectionContentInterface
{
    public function blockType(): BlockType;
}
