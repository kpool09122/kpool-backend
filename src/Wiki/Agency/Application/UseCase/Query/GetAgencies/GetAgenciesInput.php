<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Query\GetAgencies;

use Source\Shared\Domain\ValueObject\Language;

readonly class GetAgenciesInput implements GetAgenciesInputPort
{
    public function __construct(
        private int      $limit,
        private string   $order,
        private string   $sort,
        private string   $searchWords,
        private Language $language,
    ) {
    }

    public function limit(): int
    {
        return $this->limit;
    }

    public function order(): string
    {
        return $this->order;
    }

    public function sort(): string
    {
        return $this->sort;
    }

    public function searchWords(): string
    {
        return $this->searchWords;
    }

    public function language(): Language
    {
        return $this->language;
    }
}
