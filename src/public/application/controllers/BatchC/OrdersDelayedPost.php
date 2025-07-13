<?php
/*
SW Serviços de Informática 2019

Atualiza pedidos que chegaram no BLING

//php index.php BatchC/OrdersDelayedPost run

*/
class OrdersDelayedPost extends BatchBackground_Controller {
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
        $this->load->model('model_orders','myorders');

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
        echo "Pegando ordens com postagem em atraso \n";
        $this->ordersDelayedPost();

        /* encerra o job */
        $this->log_data('batch',$log_name,'finish',"I");
        $this->gravaFimJob();
    }

    public function ordersDelayedPost()
    {
        $orders = $this->myorders->getOrderDelayedPost(false, true);

        foreach ($orders as $order)
            $this->myorders->updateOrderDelayedPost($order['id']);
    }
}