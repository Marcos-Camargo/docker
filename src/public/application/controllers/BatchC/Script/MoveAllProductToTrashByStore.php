<?php

class MoveAllProductToTrashByStore extends BatchBackground_Controller
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
            'logged_in' => true
        );
        $this->session->set_userdata($logged_in_sess);
        $this->load->library('JWT');
    }
    // php index.php BatchC/Script/MoveAllProductToTrashByStore run null store_id
    function run($id = null, $params = null)
    {
        if($params==="store_id"){
            echo("Configure a loja para o script e tenha certeza que é ela.");
            return;
        }
        $this->setIdJob($id);
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
        if (!$this->gravaInicioJob($this->router->fetch_class(), __FUNCTION__)) {
            get_instance()->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
            return;
        }
        get_instance()->log_data('batch', $log_name, 'start ' . trim($id . " " . $params), "I");
        $this->db->trans_begin();
        $stores = $this->model_stores->getStoreById($params);
        $products = $this->model_products->getProductsByStore($stores['id']);
        $this->db->trans_begin();
        foreach ($products as $product) {
            $update = [];
            $update['product_id_erp'] = null;
            $time = strtotime("now");
            $update['sku'] = "DEL_" . $product['sku'] . "_{$time}";
            $update['status'] = 3;
            // dd($update, $product);
            $variants = $this->model_products->getVariantsByProd_id($product['id']);
            foreach ($variants as $variant) {
                $updateVar = [];
                $updateVar['variant_id_erp'] = null;
                $updateVar['sku'] = "DEL_" . $variant['sku'] . "_{$time}";
                $updateVar['status'] = 3;
                // dd($updateVar, $variant, $variant['variant']);
                $this->model_products->updateVar($updateVar, $product['id'], $variant['variant']);
            }
            $this->model_products->update($update, $product['id']);
        }
        $products = $this->model_products->getProductsByStore($stores['id']);
        // dd(123, count($products), $products[0]);
        // sleep(120);
        $this->db->trans_commit();
        get_instance()->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }
}
