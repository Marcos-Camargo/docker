<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Cookies extends Admin_Controller {
    function __construct()
    {
        parent::__construct();
        $this->load->helper('cookie');
    }
    function set()
    {
        $request = $this->postClean('value');
        $cookie = [
            'name'   => 'remember_me',
            'value'  => $request,
            'expire' => time() + (86400 * 30), // 30 dias
            'path' =>  '/',
            'secure' => TRUE
        ];

        $this->input->set_cookie($cookie);
        return $this->get();
    }
    function get()
    {
        echo json_encode($this->input->cookie('remember_me',true));
    }
}