<?php

/**
 * @property Model_products $model_products
 * @property Model_integrations $model_integrations
 */
class RemoveWrongProductAttributes extends BatchBackground_Controller
{
    public function __construct()
    {
        parent::__construct();

        $logged_in_sess = array(
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'userstore' => 0,
            'logged_in' => true
        );
        $this->session->set_userdata($logged_in_sess);
        $this->load->model('model_products');
        $this->load->model('model_integrations');
    }

    // php index.php BatchC/Automation/Fix/RemoveWrongProductAttributes run
    function run($id = null, $params = null)
    {
        $this->setIdJob($id);
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
        if (!$this->gravaInicioJob('Automation/' . $this->router->fetch_class(), __FUNCTION__)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
            return;
        }
        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $params), "I");

        $this->fixAttributes();

        $this->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }

    public function fixAttributes()
    {
        $limit = 200;
        $offset = 0;

        $integrations = array_map(function($integration) {
            return $integration['int_to'];
        },$this->model_integrations->getIntegrationsbyStoreId(0));

        while (true) {
            $products = $this->model_products->listProduct($offset, $limit);
            if (count($products) == 0) {
                break;
            }
            $offset += $limit;

            foreach ($products as $product) {
                $product_id = $product['id'];

                foreach ($integrations as $integration) {
                    $product_attributes = $this->db->select('pam.*')
                        ->join('atributos_categorias_marketplaces acm', 'acm.id_atributo = pam.id_atributo', 'left')
                        ->join('products p', 'p.id = pam.id_product')
                        ->join('categorias_marketplaces cm', 'cm.category_id = left(substr(p.category_id,3),length(p.category_id)-4) AND cm.category_marketplace_id = acm.id_categoria', 'left')
                        ->where('cm.int_to IS NOT NULL', null, false)
                        ->where('pam.id_product', $product_id)
                        ->where('pam.int_to', $integration)
                        ->get('produtos_atributos_marketplaces pam')
                        ->result_array();

                    $attributes_id = array_map(function ($attribute) {
                        return $attribute['id_atributo'];
                    }, $product_attributes);

                    // Consulta quantos atributos existem no produto para o marketplace.
                    $rows_in_product = $this->db->where(array(
                        'id_product' => $product_id,
                        'int_to' => $integration
                    ))->get('produtos_atributos_marketplaces')->num_rows();

                    if ($rows_in_product == 0) {
                        continue;
                    }

                    // Não existem atributos no produto para o marketplace para ser excluído.
                    if ($rows_in_product == count($attributes_id)) {
                        continue;
                    }

                    // Remove os atributos.
                    $this->db->where(array(
                        'id_product' => $product_id,
                        'int_to'     => $integration
                    ));

                    if (!empty($attributes_id)) {
                        $this->db->where_not_in('id_atributo', $attributes_id);
                    }

                    $this->db->delete('produtos_atributos_marketplaces');

                    echo "[OK] $product_id - $integration\n";
                }
            }
        }

        // Ao fim do processamento o evento pode ser excluído.
        $this->db->delete('calendar_events', array('module_path' => 'Automation/Fix/RemoveWrongProductAttributes'));
    }

}
