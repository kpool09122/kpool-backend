<?php

declare(strict_types=1);

namespace Application\Http\Client\GeminiClient;

use Source\Wiki\Shared\Application\DTO\SourceReference;

final readonly class SourceReferenceExtractor
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $responseBody
     * @return SourceReference[]
     */
    public function extract(array $data, array $responseBody): array
    {
        $generatedSources = $this->extractGeneratedSources($data);
        if ($generatedSources !== []) {
            return $generatedSources;
        }

        return $this->extractGroundingSources($responseBody);
    }

    /**
     * @param array<string, mixed> $data
     * @return SourceReference[]
     */
    private function extractGeneratedSources(array $data): array
    {
        if (! is_array($data['sources'] ?? null)) {
            return [];
        }

        $sources = [];
        $seenUris = [];
        foreach ($data['sources'] as $source) {
            if (! is_array($source)) {
                continue;
            }

            $uri = $source['url'] ?? null;
            if (! is_string($uri) || ! $this->isValidHttpsUrl($uri) || isset($seenUris[$uri])) {
                continue;
            }

            $sources[] = new SourceReference(
                uri: $uri,
                title: $this->generatedSourceTitle($source),
            );
            $seenUris[$uri] = true;
        }

        return $sources;
    }

    private function isValidHttpsUrl(string $uri): bool
    {
        return str_starts_with($uri, 'https://') && filter_var($uri, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * @param array<string, mixed> $source
     */
    private function generatedSourceTitle(array $source): string
    {
        $pageTitle = $source['page_title'] ?? null;
        $siteName = $source['site_name'] ?? null;
        $pageTitle = is_string($pageTitle) ? trim($pageTitle) : '';
        $siteName = is_string($siteName) ? trim($siteName) : '';

        return match (true) {
            $pageTitle !== '' && $siteName !== '' => "{$pageTitle} - {$siteName}",
            $pageTitle !== '' => $pageTitle,
            $siteName !== '' => $siteName,
            default => '',
        };
    }

    /**
     * @param array<string, mixed> $responseBody
     * @return SourceReference[]
     */
    private function extractGroundingSources(array $responseBody): array
    {
        $groundingMetadata = $responseBody['candidates'][0]['groundingMetadata'] ?? [];
        $groundingChunks = is_array($groundingMetadata) && is_array($groundingMetadata['groundingChunks'] ?? null)
            ? $groundingMetadata['groundingChunks']
            : [];
        $sources = [];
        $seenUris = [];

        foreach ($groundingChunks as $chunk) {
            if (! is_array($chunk) || ! is_array($chunk['web'] ?? null)) {
                continue;
            }

            $uri = $chunk['web']['uri'] ?? null;
            $title = $chunk['web']['title'] ?? null;

            if (is_string($uri) && $uri !== '' && ! isset($seenUris[$uri])) {
                $sources[] = new SourceReference(
                    uri: $uri,
                    title: is_string($title) ? $title : '',
                );
                $seenUris[$uri] = true;
            }
        }

        return $sources;
    }
}
