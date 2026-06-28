<?php

declare(strict_types=1);

namespace Kpool\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Name>
 */
final class ForbiddenReflectionClassInTestRule implements Rule
{
    /**
     * @param list<string> $targetFilePathPatterns
     */
    public function __construct(
        private readonly array $targetFilePathPatterns,
    ) {
    }

    public function getNodeType(): string
    {
        return Name::class;
    }

    /**
     * @param Name $node
     * @param Scope $scope
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->isTargetFile($scope->getFile())) {
            return [];
        }

        $className = RuleSupport::resolveName($node, $scope);

        if (!preg_match('#^Reflection[A-Z]\w*$#', $className)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                'Tests must not use PHP Reflection classes: %s.',
                $className,
            ))
                ->identifier('kpool.reflectionClassInTest')
                ->build(),
        ];
    }

    private function isTargetFile(string $file): bool
    {
        foreach ($this->targetFilePathPatterns as $targetFilePathPattern) {
            if (preg_match($targetFilePathPattern, $file) === 1) {
                return true;
            }
        }

        return false;
    }
}
