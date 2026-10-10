<?php

declare(strict_types=1);

namespace Tests\Http\Support;

use Application\Http\Support\VisitorLocationVerifier;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class VisitorLocationVerifierTest extends TestCase
{
    private const string SECRET = 'test-only-visitor-forwarding-key-32-bytes';

    private function signedRequest(string $country = 'JP', string $region = '01', int $offset = 0): Request
    {
        Date::setTestNow(new DateTimeImmutable('@1800000000'));
        $this->app()['config']->set('wiki.visitor_location_secret', self::SECRET);
        $request = Request::create('/api/v1/wiki/drafts/example/submit', 'POST', server: ['HTTP_COOKIE' => 'session=actor-a', 'HTTP_AUTHORIZATION' => 'Bearer actor-a'], content: '{"resourceType":"group"}');
        $timestamp = (string) (1800000000 + $offset);
        $request->headers->set('X-Kpool-Visitor-Country', $country);
        $request->headers->set('X-Kpool-Visitor-Region', $region);
        $request->headers->set('X-Kpool-Visitor-Timestamp', $timestamp);
        $payload = implode("\n", ['kpool-visitor-v1', $timestamp, 'POST', '/api/v1/wiki/drafts/example/submit', hash('sha256', '{"resourceType":"group"}'), hash('sha256', 'Bearer actor-a'), hash('sha256', 'session=actor-a'), $country, $region]);
        $request->headers->set('X-Kpool-Visitor-Signature', hash_hmac('sha256', $payload, self::SECRET));

        return $request;
    }

    #[DataProvider('codes')]
    public function testAcceptsSignedCodes(string $country, string $region, ?string $expectedCountry, ?string $expectedRegion): void
    {
        $location = (new VisitorLocationVerifier())->verify($this->signedRequest($country, $region));
        self::assertSame($expectedCountry, $location->country());
        self::assertSame($expectedRegion, $location->region());
    }

    /** @return array<array{string, string, ?string, ?string}> */
    public static function codes(): array
    {
        return [['JP', '01', 'JP', '01'], ['ZZ', 'NEW9', 'ZZ', 'NEW9'], ['US', 'CA', 'US', 'CA'], ['JP', '', 'JP', null], ['', '01', null, null], ['', '', null, null], ['jp', '01', null, null], ['JP', 'JP-01', null, null], ['JP', 'abcdefghijklmn', null, null], ['JPN', '01', null, null]];
    }

    #[DataProvider('tampering')]
    public function testRejectsTampering(string $target, string $value): void
    {
        $request = $this->signedRequest();
        if ($target === 'method') {
            $request->setMethod($value);
        } elseif ($target === 'path') {
            $request->server->set('REQUEST_URI', $value);
        } elseif ($target === 'body') {
            $headers = $request->headers->all();
            $request->initialize(server: $request->server->all(), content: $value);
            $request->headers->replace($headers);
        } else {
            $request->headers->set($target, $value);
        }
        $location = (new VisitorLocationVerifier())->verify($request);
        self::assertNull($location->country());
        self::assertNull($location->region());
    }

    /** @return array<array{string, string}> */
    public static function tampering(): array
    {
        return [['method', 'DELETE'], ['path', '/different'], ['path', '/api/v1/wiki/drafts/example/submit?target=other'], ['body', '{}'], ['Authorization', 'Bearer actor-b'], ['Cookie', 'session=actor-b'], ['X-Kpool-Visitor-Country', 'US'], ['X-Kpool-Visitor-Region', '13'], ['X-Kpool-Visitor-Timestamp', '0'], ['X-Kpool-Visitor-Signature', 'bad']];
    }

    #[DataProvider('staleOffsets')]
    public function testRejectsExpiredAndFutureTimestamps(int $offset): void
    {
        self::assertNull((new VisitorLocationVerifier())->verify($this->signedRequest(offset: $offset))->country());
    }

    /** @return array<array{int}> */
    public static function staleOffsets(): array
    {
        return [[-301], [31]];
    }

    public function testDisabledWithoutSecretAndPlainHeadersAreNotTrusted(): void
    {
        $request = $this->signedRequest();
        $this->app()['config']->set('wiki.visitor_location_secret', '');
        self::assertNull((new VisitorLocationVerifier())->verify($request)->country());
        $this->app()['config']->set('wiki.visitor_location_secret', self::SECRET);
        $request->headers->remove('X-Kpool-Visitor-Signature');
        self::assertNull((new VisitorLocationVerifier())->verify($request)->country());
    }

    public function testDuplicateHeadersAreIgnored(): void
    {
        $request = $this->signedRequest();
        $request->headers->set('X-Kpool-Visitor-Country', ['JP', 'US']);
        self::assertNull((new VisitorLocationVerifier())->verify($request)->country());
    }

    #[DataProvider('freshOffsets')]
    public function testAcceptsFreshTimestampBoundaries(int $offset): void
    {
        self::assertSame('JP', (new VisitorLocationVerifier())->verify($this->signedRequest(offset: $offset))->country());
    }

    /** @return array<array{int}> */
    public static function freshOffsets(): array
    {
        return [[-300], [30]];
    }

    public function testShortSecretDisablesTrust(): void
    {
        $request = $this->signedRequest();
        $this->app()['config']->set('wiki.visitor_location_secret', str_repeat('x', 31));
        self::assertNull((new VisitorLocationVerifier())->verify($request)->country());
    }

    #[Override]
    protected function tearDown(): void
    {
        Date::setTestNow();
        parent::tearDown();
    }

    public function testSharedProtocolVectorGeneratedByNodeCrypto(): void
    {
        $request = $this->signedRequest();
        $request->headers->set('X-Kpool-Visitor-Signature', '50e328bb74ff1624e557bca24c4454d7f508bd900ecdf56cf8a8d4a8d71d6725');
        self::assertSame('JP', (new VisitorLocationVerifier())->verify($request)->country());
    }
}
