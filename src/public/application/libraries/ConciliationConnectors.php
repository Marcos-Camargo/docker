<?php 

defined('BASEPATH') or exit('No direct script access allowed');

class ConciliationConnectors
{
    protected $_CI;      


    public function __construct()
    {
        $this->_CI = &get_instance();
        $this->_CI->load->model('model_billet');
    }

}