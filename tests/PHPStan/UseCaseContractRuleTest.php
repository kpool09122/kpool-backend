<?php

declare(strict_types=1);

namespace Tests\PHPStan;

use Kpool\PHPStan\Rules\UseCaseContractRule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<UseCaseContractRule> */
class UseCaseContractRuleTest extends RuleTestCase
{
    protected function getRule(): UseCaseContractRule
    {
        require_once __DIR__ . '/../../phpstan/fixtures/use-case-contract.php';

        return new UseCaseContractRule();
    }

    public function testContractsAndInfrastructureImplementationsWhileExcludingPortsAndHelpers(): void
    {
        $publicMethodMessage = 'Use cases must not declare public methods other than process and __construct.';
        $returnMessage = 'Use case process must return void, a ReadModel, or an array of ReadModels.';
        $this->analyse([__DIR__ . '/../../phpstan/fixtures/use-case-contract.php'], [
            [$publicMethodMessage, 19],
            ['Use case process parameter $account must be an Input or Output type.', 29],
            ['Use case process parameter $value must be an Input or Output type.', 29],
            [$returnMessage, 29],
            [$publicMethodMessage, 47],
            [$returnMessage, 53],
            [$returnMessage, 58],
        ]);
    }
}
