<?php

declare(strict_types=1);

namespace Kpool\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/** @implements Rule<Node> */
final class ForbiddenLogFacadeRule implements Rule
{
    public function getNodeType(): string
    {
        return Node::class;
    }

    /** @return list<IdentifierRuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        if (str_contains(str_replace('\\', '/', $scope->getFile()), '/application/Jobs/')) {
            return [];
        }

        if (! ($node instanceof StaticCall || $node instanceof ClassConstFetch || $node instanceof New_ || $node instanceof Instanceof_) || ! $node->class instanceof Name) {
            return [];
        }

        $className = strtolower(RuleSupport::resolveName($node->class, $scope));
        if (! in_array($className, ['illuminate\\support\\facades\\log', 'log'], true)) {
            return [];
        }

        return [RuleErrorBuilder::message('Log Facade is forbidden outside Jobs. Use Psr\\Log\\LoggerInterface instead.')
            ->identifier('kpool.logFacade')->build()];
    }
}
