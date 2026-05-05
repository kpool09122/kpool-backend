<?php

declare(strict_types=1);

namespace Tests\Http\Context;

use Application\Http\Action\Concerns\ResolvesLanguage;
use Application\Http\Context\ActorContext;
use Illuminate\Foundation\Http\FormRequest;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ResolvesLanguageTest extends TestCase
{
    public function testReturnsLanguageFromActorContextWhenBound(): void
    {
        $actorContext = new ActorContext(
            identityIdentifier: new IdentityIdentifier(StrTestHelper::generateUuid()),
            language: Language::KOREAN,
            delegationIdentifier: null,
            originalIdentityIdentifier: null,
        );
        app()->instance(ActorContext::class, $actorContext);

        $request = new class () extends FormRequest {
            use ResolvesLanguage;
        };

        $this->assertSame('ko', $request->language());
    }

    public function testReturnsAcceptLanguageHeaderWhenActorContextNotBound(): void
    {
        $request = new class () extends FormRequest {
            use ResolvesLanguage;
        };
        $request->headers->set('Accept-Language', 'ja');

        $this->assertSame('ja', $request->language());
    }

    public function testReturnsSupportedLanguageFromAcceptLanguageList(): void
    {
        $request = new class () extends FormRequest {
            use ResolvesLanguage;
        };
        $request->headers->set('Accept-Language', 'fr-CA,ja-JP;q=0.9,en;q=0.8');

        $this->assertSame('ja', $request->language());
    }

    public function testReturnsDefaultEnglishWhenAcceptLanguageWildcard(): void
    {
        $request = new class () extends FormRequest {
            use ResolvesLanguage;
        };
        $request->headers->set('Accept-Language', '*');

        $this->assertSame('en', $request->language());
    }

    public function testReturnsDefaultEnglishWhenAcceptLanguageIsUnsupported(): void
    {
        $request = new class () extends FormRequest {
            use ResolvesLanguage;
        };
        $request->headers->set('Accept-Language', 'fr-CA,es;q=0.8');

        $this->assertSame('en', $request->language());
    }

    public function testReturnsDefaultEnglishWhenNoActorContextAndNoHeader(): void
    {
        $request = new class () extends FormRequest {
            use ResolvesLanguage;
        };

        $this->assertSame('en', $request->language());
    }
}
