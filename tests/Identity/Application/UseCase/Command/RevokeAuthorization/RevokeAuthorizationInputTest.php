<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Command\RevokeAuthorization;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\UseCase\Command\RevokeAuthorization\RevokeAuthorizationInput;
use Source\Identity\Domain\ValueObject\AuthorizationConsentIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\StrTestHelper;

class RevokeAuthorizationInputTest extends TestCase
{
    public function testPreservesValueObjectInstances(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $consent = new AuthorizationConsentIdentifier(StrTestHelper::generateUuid());
        $input = new RevokeAuthorizationInput($identity, $consent);
        self::assertSame($consent, $input->consentIdentifier());
        self::assertSame($identity, $input->identityIdentifier());
    }
}
