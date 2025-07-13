<?php

use phpDocumentor\Reflection\Types\Array_;

require APPPATH . 'controllers/Api/queue/ProductsConectala.php';

/**
 * Class ProductsWake
 * @property CI_DB_query_builder $db
 * @property Bucket $bucket
 */

class ProductsWake extends ProductsConectala
{

	var $inicio;   // hora de inicio do programa em ms
	var $auth_data;
	var $int_to_principal;
	var $empty_ean = false;
	var $integration;
	var $numft = 0;
	var $statusOcc = 'enabled';
	var $bling_ult_envio = null;
	var $adlink = null;   // http da marketplace
	var $skuAtributtes = [];
	var $prdAtributtes = [];
	var $alreadyCategory = false;
	var $variacoes_default = null;
	var $update_sku_specifications = false;
	var $update_sku_wake = false;
	var $update_product_wake = false;
	var $update_images_specifications = false;

	var $maxAttempts = 3; // Número máximo de tentativas
	var $attempt = 0; // Contador de tentativas
	var $totalTimeSleep = 0; // Tempo até tentar outra requisição

	
	var $promo = false; // valida se tem campanha ou promo para enviar preço do sku ou produto
	const COLOR_DEFAULT = 'Cor';
	const SIZE_DEFAULT = 'TAMANHO';
	const VOLTAGE_DEFAULT = 'VOLTAGEM';
	const FLAVOR_DEFAULT = 'SABOR';
	const DEGREE_DEFAULT = 'GRAU';
	const SIDE_DEFAULT = 'LADO';

	public function __construct()
	{
		parent::__construct();


		$this->load->model('model_sellercenter_last_post');
		$this->load->model('model_brands');
		$this->load->model('model_category');
		$this->load->model('model_categorias_marketplaces');
		$this->load->model('model_brands_marketplaces');
		$this->load->model('model_atributos_categorias_marketplaces');
		$this->load->model('model_marketplace_prd_variants');
		$this->load->model('model_collections');
		$this->load->model('model_settings');
		$this->load->library('Bucket');

		$this->variacoes_default = $this->model_settings->getValueIfAtiveByName('variacao_valor_default');
		$this->variant_color  = $this->model_settings->getValueIfAtiveByName('variant_color_attribute');
		if (!$this->variant_color) {  $this->variant_color = self::COLOR_DEFAULT; }

		$this->variant_size  = $this->model_settings->getValueIfAtiveByName('variant_size_attribute');
		if (!$this->variant_size) {  $this->variant_size = self::SIZE_DEFAULT; }

		$this->variant_voltage  = $this->model_settings->getValueIfAtiveByName('variant_voltage_attribute');
		if (!$this->variant_voltage) {  $this->variant_voltage = self::VOLTAGE_DEFAULT; }

		$this->variant_flavor  = $this->model_settings->getValueIfAtiveByName('variant_flavor_attribute');
		if (!$this->variant_flavor) {  $this->variant_flavor = self::FLAVOR_DEFAULT; }
		
		$this->variant_degree  = $this->model_settings->getValueIfAtiveByName('variant_degree_attribute');
		if (!$this->variant_degree) {  $this->variant_degree = self::DEGREE_DEFAULT; }
		
		$this->variant_side  = $this->model_settings->getValueIfAtiveByName('variant_side_attribute');
		if (!$this->variant_side) {  $this->variant_side = self::SIDE_DEFAULT; }

		$this->maxAttempts  = intval($this->model_settings->getValueIfAtiveByName('max_attemp_request'));
		$this->totalTimeSleep  = intval($this->model_settings->getValueIfAtiveByName('max_sleep_request'));

	}

	public function index_post()
	{
		$this->inicio = microtime(true);
		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

		$integrationSettings = $this->getIntegrationSettings();
        if($integrationSettings){
            $this->update_sku_specifications = $integrationSettings->update_sku_specifications;
            $this->update_sku_wake = $integrationSettings->update_sku_vtex;
			$this->update_images_specifications = $integrationSettings->update_images_specifications;
			$this->update_product_wake = $integrationSettings->update_product_vtex;
        }

		// verifico se quem me chamou mandou a chave certa
		$this->receiveData();

		// verifico se é cadastrar, inativar ou alterar o produto
		$this->checkAndProcessProduct();

		// Acabou a importação, retiro da fila
        if ($this->remove_product_queue) {
            $this->RemoveFromQueue();
        }

		$fim = microtime(true);
		echo "\nExecutou em: " . ($fim - $this->inicio) * 1000 . " ms\n";
		return;
	}

	public function checkAndProcessProduct()
	{

		$this->getkeys();
		// faço o que tenho q fazer
		parent::checkAndProcessProduct();
	}

	function insertProduct()
	{
		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
		echo "Insert" . "\n";

		$sku = 'P' . $this->prd['id'] . 'S' . $this->prd['store_id'] . $this->int_to;
		$this->prd_to_integration['skubling'] = $sku;

		$skumkt = $sku;
		// limpa os erros de transformação existentes da fase de preparação para envio

		$this->model_errors_transformation->setStatusResolvedByProductId($this->prd['id'], $this->int_to);

		// pego informações adicionais como preço, estoque e marca .
		if ($this->prepareProduct($sku) == false) {
			return true;
		};		

		//agora envio os skus
        $variations_successfully = array();
		if ($this->prd['has_variants'] !== '') {
			if (count($this->variants) == 0) {
				$erro = "As variações deste produto " . $this->prd['id'] . " sumiram.";
				echo $erro . "\n";
				$this->log_data('batch', $log_name, $erro, "E");
				die;
			}
			foreach ($this->variants as $variant) {
                if ($variant['status'] != 1) {
                    $this->disableProductVariant(null, $variant);
                } else {
                    if ($this->insertProductVariant($variant)) {
                        $variations_successfully[] = $variant['variant'];
                    }
                }
			}
		} else {
			if ($this->insertProductVariant(null) == false) {
				return true;
			}
		}

		$this->prd_to_integration['skubling'] = $sku;
		$this->prd_to_integration['skumkt'] = $skumkt;
		if ($this->prd['has_variants'] != '') {
			foreach ($this->variants as $variant) {
                if (in_array($variant['variant'], $variations_successfully)) {
                    $this->setDataVariantToUpdate($variant);
                    $this->updateSellerCenterLastPost($this->prd, $variant);
                }
			}
		} else {
			$this->updateSellerCenterLastPost($this->prd, null);
		}
		return true;
	}

	function updateProduct()
	{
		
		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
		echo 'Update' . "\n";
		//verificar se a imagem existe
		$sku = 'P' . $this->prd['id'] . 'S' . $this->prd['store_id'] . $this->int_to;
		$this->prd_to_integration['skubling'] = $sku;
		$skumkt = $sku;
		// limpa os erros de transformação existentes da fase de preparação para envio
		$this->model_errors_transformation->setStatusResolvedByProductId($this->prd['id'], $this->int_to);

		// pego informações adicionais como preço, estoque e marca .
		if ($this->prepareProduct($sku) == false) {
			return true;
		};

        $prd_to_integration_var = array();
        $variations_successfully = array();
		if ($this->prd['has_variants'] !== '') {
			if (count($this->variants) == 0) {
				$erro = "As variações deste produto " . $this->prd['id'] . " sumiram.";
				echo $erro . "\n";
				$this->log_data('batch', $log_name, $erro, "E");
				die;
			}
			foreach ($this->variants as $variant) {
                $prd_to_integration = $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'], $this->int_to, $variant['variant']);
                $prd_to_integration_var[$variant['variant']] = $prd_to_integration;
                if ($variant['status'] != 1) {
                    $this->disableProductVariant($prd_to_integration, $variant);
                } else {
                    if ($this->updateProductVariant($variant, $skumkt)) {
                        $variations_successfully[] = $variant['variant'];
                    }
                }
			}
		} else {
			if ($this->updateProductVariant(null, $skumkt) == false) {
				return true;
			}
		}

		$this->prd_to_integration['skumkt'] = $skumkt;
		$this->prd_to_integration['skubling'] = $sku;

		if ($this->prd['has_variants'] != '') {
            foreach ($this->variants as $variant) {
                if (in_array($variant['variant'], $variations_successfully)) {
                    $this->setDataVariantToUpdate($variant);
                    $this->updateSellerCenterLastPost($this->prd, $variant);
                }
			}
		} else {
			$this->updateSellerCenterLastPost($this->prd, null);
		}
		return true;
	}

