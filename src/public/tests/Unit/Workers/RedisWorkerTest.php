<?php

use PHPUnit\Framework\TestCase;
use App\Libraries\Queue\Workers\RedisWorker;
use App\Libraries\Cache\RedisCacheHandler;
use App\Libraries\Cache\CacheManager;
use App\Jobs\GenericJob;
use App\libraries\Enum\QueueDriverEnum;
use App\libraries\Enum\QueueEnum;
use App\Libraries\Queue\QueueDispatcher;
use Carbon\Carbon;
use Tests\Fakes\FunctionMockTrait;

class RedisWorkerTest extends TestCase
{
    use FunctionMockTrait;
    private $redisMock;
    private $worker;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a mock for RedisCacheHandler
        $this->redisMock = $this->getMockBuilder(RedisCacheHandler::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['zrangebyscore', 'get', 'set', 'delete', 'has', 'zadd'])
            ->addMethods(['keys'])
            ->getMock();

        // Create a mock for CacheManager
        $cacheManagerMock = $this->getMockBuilder(CacheManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['closeConnection'])
            ->getMock();

        // Mock CacheManager static methods
        $this->getFunctionMock('App\Libraries\Cache', 'CacheManager::get')
            ->expects($this->any())
            ->method('__invoke')
            ->willReturnCallback(function($key) {
                return null;
            });

        $this->getFunctionMock('App\Libraries\Cache', 'CacheManager::set')
            ->expects($this->any())
            ->method('__invoke');

        $this->getFunctionMock('App\Libraries\Cache', 'CacheManager::delete')
            ->expects($this->any())
            ->method('__invoke');

        $this->getFunctionMock('App\Libraries\Cache', 'CacheManager::has')
            ->expects($this->any())
            ->method('__invoke')
            ->willReturn(false);

        $this->getFunctionMock('App\Libraries\Cache', 'CacheManager::zadd')
            ->expects($this->any())
            ->method('__invoke');

        $this->getFunctionMock('App\Libraries\Cache', 'CacheManager::zrangebyscore')
            ->expects($this->any())
            ->method('__invoke')
            ->willReturn([]);

        $this->getFunctionMock('App\Libraries\Cache', 'CacheManager::getAllKeysByPrefix')
            ->expects($this->any())
            ->method('__invoke')
            ->willReturn([]);

        $this->getFunctionMock('App\Libraries\Cache', 'CacheManager::setnx')
            ->expects($this->any())
            ->method('__invoke')
            ->willReturn(true);

        $this->getFunctionMock('App\Libraries\Cache', 'CacheManager::closeConnection')
            ->expects($this->any())
            ->method('__invoke');

        // Create the worker with the mock Redis
        $this->worker = new RedisWorker('test_seller', ['default', 'high']);

        // Use reflection to set the redis property
        $reflectionClass = new ReflectionClass(RedisWorker::class);
        $redisProperty = $reflectionClass->getProperty('redis');
        $redisProperty->setAccessible(true);
        $redisProperty->setValue($this->worker, $this->redisMock);

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
        $this->assertEquals('test_seller', $this->getProtectedProperty($this->worker, 'sellercenter'));
        $this->assertEquals(['default', 'high'], $this->getProtectedProperty($this->worker, 'queues'));
    }

    public function testHandleJobFailureWithRetry()
    {
        // Create a job with attempts remaining
        $job = $this->getMockBuilder(GenericJob::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getMaxAttempts', 'getRetryDelaySeconds', 'computeClassHash'])
            ->getMock();

        $job->attempts = 2;

        $job->expects($this->once())
            ->method('getMaxAttempts')
            ->willReturn(5);

        $job->expects($this->once())
            ->method('getRetryDelaySeconds')
            ->willReturn(60);

        $job->expects($this->once())
            ->method('computeClassHash')
            ->willReturn('job_hash');

        // Setup Redis mock for retry
        $this->redisMock->expects($this->once())
            ->method('has')
            ->with('test_seller:queue:default:job_hash')
            ->willReturn(false);

        $this->redisMock->expects($this->once())
            ->method('set')
            ->with('test_seller:queue:default:job_hash', $this->isType('string'));

        $this->redisMock->expects($this->once())
            ->method('zadd')
            ->with('test_seller:queue:default:schedule', $this->greaterThan(time()), 'test_seller:queue:default:job_hash');

        // Call the method
        $this->callProtectedMethod($this->worker, 'handleJobFailure', [$job, 'default', 'error_reason']);

        // Verify attempts was incremented
        $this->assertEquals(3, $job->attempts);
    }

    public function testReleaseLockDeletesLockKey()
    {
        // Setup Redis mock
        $this->redisMock->expects($this->once())
            ->method('delete')
            ->with('test_seller:lock:test_key');

        // Call the method
        $this->callProtectedMethod($this->worker, 'releaseLock', ['test_key']);
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
