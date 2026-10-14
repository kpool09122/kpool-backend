<?php

namespace Fixtures\DatabaseQueries;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\DB as Database;

/** @param EloquentBuilder<Model> $eloquent
 * @param Collection<int, Model> $collection
 */
function queries(ConnectionInterface $connection, EloquentBuilder $eloquent, Collection $collection, Builder $query, JoinClause $join, string $method): void
{
    DB::table('accounts');
    Database::select('SELECT 1');
    \Illuminate\Support\Facades\DB::raw('COUNT(*)');
    \DB::table('accounts');
    DB::connection();
    DB::$method();
    $connection->table('accounts');
    $connection->select('SELECT 1');
    new Builder($connection);
    $eloquent->toBase();
    $eloquent->getQuery();
    $query->get();
    $query->insert(['id' => 1]);

    DB::transaction(static fn () => null);
    DB::beginTransaction();
    DB::commit();
    DB::rollBack();
    DB::transactionLevel();
    DB::afterCommit(static fn () => null);
    DB::afterRollBack(static fn () => null);
    $connection->transaction(static fn () => null);
    $connection->beginTransaction();
    $connection->commit();
    $connection->rollBack();
    $connection->transactionLevel();
    $eloquent->get();
    $eloquent->where('id', 1);
    $collection->toBase();
    $query->whereColumn('account_id', 'accounts.id');
    $join->on('a.id', '=', 'b.id');
}