	function inactivateProduct($status_int, $disable, $variant = null)
	{

		echo "Verificando se o produto não esta ativo no seller antes de inativar. \n\n";
		echo "Validando na tb products e na  tb prd_variants \n\n";
		
		if ($this->prd['has_variants'] !== '') {
			$variants = $this->variants;
			foreach($variants as $variant) {
				if ($this->prd_to_integration['status'] == 0 && $variant['status'] == 1) {
					$sql_update = "UPDATE prd_to_integration SET status = 1 WHERE prd_id = ? AND  int_to = ? AND  variant = ?";

					$this->db->query($sql_update, array($this->prd_to_integration['prd_id'], $this->int_to, $variant['variant']));
					echo "Status atualizado para ativo (1) na tabela 'prd_to_integration' para o produto ID: " . $this->prd_to_integration['prd_id'] . ".\n\n";
				}
			}
			return;
		}else{
			if ($this->prd_to_integration['status'] == 0 && $this->prd['status'] == 1) {
				$sql_update = "UPDATE prd_to_integration SET status = 1 WHERE id = ?";
				$this->db->query($sql_update, array($this->prd_to_integration['id']));
				echo "Status atualizado para ativo (1) na tabela 'prd_to_integration' para o produto ID: " . $this->prd_to_integration['prd_id'] . ".\n\n";
				return;
			}
		}

		$this->update_price_product = false;
		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
		echo "Inativando\n";
		$sku = 'P' . $this->prd['id'] . 'S' . $this->prd['store_id'] . $this->int_to;
		$this->model_errors_transformation->setStatusResolvedByProductId($this->prd['id'], $this->int_to);

		if ($this->prepareProduct($sku) == false) {
			return false;
		};

		$this->prd['qty'] = 0; // zero a quantidade do produto
	
		if ($this->prd['has_variants'] !== '') {
			if (count($this->variants) == 0) {
				$erro = 'As variações deste produto ' . $this->prd['id'] . ' sumiram.';
				echo $erro . "\n";
				$this->log_data('batch', $log_name, $erro, 'E');
				$disable = true; // melhor dar disable  
			}

            $variants = $this->variants;
            if (!is_null($variant)) {
                $variants = array($variant);
            }

            foreach($variants as $variant) {
				$variant['qty'] = 0;  // zero a quantidade da variant tb
				$this->inactivateProductVariant($variant, $sku);
				$prd_to_integration= $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'], $this->int_to, $variant['variant']);
				$this->model_integrations->updatePrdToIntegration(array('status_int'=>$status_int, 'date_last_int' => $this->dateLastInt),$prd_to_integration['id']);
			}
		}else{
			$this->prd['qty'] = 0;
			$this->inactivateProductVariant(null, $sku);
			$this->model_integrations->updatePrdToIntegration(array('status_int'=>$status_int, 'date_last_int' => $this->dateLastInt),$this->prd_to_integration['id']);
		}

		if ($this->prd['has_variants'] != '') {
            foreach ($this->variants as $variant) {
                $this->setDataVariantToUpdate($variant);
				$this->updateSellerCenterLastPost($this->prd, $variant);
			}
		} else {
			$this->updateSellerCenterLastPost($this->prd, null);
		}

	}

	function insertProductVariant($variant = null)
	{

		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
		$this->numft = 0;
		$skus = $this->montaArraySku($variant, $variant);
		if ($skus == false) {
			return false;
		};

		echo 'Incluindo os skus ' . $this->prd['id'] . ' ' . $this->prd['name'] . "\n";
		//consulta o sku
		$url = '/produtos/' . $skus['sku']. '?tipoIdentificador=Sku';
		$retorno = $this->process($url, 'GET', null, $this->prd['id'], $this->int_to, 'GET Product Insert');

		$skus['fabricante'] = htmlspecialchars_decode($skus['fabricante']);

		if ($this->responseCode == 200) { // O produto já existe na Wake
			return $this->updateProductVariant($variant, $this->prd_to_integration['skubling']);
		}

        if ($this->responseCode != 404 && $this->responseCode != 422) {
            $this->remove_product_queue = false;
            echo "Não foi possível identificar o SKU. URL=$url | HTTPCODE=$this->responseCode | RESPONSE=$this->result\n";
            return false;
        }

		// envio o produto para a Wake
		$url = '/produtos';
		$retorno = $this->process($url, 'POST', json_encode($skus), $this->prd['id'], $this->int_to, 'Criando Produto com sku');

		if ($this->responseCode != 201) { // Deu um erro que não consigo tratar
			echo ' Erro URL: ' . $url . "\n";
			echo ' httpcode: ' . $this->responseCode . "\n";
			echo " RESPOSTA: " . print_r($this->result, true) . " \n";
			echo ' ENVIADO : ' . print_r(json_encode($skus), true) . " \n";
			$this->log_data('batch', $log_name, 'ERRO no post produto de variaçao site:' . $url . ' - httpcode: ' . $this->responseCode . ' RESPOSTA: ' . print_r($this->result, true) . ' ENVIADO:' . print_r(json_encode($skus), true), 'E');
			$this->errorTransformation($this->prd['id'], $skus['sku'], $this->result, 'Erro ao cadastrar produto de variaçao', null, null, empty($variant) || $variant['variant'] === '' ? null : $variant['variant']);
			return false;
		}
		$sku_wake = $this->result;

		//atribuo a categoria
		if(!$this->alreadyCategory){
			$url = '/produtos/'.$skus['sku'].'/categorias?tipoIdentificador=Sku';
			$retorno = $this->process($url, 'POST', '{"categoriaPrincipalId":'.$this->prd['categoryname'].'}', $this->prd['id'], $this->int_to, 'Criando Produto');

			if ($this->responseCode != 200) { // Deu um erro que não consigo tratar
				echo ' Erro URL: ' . $url . "\n";
				echo ' httpcode: ' . $this->responseCode . "\n";
				echo " RESPOSTA: " . print_r($this->result, true) . " \n";
				echo ' ENVIADO : ' . print_r('{"categoriaPrincipalId":'.$this->prd['categoryname'].'}', true) . " \n";
				$this->log_data('batch', $log_name, 'ERRO no post produto site:' . $url . ' - httpcode: ' . $this->responseCode . ' RESPOSTA: ' . print_r($this->result, true) . ' ENVIADO:' . print_r('{"categoriaPrincipalId":'.$this->prd['categoryname'].'}', true), 'E');
				$this->errorTransformation($this->prd['id'], $skus['sku'], $this->result, 'Erro ao cadastrar categoria', null, null, empty($variant) || $variant['variant'] === '' ? null : $variant['variant']);
				return false;
			}

			$url = '/produtos/'.$skus['sku'].'/informacoes?tipoIdentificador=Sku&tipoRetorno=Booleano';
			$description = [	
							"tipoInformacao" => "Descricao",
							"titulo" =>"Descrição",
							"texto" => 	$this->prd['description'],
							"exibirSite" => true
						];
			  

			$retorno = $this->process($url, 'POST', json_encode($description), $this->prd['id'], $this->int_to, 'Criando Produto descrição');

			$this->alreadyCategory = true;
		}
	

		if (!is_null($variant)) {
			$skuId = $skus['sku'];

			// Subo as imagens para Wake
			if($this->prd['is_on_bucket']){
				$productImages = $this->getProductImagesBucket($this->prd['image'], $this->pathImage, '', false);
				$skuImages = $this->getProductImagesBucket($this->prd['image'], $this->pathImage, $variant['image'], $skus['sku']);
			}else{
				$productImages = $this->getProductImages($this->prd['image'], $this->pathImage, '', false);
				$skuImages = $this->getProductImages($this->prd['image'], $this->pathImage, $variant['image'], $skus['sku']);
			}

			$allImages = array_merge($productImages, $skuImages);
			if (is_array($allImages)) {
				// Atualizo o Produto com as imagens
				$url = '/produtos/' . $skus['sku'] . '/imagens?tipoIdentificador=Sku&tipoRetorno=ListaIds';
				//echo json_encode($allImages);
				$retorno = $this->process($url, 'POST', json_encode($allImages), $this->prd['id'], $this->int_to, 'Adicionando imagem ao Produto');
			}	

			$prd_to_integration = $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'], $this->int_to, $variant['variant']);
			if($prd_to_integration){
				$this->model_integrations->updatePrdToIntegration(
					array(
						'skumkt' 		=> $skuId,
						'skubling' 		=> $skuId,
						'status_int' 	=> ($this->prd['qty'] == 0) ? 10 : 2,
						'date_last_int' => $this->dateLastInt,
						'mkt_product_id' => $sku_wake,
						'mkt_sku_id' => $sku_wake
					),
					$prd_to_integration['id']
				);
			}else{
				//adicionou novas variações
				$prd_to_integration = $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'], $this->int_to, null);
				if($prd_to_integration){
					$this->model_integrations->updatePrdToIntegration(
						array(
							'skumkt' 		=> $skuId,
							'skubling' 		=> $skuId,
							'status_int' 	=> ($this->prd['qty'] == 0) ? 10 : 2,
							'date_last_int' => $this->dateLastInt,
							'variant' => $variant['variant'],
							'mkt_product_id' => $sku_wake,
							'mkt_sku_id' => $sku_wake
						),
						$prd_to_integration['id']
					);
				}else{
					//adicionou novas variações
					$prd_to_integration = $this->model_integrations->getPrdIntegrationByIntToProdId($this->int_to,$this->prd['id']);			
					$this->model_integrations->createPrdToIntegration(
					array(
							'prd_id'             => $this->prd['id'],
							'company_id'         => $this->prd['company_id'],
							'status'             => 1,
							'status_int'         => ($this->prd['qty'] == 0) ? 10 : 2,
							'int_to'             => $this->int_to,
							'skumkt'             => $skuId,
							'skubling'           => $skuId,
							'store_id'           => $this->prd['store_id'],
							'approved'           => $prd_to_integration['approved'],
							'int_id'             => $prd_to_integration['int_id'],
							'user_id'            => $prd_to_integration['user_id'],
							'int_type'           => $prd_to_integration['int_type'],
							'variant'            => $variant['variant'],
							'date_last_int' => $this->dateLastInt,
							'mkt_product_id' => $sku_wake,
							'mkt_sku_id' => $sku_wake
						)
					);
				}

			}

		} else {

			// Subo as imagens para Wake
			if($this->prd['is_on_bucket']){
				$productImages = $this->getProductImagesBucket($this->prd['image'], $this->pathImage, '', false);
			}else{
				$productImages = $this->getProductImages($this->prd['image'], $this->pathImage, '', false);
			}

			if (is_array($productImages)) {
				// Atualizo o Produto com as imagens
				$url = '/produtos/' . $skus['sku'] . '/imagens?tipoIdentificador=Sku&tipoRetorno=ListaIds';
				//echo json_encode($allImages);
				$retorno = $this->process($url, 'POST', json_encode($productImages), $this->prd['id'], $this->int_to, 'Adicionando imagem ao Produto');
			}			

			$skuId = $skus['sku'];		
			$prd_to_integration = $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'], $this->int_to, (isset($variant['variant']) ? $variant['variant'] : null));
			if($prd_to_integration){
				$this->model_integrations->updatePrdToIntegration(
					array(
						'skumkt' 		=> $skuId,
						'skubling' 		=> $skuId,
						'status_int' 	=> ($this->prd['qty'] == 0) ? 10 : 2,
						'date_last_int' => $this->dateLastInt,
						'mkt_product_id' => $sku_wake,
						'mkt_sku_id' => $sku_wake
					),
					$prd_to_integration['id']
				);
			}else{
				$prd_to_integration = $this->model_integrations->getPrdIntegrationByIntToProdId($this->int_to,$this->prd['id']);
				$this->model_integrations->updatePrdToIntegration(
					array(
						'skumkt' 		=> $skuId,
						'skubling' 		=> $skuId,
						'status_int' 	=> ($this->prd['qty'] == 0) ? 10 : 2,
						'date_last_int' => $this->dateLastInt,
						'variant'       => null,
						'mkt_product_id' => $sku_wake,
						'mkt_sku_id' => $sku_wake
					),
					$prd_to_integration['id']
				);

			}

		}
		return true;
	}

