<?php

declare(strict_types=1);

namespace Tests\PHPStan;

use Kpool\PHPStan\Rules\RequireImportedNameRule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<RequireImportedNameRule> */
class RequireImportedNameRuleTest extends RuleTestCase
{
    protected function getRule(): RequireImportedNameRule
    {
        return new RequireImportedNameRule();
    }

    public function testCodeAndPhpDocImports(): void
    {
        $this->analyse([__DIR__ . '/../../phpstan/fixtures/imported-names.php'], [
            ['Import \\DateTimeImmutable with use and reference its short name.', 8],
            ['Import \\DateTimeImmutable with use and reference its short name.', 9],
            ['Import \\DateTimeImmutable with use and reference its short name.', 11],
            ['Import \\DateTimeImmutable with use and reference its short name.', 12],
        ]);
    }
}
