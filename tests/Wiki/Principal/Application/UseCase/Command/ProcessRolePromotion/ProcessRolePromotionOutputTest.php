<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Application\UseCase\Command\ProcessRolePromotion;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Principal\Application\UseCase\Command\ProcessRolePromotion\ProcessRolePromotionOutput;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;

class ProcessRolePromotionOutputTest extends TestCase
{
    /**
     * 正常系: 初期状態で空の配列が返されること.
     *
     * @return void
     */
    public function testInitialState(): void
    {
        $output = new ProcessRolePromotionOutput();

        $this->assertSame([], $output->promoted());
        $this->assertSame([], $output->demoted());
        $this->assertSame([], $output->warned());
    }

    /**
     * 正常系: setPromotedで昇格者が設定され、promotedで取得できること.
     *
     * @return void
     */
    public function testSetPromotedAndPromoted(): void
    {
        $output = new ProcessRolePromotionOutput();
        $promoted = [
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
        ];

        $output->setPromoted($promoted);

        $this->assertSame($promoted, $output->promoted());
    }

    /**
     * 正常系: setDemotedで降格者が設定され、demotedで取得できること.
     *
     * @return void
     */
    public function testSetDemotedAndDemoted(): void
    {
        $output = new ProcessRolePromotionOutput();
        $demoted = [
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
        ];

        $output->setDemoted($demoted);

        $this->assertSame($demoted, $output->demoted());
    }

    /**
     * 正常系: setWarnedで警告者が設定され、warnedで取得できること.
     *
     * @return void
     */
    public function testSetWarnedAndWarned(): void
    {
        $output = new ProcessRolePromotionOutput();
        $warned = [
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
        ];

        $output->setWarned($warned);

        $this->assertSame($warned, $output->warned());
    }
}
