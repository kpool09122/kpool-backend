<?php

declare(strict_types=1);

namespace Source\Wiki\Grading\Application\UseCase\Command\ProcessRolePromotion;

use Source\Wiki\Grading\Domain\ValueObject\YearMonth;

readonly class ProcessRolePromotionInput implements ProcessRolePromotionInputPort
{
    public function __construct(
        private YearMonth $yearMonth,
    ) {
    }

    public function yearMonth(): YearMonth
    {
        return $this->yearMonth;
    }
}
