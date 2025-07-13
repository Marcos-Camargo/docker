<?php

namespace Tests\Fakes;

use PHPUnit\Framework\MockObject\MockBuilder;

trait FunctionMockTrait
{
    private $functionMocks = [];

    /**
     * Creates a mock for a function in the specified namespace
     *
     * @param string $namespace The namespace where the function is defined
     * @param string $functionName The name of the function to mock
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    public function getFunctionMock(string $namespace, string $functionName)
    {
        $key = $namespace . '\\' . $functionName;

        if (!isset($this->functionMocks[$key])) {
            $mockObject = $this->getMockBuilder(\stdClass::class)
                ->addMethods(['__invoke'])
                ->getMock();

            $this->functionMocks[$key] = $mockObject;
        }

        return $this->functionMocks[$key];
    }
}
