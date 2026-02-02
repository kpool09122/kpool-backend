<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Agency\Domain\ValueObject\AutoAgencyCreationPayload;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Name;

class AutoAgencyCreationPayloadTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスが作成されること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $name = new Name('JYP엔터테인먼트');
        $translation = Language::KOREAN;

        $payload = new AutoAgencyCreationPayload(
            $translation,
            $name,
        );

        $this->assertSame($translation, $payload->language());
        $this->assertSame($name, $payload->name());

    }
}
