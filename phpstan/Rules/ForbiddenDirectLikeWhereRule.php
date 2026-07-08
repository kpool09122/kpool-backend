<?php

declare(strict_types=1);

namespace Kpool\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<MethodCall>
 */
final class ForbiddenDirectLikeWhereRule implements Rule
{
    /**
     * @param list<string> $targetFilePathPatterns
     * @param list<string> $allowedFilePathPatterns
     * @param list<string> $targetMethodNames
     * @param list<string> $allowedFunctionNames
     */
    public function __construct(
        private readonly array $targetFilePathPatterns,
        private readonly array $allowedFilePathPatterns,
        private readonly array $targetMethodNames,
        private readonly array $allowedFunctionNames,
    ) {
    }

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @param MethodCall $node
     * @param Scope $scope
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->isTargetFile($scope->getFile()) || $this->isAllowedFile($scope->getFile())) {
            return [];
        }

        if (in_array($scope->getFunctionName(), $this->allowedFunctionNames, true)) {
            return [];
        }

        if (!$node->name instanceof Identifier) {
            return [];
        }

        if (!in_array($node->name->toString(), $this->targetMethodNames, true)) {
            return [];
        }

        $operatorArg = $node->args[1] ?? null;

        if ($operatorArg === null || !$operatorArg->value instanceof String_) {
            return [];
        }

        if (mb_strtolower($operatorArg->value->value) !== 'like') {
            return [];
        }

        return [
            RuleErrorBuilder::message('LIKE search must use Source\Shared\Infrastructure\Trait\WhereLike instead of direct where/orWhere(..., \'like\', ...).')
                ->identifier('kpool.directLikeWhere')
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

    private function isAllowedFile(string $file): bool
    {
        foreach ($this->allowedFilePathPatterns as $allowedFilePathPattern) {
            if (preg_match($allowedFilePathPattern, $file) === 1) {
                return true;
            }
        }

        return false;
    }
}
