<?php

declare(strict_types=1);

namespace Source\Shared\Infrastructure\Trait;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

trait WhereLike
{
    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     * @param EloquentBuilder<TModel>|QueryBuilder $query
     * @param string $column
     * @param string $value
     * @return EloquentBuilder<TModel>|QueryBuilder
     */
    public function whereLike(EloquentBuilder|QueryBuilder $query, string $column, string $value): EloquentBuilder|QueryBuilder
    {
        return $query->where($column, 'LIKE', '%' . addcslashes($value, '%_\\') . '%');
    }
}
