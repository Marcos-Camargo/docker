<?php

/**
 * Corrige os produtos com estoque incorreto.
 * Diversos produtos apresentam estoque 0.
 */
class FixPrdQty extends BatchBackground_Controller
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
    }

    /**
     * php index.php BatchC/Automation/Fix/FixPrdQty run
     */
    public function run($id = null, $params = null)
    {
        $this->setIdJob($id);
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
        if (!$this->gravaInicioJob($this->router->fetch_class(), __FUNCTION__)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
            return;
        }

        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $params), "I");
        $this->fixPrdStock();
        echo PHP_EOL . PHP_EOL . 'Fim da rotina' . PHP_EOL . PHP_EOL;

        $this->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }

    /**
     * Corrige o estoque dos produtos.
     */
    private function fixPrdStock()
    {
		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
        $products_to_fix_count = (int)$this->db->query(
            "SELECT COUNT(*) as count FROM (
                SELECT prod.qty,SUM(IF(prod_vars.qty = '', 0, prod_vars.qty)) AS var_qty_total
                FROM products prod 
                LEFT JOIN prd_variants AS prod_vars ON prod.id = prod_vars.prd_id
                WHERE prod.id > 0 AND prod.status NOT IN (3) AND prod.has_variants!='' 
                GROUP BY prod.id
                HAVING var_qty_total != CAST(prod.qty AS DECIMAL)) as grouped_qty"
        )->row()->count;

        echo "Corrigindo $products_to_fix_count produtos.\n";
        while ($products_to_fix_count > 0) {
            $queue_size = (int) $this->db->query("SELECT COUNT(*) AS queue_size FROM queue_products_marketplace")->row()->queue_size;
            if ($queue_size <= 500) {
                echo "Corrigindo 200 produtos";
                echo "\n";

                $this->db->query(
                    "UPDATE products p
                        INNER JOIN (
        	                SELECT prod.id, SUM(IF(prod_vars.qty = '', 0, prod_vars.qty)) AS newQty
        	                FROM products prod
        	                JOIN prd_variants AS prod_vars ON prod_vars.prd_id = prod.id
        	                WHERE prod.id>0 AND prod.status NOT IN(3)
        	                GROUP BY prod.id, prod.qty
			                HAVING newQty != CAST(prod.qty AS DECIMAL)
			                LIMIT 200
                        ) newVals ON p.id = newVals.id
                    SET p.qty = newVals.newQty 
                    WHERE p.status NOT IN (3) AND p.has_variants !='';"
                );

                $products_to_fix_count -= 200;
            } else {
                echo "Fila está muito cheia, não podemos atualizar o estoque para não inserir na fila. Aguardando 30 segundos. Rows: $queue_size\n";
                sleep(30);
            }
        }
		echo "Excluindo o Job...\n";
		$this->db->delete('calendar_events', array('module_path' => 'Automation/Fix/FixPrdQty'));
        $this->log_data('batch', $log_name, 'Todos os produtos foram corrigidos', "I");
    }
}
