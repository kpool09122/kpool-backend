<?php

declare(strict_types=1);

namespace Application\Http\Client\GeminiClient\SearchFreeMaterials;

use Application\Http\Client\GeminiClient\Exceptions\GeminiException;
use JsonException;
use Psr\Http\Message\ResponseInterface;

final readonly class SearchFreeMaterialsResponse
{
    private string $contents;

    public function __construct(ResponseInterface $response)
    {
        $this->contents = (string) $response->getBody();
    }

    /** @return list<array{siteName: string, pageUrl: string, reason: string, usageEvidence: string, usageEvidenceUrl: string}> */
    public function sites(): array
    {
        try {
            $body = json_decode($this->contents, true, 512, JSON_THROW_ON_ERROR);
            $candidate = is_array($body) ? ($body['candidates'][0] ?? null) : null;
            if (! is_array($candidate) || ($candidate['finishReason'] ?? null) !== 'STOP') {
                throw new GeminiException('Material search did not complete');
            }
            $parts = $candidate['content']['parts'] ?? null;
            if (! is_array($parts)) {
                throw new GeminiException('Material search content is missing');
            }
            $text = '';
            foreach ($parts as $part) {
                if (is_array($part) && ($part['thought'] ?? false) !== true && is_string($part['text'] ?? null)) {
                    $text .= $part['text'];
                }
            }
            // Decode as objects first so an empty object cannot masquerade as an empty list.
            $object = json_decode($text, false, 512, JSON_THROW_ON_ERROR);
            if (! is_object($object) || ! isset($object->sites) || ! is_array($object->sites)) {
                throw new GeminiException('Material search sites must be a JSON array');
            }
            $sites = json_decode($text, true, 512, JSON_THROW_ON_ERROR)['sites'];
        } catch (JsonException $e) {
            throw new GeminiException('Invalid material search JSON', 0, $e);
        }

        $groundedText = $this->groundedText($candidate);
        $results = [];
        $seenDomains = [];
        $seenNames = [];
        foreach ($sites as $site) {
            if (! is_array($site)) {
                throw new GeminiException('Invalid material search site');
            }
            foreach (['siteName', 'pageUrl', 'reason', 'usageEvidence', 'usageEvidenceUrl'] as $field) {
                if (! is_string($site[$field] ?? null) || trim($site[$field]) === '' || mb_strlen($site[$field]) > 4096) {
                    throw new GeminiException('Missing or invalid material search field: ' . $field);
                }
                $site[$field] = trim($site[$field]);
            }
            $host = $this->validateUrl($site['pageUrl']);
            $this->validateUrl($site['usageEvidenceUrl']);
            $evidence = $site['usageEvidence'];
            $grounded = true;
            foreach (['pageUrl', 'usageEvidence', 'usageEvidenceUrl'] as $field) {
                $value = $site[$field];
                $encoded = json_encode($value, JSON_THROW_ON_ERROR);
                if (! str_contains($groundedText, $value) && ! str_contains($groundedText, substr($encoded, 1, -1))) {
                    $grounded = false;
                }
            }
            if (! $grounded) {
                continue;
            }
            // Conservatively collapse subdomains (including www) to avoid multiple suggestions from one site.
            $labels = explode('.', $host);
            $domain = implode('.', array_slice($labels, -2));
            $name = mb_strtolower($site['siteName']);
            if (isset($seenDomains[$domain]) || isset($seenNames[$name])) {
                continue;
            }
            $seenDomains[$domain] = true;
            $seenNames[$name] = true;
            if (count($results) < 3) {
                $results[] = ['siteName' => $site['siteName'], 'pageUrl' => $site['pageUrl'], 'reason' => $site['reason'], 'usageEvidence' => $evidence, 'usageEvidenceUrl' => $site['usageEvidenceUrl']];
            }
        }

        return $results;
    }

    /** @param array<string, mixed> $candidate */
    private function groundedText(array $candidate): string
    {
        $metadata = $candidate['groundingMetadata'] ?? null;
        if (! is_array($metadata)) {
            return '';
        }
        $chunks = $metadata['groundingChunks'] ?? [];
        $supports = $metadata['groundingSupports'] ?? [];
        if (! is_array($chunks) || ! is_array($supports)) {
            return '';
        }
        $text = '';
        foreach ($supports as $support) {
            if (! is_array($support) || ! is_string($support['segment']['text'] ?? null) || ! is_array($support['groundingChunkIndices'] ?? null)) {
                continue;
            }
            foreach ($support['groundingChunkIndices'] as $index) {
                if (! is_int($index) || ! is_array($chunks[$index] ?? null)) {
                    continue;
                }
                $uri = $chunks[$index]['web']['uri'] ?? null;
                if (is_string($uri) && str_starts_with($uri, 'https://') && filter_var($uri, FILTER_VALIDATE_URL) !== false) {
                    $text .= $support['segment']['text'] . "\n";

                    break;
                }
            }
        }

        return $text;
    }

    private function validateUrl(string $url): string
    {
        $parts = parse_url($url);
        $host = is_array($parts) ? strtolower($parts['host'] ?? '') : '';
        if (filter_var($url, FILTER_VALIDATE_URL) === false || ! is_array($parts) || ($parts['scheme'] ?? '') !== 'https'
            || isset($parts['user']) || isset($parts['pass']) || (isset($parts['port']) && $parts['port'] !== 443)
            || ! str_contains($host, '.') || filter_var(trim($host, '[]'), FILTER_VALIDATE_IP) !== false
            || preg_match('/(^|\.)(localhost|local|internal|test|invalid)$/', $host)
            || $host === 'vertexaisearch.cloud.google.com' || str_ends_with($host, '.vertexaisearch.cloud.google.com')
            || ($host === 'www.google.com' && str_starts_with($parts['path'] ?? '', '/search'))) {
            throw new GeminiException('Invalid material source HTTPS URL');
        }

        return $host;
    }
}