	function updateProductVariant($variant = null, $skumkt)
	{

		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
		$skus = $this->montaArraySku($variant, $variant);
		if ($skus == false) {
			return false;
		};

		if (!is_null($variant)) {
			// Envia o Estoque
			$url = '/produtos/' . $skus['sku']. '?tipoIdentificador=Sku';
			$sellerCredential = json_decode($this->integration_store['auth_data']);
			$retorno = $this->process($url, 'GET', null, $this->prd['id'], $this->int_to, 'GET Product Variant Update');

			if ($this->responseCode == 404 || $this->responseCode == 422) {
				//sku não cadastrado
				return $this->insertProductVariant($variant, null);					
			}

            if ($this->responseCode != 200) {
                $this->remove_product_queue = false;
                echo "Não foi possível identificar o SKU. URL=$url | HTTPCODE=$this->responseCode | RESPONSE=$this->result\n";
                return false;
            }

            $varWake = json_decode($this->result);
            $url = '/produtos/'.$skus['sku'].'/situacao?tipoIdentificador=Sku';
            $retorno = $this->process($url, 'PUT', '{"status":true}', $this->prd['id'], $this->int_to, 'Ativando Sku');
            //estoque
            $url = '/produtos/estoques?tipoIdentificador=Sku';
            $body = '[{"listaEstoque":[{"centroDistribuicaoId":'.$sellerCredential->seller_id.',"estoqueFisico":'.$skus['estoque'][0]['estoqueFisico'].',"produtoVarianteId":'.$varWake->produtoVarianteId.'}],"identificador":"'.$skus['sku'].'"}]';
            $retorno = $this->process($url, 'PUT', $body, $this->prd['id'], $this->int_to, 'Atualizando Estoque Sku');
            //preço
            $url = '/produtos/precos?tipoIdentificador=Sku';
            $body = '[{"identificador":"'.$skus['sku'].'","precoDe":'.$skus['precoDe'].',"precoPor":'.$skus['precoPor'].',"fatorMultiplicadorPreco":1}]';
            $retorno = $this->process($url, 'PUT', $body, $this->prd['id'], $this->int_to, 'Atualizando Preço Sku');

			//atualizo categoria
			if($this->update_product_wake){
				$url = '/produtos/'.$skus['sku'].'/categorias?tipoIdentificador=Sku';
				$retorno = $this->process($url, 'POST', '{"categoriaPrincipalId":'.$this->prd['categoryname'].'}', $this->prd['id'], $this->int_to, 'Atualizando Categoria Produto Atualizaçao');

				if ($this->responseCode != 200) { // Deu um erro que não consigo tratar
					echo ' Erro URL: ' . $url . "\n";
					echo ' httpcode: ' . $this->responseCode . "\n";
					echo " RESPOSTA: " . print_r($this->result, true) . " \n";
					echo ' ENVIADO : ' . print_r('{"categoriaPrincipalId":'.$this->prd['categoryname'].'}', true) . " \n";
					$this->log_data('batch', $log_name, 'ERRO no post produto site:' . $url . ' - httpcode: ' . $this->responseCode . ' RESPOSTA: ' . print_r($this->result, true) . ' ENVIADO:' . print_r('{"categoriaPrincipalId":'.$this->prd['categoryname'].'}', true), 'E');
					$this->errorTransformation($this->prd['id'], $skus['sku'], $this->result, 'Erro ao Atualizar categoria', null, null, empty($variant) || $variant['variant'] === '' ? null : $variant['variant']);
					return false;
				}
				//atualizo descrição
				$url = '/produtos/' . $skus['sku']. '/informacoes/?tipoIdentificador=Sku';
				$retorno = $this->process($url, 'GET', null, $this->prd['id'], $this->int_to, 'GET Imagens Product Atualizaçao');
				if ($this->responseCode != 200) {
					//informação não retornada
					$url = '/produtos/'.$skus['sku'].'/informacoes/?tipoIdentificador=Sku&tipoRetorno=Booleano';
					$description = [	
									"tipoInformacao" => "Descricao",
									"titulo" =>"Descrição",
									"texto" => 	$this->prd['description'],
									"exibirSite" => true
								];
					
		
					$retorno = $this->process($url, 'POST', json_encode($description), $this->prd['id'], $this->int_to, 'Criando Produto descrição');	

				}else{
					$informacoes = json_decode($this->result);
					$informadescricao = array_filter($informacoes, function($informacao) {
						return $informacao->tipoInformacao == 11;
					});
					rsort($informadescricao);
					$idInformacao = $informadescricao[0]->informacaoId;

					//informação retornada atualizo
					$url = '/produtos/'.$skus['sku'].'/informacoes/'.$idInformacao.'?tipoIdentificador=Sku&tipoRetorno=Booleano';
					$description = [	
									"tipoInformacao" => "Descricao",
									"titulo" =>"Descrição",
									"texto" => 	$this->prd['description'],
									"exibirSite" => true
								];
					
		
					$retorno = $this->process($url, 'PUT', json_encode($description), $this->prd['id'], $this->int_to, 'Atualizando Produto descrição');


				}
			}	

			if($this->update_sku_wake){
				//atualizo o sku validação de atualizar o sku
				$url = '/produtos/' . $skus['sku']. '?tipoIdentificador=Sku';
				$retorno = $this->process($url, 'PUT', json_encode($skus), $this->prd['id'], $this->int_to, 'Atualizando Produto com sku');
		
				if ($this->responseCode != 200) { // Deu um erro que não consigo tratar
					echo ' Erro URL: ' . $url . "\n";
					echo ' httpcode: ' . $this->responseCode . "\n";
					echo " RESPOSTA: " . print_r($this->result, true) . " \n";
					echo ' ENVIADO : ' . print_r(json_encode($skus), true) . " \n";
					$this->log_data('batch', $log_name, 'ERRO no PUT produto site:' . $url . ' - httpcode: ' . $this->responseCode . ' RESPOSTA: ' . print_r($this->result, true) . ' ENVIADO:' . print_r($skus, true), 'E');
					$this->errorTransformation($this->prd['id'], $skus['sku'], $this->result, 'Erro ao atualizar produto', null, null, empty($variant) || $variant['variant'] === '' ? null : $variant['variant']);
					return false;
				}
			}

			if($this->update_images_specifications){
				//atualizo imagem
				$url = '/produtos/' . $skus['sku']. '/imagens?tipoIdentificador=Sku&produtosIrmaos=false';
				$retorno = $this->process($url, 'GET', null, $this->prd['id'], $this->int_to, 'GET Imagens Produto Atualizacao');
				if ($this->responseCode == 200) {
					//existem imagens apago
					$imagensWake = json_decode($this->result);
					foreach($imagensWake as $imgWake){
						$url = '/produtos/' . $skus['sku']. '/imagens/'.$imgWake->idImagem.'?tipoIdentificador=Sku';
						$retorno = $this->process($url, 'DELETE', null, $this->prd['id'], $this->int_to, 'DELETE SKU IMAGE');
					}
				}

				// Subo as imagens para Wake
				if($this->prd['is_on_bucket']){
					$productImages = $this->getProductImagesBucket($this->prd['image'], $this->pathImage, '', false);
					$skuImages = $this->getProductImagesBucket($this->prd['image'], $this->pathImage, $variant['image'], $skus['sku']);
				}else{
					$productImages = $this->getProductImages($this->prd['image'], $this->pathImage, '', false);
					$skuImages = $this->getProductImages($this->prd['image'], $this->pathImage, $variant['image'], $skus['sku']);
				}

				$allImages = array_merge($productImages, $skuImages);
				if (is_array($allImages)) {
					// Atualizo o Produto com as imagens
					$url = '/produtos/' . $skus['sku'] . '/imagens?tipoIdentificador=Sku&tipoRetorno=ListaIds';
					//echo json_encode($allImages);
					$retorno = $this->process($url, 'POST', json_encode($allImages), $this->prd['id'], $this->int_to, 'Adicionando imagem ao Produto');
				}
			}	

			$prd_to_integration = $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'], $this->int_to, $variant['variant']);
			if($prd_to_integration){
				$this->model_integrations->updatePrdToIntegration(
					array(
						'skumkt' 		=> $skus['sku'],
						'skubling' 		=> $skus['sku'],
						'status_int' 	=> ($this->prd['qty'] == 0) ? 10 : 2,
						'date_last_int' => $this->dateLastInt,
						'mkt_product_id' => $varWake->produtoId,
						'mkt_sku_id' => $varWake->produtoVarianteId
					),
					$prd_to_integration['id']
				);
			}else{
				$prd_to_integration = $this->model_integrations->getPrdIntegrationByIntToProdId($this->int_to,$this->prd['id']);			
					$this->model_integrations->createPrdToIntegration(
					array(
							'prd_id'             => $this->prd['id'],
							'company_id'         => $this->prd['company_id'],
							'status'             => 1,
							'status_int'         => ($this->prd['qty'] == 0) ? 10 : 2,
							'int_to'             => $this->int_to,
							'skumkt'             => $skus['sku'],
							'skubling'           => $skus['sku'],
							'store_id'           => $this->prd['store_id'],
							'approved'           => $prd_to_integration['approved'],
							'int_id'             => $prd_to_integration['int_id'],
							'user_id'            => $prd_to_integration['user_id'],
							'int_type'           => $prd_to_integration['int_type'],
							'variant'            => $variant['variant'],
							'date_last_int' => $this->dateLastInt,
							'mkt_product_id' => $varWake->produtoId,
							'mkt_sku_id' => $varWake->produtoVarianteId
						)
					);
			}


		} else {
			$url = '/produtos/' . $skus['sku']. '?tipoIdentificador=Sku';
			$sellerCredential = json_decode($this->integration_store['auth_data']);
			$retorno = $this->process($url, 'GET', null, $this->prd['id'], $this->int_to, 'GET Product Insert');

            if ($this->responseCode == 404 || $this->responseCode == 422) {
				//sku não cadastrado
				return $this->insertProductVariant($variant, null);					
			}

            if ($this->responseCode != 200) {
                $this->remove_product_queue = false;
                echo "Não foi possível identificar o SKU. URL=$url | HTTPCODE=$this->responseCode | RESPONSE=$this->result\n";
                return false;
            }

			if ($this->responseCode == 200) { 
				$varWake = json_decode($this->result);
				$url = '/produtos/'.$skus['sku'].'/situacao?tipoIdentificador=Sku';
				$retorno = $this->process($url, 'PUT', '{"status":true}', $this->prd['id'], $this->int_to, 'Ativando Sku');
				//atualiza estoque
				$url = '/produtos/estoques?tipoIdentificador=Sku';
				$body = '[{"listaEstoque":[{"centroDistribuicaoId":'.$sellerCredential->seller_id.',"estoqueFisico":'.$skus['estoque'][0]['estoqueFisico'].',"produtoVarianteId":'.$varWake->produtoVarianteId.'}],"identificador":"'.$skus['sku'].'"}]';
				$retorno = $this->process($url, 'PUT', $body, $this->prd['id'], $this->int_to, 'Atualizando Estoque Sku');
				//atualiza preço
				$url = '/produtos/precos?tipoIdentificador=Sku';
				$body = '[{"identificador":"'.$skus['sku'].'","precoDe":'.$skus['precoDe'].',"precoPor":'.$skus['precoPor'].',"fatorMultiplicadorPreco":1}]';
				$retorno = $this->process($url, 'PUT', $body, $this->prd['id'], $this->int_to, 'Atualizando Preco Sku');

			}

			//atualizo categoria
			if($this->update_product_wake){
				$url = '/produtos/'.$skus['sku'].'/categorias?tipoIdentificador=Sku';
                $data = '{"categoriaPrincipalId":'.$this->prd['categoryname'].'}';
				$retorno = $this->process($url, 'POST', $data, $this->prd['id'], $this->int_to, 'Atualizando categoria Produto');

				if ($this->responseCode != 200) { // Deu um erro que não consigo tratar
					echo ' Erro URL: ' . $url . "\n";
					echo ' httpcode: ' . $this->responseCode . "\n";
					echo " RESPOSTA: " . print_r($this->result, true) . " \n";
					echo ' ENVIADO : ' . print_r('{"categoriaPrincipalId":'.$this->prd['categoryname'].'}', true) . " \n";
					$this->log_data('batch', $log_name, 'ERRO no post produto site:' . $url . ' - httpcode: ' . $this->responseCode . ' RESPOSTA: ' . print_r($this->result, true) . ' ENVIADO:' . print_r($data, true), 'E');
					$this->errorTransformation($this->prd['id'], $skus['sku'], $this->result, 'Erro ao cadastrar categoria', null, null, empty($variant) || $variant['variant'] === '' ? null : $variant['variant']);
					return false;
				}
				//atualizo descrição
				$url = '/produtos/' . $skus['sku']. '/informacoes/?tipoIdentificador=Sku';
				$retorno = $this->process($url, 'GET', null, $this->prd['id'], $this->int_to, 'GET Informaçoes Produto Atualizaçao');
				if ($this->responseCode != 200) {
					//informação não retornada
					$url = '/produtos/'.$skus['sku'].'/informacoes/?tipoIdentificador=Sku&tipoRetorno=Booleano';
					$description = [	
									"tipoInformacao" => "Descricao",
									"titulo" =>"Descrição",
									"texto" => 	$this->prd['description'],
									"exibirSite" => true
								];
					
		
					$retorno = $this->process($url, 'POST', json_encode($description), $this->prd['id'], $this->int_to, 'Criando Produto descrição');	
								
				}else{
					$informacoes = json_decode($this->result);
					$informadescricao = array_filter($informacoes, function($informacao) {
						return $informacao->tipoInformacao == 11;
					});
					rsort($informadescricao);
					$idInformacao = $informadescricao[0]->informacaoId;

					//informação retornada atualizo
					$url = '/produtos/'.$skus['sku'].'/informacoes/'.$idInformacao.'?tipoIdentificador=Sku&tipoRetorno=Booleano';
					$description = [	
									"tipoInformacao" => "Descricao",
									"titulo" =>"Descrição",
									"texto" => 	$this->prd['description'],
									"exibirSite" => true
								];
					
		
					$retorno = $this->process($url, 'PUT', json_encode($description), $this->prd['id'], $this->int_to, 'Atualizando Produto descrição');


				}
			}	

			if($this->update_sku_wake){
				//atualizo o sku validação de atualizar o sku
				$url = '/produtos/' . $skus['sku']. '?tipoIdentificador=Sku';
				$retorno = $this->process($url, 'PUT', json_encode($skus), $this->prd['id'], $this->int_to, 'Atualizando Produto com sku');
		
				if ($this->responseCode != 200) { // Deu um erro que não consigo tratar
					echo ' Erro URL: ' . $url . "\n";
					echo ' httpcode: ' . $this->responseCode . "\n";
					echo " RESPOSTA: " . print_r($this->result, true) . " \n";
					echo ' ENVIADO : ' . print_r(json_encode($skus), true) . " \n";
					$this->log_data('batch', $log_name, 'ERRO no PUT produto site:' . $url . ' - httpcode: ' . $this->responseCode . ' RESPOSTA: ' . print_r($this->result, true) . ' ENVIADO:' . print_r(json_encode($skus), true), 'E');
					$this->errorTransformation($this->prd['id'], $skus['sku'], $this->result, 'Erro ao atualizar produto', null, null, empty($variant) || $variant['variant'] === '' ? null : $variant['variant']);
					return false;
				}
			}

			if($this->update_images_specifications){
				//atualizo imagem
				$url = '/produtos/' . $skus['sku']. '/imagens?tipoIdentificador=Sku&produtosIrmaos=false';
				$retorno = $this->process($url, 'GET', null, $this->prd['id'], $this->int_to, 'GET Imagens Produto atualizaçao');
				if ($this->responseCode == 200) {
					//existem imagens apago
					$imagensWake = json_decode($this->result);
					foreach($imagensWake as $imgWake){
						$url = '/produtos/' . $skus['sku']. '/imagens/'.$imgWake->idImagem.'?tipoIdentificador=Sku';
						$retorno = $this->process($url, 'DELETE', null, $this->prd['id'], $this->int_to, 'DELETE SKU IMAGE');
					}
				}

				// Subo as imagens para Wake
				if($this->prd['is_on_bucket']){
					$productImages = $this->getProductImagesBucket($this->prd['image'], $this->pathImage, '', false);
				}else{
					$productImages = $this->getProductImages($this->prd['image'], $this->pathImage, '', false);
				}

				if (is_array($productImages)) {
					// Atualizo o Produto com as imagens
					$url = '/produtos/' . $skus['sku'] . '/imagens?tipoIdentificador=Sku&tipoRetorno=ListaIds';
					//echo json_encode($allImages);
					$retorno = $this->process($url, 'POST', json_encode($productImages), $this->prd['id'], $this->int_to, 'Adicionando imagem ao Produto');
				}
			}	

			$prd_to_integration= $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'], $this->int_to, (isset($variant['variant']) ? $variant['variant'] : null));
			$this->model_integrations->updatePrdToIntegration(
				array(
					'skumkt' 		=> $skus['sku'],
					'skubling' 		=> $skus['sku'],
					'status_int' 	=> ($this->prd['qty'] == 0) ? 10 : 2,
					'date_last_int' => $this->dateLastInt,
					'mkt_product_id' => $varWake->produtoId,
					'mkt_sku_id' => $varWake->produtoVarianteId
				),
				$prd_to_integration['id']
			);
		}
		return true; 
	}

