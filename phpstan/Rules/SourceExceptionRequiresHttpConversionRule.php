<?php

declare(strict_types=1);

namespace Kpool\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\TryCatch;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<TryCatch>
 */
final class SourceExceptionRequiresHttpConversionRule implements Rule
{
    /**
     * @param list<string> $targetMethodNames Empty list means all methods.
     * @param list<string> $broadCatchTypes
     * @param list<string> $sourceExceptionRegexes
     */
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        private readonly string $targetClassNamePattern,
        private readonly array $targetMethodNames,
        private readonly array $broadCatchTypes,
        private readonly array $sourceExceptionRegexes,
        private readonly string $httpExceptionClass
    ) {
    }

    public function getNodeType(): string
    {
        return TryCatch::class;
    }

    /**
     * @param TryCatch $node
     * @param Scope $scope
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!RuleSupport::isTargetScope($scope, $this->targetClassNamePattern, $this->targetMethodNames)) {
            return [];
        }

        $broadCatch = $this->firstBroadCatch($node, $scope);

        if ($broadCatch === null) {
            return [];
        }

        $missingTypes = [];

        foreach ($this->sourceExceptionTypesThrownByNodes($node->stmts, $scope) as $exceptionType) {
            if ($this->isConvertedByCatchBefore($node, $broadCatch, $exceptionType, $scope)) {
                continue;
            }

            $missingTypes[] = $exceptionType;
        }

        $missingTypes = array_values(array_unique($missingTypes));

        if ($missingTypes === []) {
            return [];
        }

        sort($missingTypes);

        return [
            RuleErrorBuilder::message(sprintf(
                'Source application/domain exceptions must be caught and converted to %s before broad catch: %s.',
                $this->httpExceptionClass,
                implode(', ', $missingTypes)
            ))
                ->identifier('catch.sourceExceptionNotConvertedToHttp')
                ->line($broadCatch->getStartLine())
                ->build(),
        ];
    }

    private function firstBroadCatch(TryCatch $tryCatch, Scope $scope): ?Catch_
    {
        foreach ($tryCatch->catches as $catch) {
            foreach (RuleSupport::catchTypes($catch, $scope) as $catchType) {
                if (RuleSupport::isConfiguredType($catchType, $this->broadCatchTypes)) {
                    return $catch;
                }
            }
        }

        return null;
    }

    private function isConvertedByCatchBefore(TryCatch $tryCatch, Catch_ $broadCatch, string $exceptionType, Scope $scope): bool
    {
        foreach ($tryCatch->catches as $catch) {
            if ($catch === $broadCatch) {
                return false;
            }

            if (!$this->isCoveredByCatch($exceptionType, RuleSupport::catchTypes($catch, $scope))) {
                continue;
            }

            return $this->catchThrowsHttpException($catch, $scope);
        }

        return false;
    }

    /**
     * @param list<string> $catchTypes
     */
    private function isCoveredByCatch(string $exceptionType, array $catchTypes): bool
    {
        foreach ($catchTypes as $catchType) {
            if ($exceptionType === $catchType) {
                return true;
            }

            if (RuleSupport::shortName($exceptionType) === RuleSupport::shortName($catchType)) {
                return true;
            }

            if (RuleSupport::isTypeOf($exceptionType, $catchType, $this->reflectionProvider)) {
                return true;
            }
        }

        return false;
    }

    private function catchThrowsHttpException(Catch_ $catch, Scope $scope): bool
    {
        foreach ($this->newThrows($catch->stmts) as $throw) {
            $new = $throw->expr;

            if (!$new instanceof New_ || !$new->class instanceof Name) {
                continue;
            }

            $exceptionClass = RuleSupport::resolveName($new->class, $scope);

            if ($exceptionClass === $this->httpExceptionClass) {
                return true;
            }

            if (RuleSupport::isTypeOf($exceptionClass, $this->httpExceptionClass, $this->reflectionProvider)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<Node> $nodes
     * @return list<string>
     */
    private function sourceExceptionTypesThrownByNodes(array $nodes, Scope $scope): array
    {
        $exceptionTypes = [];

        foreach ($nodes as $node) {
            foreach ($this->sourceExceptionTypesThrownByNode($node, $scope) as $exceptionType) {
                $exceptionTypes[] = $exceptionType;
            }
        }

        return array_values(array_unique($exceptionTypes));
    }

    /**
     * @return list<string>
     */
    private function sourceExceptionTypesThrownByNode(Node $node, Scope $scope): array
    {
        if ($node instanceof TryCatch) {
            return $this->sourceExceptionTypesEscapingTryCatch($node, $scope);
        }

        if (RuleSupport::isNestedExecutableScope($node)) {
            return [];
        }

        $exceptionTypes = [];

        if ($node instanceof MethodCall) {
            foreach ($this->methodCallThrowTypes($node, $scope) as $exceptionType) {
                if ($this->isSourceException($exceptionType)) {
                    $exceptionTypes[] = $exceptionType;
                }
            }
        }

        if ($node instanceof Throw_) {
            foreach ($this->directThrowTypes($node, $scope) as $exceptionType) {
                if ($this->isSourceException($exceptionType)) {
                    $exceptionTypes[] = $exceptionType;
                }
            }
        }

        foreach ($node->getSubNodeNames() as $subNodeName) {
            $subNode = $node->{$subNodeName};

            if ($subNode instanceof Node) {
                foreach ($this->sourceExceptionTypesThrownByNode($subNode, $scope) as $exceptionType) {
                    $exceptionTypes[] = $exceptionType;
                }
            }

            if (!is_array($subNode)) {
                continue;
            }

            foreach ($subNode as $item) {
                if (!$item instanceof Node) {
                    continue;
                }

                foreach ($this->sourceExceptionTypesThrownByNode($item, $scope) as $exceptionType) {
                    $exceptionTypes[] = $exceptionType;
                }
            }
        }

        return array_values(array_unique($exceptionTypes));
    }

    /**
     * @return list<string>
     */
    private function sourceExceptionTypesEscapingTryCatch(TryCatch $tryCatch, Scope $scope): array
    {
        $escapingTryTypes = $this->sourceExceptionTypesThrownByNodes($tryCatch->stmts, $scope);
        $escapingCatchTypes = [];

        foreach ($tryCatch->catches as $catch) {
            $catchTypes = RuleSupport::catchTypes($catch, $scope);
            $escapingTryTypes = array_values(array_filter(
                $escapingTryTypes,
                fn (string $exceptionType): bool => !$this->isCoveredByCatch($exceptionType, $catchTypes)
            ));

            foreach ($this->sourceExceptionTypesThrownByNodes($catch->stmts, $scope) as $exceptionType) {
                $escapingCatchTypes[] = $exceptionType;
            }
        }

        $escapingFinallyTypes = $tryCatch->finally === null
            ? []
            : $this->sourceExceptionTypesThrownByNodes($tryCatch->finally->stmts, $scope);

        return array_values(array_unique([
            ...$escapingTryTypes,
            ...$escapingCatchTypes,
            ...$escapingFinallyTypes,
        ]));
    }

    /**
     * @return list<string>
     */
    private function methodCallThrowTypes(MethodCall $methodCall, Scope $scope): array
    {
        if (!$methodCall->name instanceof Identifier) {
            return [];
        }

        $calledOnType = $scope->getType($methodCall->var);
        $methodName = $methodCall->name->toString();

        if (!$calledOnType->hasMethod($methodName)->yes()) {
            return [];
        }

        $throwType = $calledOnType->getMethod($methodName, $scope)->getThrowType();

        if ($throwType === null) {
            return [];
        }

        return $throwType->getObjectClassNames();
    }

    /**
     * @return list<string>
     */
    private function directThrowTypes(Throw_ $throw, Scope $scope): array
    {
        if (!$throw->expr instanceof New_) {
            return [];
        }

        if (!$throw->expr->class instanceof Name) {
            return [];
        }

        return [RuleSupport::resolveName($throw->expr->class, $scope)];
    }

    /**
     * @param array<Node> $nodes
     * @return list<Throw_>
     */
    private function newThrows(array $nodes): array
    {
        $throws = [];

        foreach ($nodes as $node) {
            if (RuleSupport::isNestedExecutableScope($node)) {
                continue;
            }

            if ($node instanceof Throw_ && $node->expr instanceof New_) {
                $throws[] = $node;
                continue;
            }

            foreach ($node->getSubNodeNames() as $subNodeName) {
                $subNode = $node->{$subNodeName};

                if ($subNode instanceof Node) {
                    foreach ($this->newThrows([$subNode]) as $throw) {
                        $throws[] = $throw;
                    }
                }

                if (!is_array($subNode)) {
                    continue;
                }

                foreach ($subNode as $item) {
                    if (!$item instanceof Node) {
                        continue;
                    }

                    foreach ($this->newThrows([$item]) as $throw) {
                        $throws[] = $throw;
                    }
                }
            }
        }

        return $throws;
    }

    private function isSourceException(string $exceptionType): bool
    {
        $normalizedType = mb_ltrim($exceptionType, '\\');

        foreach ($this->sourceExceptionRegexes as $sourceExceptionRegex) {
            if (preg_match($sourceExceptionRegex, $normalizedType) === 1) {
                return true;
            }
        }

        return false;
    }
}
