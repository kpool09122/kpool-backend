<?php

declare(strict_types=1);

namespace Kpool\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;

/** @implements Rule<Node> */
final class ForbiddenValueObjectInRequestRule implements Rule
{
    public function __construct(private readonly FileTypeMapper $fileTypeMapper)
    {
    }

    public function getNodeType(): string
    {
        return Node::class;
    }

    /** @return list<IdentifierRuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null || preg_match('/^Application\\\\Http\\\\Action\\\\.*Request$/', $classReflection->getName()) !== 1) {
            return [];
        }
        $errors = [];
        if ($node instanceof ClassMethod) {
            $this->inspectNativeType($node->returnType, $scope, $errors);
            foreach ($node->params as $param) {
                $this->inspectNativeType($param->type, $scope, $errors);
            }
        } elseif ($node instanceof Property) {
            $this->inspectNativeType($node->type, $scope, $errors);
        } elseif ($node instanceof Return_ && $node->expr !== null) {
            $this->inspectType($scope->getType($node->expr), $node->getStartLine(), $errors);
        }
        if ($node instanceof ClassMethod || $node instanceof Property) {
            $comment = $node->getDocComment();
            if ($comment !== null) {
                $phpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
                    $scope->getFile(),
                    $classReflection->getName(),
                    null,
                    $node instanceof ClassMethod ? $node->name->toString() : null,
                    $comment->getText(),
                );
                foreach (array_merge($phpDoc->getParamTags(), $phpDoc->getVarTags()) as $tag) {
                    $this->inspectType($tag->getType(), $comment->getStartLine(), $errors);
                }
                $returnTag = $phpDoc->getReturnTag();
                if ($returnTag !== null) {
                    $this->inspectType($returnTag->getType(), $comment->getStartLine(), $errors);
                }
            }
        }

        return array_values($errors);
    }

    /** @param array<string, IdentifierRuleError> $errors */
    private function inspectNativeType(?Node $node, Scope $scope, array &$errors): void
    {
        if ($node === null) {
            return;
        }
        if ($node instanceof Name) {
            $this->report(RuleSupport::resolveName($node, $scope), $node->getStartLine(), $errors);

            return;
        }
        foreach ($node->getSubNodeNames() as $key) {
            $children = $node->$key;
            foreach (is_array($children) ? $children : [$children] as $child) {
                if ($child instanceof Node) {
                    $this->inspectNativeType($child, $scope, $errors);
                }
            }
        }
    }

    /** @param array<string, IdentifierRuleError> $errors */
    private function inspectType(Type $type, int $line, array &$errors): void
    {
        TypeTraverser::map($type, function (Type $part, callable $traverse) use ($line, &$errors): Type {
            foreach ($part->getObjectClassNames() as $className) {
                $this->report($className, $line, $errors);
            }

            return $traverse($part);
        });
    }

    /** @param array<string, IdentifierRuleError> $errors */
    private function report(string $className, int $line, array &$errors): void
    {
        if (preg_match('/^Source\\\\.*\\\\ValueObject\\\\/', $className) !== 1) {
            return;
        }
        $errors[$line . ':' . $className] = RuleErrorBuilder::message(sprintf(
            'Request classes must not expose value objects: %s. Create value objects in the Action.',
            $className,
        ))->identifier('kpool.valueObjectInRequest')->line($line)->build();
    }
}
