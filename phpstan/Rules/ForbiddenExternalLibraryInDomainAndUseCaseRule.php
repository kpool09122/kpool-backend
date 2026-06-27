<?php

declare(strict_types=1);

namespace Kpool\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Name>
 */
final class ForbiddenExternalLibraryInDomainAndUseCaseRule implements Rule
{
    /**
     * @param list<string> $targetFilePathPatterns
     * @param list<string> $forbiddenClassNamePatterns
     * @param list<string> $allowedNamespacePrefixes
     * @param list<string> $allowedClassNames
     */
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        private readonly array $targetFilePathPatterns,
        private readonly array $forbiddenClassNamePatterns,
        private readonly array $allowedNamespacePrefixes,
        private readonly array $allowedClassNames,
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

        if (!$this->reflectionProvider->hasClass($className)) {
            return [];
        }

        if ($this->isForbiddenClass($className)) {
            return [
                RuleErrorBuilder::message(sprintf(
                    'Domain layer and use cases must not depend on infrastructure layer: %s.',
                    $className,
                ))
                    ->identifier('kpool.infrastructureDependencyInDomainOrUseCase')
                    ->build(),
            ];
        }

        if ($this->isAllowedClass($className)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                'Domain layer and use cases must not depend on external libraries: %s.',
                $className,
            ))
                ->identifier('kpool.externalLibraryInDomainOrUseCase')
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

    private function isForbiddenClass(string $className): bool
    {
        foreach ($this->forbiddenClassNamePatterns as $forbiddenClassNamePattern) {
            if (preg_match($forbiddenClassNamePattern, $className) === 1) {
                return true;
            }
        }

        return false;
    }

    private function isAllowedClass(string $className): bool
    {
        if (in_array($className, $this->allowedClassNames, true)) {
            return true;
        }

        foreach ($this->allowedNamespacePrefixes as $allowedNamespacePrefix) {
            if (str_starts_with($className, $allowedNamespacePrefix)) {
                return true;
            }
        }

        return false;
    }
}
