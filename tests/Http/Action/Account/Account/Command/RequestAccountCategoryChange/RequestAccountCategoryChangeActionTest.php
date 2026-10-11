<?php

declare(strict_types=1);

namespace Tests\Http\Action\Account\Account\Command\RequestAccountCategoryChange;

use Application\Http\Action\Account\Account\Command\RequestAccountCategoryChange\RequestAccountCategoryChangeAction;
use Application\Http\Context\AccountContext;
use Application\Http\Context\ActorContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestAlreadyPendingException;
use Source\Account\Account\Application\Exception\IncompleteAccountContactForCategoryChangeException;
use Source\Account\Account\Application\Exception\InvalidDocumentsForVerificationException;
use Source\Account\Account\Application\Exception\SameAccountCategoryChangeRequestException;
use Source\Account\Account\Application\UseCase\Command\RequestAccountCategoryChange\RequestAccountCategoryChangeInterface;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RequestAccountCategoryChangeActionTest extends TestCase
{
    /** @return iterable<string, array{RuntimeException, string, string}> */
    public static function errorCases(): iterable
    {
        $errors = [
            'incomplete_account_contact_for_category_change' => new IncompleteAccountContactForCategoryChangeException(),
            'invalid_documents_for_category_change' => new InvalidDocumentsForVerificationException('Required account documents are missing.'),
            'same_account_category_change_request' => new SameAccountCategoryChangeRequestException(),
            'account_category_change_request_already_pending' => new AccountCategoryChangeRequestAlreadyPendingException(),
        ];
        foreach (['ja', 'en', 'ko'] as $language) {
            foreach ($errors as $key => $exception) {
                yield $language.' '.$key => [$exception, $key, $language];
            }
        }
    }

    #[DataProvider('errorCases')]
    public function testReturnsLocalizedActionableErrors(RuntimeException $exception, string $key, string $language): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $principal = new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), $identityIdentifier, $accountIdentifier);
        $this->app()->instance(AccountContext::class, new AccountContext($principal, AccountType::INDIVIDUAL, AccountStatus::ACTIVE, AccountCategory::GENERAL));
        // The UI request language must take precedence over the saved identity language.
        $this->app()->instance(ActorContext::class, new ActorContext($identityIdentifier, Language::ENGLISH));
        $requestAccountCategoryChange = $this->createMock(RequestAccountCategoryChangeInterface::class);
        $requestAccountCategoryChange->expects($this->once())->method('process')->willThrowException($exception);
        $this->app()->instance(RequestAccountCategoryChangeInterface::class, $requestAccountCategoryChange);
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();
        DB::shouldReceive('commit')->never();
        Route::post('/test/accounts/{accountIdentifier}/category-change-requests', RequestAccountCategoryChangeAction::class);
        $expected = error_message($key, $language);
        $this->assertNotSame('errors.'.$key, $expected);
        $this->assertNotSame($exception->getMessage(), $expected);
        $this->postJson('/test/accounts/'.(string) $accountIdentifier.'/category-change-requests', [
            'requestedAccountCategory' => 'agency',
        ], ['Accept-Language' => $language])->assertUnprocessable()->assertJsonPath('detail', $expected);
    }
}
