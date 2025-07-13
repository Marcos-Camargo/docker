<?php

namespace Logistic\Repositories;

abstract class RepositoryException extends \Exception implements \Throwable
{
    public function __construct($message = "", $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public abstract function getFormattedMessages(): array;
}