<?php

use PHPUnit\Framework\TestCase;
use App\Libraries\Queue\JobFileWatcher;

class JobFileWatcherTest extends TestCase
{
    private $tempFile;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a temporary file for testing
        $this->tempFile = sys_get_temp_dir() . '/test_file_' . uniqid() . '.txt';
        file_put_contents($this->tempFile, 'initial content');
    }

    protected function tearDown(): void
    {
        // Clean up the temporary file
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
        parent::tearDown();
    }

    public function testGetInstanceReturnsSingletonInstance()
    {
        $instance1 = JobFileWatcher::getInstance();
        $instance2 = JobFileWatcher::getInstance();
        
        $this->assertSame($instance1, $instance2, 'getInstance should return the same instance');
        $this->assertInstanceOf(JobFileWatcher::class, $instance1);
    }

    public function testCheckReturnsTrueOnFirstCheck()
    {
        $watcher = JobFileWatcher::getInstance();
        $result = $watcher->check($this->tempFile);
        
        $this->assertTrue($result, 'First check should return true');
    }

    public function testCheckReturnsTrueWhenFileUnchanged()
    {
        $watcher = JobFileWatcher::getInstance();
        // First check to store the hash
        $watcher->check($this->tempFile);
        
        // Second check without changing the file
        $result = $watcher->check($this->tempFile);
        
        $this->assertTrue($result, 'Check should return true when file is unchanged');
    }

    public function testCheckReturnsFalseWhenFileChanged()
    {
        $watcher = JobFileWatcher::getInstance();
        // First check to store the hash
        $watcher->check($this->tempFile);
        
        // Change the file content
        file_put_contents($this->tempFile, 'modified content');
        
        // Check again after changing the file
        $result = $watcher->check($this->tempFile);
        
        $this->assertFalse($result, 'Check should return false when file is changed');
    }
}