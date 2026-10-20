<?php

declare(strict_types=1);

namespace Application\Http\Client\GoogleTranslateClient;

use Application\Http\Client\GoogleTranslateClient\TranslateTexts\TranslateTextsRequest;
use Application\Http\Client\GoogleTranslateClient\TranslateTexts\TranslateTextsResponse;
use Google\Cloud\Translate\V3\Client\TranslationServiceClient;
use Google\Cloud\Translate\V3\TranslateTextRequest;
use Psr\Log\LoggerInterface;
use Throwable;

class GoogleTranslateClient
{
    public function __construct(
        private readonly GoogleTranslateCredentials $googleTranslateCredentials,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->googleTranslateCredentials->isConfigured();
    }

    public function translateTexts(TranslateTextsRequest $request): TranslateTextsResponse
    {
        if (! $this->isConfigured()) {
            $this->logger->warning('Google Translate credentials are not configured');

            return new TranslateTextsResponse([]);
        }

        try {
            $client = new TranslationServiceClient([
                'credentials' => $this->googleTranslateCredentials->create(),
            ]);

            $translateRequest = new TranslateTextRequest()
                ->setContents($request->texts())
                ->setTargetLanguageCode($request->targetLanguage())
                ->setParent(TranslationServiceClient::locationName($this->googleTranslateCredentials->projectId(), 'global'))
                ->setMimeType('text/plain');

            $response = $client->translateText($translateRequest);

            $translations = [];
            foreach ($response->getTranslations() as $translation) {
                $translations[] = $translation->getTranslatedText();
            }

            return new TranslateTextsResponse(translatedTexts: $translations);
        } catch (Throwable $e) {
            $this->logger->error('Google Translate API failed', [
                'exception_class' => $e::class,
                'code' => $e->getCode(),
            ]);

            return new TranslateTextsResponse([]);
        }
    }
}
