<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Application\UseCase\Query\ListPrincipalGroups;

use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\SiteManagement\Principal\Application\UseCase\Query\ListPrincipalGroups\ListPrincipalGroupsInput;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;

class ListPrincipalGroupsInputTest extends TestCase
{
    public function testPreservesValues(): void
    {
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $input = new ListPrincipalGroupsInput($account, $principal);
        self::assertSame($account, $input->accountIdentifier());
        self::assertSame($principal, $input->operatorPrincipalIdentifier());
    }
}
