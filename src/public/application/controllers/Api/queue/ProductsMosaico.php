<?php
require APPPATH . 'controllers/Api/queue/ProductsConectala.php';

/**
 * Classe responsável por envio de produtos da Mosaico para publicação.
 * Publicação da Mosaico é assincrona e em batches.
 * Retorna apenas o json para envio para a Mosaico.
 * 
 * NÃO CHAMAR DIRETAMENTE VIA API PARA TESTES (Não realiza o envio, apenas retorna o body, mas altera no banco o last post) 
 * @property	 Bucket									$bucket
 * 
 * @property	 Model_attributes						$model_attributes
 * @property	 Model_sellercenter_last_post 			$model_sellercenter_last_post	
 * @property	 Model_sku_locks						$model_sku_locks
 * 
 * @property	 CI_Output								$output
 */
class ProductsMosaico extends ProductsConectala
{
	/**
	 * @var 	 bool		Bool para indicar se há promoção aplicada.
	 */
	private $promo = false;

	/**
	 * @var 	 string	 	SellerId da loja.
	 */
	private $msc_seller_id;

	/**
	 * @var 	 array	 	Array com os dados dos produtos.
	 */
	private $bodies = [];

	const INACTIVATE = 0;
	const UPSERT = 1;

	public function __construct()
	{
		parent::__construct();
		$this->load->library('bucket');

		$this->load->model('model_attributes');
		$this->load->model('model_sellercenter_last_post');
		$this->load->model("model_sku_locks");
	}

	/**
	 * Chamada para envio dos produtos.
	 */
	public function index_post()
	{
		// Inicia 
		ob_start();

		// Verifica se as chaves estão corretas.
		$this->receiveData();

		// Verifica se deve atualizar, criar ou inativar.
		$this->checkAndProcessProduct();

		// Acabou a importação, retira da fila .
		$this->RemoveFromQueue();

		while (ob_get_level()) {
			ob_end_clean();
		}

		return $this->output->set_output(
			json_encode($this->bodies)
		);
	}

	/**
	 * Carrega dados de autenticação e verifica os produtos filhos.
	 */
	public function checkAndProcessProduct()
	{
		// Busca as credenciais.
		$this->getIntegration();

		$authDataStore = json_decode($this->integration_store["auth_data"], true);
		$this->msc_seller_id = $authDataStore["seller_id"] ?? null;
		parent::checkAndProcessProduct();
	}

	/**
	 * Monta os atributos do produto.
	 * Mosaico não possui categorização do nosso lado, portanto precisamos dos atributos do seller.
	 * @param	 array			$variant Dados da variação do produto.
	 * 
	 * @return	 array<string,string> Array contendo o nome do atributo como chave e o valor.
	 */
	private function getAttributes($variant)
	{
		$attributes = [];

		// Adiciona os tipos de variantes e valores para os atributos.
		if ($variant) {
			$typeVariants = explode(";", $this->prd["has_variants"]);
			$valueVariants = explode(";", $variant["name"]);

			foreach ($typeVariants as $key => $type) {
				if (!isset($valueVariants) || !$valueVariants[$key]) {
					continue;
				}

				$attributes[] = [
					"name" => $type,
					"value" => $valueVariants[$key]
				];
			}
		}

		$sellerAttr = $this->model_attributes->getAttributeDataByPrdId($this->prd["id"]);
		foreach ($sellerAttr as $attr) {
			$attributes[] = [
				"name" => $attr["name"],
				"value" => $attr["value"]
			];
		}

		return $attributes;
	}

	/**
	 * Implementa método da classe abstrata.
	 */
	function getLastPost(int $prd_id, string $int_to, ?int $variant = null)
	{
		return $this->model_sellercenter_last_post->getDataByIntToPrdIdVariant($int_to, $prd_id, $variant);
	}

