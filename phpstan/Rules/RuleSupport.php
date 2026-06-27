<?php

declare(strict_types=1);

namespace Kpool\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Trait_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ObjectType;

final class RuleSupport
{
    private function __construct()
    {
    }

    /**
     * @param list<string> $targetMethodNames
     */
    public static function isTargetScope(Scope $scope, string $targetClassNamePattern, array $targetMethodNames): bool
    {
        if ($targetMethodNames !== [] && !in_array($scope->getFunctionName(), $targetMethodNames, true)) {
            return false;
        }

        $classReflection = $scope->getClassReflection();

        if ($classReflection === null) {
            return false;
        }

        $className = $classReflection->getName();

        return $targetClassNamePattern === '' || preg_match($targetClassNamePattern, $className) === 1;
    }

    public static function resolveName(Name $name, Scope $scope): string
    {
        return mb_ltrim($scope->resolveName($name), '\\');
    }

    /**
     * @return list<string>
     */
    public static function catchTypes(Catch_ $catch, Scope $scope): array
    {
        $catchTypes = [];

        foreach ($catch->types as $type) {
            $catchTypes[] = self::resolveName($type, $scope);
        }

        return $catchTypes;
    }

    /**
     * @param list<string> $configuredTypes
     */
    public static function isConfiguredType(string $type, array $configuredTypes): bool
    {
        $normalizedType = mb_ltrim(mb_trim($type), '\\');

        foreach ($configuredTypes as $configuredType) {
            $normalizedConfiguredType = mb_ltrim(mb_trim($configuredType), '\\');

            if ($normalizedType === $normalizedConfiguredType || $normalizedType === self::shortName($normalizedConfiguredType)) {
                return true;
            }
        }

        return false;
    }

    public static function shortName(string $className): string
    {
        $classNameParts = explode('\\', $className);

        return end($classNameParts) ?: $className;
    }

    public static function isNestedExecutableScope(Node $node): bool
    {
        return $node instanceof Class_
            || $node instanceof ClassMethod
            || $node instanceof Function_
            || $node instanceof Interface_
            || $node instanceof Node\Expr\Closure
            || $node instanceof Node\Expr\ArrowFunction
            || $node instanceof Trait_;
    }

    public static function isTypeOf(string $className, string $parentClassName, ReflectionProvider $reflectionProvider): bool
    {
        if (!$reflectionProvider->hasClass($className)) {
            return false;
        }

        if (!$reflectionProvider->hasClass($parentClassName)) {
            return false;
        }

        return (new ObjectType($parentClassName))->isSuperTypeOf(new ObjectType($className))->yes();
    }
}
