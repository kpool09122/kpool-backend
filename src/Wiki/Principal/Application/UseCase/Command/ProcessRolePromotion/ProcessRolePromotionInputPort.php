<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\ProcessRolePromotion;

use Source\Wiki\Principal\Domain\ValueObject\YearMonth;

interface ProcessRolePromotionInputPort
{
    public function yearMonth(): YearMonth;
}
