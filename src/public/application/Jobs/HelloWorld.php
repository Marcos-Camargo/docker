<?php

namespace App\Jobs;

class HelloWorld extends GenericJob
{

    public $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function handle()
    {
        echo "Hello World {$this->message}" . PHP_EOL;
    }
}