	/**
	 * Mosaico necessita do nome do departamento e sub-departamento.
	 * Ex: Departamento(Eletrônicos) - SubDepartamento(Processador)
	 * Normaliza as categorias importadas e separa as mesmas.
	 */
	private function getNormalizedDepartements()
	{
		$categoryImported = $this->prd["category_imported"];

		// Normaliza a categoria importada, altera separadores utilizados para / e remove das extremidades.
		$categoryImported = str_replace(['>', '&gt;'], '/', $categoryImported);
		$categoryImported = trim($categoryImported, '/');

		$departments = explode('/', $categoryImported, 2);
		if (!$departments[0]) {
			return [
				"department" => null,
				"subDepartment" => null
			];
		}

		if (!isset($departments[1]) || !$departments[1]) {
			$departments[1] = $departments[0];
		}

		$departments[0] = trim($departments[0], '/');

		return [
			"department" => trim($departments[0], ' '),
			"subDepartment" => trim($departments[1], ' ')
		];
	}

	/**
	 * Busca as imagens do produto para enviar na Mosaico.
	 * @param	 string			$productImage Hash da pasta de imagem do produto.
	 * @param	 string			$variantImage Hash da pasta de imagem da variante. Opcional.
	 * 
	 * @return	 array{url:string,main:bool} 
	 */
	private function getProductImages($productImage, $variantImage = '')
	{
		$imagesData = [];

		// Caso seja a primeira variação executada, pega primeiro as imagens do produto pai. 
		if ($variantImage && empty($imagesData)) {
			$imagesData = $this->getProductImages($productImage, '');
		}

		$folder = $productImage;
		if ($variantImage) {
			// Chamada recursiva para pegar também as imagens do produto pai.
			$folder .= "/$variantImage";
		}

		$assetFolder = "assets/images/{$this->pathImage}/$folder";

		$images = $this->bucket->getFinalObject($assetFolder);
		foreach ($images['contents'] as $foto) {
			$images = [
				"url" => $foto["url"],
				"main" => empty($imagesData)
			];
			$imagesData[] = $images;
		}

		return $imagesData;
	}

	/**
	 * Retorna o SKU marketplace para determinado produto.
	 * Composto pelo id do produto, variação e int_to.
	 * Ex: 123_0_Mosaico -> Variação
	 * Ex: 1234_Mosaico  -> Simples
	 * @param	 array			$variant Array contendo a variação, null por padrão.
	 *
	 * @return 	 string
	 */
	private function getSkumkt($variant = null)
	{
		if (!$variant || is_null($variant["variant"])) {
			return $this->prd["id"] . '_' . $this->int_to;
		}
		return $this->prd["id"] . '_' . $variant["variant"] . '_' . $this->int_to;
	}

	/**
	 * Inativa os produtos na Mosaico e nas tabelas de last_post e integração.
	 * 
	 * @param	 mixed			$status_int Status a ser inserido para o produto.
	 * @param	 mixed			$disable
	 * @param	 mixed			$variant Dados da integração do produto (Caso disponíveis).
	 */
	function inactivateProduct($status_int, $disable, $variant = null)
	{
		$log_name = __CLASS__ . '/' . __FUNCTION__;

		$this->prepareProduct();

		if ($this->prd["has_variants"] !== '') {
			if (count($this->variants) == 0) {
				$erro = "As variações deste produto {$this->prd["id"]} sumiram.";
				$this->log_data('batch', $log_name, $erro, "E");
				die;
			}

			// Percorre cada variante do produto e realiza a desativação.
			foreach ($this->variants as $variant) {
				// Zera o estoque da variação para enviar para a Mosaico.
				$variant['qty'] = 0;
				$this->sendProduct($variant, self::INACTIVATE);

				// Busca a integração da variação especifica e atualiza para inativa.
				$prd_to_integration = $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'], $this->int_to, $variant['variant']);
				$this->model_integrations->updatePrdToIntegration(['status_int' => $status_int, 'date_last_int' => $this->dateLastInt], $prd_to_integration['id']);

				// Prepare e envia a variante.
				$prd = $this->setDataVariantToUpdate($variant);
				$this->upsertSellercenterLastPost($prd, $variant);
			}
			return;
		}

		// Prepara o produto, envia para a Mosaico e atualiza o status.
		$this->prd['qty'] = 0;
		$this->sendProduct(null, self::INACTIVATE);
		$this->model_integrations->updatePrdToIntegration(['status_int' => $status_int, 'date_last_int' => $this->dateLastInt], $this->prd_to_integration['id']);
		$this->upsertSellercenterLastPost($this->prd, $variant);
	}

