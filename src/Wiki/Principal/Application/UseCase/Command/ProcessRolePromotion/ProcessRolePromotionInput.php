<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\ProcessRolePromotion;

use Source\Wiki\Principal\Domain\ValueObject\YearMonth;

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
