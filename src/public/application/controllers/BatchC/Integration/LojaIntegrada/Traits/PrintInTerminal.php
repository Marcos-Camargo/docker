<?php
if (!defined('PrintInTerminal')) {
    define('PrintInTerminal', '');
    trait PrintInTerminal
    {
        private function printInTerminal($string)
        {
            file_put_contents('php://stdout', print_r($string, true));
        }
    }
}
