<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Infrastructure\Service;

use Application\Http\Client\Foundation\PsrFactories;
use Application\Http\Client\GeminiClient\GeminiClient;
use Mockery;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Image\Application\DTO\MaterialSearchContext;
use Source\Wiki\Image\Application\DTO\RelatedWikiContext;
use Source\Wiki\Image\Application\Exception\MaterialSearchFailedException;
use Source\Wiki\Image\Application\Service\FreeMaterialSearchServiceInterface;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class FreeMaterialSearchServiceTest extends TestCase
{
    public function testMapsGroundedResultToReadModel(): void
    {
        $site = ['siteName' => 'Commons', 'pageUrl' => 'https://commons.wikimedia.org/photo', 'reason' => 'Target matches', 'usageEvidence' => 'CC BY', 'usageEvidenceUrl' => 'https://commons.wikimedia.org/license'];
        $body = json_encode(['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => json_encode(['sites' => [$site]], JSON_THROW_ON_ERROR)]]], 'groundingMetadata' => ['groundingChunks' => [['web' => ['uri' => $site['usageEvidenceUrl']]]], 'groundingSupports' => [['segment' => ['text' => json_encode($site, JSON_THROW_ON_ERROR)], 'groundingChunkIndices' => [0]]]]]]], JSON_THROW_ON_ERROR);
        $this->bindClient(200, $body);
        $result = $this->app()->make(FreeMaterialSearchServiceInterface::class)->search($this->context());
        self::assertCount(1, $result);
        self::assertSame($site, $result[0]->toArray());
    }

    public function testForwardsUnsavedRelatedWikiNamesAndRelationships(): void
    {
        $this->bindClient(200, '{"candidates":[{"finishReason":"STOP","content":{"parts":[{"text":"{\"sites\":[]}"}]}}]}', expectedRelated: true);
        $context = new MaterialSearchContext('Edited display name', ResourceType::TALENT, Language::KOREAN, [
            new RelatedWikiContext(new WikiIdentifier(StrTestHelper::generateUuid()), 'New group', ResourceType::GROUP, 'member_of'),
        ]);
        self::assertSame([], $this->app()->make(FreeMaterialSearchServiceInterface::class)->search($context));
    }

    public function testEmptySearchIsNotAnError(): void
    {
        $this->bindClient(200, '{"candidates":[{"finishReason":"STOP","content":{"parts":[{"text":"{\"sites\":[]}"}]}}]}');
        self::assertSame([], $this->app()->make(FreeMaterialSearchServiceInterface::class)->search($this->context()));
    }

    /** @return iterable<string, array{int, string, string}> */
    public static function failures(): iterable
    {
        yield 'unconfigured' => [200, '{}', ''];
        yield 'provider error' => [503, '{"error":{"message":"private upstream detail"}}', 'test-key'];
        yield 'invalid response' => [200, 'not-json', 'test-key'];
    }

    #[DataProvider('failures')]
    public function testProviderFailureIsNotNoMatches(int $status, string $body, string $key): void
    {
        $this->bindClient($status, $body, $key);
        $this->expectException(MaterialSearchFailedException::class);
        $this->app()->make(FreeMaterialSearchServiceInterface::class)->search($this->context());
    }

    private function context(): MaterialSearchContext
    {
        return new MaterialSearchContext('Edited display name', ResourceType::TALENT, Language::KOREAN, []);
    }

    private function bindClient(int $status, string $body, string $key = 'test-key', bool $expectedRelated = false): void
    {
        $factory = new Psr17Factory();
        /** @var ClientInterface&Mockery\MockInterface $httpClient */
        $httpClient = Mockery::mock(ClientInterface::class);
        if ($key === '') {
            $httpClient->shouldNotReceive('sendRequest');
        } else {
            $httpClient->shouldReceive('sendRequest')->once()->withArgs(function (RequestInterface $request) use ($expectedRelated): bool {
                /** @var array{contents: list<array{parts: list<array{text: string}>}>} $data */
                $data = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
                self::assertStringContainsString('Korean', $data['contents'][0]['parts'][0]['text']);
                self::assertStringContainsString('Edited display name', $data['contents'][0]['parts'][0]['text']);
                if ($expectedRelated) {
                    foreach (['New group', 'member_of', 'group', 'identifier'] as $text) {
                        self::assertStringContainsString($text, $data['contents'][0]['parts'][0]['text']);
                    }
                } else {
                    self::assertStringContainsString('"relatedWikis":[]', $data['contents'][0]['parts'][0]['text']);
                }

                return true;
            })->andReturn($factory->createResponse($status)->withBody($factory->createStream($body)));
        }
        $this->app()->instance(GeminiClient::class, new GeminiClient($key, 'gemini-test', $httpClient, new PsrFactories($factory, $factory, $factory)));
    }
}
