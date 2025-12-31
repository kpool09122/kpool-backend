<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Domain\ValueObject;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Agency\Domain\ValueObject\AutomaticDraftAgencyCreationPayload;
use Source\Wiki\Agency\Domain\ValueObject\AutomaticDraftAgencySource;
use Source\Wiki\Agency\Domain\ValueObject\CEO;
use Source\Wiki\Agency\Domain\ValueObject\Description;
use Source\Wiki\Agency\Domain\ValueObject\FoundedIn;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;

class AutomaticDraftAgencyCreationPayloadTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスが作成されること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $name = new AgencyName('JYP엔터테인먼트');
        $ceo = new CEO('J.Y. Park');
        $foundedIn = new FoundedIn(new DateTimeImmutable('1997-04-25'));
        $description = new Description('auto generated agency profile');
        $source = new AutomaticDraftAgencySource('webhook::news');

        $editorIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $translation = Language::KOREAN;

        $payload = new AutomaticDraftAgencyCreationPayload(
            $editorIdentifier,
            $translation,
            $name,
            $ceo,
            $foundedIn,
            $description,
            $source,
        );

        $this->assertSame($editorIdentifier, $payload->editorIdentifier());
        $this->assertSame($translation, $payload->language());
        $this->assertSame($name, $payload->name());
        $this->assertSame($ceo, $payload->CEO());
        $this->assertSame($foundedIn, $payload->foundedIn());
        $this->assertSame($description, $payload->description());
        $this->assertSame($source, $payload->source());
    }

    /**
     * 正常系: null許容のフィールドが正しく許容されていること.
     *
     * @return void
     */
    public function testAllowsNullableFields(): void
    {
        $name = new AgencyName('JYP엔터테인먼트');
        $description = new Description('auto generated agency profile');
        $payload = new AutomaticDraftAgencyCreationPayload(
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            Language::ENGLISH,
            $name,
            null,
            null,
            $description,
            new AutomaticDraftAgencySource('webhook::minimal'),
        );

        $this->assertNull($payload->CEO());
        $this->assertNull($payload->foundedIn());
    }
}
