<?php

defined('BASEPATH') or exit('No direct script access allowed');

class TesteJobs extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();

    }

    public function index()
    {
        get_instance()->load->model('model_settings');
        $sellercenter = get_instance()->model_settings->getValueIfAtiveByName('sellercenter');
        $uniqId = uniqid();
        \App\Jobs\HelloWorld::dispatch("$sellercenter: Funcionou - Teste: $uniqId :)");
    }

}
