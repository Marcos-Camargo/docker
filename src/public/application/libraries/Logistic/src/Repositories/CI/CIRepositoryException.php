<?php

namespace Logistic\Repositories;

class CIRepositoryException extends RepositoryException
{
    public function __construct($message = "", $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getFormattedMessages(): array
    {
        return [
            $this->message
        ];
    }
}