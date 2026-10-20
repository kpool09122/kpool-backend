<?php

declare(strict_types=1);

namespace Tests\Http\Client\GeminiClient;

use Application\Http\Client\GeminiClient\Exceptions\GeminiException;
use Application\Http\Client\GeminiClient\SearchFreeMaterials\SearchFreeMaterialsResponse;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SearchFreeMaterialsResponseTest extends TestCase
{
    /** @return array{siteName: string, pageUrl: string, reason: string, usageEvidence: string, usageEvidenceUrl: string} */
    private static function site(string $host = 'commons.wikimedia.org'): array
    {
        return ['siteName' => $host, 'pageUrl' => "https://{$host}/wiki/File:Example.jpg", 'reason' => 'Subject matches the target', 'usageEvidence' => 'CC BY with attribution', 'usageEvidenceUrl' => "https://{$host}/license"];
    }

    /**
     * @param list<array<string, mixed>> $sites
     * @return array{candidates: list<array{finishReason: string, content: array{parts: list<array{text: string}>}, groundingMetadata: array{groundingChunks: list<array{web: array{uri: string, title: string}}>, groundingSupports: list<array{segment: array{text: string}, groundingChunkIndices: list<int>}>}}>}
     */
    private static function body(array $sites): array
    {
        return ['candidates' => [[
            'finishReason' => 'STOP',
            'content' => ['parts' => [['text' => json_encode(['sites' => $sites], JSON_THROW_ON_ERROR)]]],
            'groundingMetadata' => [
                'groundingChunks' => [['web' => ['uri' => 'https://vertexaisearch.cloud.google.com/grounding-api-redirect/example', 'title' => 'Source']]],
                'groundingSupports' => [['segment' => ['text' => json_encode(['sites' => $sites], JSON_THROW_ON_ERROR)], 'groundingChunkIndices' => [0]]],
            ],
        ]]];
    }

    public function testReturnsValidatedSitesAndKeepsDirectUrlsNotGroundingRedirect(): void
    {
        self::assertSame([self::site()], $this->parse(self::body([self::site()])));
    }

    public function testDeduplicatesSubdomainsAndNamesThenLimitsToThreeSites(): void
    {
        $sites = [self::site(), self::site('www.wikimedia.org'), self::site('flickr.com'), self::site('unsplash.com'), self::site('pexels.com')];
        self::assertSame([$sites[0], $sites[2], $sites[3]], $this->parse(self::body($sites)));
    }

    public function testOmitsCandidateWithoutGroundedUsageEvidence(): void
    {
        $site = self::site();
        $site['usageEvidence'] = 'Unverified permission';
        $body = self::body([$site]);
        $body['candidates'][0]['groundingMetadata']['groundingSupports'][0]['segment']['text'] = 'CC BY with attribution';
        self::assertSame([], $this->parse($body));
    }

    public function testDoesNotReuseAnotherSitesGroundingForIdenticalLicenseText(): void
    {
        $body = self::body([self::site()]);
        $body['candidates'][0]['groundingMetadata']['groundingSupports'][0]['segment']['text'] = json_encode(['sites' => [self::site('flickr.com')]], JSON_THROW_ON_ERROR);
        self::assertSame([], $this->parse($body));
    }

    public function testOmitsCandidatesWhenGroundingIsAbsent(): void
    {
        $body = self::body([self::site()]);
        unset($body['candidates'][0]['groundingMetadata']);
        self::assertSame([], $this->parse($body));
    }

    /** @return iterable<string, array{array<array-key, mixed>}> */
    public static function malformedResponses(): iterable
    {
        $body = self::body([self::site()]);
        $bad = $body;
        $bad['candidates'][0]['finishReason'] = 'MAX_TOKENS';
        yield 'truncated generation' => [$bad];
        yield 'no candidate' => [[]];
        foreach (['{}', '{"sites":{}}', 'not JSON', '{"sites":[{}]}', '{"sites":null}'] as $text) {
            $bad = $body;
            $bad['candidates'][0]['content']['parts'][0]['text'] = $text;
            yield $text => [$bad];
        }
        foreach (['siteName', 'pageUrl', 'reason', 'usageEvidence', 'usageEvidenceUrl'] as $key) {
            $site = self::site();
            unset($site[$key]);
            yield 'missing '.$key => [self::body([$site])];
            $site = self::site();
            $site[$key] = '  ';
            yield 'blank '.$key => [self::body([$site])];
        }
        foreach (['http://example.org/a', 'javascript:alert(1)', 'https://user:password@example.org/a', 'https://127.0.0.1/a', 'https://localhost/a', 'https://vertexaisearch.cloud.google.com/grounding-api-redirect/test'] as $url) {
            foreach (['pageUrl', 'usageEvidenceUrl'] as $key) {
                $site = self::site();
                $site[$key] = $url;
                yield $key.$url => [self::body([$site])];
            }
        }
    }

    /** @param array<array-key, mixed> $body */
    #[DataProvider('malformedResponses')]
    public function testRejectsMalformedResponsesInsteadOfReportingNoMatches(array $body): void
    {
        $this->expectException(GeminiException::class);
        $this->parse($body);
    }

    public function testRejectsInvalidEnvelopeJson(): void
    {
        $this->expectException(GeminiException::class);
        (new SearchFreeMaterialsResponse(new Response(200, [], '{invalid')))->sites();
    }

    /**
     * @param array<array-key, mixed> $body
     * @return list<array{siteName: string, pageUrl: string, reason: string, usageEvidence: string, usageEvidenceUrl: string}>
     */
    private function parse(array $body): array
    {
        return (new SearchFreeMaterialsResponse(new Response(200, [], json_encode($body, JSON_THROW_ON_ERROR))))->sites();
    }
}
