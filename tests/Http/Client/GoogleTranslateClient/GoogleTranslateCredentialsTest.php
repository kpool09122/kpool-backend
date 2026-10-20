<?php

declare(strict_types=1);

namespace Tests\Http\Client\GoogleTranslateClient;

use Application\Http\Client\GoogleTranslateClient\GoogleTranslateClient;
use Application\Http\Client\GoogleTranslateClient\GoogleTranslateCredentials;
use Application\Http\Client\GoogleTranslateClient\TranslateTexts\TranslateTextsRequest;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class GoogleTranslateCredentialsTest extends TestCase
{
    private const string JSON = '{"type":"service_account","project_id":"translation-test","client_email":"translation@example.test","private_key":"test-only-key"}';

    public function testJsonCredentialsProvideProjectAndExplicitServiceAccountAuthentication(): void
    {
        $credentials = new GoogleTranslateCredentials('', self::JSON);

        self::assertTrue($credentials->isConfigured());
        self::assertSame('translation-test', $credentials->projectId());
        self::assertSame('translation-test', $credentials->create()->getProjectId());
    }

    public function testConfiguredProjectOverridesJsonProject(): void
    {
        self::assertSame('override-project', (new GoogleTranslateCredentials('override-project', self::JSON))->projectId());
    }

    public function testLocalCredentialFileStillWorks(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'translation-test-');
        self::assertIsString($path);

        try {
            file_put_contents($path, self::JSON);
            $credentials = new GoogleTranslateCredentials('', $path);
            self::assertTrue($credentials->isConfigured());
            self::assertSame('translation-test', $credentials->create()->getProjectId());
        } finally {
            unlink($path);
        }
    }

    #[DataProvider('invalidCredentials')]
    public function testInvalidCredentialsAreRejectedWithoutMakingTranslationRequests(string $json): void
    {
        $credentials = new GoogleTranslateCredentials('', $json);
        self::assertFalse($credentials->isConfigured());
        $client = new GoogleTranslateClient($credentials, new NullLogger());
        self::assertSame([], $client->translateTexts(new TranslateTextsRequest(['test'], 'en'))->translatedTexts());
        $this->expectException(InvalidArgumentException::class);
        $credentials->create();
    }

    /** @return array<string, array{string}> */
    public static function invalidCredentials(): array
    {
        return [
            'missing' => [''],
            'malformed JSON' => ['{invalid'],
            'missing file' => ['/missing/credentials.json'],
            'wrong credential type' => ['{"type":"external_account","project_id":"test","client_email":"test","private_key":"test"}'],
            'incomplete account' => ['{"type":"service_account","project_id":"test"}'],
            'missing project' => ['{"type":"service_account","client_email":"test","private_key":"test"}'],
            'non-object JSON' => ['[]'],
        ];
    }
}
