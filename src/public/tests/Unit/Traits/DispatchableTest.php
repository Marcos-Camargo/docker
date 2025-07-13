<?php

use PHPUnit\Framework\TestCase;
use App\Libraries\Queue\Traits\Dispatchable;
use App\Libraries\Queue\QueueDispatcher;
use App\libraries\Enum\QueueEnum;
use Tests\Fakes\FunctionMockTrait;

// Test class that uses the Dispatchable trait
class TestDispatchableJob
{
    use Dispatchable;

    public $param1;
    public $param2;

    public function __construct($param1 = null, $param2 = null)
    {
        $this->param1 = $param1;
        $this->param2 = $param2;
    }

    public function setQueueName($name)
    {
        $this->queueName = $name;
    }

    public function getQueueName()
    {
        return $this->queueName ?? QueueEnum::DEFAULT;
    }

    public function setAvailableAt($time)
    {
        $this->availableAt = $time;
    }

    public function getAvailableAt()
    {
        return $this->availableAt ?? null;
    }

    public function setRetryDelaySeconds($seconds)
    {
        $this->retryDelaySeconds = $seconds;
    }

    public function getRetryDelaySeconds()
    {
        return $this->retryDelaySeconds ?? 0;
    }

    public function setReservedTimeoutSeconds($seconds)
    {
        $this->reservedTimeoutSeconds = $seconds;
    }

    public function getReservedTimeoutSeconds()
    {
        return $this->reservedTimeoutSeconds ?? 0;
    }

    public function setMaxAttempts($attempts)
    {
        $this->maxAttempts = $attempts;
    }

    public function getMaxAttempts()
    {
        return $this->maxAttempts ?? 0;
    }

    public function handle()
    {
        // Do nothing
    }
}

class DispatchableTest extends TestCase
{
    use FunctionMockTrait;

    private $ciMock;
    private $configMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock CodeIgniter instance
        $this->ciMock = $this->getMockBuilder(stdClass::class)
            ->addMethods(['load', 'config'])
            ->getMock();

        // Mock config
        $this->configMock = $this->getMockBuilder(stdClass::class)
            ->addMethods(['item'])
            ->getMock();

        // Setup CI instance to return our mocks
        $this->ciMock->load = $this->getMockBuilder(stdClass::class)
            ->addMethods(['config', 'model'])
            ->getMock();
        $this->ciMock->config = $this->configMock;

        // Mock get_instance function
        $this->getFunctionMock('App\Libraries\Queue\Traits', 'get_instance')
            ->expects($this->any())
            ->method('__invoke')
            ->willReturn($this->ciMock);

        // Setup expectations for load->config
        $this->ciMock->load->expects($this->any())
            ->method('config')
            ->with('queue');

        // Setup expectations for config->item
        $this->configMock->expects($this->any())
            ->method('item')
            ->with('default_driver')
            ->willReturn('sync');
    }

    public function testDelayMethodSetsAvailableAt()
    {
        $job = new TestDispatchableJob();
        $result = $job->delay(60);

        // Verify availableAt was set to a future time
        $availableAt = $job->getAvailableAt();
        $this->assertNotNull($availableAt);
        $this->assertGreaterThan(date('Y-m-d H:i:s'), $availableAt);
        $this->assertSame($job, $result);
    }

    public function testDelayRetryMethodSetsRetryDelaySeconds()
    {
        $job = new TestDispatchableJob();
        $result = $job->delayRetry(120);

        // Verify retryDelaySeconds was set
        $this->assertEquals(120, $job->getRetryDelaySeconds());
        $this->assertSame($job, $result);
    }

    public function testReservedTimeoutMethodSetsReservedTimeoutSeconds()
    {
        $job = new TestDispatchableJob();
        $result = $job->reservedTimeout(180);

        // Verify reservedTimeoutSeconds was set
        $this->assertEquals(180, $job->getReservedTimeoutSeconds());
        $this->assertSame($job, $result);
    }

    public function testMaxAttemptsMethodSetsMaxAttempts()
    {
        $job = new TestDispatchableJob();
        $result = $job->maxAttempts(5);

        // Verify maxAttempts was set
        $this->assertEquals(5, $job->getMaxAttempts());
        $this->assertSame($job, $result);
    }

    public function testWasSentToQueueReturnsSentToQueueProperty()
    {
        $job = new TestDispatchableJob();

        // Initially false
        $this->assertFalse($job->wasSentToQueue());

        // Set to true
        $this->setProtectedProperty($job, 'sentToQueue', true);
        $this->assertTrue($job->wasSentToQueue());
    }

    public function testMarkAsQueuedSetsSentToQueueToTrue()
    {
        $job = new TestDispatchableJob();

        // Initially false
        $this->assertFalse($this->getProtectedProperty($job, 'sentToQueue'));

        // Mark as queued
        $job->markAsQueued();

        // Now true
        $this->assertTrue($this->getProtectedProperty($job, 'sentToQueue'));
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

    /**
     * Helper method to set protected properties using reflection
     */
    private function setProtectedProperty($object, $propertyName, $value)
    {
        $reflection = new ReflectionClass(get_class($object));
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }

    /**
     * Helper method to call protected methods using reflection
     */
    private function callProtectedMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
}
