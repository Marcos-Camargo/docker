<?php

use PHPUnit\Framework\TestCase;
use App\libraries\Queue\Workers\BaseWorker;
use App\Jobs\GenericJob;
use App\Libraries\Queue\JobFileWatcher;
use Tests\Fakes\FunctionMockTrait;

class ConcreteWorker extends BaseWorker
{
    // Implement the abstract method from WorkerInterface
    public function run()
    {
        // Empty implementation for testing
    }

    // Expose protected methods for testing
    public function exposedJobHasChanged($instance): bool
    {
        return $this->jobHasChanged($instance);
    }

    public function exposedSleepAndFlush(int $seconds = 0): void
    {
        $this->sleepAndFlush($seconds);
    }
}

class BaseWorkerTest extends TestCase
{
    use FunctionMockTrait;
    private $worker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->worker = new ConcreteWorker();

        // Define getenv function if needed
        if (!function_exists('getenv')) {
            function getenv($name) {
                if ($name === 'MODE_DEBUG') {
                    return true;
                }
                return false;
            }
        }
    }

    public function testJobHasChangedReturnsTrueWhenFileChanged()
    {
        // Create a mock for JobFileWatcher
        $watcherMock = $this->createMock(JobFileWatcher::class);
        $watcherMock->method('check')
            ->willReturn(false); // File has changed

        // Create a mock for JobFileWatcher::getInstance()
        $staticWatcherMock = $this->getMockBuilder('stdClass')
            ->addMethods(['getInstance'])
            ->getMock();
        $staticWatcherMock->method('getInstance')
            ->willReturn($watcherMock);

        // Replace the JobFileWatcher class with our mock
        $reflectionClass = new ReflectionClass(JobFileWatcher::class);
        $instanceProperty = $reflectionClass->getProperty('instance');
        $instanceProperty->setAccessible(true);
        $instanceProperty->setValue(null, $watcherMock);

        // Create a mock for GenericJob
        $jobMock = $this->createMock(GenericJob::class);

        // Test
        $result = $this->worker->exposedJobHasChanged($jobMock);

        $this->assertTrue($result);
    }

    public function testJobHasChangedReturnsFalseWhenFileUnchanged()
    {
        // Create a mock for JobFileWatcher
        $watcherMock = $this->createMock(JobFileWatcher::class);
        $watcherMock->method('check')
            ->willReturn(true); // File has not changed

        // Create a mock for JobFileWatcher::getInstance()
        $staticWatcherMock = $this->getMockBuilder('stdClass')
            ->addMethods(['getInstance'])
            ->getMock();
        $staticWatcherMock->method('getInstance')
            ->willReturn($watcherMock);

        // Replace the JobFileWatcher class with our mock
        $reflectionClass = new ReflectionClass(JobFileWatcher::class);
        $instanceProperty = $reflectionClass->getProperty('instance');
        $instanceProperty->setAccessible(true);
        $instanceProperty->setValue(null, $watcherMock);

        // Create a mock for GenericJob
        $jobMock = $this->createMock(GenericJob::class);

        // Test
        $result = $this->worker->exposedJobHasChanged($jobMock);

        $this->assertFalse($result);
    }

    public function testSleepAndFlushWithZeroSeconds()
    {
        // We can't easily test the sleep function, but we can verify it doesn't error
        $this->worker->exposedSleepAndFlush(0);
        $this->assertTrue(true); // If we got here, no exception was thrown
    }

}
