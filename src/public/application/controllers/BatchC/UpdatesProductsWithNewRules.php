<?php

/**
 * @author Fábio Monteiro
 */
class UpdatesProductsWithNewRules extends BatchBackground_Controller
{
	/**
	 * @var array|null $blockingRules
	 * @var array|null $permissionRules
	 */
	private $blockingRules;
	private $permissionRules;
	private $permissionRulesChanged;

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

		$this->load->model('model_blacklist_words');
		$this->load->model('model_whitelist');
		$this->load->model('model_products');
        $this->load->library('BlacklistOfWords');
    }

	// php index.php BatchC/UpdatesProductsWithNewRules run
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
		$this->setNewOrUpdatedBlockingRules();
		$this->setNewOrUpdatedPermissionRules();
		$this->updateProductsStatus($params);
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}

	/**
     * Define novas regras de bloqueio
	 */
	private function setNewOrUpdatedBlockingRules()
	{
		echo "Verificando se existem regras de bloqueio novas ou recentemente atualizadas\n";
		$this->blockingRules = $this->model_blacklist_words->getNewOrUpdatedBlockingRules();
	}

	/**
     * Define novas regras de permissão
	 */
	private function setNewOrUpdatedPermissionRules()
	{
		echo "Verificando se existem regras de permissão novas ou recentemente atualizadas\n";
		$this->permissionRulesChanged = $this->model_whitelist->getNewOrUpdatedPermissionRules();
		
		if (empty($this->blockingRules) ) { // se não tem  nenhuma lista de bloqueio nova, pego só a lista de whitelist nova
			$this->permissionRules = $this->permissionRulesChanged; 
		}
		else { // se tem alguma de bloqueio, pego todas de whitelist
			$this->permissionRules = $this->model_whitelist->getAllWhiteListRules();
		}

	}

	/**
     * @param int|null $idDebug Código do produto para debug
     *
	 * @return bool
	 */
	private function updateProductsStatus($idDebug)
    {
        $idDebug = $idDebug == 'null' ? false : $idDebug;

        // não existem atualização ou novas regras
		if (!$this->blockingRules && !$this->permissionRules) {
		    echo "Sem regras que necessitem atualizar algum produto\n";
            return false;
        }

        $msgLogBlockAndPermission = "Regras a serem aplicadas\n\nblack_list:\n".json_encode($this->blockingRules)."\n\nwhite_list:".json_encode($this->permissionRules);

		echo $msgLogBlockAndPermission."\n";
        $this->log_data(
            'batch',
            __CLASS__.'/'.__FUNCTION__,
            $msgLogBlockAndPermission
        );
		
        //$this->db->trans_begin();

        // define offset iniciando em  0(zero)
		$offset = 0;
		echo "Buscando produtos\n";
		// criado rótulo de destino
		$exist = true;
		while($exist) {
	        // recuperar 200 produtos por vez para fazer a leitura
			$products = $this->getProductsForRules($offset, self::PAGINATION_TO_GET_PRODUCTS, $idDebug);
	
			// não foi encontrado produtos
			if (!$products) {
	            /*if ($this->db->trans_status() === FALSE){
	                $this->db->trans_rollback();
	                $this->log_data('api',__CLASS__.'/'.__FUNCTION__,'Não foi possível salvar os dados de black list e white list',"E");
	            }
	
	            $this->db->trans_commit();*/
	
	            if (!$idDebug) {
	                // percorre as regras de permissão para atualizar o new_or_update para 0(zero)
	                if ($this->permissionRules)
	                    foreach ($this->permissionRulesChanged as $permissionRule) { // altero somente as que tinham mudado inicialmente. Durante o processamento, alguma outra regra pode ter mudado e tem que rodar novamente. 
	                    	$this->model_whitelist->update($permissionRule['id'], ['new_or_update' => Model_whitelist::OLD_RULE]);
						}
	
	                // percorre as regras de bloqueio para atualizar o new_or_update para 0(zero)
	                if ($this->blockingRules)
	                    foreach ($this->blockingRules as $blockingRule)
	                        $this->model_blacklist_words->update($blockingRule['id'], ['new_or_update' => Model_blacklist_words::OLD_RULE]);
	                else $this->blockingRules = $this->model_blacklist_words->getAllBlockingRules();
	            } else {
	                if (!$this->blockingRules) $this->blockingRules = $this->model_blacklist_words->getAllBlockingRules();
	            }
				$exist = false; 
	        }
	
			foreach ($products as $product) {
				// atualizo o status do produto caso necessário
				$checkBlock = $this->blacklistofwords->updateStatusProductAfterUpdateOrCreateRules($product, $product['id'], $this->permissionRules, $this->blockingRules);
	
	            echo "[PRD_ID = {$product['id']}] RETURN -> ".json_encode($checkBlock)."\n";
	
	            if ($checkBlock['blocked'] && $checkBlock['original_status'] != 4) {
	                echo "----> [PRD_ID = {$product['id']}] BLOQUEADO \n";
	                $this->log_data(
	                    'batch',
	                    __CLASS__ . '/' . __FUNCTION__,
	                    "Produto {$product['id']} bloqueado... ".json_encode($checkBlock)
	                );
	            }
	
	            if (!$checkBlock['blocked'] && $checkBlock['original_status'] == 4) {
	                echo "----> [PRD_ID = {$product['id']}] LIBERADO \n";
	                $this->log_data(
	                    'batch',
	                    __CLASS__ . '/' . __FUNCTION__,
	                    "Produto {$product['id']} liberado... ".json_encode($checkBlock)
	                );
	            }
	
				echo "____________________________________________________\n";
			}
	
			// incrementar 200 no offset para não pegar produtos repetidos
	        $offset += self::PAGINATION_TO_GET_PRODUCTS;
		}
	}

	public function getProductsForRules($offset, $limit, $idDebug = false)
    {

        $query = $this->db->select(
            [
                'products.id',
                'products.name',
                'products.description',
                'products.status',
                'products.image',
                'sku',
                'products.store_id',
                'category_id',
                'brand_id'
            ]
			);        
        if ($idDebug) {
            $query->where('products.id', $idDebug);
        }
        return $query->where_in('products.status', [1, 4])
            ->limit($limit)
            ->offset($offset)
			->order_by('products.id','desc')
            ->get('products')
            ->result_array();
    }
}
