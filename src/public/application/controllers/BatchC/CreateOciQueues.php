<?php
class CreateOciQueues extends BatchBackground_Controller
{

    public function __construct()
    {
        parent::__construct();
        ini_set('display_errors', 1);

        $logged_in_sess = array(
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'userstore' => 0,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);
        $this->load->model('model_settings');
        $this->load->model('model_oci_queues');
        $settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');
        $this->sellercenter = $settingSellerCenter['value'];

    }

    // php index.php BatchC/CreateLogisticSummary run null
    function run($id = null, $params = null)
    {

        /* inicia o job */
        $this->setIdJob($id);
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
        if (!$this->gravaInicioJob($this->router->fetch_class(), __FUNCTION__)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
            return;
        }
        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $params), "I");

        /**
         * Nome das filas sempre em letra minuscula
         * Mapear todas que deverão ser criadas aqui
         */
        $jobsToBeCreated = [
            'job_via_callbacks_'.ENVIRONMENT.'_'.$this->sellercenter,
            'job_batch_'.ENVIRONMENT.'_'.$this->sellercenter,
            'queue_'.ENVIRONMENT.'_'.$this->sellercenter,
        ];

        //Verificação de quais itens já foram cadastrados
        $queuesCreated = $this->model_oci_queues->findALl();
        if ($queuesCreated){
            foreach ($queuesCreated as $queueCreated){
                foreach ($jobsToBeCreated as $i => $jobToBeCreated){
                    if ($jobToBeCreated == $queueCreated['display_name']){
                        unset($jobsToBeCreated[$i]);
                    }
                }
            }
        }

        if ($jobsToBeCreated){

            get_instance()->load->library('Queue/QueueManager');
            QueueManager::createQueues($jobsToBeCreated);

        }

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }

}
