<?php

declare(strict_types=1);

namespace Tests\PHPStan;

use Kpool\PHPStan\Rules\MissingDedicatedTestRule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<MissingDedicatedTestRule> */
class MissingDedicatedTestRuleTest extends RuleTestCase
{
    protected function getRule(): MissingDedicatedTestRule
    {
        $fixturesDirectory = dirname(__DIR__, 2) . '/phpstan/fixtures/test-presence';
        require_once $fixturesDirectory . '/src/targets.php';
        require_once $fixturesDirectory . '/src-sibling/outside.php';

        return new MissingDedicatedTestRule($fixturesDirectory . '/src', $fixturesDirectory . '/tests');
    }

    public function testAllTargetCategoriesWhileExcludingHttpClassesRulesPortsAndAbstractClasses(): void
    {
        $this->analyse([dirname(__DIR__, 2) . '/phpstan/fixtures/test-presence/src/targets.php'], [
            ['Input Source\\TestPresence\\Application\\UseCase\\Command\\Operation\\OperationInput must have a dedicated test: tests/TestPresence/Application/UseCase/Command/Operation/OperationInputTest.php.', 7],
            ['Output Source\\TestPresence\\Application\\UseCase\\Command\\Operation\\OperationOutput must have a dedicated test: tests/TestPresence/Application/UseCase/Command/Operation/OperationOutputTest.php.', 8],
            ['ReadModel Source\\TestPresence\\Application\\UseCase\\Query\\ExampleReadModel must have a dedicated test: tests/TestPresence/Application/UseCase/Query/ExampleReadModelTest.php.', 15],
            ['Factory Source\\TestPresence\\Infrastructure\\Factory\\ExampleFactory must have a dedicated test: tests/TestPresence/Infrastructure/Factory/ExampleFactoryTest.php.', 19],
            ['Service Source\\TestPresence\\Domain\\Service\\Generator must have a dedicated test: tests/TestPresence/Domain/Service/GeneratorTest.php.', 25],
            ['Repository Source\\TestPresence\\Infrastructure\\Repository\\ExampleRepository must have a dedicated test: tests/TestPresence/Infrastructure/Repository/ExampleRepositoryTest.php.', 32],
            ['ValueObject Source\\TestPresence\\Domain\\ValueObject\\Action must have a dedicated test: tests/TestPresence/Domain/ValueObject/ActionTest.php.', 37],
            ['Entity Source\\TestPresence\\Domain\\Entity\\ExampleEntity must have a dedicated test: tests/TestPresence/Domain/Entity/ExampleEntityTest.php.', 43],
        ]);
    }

    public function testSourceDirectoryDoesNotMatchSiblingDirectories(): void
    {
        $this->analyse([dirname(__DIR__, 2) . '/phpstan/fixtures/test-presence/src-sibling/outside.php'], []);
    }
}
