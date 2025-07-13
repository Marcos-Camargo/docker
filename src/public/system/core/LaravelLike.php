<?php

require_once 'LaravelLikeUtils/Dump.php';
use LaravelLikeUtils\Dump;

if (!function_exists('dd')) {
    function dd(...$vars)
    {
        foreach ($vars as $v) {
            Dump::dump($v);
        }

        exit(1);
    }
}