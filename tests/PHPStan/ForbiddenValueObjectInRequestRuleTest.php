<?php

declare(strict_types=1);

namespace Tests\PHPStan;

use Kpool\PHPStan\Rules\ForbiddenValueObjectInRequestRule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\FileTypeMapper;

/** @extends RuleTestCase<ForbiddenValueObjectInRequestRule> */
class ForbiddenValueObjectInRequestRuleTest extends RuleTestCase
{
    protected function getRule(): ForbiddenValueObjectInRequestRule
    {
        require_once __DIR__ . '/../../phpstan/fixtures/request-value-objects.php';

        return new ForbiddenValueObjectInRequestRule(self::getContainer()->getByType(FileTypeMapper::class));
    }

    public function testRequestTypesAndReturnedValuesWhileAllowingValidationAndActions(): void
    {
        $accountMessage = 'Request classes must not expose value objects: Source\Shared\Domain\ValueObject\AccountIdentifier. Create value objects in the Action.';
        $enumMessage = 'Request classes must not expose value objects: Source\Account\Shared\Domain\ValueObject\AccountType. Create value objects in the Action.';
        $this->analyse([__DIR__ . '/../../phpstan/fixtures/request-value-objects.php'], [
            [$accountMessage, 12],
            [$accountMessage, 13],
            [$accountMessage, 15],
            [$accountMessage, 17],
            [$accountMessage, 19],
            [$accountMessage, 22],
            [$enumMessage, 26],
        ]);
    }
}
