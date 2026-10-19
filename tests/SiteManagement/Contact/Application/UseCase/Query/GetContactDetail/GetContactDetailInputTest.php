<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Application\UseCase\Query\GetContactDetail;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Source\SiteManagement\Contact\Application\UseCase\Query\GetContactDetail\GetContactDetailInput;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;

class GetContactDetailInputTest extends TestCase
{
    public function testTargetPrincipalIsRequired(): void
    {
        $constructor = new ReflectionMethod(GetContactDetailInput::class, '__construct');
        $this->assertFalse($constructor->getParameters()[1]->allowsNull());
    }

    public function test__construct(): void
    {
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $targetPrincipalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $contactIdentifier = new ContactIdentifier(StrTestHelper::generateUuid());

        $input = new GetContactDetailInput($principalIdentifier, $targetPrincipalIdentifier, $contactIdentifier);

        $this->assertSame($principalIdentifier, $input->principalIdentifier());
        $this->assertSame($targetPrincipalIdentifier, $input->targetPrincipalIdentifier());
        $this->assertSame($contactIdentifier, $input->contactIdentifier());
    }
}
