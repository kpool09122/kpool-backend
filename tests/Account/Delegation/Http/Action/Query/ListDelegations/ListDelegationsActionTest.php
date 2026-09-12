<?php

declare(strict_types=1);

namespace Tests\Account\Delegation\Http\Action\Query\ListDelegations;

use Application\Http\Action\Account\Delegation\Query\ListDelegations\ListDelegationsAction;
use Application\Http\Action\Account\Delegation\Query\ListDelegations\ListDelegationsRequest;
use Application\Http\Context\AccountContext;
use Mockery;
use Psr\Log\LoggerInterface;
use Source\Account\Delegation\Application\Exception\DisallowedDelegationOperationException;
use Source\Account\Delegation\Application\UseCase\Query\ListDelegations\ListDelegationsInput;
use Source\Account\Delegation\Application\UseCase\Query\ListDelegations\ListDelegationsInterface;
use Source\Account\Delegation\Application\UseCase\Query\ListDelegations\ListDelegationsOutput;
use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Symfony\Component\HttpFoundation\Response;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ListDelegationsActionTest extends TestCase
{
    public function testRequestRulesMatchQueryContract(): void
    {
        $this->assertSame(['status', 'viewerRole', 'perPage', 'page'], array_keys((new ListDelegationsRequest())->rules()));
    }

    public function testUsesAccountContextPrincipalAndReturnsPagination(): void
    {
        $principal = $this->principal();
        $request = $this->request('pending', 'approver', 25, 2);
        /** @var ListDelegationsInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(ListDelegationsInterface::class);
        $useCase->shouldReceive('process')->once()->with(
            Mockery::on(fn ($input) => $input instanceof ListDelegationsInput && $input->principal() === $principal && $input->status() === 'pending' && $input->viewerRole() === 'approver' && $input->perPage() === 25 && $input->page() === 2),
            Mockery::on(function ($output): bool {
                if (! $output instanceof ListDelegationsOutput) {
                    return false;
                }
                $output->output([], 2, 3, 51, 25);

                return true;
            }),
        );
        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldNotReceive('error');
        $response = (new ListDelegationsAction($useCase, $this->context($principal), $logger))($request);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame(51, $response->getData(true)['total']);
    }

    public function testReturnsForbiddenWhenPolicyDenies(): void
    {
        $principal = $this->principal();
        $request = $this->request(null, null, null, 1);
        /** @var ListDelegationsInterface&Mockery\MockInterface $useCase */
        $useCase = Mockery::mock(ListDelegationsInterface::class);
        $useCase->shouldReceive('process')->once()->andThrow(new DisallowedDelegationOperationException());
        /** @var LoggerInterface&Mockery\MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once();
        $response = (new ListDelegationsAction($useCase, $this->context($principal), $logger))($request);
        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    /** @return ListDelegationsRequest&Mockery\MockInterface */
    private function request(?string $status, ?string $viewerRole, ?int $perPage, int $page): ListDelegationsRequest
    {
        /** @var ListDelegationsRequest&Mockery\MockInterface $request */
        $request = Mockery::mock(ListDelegationsRequest::class);
        $request->shouldReceive('status')->andReturn($status);
        $request->shouldReceive('viewerRole')->andReturn($viewerRole);
        $request->shouldReceive('perPage')->andReturn($perPage);
        $request->shouldReceive('page')->andReturn($page);
        $request->shouldReceive('language')->andReturn('en');

        return $request;
    }

    private function context(Principal $principal): AccountContext
    {
        return new AccountContext($principal, AccountType::CORPORATION, AccountCategory::AGENCY);
    }

    private function principal(): Principal
    {
        return new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), new IdentityIdentifier(StrTestHelper::generateUuid()), new AccountIdentifier(StrTestHelper::generateUuid()));
    }
}
