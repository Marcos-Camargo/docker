<?php
/*
Verifica quais lojas precisam ser criadas no frete rapido como expedidor e as cria
*/

class CancelPlpCorreios extends BatchBackground_Controller {

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
        $this->load->model('model_stores','mystores');
        $this->load->model('model_company','mycompany');
        $this->load->model('model_integrations', 'myintegrations');
        $this->load->model('model_settings','mysettings');
        $this->load->model('Model_freights','myfreights');
        $this->load->model('Model_orders','myorders');

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
        $retorno = $this->cancelPlps();

        /* encerra o job */
        $this->log_data('batch',$log_name,'finish',"I");
        $this->gravaFimJob();
    }

    function cancelPlps($params = null)
    {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;

        $this->log_data('batch',$log_name,'start '.trim($params),"I");

        $dataPlp = $this->myfreights->getDataPlpExpired();
        $this->log_data('batch',$log_name,'pedidos para cancelar '.json_encode($dataPlp),"I");

        foreach ($dataPlp as $order) {

            echo "Cancelando PLP | order_id={$order['order_id']} | paid_status={$order['paid_status']} | number_plp={$order['number_plp']}\n";

            $this->db->trans_begin();

            $trackings = array();
            foreach ($this->myfreights->getCountObjetosOrder($order['order_id'], $order['in_resend_active']) as $tracking) {
                array_push($trackings, $tracking['codigo_rastreio']);
            }

            if(!$this->myfreights->removePlpOrder($order['number_plp'], $order['order_id'], $order['in_resend_active'])) {
                $this->db->trans_rollback();
                echo "Não foi encontrado o pedido: {$order['order_id']} para remover os rastreios!\n";
                $this->log_data('batch',$log_name,"Não foi encontrado o pedido: {$order['order_id']} para remover os rastreios!","E");
                continue;
            }

            if(!$this->myorders->updatePaidStatus($order['order_id'], 50)) {
                $this->db->trans_rollback();
                echo "Não foi encontrado o pedido: {$order['order_id']} para atalizar o status!\n";
                $this->log_data('batch',$log_name,"Não foi encontrado o pedido: {$order['order_id']} para atalizar o status!","E");
                continue;
            }

            if(!$this->myfreights->removePlp($order['number_plp'], true, $order['order_id'])) {
                $this->db->trans_rollback();
                echo "Não foi encontrado a plp: {$order['number_plp']} para remover a plp!\n";
                $this->log_data('batch',$log_name,"Não foi encontrado a plp: {$order['number_plp']} para remover a plp!","E");
                continue;
            }

            if ($this->db->trans_status() === FALSE){
                $this->db->trans_rollback();
                echo "Ocorreu um problema para processar as queries!\n";
                $this->log_data('batch',$log_name,'Ocorreu um problema para processar as queries!',"E");
                continue;
            }

            $files = "assets/images/etiquetas/P_{$order['order_id']}";

            if (file_exists($files . "_PLP.pdf")) unlink($files . "_PLP.pdf");
            if (file_exists($files . "_A4.pdf")) unlink($files . "_A4.pdf");
            if (file_exists($files . "_Termica.pdf")) unlink($files . "_Termica.pdf");

            // cria comentario no pedido da plp excluída
            $arrComment = array();
            if($order['comments_adm'])
                $arrComment = json_decode($order['comments_adm']);

            array_push($arrComment, array(
                'order_id'  => $order['order_id'],
                'comment'   => "Etiqueta(s) ".implode(',', $trackings)." da PLP {$order['number_plp']} removida do pedido por validade de 7 dias",
                'user_id'   => 0,
                'user_name' => 'BATCH',
                'date'      => date('Y-m-d H:i:s')
            ));

            $sendComment  = json_encode($arrComment);

            $this->myorders->createCommentOrderInProgress($sendComment, $order['order_id']);

            $this->db->trans_commit();
            echo "PLP do pedido {$order['order_id']} cancelado com sucesso!\n";

        }

        return true;
    }

}
