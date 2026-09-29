<?php

declare(strict_types=1);

namespace Kpool\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Use_;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FileNode;
use PHPStan\PhpDocParser\Ast\Node as PhpDocNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/** @implements Rule<FileNode> */
final class RequireImportedNameRule implements Rule
{
    private readonly Lexer $lexer;
    private readonly PhpDocParser $phpDocParser;

    public function __construct()
    {
        $config = new ParserConfig(['lines' => true]);
        $constExprParser = new ConstExprParser($config);
        $this->lexer = new Lexer($config);
        $this->phpDocParser = new PhpDocParser($config, new TypeParser($config, $constExprParser), $constExprParser);
    }

    public function getNodeType(): string
    {
        return FileNode::class;
    }

    /**
     * @param FileNode $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $errors = [];
        foreach ($node->getNodes() as $statement) {
            $this->inspect($statement, $errors);
        }

        return array_values($errors);
    }

    /** @param array<string, IdentifierRuleError> $errors */
    private function inspect(Node $node, array &$errors): void
    {
        if ($node instanceof Use_ || $node instanceof GroupUse) {
            return;
        }
        if ($node instanceof Name) {
            $originalName = $node->getAttribute('originalName', $node);
            if ($originalName instanceof FullyQualified) {
                $this->report($originalName->toCodeString(), $originalName->getStartLine(), $errors);
            }
        }
        $comment = $node->getDocComment();
        if ($comment !== null) {
            $phpDoc = $this->phpDocParser->parse(new TokenIterator($this->lexer->tokenize($comment->getText())));
            $this->inspectPhpDoc($phpDoc, $comment->getStartLine(), $errors);
        }
        foreach ($node->getSubNodeNames() as $key) {
            $children = $node->$key;
            foreach (is_array($children) ? $children : [$children] as $child) {
                if ($child instanceof Node) {
                    $this->inspect($child, $errors);
                }
            }
        }
    }

    /** @param array<string, IdentifierRuleError> $errors */
    private function inspectPhpDoc(PhpDocNode $node, int $startLine, array &$errors): void
    {
        if ($node instanceof IdentifierTypeNode && str_starts_with($node->name, '\\')) {
            $relativeLine = $node->getAttribute('startLine');
            $this->report($node->name, $startLine + (is_int($relativeLine) ? $relativeLine - 1 : 0), $errors);
        }
        foreach (get_object_vars($node) as $children) {
            foreach (is_array($children) ? $children : [$children] as $child) {
                if ($child instanceof PhpDocNode) {
                    $this->inspectPhpDoc($child, $startLine, $errors);
                }
            }
        }
    }

    /** @param array<string, IdentifierRuleError> $errors */
    private function report(string $name, int $line, array &$errors): void
    {
        $errors[$line . ':' . $name] = RuleErrorBuilder::message(sprintf(
            'Import %s with use and reference its short name.',
            $name,
        ))->identifier('kpool.requireImportedName')->line($line)->build();
    }
}
