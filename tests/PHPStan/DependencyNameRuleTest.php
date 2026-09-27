<?php

declare(strict_types=1);

namespace Tests\PHPStan;

use Kpool\PHPStan\Rules\DependencyNameRule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<DependencyNameRule> */
class DependencyNameRuleTest extends RuleTestCase
{
    protected function getRule(): DependencyNameRule
    {
        return new DependencyNameRule();
    }

    public function testDependencyNames(): void
    {
        $this->analyse([__DIR__ . '/../../phpstan/fixtures/dependency-names.php'], [
            ['Dependency IdentityRepositoryInterface must be named $identityRepository; $identities hides its role.', 9],
            ['Dependency IdentityRepositoryInterface must be named $identityRepository; $repository hides its role.', 12],
            ['Dependency AuthServiceInterface must be named $authService; $auth hides its role.', 13],
            ['Dependency IdentityRepositoryInterface must be named $identityRepository; $repos hides its role.', 16],
        ]);
    }
}
