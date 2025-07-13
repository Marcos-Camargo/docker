<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Omnilogic {
    
    public static function url_notify() {
        return 'https://integration.oppuz.com/store/conectala/notification';
    }

    public static function token() {
        return 'b1d4da9a97418aef0f1041c35c332f59';
    }

    public static function store() {
        return 'conectala';
    }

    public static function channel_int_to() {
        $arr = array();
        
        array_push($arr, array('channel' => 'conectala_ML', 'int_to' => 'ML'));
        // array_push($arr, array('channel' => 'conectala_VIA', 'int_to' => 'VIA'));

        return $arr;
    }


}