<?php

use PHPUnit\Framework\TestCase;
use App\Libraries\Queue\Pushers\RedisQueuePusher;
use App\Jobs\GenericJob;
use App\Libraries\Enum\QueueDriverEnum;
use Tests\Fakes\FakeRedisCacheHandler;

class RedisQueuePusherTest extends TestCase
{
    public function testPushWithoutDelayStoresJobUsingSetOnly()
    {
        $redis = $this->createMock(FakeRedisCacheHandler::class);
        $redis->method('has')->willReturn(false);

        $redis->expects($this->once())
            ->method('set')
            ->with('teste:queue:financeiro:abc123', $this->isType('string'));

        $redis->expects($this->never())->method('zadd');

        $job = $this->createMock(GenericJob::class);
        $job->method('getOriginalQueueName')->willReturn('financeiro');
        $job->method('computeClassHash')->willReturn('abc123');
        $job->method('getDelayInSeconds')->willReturn(0);
        $job->expects($this->once())->method('setDriver')->with(QueueDriverEnum::REDIS);

        $pusher = new RedisQueuePusher('teste', $redis);
        $result = $pusher->push($job);

        $this->assertTrue($result);
    }

    public function testPushWithDelayUsesZaddAndSet()
    {
        $redis = $this->createMock(FakeRedisCacheHandler::class);
        $redis->method('has')->willReturn(false);

        $redis->expects($this->once())
            ->method('zadd')
            ->with('queue:agendada:schedule', $this->greaterThan(time()), 'teste:queue:agendada:abc123');

        $redis->expects($this->once())
            ->method('set')
            ->with('teste:queue:agendada:abc123', $this->isType('string'));

        $job = $this->createMock(GenericJob::class);
        $job->method('getOriginalQueueName')->willReturn('agendada');
        $job->method('computeClassHash')->willReturn('abc123');
        $job->method('getDelayInSeconds')->willReturn(60);
        $job->expects($this->once())->method('setDriver')->with(QueueDriverEnum::REDIS);

        $pusher = new RedisQueuePusher('teste', $redis);
        $result = $pusher->push($job);

        $this->assertTrue($result);
    }

    public function testPushWithDuplicateHashReturnsFalse()
    {
        $redis = $this->createMock(FakeRedisCacheHandler::class);
        $redis->method('has')->willReturn(true); // já existe

        $job = $this->createMock(GenericJob::class);
        $job->method('getOriginalQueueName')->willReturn('duplicada');
        $job->method('computeClassHash')->willReturn('hash');

        $pusher = new RedisQueuePusher('teste', $redis);
        $result = $pusher->push($job);

        $this->assertFalse($result);
    }
}
