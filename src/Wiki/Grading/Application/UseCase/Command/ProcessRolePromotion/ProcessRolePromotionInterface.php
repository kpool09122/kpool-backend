<?php

declare(strict_types=1);

namespace Source\Wiki\Grading\Application\UseCase\Command\ProcessRolePromotion;

interface ProcessRolePromotionInterface
{
    public function process(
        ProcessRolePromotionInputPort $input,
        ProcessRolePromotionOutputPort $output,
    ): void;
}
