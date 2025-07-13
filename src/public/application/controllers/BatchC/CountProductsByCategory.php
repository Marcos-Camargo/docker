<?php

//php index.php BatchC/CountProductsByCategory run

class CountProductsByCategory extends BatchBackground_Controller {

    public function __construct()
    {
        parent::__construct();
        // log_message('debug', 'Class BATCH ini.');

        $logged_in_sess = array(
            'id' => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp' => 1,
            'userstore' => 0,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);

        // carrega os modulos necessários para o Job
        $this->load->model('model_products');
        $this->load->model('model_category');
		$this->load->model('model_products_catalog');

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
        echo "Pegando categorias para atualizar qtd de produtos \n";
        $this->countProductsByCategory();

        /* encerra o job */
        $this->log_data('batch',$log_name,'finish',"I");
        $this->gravaFimJob();
    }

    function countProductsByCategory()
    {
        $log_name =$this->router->fetch_class().'/'.__FUNCTION__;
        $categories = $this->model_category->getCategoryData();
        $logUpdated = array();

        foreach ($categories as $category) {
            $qtyProducts = $this->model_products->getCountProductsCategory($category['id']);
            if (!$this->model_category->update(array('qty_products' => $qtyProducts), $category['id'])) {
                $this->log_data('batch',$log_name,"Erro para atualizar a quantidade produtos que a categoria({$category['id']}) - {$category['name']}, qtd_products={$qtyProducts}","E");
                continue;
            }
            array_push($logUpdated, array('idCat' => $category['id'], 'qty_products' => $qtyProducts));
        }
        $this->log_data('batch',$log_name,'Categorias atualizadas!!! log='.print_r($logUpdated, true),"I");
        echo json_encode($logUpdated, true);

    }
}