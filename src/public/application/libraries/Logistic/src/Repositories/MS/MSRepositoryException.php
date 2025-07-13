<?php

namespace Logistic\Repositories;

class MSRepositoryException extends RepositoryException
{
    public function __construct($message = "", $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getFormattedMessages(): array
    {
        $errorsFormatted = [];
        $errors = json_decode($this->message, true);
        if (!is_array($errors)) {
            if (is_string($errors)) {
                return [$errors];
            }
            return (array)json_encode($errors, JSON_UNESCAPED_UNICODE);
        }
        foreach ($errors as $errorsField) {
            foreach ($errorsField as $error) {
                $errorsFormatted[] = $error;
            }
        }
        return $errorsFormatted;
    }
}