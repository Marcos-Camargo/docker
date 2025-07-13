<?php

/**
 * @property Model_queue_products_marketplace $model_queue_products_marketplace
 */
class Vacation extends BatchBackground_Controller
{
    public function __construct()
    {
        parent::__construct();
        $logged_in_sess = array(
            'id'        => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp'  => 1,
            'userstore' => 0,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);

        $this->load->model('model_queue_products_marketplace');
    }

    public function run($id = null, $params = null)
    {
        $this->setIdJob($id);
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
        if (!$this->gravaInicioJob($this->router->fetch_class(), __FUNCTION__)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
            return;
        }

        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $params), "I");
        $this->prdIntoQueue($params);
        $this->destroyJob($params);
        echo PHP_EOL . PHP_EOL . 'Fim da rotina' . PHP_EOL . PHP_EOL;

        $this->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }

    private function destroyJob($store_id)
    {
        $this->db->delete('calendar_events', array('module_path' => 'Vacation', 'params' => (string)$store_id));
    }
    
    private function prdIntoQueue($store_id) {
        $vacation_data = $this->db->query("SELECT start_vacation, end_vacation, is_vacation FROM stores WHERE id = ?", [$store_id])->row();
        if (!$vacation_data) {
            $this->log_data('batch', 'Vacation/prdIntoQueue', 'Loja não encontrada ou sem dados de férias', "E");
            return;
        }
        $startV = $vacation_data->start_vacation;
        $endV = $vacation_data->end_vacation;
        $isV = $vacation_data->is_vacation;

        $remaining_products = (int) $this->db->query("
            SELECT COUNT(*) AS remaining 
            FROM products 
            WHERE store_id = ? AND date_update < ?", 
            [$store_id, $isV == 1 ? $startV : $endV]
        )->row()->remaining;

        echo "Lendo $remaining_products produtos\n";

        while($remaining_products > 0){
            echo "Total de produtos a serem processados: ".$remaining_products;
            echo "\n";  
            $queue_size = (int) $this->db->query("SELECT COUNT(*) AS queue_size FROM queue_products_marketplace")->row()->queue_size;
            echo "Tamanho da fila: ".$queue_size;
            echo "\n";
            if ($queue_size <= 500) {
                echo "Atualizando 200 produtos da loja id: ".$store_id;
                echo "\n";

                $this->db->query("
                    UPDATE products p SET p.date_update = NOW() 
                        WHERE p.store_id = ? 
                        AND p.date_update < ?
                        LIMIT 200", 
                        [$store_id, $isV == 1 ? $startV : $endV]);
                
                $remaining_products -= 200;
            } else {
                echo "Aguardando 15 segundos. Tamanho da fila: ".$queue_size;
                echo "\n";
                $this->log_data('batch', 'Vacation/prdIntoQueue', 'Fila cheia, aguardando próximo ciclo', "E");
                sleep(15);
            }
        }

        $this->log_data('batch', 'Vacation/processQueue', 'Todos os produtos foram adicionados à fila', "I");
    } 
}