<?php

declare(strict_types=1);

namespace Tests\Account\Application\UseCase\Command\RequestAffiliation;

use PHPUnit\Framework\TestCase;
use Source\Account\Application\UseCase\Command\RequestAffiliation\RequestAffiliationInput;
use Source\Account\Domain\ValueObject\AffiliationTerms;
use Source\Monetization\Shared\ValueObject\Percentage;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\StrTestHelper;

class RequestAffiliationInputTest extends TestCase
{
    /**
     * 正常系: インスタンスが正しく作成できること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $agencyAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $talentAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $requestedBy = $agencyAccountIdentifier;
        $terms = new AffiliationTerms(new Percentage(30), 'Contract notes');

        $input = new RequestAffiliationInput(
            $agencyAccountIdentifier,
            $talentAccountIdentifier,
            $requestedBy,
            $terms,
        );

        $this->assertSame($agencyAccountIdentifier, $input->agencyAccountIdentifier());
        $this->assertSame($talentAccountIdentifier, $input->talentAccountIdentifier());
        $this->assertSame($requestedBy, $input->requestedBy());
        $this->assertSame($terms, $input->terms());
    }

    /**
     * 正常系: termsがnullでもインスタンスが正しく作成できること
     *
     * @return void
     */
    public function test__constructWithNullTerms(): void
    {
        $agencyAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $talentAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $requestedBy = $agencyAccountIdentifier;

        $input = new RequestAffiliationInput(
            $agencyAccountIdentifier,
            $talentAccountIdentifier,
            $requestedBy,
            null,
        );

        $this->assertSame($agencyAccountIdentifier, $input->agencyAccountIdentifier());
        $this->assertSame($talentAccountIdentifier, $input->talentAccountIdentifier());
        $this->assertSame($requestedBy, $input->requestedBy());
        $this->assertNull($input->terms());
    }
}
