<?php

use PHPUnit\Framework\TestCase;
use App\Libraries\Queue\Pushers\OracleQueuePusher;
use App\libraries\Enum\QueueDriverEnum;
use Tests\Fakes\FunctionMockTrait;

class OracleQueuePusherTest extends TestCase
{
    use FunctionMockTrait;
    private $queueServiceMock;
    private $modelSettingsMock;
    private $pusher;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset static properties
        $reflectionClass = new ReflectionClass(OracleQueuePusher::class);
        $ciProperty = $reflectionClass->getProperty('ci');
        $ciProperty->setAccessible(true);
        $ciProperty->setValue(null, null);

        $queueNameProperty = $reflectionClass->getProperty('queueName');
        $queueNameProperty->setAccessible(true);
        $queueNameProperty->setValue(null, null);

        // Create mocks
        $this->queueServiceMock = $this->getMockBuilder(stdClass::class)
            ->addMethods(['initService', 'sendMessageToQueue'])
            ->getMock();

        $this->modelSettingsMock = $this->getMockBuilder(stdClass::class)
            ->addMethods(['getSettingDatabyName'])
            ->getMock();

        // Define ENVIRONMENT constant if it doesn't exist
        if (!defined('ENVIRONMENT')) {
            define('ENVIRONMENT', 'testing');
        }

        // Setup expectations for modelSettingsMock to allow multiple calls
        $this->modelSettingsMock->expects($this->atLeastOnce())
            ->method('getSettingDatabyName')
            ->with('sellercenter')
            ->willReturn(['value' => 'test_seller']);

        // Allow initService to be called any number of times
        $this->queueServiceMock->expects($this->atLeastOnce())
            ->method('initService')
            ->with('OCI');

        // Create the pusher with mocks
        $this->pusher = new OracleQueuePusher($this->queueServiceMock, $this->modelSettingsMock);
    }

    public function testConstructorWithProvidedDependencies()
    {
        // Verify queue service was set
        $this->assertSame($this->queueServiceMock, $this->getProtectedProperty($this->pusher, 'queueService'));

        // Verify queue name was set
        $this->assertEquals('queue_testing_test_seller', OracleQueuePusher::$queueName);
    }

    public function testPushSendsJobToQueue()
    {
        // Create a mock for the job
        $job = $this->getMockBuilder(stdClass::class)
            ->addMethods(['computeClassHash', 'setDriver'])
            ->getMock();

        // Setup expectations
        $job->expects($this->once())
            ->method('computeClassHash')
            ->willReturn('job_hash');

        $job->expects($this->once())
            ->method('setDriver')
            ->with(QueueDriverEnum::ORACLE);

        // Expect sendToQueue to be called
        $pusherMock = $this->getMockBuilder(OracleQueuePusher::class)
            ->setConstructorArgs([$this->queueServiceMock, $this->modelSettingsMock])
            ->onlyMethods(['sendToQueue'])
            ->getMock();

        $pusherMock->expects($this->once())
            ->method('sendToQueue')
            ->with($job);

        // Call push method
        $pusherMock->push($job);
    }

    public function testSendToQueueCallsQueueService()
    {
        // Create a mock for the job
        $job = $this->getMockBuilder(stdClass::class)
            ->getMock();

        // Expect queue service's sendMessageToQueue to be called
        $this->queueServiceMock->expects($this->once())
            ->method('sendMessageToQueue')
            ->with('queue_testing_test_seller', $job);

        // Call sendToQueue method
        $this->pusher->sendToQueue($job);
    }

    /**
     * Helper method to get protected properties using reflection
     */
    private function getProtectedProperty($object, $propertyName)
    {
        $reflection = new ReflectionClass(get_class($object));
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        return $property->getValue($object);
    }
}
