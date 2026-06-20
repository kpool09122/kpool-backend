<?php

declare(strict_types=1);

namespace Tests\Application\Http\Action\SiteManagement\Contact\Command\SubmitContact;

use Application\Http\Action\SiteManagement\Contact\Command\SubmitContact\SubmitContactAction;
use Application\Http\Action\SiteManagement\Contact\Command\SubmitContact\SubmitContactRequest;
use Illuminate\Support\Facades\DB;
use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\SiteManagement\Contact\Application\UseCase\Command\SubmitContact\SubmitContactInput;
use Source\SiteManagement\Contact\Application\UseCase\Command\SubmitContact\SubmitContactInterface;
use Source\SiteManagement\Contact\Application\UseCase\Command\SubmitContact\SubmitContactOutputPort;
use Source\SiteManagement\Contact\Domain\Entity\Contact;
use Source\SiteManagement\Contact\Domain\ValueObject\Category;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactName;
use Source\SiteManagement\Contact\Domain\ValueObject\Content;
use Symfony\Component\HttpFoundation\Response;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SubmitContactActionTest extends TestCase
{
    public function testInvokeReturnsCreatedContact(): void
    {
        $contactIdentifier = new ContactIdentifier(StrTestHelper::generateUuid());
        $contact = new Contact(
            $contactIdentifier,
            null,
            Category::SUGGESTIONS,
            new ContactName('問い合わせ太郎'),
            new Email('john.doe@example.com'),
            new Content('お問い合わせ内容'),
        );

        /** @var SubmitContactRequest&MockInterface $request */
        $request = Mockery::mock(SubmitContactRequest::class);
        $request->shouldReceive('category')->once()->andReturn(Category::SUGGESTIONS->value);
        $request->shouldReceive('name')->once()->andReturn('問い合わせ太郎');
        $request->shouldReceive('email')->once()->andReturn('john.doe@example.com');
        $request->shouldReceive('content')->once()->andReturn('お問い合わせ内容');

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();

        /** @var SubmitContactInterface&MockInterface $submitContact */
        $submitContact = Mockery::mock(SubmitContactInterface::class);
        $submitContact->shouldReceive('process')
            ->once()
            ->with(
                Mockery::on(static function (mixed $input): bool {
                    return $input instanceof SubmitContactInput
                        && $input->identityIdentifier() === null
                        && $input->category() === Category::SUGGESTIONS
                        && (string) $input->name() === (string) new ContactName('問い合わせ太郎')
                        && (string) $input->email() === (string) new Email('john.doe@example.com')
                        && (string) $input->content() === (string) new Content('お問い合わせ内容');
                }),
                Mockery::on(static function (mixed $output) use ($contact): bool {
                    if (! $output instanceof SubmitContactOutputPort) {
                        return false;
                    }

                    $output->setContact($contact);

                    return true;
                }),
            )
            ->andReturnNull();

        /** @var LoggerInterface&MockInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldNotReceive('error');

        $action = new SubmitContactAction($submitContact, $logger);
        $response = $action($request);

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertSame([
            'contactIdentifier' => (string) $contactIdentifier,
            'identityIdentifier' => null,
            'category' => Category::SUGGESTIONS->value,
            'name' => '問い合わせ太郎',
            'email' => 'john.doe@example.com',
            'content' => 'お問い合わせ内容',
        ], $response->getData(true));
    }
}