	/**
	 * Método implementado, apenas wrapper para a chamada do Upsert.
	 */
	function insertProduct()
	{
		return $this->upsertProduct();
	}

	/**
	 * Realiza a montagem do body que será enviado para a Mosaico.
	 * @param	 array			$variant Dados da variante. Opcional, por padrão null.
	 * 
	 * @return	 array	Retorna o array com o body para envio na Mosaico.
	 */
	private function montaArraySku($variant = null)
	{
		$product = $this->prd;

		$varImage = isset($variant["image"]) ? $variant["image"] : null;
		$images = $this->getProductImages($product["image"], $varImage);
		$productSkuMkt = $this->getSkumkt($variant);

		// É obrigatório o envio de imagens para a Mosaico.
		if (count($images) == 0) {
			$this->errorTransformation($this->prd['id'],$productSkuMkt, 'Produto sem Imagem', 'Preparação para o envio', null, null, empty($variant) || $variant['variant'] === '' ? null : $variant['variant']);
			return null;
		}

		// Na Mosaico é necessário o envio do nome das categorias, normaliza o nome dos departamentos para envio.
		$departments = $this->getNormalizedDepartements();

		if ($this->promo) {
			$product["price"] = $product["promotional_price"];
		}
		// Armazena a variante ou o produto, utilizado para pegar preço, EAN e estoque, pois podem divergir do produto base.
		$sku = $variant ?? $product;
		$sku["list_price"] = $sku["list_price"] ?: $sku["price"];

		$body = [
			"department" => $departments["department"],
			"id" => $productSkuMkt,
			"name" => $product["name"],
			"price" => (float)$sku["price"],
			"sub_department" => $departments["subDepartment"],
			"url_images" => $images,
			"attributes" => $this->getAttributes($variant),
			"availability" => true,
			"base_price" => (float)$sku["list_price"],
			"description" => $product["description"],
			"ean" => $sku["EAN"],
			"quantity" => $sku["qty"],
			"sku" => $product["sku"],
			"stock_info" => [
				"height" => (float)$product["altura"],
				"length" => (float)$product["profundidade"],
				"weight" => (float)$product["peso_bruto"],
				"width" => (float)$product["largura"],
				"cross_docking" => $product["prazo_operacional_extra"]
			]
		];

		return $body;
	}

	/**
	 * Prepara o produto, inserindo informações adicionais.
	 */
	private function prepareProduct()
	{
		// Leio o percentual do estoque.
		$percEstoque = $this->percEstoque();

		$this->prd["promotional_price"] = $this->getPrice(null);

		if ($this->prd["promotional_price"] < $this->prd["price"]) {
			$this->promo = true;
		}

		if ($this->prd["promotional_price"] > $this->prd["price"]) {
			$this->prd["price"] = $this->prd["promotional_price"];
		}

		// Caso haja variação, acerta estoque.
		if ($this->prd["has_variants"] != '') {
			foreach ($this->variants as $key => $variant) {
				if ((int)$this->variants[$key]["qty"] < 0) {
					$this->variants[$key]["qty"] = 0;
				}

				$this->variants[$key]["qty"] = ceil((int) $variant["qty"] * $percEstoque / 100);
				if ((is_null($variant["price"])) || ($variant["price"] == '') || ($variant["price"] == 0)) {
					$this->variants[$key]["price"] = $this->prd["price"];
				}

				$this->variants[$key]["promotional_price"] = $this->getPrice($variant);
				if ($this->variants[$key]["promotional_price"] > $this->variants[$key]["price"]) {
					$this->variants[$key]["price"] = $this->variants[$key]["promotional_price"];
				}
			}
		}

		if ($this->prd["is_kit"]) {
			$productsKit = $this->model_products->getProductsKit($this->prd["id"]);
			$originalPrice = 0;
			foreach ($productsKit as $productkit) {
				$originalPrice += $productkit["qty"] * $productkit["original_price"];
			}

			$this->prd["price"] = $originalPrice;
		}

		// Seta o prazo operacional para ao menos 1 dia.
		if ($this->prd["prazo_operacional_extra"] < 1) {
			$this->prd["prazo_operacional_extra"] = 1;
		}

		return true;
	}

