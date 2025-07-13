<?php

use PHPUnit\Framework\TestCase;
use App\Libraries\Queue\Workers\DatabaseWorker;
use App\Libraries\Queue\JobFileWatcher;

class DatabaseWorkerTest extends TestCase
{
    private $jobModelMock;
    private $worker;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a mock for the job model
        $this->jobModelMock = $this->getMockBuilder(stdClass::class)
            ->addMethods(['getNextJob', 'unlock', 'delete', 'moveToFailedQueue'])
            ->getMock();

        // Create a DatabaseWorker with the mock job model
        $this->worker = new DatabaseWorker($this->jobModelMock, ['default', 'high']);

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

    public function testConstructorSetsProperties()
    {
        $this->assertSame($this->jobModelMock, $this->worker->jobModel);
        $this->assertEquals(['default', 'high'], $this->worker->queues);
    }

    public function testFetchNextJobReturnsJobFromFirstQueue()
    {
        $job = (object) ['id' => 1, 'payload' => 'serialized_job'];

        // First queue has a job
        $this->jobModelMock->expects($this->exactly(1))
            ->method('getNextJob')
            ->with('default')
            ->willReturn($job);

        // Call the protected method using reflection
        $result = $this->callProtectedMethod($this->worker, 'fetchNextJob');

        $this->assertSame($job, $result);
    }

    public function testFetchNextJobChecksAllQueuesUntilJobFound()
    {
        // First queue has no job, second queue has a job
        $job = (object) ['id' => 1, 'payload' => 'serialized_job'];

        $this->jobModelMock->expects($this->exactly(2))
            ->method('getNextJob')
            ->withConsecutive(['default'], ['high'])
            ->willReturnOnConsecutiveCalls(null, $job);

        // Call the protected method using reflection
        $result = $this->callProtectedMethod($this->worker, 'fetchNextJob');

        $this->assertSame($job, $result);
    }

    public function testFetchNextJobReturnsNullWhenNoJobsFound()
    {
        // No jobs in any queue
        $this->jobModelMock->expects($this->exactly(2))
            ->method('getNextJob')
            ->withConsecutive(['default'], ['high'])
            ->willReturn(null);

        // Call the protected method using reflection
        $result = $this->callProtectedMethod($this->worker, 'fetchNextJob');

        $this->assertNull($result);
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

    /**
     * Helper method to execute a function with a timeout
     */
    private function executeWithTimeout(callable $function, $timeout)
    {
        // Set a temporary error handler to catch the E_WARNING from set_time_limit
        set_error_handler(function($errno, $errstr) {
            return true;
        }, E_WARNING);

        // Set the time limit
        set_time_limit($timeout);

        // Restore the error handler
        restore_error_handler();

        // Execute the function
        return $function();
    }
}

// Test job class for serialization/deserialization
class TestJob
{
    public function handle()
    {
        // Do nothing
    }

    public function markAsHandled()
    {
        // Do nothing
    }
}
