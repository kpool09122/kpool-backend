<?php

declare(strict_types=1);

namespace Tests\Http\Action\SiteManagement\Contact\Query;

use Application\Http\Action\SiteManagement\Contact\Query\GetContactDetail\GetContactDetailAction;
use Application\Http\Action\SiteManagement\Contact\Query\GetContactDetail\GetContactDetailRequest;
use Application\Http\Action\SiteManagement\Contact\Query\GetMyContactDetail\GetMyContactDetailAction;
use Application\Http\Action\SiteManagement\Contact\Query\GetMyContactDetail\GetMyContactDetailRequest;
use Application\Http\Context\ActorContext;
use Application\Http\Context\SiteManagementContext;
use Application\Http\Exceptions\Handler;
use Application\Http\Exceptions\HttpException;
use Database\Seeders\SiteManagementAuthorizationSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Psr\Log\NullLogger;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipalInput;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipalInterface;
use Source\SiteManagement\Principal\Application\UseCase\Command\ProvisionPrincipal\ProvisionPrincipalOutput;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\SiteManagementAuthorization;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class ContactDetailErrorsTest extends TestCase
{
    /** @return array<string, array{bool, bool, int, string}> */
    public static function cases(): array
    {
        return [
            'my missing' => [true, false, 404, 'contact_not_found'],
            'my policy denied' => [true, true, 403, 'unauthorized'],
            'my other owner' => [true, true, 404, 'contact_not_found'],
            'admin missing' => [false, false, 404, 'contact_not_found'],
            'general denied other owner' => [false, true, 403, 'unauthorized'],
        ];
    }

    #[DataProvider('cases')]
    public function testRealQueryErrorsRenderLocalizedHttpResponses(bool $mine, bool $exists, int $status, string $message): void
    {
        $this->app()->make(SiteManagementAuthorizationSeeder::class)->run();

        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $account);
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identity);
        $output = new ProvisionPrincipalOutput();
        $this->app()->make(ProvisionPrincipalInterface::class)->process(new ProvisionPrincipalInput($identity, $account), $output);
        $principal = $output->principal();
        $this->assertNotNull($principal);
        $this->app()->instance(ActorContext::class, new ActorContext($identity, Language::ENGLISH));
        $this->app()->instance(SiteManagementContext::class, new SiteManagementContext($principal->principalIdentifier()));
        if (! $mine && ! $exists) {
            SiteManagementAuthorization::grantAdministrator($principal);
        }
        $contact = StrTestHelper::generateUuid();
        $owner = $mine && $status === 403 ? (string) $identity : StrTestHelper::generateUuid();
        if ($mine && $status === 403) {
            DB::table('site_management_policies')->where('id', SiteManagementAuthorizationSeeder::GENERAL_ROLE)->update(['statements' => json_encode([['effect' => 'deny', 'actions' => ['contact:view'], 'resource_types' => ['contact'], 'condition' => 'own_contact']], JSON_THROW_ON_ERROR)]);
        }
        if ($exists) {
            DB::table('contacts')->insert(['id' => $contact, 'identity_identifier' => $owner, 'category' => 1, 'name' => 'Other', 'email' => 'encrypted', 'content' => 'history', 'language' => 'ja']);
        }
        $uri = '/identities/'.$owner.'/contacts/'.$contact;
        $request = $mine ? GetMyContactDetailRequest::create($uri, 'GET') : GetContactDetailRequest::create($uri, 'GET');
        $route = new Route('GET', '/identities/{identityIdentifier}/contacts/{contactIdentifier}', static fn () => null);
        $route->bind($request);
        $request->setRouteResolver(static fn (): Route => $route);
        $action = $this->app()->make($mine ? GetMyContactDetailAction::class : GetContactDetailAction::class);

        try {
            $action($request);
            $this->fail('Expected HTTP error');
        } catch (HttpException $exception) {
            $jsonRequest = Request::create($uri, 'GET', server: ['HTTP_ACCEPT' => 'application/json']);
            $response = (new Handler(new NullLogger()))($exception, $jsonRequest);
            $this->assertInstanceOf(JsonResponse::class, $response);
            $this->assertSame($status, $response->getStatusCode());
            $data = $response->getData(true);
            $this->assertIsArray($data);
            $this->assertSame(error_message($message, 'en'), $data['detail']);
        }
    }
}
