<?php

declare(strict_types=1);

namespace Tests\PHPStan;

use Kpool\PHPStan\Rules\ForbiddenDatabaseQueryBuilderRule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<ForbiddenDatabaseQueryBuilderRule> */
class ForbiddenDatabaseQueryBuilderRuleTest extends RuleTestCase
{
    protected function getRule(): ForbiddenDatabaseQueryBuilderRule
    {
        return new ForbiddenDatabaseQueryBuilderRule();
    }

    public function testQueriesAreForbiddenAndTransactionsAndEloquentAreAllowed(): void
    {
        $message = 'Database queries in src must use Eloquent. DB facade and connections may only perform transaction operations.';
        $this->analyse([__DIR__ . '/../../phpstan/fixtures/src/database-query-builder.php'], array_map(
            static fn (int $line): array => [$message, $line],
            range(19, 31),
        ));
    }

    public function testFilesOutsideSourceAreExcluded(): void
    {
        $this->analyse([__DIR__ . '/../../phpstan/fixtures/database-query-builder-outside-src.php'], []);
    }
}
