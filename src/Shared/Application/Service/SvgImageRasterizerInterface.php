<?php

declare(strict_types=1);

namespace Source\Shared\Application\Service;

interface SvgImageRasterizerInterface
{
    public function rasterizeToPng(string $svg, int $maxDimension): string | false;
}
