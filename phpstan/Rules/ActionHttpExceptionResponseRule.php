<?php

declare(strict_types=1);

namespace Kpool\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Cast\String_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\TryCatch;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<ClassMethod>
 */
final class ActionHttpExceptionResponseRule implements Rule
{
    /**
     * @param list<string> $targetMethodNames Empty list means all methods.
     * @param list<string> $broadCatchTypes
     * @param list<string> $clientErrorHttpExceptionClasses
     */
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        private readonly string $targetClassNamePattern,
        private readonly array $targetMethodNames,
        private readonly array $broadCatchTypes,
        private readonly array $clientErrorHttpExceptionClasses,
        private readonly string $internalServerErrorHttpExceptionClass
    ) {
    }

    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    /**
     * @param ClassMethod $node
     * @param Scope $scope
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->isTargetMethod($node, $scope)) {
            return [];
        }

        $outerTryCatches = $this->outerTryCatches($node->stmts ?? []);

        if ($outerTryCatches === []) {
            return [
                RuleErrorBuilder::message('Action __invoke must wrap processing in try/catch and end with defensive Throwable catch.')
                    ->identifier('actionHttpExceptionResponse.missingTryCatch')
                    ->line($node->getStartLine())
                    ->build(),
            ];
        }

        $errors = [];

        foreach ($outerTryCatches as $tryCatch) {
            foreach ($tryCatch->catches as $catch) {
                if (!$this->catchesClientErrorHttpException($catch, $scope)) {
                    continue;
                }

                if (!$this->catchLogsException($catch)) {
                    $errors[] = RuleErrorBuilder::message('4xx HTTP exception catches in Actions must log with $this->logger->error((string) $e).')
                        ->identifier('actionHttpExceptionResponse.clientErrorNotLogged')
                        ->line($catch->getStartLine())
                        ->build();
                }

                if (!$this->catchReturnsProblemDetailsJson($catch)) {
                    $errors[] = RuleErrorBuilder::message('4xx HTTP exception catches in Actions must return response()->json($e->toProblemDetails(), $e->getHttpStatus()).')
                        ->identifier('actionHttpExceptionResponse.clientErrorNotReturnedAsProblemDetails')
                        ->line($catch->getStartLine())
                        ->build();
                }
            }

            $lastCatch = $tryCatch->catches[array_key_last($tryCatch->catches)] ?? null;

            if (!$lastCatch instanceof Catch_ || !$this->catchesBroadException($lastCatch, $scope)) {
                $errors[] = RuleErrorBuilder::message('Action try/catch must end with a defensive Throwable catch.')
                    ->identifier('actionHttpExceptionResponse.missingFinalThrowableCatch')
                    ->line($tryCatch->getStartLine())
                    ->build();

                continue;
            }

            if (!$this->catchLogsException($lastCatch)) {
                $errors[] = RuleErrorBuilder::message('Final Throwable catches in Actions must log with $this->logger->error((string) $e).')
                    ->identifier('actionHttpExceptionResponse.throwableNotLogged')
                    ->line($lastCatch->getStartLine())
                    ->build();
            }

            if (!$this->catchThrowsInternalServerError($lastCatch, $scope)) {
                $errors[] = RuleErrorBuilder::message(sprintf(
                    'Final Throwable catches in Actions must throw %s.',
                    $this->internalServerErrorHttpExceptionClass
                ))
                    ->identifier('actionHttpExceptionResponse.throwableNotConvertedToInternalServerError')
                    ->line($lastCatch->getStartLine())
                    ->build();
            }
        }

        return $errors;
    }

    private function isTargetMethod(ClassMethod $method, Scope $scope): bool
    {
        if ($this->targetMethodNames !== [] && !in_array($method->name->toString(), $this->targetMethodNames, true)) {
            return false;
        }

        $classReflection = $scope->getClassReflection();

        if ($classReflection === null) {
            return false;
        }

        $className = $classReflection->getName();

        return $this->targetClassNamePattern === '' || preg_match($this->targetClassNamePattern, $className) === 1;
    }

    /**
     * @param array<Node> $nodes
     * @return list<TryCatch>
     */
    private function outerTryCatches(array $nodes): array
    {
        $tryCatches = [];

        foreach ($nodes as $node) {
            if ($node instanceof TryCatch) {
                $tryCatches[] = $node;
                continue;
            }

            if (RuleSupport::isNestedExecutableScope($node)) {
                continue;
            }

            foreach ($node->getSubNodeNames() as $subNodeName) {
                $subNode = $node->{$subNodeName};

                if ($subNode instanceof Node) {
                    foreach ($this->outerTryCatches([$subNode]) as $tryCatch) {
                        $tryCatches[] = $tryCatch;
                    }
                }

                if (!is_array($subNode)) {
                    continue;
                }

                foreach ($subNode as $item) {
                    if (!$item instanceof Node) {
                        continue;
                    }

                    foreach ($this->outerTryCatches([$item]) as $tryCatch) {
                        $tryCatches[] = $tryCatch;
                    }
                }
            }
        }

        return $tryCatches;
    }

    private function catchesClientErrorHttpException(Catch_ $catch, Scope $scope): bool
    {
        foreach (RuleSupport::catchTypes($catch, $scope) as $catchType) {
            foreach ($this->clientErrorHttpExceptionClasses as $clientErrorHttpExceptionClass) {
                if ($catchType === mb_ltrim($clientErrorHttpExceptionClass, '\\')) {
                    return true;
                }

                if (RuleSupport::shortName($catchType) === RuleSupport::shortName($clientErrorHttpExceptionClass)) {
                    return true;
                }

                if (RuleSupport::isTypeOf($catchType, $clientErrorHttpExceptionClass, $this->reflectionProvider)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function catchesBroadException(Catch_ $catch, Scope $scope): bool
    {
        foreach (RuleSupport::catchTypes($catch, $scope) as $catchType) {
            if (RuleSupport::isConfiguredType($catchType, $this->broadCatchTypes)) {
                return true;
            }
        }

        return false;
    }

    private function catchLogsException(Catch_ $catch): bool
    {
        $variableName = $this->catchVariableName($catch);

        if ($variableName === null) {
            return false;
        }

        foreach ($this->nodes($catch->stmts) as $node) {
            if (!$node instanceof MethodCall) {
                continue;
            }

            if (!$this->isIdentifier($node->name, 'error')) {
                continue;
            }

            if (!$node->var instanceof PropertyFetch || !$this->isIdentifier($node->var->name, 'logger')) {
                continue;
            }

            if (!$node->var->var instanceof Variable || $node->var->var->name !== 'this') {
                continue;
            }

            $firstArg = $node->args[0] ?? null;

            if (!$firstArg instanceof Arg || !$firstArg->value instanceof String_) {
                continue;
            }

            if ($this->isVariableNamed($firstArg->value->expr, $variableName)) {
                return true;
            }
        }

        return false;
    }

    private function catchReturnsProblemDetailsJson(Catch_ $catch): bool
    {
        $variableName = $this->catchVariableName($catch);

        if ($variableName === null) {
            return false;
        }

        foreach ($this->nodes($catch->stmts) as $node) {
            if (!$node instanceof Return_ || !$node->expr instanceof MethodCall) {
                continue;
            }

            $jsonCall = $node->expr;

            if (!$this->isIdentifier($jsonCall->name, 'json')) {
                continue;
            }

            if (!$jsonCall->var instanceof FuncCall || !$jsonCall->var->name instanceof Name) {
                continue;
            }

            if ($jsonCall->var->name->toString() !== 'response') {
                continue;
            }

            $bodyArg = $jsonCall->args[0] ?? null;
            $statusArg = $jsonCall->args[1] ?? null;

            if (!$bodyArg instanceof Arg || !$statusArg instanceof Arg) {
                continue;
            }

            if (!$this->isMethodCallOnVariable($bodyArg->value, $variableName, 'toProblemDetails')) {
                continue;
            }

            if ($this->isMethodCallOnVariable($statusArg->value, $variableName, 'getHttpStatus')) {
                return true;
            }
        }

        return false;
    }

    private function catchThrowsInternalServerError(Catch_ $catch, Scope $scope): bool
    {
        foreach ($this->nodes($catch->stmts) as $node) {
            if (!$node instanceof Throw_ || !$node->expr instanceof New_) {
                continue;
            }

            $new = $node->expr;

            if (!$new->class instanceof Name) {
                continue;
            }

            $exceptionClass = RuleSupport::resolveName($new->class, $scope);

            if (
                $exceptionClass !== $this->internalServerErrorHttpExceptionClass
                && RuleSupport::shortName($exceptionClass) !== RuleSupport::shortName($this->internalServerErrorHttpExceptionClass)
                && !RuleSupport::isTypeOf($exceptionClass, $this->internalServerErrorHttpExceptionClass, $this->reflectionProvider)
            ) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * @param array<Node> $nodes
     * @return list<Node>
     */
    private function nodes(array $nodes): array
    {
        $collected = [];

        foreach ($nodes as $node) {
            if (RuleSupport::isNestedExecutableScope($node)) {
                continue;
            }

            $collected[] = $node;

            foreach ($node->getSubNodeNames() as $subNodeName) {
                $subNode = $node->{$subNodeName};

                if ($subNode instanceof Node) {
                    foreach ($this->nodes([$subNode]) as $child) {
                        $collected[] = $child;
                    }
                }

                if (!is_array($subNode)) {
                    continue;
                }

                foreach ($subNode as $item) {
                    if (!$item instanceof Node) {
                        continue;
                    }

                    foreach ($this->nodes([$item]) as $child) {
                        $collected[] = $child;
                    }
                }
            }
        }

        return $collected;
    }

    private function catchVariableName(Catch_ $catch): ?string
    {
        if (!$catch->var instanceof Variable || !is_string($catch->var->name)) {
            return null;
        }

        return $catch->var->name;
    }

    private function isIdentifier(Node|string $node, string $name): bool
    {
        return $node instanceof Identifier && $node->toString() === $name;
    }

    private function isMethodCallOnVariable(Expr $expr, string $variableName, string $methodName): bool
    {
        return $expr instanceof MethodCall
            && $this->isIdentifier($expr->name, $methodName)
            && $this->isVariableNamed($expr->var, $variableName);
    }

    private function isVariableNamed(Expr $expr, string $variableName): bool
    {
        return $expr instanceof Variable && $expr->name === $variableName;
    }
}
