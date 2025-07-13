<?php

/**
 * Class OrtobomCheckProductsMain
 */
class OrtobomCheckProductsMain extends BatchBackground_Controller
{
	/**
	 * @var array
	 */
	private $companyMain;

    /**
     * @var array
     */
	private $storeMain;

    /**
     * OrtobomCheckProductsMain constructor.
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
		
		// carrega os modulos necessários para o Job
		$this->load->model('model_settings');
		$this->load->model('model_products');
        $this->load->model('model_company');
        $this->load->model('model_stores');
		$this->load->model('model_products_catalog');
		$this->load->model('model_sika_products_creation_control');
		$this->load->model('model_catalogs');
		$this->load->model('model_job_schedule');
    }

    /**
     * Início do job
     *
     * @param int|null     $id      Código do job que será executado
     * @param string|null  $params  Parametro adicional
     */
	public function run(int $id = NULL, string $params = NULL)
	{
		$this->setIdJob($id);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$modulePath = (str_replace("BatchC/", '',$this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
			$this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}
		$this->log_data('batch',$log_name,'start '.trim($id." ".$params));

		if ($this->getDataStoreMain()) {
			$this->createProducts();
			$this->updateStockPrice();
		}
		else echo "Não encontrou loja configurada no parametro 'publish_only_stores'\n";

		/* encerra o job */
		$this->log_data('batch', $log_name, 'finish');
		$this->gravaFimJob();
	}

    /**
     * Recupera o código da loja e empresa Main para ser dona do produto publicado
     *
     * @return int|null
     */
    private function getDataStoreMain()
    {
        $log_name = $this->router->fetch_class().'/'.__FUNCTION__;

        // recupera loja main
        $onlyStorePublishedSetting = $this->model_settings->getSettingDatabyName('publish_only_one_store_company');
        if ($onlyStorePublishedSetting && $onlyStorePublishedSetting['status'] == 1) {
            $storePublished = $onlyStorePublishedSetting['value'];
        } else {
            echo "parametro 'publish_only_one_store_company' não existe ou não está ativo.\n";
            return false;
        }

        //pego a loja
        $this->storeMain = $this->model_stores->getStoresData($storePublished);
        if (is_null($this->storeMain)) {
            $erro = "Não encontrei nenhuma loja com o mesmo CNPJ da empresa {$this->companyMain['name']} garanta que a loja franqueadora master tenha o CNPJ {$this->companyMain['CNPJ']}\n";
            echo $erro."\n";
            $this->log_data('batch',$log_name, $erro ,"E");
            return false;
        }

        // pego a empresa
        $this->companyMain = $this->model_company->getCompanyData($this->storeMain['company_id']);
        if (is_null($this->companyMain)) {
            $erro = 'Não existe nenhuma empresa configurada.';
            echo "{$erro}\n";
            $this->log_data('batch',$log_name, $erro ,"E");
            return false;
        }


        // pego o catálogo
        /*$catalog = $this->model_catalogs->getCatalogByName('Catálogo Sika');
        if (!$catalog) {
            $erro = 'Não existe nenhum catálogo chamado '.'Catálogo Sika'.'. Crie o catálogo no sistema antes.';
            echo $erro."\n";
            $this->log_data('batch',$log_name, $erro ,"E");
            return false;
        }
        $this->catalogSika = $catalog;*/

        return true;
    }

	/**
	 * Retorna o código do evento no calendario (calendar_events.ID ou job_schedule.server_id)
	 *
	 * @return int|null
	 */
	private function getIdCalendar(): ?int
	{
		$data = $this->model_job_schedule->find(['id' => $this->getIdJob()]);

		return $data['server_id'];
	}

	/**
	 * Cria novos produtos na loja Main que foram cadastrados no catálogo, sem precisar escolher na vitrine.
	 *
	 * @return void
	 */
	private function createProducts(): void
	{
		$run_date 	= date("Y-m-d H:i:s");
		$find_date 	= "2020-01-01 00:00:00";
		$gscc 		= $this->model_sika_products_creation_control->getData($this->getIdCalendar());

		if ($gscc) $find_date = $gscc['last_run'];

		foreach ($this->model_catalogs->getAllCatalogs() as $catalog) {
			// vejo nas lojas se teve produtos de catalogo novos para serem criados
			$catalog_stores = $this->model_catalogs->getActiveCatalogsStoresDataByCatalogId($catalog['id']);
			foreach ($catalog_stores as $catalog_store) {

                if ($catalog_store['company_id'] != $this->companyMain['id']) {
                    echo "loja={$catalog_store['store_id']}, company={$catalog_store['company_id']} não precisa fazer nada, não é da loja configurada\n";
                    continue;
                }

				echo "Buscando produtos para a loja {$catalog_store['store_id']} do catálogo {$catalog['name']} desde a última execução em {$find_date}\n";
				$offset = 0;
				$limit  = 20;
				echo "Procurando produtos novos e ativos desde a última execução em {$find_date}\n";
				while (true) {
					// consulta dados dos produtos do catalogo
					$products_catalog = $this->model_products_catalog->getProductsCatalogUpdated($catalog['id'], $find_date, $offset, $limit);

					// não encontrou mais produtos, paro o while
					if (!$products_catalog) break;

					$offset += $limit;
					foreach ($products_catalog as $product_catalog)
						$this->verifyAndCreateProduct($product_catalog, $catalog_store);
				}
			}
		}

		$this->model_sika_products_creation_control->replace(array('id' => $this->getIdCalendar(), 'last_run' => $run_date));
	}

	/**
	 * Atualiza o estoque dos produtos Main, de acordo com os produtos da fabricas
	 * Sempre considerar o maior estoque, o preço será o mesmo do catálogo, o seller não atualiza
	 *
	 * @return void
	 */
	private function updateStockPrice(): void
	{
		foreach ($this->model_catalogs->getAllCatalogs() as $catalog) {
			$offset = 0;
			$limit = 20;
			echo "Procurando estoques da lojas\n";
			while (true) {
				$products_catalog = $this->model_products_catalog->getProductsCatalogbyCatalogid($catalog['id'], $offset, $limit);

				// não encontrou mais produtos, paro o while
				if (!$products_catalog) break;

				$offset += $limit;
				foreach ($products_catalog as $product_catalog)
					if (is_null($product_catalog['prd_principal_id']))
						$this->checkProductUpdateStockPrice($product_catalog);
			}
		}
	}

	/**
	 * Verifica se produto de catalogo já existe, se não atualiza
	 *
	 * @param 	array	$product_catalog	Dados do produto do catalogo
	 * @param 	array 	$catalog_store		Dados da loja do catalogo
	 * @return 	void
	 */
	private function verifyAndCreateProduct(array $product_catalog, array $catalog_store): void
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$data_prod = array(
			'name' 					=> $product_catalog['name'],
			'price' 				=> $product_catalog['price'],
			'image' 				=> 'catalog_'.$product_catalog['id'],
			'principal_image' 		=> $product_catalog['principal_image'],
			'description' 			=> $product_catalog['description'],
			'attribute_value_id' 	=> $product_catalog['attribute_value_id'],
			'brand_id' 				=> json_encode(array($product_catalog['brand_id'])),
			'category_id' 			=> json_encode(array($product_catalog['category_id'])),
			'status' 				=>  $product_catalog['status'],
			'EAN' 					=> $product_catalog['EAN'],
			'codigo_do_fabricante' 	=> (is_null($product_catalog['brand_code'])) ? '' : $product_catalog['brand_code'],
			'peso_liquido' 			=> $product_catalog['net_weight'],
			'peso_bruto' 			=> $product_catalog['gross_weight'],
			'largura' 				=> $product_catalog['width'],
			'altura' 				=> $product_catalog['height'],
			'has_variants' 			=> $product_catalog['has_variants'],
			'profundidade' 			=> $product_catalog['length'],
			'garantia' 				=> $product_catalog['warranty'],
			'NCM' 					=> $product_catalog['NCM'],
			'origin' 				=> $product_catalog['origin'],
			'product_catalog_id' 	=> $product_catalog['id']
		);

		echo " Procurando ".$product_catalog['id']. " para a loja ". $catalog_store['store_id']. "\n";

		$product = $this->model_products_catalog->getProductByProductCatalogIdAndStoreId($product_catalog['id'],$catalog_store['store_id']);

		// essa loja não tem o produto. Vou criar
		if ($product === NULL) {

		    // não cria produtos para loja que não seja a configurada
		    if ($catalog_store['store_id'] != $this->storeMain['id']) return;

			echo "Não achei ".$product_catalog['id']." para a loja ".$catalog_store['store_id']."\n";
			$data_prod = array_merge($data_prod,
				array(
					'sku' 						=> str_replace(".","",$product_catalog['EAN']).$product_catalog['id'],
					'qty' 						=> 0,
					'store_id' 					=> $catalog_store['store_id'],
					'company_id' 				=> $catalog_store['company_id'],
					'situacao' 					=> 2,
					'prazo_operacional_extra'   => 0,
				)
			);

			echo "sku ". str_replace(".","",$product_catalog['EAN']) . " \n";

			$create = $this->model_products->create($data_prod);

			if($create != false) {

				$log_var = array('id'=> $create);
				$this->log_data('Products','create',json_encode(array_merge($log_var,$data_prod)));
				echo "Produto criado ".json_encode(array_merge($log_var,$data_prod)). "\n";

				if ($product_catalog['has_variants'] !== "") {

					$product_variants = $this->model_products_catalog->getProductCatalogVariants($product_catalog['id']);

					foreach ($product_variants as $product_variant) {

						if (empty($product_variant['EAN'])) $skuvar = $product_catalog['EAN'].$product_variant['variant_id'];
						else $skuvar = $product_variant['EAN'];

						$data_var = Array (
							'prd_id' 				=> $create,
							'variant' 				=> $product_variant['variant_id'],
							'name' 					=> $product_variant['name'],
							'sku' 					=> $skuvar,
							'price' 				=> $product_catalog['price'],
							'qty' 					=> 0,
							'image' 				=> $product_variant['principal_image'],
							'status' 				=> 1,
							'EAN' 					=> $product_variant['EAN'],
							'codigo_do_fabricante' 	=> '',
						);

						$this->model_products->createvar($data_var);
						$this->log_data('Products','create_variation',json_encode($data_var));
						echo "Variação de Produto criado ".json_encode($data_var). "\n";
					}
				}
			}
			else {
				$erro = 'Erro ao criar produto. Dados:'.json_encode($data_prod);
				echo $erro."\n";
				$this->log_data('batch',$log_name, $erro ,"W");
				return;
			}
		}
		else {  // alterou algo, então mudo no produto
			$this->log_data('Products','edit_before',json_encode($product));
			$update = $this->model_products->update($data_prod,$product['id']);

			if ($update) {

				$log_var = array('id'=> $product['id']);
				$this->log_data('Products','edit_after',json_encode(array_merge($log_var,$data_prod)));
				echo "Produto Alterado ".json_encode(array_merge($log_var,$data_prod)). "\n";

				if ($product_catalog['has_variants'] !== "") {

					$product_variants = $this->model_products_catalog->getProductCatalogVariants($product_catalog['id']);

					foreach ($product_variants as $product_variant) {

						$variant_prd = $this->model_products->getVariants($product['id'], $product_variant['variant_id']);

						if (!$variant_prd) { // a variant do produto não existe, eu crio

							if (is_null($product_variant['EAN'])) $skuvar = $product_catalog['EAN'].$product_variant['variant_id'];
							else $skuvar = $product_variant['EAN'];

							$data_var = Array (
								'prd_id' 				=> $product['id'],
								'variant' 				=> $product_variant['variant_id'],
								'name' 					=> $product_variant['name'],
								'sku' 					=> $skuvar,
								'price' 				=> $product_catalog['price'],
								'qty' 					=> 0,
								'image'					=> $product_variant['principal_image'],
								'status' 				=> 1,
								'EAN' 					=> $product_variant['EAN'],
								'codigo_do_fabricante' 	=> '',
							);

							$this->model_products->createvar($data_var);

							$this->log_data('Products','create_variation',json_encode($data_var));
							echo "Variação de Produto criado ".json_encode($data_var). "\n";
						}
						else  {	// a variante existe, altero
							$data_var = Array (
								'prd_id' => $product['id'],
								'name' 	 => $product_variant['name'],
								'price'  => $product_catalog['price'],
								'image'  => $product_variant['principal_image'],
								'EAN' 	 => $product_variant['EAN'],
							);
							$this->log_data('Products','update_variation_before',json_encode($variant_prd));

							$this->model_products->updateVar($data_var, $product['id'], $product_variant['variant_id'] );

							$this->log_data('Products','update_variation_after',json_encode($data_var));
							echo "Variação de Produto alterado ".json_encode($data_var). "\n";
						}
					}
				}
			}
			else {
				$erro = 'Erro no update do produto '.$product['id'].'. Dados:'.json_encode($data_prod);
				echo $erro."\n";
				$this->log_data('batch',$log_name, $erro ,"W");
				return;
			}
		}
	}