	function inactivateProductVariant($variant = null){

		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
		$this->numft = 0;
		$skus = $this->montaArraySku($variant, $variant);
		if ($skus == false) {
			return false;
		};

		echo 'Inativando os sku ' . $this->prd['id'] . ' ' . $this->prd['name'] . "\n";
		//consulta o sku
		$url = '/produtos/' . $skus['sku']. '?tipoIdentificador=Sku';
		$sellerCredential = json_decode($this->integration_store['auth_data']);
		$retorno = $this->process($url, 'GET', null, $this->prd['id'], $this->int_to, 'GET Produto inativar');
		if ($this->responseCode == 200) { 
			$varWake = json_decode($this->result);
			$url = '/produtos/'.$skus['sku'].'/situacao?tipoIdentificador=Sku';
			$retorno = $this->process($url, 'PUT', '{"status":false}', $this->prd['id'], $this->int_to, 'Inativando Sku');
			$url = '/produtos/estoques?tipoIdentificador=Sku';
			$retorno = $this->process($url, 'PUT', '[{"listaEstoque":[{"centroDistribuicaoId":'.$sellerCredential->seller_id.',"estoqueFisico":0,"produtoVarianteId":'.$varWake->produtoVarianteId.'}],"identificador":"'.$skus['sku'].'"}]', $this->prd['id'], $this->int_to, 'Zerando Estoque Sku');
		}

		return true;

	}



