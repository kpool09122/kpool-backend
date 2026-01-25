<?php

declare(strict_types=1);

namespace Source\Wiki\Grading\Application\UseCase\Command\ProcessRolePromotion;

use Source\Wiki\Grading\Domain\ValueObject\YearMonth;

interface ProcessRolePromotionInputPort
{
    public function yearMonth(): YearMonth;
}
