<?php
defined('BASEPATH') or exit('No direct script access allowed');

class CommissioningLogs
{
    protected $_CI;

    public function __construct()
    {
        $this->_CI = &get_instance();
        $this->_CI->load->model('model_commissioning_logs');
    }


    public function log(array $log_array, $model_id, $model, $method)
    {

        $log_array = array
        (
            'model' => $model,
            'method' => $method,
            'model_id' => $model_id,
            'commissioning_id' => $model == 'commissionings' ? $model_id : $log_array['commissioning_id'],
            'data' => stripslashes(json_encode($log_array, JSON_UNESCAPED_SLASHES)),
            'user_id' => (isset($_SESSION['id'])) ? $_SESSION['id'] : 0
        );

        $this->_CI->model_commissioning_logs->create($log_array);
    }


}