	function getkeys()
	{
		//pega os dados da integração. 
		$this->getIntegration();
		$this->auth_data = isset($this->integration_main['auth_data']) ? json_decode($this->integration_main['auth_data']) : null;
		//$this->setApikey($api_keys['apikey']);
		//	$this->setEmail($api_keys['email']);
	}

	function getIntegration()
	{

		$this->integration_store = $this->model_integrations->getIntegrationbyStoreIdAndInto($this->store['id'], $this->int_to);
		if ($this->integration_store) {
			if ($this->integration_store['int_type'] == 'BLING') {
				$this->integration_main = $this->model_integrations->getIntegrationbyStoreIdAndInto('0', $this->int_to);
			} else {
				$this->integration_main = $this->integration_store;
			}
		}
	}

	//public function getCategoryMarketplace($skumkt,$int_to = '')
	public function getCategory($skumkt, $int_to = '', $mandatory_category = true)
	{
		if ($int_to == '') {
			$int_to = $this->int_to;
		}

		$categoryId = json_decode($this->prd['category_id']);
		if (is_array($categoryId)) {
			$categoryId = $categoryId[0];
		}
		$category   = $this->model_categorias_marketplaces->getDataByCategoryIdAndIntTo($categoryId, $int_to);
		if (!$category) {
			$msg = 'Produto sem categoria.';
			echo 'Produto ' . $this->prd['id'] . ' ' . $msg . "\n";
			$this->errorTransformation($this->prd['id'], $skumkt, $msg, 'Preparação para o envio');
			//die;
			return false;
		}

		$this->prd['categoryname'] = $category[0]['category_marketplace_id'];

		return $categoryId;
	}

	function prepareProduct($sku)
	{
		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
		echo 'Preparando produto' . "\n";

		// busco a categoria 
		$this->prd['categoria_wake'] = $this->getCategory($sku);
		if ($this->prd['categoria_wake'] == false) {
			return false;
		}

		// leio o percentual do estoque;
		$percEstoque = $this->percEstoque();

		// Pego o preço do produto
		$this->prd['promotional_price'] = $this->getPrice(null);

		if($this->prd['promotional_price'] < $this->prd['price']){
			$this->promo = true;
		}
		if ($this->prd['promotional_price'] > $this->prd['price']) {
			$this->prd['price'] = $this->prd['promotional_price'];
		}

		// se tiver Variação,  acerto o estoque de cada variação
		if ($this->prd['has_variants'] != '') {
			$variações = explode(";", $this->prd['has_variants']);

			// Acerto o estoque
			foreach ($this->variants as $key => $variant) {
				$this->variants[$key]['qty_original'] = $variant['qty'];
				if ((int)$this->variants[$key]['qty'] < 0) {
					$this->variants[$key]['qty'] = 0;
				}
				$this->variants[$key]['qty'] = ceil((int) $variant['qty'] * $percEstoque / 100); // arredondo para cima 
				if ((is_null($variant['price'])) || ($variant['price'] == '') || ($variant['price'] == 0)) {
					$this->variants[$key]['price'] = $this->prd['price'];
				}

				$this->variants[$key]['promotional_price'] = $this->getPrice($variant);
				if ($this->variants[$key]['promotional_price'] > $this->variants[$key]['price']) {
					$this->variants[$key]['price'] = $this->variants[$key]['promotional_price'];
				}


			}
		}

		if ($this->prd['is_kit']) {  // B2W consegue mostrar o preço original dos produtos que o componhe 
			$productsKit = $this->model_products->getProductsKit($this->prd['id']);
			$original_price = 0;
			foreach ($productsKit as $productkit) {
				$original_price += $productkit['qty'] * $productkit['original_price'];
			}
			$this->prd['price'] = $original_price;
			echo ' KIT ' . $this->prd['id'] . ' preço de ' . $this->prd['price'] . ' por ' . $this->prd['promotional_price'] . "\n";
		}

		//leio a brand
		if ($this->getBrandMarketplace($sku, false) == false) {return false;}

		// marco o prazo_operacional para pelo menos 1 dia
		if ($this->prd['prazo_operacional_extra'] < 1) {
			$this->prd['prazo_operacional_extra'] = 1;
		}

		// monto atributos de produto e sku
		//verificar se é obrigatório e se ta preenchido arthur
		$atributtes = $this->model_atributos_categorias_marketplaces->getAllProdutosAtributos($this->prd['id']);
		foreach ($atributtes as $atributte) {
			$atributo_prd = $this->model_atributos_categorias_marketplaces->getAtributoCategoria_MKT($this->prd['categoryname'], $atributte['id_atributo'], $this->int_to);
			if ($atributo_prd) {
				if ($atributo_prd['prd_sku']) {
					//$this->skuAtributtes[$atributte['id_atributo']] = $atributte['valor'];
					array_push($this->prdAtributtes, (object)["nome" => $atributo_prd['nome'], "valor" => $atributte['valor'], "exibir" => true ]);
				} else {
					array_push($this->prdAtributtes, (object)["nome" => $atributo_prd['nome'], "valor" => $atributte['valor'], "exibir" => true ]);
				}	
			}		
		}
		
		return true;
	}

