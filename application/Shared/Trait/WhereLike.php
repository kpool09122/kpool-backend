<?php

declare(strict_types=1);

namespace Application\Shared\Trait;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

trait WhereLike
{
    /**
     * @param EloquentBuilder|QueryBuilder $query
     * @param string $column
     * @param string $value
     * @return EloquentBuilder|QueryBuilder
     */
    public function whereLike(EloquentBuilder|QueryBuilder $query, string $column, string $value): EloquentBuilder|QueryBuilder
    {
        return $query->where($column, 'LIKE', '%' . addcslashes($value, '%_\\') . '%');
    }
}
