<?php

class UserAutomation extends BatchBackground_Controller
{

    public function __construct()
    {
        parent::__construct();

        $logged_in_sess = array(
            'id' => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp' => 1,
            'userstore' => 0,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);
        $this->load->model('model_users');
        $this->load->model('model_settings');
    }
    // php index.php BatchC/Automation/UserAutomation run null 150
    function run($id = null, $params = null)
    {
        $this->setIdJob($id);
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
        if (!$this->gravaInicioJob('Automation/'.$this->router->fetch_class(), __FUNCTION__)) {
            $this->log_data(
                'batch',
                $log_name,
                'Já tem um job rodando ou que foi cancelado',
                "E"
            );
            return;
        }
        $setting=$this->model_settings->getSettingDatabyName('maximum_number_of_days_to_block_user');
        if($setting){
            if($setting["status"]=='1'){
                $this->log_data('batch', $log_name, 'start ' . trim($id . " " .$setting["value"]), "I");
                $this->model_users->inactiveUserForDay($setting["value"]);
                $this->model_users->inactiveUserUnloged($setting["value"]);
            }
        }

        /* encerra o job */
		get_instance()->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
    }
}