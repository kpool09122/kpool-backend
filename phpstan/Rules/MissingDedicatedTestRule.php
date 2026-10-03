<?php

declare(strict_types=1);

namespace Kpool\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/** @implements Rule<InClassNode> */
final class MissingDedicatedTestRule implements Rule
{
    public function __construct(
        private readonly string $sourceDirectory,
        private readonly string $testsDirectory,
    ) {
    }

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /** @return list<IdentifierRuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        $sourceDirectory = rtrim(str_replace('\\', '/', $this->sourceDirectory), '/') . '/';
        if (! str_starts_with(str_replace('\\', '/', $scope->getFile()), $sourceDirectory)) {
            return [];
        }

        $classReflection = $node->getClassReflection();
        if ($classReflection->isAnonymous() || $classReflection->isInterface()
            || $classReflection->isTrait() || $classReflection->isAbstract()) {
            return [];
        }

        $className = $classReflection->getName();
        if (! str_starts_with($className, 'Source\\')) {
            return [];
        }
        $category = $this->category($className);
        if ($category === null) {
            return [];
        }

        $relativeTestPath = str_replace('\\', '/', substr($className, strlen('Source\\'))) . 'Test.php';
        if (is_file(rtrim($this->testsDirectory, '/\\') . '/' . $relativeTestPath)) {
            return [];
        }

        return [RuleErrorBuilder::message(sprintf(
            '%s %s must have a dedicated test: tests/%s.',
            $category,
            $className,
            $relativeTestPath,
        ))->identifier('kpool.missingTest')->build()];
    }

    private function category(string $className): ?string
    {
        if (str_contains($className, '\\Application\\UseCase\\')) {
            foreach (['Input', 'Output', 'ReadModel'] as $category) {
                if (str_ends_with($className, $category)) {
                    return $category;
                }
            }
        }

        $namespaceParts = array_slice(explode('\\', $className), 0, -1);
        foreach (['Factory', 'Service', 'Repository', 'ValueObject', 'Entity'] as $category) {
            if (in_array($category, $namespaceParts, true)) {
                return $category;
            }
        }

        return null;
    }
}
