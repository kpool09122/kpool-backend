<?php

declare(strict_types=1);

namespace Kpool\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Throwable;

/**
 * @implements Rule<Class_>
 */
final class ExceptionConstructorRule implements Rule
{
    /** @param list<string> $targetClassNamePatterns */
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        private readonly array $targetClassNamePatterns,
    ) {
    }

    public function getNodeType(): string
    {
        return Class_::class;
    }

    /**
     * @param Class_ $node
     * @param Scope $scope
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $className = $node->namespacedName?->toString();
        if ($className === null
            || !RuleSupport::isTypeOf($className, Throwable::class, $this->reflectionProvider)
            || !$this->isTargetClass($className)
        ) {
            return [];
        }

        $constructor = $node->getMethod('__construct');
        if ($constructor === null) {
            return [
                RuleErrorBuilder::message('Domain and application exceptions must declare a constructor with a default message, previous exception, and code 0.')
                    ->identifier('kpool.exceptionConstructor.missing')
                    ->build(),
            ];
        }

        $errors = [];
        $parentConstructorCall = $this->findParentConstructorCall($constructor);

        if (!$this->hasDefaultMessage($constructor, $parentConstructorCall)) {
            $errors[] = RuleErrorBuilder::message('Domain and application exceptions must provide a non-empty default message.')
                ->identifier('kpool.exceptionConstructor.defaultMessage')
                ->build();
        }

        if (!$this->hasPreviousParameter($constructor, $scope)) {
            $errors[] = RuleErrorBuilder::message('Domain and application exceptions must declare a ?Throwable $previous = null parameter.')
                ->identifier('kpool.exceptionConstructor.previousParameter')
                ->build();
        }

        if (!$this->hasZeroCode($parentConstructorCall)) {
            $errors[] = RuleErrorBuilder::message('Domain and application exceptions must pass the literal code 0 to the parent constructor.')
                ->identifier('kpool.exceptionConstructor.zeroCode')
                ->build();
        }

        if (!$this->forwardsPrevious($parentConstructorCall)) {
            $errors[] = RuleErrorBuilder::message('Domain and application exceptions must pass $previous to the parent constructor.')
                ->identifier('kpool.exceptionConstructor.previousForwarding')
                ->build();
        }

        return $errors;
    }

    private function isTargetClass(string $className): bool
    {
        foreach ($this->targetClassNamePatterns as $targetClassNamePattern) {
            if (preg_match($targetClassNamePattern, $className) === 1) {
                return true;
            }
        }

        return false;
    }

    private function findParentConstructorCall(ClassMethod $constructor): ?StaticCall
    {
        $node = (new NodeFinder())->findFirst(
            $constructor->stmts ?? [],
            static fn (Node $node): bool => $node instanceof StaticCall
                && $node->class instanceof Name
                && mb_strtolower($node->class->toString()) === 'parent'
                && $node->name instanceof Node\Identifier
                && mb_strtolower($node->name->toString()) === '__construct',
        );

        return $node instanceof StaticCall ? $node : null;
    }

    private function hasDefaultMessage(ClassMethod $constructor, ?StaticCall $parentConstructorCall): bool
    {
        $messageArgument = $this->argumentAt($parentConstructorCall, 0);
        if ($messageArgument === null) {
            return false;
        }

        if (!$messageArgument->value instanceof Variable || $messageArgument->value->name !== 'message') {
            return !$messageArgument->value instanceof String_ || $messageArgument->value->value !== '';
        }

        foreach ($constructor->params as $parameter) {
            if ($parameter->var instanceof Variable
                && $parameter->var->name === 'message'
                && $parameter->default instanceof String_
                && $parameter->default->value !== ''
            ) {
                return true;
            }
        }

        return false;
    }

    private function hasPreviousParameter(ClassMethod $constructor, Scope $scope): bool
    {
        foreach ($constructor->params as $parameter) {
            if (!$parameter->var instanceof Variable
                || $parameter->var->name !== 'previous'
                || !$parameter->default instanceof ConstFetch
                || mb_strtolower($parameter->default->name->toString()) !== 'null'
                || !$parameter->type instanceof NullableType
                || !$parameter->type->type instanceof Name
            ) {
                continue;
            }

            return RuleSupport::resolveName($parameter->type->type, $scope) === Throwable::class;
        }

        return false;
    }

    private function hasZeroCode(?StaticCall $parentConstructorCall): bool
    {
        $codeArgument = $this->argumentAt($parentConstructorCall, 1);

        return $codeArgument !== null
            && $codeArgument->value instanceof Int_
            && $codeArgument->value->value === 0;
    }

    private function forwardsPrevious(?StaticCall $parentConstructorCall): bool
    {
        $previousArgument = $this->argumentAt($parentConstructorCall, 2);

        return $previousArgument !== null
            && $previousArgument->value instanceof Variable
            && $previousArgument->value->name === 'previous';
    }

    private function argumentAt(?StaticCall $parentConstructorCall, int $position): ?Arg
    {
        if ($parentConstructorCall === null) {
            return null;
        }

        $argument = $parentConstructorCall->args[$position] ?? null;

        return $argument instanceof Arg ? $argument : null;
    }
}
