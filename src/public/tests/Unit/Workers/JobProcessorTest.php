<?php

use PHPUnit\Framework\TestCase;
use App\Libraries\Queue\Workers\JobProcessor;
use App\Libraries\Queue\Workers\WorkerInterface;

class JobProcessorTest extends TestCase
{
    public function testStartCallsWorkerRun()
    {
        // Create a mock for WorkerInterface
        $workerMock = $this->createMock(WorkerInterface::class);
        
        // Set expectation that run() will be called once
        $workerMock->expects($this->once())
            ->method('run');
            
        // Create JobProcessor with the mock worker
        $processor = new JobProcessor($workerMock);
        
        // Call start() which should call the worker's run() method
        $processor->start();
    }
    
    public function testConstructorSetsWorkerProperty()
    {
        // Create a mock for WorkerInterface
        $workerMock = $this->createMock(WorkerInterface::class);
        
        // Create JobProcessor with the mock worker
        $processor = new JobProcessor($workerMock);
        
        // Verify that the worker property was set correctly
        $this->assertSame($workerMock, $processor->worker);
    }
}