<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Application\UseCase\Command\AutomaticCreateDraftTalent;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Role;
use Source\Wiki\Talent\Application\UseCase\Command\AutomaticCreateDraftTalent\AutomaticCreateDraftTalentInput;
use Source\Wiki\Talent\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\AutomaticDraftTalentCreationPayload;
use Source\Wiki\Talent\Domain\ValueObject\AutomaticDraftTalentSource;
use Source\Wiki\Talent\Domain\ValueObject\Birthday;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;
use Tests\Helper\StrTestHelper;

class AutomaticCreateDraftTalentInputTest extends TestCase
{
    public function testAccessors(): void
    {
        $payload = new AutomaticDraftTalentCreationPayload(
            new EditorIdentifier(StrTestHelper::generateUlid()),
            Language::JAPANESE,
            new TalentName('自動作成タレント'),
            new RealName('Auto Talent'),
            new AgencyIdentifier(StrTestHelper::generateUlid()),
            [new GroupIdentifier(StrTestHelper::generateUlid())],
            new Birthday(new DateTimeImmutable('1995-05-05')),
            new Career('Auto generated career'),
            new AutomaticDraftTalentSource('webhook::talent'),
        );
        $principal = new Principal(
            new PrincipalIdentifier(StrTestHelper::generateUlid()),
            new IdentityIdentifier(StrTestHelper::generateUlid()),
            Role::ADMINISTRATOR,
            null,
            [],
            [],
        );

        $input = new AutomaticCreateDraftTalentInput($payload, $principal);

        $this->assertSame($payload, $input->payload());
        $this->assertSame($principal, $input->principal());
    }
}
