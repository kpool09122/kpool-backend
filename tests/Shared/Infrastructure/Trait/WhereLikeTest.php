<?php

declare(strict_types=1);

namespace Tests\Shared\Infrastructure\Trait;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Mockery;
use Source\Shared\Infrastructure\Trait\WhereLike;
use Tests\TestCase;

class WhereLikeTest extends TestCase
{
    /**
     * 正常系: 通常の文字列がLIKE検索用にラップされること.
     *
     * @return void
     */
    public function testWhereLikeWithNormalValue(): void
    {
        $subject = $this->createSubject();
        $query = Mockery::mock(QueryBuilder::class);
        $query->shouldReceive('where')
            ->once()
            ->with('name', 'LIKE', '%test%')
            ->andReturnSelf();

        $subject->whereLike($query, 'name', 'test');
    }

    /**
     * 正常系: %がエスケープされること.
     *
     * @return void
     */
    public function testWhereLikeEscapesPercent(): void
    {
        $subject = $this->createSubject();
        $query = Mockery::mock(QueryBuilder::class);
        $query->shouldReceive('where')
            ->once()
            ->with('name', 'LIKE', '%100\%%')
            ->andReturnSelf();

        $subject->whereLike($query, 'name', '100%');
    }

    /**
     * 正常系: _がエスケープされること.
     *
     * @return void
     */
    public function testWhereLikeEscapesUnderscore(): void
    {
        $subject = $this->createSubject();
        $query = Mockery::mock(QueryBuilder::class);
        $query->shouldReceive('where')
            ->once()
            ->with('name', 'LIKE', '%test\_value%')
            ->andReturnSelf();

        $subject->whereLike($query, 'name', 'test_value');
    }

    /**
     * 正常系: \がエスケープされること.
     *
     * @return void
     */
    public function testWhereLikeEscapesBackslash(): void
    {
        $subject = $this->createSubject();
        $query = Mockery::mock(QueryBuilder::class);
        $query->shouldReceive('where')
            ->once()
            ->with('name', 'LIKE', '%test\\\\value%')
            ->andReturnSelf();

        $subject->whereLike($query, 'name', 'test\\value');
    }

    public function testWhereStartsWith(): void
    {
        $subject = $this->createSubject();
        $query = Mockery::mock(QueryBuilder::class);
        $query->shouldReceive('where')
            ->once()
            ->with('name', 'LIKE', 'test%')
            ->andReturnSelf();

        $subject->whereStartsWith($query, 'name', 'test');
    }

    public function testWhereStartsWithEscapesLikeWildcards(): void
    {
        $subject = $this->createSubject();
        $query = Mockery::mock(QueryBuilder::class);
        $query->shouldReceive('where')
            ->once()
            ->with('name', 'LIKE', '100\%\_test%')
            ->andReturnSelf();

        $subject->whereStartsWith($query, 'name', '100%_test');
    }

    /**
     * @return object traitを使用するオブジェクト
     */
    private function createSubject(): object
    {
        return new class () {
            use WhereLike;
        };
    }
}
