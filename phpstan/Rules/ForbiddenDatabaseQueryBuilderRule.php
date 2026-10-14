<?php

declare(strict_types=1);

namespace Kpool\PHPStan\Rules;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

/** @implements Rule<Node> */
final class ForbiddenDatabaseQueryBuilderRule implements Rule
{
    private const array TRANSACTION_METHODS = [
        'transaction', 'begintransaction', 'commit', 'rollback', 'transactionlevel',
        'aftercommit', 'afterrollback',
    ];

    private const array QUERY_EXECUTION_METHODS = [
        'get', 'first', 'find', 'value', 'pluck', 'count', 'sum', 'avg', 'average', 'min', 'max',
        'exists', 'doesntexist', 'insert', 'insertgetid', 'insertorignore', 'update', 'updateorinsert',
        'upsert', 'delete', 'truncate', 'increment', 'decrement', 'cursor', 'paginate',
        'simplepaginate', 'cursorpaginate', 'chunk', 'chunkbyid', 'each', 'eachbyid', 'lazy', 'lazybyid', 'sole',
    ];

    public function getNodeType(): string
    {
        return Node::class;
    }

    /** @return list<IdentifierRuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! str_contains(str_replace('\\', '/', $scope->getFile()), '/src/')) {
            return [];
        }

        if ($node instanceof StaticCall && $node->class instanceof Name) {
            $class = strtolower(RuleSupport::resolveName($node->class, $scope));
            if (in_array($class, ['illuminate\\support\\facades\\db', 'db'], true)
                && (! $node->name instanceof Identifier || ! in_array(strtolower($node->name->toString()), self::TRANSACTION_METHODS, true))) {
                return $this->error();
            }
        }

        if ($node instanceof New_ && $node->class instanceof Name
            && (new ObjectType(QueryBuilder::class))->isSuperTypeOf($scope->resolveTypeByName($node->class))->yes()) {
            return $this->error();
        }

        if ($node instanceof MethodCall) {
            $type = $scope->getType($node->var);
            if ((new ObjectType(ConnectionInterface::class))->isSuperTypeOf($type)->yes()
                || (new ObjectType(DatabaseManager::class))->isSuperTypeOf($type)->yes()) {
                if (! $node->name instanceof Identifier || ! in_array(strtolower($node->name->toString()), self::TRANSACTION_METHODS, true)) {
                    return $this->error();
                }
            }
            if ($node->name instanceof Identifier
                && in_array(strtolower($node->name->toString()), ['tobase', 'getquery'], true)
                && (new ObjectType(EloquentBuilder::class))->isSuperTypeOf($type)->yes()) {
                return $this->error();
            }
            if ($node->name instanceof Identifier
                && in_array(strtolower($node->name->toString()), self::QUERY_EXECUTION_METHODS, true)
                && (new ObjectType(QueryBuilder::class))->isSuperTypeOf($type)->yes()) {
                return $this->error();
            }
        }

        return [];
    }

    /** @return list<IdentifierRuleError> */
    private function error(): array
    {
        return [RuleErrorBuilder::message('Database queries in src must use Eloquent. DB facade and connections may only perform transaction operations.')
            ->identifier('kpool.databaseQueryBuilder')->build()];
    }
}
