<?php

declare(strict_types=1);

namespace Tests\Http\Context;

use Application\Http\Context\ActorContext;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ActorContextTest extends TestCase
{
    public function testCanBeConstructed(): void
    {
        $identityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $language = Language::JAPANESE;
        $context = new ActorContext(
            identityIdentifier: $identityId,
            language: $language,
        );

        $this->assertSame($identityId, $context->identityIdentifier);
        $this->assertSame($language, $context->language);
    }
}
