<?php

declare(strict_types=1);

namespace Kpool\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Type;

/** @implements Rule<ClassMethod> */
final class UseCaseContractRule implements Rule
{
    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    /**
     * @param ClassMethod $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null || ! $this->isUseCase($classReflection) || ! $node->isPublic()) {
            return [];
        }
        $methodName = $node->name->toString();
        if (strtolower($methodName) === '__construct') {
            return [];
        }
        if ($methodName !== 'process') {
            return [RuleErrorBuilder::message('Use cases must not declare public methods other than process and __construct.')
                ->identifier('kpool.useCasePublicMethod')->build()];
        }
        $errors = [];
        $variant = $classReflection->getNativeMethod($methodName)->getVariants()[0];
        foreach ($variant->getParameters() as $parameter) {
            if (! $this->isPort($parameter->getType())) {
                $errors[] = RuleErrorBuilder::message(sprintf(
                    'Use case process parameter $%s must be an Input or Output type.',
                    $parameter->getName(),
                ))->identifier('kpool.useCaseParameter')->build();
            }
        }
        if ($node->returnType === null || ! $this->isReturnType($variant->getReturnType())) {
            $errors[] = RuleErrorBuilder::message('Use case process must return void, a ReadModel, or an array of ReadModels.')
                ->identifier('kpool.useCaseReturnType')->build();
        }

        return $errors;
    }

    private function isUseCase(ClassReflection $classReflection): bool
    {
        if ($this->isUseCaseName($classReflection->getName())) {
            return true;
        }
        foreach ($classReflection->getInterfaces() as $interface) {
            if ($this->isUseCaseName($interface->getName())) {
                return true;
            }
        }

        return false;
    }

    private function isUseCaseName(string $className): bool
    {
        return preg_match('/^Source\\\\.*\\\\Application\\\\UseCase\\\\(?:Command|Query)\\\\([^\\\\]+)\\\\\1(?:Interface)?$/', $className) === 1;
    }

    private function isPort(Type $type): bool
    {
        $classNames = $type->getObjectClassNames();

        return $type->isObject()->yes()
            && count($classNames) === 1
            && preg_match('/^Source\\\\.*\\\\Application\\\\UseCase\\\\(?:Command|Query)\\\\[^\\\\]+\\\\[^\\\\]+(?:Input|Output)(?:Port)?$/', $classNames[0]) === 1;
    }

    private function isReturnType(Type $type): bool
    {
        if ($type->isVoid()->yes()) {
            return true;
        }
        if ($type->isArray()->yes()) {
            return $this->isReadModel($type->getIterableValueType());
        }

        return $this->isReadModel($type);
    }

    private function isReadModel(Type $type): bool
    {
        $classNames = $type->getObjectClassNames();

        return $type->isObject()->yes()
            && count($classNames) === 1
            && preg_match('/^Source\\\\.*\\\\Application\\\\UseCase\\\\Query\\\\.*ReadModel$/', $classNames[0]) === 1;
    }
}