	private function getProductImages($folder_ori, $path, $vardir = '', $variacao = false, $update = [])
	{
		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

		$folder = $folder_ori;
		if ($vardir !== '') {
			$folder .= '/' . $vardir;
		} elseif ($variacao) {
			return array(); // se é uma variação mas não passou o diretório da variação, retorna o array vazio
		}
		$images = scandir(FCPATH . 'assets/images/' . $path . '/' . $folder);

		if (!$images) {
			return array();
		}
		if (count($images) <= 2) { // não achei nenhuma imagem
			if ($variacao) { // Mas é uma variação, retorna o array vazio
				return  array();
			}
			return array();
		}

		if ($update) {
			$needUpdate = false;
			$notEmpty = [];
			$count = 0;
			foreach ($images as $foto) {
				if (($foto != '.') && ($foto != '..') && ($foto != '')) {
					if (!is_dir(FCPATH . 'assets/images/' . $path . '/' . $folder . '/' . $foto)) {
						//existem imagens no diretorio
						$notEmpty = true;
						$count++;
						if (!in_array($this->prd_to_integration['skubling'] . $foto, $update)) {
							$needUpdate = true;
						}
					}
				}
			}
			//se não existem imagens no diretorio retorno array vazio para atualizar na occ
			if(!$notEmpty){
				return [];
			}
			//as imagens da occ batem com as armazenadas no servidor
			if (!$needUpdate && $count == count($update)) {
				return true;
			}
		}

		$imagesData = array();
		foreach ($images as $foto) {
			if (($foto != '.') && ($foto != '..') && ($foto != '')) {
				if (!is_dir(FCPATH . 'assets/images/' . $path . '/' . $folder . '/' . $foto)) {
					$fileFullPath = FCPATH . 'assets/images/' . $path . '/' . $folder . '/' . $foto;
					$exp_extens = explode(".", $foto);
					$extensao = $exp_extens[count($exp_extens) - 1];
					$name = $this->prd_to_integration['skubling'] . $foto;
					$image = file_get_contents($fileFullPath);
					$image_base64 = base64_encode($image);
					$data = (object)['base64' => $image_base64, 'formato' => $extensao, 'exibirMiniatura' => false, 'estampa' => false, 'ordem' => $this->numft];
					array_push($imagesData, $data);
					$this->numft++;
				}
			}
		}
		return $imagesData;
	}

	private function getProductImagesBucket($folder_ori, $path, $vardir = '', $variacao = false, $update = [])
	{
		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

		$folder = $folder_ori;
		if ($vardir !== '') {
			$folder .= '/' . $vardir;
		} elseif ($variacao) {
			return array(); // se é uma variação mas não passou o diretório da variação, retorna o array vazio
		}
		$images = $this->bucket->getFinalObject('assets/images/' . $path . '/' . $folder);
		if (!$images['success']) {
			return array();
		}
		$images = $images['contents'];

		if ($update) {
			$needUpdate = false;
			$notEmpty = [];
			$count = 0;
			foreach ($images as $foto) {
				$notEmpty = true;
				$count++;
				if (!in_array($this->prd_to_integration['skubling'] . $foto['key'], $update)) {
					$needUpdate = true;
				}
			}
			//se não existem imagens no diretorio retorno array vazio para atualizar na occ
			if(!$notEmpty){
				return [];
			}
			//as imagens da occ batem com as armazenadas no servidor
			if (!$needUpdate && $count == count($update)) {
				return true;
			}
		}

		$imagesData = array();
		foreach ($images as $foto) {
			$fileFullPath = $foto['url'];;
			$exp_extens = explode(".", $foto['key']);
			$extensao = $exp_extens[count($exp_extens) - 1];
			$name = $this->prd_to_integration['skubling'] . $foto['key'];
			$image = file_get_contents($fileFullPath);
			$image_base64 = base64_encode($image);
			$data = (object)['base64' => $image_base64, 'formato' => $extensao, 'exibirMiniatura' => false, 'estampa' => false, 'ordem' => $this->numft];
			array_push($imagesData, $data);
			$this->numft++;
		}
		return $imagesData;
	}


	function montaArray($sku, $novo_produto = true, $vendas = 0)
	{
        //arthur
		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

		$description = $this->prd['description'];
		$description = str_replace("&amp;amp;", " ", $description);
		$description = str_replace("&amp;", " ", $description);
		$description = str_replace("&nbsp;", " ", $description);
		$description = substr(htmlspecialchars(strip_tags($description), ENT_QUOTES, "utf-8"), 0, 3800);

		$produto = array(
			'idPaiExterno'		=> $sku,
			'exibirMatrizAtributos'	=> "Sim",
			"valido" => true,
			'contraProposta'	=> false,
			'sku' => $sku,
			"nome" => substr(strip_tags($this->strReplaceName($this->prd['name']), " \t\n\r\0\x0B\xC2\xA0"), 0, 100),
			"fabricante" => html_entity_decode($this->prd['brandname']),
			"precoDe" => (float)$this->prd['list_price'] ? (float)$this->prd['list_price'] : (float)$this->prd['price'],
			"precoPor" => (float)$this->prd['price'],
			"freteGratis" => "Desconsiderar_Regras",
			"fatorMultiplicadorPreco" => 1,
			"peso" => (float)$this->prd['peso_bruto'],
			"altura" => (float)$this->prd['altura'],
			"comprimento" => (float)($this->prd['profundidade'] < 16) ? 16 : $this->prd['profundidade'],
			"largura" => (float)$this->prd['largura'],
			"garantia" => $this->prd['garantia'],
			"estoque" => [
				array(
				"estoqueFisico" => 10
			  )
			],
			"listaAtributos" => [],
			"condicao" => "Novo",
			"marketplace" => true,
			"buyBox" => true, //consultar no seller
			"idVinculoExterno" => $sku
		);

		if($this->prdAtributtes){
			$produto['listaAtributos'] = array_merge($produto['listaAtributos'], $this->prdAtributtes);
		}
		

		$resp_json = json_encode($produto);
		if ($resp_json === false) {
			// a descrição está com algum problema . tento reduzir... 
			$produto['name'] = substr(strip_tags($this->strReplaceName($this->prd['name']), " \t\n\r\0\x0B\xC2\xA0"), 0, 96);
			$resp_json = json_encode($produto);
			if ($resp_json === false) {
				$msg = 'Erro ao fazer o json do produto ' . $this->prd['id'] . ' ' . print_r($produto, true) . ' json error = ' . json_last_error_msg();
				var_dump($resp_json);
				echo $msg . "\n";
				$this->log_data('batch', $log_name, $msg, 'E');
				return false;;
			}
		}

		echo print_r($resp_json, true) . "\n";

		return $produto;
	}

	private function montaVariacoesDefault($variant_sku, $variant_category){
		foreach($variant_category as $vc){
			$nome = $vc['nome'];
			$atributoPreenchido = array_filter($this->skuAtributtes, function($atributo) use ($nome) {
				return $atributo->nome == $nome;
			});
			if($atributoPreenchido){continue;}
			array_push($this->skuAtributtes, (object)["nome" => $vc['nome'], "valor" => $this->variacoes_default, "exibir" => true ]);
		}

	}
	
