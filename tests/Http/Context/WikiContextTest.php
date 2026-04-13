<?php

declare(strict_types=1);

namespace Tests\Http\Context;

use Application\Http\Context\WikiContext;
use PHPUnit\Framework\Attributes\CoversClass;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[CoversClass(WikiContext::class)]
class WikiContextTest extends TestCase
{
    public function testStoresPrincipalIdentifier(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $context = new WikiContext($principalIdentifier);

        $this->assertSame($principalIdentifier, $context->principalIdentifier);
    }
}
