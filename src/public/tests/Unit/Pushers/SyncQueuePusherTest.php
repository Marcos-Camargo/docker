<?php

use PHPUnit\Framework\TestCase;
use App\Libraries\Queue\Pushers\SyncQueuePusher;

class SyncQueuePusherTest extends TestCase
{
    private $pusher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pusher = new SyncQueuePusher();
    }

    public function testPushCallsHandleMethodOnJob()
    {
        // Create a mock for a job with a handle method
        $job = $this->getMockBuilder(stdClass::class)
            ->addMethods(['handle'])
            ->getMock();

        // Expect handle to be called once
        $job->expects($this->once())
            ->method('handle');

        // Call push method
        $this->pusher->push($job);
    }

    public function testPushThrowsExceptionWhenJobDoesNotHaveHandleMethod()
    {
        // Create a mock for a job without a handle method
        $job = $this->getMockBuilder(stdClass::class)
            ->getMock();

        // Expect an exception to be thrown
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Método handle() não encontrado no Job.');

        // Call push method
        $this->pusher->push($job);
    }
}
