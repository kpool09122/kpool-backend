<?php

declare(strict_types=1);

namespace Kpool\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\UnionType;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/** @implements Rule<Node> */
final class DependencyNameRule implements Rule
{
    public function getNodeType(): string
    {
        return Node::class;
    }

    /** @return list<IdentifierRuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        $errors = [];
        if ($node instanceof ClassMethod && strtolower($node->name->toString()) === '__construct') {
            foreach ($node->params as $param) {
                if ($param->var instanceof Variable && is_string($param->var->name)) {
                    $errors = array_merge($errors, $this->check($param->type, $param->var->name, $param->getStartLine(), $scope));
                }
            }
        } elseif ($node instanceof Property) {
            foreach ($node->props as $property) {
                $errors = array_merge($errors, $this->check($node->type, $property->name->toString(), $property->getStartLine(), $scope));
            }
        }

        return $errors;
    }

    /** @return list<IdentifierRuleError> */
    private function check(?Node $type, string $name, int $line, Scope $scope): array
    {
        if ($type instanceof NullableType) {
            $type = $type->type;
        }
        if ($type instanceof UnionType || $type instanceof IntersectionType) {
            $dependencies = array_values(array_filter($type->types, static fn (Node $part): bool => $part instanceof Name
                && preg_match('/(?:Service|Repository)(?:Interface)?$/', RuleSupport::shortName(RuleSupport::resolveName($part, $scope))) === 1));
            $type = count($dependencies) === 1 ? $dependencies[0] : null;
        }
        if (! $type instanceof Name) {
            return [];
        }
        $className = RuleSupport::resolveName($type, $scope);
        $shortName = RuleSupport::shortName($className);
        if (! preg_match('/(?:Service|Repository)(?:Interface)?$/', $shortName)) {
            return [];
        }
        $writtenType = $type->getAttribute('originalName', $type);
        if ($writtenType instanceof Name && preg_match('/(?:Service|Repository)(?:Interface)?$/', $writtenType->getLast())) {
            $shortName = $writtenType->getLast();
        }
        $expected = lcfirst(preg_replace('/Interface$/', '', $shortName) ?? $shortName);
        if ($name === $expected) {
            return [];
        }

        return [RuleErrorBuilder::message(sprintf(
            'Dependency %s must be named $%s; $%s hides its role.',
            $shortName,
            $expected,
            $name,
        ))->identifier('kpool.dependencyName')->line($line)->build()];
    }
}
