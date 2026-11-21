<?php

declare(strict_types=1);

namespace Tests\Http\Context;

use Application\Http\Context\MonetizationContext;
use PHPUnit\Framework\Attributes\CoversClass;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[CoversClass(MonetizationContext::class)]
class MonetizationContextTest extends TestCase
{
    public function testStoresPrincipalIdentifier(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $context = new MonetizationContext($principalIdentifier);

        $this->assertSame($principalIdentifier, $context->principalIdentifier);
    }
}
