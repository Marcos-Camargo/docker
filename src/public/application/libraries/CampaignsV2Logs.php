<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class CampaignsV2Logs
{
    protected $_CI;

    public function __construct()
	{
        $this->_CI = &get_instance();
        $this->_CI->load->model('model_campaigns_v2_logs');
    }


    public function log(array $log_array, $model_id, $model, $method)
    {

        $log_array = array
            (
                'model' => $model,
                'method' => $method,
                'model_id' => $model_id,
                'data' => stripslashes(json_encode($log_array, JSON_UNESCAPED_SLASHES)),
                'user_id' => (isset($_SESSION['id'])) ? $_SESSION['id'] : 0
            );

        $this->_CI->model_campaigns_v2_logs->saveLog($log_array);
    }


}