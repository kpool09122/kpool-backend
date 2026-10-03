<?php

declare(strict_types=1);

namespace Tests\PHPStan;

use Kpool\PHPStan\Rules\ForbiddenLogFacadeRule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<ForbiddenLogFacadeRule> */
class ForbiddenLogFacadeRuleTest extends RuleTestCase
{
    protected function getRule(): ForbiddenLogFacadeRule
    {
        require_once __DIR__ . '/../../phpstan/fixtures/log-facade.php';

        return new ForbiddenLogFacadeRule();
    }

    public function testFacadeReferencesAreForbidden(): void
    {
        $message = 'Log Facade is forbidden outside Jobs. Use Psr\\Log\\LoggerInterface instead.';
        $this->analyse([__DIR__ . '/../../phpstan/fixtures/log-facade.php'], [
            [$message, 11],
            [$message, 12],
            [$message, 13],
            [$message, 14],
        ]);
    }

    public function testJobsAreExcluded(): void
    {
        $this->analyse([__DIR__ . '/../../phpstan/fixtures/application/Jobs/log-facade.php'], []);
    }
}
