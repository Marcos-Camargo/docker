<?php

class SendEmailNotificationTrigger extends BatchBackground_Controller {
    public function __construct()
    {
        parent::__construct();
        // log_message('debug', 'Class BATCH ini.');

        $logged_in_sess = array(
            'id' => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp' => 1,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);

        // carrega os modulos necessários para o Job
        $this->load->model('model_queue_send_notification');
        $this->load->model('model_stores');

    }

    function run($id=null,$params=null)
    {
        /* inicia o job */
        $this->setIdJob($id);
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;
        if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__)) {
            $this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
            return ;
        }
        $this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");

        /* faz o que o job precisa fazer */
        echo "Pegando mensagens para notificar por email \n";
        $this->sendEmailNotificationTrigger();

        /* encerra o job */
        $this->log_data('batch',$log_name,'finish',"I");
        $this->gravaFimJob();
    }

    public function sendEmailNotificationTrigger()
    {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;

        $notifications = $this->model_queue_send_notification->list();

        foreach ($notifications as $notification) {
            echo "[ LOG ]".json_encode($notification)."\n";

            if ($notification['store_id']) {
                $store = $this->model_stores->getStoresData($notification['store_id']);

                if ($notification['identifier'] == 'FIRST_PRODUCT') {
                    $subject = $notification['subject'];
                    
                    $body = $notification['description'];
                    $emailStore = $store['responsible_email'];
    
                    $subject = str_replace('{{NomeDoResponsavelLoja}}', $store['responsible_name'], $subject);
                    $subject = str_replace('{{NomeDaLoja}}', $store['name'], $subject);

                    $body = str_replace('{{NomeDoResponsavelLoja}}', $store['responsible_name'], $body);
                    $body = str_replace('{{NomeDaLoja}}', $store['name'], $body);

                    $statusSendEmail = $this->sendEmailMarketing($emailStore, $subject, $body) ? 'enviado' : 'nao_enviado';
                    if ($statusSendEmail == 'enviado') $updateAlert = true;
                    //$this->log_data('batch', $log_name, 'Enviou e-mail de Primeiro produto cadastrado.'.$statusSendEmail."\n\n". json_encode(array($emailStore, $subject, $body)) . "\n\nLOG=" . json_encode($log_prd) . "\n\nProduct=" . json_encode($prd), "I");
        
                    if ($updateAlert) {
                        $this->model_queue_send_notification->updateStatus($notification['id'], 1);
                        //$this->log_data('batch', $log_name, "Atualizou log_products_catalog_price para alert=1. \n\nLOG=" . json_encode($log_prd), "I");
                    }
                    if ($updateAlert) {
                        $this->model_queue_send_notification->updateStatus($notification['id'], 2);
                        //$this->log_data('batch', $log_name, "Atualizou log_products_catalog_price para alert=1. \n\nLOG=" . json_encode($log_prd), "I");
                    }
                }
            }
        }
    }
}
