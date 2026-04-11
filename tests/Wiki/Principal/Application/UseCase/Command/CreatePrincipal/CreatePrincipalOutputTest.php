<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\CreatePrincipal;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Application\UseCase\Command\CreatePrincipal\CreatePrincipalOutput;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class CreatePrincipalOutputTest extends TestCase
{
    /**
     * 正常系: PrincipalがセットされるとtoArrayが正しい値を返すこと.
     */
    public function testToArrayWithPrincipal(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());

        $principal = new Principal(
            $principalIdentifier,
            $identityIdentifier,
            null,
            [],
            [],
        );

        $output = new CreatePrincipalOutput();
        $output->setPrincipal($principal);

        $result = $output->toArray();

        $this->assertSame((string) $principalIdentifier, $result['principalIdentifier']);
        $this->assertSame((string) $identityIdentifier, $result['identityIdentifier']);
        $this->assertFalse($result['isDelegatedPrincipal']);
        $this->assertTrue($result['isEnabled']);
    }

    /**
     * 正常系: Principalが未セットの場合toArrayがnull値の配列を返すこと.
     */
    public function testToArrayWithoutPrincipal(): void
    {
        $output = new CreatePrincipalOutput();

        $result = $output->toArray();

        $this->assertNull($result['principalIdentifier']);
        $this->assertNull($result['identityIdentifier']);
        $this->assertNull($result['isDelegatedPrincipal']);
        $this->assertNull($result['isEnabled']);
    }
}
