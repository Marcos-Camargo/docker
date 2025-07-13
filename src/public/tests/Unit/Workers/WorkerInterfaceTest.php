<?php

use PHPUnit\Framework\TestCase;
use App\Libraries\Queue\Workers\WorkerInterface;

class WorkerInterfaceTest extends TestCase
{
    public function testInterfaceExists()
    {
        $this->assertTrue(interface_exists(WorkerInterface::class));
    }
    
    public function testInterfaceHasRunMethod()
    {
        $reflectionClass = new ReflectionClass(WorkerInterface::class);
        $this->assertTrue($reflectionClass->hasMethod('run'));
        
        $runMethod = $reflectionClass->getMethod('run');
        $this->assertTrue($runMethod->isPublic());
    }
    
    public function testImplementationWorks()
    {
        // Create a concrete implementation of the interface
        $worker = new class implements WorkerInterface {
            public function run()
            {
                return 'running';
            }
        };
        
        // Test that the implementation works
        $this->assertInstanceOf(WorkerInterface::class, $worker);
        $this->assertEquals('running', $worker->run());
    }
}