	/**
	 * Le todos os produtos em uso no catalogo para atualizar preço e estoque 
	 * 
	 * @param 	array 	$product_catalog	Produto de catalogo para encontrar produtos das fabricar para atualizar preço e estoque
	 * @return 	void
	 */
	private function checkProductUpdateStockPrice(array $product_catalog)
	{
		echo "Checando estoque do product_catalog_id ".$product_catalog['id'].' nome '.$product_catalog['name']."\n";

		$offset 		= 0;
		$limit 			= 20;
		$stockWin 		= 0;
		$quantityMain	= NULL;
		$priceMain		= NULL;
		$productMain	= NULL;
		$productWinner 	= NULL;
		$priceCatalog 	= $product_catalog['price'];

		while (true) {
			$products = $this->model_products_catalog->getProductsByProductCatalogId($product_catalog['id'], $offset, $limit);

			// não encontrou mais produtos, paro o while
			if (!$products) break;

			$offset += $limit;

			foreach($products as $product) {

			    if ($product['company_id'] != $this->companyMain['id']) continue;

				if ($product['store_id'] == $this->storeMain['id']) {
					$productMain 	= $product['id'];
					$quantityMain	= $product['qty'];
					$priceMain 		= $product['price']; // preço da loja Main que será usada como base
				}
				else {
					// preço do produto
					//$priceCatalog = $product['price']; // esse não pode ser alterado

					// tem pelo menos 1
					if (is_null($productWinner)) $productWinner = $product['id'];

					// estoque maior que o estoque vencedor atual
					if ($product['qty'] > $stockWin) {
						$stockWin 	    = $product['qty'];
						$productWinner 	= $product['id'];
					}
				}
			}
		}

		// se o novo estoque encontrado for diferente do estoque atual do produto Main, atualizo no produto Main
		if ($stockWin != $quantityMain) {
            $this->model_products->update(array('qty' => $stockWin), $productMain);

            // se existir variação atualizar a quantidade das variações do vencedor atual
            if ($product_catalog['has_variants'] !== "") {

                $variants = $this->model_products->getVariants($productMain); // recupero as variações

                foreach ($variants as $variant)
                    $this->model_products->updateVar(array('qty' => $variant['qty']), $productMain, $variant['variant']);
            }
        }

		// se o preço do catálogo mudou, altera o preço dos produto com o novo preço do catalogo
		if ($priceMain !== NULL && (float)$priceMain != (float)$priceCatalog) {
		    echo "[UPDATE_PRICE] - NEW_PRICE={$priceCatalog} - CATALOG={$product_catalog['id']}\n";

            $desconto = round((1-$product_catalog['price'] / $priceMain)*100,2);
		    
			$this->model_products_catalog->updateProductsBasedFromProductCatalog(array('price' => $priceCatalog), $product_catalog['id']);
			$this->model_products_catalog->updateSimple(array('price' => $priceCatalog), $product_catalog['id']);
            $this->model_products_catalog->disableProductsMaximumDiscount($product_catalog['id'], $desconto);
		}
	}
}
