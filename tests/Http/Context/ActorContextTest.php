<?php

declare(strict_types=1);

namespace Tests\Http\Context;

use Application\Http\Context\ActorContext;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ActorContextTest extends TestCase
{
    public function testCanBeConstructedWithAllProperties(): void
    {
        $identityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $language = Language::JAPANESE;
        $delegationId = new DelegationIdentifier(StrTestHelper::generateUuid());
        $originalIdentityId = new IdentityIdentifier(StrTestHelper::generateUuid());

        $context = new ActorContext(
            identityIdentifier: $identityId,
            language: $language,
            delegationIdentifier: $delegationId,
            originalIdentityIdentifier: $originalIdentityId,
        );

        $this->assertSame($identityId, $context->identityIdentifier);
        $this->assertSame($language, $context->language);
        $this->assertSame($delegationId, $context->delegationIdentifier);
        $this->assertSame($originalIdentityId, $context->originalIdentityIdentifier);
    }

    public function testCanBeConstructedWithNullOptionalProperties(): void
    {
        $identityId = new IdentityIdentifier(StrTestHelper::generateUuid());
        $language = Language::ENGLISH;

        $context = new ActorContext(
            identityIdentifier: $identityId,
            language: $language,
            delegationIdentifier: null,
            originalIdentityIdentifier: null,
        );

        $this->assertSame($identityId, $context->identityIdentifier);
        $this->assertSame($language, $context->language);
        $this->assertNull($context->delegationIdentifier);
        $this->assertNull($context->originalIdentityIdentifier);
    }
}
