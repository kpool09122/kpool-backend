<?php

declare(strict_types=1);

namespace Application\Http\Client\GeminiClient\GenerateGroup;

use Application\Http\Client\Foundation\Json\Decoder;
use Psr\Http\Message\ResponseInterface;
use Source\Wiki\Shared\Application\DTO\SourceReference;

final readonly class GenerateGroupResponse
{
    private string $contents;

    public function __construct(ResponseInterface $response)
    {
        $this->contents = $response->getBody()->getContents();
    }

    public function params(): GenerateGroupParams
    {
        /** @var array<string, mixed> $responseBody */
        $responseBody = Decoder::decode($this->contents, true);

        $content = $responseBody['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
        /** @var array<string, mixed> $data */
        $data = Decoder::decode($content, true);

        $sources = $this->extractSources($responseBody);

        return GenerateGroupParams::fromArray($data, $sources);
    }

    /**
     * @param array<string, mixed> $responseBody
     * @return SourceReference[]
     */
    private function extractSources(array $responseBody): array
    {
        $groundingMetadata = $responseBody['candidates'][0]['groundingMetadata'] ?? [];
        $sources = [];
        $seenUris = [];

        foreach ($groundingMetadata['groundingChunks'] ?? [] as $chunk) {
            $uri = $chunk['web']['uri'] ?? null;
            $title = $chunk['web']['title'] ?? null;

            if ($uri !== null && ! isset($seenUris[$uri])) {
                $sources[] = new SourceReference(
                    uri: $uri,
                    title: $title ?? '',
                );
                $seenUris[$uri] = true;
            }
        }

        return $sources;
    }
}