	public function montaArraySku($sku, $is_variant = null)
	{

		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
		$this->skuAtributtes = [];
		$this->prd['skuname'] = $this->prd['name'];
		$allAtributoCat = $this->model_atributos_categorias_marketplaces->getAtributosCategoriaVariant($this->prd['categoryname'], $this->int_to);

		if (!is_null($sku)) {

			$this->prd['skuname'] = $this->prd['name'] . ' ' . str_replace(";", " / ", $sku['name']);
			$this->prd['variant_value'] = $sku['name'];
			$variants_sku = explode(';', $this->prd['has_variants']);
			$variants_value  = explode(';', $sku['name']);
			$skumkt = 'P' . $this->prd['id'] . 'S' . $this->prd['store_id'] . $this->int_to . 'V' . $sku['variant'];
			$variants_sku_upper = array_map('strtoupper', $variants_sku);

			foreach($variants_sku_upper as $key => $vs){
				if($vs == 'TAMANHO') {
					$variants_sku_upper[$key] = $this->variant_size;
					continue; 
				} 
				if($vs == 'COR') {
					$variants_sku_upper[$key] = $this->variant_color;
					continue; 
				} 
				if($vs == 'VOLTAGEM') {
					$variants_sku_upper[$key] = $this->variant_voltage;
					continue;
				} 
				if($vs == 'SABOR') {
					$variants_sku_upper[$key] = $this->variant_flavor;
					continue;
				} 
				if($vs == 'GRAU') {
					$variants_sku_upper[$key] = $this->variant_degree;
					continue;
				} 
				if($vs == 'LADO') {
					$variants_sku_upper[$key] = $this->variant_side;
					continue;
				} 
			}

			$variants_sku_upper = array_map('strtoupper', $variants_sku_upper);

			// if(!$this->variacoes_default){	
			// 	//validação para ver se está faltando preencher alguma variação
				
			// 	foreach($allAtributoCat as $atr){
			// 		if(strtoupper($atr['nome']) == strtoupper($this->variant_size)){
			// 			if(in_array(strtoupper($this->variant_size), $variants_sku_upper)){
			// 				continue;
			// 			}
			// 			$notice = "Esta categoria obriga variação por " . $atr['nome'];
			// 			$this->errorTransformation($this->prd['id'], $skumkt, $notice, 'Preparação para o envio');
			// 			die;
			// 			return false;

			// 		}
			// 		if(strtoupper($atr['nome']) == strtoupper($this->variant_color)){
			// 			if(in_array(strtoupper($this->variant_color), $variants_sku_upper)){
			// 				continue;
			// 			}
			// 			$notice = "Esta categoria obriga variação por " . $atr['nome'];
			// 			$this->errorTransformation($this->prd['id'], $skumkt, $notice, 'Preparação para o envio');
			// 			die;
			// 			return false;
						
			// 		}
			// 		if(strtoupper($atr['nome']) == strtoupper($this->variant_voltage)){
			// 			if(in_array(strtoupper($this->variant_voltage), $variants_sku_upper)){
			// 				continue;
			// 			}
			// 			$notice = "Esta categoria obriga variação por " . $atr['nome'];
			// 			$this->errorTransformation($this->prd['id'], $skumkt, $notice, 'Preparação para o envio');
			// 			die;
			// 			return false;

						
			// 		}

			// 		if(strtoupper($atr['nome']) == strtoupper($this->variant_flavor)){
			// 			if(in_array(strtoupper($this->variant_flavor), $variants_sku_upper)){
			// 				continue;
			// 			}
			// 			$notice = "Esta categoria obriga variação por " . $atr['nome'];
			// 			$this->errorTransformation($this->prd['id'], $skumkt, $notice, 'Preparação para o envio');
			// 			die;
			// 			return false;

						
			// 		}

			// 		if(strtoupper($atr['nome']) == strtoupper($this->variant_degree)){
			// 			if(in_array(strtoupper($this->variant_degree), $variants_sku_upper)){
			// 				continue;
			// 			}
			// 			$notice = "Esta categoria obriga variação por " . $atr['nome'];
			// 			$this->errorTransformation($this->prd['id'], $skumkt, $notice, 'Preparação para o envio');
			// 			die;
			// 			return false;

						
			// 		}

			// 		if(strtoupper($atr['nome']) == strtoupper($this->variant_side)){
			// 			if(in_array(strtoupper($this->variant_side), $variants_sku_upper)){
			// 				continue;
			// 			}
			// 			$notice = "Esta categoria obriga variação por " . $atr['nome'];
			// 			$this->errorTransformation($this->prd['id'], $skumkt, $notice, 'Preparação para o envio');
			// 			die;
			// 			return false;

						
			// 		}
			// 	}
			// 	//validação para ver se tem variações que a categoria não permite

				

			// }else{
				if($this->variacoes_default){
				$variacoes_preenchidas = [];
				foreach ($variants_sku_upper as $key => $value) {
					$atributoCat = $this->model_atributos_categorias_marketplaces->getAtributoCategoriaMKT($this->prd['categoryname'], ucfirst(strtolower($value)), $this->int_to, !empty($this->prd['has_variants']));
					if($atributoCat){
					array_push($this->skuAtributtes, (object)["nome" => $atributoCat['nome'], "valor" => $variants_value[$key], "exibir" => true ]);					
					}
				}
					$this->montaVariacoesDefault($variacoes_preenchidas, $allAtributoCat);
				}else{
					foreach ($variants_sku_upper as $key => $value) {

						$atributoCat = $this->model_atributos_categorias_marketplaces->getAtributoCategoriaMKT($this->prd['categoryname'], ucfirst(strtolower($value)), $this->int_to, !empty($this->prd['has_variants']));
						if (!$atributoCat) {
								$notice = "Esta categoria não aceita variação por " . $value;
								$this->errorTransformation($this->prd['id'], $skumkt, $notice, 'Preparação para o envio');
								//die;
								return false;					
						}
						array_push($this->skuAtributtes, (object)["nome" => $atributoCat['nome'], "valor" => $variants_value[$key], "exibir" => true ]);
						//$this->skuAtributtes[$atributoCat['id_atributo']] = $variants_value[$key];
						
					}
				}

			} 
		else {
			// if(!$this->variacoes_default){
			// 	//validação para ver se está faltando preencher alguma variação
			// 	foreach($allAtributoCat as $atr){
			// 		if($atr && $atr['nome'] != "sellerId"){
			// 			$notice = "Esta categoria obriga variação por " . $atr['nome'];
			// 			$this->errorTransformation($this->prd['id'], $skumkt, $notice, 'Preparação para o envio');
			// 			die;
			// 			return false;
			// 		}
			// 	}
			// }else
			if($this->variacoes_default){
				$this->montaVariacoesDefault([], $allAtributoCat);
			}	
			$skumkt = 'P' . $this->prd['id'] . 'S' . $this->prd['store_id'] . $this->int_to . 'S';
		}
		$prd_id = 'P' . $this->prd['id'] . 'S' . $this->prd['store_id'] . $this->int_to;
		//quantidade
		$qty = $this->prd['qty'];
		if($is_variant){
			$qty = $sku['qty'];
		}
		//preço por
		$priceCampaign = $this->prd['price'];
		if($is_variant){
			$priceCampaign = $sku['price'];
		}		
		if($this->promo){
			$priceCampaign = $this->prd['promotional_price'];
		}
		//preço de
		$listprice = $this->prd['list_price'] ? $this->prd['list_price'] : $this->prd['price'];
		if($is_variant){
			$listprice = $sku['list_price'] ? $sku['list_price'] : $sku['price'];
		}

		//EAN
		$ean = $this->prd['EAN'];
		if($is_variant){
			$ean = $sku['EAN'];
		}

			
			$variant = array(
				'idPaiExterno'		=> $prd_id,
				'exibirMatrizAtributos'	=> "Sim",
				"valido" => true,
				'contraProposta' => false,
				'sku' => $skumkt,
				"nomeProdutoPai" => substr(strip_tags($this->strReplaceName($this->prd['name']), " \t\n\r\0\x0B\xC2\xA0"), 0, 100),
				"nome" => substr(strip_tags($this->strReplaceName($this->prd['skuname']), " \t\n\r\0\x0B\xC2\xA0"), 0, 100),
				"fabricante" => html_entity_decode($this->prd['brandname']),
				"precoDe" => (float)$listprice,
				"precoPor" => (float)$priceCampaign,
				"freteGratis" => "Desconsiderar_Regras",
				"fatorMultiplicadorPreco" => 1,
				"peso" => (float)$this->prd['peso_bruto']*1000, // peso em gramas
				"altura" => (float)$this->prd['altura'],
				"comprimento" => (float)($this->prd['profundidade'] < 16) ? 16 : $this->prd['profundidade'],
				"largura" => (float)$this->prd['largura'],
				"garantia" => $this->prd['garantia'],
				"ean" => $ean,
				"estoque" => [
					array(
					"estoqueFisico" => $qty
				  )
				],
				"listaAtributos" => [],
				"condicao" => "Novo",
				"marketplace" => true,
				"buyBox" => true, //consultar no seller $this->store["buybox"]
				"idVinculoExterno" => $skumkt
			);

		$variant['listaAtributos'] = array_merge($variant['listaAtributos'], $this->skuAtributtes);
		$variant['listaAtributos'] = array_merge($variant['listaAtributos'], $this->prdAtributtes);

		return $variant;
	}

