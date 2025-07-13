<?php 

class TransformationException extends Exception{
    public function __construct($mensage)
    {
        parent::__construct($mensage);
    }
}