	/**
	 * Mosaico não apresenta produtos pai/filho, cada variação será criada separadamente.
	 * Caso não haja variação, realiza o envio do produto pai apenas.
	 * Caso haja variação, apenas envia as informações das mesmas como um produto completo.
	 * 
	 * @param	 array				$variant Dados da variação do produto.
	 * @param	 string				$message_type Tipo da mensagem.
	 */
	private function sendProduct($variant, $message_type)
	{
		$type = null;
		$body = null;

		$skuMkt = $this->getSkumkt($variant);

		$blocked = $this->model_sku_locks->getFirst([
			"prd_id" => $this->prd['id'],
			"sku_mkt" => $skuMkt
		]);
		if ($blocked) {
			return;
		}

		switch ($message_type) {
			case self::INACTIVATE:
				$type = "INACTIVATE";
				$body = ["id" => $skuMkt];
				break;
			case self::UPSERT:
				$body = $this->montaArraySku($variant);
				if (!$body) return;
				$this->updatePrdToIntegration($variant, $message_type);
				$type = "UPSERT";
				break;
		}

		// Adiciona o body ao array.
		$this->bodies[] = [
			"type" => "$type",
			"sku_mkt" => $body["id"],
			"json_data" => $body,
			"product_id" => $this->prd['id'],
			"seller_id" => $this->msc_seller_id,
			"store_id" => $this->store["id"],
		];
	}

	/**
	 * Método implementado, apenas wrapper para a chamada do Upsert.
	 */
	function updateProduct()
	{
		return $this->upsertProduct();
	}

	/**
	 * Atualiza o produto na tabela prd_to_integration.
	 * Para a Mosaico, a atualização já será realizada antes mesmo do envio.
	 */
	private function updatePrdToIntegration($variant)
	{
		$prd_to_integration = $this->model_integrations->getIntegrationsProductWithVariant($this->prd['id'], $this->int_to, $variant['variant'] ?? null);
		if ($prd_to_integration) {
			$this->model_integrations->updatePrdToIntegration(
				[
					'skumkt' 		=> $this->getSkumkt($variant),
					'skubling' 		=> $this->getSkumkt($variant),
					'status_int' 	=> 1,
					'date_last_int' => $this->dateLastInt
				],
				$prd_to_integration['id']
			);
		} else {
			$prd_to_integration = $this->model_integrations->getPrdIntegrationByIntToProdId($this->int_to, $this->prd['id']);
			$this->model_integrations->createPrdToIntegration(
				[
					'prd_id'             => $this->prd['id'],
					'company_id'         => $this->prd['company_id'],
					'status'             => 1,
					'status_int'         => 1,
					'int_to'             => $this->int_to,
					'skumkt'             => $this->getSkumkt($variant),
					'skubling'           => $this->getSkumkt($variant),
					'store_id'           => $this->prd['store_id'],
					'approved'           => $prd_to_integration['approved'],
					'int_id'             => $prd_to_integration['int_id'],
					'user_id'            => $prd_to_integration['user_id'],
					'int_type'           => $prd_to_integration['int_type'],
					'variant'            => $variant['variant'],
					'date_last_int' 	 => $this->dateLastInt
				]
			);
		}
	}