	protected function process($endPoint, $method = 'GET', $data = null, $prd_id = null, $int_to = null, $function = null)
	{
		//gravar log
		$sellerCredential = json_decode($this->integration_store['auth_data']);
		$sellerToken = $sellerCredential->token;
		//$credentials = $this->auth($this->auth_data->site, $this->auth_data->apikey);

		$this->header = [
			'content-type: application/json; charset=UTF-8',
			'Authorization: Basic ' . $sellerToken,
			'accept: application/json'
		];

		$url = 'https://' . $this->auth_data->api_url . $endPoint;

		// Reinicia o contador a cada nova chamada
		$this->attempt = 0; 
		
		while ($this->attempt < $this->maxAttempts) {
			$this->attempt++;

			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

			if ($method == 'POST') {
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			}

			if ($method == 'PUT') {
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
			}

			if ($method == 'DELETE') {
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
			}

			//curl_setopt($ch, CURLOPT_VERBOSE, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			$this->result       = curl_exec($ch);
			$this->responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
			$err        = curl_errno($ch);
			$errmsg     = curl_error($ch);

			curl_close($ch);

			 // Verifica os códigos de resposta
			if ($this->responseCode == 429) {
				$this->log("Muitas requisições já enviadas httpcode=429. Nova tentativa em $this->totalTimeSleep segundos.\n");
				sleep($this->totalTimeSleep);
			} elseif ($this->responseCode == 504) {
				$this->log("Deu Timeout httpcode=504. Nova tentativa em $this->totalTimeSleep segundos.\n");
				sleep($this->totalTimeSleep);
			} else if ($this->responseCode >= 200 && $this->responseCode <= 300 ) {
				break; 
			}else if($this->responseCode == 422){
                break;
            }
        }

		// Se o loop terminar e não houver sucesso
		if ($this->attempt >= $this->maxAttempts) {
			echo "Número máximo de tentativas atingido. Não tentarei novamente.\n";
			die;
		}

		if (!is_null($prd_id)) {
			$data_log = array(
				'int_to' => $int_to,
				'prd_id' => $prd_id,
				'url' => $url,
				'function' => $function,
				'method' => $method,
				'sent' => $data,
				'response' => $this->result,
				'httpcode' => $this->responseCode,
			);
			$this->model_log_integration_product_marketplace->create($data_log);
		}

		return;
	}


	function updateSellerCenterLastPost($prd, $variant = null)
	{

		$log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

		$variant_num = (is_null($variant)) ? $variant : $variant['variant'];
		$ean = $prd['EAN'];
		if ($prd['EAN'] == '') {
			if ($prd['is_kit'] == 1) {
				$ean = 'IS_KIT' . $prd['id'];
			} else {
				$ean = 'NO_EAN' . $prd['id'];
			}
			if (!is_null($variant_num)) {
				$ean = $ean . 'V' . $variant_num;
			}
		}
		$skulocal = 'P' . $this->prd['id'] . 'S' . $this->prd['store_id'] . $this->int_to . 'S';
		if (!is_null($variant_num)) {			
			$skulocal = 'P' . $this->prd['id'] . 'S' . $this->prd['store_id'] . $this->int_to . 'V' . $variant['variant'];
			if(!isset($variant['list_price']) || $variant['list_price'] == null || $variant['list_price'] <= $variant['price']){
				if(!isset($variant['price']) || $variant['price'] == null || $variant['price'] == 0){
					$variant['list_price'] = $prd['price'];
				}else{
					$variant['list_price'] = $variant['price'];
				}				
			}
			if(!isset($variant['price']) || $variant['price'] == null || $variant['price'] == 0){
				$variant['price'] = $prd['promotional_price'];
			}
		}else{
			$variant['price'] = $prd['promotional_price'];
			$variant['list_price'] = $prd['list_price'];
			if(!isset($variant['list_price']) || $variant['list_price'] == null || $variant['list_price'] <= $variant['price']){
				$variant['list_price'] = $prd['price'];	
			}

		}

		$data = array(
			'int_to' => $this->int_to,
			'prd_id' => $prd['id'],
			'variant' => $variant_num,
			'company_id' => $prd['company_id'],
			'store_id' => $prd['store_id'],
			'EAN' => $ean,
			'price' => $variant['price'],
			'list_price' => $variant['list_price'],
			'qty' => $prd['qty'],
			'qty_total' => $prd['qty'],
			'sku' => $prd['sku'],
			'skulocal' => $skulocal,
			'skumkt' => $this->prd_to_integration['skumkt'],
			'date_last_sent' => $this->dateLastInt,
			'tipo_volume_codigo' => $prd['tipovolumecodigo'] ?? null,
			'width' => (float)$prd['largura'],
			'height' => (float)$prd['altura'],
			'length' => (float)$prd['profundidade'],
			'gross_weight' => (float)$prd['peso_bruto'],
			'crossdocking' => (is_null($prd['prazo_operacional_extra'])) ? 1 : $prd['prazo_operacional_extra'],
			'zipcode' => preg_replace('/\D/', '', $this->store['zipcode']),
			'CNPJ' => preg_replace('/\D/', '', $this->store['CNPJ']),
			'freight_seller' =>  $this->store['freight_seller'],
			'freight_seller_end_point' => $this->store['freight_seller_end_point'],
			'freight_seller_type' => $this->store['freight_seller_type'],
		);

        $data = $this->formatFieldsUltEnvio($data);

		$savedSellerCenterlastPost = $this->model_sellercenter_last_post->createIfNotExist( $prd['id'], $this->int_to,  $data, $variant_num);
		if (!$savedSellerCenterlastPost) {
			$notice = 'Falha ao tentar gravar dados na tabela sellercenter_last_post.';
			echo $notice . "\n";
			$this->log_data('batch', $log_name, $notice, 'E');
			die;
		}
	}

	function hasShipCompany()
	{
		return true;
	}
	
	public function getLastPost(int $prd_id, string $int_to, int $variant = null)
	{
		return $this->model_sellercenter_last_post->getDataByIntToPrdIdVariant($int_to, $prd_id, $variant);
	}

	public function strReplaceName($name) {
        return str_replace('&amp;', '&', str_replace('&#039', "'", $name));
    }

    public function errorTransformation($prd_id, $sku, $msg, $step, $prd_to_integration_id = null, $mkt_code = null, $variant = null)
    {
        // Erro de transformação zera estoque.
        if ($this->getLastPost($prd_id, $this->int_to, $variant)) {
            $prd = $this->prd;

            if (!is_null($variant)) {
                $variant_data = getArrayByValueIn($this->variants, $variant, 'variant');
                $prd = $this->setDataVariantToUpdate($variant_data);
            }
            $prd['qty'] = 0;
            $prd['qty_original'] = 0;

            if (is_null($variant) && $this->prd['has_variants'] !== '') {
                if (count($this->variants) > 0) {
                    // Zera o estoque de todas as variações.
                    foreach ($this->variants as $variant_prd) {
                        $prd = $this->setDataVariantToUpdate($variant_prd);
                        $prd['qty'] = 0;
                        $prd['qty_original'] = 0;
                        $this->updateSellerCenterLastPost($prd, $variant_prd);
                    }
                }
            } else {
                $this->updateSellerCenterLastPost($prd, $variant);
            }
        }

        parent::errorTransformation($prd_id, $sku, $msg, $step, $prd_to_integration_id, $mkt_code, $variant);
    }

    private function setDataVariantToUpdate(array $variant)
    {
        $prd = $this->prd;
        $prd['qty']             = $variant['qty'];
        $prd['sku']             = $variant['sku'];
        $prd['price']           = $variant['price'];
        $prd['qty_original']    = $variant['qty_original'];
        $prd['EAN']             = ($variant['EAN'] != '') ? $variant['EAN'] : $this->prd['EAN'];

        if ($variant['status'] != 1) {
            $prd['qty'] = 0;
            $prd['qty_original'] = 0;
        }

        $this->prd = $prd;
    }
}
