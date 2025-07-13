<?php

use PHPUnit\Framework\TestCase;
use App\Libraries\Queue\Tools\RedisQueueMonitor;
use App\Libraries\Cache\RedisCacheHandler;
use App\Libraries\Cache\CacheManager;
use Tests\Fakes\FunctionMockTrait;

class RedisQueueMonitorTest extends TestCase
{
    use FunctionMockTrait;
    private $redisMock;
    private $monitor;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock RedisCacheHandler
        $this->redisMock = $this->getMockBuilder(RedisCacheHandler::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['zrange'])
            ->getMock();

        // Create a partial mock of RedisQueueMonitor to avoid creating a real RedisCacheHandler
        $this->monitor = $this->getMockBuilder(RedisQueueMonitor::class)
            ->onlyMethods(['__construct'])
            ->getMock();

        // Use reflection to set the redis property
        $reflectionClass = new ReflectionClass(RedisQueueMonitor::class);
        $redisProperty = $reflectionClass->getProperty('redis');
        $redisProperty->setAccessible(true);
        $redisProperty->setValue($this->monitor, $this->redisMock);

        // Define constants if needed
        if (!defined('PHP_OS_FAMILY')) {
            define('PHP_OS_FAMILY', 'Linux');
        }
    }

    public function testConstructorInitializesRedis()
    {
        // Create a real instance to test the constructor
        $realMonitor = new RedisQueueMonitor();

        // Verify redis was initialized
        $reflectionClass = new ReflectionClass(RedisQueueMonitor::class);
        $redisProperty = $reflectionClass->getProperty('redis');
        $redisProperty->setAccessible(true);
        $redis = $redisProperty->getValue($realMonitor);

        $this->assertInstanceOf(RedisCacheHandler::class, $redis);
    }

}