	/**
	 * Prepara o produto para envio e chama o método para realização do mesmo.
	 * Utilizado tanto para atualização quanto criação de produto.
	 * Também pode ser utilizado para mudança de disponibilidade.
	 * Mosaico não apresenta produtos pai/filho, cada variação será um produto completo.
	 */
	private function upsertProduct()
	{
		$log_name = __CLASS__ . '/' . __FUNCTION__;

		// Limpa os erros de transformação.
		$this->model_errors_transformation->setStatusResolvedByProductId($this->prd["id"], $this->int_to);

		$this->prepareProduct();

		// Tratamento para o caso de ter variações.
		if ($this->prd["has_variants"] !== '') {
			if (count($this->variants) == 0) {
				$erro = "As variações deste produto {$this->prd["id"]} sumiram.";
				$this->log_data('batch', $log_name, $erro, "E");
				die;
			}

			// Percorre cada variante do produto e realiza a inserção/desativação.
			foreach ($this->variants as $variant) {
				if ($variant["status"] != 1) {
					$this->disableProductVariant(null, $variant);
				} else {
					$this->sendProduct($variant, self::UPSERT);
					$prd['qty'] = 0;
					$prd = $this->setDataVariantToUpdate($variant);
					$this->upsertSellercenterLastPost($prd, $variant);
				}
			}
			return;
		}

		$this->sendProduct(null, self::UPSERT);
		$this->upsertSellerCenterLastPost($this->prd, null);
	}

	/**
	 * Realiza a atualização da tabela de último envio para a Mosaico.
	 */
	private function upsertSellercenterLastPost($prd, $variant = null)
	{
		$log_name = __CLASS__ . '/' . __FUNCTION__;

		$variant_num = (is_null($variant)) ? $variant : $variant['variant'];
		$ean = $prd['EAN'];
		if (!$ean) {
			if ($prd['is_kit'] == 1) {
				$ean = "IS_KIT{$prd['id']}";
			} else {
				$ean = "NO_EAN{$prd['id']}";
			}
			if (!is_null($variant_num)) {
				$ean = $ean . 'V' . $variant_num;
			}
		}

		$skuMkt = $this->getSkumkt($variant);
		if (!is_null($variant_num)) {
			if (!isset($variant['list_price']) || $variant['list_price'] == null || $variant['list_price'] <= $variant['price']) {
				if (!isset($variant['price']) || $variant['price'] == null || $variant['price'] == 0) {
					$variant['list_price'] = $prd['price'];
				} else {
					$variant['list_price'] = $variant['price'];
				}
			}
			if (!isset($variant['price']) || $variant['price'] == null || $variant['price'] == 0) {
				$variant['price'] = $prd['promotional_price'];
			}
		} else {
			$variant['price'] = $prd['promotional_price'];
			$variant['list_price'] = $prd['list_price'];
			if (!isset($variant['list_price']) || $variant['list_price'] == null || $variant['list_price'] <= $variant['price']) {
				$variant['list_price'] = $prd['price'];
			}
		}

		$data = [
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
			'skulocal' => $skuMkt,
			'skumkt' => $skuMkt,
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
			'seller_id' => $this->msc_seller_id
		];

		$data = $this->formatFieldsUltEnvio($data);
		$savedSellerCenterlastPost = $this->model_sellercenter_last_post->createIfNotExist($prd['id'], $this->int_to,  $data, $variant_num);
		if (!$savedSellerCenterlastPost) {
			$erro = 'Falha ao gravar dados na tabela sellercenter_last_post.';
			$this->log_data('batch', $log_name, $erro, 'E');
			die;
		}
	}

	/**
	 * Prepara o produto com variantes para ser inserido na Sellercenter_last_post. 
	 */
	private function setDataVariantToUpdate(array $variant)
	{
		$prd = $this->prd;
		$prd['qty']             = $variant['qty'];
		$prd['sku']             = $variant['sku'];
		$prd['price']           = $variant['price'];
		$prd['EAN']             = ($variant['EAN'] != '') ? $variant['EAN'] : $this->prd['EAN'];

		if ($variant['status'] != 1) {
			$prd['qty'] = 0;
		}

		return $prd;
	}
}
