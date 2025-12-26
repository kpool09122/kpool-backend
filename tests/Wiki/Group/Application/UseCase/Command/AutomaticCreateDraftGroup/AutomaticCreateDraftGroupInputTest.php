<?php

declare(strict_types=1);

namespace Tests\Wiki\Group\Application\UseCase\Command\AutomaticCreateDraftGroup;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Group\Application\UseCase\Command\AutomaticCreateDraftGroup\AutomaticCreateDraftGroupInput;
use Source\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Group\Domain\ValueObject\AutomaticDraftGroupCreationPayload;
use Source\Wiki\Group\Domain\ValueObject\AutomaticDraftGroupSource;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Group\Domain\ValueObject\SongIdentifier;
use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Role;
use Tests\Helper\StrTestHelper;

class AutomaticCreateDraftGroupInputTest extends TestCase
{
    public function testAccessors(): void
    {
        $payload = new AutomaticDraftGroupCreationPayload(
            new EditorIdentifier(StrTestHelper::generateUlid()),
            Language::KOREAN,
            new GroupName('TWICE'),
            new AgencyIdentifier(StrTestHelper::generateUlid()),
            new Description('auto generated'),
            [new SongIdentifier(StrTestHelper::generateUlid())],
            new AutomaticDraftGroupSource('webhook::draft'),
        );

        $principal = new Principal(
            new PrincipalIdentifier(StrTestHelper::generateUlid()),
            new IdentityIdentifier(StrTestHelper::generateUlid()),
            Role::ADMINISTRATOR,
            null,
            [],
            [],
        );

        $input = new AutomaticCreateDraftGroupInput($payload, $principal);

        $this->assertSame($payload, $input->payload());
        $this->assertSame($principal, $input->principal());
    }
}
