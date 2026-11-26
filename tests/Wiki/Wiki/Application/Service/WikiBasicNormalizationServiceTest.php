<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Application\Service;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Wiki\Application\Service\WikiBasicNormalizationService;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent\TalentBasic;
use Tests\TestCase;

class WikiBasicNormalizationServiceTest extends TestCase
{
    public function testNormalizesACopyAndPreservesOtherFields(): void
    {
        $basic = TalentBasic::fromArray([
            'name' => 'CHAEYOUNG',
            'normalized_name' => 'stale-name',
            'real_name' => 'Son Chaeyoung',
            'normalized_real_name' => 'stale-real-name',
            'agency_identifier' => null,
            'group_identifiers' => [],
            'birthday' => '1999-04-23',
            'fandom_name' => 'ONCE',
            'position' => 'RAPPER',
        ]);
        $original = $basic->toArray();

        $result = $this->app()->make(WikiBasicNormalizationService::class)->normalize($basic, Language::ENGLISH);

        self::assertNotSame($basic, $result);
        self::assertInstanceOf(TalentBasic::class, $result);
        self::assertSame($original, $basic->toArray());
        self::assertSame(array_replace($original, [
            'normalized_name' => 'chaeyoung',
            'normalized_real_name' => 'son chaeyoung',
        ]), $result->toArray());
    }
}
