<?php

class ProductsModified extends BatchBackground_Controller
{
	
	 const PAGINATION_TO_GET_PRODUCTS = 5000;

	/**
	 * @return void
	 */
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

		$this->load->model('model_products_modified');
		$this->load->model('model_products');
    }

	// php index.php BatchC/ProductsModified run
	/**
	 * @var string $log_name
	 * @param mixed|null $id
	 * @param mixed|null $params
	 * @return void
	 */
	public function run($id = null, $params = null)
	{
		/* inicia o job */
		$this->setIdJob($id); 
		$log_name = $this->router->fetch_class().'/'.__FUNCTION__;
		if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__)) {
			$this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return;
		}
		$this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");
		
		/* faz o que o job precisa fazer */
		$this->productsModifiedinSeller($params);
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}

	/**
     * @param int|null $idDebug id do produto para debug
     *
	 * @return bool
	 */
	private function productsModifiedinSeller($idDebug)
    {
        $log_name = $this->router->fetch_class().'/'.__FUNCTION__;
        $idDebug = $idDebug == 'null' ? false : $idDebug;

        // define offset iniciando em  0(zero)
		$offset = 0;
		echo "Buscando produtos \n\n";
		
		$exist = true;
		while($exist) {
	        // recuperar 200 produtos por vez para fazer a leitura
			$products =  $this->model_products->getProductsModified($offset, self::PAGINATION_TO_GET_PRODUCTS, $idDebug);
	
			// não foi encontrado produtos
			if (!$products) {
				$exist = false; 
                
                $this->log_data('batch',$log_name,'Nenhum Produto encontrado  \n\n',"I");
                echo "Nenhum Produto encontrado  \n\n";
                echo "Encerrando o job! \n\n";
                return ;
	        }
	
			foreach ($products as $product) {
                // Verifica se o produto já existe na tabela products_modified
                $existingProduct = $this->model_products_modified->getByProductId($product['id']);
                
                if ($existingProduct) {
                 
                    $updateData = [
                        'sku' => $product['sku'],
                        'data_ultModificacao' => $product['date_update']
                    ];
            
                    $this->model_products_modified->update($updateData, $existingProduct['id']);

                    echo "[PRD_ID = {$product['id']}] Produto atualizado \n\n";
                } else {
        
                    $insertData = [
                        'sku' => $product['sku'],
                        'prd_id' => $product['id'],
                        'store_id' => $product['store_id'],
                        'date_create' => date('Y-m-d H:i:s'), 
                        'data_ultModificacao' => $product['date_update'] 
                    ];
            
                    $this->model_products_modified->create($insertData);

                    echo "[PRD_ID = {$product['id']}] Produto criado \n\n";
                }
            }
	
			// incrementar 200 no offset para não pegar produtos repetidos
	        $offset += self::PAGINATION_TO_GET_PRODUCTS;
		}
	}

	
}
