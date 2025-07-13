<?php

use PHPUnit\Framework\TestCase;
use App\Libraries\Queue\Pushers\DatabaseQueuePusher;
use App\Jobs\GenericJob;
use App\libraries\Enum\QueueDriverEnum;
use Tests\Fakes\FunctionMockTrait;

class DatabaseQueuePusherTest extends TestCase
{
    use FunctionMockTrait;
    private $jobModelMock;
    private $pusher;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a mock for the job model
        $this->jobModelMock = $this->getMockBuilder(stdClass::class)
            ->addMethods(['add'])
            ->getMock();

        // Create a DatabaseQueuePusher with the mock job model
        $this->pusher = new DatabaseQueuePusher($this->jobModelMock);

        // Define get_instance function if it doesn't exist
        if (!function_exists('get_instance')) {
            function get_instance() {
                $ci = new stdClass();
                $ci->load = new stdClass();
                $ci->load->model = function() {};
                $ci->Job_model = new stdClass();
                return $ci;
            }
        }
    }

    public function testConstructorWithProvidedJobModel()
    {
        $jobModel = new stdClass();
        $pusher = new DatabaseQueuePusher($jobModel);

        $this->assertSame($jobModel, $this->getProtectedProperty($pusher, 'jobModel'));
    }

    public function testPushAddsJobToDatabase()
    {
        // Create a mock for GenericJob
        $job = $this->getMockBuilder(GenericJob::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'computeClassHash', 
                'setDriver', 
                'getQueueName', 
                'getAvailableAt',
                'getReservedTimeoutSeconds',
                'getMaxAttempts',
                'getRetryDelaySeconds',
                'getAttempts',
                'getFailedAt',
                'getFailedReason'
            ])
            ->getMock();

        // Setup expectations
        $job->expects($this->exactly(2))
            ->method('computeClassHash')
            ->willReturn('job_hash');

        $job->expects($this->once())
            ->method('setDriver')
            ->with(QueueDriverEnum::DATABASE);

        $job->expects($this->once())
            ->method('getQueueName')
            ->willReturn('default');

        $job->expects($this->once())
            ->method('getAvailableAt')
            ->willReturn(date('Y-m-d H:i:s'));

        $job->expects($this->once())
            ->method('getReservedTimeoutSeconds')
            ->willReturn(60);

        $job->expects($this->once())
            ->method('getMaxAttempts')
            ->willReturn(5);

        $job->expects($this->once())
            ->method('getRetryDelaySeconds')
            ->willReturn(600);

        $job->expects($this->once())
            ->method('getAttempts')
            ->willReturn(0);

        $job->expects($this->once())
            ->method('getFailedAt')
            ->willReturn(null);

        $job->expects($this->once())
            ->method('getFailedReason')
            ->willReturn(null);

        // Expect job model's add method to be called
        $this->jobModelMock->expects($this->once())
            ->method('add')
            ->with(
                get_class($job),
                $this->isType('string'),
                'default',
                'job_hash',
                $this->isType('string'),
                'job_hash',
                60,
                5,
                600,
                0,
                null,
                null
            );

        // Call push method
        $this->pusher->push($job);
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
