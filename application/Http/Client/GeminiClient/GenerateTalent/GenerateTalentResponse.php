<?php

declare(strict_types=1);

namespace Application\Http\Client\GeminiClient\GenerateTalent;

use Application\Http\Client\Foundation\Json\Decoder;
use Application\Http\Client\GeminiClient\JsonContentExtractor;
use Application\Http\Client\GeminiClient\SourceReferenceExtractor;
use Psr\Http\Message\ResponseInterface;

final readonly class GenerateTalentResponse
{
    private string $contents;

    public function __construct(ResponseInterface $response)
    {
        $this->contents = $response->getBody()->getContents();
    }

    public function params(): GenerateTalentParams
    {
        /** @var array<string, mixed> $responseBody */
        $responseBody = Decoder::decode($this->contents, true);

        $content = $responseBody['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
        /** @var array<string, mixed> $data */
        $data = JsonContentExtractor::decodeObject($content);

        $sources = (new SourceReferenceExtractor())->extract($data, $responseBody);

        return GenerateTalentParams::fromArray($data, $sources);
    }
}
