<?php

declare(strict_types=1);

namespace Tests\Jobs;

use Application\Jobs\ExecuteTransferJob;
use Application\Jobs\SyncPayoutAccountJob;
use Application\Jobs\Wiki\CollectVideoLinksJob;
use Application\Mail\PasskeyRecoveryCodeMail;
use Aws\CommandInterface;
use Aws\MockHandler;
use Aws\Result;
use Aws\Sqs\SqsClient;
use Illuminate\Queue\Connectors\ConnectorInterface;
use Illuminate\Queue\SqsQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Psr\Http\Message\RequestInterface;
use RuntimeException;
use Source\Monetization\Settlement\Domain\ValueObject\TransferIdentifier;
use Source\Shared\Domain\ValueObject\Language;
use Tests\TestCase;

class SqsQueueBoundaryTest extends TestCase
{
    private const string URL = 'https://sqs.ap-northeast-1.amazonaws.com/123456789012/kpool-prod-work-v1';

    /** @var list<CommandInterface> */
    private array $commands = [];

    private ?MockHandler $handler = null;

    private ?SqsQueue $sqsQueue = null;

    /** @var array<string, string|null> */
    private array $originalEnvironment = [];

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['APP_ENV', 'QUEUE_CONNECTION', 'SQS_QUEUE_URL'] as $key) {
            $value = $_ENV[$key] ?? null;
            self::assertTrue($value === null || is_string($value));
            $this->originalEnvironment[$key] = $value;
        }
        // Resolve the real production config; substitute only the SDK transport.
        $_ENV['APP_ENV'] = 'production';
        $_ENV['QUEUE_CONNECTION'] = 'sqs';
        $_ENV['SQS_QUEUE_URL'] = self::URL;
        config(['queue' => require __DIR__ . '/../../config/queue.php', 'cache.default' => 'array']);
        $this->handler = new MockHandler();
        $client = new SqsClient([
            'version' => 'latest', 'region' => 'ap-northeast-1',
            'credentials' => ['key' => 'test', 'secret' => 'test'],
            'handler' => function (CommandInterface $command, RequestInterface $request) {
                $this->commands[] = $command;

                return ($this->sdkHandler())($command, $request);
            },
        ]);
        $connection = config('queue.connections.sqs');
        self::assertIsArray($connection);
        self::assertIsString($connection['queue']);
        self::assertIsString($connection['prefix']);
        self::assertIsString($connection['suffix']);
        self::assertIsBool($connection['after_commit']);
        $this->sqsQueue = new SqsQueue($client, $connection['queue'], $connection['prefix'], $connection['suffix'], $connection['after_commit']);
        $this->sqsQueue->setContainer($this->app());
        $this->sqsQueue->setConnectionName('sqs');
        Queue::addConnector('sqs', fn () => new class ($this->queue()) implements ConnectorInterface {
            public function __construct(private SqsQueue $sqsQueue)
            {
            }

            /** @param array<string, mixed> $config */
            public function connect(array $config): SqsQueue
            {
                return $this->sqsQueue;
            }
        });
    }

    protected function tearDown(): void
    {
        foreach ($this->originalEnvironment as $key => $value) {
            if ($value === null) {
                unset($_ENV[$key]);
            } else {
                $_ENV[$key] = $value;
            }
        }
        parent::tearDown();
    }

    public function testAllProducersUseTheProvisionedWorkQueue(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->sdkHandler()->append(new Result(['MessageId' => 'message-' . $i]));
        }
        Mail::to('recipient@example.com')->queue(new PasskeyRecoveryCodeMail(Language::JAPANESE, '123456'));
        CollectVideoLinksJob::dispatch();
        SyncPayoutAccountJob::dispatch('acct_test', 'ba_test', 'account.external_account.updated');
        ExecuteTransferJob::dispatch(new TransferIdentifier('00000000-0000-7000-8000-000000000001'));
        self::assertCount(4, $this->commands);
        foreach ($this->commands as $command) {
            self::assertSame('SendMessage', $command->getName());
            self::assertSame(self::URL, $command['QueueUrl']);
        }
        self::assertIsString($this->commands[3]['MessageBody']);
        $payload = json_decode($this->commands[3]['MessageBody'], true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);
        self::assertSame(3, $payload['maxTries']);
        self::assertSame('60', $payload['backoff']);
    }

    public function testReceiveDeleteAndReleaseUseSdkReceiptAndVisibility(): void
    {
        $this->sdkHandler()->append(new Result(['MessageId' => 'message']));
        Queue::push(new ExecuteTransferJob(new TransferIdentifier('00000000-0000-7000-8000-000000000001')));
        $body = $this->commands[0]['MessageBody'];
        $this->sdkHandler()->append(new Result(['Messages' => [['MessageId' => 'message', 'ReceiptHandle' => 'receipt', 'Body' => $body, 'Attributes' => ['ApproximateReceiveCount' => '2']]]]));
        $job = $this->queue()->pop();
        self::assertNotNull($job);
        self::assertSame(2, $job->attempts());
        $this->sdkHandler()->append(new Result());
        $job->release(60);
        self::assertSame('ChangeMessageVisibility', $this->commands[2]->getName());
        self::assertSame(60, $this->commands[2]['VisibilityTimeout']);
        $this->sdkHandler()->append(new Result());
        $job->delete();
        self::assertSame('DeleteMessage', $this->commands[3]->getName());
        self::assertSame('receipt', $this->commands[3]['ReceiptHandle']);
        self::assertSame(self::URL, $this->commands[1]['QueueUrl']);
        self::assertSame(self::URL, $this->commands[3]['QueueUrl']);
    }

    public function testPermanentFailureDeletesMessageRatherThanAutomaticallyRedriving(): void
    {
        $this->sdkHandler()->append(new Result(['MessageId' => 'message']));
        Queue::push(new ExecuteTransferJob(new TransferIdentifier('00000000-0000-7000-8000-000000000001')));
        $body = $this->commands[0]['MessageBody'];
        $this->sdkHandler()->append(new Result(['Messages' => [['MessageId' => 'message', 'ReceiptHandle' => 'receipt', 'Body' => $body, 'Attributes' => ['ApproximateReceiveCount' => '3']]]]));
        $job = $this->queue()->pop();
        self::assertNotNull($job);
        $this->sdkHandler()->append(new Result());
        $job->fail(new RuntimeException('transfer failed'));
        self::assertTrue($job->hasFailed());
        self::assertSame('DeleteMessage', $this->commands[2]->getName());
    }

    public function testSdkReceiveFailurePropagatesWithoutDeletion(): void
    {
        $this->sdkHandler()->append(new RuntimeException('receive unavailable'));

        try {
            $this->queue()->pop();
            self::fail('Receive failure must propagate');
        } catch (RuntimeException $exception) {
            self::assertSame('receive unavailable', $exception->getMessage());
        }
        self::assertCount(1, $this->commands);
        self::assertSame('ReceiveMessage', $this->commands[0]->getName());
    }

    public function testSdkDeleteFailurePropagatesForRedelivery(): void
    {
        $this->sdkHandler()->append(new Result(['MessageId' => 'message']));
        Queue::push(new ExecuteTransferJob(new TransferIdentifier('00000000-0000-7000-8000-000000000001')));
        $this->sdkHandler()->append(new Result(['Messages' => [['MessageId' => 'message', 'ReceiptHandle' => 'receipt', 'Body' => $this->commands[0]['MessageBody'], 'Attributes' => ['ApproximateReceiveCount' => '1']]]]));
        $job = $this->queue()->pop();
        self::assertNotNull($job);
        $this->sdkHandler()->append(new RuntimeException('delete unavailable'));
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('delete unavailable');
        $job->delete();
    }

    private function sdkHandler(): MockHandler
    {
        self::assertNotNull($this->handler);

        return $this->handler;
    }

    private function queue(): SqsQueue
    {
        self::assertNotNull($this->sqsQueue);

        return $this->sqsQueue;
    }

    public function testSdkEnqueueFailurePropagates(): void
    {
        $this->sdkHandler()->append(new RuntimeException('SQS unavailable'));
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SQS unavailable');
        Queue::push(new CollectVideoLinksJob());
    }
}
