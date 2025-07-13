<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

if (!function_exists('customErrorHandler')) {
    /**
     * @param   int     $errNo
     * @param   string  $errMsg
     * @param   string  $file
     * @param   int     $line
     * @return  mixed
     * @throws  Exception
     */
    function customErrorHandler(int $errNo, string $errMsg, string $file, int $line)
    {
        throw new Exception("#[$errNo] occurred in [$file] at line [$line]: [$errMsg]");
    }
}

if (!function_exists('customErrorUploadImage')) {
    /**
     * @param   int     $errNo
     * @param   string  $errMsg
     * @param   string  $file
     * @param   int     $line
     * @return  mixed
     * @throws  Exception
     */
    function customErrorUploadImage(int $errNo, string $errMsg, string $file, int $line)
    {
        throw new Exception($errMsg);
    }
}
