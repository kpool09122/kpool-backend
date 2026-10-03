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
        private readonly string $projectId,
        private readonly string $credentialsPath,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->projectId !== '' && $this->credentialsPath !== '' && file_exists($this->credentialsPath);
    }

    public function translateTexts(TranslateTextsRequest $request): TranslateTextsResponse
    {
        if (! $this->isConfigured()) {
            $this->logger->warning('Google Translate credentials are not configured');

            return new TranslateTextsResponse([]);
        }

        try {
            $client = new TranslationServiceClient([
                'credentials' => $this->credentialsPath,
            ]);

            $translateRequest = new TranslateTextRequest()
                ->setContents($request->texts())
                ->setTargetLanguageCode($request->targetLanguage())
                ->setParent(TranslationServiceClient::locationName($this->projectId, 'global'))
                ->setMimeType('text/plain');

            $response = $client->translateText($translateRequest);

            $translations = [];
            foreach ($response->getTranslations() as $translation) {
                $translations[] = $translation->getTranslatedText();
            }

            return new TranslateTextsResponse(translatedTexts: $translations);
        } catch (Throwable $e) {
            $this->logger->error('Google Translate API failed', [
                'message' => $e->getMessage(),
            ]);

            return new TranslateTextsResponse([]);
        }
    }
}
