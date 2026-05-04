<?php

declare(strict_types=1);

namespace Application\Http\Action\Concerns;

use Application\Http\Context\ActorContext;
use Source\Shared\Domain\ValueObject\Language;

trait ResolvesLanguage
{
    public function language(): string
    {
        if (app()->bound(ActorContext::class)) {
            return app(ActorContext::class)->language->value;
        }

        return $this->resolveAcceptLanguage($this->header('Accept-Language'));
    }

    private function resolveAcceptLanguage(?string $header): string
    {
        if ($header === null || trim($header) === '') {
            return Language::ENGLISH->value;
        }

        foreach (explode(',', $header) as $languageRange) {
            $language = strtolower(trim(explode(';', $languageRange, 2)[0]));

            if ($language === '' || $language === '*') {
                continue;
            }

            $primaryLanguage = explode('-', $language, 2)[0];
            foreach (Language::cases() as $supportedLanguage) {
                if ($primaryLanguage === $supportedLanguage->value) {
                    return $supportedLanguage->value;
                }
            }
        }

        return Language::ENGLISH->value;
    }
}
