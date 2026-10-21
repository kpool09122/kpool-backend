<?php

declare(strict_types=1);

namespace Source\Shared\Infrastructure\Service;

use DOMDocument;
use DOMElement;
use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Source\Shared\Application\Service\SvgImageRasterizerInterface;

class SvgImageRasterizer implements SvgImageRasterizerInterface
{
    public function rasterizeToPng(string $svg, int $maxDimension): string | false
    {
        if ($svg === '') {
            return false;
        }

        $document = new DOMDocument();
        if (! @$document->loadXML($svg, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING)) {
            return false;
        }

        $root = $document->documentElement;
        if ($root === null || $root->localName !== 'svg' || $root->namespaceURI !== 'http://www.w3.org/2000/svg'
            || $document->doctype?->internalSubset !== null) {
            return false;
        }

        [$width, $height] = $this->dimensions($root, $maxDimension);
        $svg = $document->saveXML($root);
        if ($svg === false) {
            return false;
        }

        // 標準入力で渡してbase URIを持たせず、外部ファイルの参照を許可しない。
        try {
            $result = Process::input($svg)->timeout(5)->run([
                'rsvg-convert', '--format=png', '--keep-aspect-ratio',
                '--width=' . $width, '--height=' . $height,
            ]);
        } catch (ProcessTimedOutException) {
            return false;
        }

        if ($result->exitCode() === 127) {
            throw new RuntimeException('SVG renderer is not available.');
        }

        return $result->successful() ? $result->output() : false;
    }

    /** @return array{int, int} */
    private function dimensions(DOMElement $root, int $maxDimension): array
    {
        $viewBox = preg_split('/[\s,]+/', trim($root->getAttribute('viewBox')));
        $viewWidth = $viewBox !== false && count($viewBox) === 4 ? (float) $viewBox[2] : 0.0;
        $viewHeight = $viewBox !== false && count($viewBox) === 4 ? (float) $viewBox[3] : 0.0;
        $width = $this->lengthInPixels($root->getAttribute('width'));
        $height = $this->lengthInPixels($root->getAttribute('height'));
        if ($viewWidth > 0 && $viewHeight > 0) {
            $width ??= $height !== null ? $height * $viewWidth / $viewHeight : $viewWidth;
            $height ??= $width * $viewHeight / $viewWidth;
        }

        $width ??= $maxDimension;
        $height ??= $maxDimension;
        $scale = min(1.0, $maxDimension / max($width, $height));

        return [max(1, (int) floor($width * $scale)), max(1, (int) floor($height * $scale))];
    }

    private function lengthInPixels(string $length): ?float
    {
        if (! preg_match('/^\s*(\d+(?:\.\d*)?|\.\d+)(?:[eE]([+-]?\d+))?(px|in|cm|mm|pt|pc|Q)?\s*$/', $length, $matches)) {
            return null;
        }

        $factor = match ($matches[3] ?? '') {
            'in' => 96.0,
            'cm' => 96.0 / 2.54,
            'mm' => 96.0 / 25.4,
            'pt' => 96.0 / 72.0,
            'pc' => 16.0,
            'Q' => 96.0 / 101.6,
            default => 1.0,
        };
        $pixels = (float) $matches[1] * (10 ** (int) ($matches[2] ?? 0)) * $factor;

        return is_finite($pixels) && $pixels > 0 ? $pixels : null;
    }
}
