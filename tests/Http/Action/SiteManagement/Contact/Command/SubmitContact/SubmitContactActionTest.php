<?php

declare(strict_types=1);

namespace Tests\Http\Action\SiteManagement\Contact\Command\SubmitContact;

use Application\Http\Action\SiteManagement\Contact\Command\SubmitContact\SubmitContactAction;
use Application\Http\Action\SiteManagement\Contact\Command\SubmitContact\SubmitContactRequest;
use Application\Http\Context\AccountContext;
use Application\Http\Context\AccountResolver;
use Application\Http\Context\SiteManagementPrincipalResolver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\NullLogger;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Contact\Application\UseCase\Command\SubmitContact\SubmitContactInputPort;
use Source\SiteManagement\Contact\Application\UseCase\Command\SubmitContact\SubmitContactInterface;
use Source\SiteManagement\Contact\Application\UseCase\Command\SubmitContact\SubmitContactOutputPort;
use Source\SiteManagement\Contact\Domain\Entity\Contact;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SubmitContactActionTest extends TestCase
{
    /** @return array<string, array{bool}> */
    public static function authenticationCases(): array
    {
        return ['signed in' => [true], 'anonymous' => [false]];
    }

    #[DataProvider('authenticationCases')]
    public function testSubmissionUsesResolvedPrincipalOrNull(bool $authenticated): void
    {
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = $authenticated ? new PrincipalIdentifier(StrTestHelper::generateUuid()) : null;
        Auth::shouldReceive('check')->once()->andReturn($authenticated);
        $accountResolver = $this->createMock(AccountResolver::class);
        $siteManagementPrincipalResolver = $this->createMock(SiteManagementPrincipalResolver::class);
        if ($authenticated) {
            Auth::shouldReceive('id')->once()->andReturn((string) $identityIdentifier);
            $accountContext = $this->createStub(AccountContext::class);
            $accountResolver->expects($this->once())->method('resolve')->with($identityIdentifier)->willReturn($accountContext);
            $siteManagementPrincipalResolver->expects($this->once())->method('resolve')->with($accountContext)->willReturn($principalIdentifier);
        } else {
            $accountResolver->expects($this->never())->method('resolve');
            $siteManagementPrincipalResolver->expects($this->never())->method('resolve');
        }
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        $submitContact = $this->createMock(SubmitContactInterface::class);
        $submitContact->expects($this->once())->method('process')->willReturnCallback(
            function (SubmitContactInputPort $input, SubmitContactOutputPort $output) use ($principalIdentifier): void {
                $this->assertSame($principalIdentifier, $input->principalIdentifier());
                $output->setContact(new Contact(
                    new ContactIdentifier(StrTestHelper::generateUuid()),
                    $input->principalIdentifier(),
                    $input->category(),
                    $input->name(),
                    $input->email(),
                    $input->content(),
                    $input->language(),
                ));
            },
        );
        $request = SubmitContactRequest::create('/api/site-management/contact/submit/v1', 'POST', [
            'category' => 1,
            'name' => 'User',
            'email' => 'user@example.com',
            'content' => 'Inquiry',
            'principalIdentifier' => StrTestHelper::generateUuid(),
        ]);
        $response = (new SubmitContactAction($submitContact, new NullLogger(), $accountResolver, $siteManagementPrincipalResolver))($request);
        $this->assertSame(201, $response->getStatusCode());
        $body = $response->getData(true);
        $this->assertIsArray($body);
        $this->assertSame($principalIdentifier === null ? null : (string) $principalIdentifier, $body['principalIdentifier']);
        $this->assertArrayNotHasKey('identityIdentifier', $body);
    }
}
