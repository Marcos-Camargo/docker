<?php

require APPPATH . "libraries/Traits/VerifyFieldsProduct.trait.php";
class Product
{
    use VerifyFieldsProduct;

    public $allowable_tags = null;

    // tipo de variação para comparar, INFORMAR EM MINÚSCULO A CHAVE
    private $tipoVariacoes = array(
        'tamanho'   => 'TAMANHO',
        'size'      => 'TAMANHO',
        'tam'       => 'TAMANHO',
        'sapato'    => 'TAMANHO',
        'calcado'   => 'TAMANHO',
        'calçado'   => 'TAMANHO',
        'short'     => 'TAMANHO',
        'calca'     => 'TAMANHO',
        'calça'     => 'TAMANHO',
        'camisa'    => 'TAMANHO',
        'mascara'   => 'TAMANHO',
        'aro'       => 'TAMANHO',

        'cor'       => 'Cor',
        'color'     => 'Cor',

        'voltagem'  => 'VOLTAGEM',
        'voltage'   => 'VOLTAGEM',
        'volts'     => 'VOLTAGEM'
    );

    // tipo de unidade para comparar, INFORMAR EM MINÚSCULO A CHAVE
    private $tipoUnidades = array(
        'jg' => 'UN',
        'pc' => 'UN',
        'pç' => 'UN',
        'und' => 'UN',
        'pr' => 'UN',
        'par' => 'UN'
    );

    private $naoAtualizar = array(
        'situacao',
        // 'status',
        'image',
        'store_id',
        'company_id',
        'category_id'
    );

    private $CI;

    // Passagem de dados
    private $_this;


    private $product_length_name ;
    private $product_length_description ;
	private $require_ean;
	private $disable_brand_creation;
    public function __construct($_this)
    {
        $this->_this = $_this;

        $this->CI =& get_instance();
        $this->CI->load->library('BlacklistOfWords');
        $this->CI->load->model('model_settings');
		$this->CI->load->model('model_brands');

        if ($allowableTags = $this->CI->model_settings->getValueIfAtiveByName('products_allowable_tags')) {
            if (!empty($allowableTags)) {
                $this->allowable_tags = '<' . implode('><', explode(',', $allowableTags)) . '>';
            }
        }
        
        $this->loadLengthSettings();
		
		$this->require_ean = ($this->CI->model_settings->getStatusbyName('products_require_ean') == 1);
		$this->disable_brand_creation = ($this->CI->model_settings->getStatusbyName('disable_brand_creation_by_seller') == 1);
    }

    /**
     * Recupera dados do produto pelo código da Bling, se existir o id bling, mas o sku for diferente, vai deixa nulo do product_id_erp
     *
     * @param   integer     $idBling    Código do produto na bling
     * @return  null|array              Retorna um array com dados do produto ou null caso não encontre
     */
    public function getProductForIdErp($idBling, $sku = null)
    {
        $query = $this->_this->db->get_where('products use index (store_iderp)',
            array(
                'store_id'       => $this->_this->store,
                'product_id_erp' => $idBling
            )
        );

        if ($query->num_rows() == 0) return null;

        $result = $query->row_array();

        if ($sku && $result['sku'] != $sku) {
            // rick $this->updateProductForSku($result['sku'], array('product_id_erp' => null));
			$this->_this->model_products->update( array('product_id_erp' => null), $result['id'] , "Alterado Integração Bling" );
            return null;
        }

        return $result;
    }

    /**
     * Recupera dados do produto pelo SKU do produto
     *
     * @param   string      $sku    SKU do produto
     * @return  null|array          Retorna um array com dados do produto ou null caso não encontre
     */
    public function getProductForSku($sku)
    {
    	// rick 
		return $this->_this->model_products->getProductCompleteBySkyAndStore($sku, $this->_this->store); 
        /* return $this->_this->db->get_where('products use index (store_sku)',
            array(
                'store_id'  => $this->_this->store,
                'sku'       => $sku
            )
        )->row_array(); 
		 */
    }

    /**
     * Recupera dados da variação do produto pelo SKU do produto e SKU da variação
     *
     * @param   string    $sku      SKU do produto
     * @param   string    $skuVar   SKU da variação
     * @return  null|array          Retorna um array com dados da variação ou null caso não encontre
     */
    public function getVariationForSkuAndSkuVar($sku, $skuVar)
    {
		
        return $this->_this->db
            ->select('prd_variants.*')
            ->from('products')
            ->join('prd_variants', 'products.id = prd_variants.prd_id')
            ->where(
                array(
                    'products.sku' => $sku,
                    'products.store_id' => $this->_this->store,
                    'prd_variants.sku' => $skuVar
                )
            )
            ->get()
            ->row_array();
    }

    /**
     * Atualiza dados de produto pelo SKU do produto
     *
     * @param   string  $sku    SKU do produto
     * @param   array   $data   Dados para atualizar
     * @return  bool            Retorna o status da atualização
     */
    public function updateProductForSku($sku, $data = array())
    {
    	/* rick
        $this->_this->db->where(
            array(
                'sku'       => $sku,
                'store_id'  => $this->_this->store,
            )
        );
        return $this->_this->db->update('products', $data) ? true : false;
		*/
		$prd = $this->_this->model_products->getProductCompleteBySkyAndStore($sku, $this->_this->store); 
		
		return $this->_this->model_products->update($data,$prd['id'], "Alterado Integração Bling");
    }

    /**
     * Cria um novo produto
     *
     * @param   object  $payload    Payload produto para cadastro
     * @param   float   $precoProd  Preço diferenciado(lista de preço)
     * @return  array               Retorna o status do cadastro ou array de mapemaneto caso seja webhook
     */
    public function createProduct($payload, $precoProd = null)
    {
        if (isset($payload->codigoPai)) {
            echo "Esse produto é uma variação, aqui só entra produto pai ou simples";
            return array('success' => false, 'message' => array('Essa variação está tentando cadastrar como um produtos pai, verifique se o sku da variação é diferente do sku do produto pai, para corrigo-lo'));
        }
        if (isset($payload->tipo) && $payload->tipo != "P") {
            echo "Chegou um tipo que não é um produto. Chegou={$payload->tipo}";
            return array('success' => false, 'message' => array('Está tentando criar um tipo que não é um produto, recebemos o seguinte tipo: '.$payload->tipo));
        }

        // type - S=string, "F"=float, "I"=integer, "A"=array
        $product = array(
            'nome'              => array('value' => $payload->descricao, 'required' => true, 'type' => 'S', 'field_database' => 'name'),
            'sku'               => array('value' => $payload->codigo, 'required' => true, 'type' => 'S', 'field_database' => 'sku'),
            'un'                => array('value' => $payload->unidade, 'required' => true, 'type' => 'S', 'field_database' => 'attribute_value_id'),
            'preco'             => array('value' => $precoProd ?? $payload->preco, 'required' => true, 'type' => 'F', 'field_database' => 'price'),
            'ncm'               => array('value' => $payload->class_fiscal, 'required' => true, 'type' => 'S', 'field_database' => 'NCM'),
            'origem'            => array('value' => $payload->origem, 'required' => true, 'type' => 'I', 'field_database' => 'origin'),
            'ean'               => array('value' => $payload->gtin, 'required' => true, 'type' => 'S', 'field_database' => 'EAN'),
            'peso_liquido'      => array('value' => $payload->pesoLiq, 'required' => true, 'type' => 'F', 'field_database' => 'peso_liquido'),
            'peso_bruto'        => array('value' => $payload->pesoBruto, 'required' => true, 'type' => 'F', 'field_database' => 'peso_bruto'),
            'sku_fabricante'    => array('value' => $payload->codigoFabricante, 'required' => true, 'type' => 'S', 'field_database' => 'codigo_do_fabricante'),
//            'descricao'         => array('value' => $payload->descricaoCurta . $payload->descricaoComplementar, 'required' => true, 'type' => 'S', 'field_database' => 'description'),
            'descricao'         => array('value' => $payload->descricaoCurta, 'required' => true, 'type' => 'S', 'field_database' => 'description'),
            'garantia'          => array('value' => $payload->garantia, 'required' => true, 'type' => 'I', 'field_database' => 'garantia'),
            'fabricante'        => array('value' => $payload->marca, 'required' => true, 'type' => 'S', 'field_database' => 'brand_id'),
            'altura'            => array('value' => $payload->alturaProduto, 'required' => true, 'type' => 'F', 'field_database' => 'altura'),
            'comprimento'       => array('value' => $payload->profundidadeProduto, 'required' => true, 'type' => 'F', 'field_database' => 'profundidade'),
            'largura'           => array('value' => $payload->larguraProduto, 'required' => true, 'type' => 'F', 'field_database' => 'largura'),
            'categoria'         => array('value' => $payload->categoria->descricao ?? "", 'required' => true, 'type' => 'S', 'field_database' => 'category_imported'),
            'imagesAnexo'       => array('value' => $payload->imagem, 'required' => true, 'type' => 'A', 'field_database' => NULL),
            'variacoes'         => array('value' => $payload->variacoes ?? array(), 'required' => true, 'type' => 'A', 'field_database' => 'has_variants'),
            'prazo_operacional_extra' => array('value' => $payload->crossdocking, 'required' => true, 'type' => 'I', 'field_database' => 'prazo_operacional_extra')
        );

        if ($payload->unidadeMedida != "Centímetros") {
            if ($payload->unidadeMedida == "Metros") { // multiplicar por 100 para obter o valor em centimetros
                if ($product['altura']['value'] != 0) $product['altura']['value'] *= 100;
                if ($product['comprimento']['value'] != 0) $product['comprimento']['value'] *= 100;
                if ($product['largura']['value'] != 0) $product['largura']['value'] *= 100;
            }
            if ($payload->unidadeMedida == "Milímetro") { // dividir por 10 para obter o valor em centimetros
                if ($product['altura']['value'] != 0) $product['altura']['value'] /= 10;
                if ($product['comprimento']['value'] != 0) $product['comprimento']['value'] /= 10;
                if ($product['largura']['value'] != 0) $product['largura']['value'] /= 10;
            }
        }

        // Validar e formatar campos
        $productFormat = $this->getDataFormat($product, true);

        // Encontrou erro na formatação dos dados
        if (isset($productFormat['success']) && !$productFormat['success']) {
            return $productFormat;
        }

        $productFormat['product_id_erp'] = $payload->id;

        // upload de imagens
        $path = $this->getPathNewImage(); // folder que receberá as imagens
        // Faz upload e recupera os códigos
        $images = $this->getImages($productFormat['image'], $path['path_complet']);
        if (!$images['success']) {
            // encontrou erro no upload de imagem
            echo "erro upload imagem\n";
            return $images;
        }

        //define nome da pasta com as imagens e a imagem principal
        $productFormat['image'] = $path['path_product'];
        $productFormat['principal_image'] = $images['primary'];

        // remove imagens
        //$this->uploadproducts->deleteImgError($arrImages, $path['path_product']);

        if (count($productFormat['has_variants']['estoque'])) {
            $productFormat['qty'] = array_sum($productFormat['has_variants']['estoque']);
        } elseif (isset($payload->variacoes) && is_array($payload->variacoes) && count($payload->variacoes)) {
            $productFormat['qty'] = 0;
        } else {
            $productFormat['qty'] = $this->getGeneralStock($payload->depositos, $payload->estoqueAtual);
        }

        // recupera as variações para inserção e define o has_variants com os tipos
        $variationsProduct = $productFormat['has_variants'];
        $productFormat['has_variants'] = $productFormat['has_variants']['tipos'];

        // Inserção produto e pegar id para passar nas variações
        $prd_id = $this->_this->model_products->create($productFormat, "Criado Integração Bling" );

        // bloqueia produto se necessário
        $this->CI->blacklistofwords->updateStatusProductAfterUpdateOrCreate($productFormat, $prd_id);

//        $sqlProd = $this->_this->db->insert_string('products', $productFormat);
//        $this->_this->db->query($sqlProd);
//        $prd_id = $this->_this->db->insert_id(); // ID produto


        // formatar variações para criação
        $newPrice = 0;
        foreach ($variationsProduct['variacoes'] as $key => $variacao) {
            $skuVar     = $variacao['sku'];
            $idVar      = $variacao['id'];
            $precoVar   = $variacao['preco'];
            $qtyVar     = $variationsProduct['estoque'][$variacao['id']];
            $varStr     = "";

            if (array_key_exists('TAMANHO', $variacao['variacao']))   $varStr .= ";{$variacao['variacao']['TAMANHO']}";
            if (array_key_exists('Cor', $variacao['variacao']))       $varStr .= ";{$variacao['variacao']['Cor']}";
            if (array_key_exists('VOLTAGEM', $variacao['variacao'])) {
                $varStr .= ";{$variacao['variacao']['VOLTAGEM']}";
                // Se não tiver 'V' no número da voltagem, adicionar
                $unityVoltage = strpos( strtoupper($variacao['variacao']['VOLTAGEM']), 'V' );
                if (!$unityVoltage) $varStr .= 'V';
            }

            $varStr = substr($varStr,1);
			
			$image_dir = ''; 
			$imagesVar = $this->checkImageIsEqualFather($payload->imagem, $variacao['imagem'], $variacao['clonardadospai']=='S'); 
	        echo " variação ".$varStr." variant ".$key." com ".count($imagesVar)." imagens\n";
	        if (count($imagesVar)) {
	        	$image_dir= $key; 
	            $path = $this->getPathNewImage($productFormat['image']."/".$image_dir);
	            $images = $this->getImages($imagesVar, $path['path_complet']);
	            if (!$images['success']) {
	                // encontrou erro no upload de imagem
	                echo "erro upload imagem\n";
	                return $images;
	            }
	        }
            $verification_sku=$this->verifyFieldsProduct('sku',$skuVar,true,'S',true,$prd_id);
            if(!$verification_sku[0]){
                return array('success' => $verification_sku[0], 'message' => array($verification_sku[1]));
            }
            $verification_ean=$this->verifyFieldsProduct('ean',$payload->gtin,true,'S',true);
            if(!$verification_ean[0]){
                return array('success' => $verification_ean[0], 'message' => array($verification_ean[1]));
            }
            $this->_this->model_products->createvar(
                array(
                    'prd_id'                => $prd_id,
                    'variant'               => $key,
                    'name'                  => $varStr,
                    'sku'                   => $skuVar,
                    'qty'                   => $qtyVar,
                    'variant_id_erp'        => $idVar,
                    'price'                 => $precoVar,
                    'image'                 => $image_dir,
                    'status'                => 1,
                    'EAN'                   => $variacao['ean'],
                    'codigo_do_fabricante'  => '',
                )
            );

            if ($newPrice < $precoVar) $newPrice = $precoVar;
        }

        // atualiza preço
        if ($newPrice) 
        	$this->_this->model_products->update(array('price' => $newPrice), $prd_id , "Alterado Integração Bling" );
           // rick $this->_this->db->where(array('sku' => $productFormat['sku'], 'store_id' => $this->_this->store))->update('products', array('price' => $newPrice));

        return array('success' => true, 'variations_not_multiloja' => $variationsProduct['varNotMultiLoja']);
    }

    /**
     * Recupera nome das imagens e faz upload para pasta criada
     *
     * @param   array   $arrImages  URLs das imagen
     * @param   string  $path       Nome da pasta para upload
     * @return  array               Retorno o satus dos uploads e o nome das imagens
     */
    public function getImages($arrImages, $path)
    {
        $arrNameImages = array();
        $primaryImage = null;

        foreach ($arrImages as $image) {

            $url = $image->link;
            $url = trim($url);

            // não encontrou imagem para upload
            if (empty($url))
                return array('success' => false, 'message' => array('Imagem inválida, não foi possível recuperar a imagem, chegou em branco!'));

            $upload = $this->_this->uploadproducts->sendImageForUrl("{$path}/", $url);

            // não conseguiu fazer upload da imagem
            if (!$upload['success'])
                return array('success' => false, 'message' => array("Não foi possível salvar a imagem. Imagem recebida: {$url} <br>{$upload['data']}"));

            if ($primaryImage === null) $primaryImage = base_url("{$path}/{$upload['path']}");

            array_push($arrNameImages, $upload['path']);

        }

        return array('success' => true, 'images' => $arrNameImages, 'primary' => $primaryImage);
    }
	
	 /**
     * verifica se as imagens que estão no payload do pai estão também no payload do filho. Somente as diferentes são retornadas
     *
     * @return array Retorna com a lista de links para download sem as imagens do pai
     */
	public function checkImageIsEqualFather($imagens_princ,$imagens_var, $clone) 
	{
		 $arrNameImages = array();
		 foreach ($imagens_var as $image_var) {
		 	$include = true;
		 	foreach ($imagens_princ as $image_prin) {
		 		if (($image_var->link ==  $image_prin->link) && ($clone)) { // a imagem se repetiu no pai e é um clone, não inclui
		 			$include = false;
		 			break;
				} 
			}
			if ($include) {
				$arrNameImages[] = $image_var;
			}
		 }
		 return $arrNameImages;
	}

    /**
     * Recupera o nome de uma pasta para as imagens
     *
     * @return array Retorna o caminho da pasta e o nome da pasta
     */
    public function getPathNewImage($path = null)
    {
        $serverpath     = $_SERVER['SCRIPT_FILENAME'];
        $pos            = strpos($serverpath,'assets');
        $serverpath     = substr($serverpath,0,$pos);
        $targetDir      = $serverpath . 'assets/images/product_image/';
        $dirImage       = $path ?? Admin_Controller::getGUID(false); // gero um novo diretorio para as imagens
        $targetDir      .= $dirImage;

        if (!file_exists($targetDir)) {
            // cria o diretorio para o produto receber as imagens
            // força mod 775
            $oldmask = umask(0);
            @mkdir($targetDir, 0775);
            umask($oldmask);
        }

        return array('path_complet' => $targetDir, 'path_product' => $dirImage);
    }

    /**
     * Recupera os dados formatado do produto
     *
     * @param   array   $product        Produto para ser formatado
     * @param   bool    $newProduct     Validação de novo produto
     * @return  array                   Retorna o produto com a formatação para ser inserido
     */
    public function getDataFormat($product, $newProduct = false)
    {
        // cria array com valores pré definidos
        $productFormat  = array(
            'image'         => array(),
            'situacao'      => 1, // incompleto pois nunca irá categoria
            'store_id'      => $this->_this->store,
            'company_id'    => $this->_this->company,
            'category_id'   => '[""]'
        );

        if ($newProduct) $productFormat['status'] = 1; // sempre entrará como ativo para novos produtos

        $erros = array();

        foreach ($product as $key => $field) {
            if (in_array($key, ['origem','variacoes','un','fabricante'])) {
                // Verificações que são diferentes dentro da integração.
                $field_format = $this->verifyFields($key, $field['value'], $field['required'], $field['type'], $newProduct);
            } else {
                $field_format = $this->verifyFieldsProduct($key, $field['value'], $field['required'], $field['type'], $newProduct);
            }

            // encontrou um erro, deve encerrar a criação do produto e apresentar o motivo
            if (!$field_format[0])
                array_push($erros, $field_format[1]);

            if ($key == "imagesAnexo")
                $productFormat['image'] = $field_format[1];
            else
                $productFormat[$field['field_database']] = $field_format[1];
        }

        if (count($erros))
            return array(
                'success' => false,
                'message' => $erros
            );

        return $productFormat;
    }

    /**
     * Cria uma nova variação
     *
     * @param   object  $payload    Payload da variação para cadastro
     * @return  array               Retorna o status do cadastro
     */
    public function createVariation($payload, $skuPai)
    {
        // Pegar tipos de variações
        //$skuPai = str_replace("/", "-", $skuPai);
        $dataProduct = $this->getProductForSku($skuPai);
        $typesVariations = $dataProduct['has_variants'] == "" ? array() : explode(";", $dataProduct['has_variants']);
        $idProd = $dataProduct['id'];
        $verifySku=$this->verifyFieldsProduct('sku',$payload->codigo,true,true,'S',$idProd);
        if(!$verifySku[0]){
            return $this->changeProductIncomplete($verifySku[1], $dataProduct);
        }
        // if(!$this->validateSkuSpace($payload->codigo)){
        //     return array('success' => false, 'message' => $this->getMessagemSkuFormatInvalid());
        // }
        // if(!$this->validateLengthSku($payload->codigo)){
        //     return array('success' => false, 'message' => $this->getMessageLenghtSkuInvalid());
        // }
        // não podeerá mais ser atualizado
        if ($this->getPrdIntegration($idProd)) 
            return array('success' => false, 'message' => "Produto {$skuPai}, não pode mais receber novas variações pois já está integrado com o marketplace.");

		if (($this->require_ean) || ($payload->gtin != '')) { // se o EAN é 
            $verifyEan=$this->verifyFieldsProduct('ean',$this->require_ean,true,true,'S',$idProd);
            if(!$verifyEan[0]){
                return $this->changeProductIncomplete($verifySku[1], $dataProduct);
            }
			// if (!$this->_this->model_products->ean_check($payload->gtin)) {
			// 	return $this->changeProductIncomplete("O código EAN ( {$payload->gtin} ) inválido!", $dataProduct);
			// 	//return array('success' => false, 'message' => "O código EAN ( {$payload->gtin} ) inválido!");
			// }
		}

        $payloadPai = $this->getProductsMultiLoja($skuPai);

        $dataPayloadVar = null;
        if (isset($payloadPai->variacoes)) {
            foreach ($payloadPai->variacoes as $varPayload) {
                if ($varPayload->variacao->codigo == $payload->codigo) $dataPayloadVar = $varPayload;
            }
        }

        if (!$dataPayloadVar)
       		return $this->changeProductIncomplete("Não foi encontrada a variação {$payload->codigo} do produto {$skuPai}.", $dataProduct);
            // rick return array('success' => false, 'message' => "Não foi encontrada a variação {$payload->codigo} do produto {$skuPai}.");

        $tipoVarArr = array();
        $varArr     = array();

        $variacoes = $dataPayloadVar->variacao->nome;
        $variacoes = explode(';', $variacoes);

        foreach ($variacoes as $var) {

            $var = explode(':', $var);

            if (!isset($var[0]) || !isset($var[1])) continue;

            $tipo   = $var[0];
            $valor  = str_replace(';', ',', $var[1]);

            $realVarEnvia = false;

            foreach ($this->tipoVariacoes as $tipoVar => $realVar) {
                if ($this->likeText("%{$tipoVar}%", strtolower($tipo))) {
                    $realVarEnvia = $realVar;
                    continue;
                }
            }
            if (!$realVarEnvia) continue;

            if (!in_array($realVarEnvia, $tipoVarArr))
                array_push($tipoVarArr, $realVarEnvia);

            $varArr[$realVarEnvia] = $valor;
        }

        if (count($variacoes) != count($varArr)) {
			return $this->changeProductIncomplete("Foram encontradas variações não compatíveis com umas das variações (Variações aceitas: Cor/Tamanho/Voltagem).", $dataProduct);
            // return array('success' => false, 'message' => "Foram encontradas variações não compatíveis com umas das variações (Variações aceitas: Cor/Tamanho/Voltagem).");
        }
        if ((array_diff($typesVariations, $tipoVarArr) || array_diff($tipoVarArr, $typesVariations)) && count($typesVariations)) {
			return $this->changeProductIncomplete("Foram encontradas tipos de variações para essa variação que não estão cadastradas no produto. Todas as variações devem conter os mesmos tipos (Cor, Tamanho e Voltagem) ", $dataProduct);
            // return array('success' => false, 'message' => "Foram encontradas tipos de variações para essa variação que não estão cadastradas no produto. Todas as variações devem conter os mesmos tipos (Cor, Tamanho e Voltagem) ");
        }
        // formatar variações para criação
        $varStr = "";
        if (array_key_exists('TAMANHO', $varArr))   $varStr .= ";{$varArr['TAMANHO']}";
        if (array_key_exists('Cor', $varArr))       $varStr .= ";{$varArr['Cor']}";
        if (array_key_exists('VOLTAGEM', $varArr)) {
            $varStr .= ";{$varArr['VOLTAGEM']}";
            // Se não tiver 'V' no número da voltagem, adicionar
            $unityVoltage = strpos( strtoupper($varArr['VOLTAGEM']), 'V' );
            if (!$unityVoltage) $varStr .= 'V';
        }
        $varStr = substr($varStr,1);

        // Existe alguma variação com esses valores
        // atualizar o sku pro sku do erp
        $existVarSkuDiff = $this->getVariantsForIdAndName($idProd, $varStr);
        if ($existVarSkuDiff) {
            $this->updateSkuVariationForId($existVarSkuDiff['id'], $payload->codigo, $payload->id);
            //return array('success' => false, 'message' => "Foi encontrada uma variação de sku diferente, com os mesmos valores de uma variação já existente para esse produto.");
            return array('success' => true);
        }

        // Já existe esse sku, mas com valores diferentes
        if ($this->getVariantsForIdAndSku($idProd, $payload->codigo))
			return $this->changeProductIncomplete("Foi encontrada uma variação com o mesmo sku, mas com valores diferentes para esse produto.", $dataProduct);
            //rick return array('success' => false, 'message' => "Foi encontrada uma variação com o mesmo sku, mas com valores diferentes para esse produto.");

        $skuVar = $payload->codigo;
        if ($skuVar == "")
			return $this->changeProductIncomplete("O SKU da variação não pode ser em branco.", $dataProduct);
            // rick return array('success' => false, 'message' => "O SKU da variação não pode ser em branco.");

        // define o valor do estoque
        $qtyVar = $this->getGeneralStock($dataPayloadVar->variacao->depositos, $dataPayloadVar->variacao->estoqueAtual);

        // recuperar todas as variações para definir o 'variant'
        $variant = null;
        foreach ($this->getVariantProduct($idProd) as $varReal) {
            $variantReal = (int)$varReal['variant'];

            if ($variant && $variantReal > $variant) $variant = $variantReal;
            if (!$variant) $variant = $variantReal;
        }
        if ($variant === null) $variant = 0;
        else $variant++;
        $verification_sku=$this->verifyFieldsProduct('sku',$skuVar,true,'S',true,$idProd);
        if(!$verification_sku[0]){
            return array('success' => $verification_sku[0], 'message' => $verification_sku[1]);
        }
        $verification_ean=$this->verifyFieldsProduct('ean',$payload->gtin,true,'S',true);
        if(!$verification_ean[0]){
            return array('success' => $verification_ean[0], 'message' => $verification_ean[1]);
        }
        $preco = $payload->produtoLoja->preco->precoPromocional == 0 ? $payload->produtoLoja->preco->preco : $payload->produtoLoja->preco->precoPromocional;

        // Faz upload da images por variação rick
        $image_dir = ''; 
		$imagesVar = $this->checkImageIsEqualFather($payloadPai->imagem,$payload->imagem, $payload->clonarDadosPai=='S'); 
		echo " variação ".$varStr." variant ".$variant." com ".count($imagesVar)." imagens\n";
        if (count($imagesVar)) {
        	$image_dir= $variant; 
            $path = $this->getPathNewImage($dataProduct['image']."/".$image_dir);
            $images = $this->getImages($imagesVar, $path['path_complet']);
            if (!$images['success']) {
                // encontrou erro no upload de imagem
                echo "erro upload imagem\n";
                return $images;
            }
        }
        
        $createVar = $this->_this->model_products->createvar(
            array(
                'prd_id'                => $idProd,
                'variant'               => $variant,
                'name'                  => $varStr,
                'sku'                   => $skuVar,
                'qty'                   => $qtyVar,
                'variant_id_erp'        => $payload->id,
                'price'                 => $preco,
                'image'                 => $image_dir,
                'status'                => 1,
                'EAN'                   => $payload->gtin,
                'codigo_do_fabricante'  => '',
            ) //rick 
        );

        // Recuperar o estoque das variações para atualizar o produto pai
        $qtyAllVariations = $this->getStockVariation($idProd);

        $dataUpdateProd = array();
        // atualiza tipos de variação caso seja a primeira variação do produto
        if (!count($typesVariations) && count($tipoVarArr)) {
            $varStr = "";
            if (in_array('TAMANHO', $tipoVarArr))   $varStr .= ";TAMANHO";
            if (in_array('Cor', $tipoVarArr))       $varStr .= ";Cor";
            if (in_array('VOLTAGEM', $tipoVarArr))  $varStr .= ";VOLTAGEM";

            $varStr = $varStr != "" ? substr($varStr,1) : "";

            $dataUpdateProd['has_variants'] = $varStr;
        }

        // atualiza estoque do produto, podendo ser alterado o has_variants também
        $dataUpdateProd['qty'] = $qtyAllVariations;
        // rick $updateProduct = $this->updateProductForSku($skuPai, $dataUpdateProd);
        $updateProduct = $this->_this->model_products->update($dataUpdateProd, $idProd , "Alterado Integração Bling" );

        // atualiza preço
        $precoVerify = $payload->produtoLoja->preco->precoPromocional == 0 ? $payload->produtoLoja->preco->preco : $payload->produtoLoja->preco->precoPromocional;
        if ($dataProduct['price'] < $precoVerify)
			$this->_this->model_products->update(array('price' => $precoVerify), $idProd , "Alterado Integração Bling" );
          // rick   $this->_this->db->where(array('sku' => $dataProduct['sku'], 'store_id' => $this->_this->store))->update('products', array('price' => $precoVerify));

        if (!$updateProduct)
            return array('success' => false, 'message' => "Não foi possível atualizar o estoque do produto pai.");

        return array('success' => true);
    }

	public function changeProductIncomplete($msg, $dataProduct ) {
		
		if ($dataProduct['situacao'] == '2') { // se estava completo, transformo em incompleto
			$update = $this->_this->model_products->update(array('situacao' => '1', 'date_update' => date('Y-d-m H:i:s')),$dataProduct['id'], 'Alterado Integração Bling');
		}
		return array('success' => false, 'message' => $msg);
	}
/**
     * Verifica os campos para validação
     *
     * @param   string  $key        Campo para validação
     * @param   string  $value      Valor do campo
     * @param   boolean $required   Se é um campo obrigatório ou não
     * @param   string  $type       Tipo do dados para formatação
     * @param   bool    $newProduct Validação é para um novo produto
     * @return  array               Retorna uma array com 2 posições, a primeira diz o status da validação, a segunda uma mensagem complementar
     */
    public function verifyFields($key, $value, $required, $type, $newProduct,$product_id=null)
    {
        $value_ok = array(true, $this->setValueFormat($value, $type));

        if ($key === "origem" && ($value < 0 || $value > 8) && $required) {

            return array(false, "A origem do produto deve ser entre 0 e 8! http://legislacao.sef.sc.gov.br/html/regulamentos/icms/ricms_01_10.htm");

        }

        if ($key === "un" || $key === "fabricante") {

            $value = trim($value);

            if(empty($value)) {
                if ($key === 'un') return array(false, "Unidade não pode estar em branco!");
                if ($key === 'fabricante') return array(false, "Fabricante não pode estar em branco!");
            }

            if ($key === "un") $codeInfoProduct = $this->getCodeInfo('attribute_value', 'value', $value);
            elseif ($key === "fabricante") $codeInfoProduct = $this->getCodeInfo('brands', 'name', $value);

            if ($codeInfoProduct) $value_ok = array(true, $this->setValueFormat("[\"{$codeInfoProduct}\"]", $type));
            else{
                if ($key === "un") {

                    $existFromTo = false;
                    foreach ($this->tipoUnidades as $tipoUn => $realUn) {
                        if ($tipoUn == strtolower($value)) {
                            $searchUn = $this->getCodeInfo('attribute_value', 'value', $realUn);
                            $value_ok = array(true, $this->setValueFormat("[\"{$searchUn}\"]", $type));
                            $existFromTo = true;
                            break;
                        }
                    }

                    if ($required && !$existFromTo)
                        return array(false, "Unidade informada do produto não encontrada, informe uma válida. (UN/Kg)");
                }
                elseif ($key === "fabricante" && $required) return array(false, "Marca '".$value."' informado do produto não encontrado, informe um válido ou abra um chamado para a criação.");
                else $value_ok = array(true, $this->setValueFormat('[""]', $type));
            }

        }
        if ($key === "variacoes") {
            $varArr     = array();
            $tipoVarArr = array();
            $varCodes   = array();
            $stockReal  = array();
            $varNotMultiLoja = array();
            $value = $value == "" ? array() : $value;

            foreach ($value as $keyVar => $type_v) {

                $sku        = $type_v->variacao->codigo ?? $type_v->codigo;
                $payloadVar = $this->getProductsMultiLoja($sku);

                if ($sku == '')
                    return array(false, "Todas as variações precisam ter o código SKU preenchidos .");

                if (!isset($payloadVar->produtoLoja)) {
                    array_push($varNotMultiLoja, $sku);
                    echo "Variação {$sku} não está na multiloja\n";
                    continue;
                }

                $id         = $payloadVar->id;
                $preco      = $payloadVar->produtoLoja->preco->precoPromocional == 0 ? $payloadVar->produtoLoja->preco->preco : $payloadVar->produtoLoja->preco->precoPromocional;
                $variacoes  = $type_v->variacao->nome;

                if (isset($type_v->variacao->estoqueAtual))
                    $stockReal[$id] = $this->getGeneralStock($type_v->variacao->depositos, $type_v->variacao->estoqueAtual);
                else
                    $stockReal[$id] = $this->getGeneralStock($payloadVar->depositos ?? 0, $payloadVar->estoqueAtual ?? 0);

                // define o sku e id da variação
                $varArr[$keyVar]['sku']     = $sku;
                $varArr[$keyVar]['id']      = $id;
                $varArr[$keyVar]['preco']   = $preco;
                $varArr[$keyVar]['variacao']= array();
				$varArr[$keyVar]['imagem']	= $payloadVar->imagem;  //rick
				$varArr[$keyVar]['ean']		= $payloadVar->gtin; //rick 
				$varArr[$keyVar]['clonardadospai'] = isset($payloadVar->clonarDadosPai) ? $payloadVar->clonarDadosPai : false ; //rick 
				
                array_push($varCodes, $id);

                $variacoes = explode(';', $variacoes);

                foreach ($variacoes as $var) {

                    $var = explode(':', $var);

                    if (!isset($var[0]) || !isset($var[1])) continue;

                    $tipo   = $var[0];
                    $valor  = str_replace(';', ',', $var[1]);

                    $realVarEnvia = false;

                    foreach ($this->tipoVariacoes as $tipoVar => $realVar) {
                        if ($this->likeText("%{$tipoVar}%", strtolower($tipo))) {
                            $realVarEnvia = $realVar;
                            continue;
                        }
                    }
                    if (!$realVarEnvia) continue;

                    if (!in_array($realVarEnvia, $tipoVarArr))
                        array_push($tipoVarArr, $realVarEnvia);

                    $varArr[$keyVar]['variacao'][$realVarEnvia] = $valor;
                }

                if (count($variacoes) != count($varArr[$keyVar]['variacao']))
                    return array(false, "Foram encontradas variações não compatíveis com umas das variações (Variações aceitas: Cor/Tamanho/Voltagem).");
            }

            foreach ($varArr as $varVerify) {
                $verifyVarMerge = array();
                foreach($varVerify['variacao'] as $typeVarVerify => $valueVerify) {
                    array_push($verifyVarMerge, $typeVarVerify);
                }
                if (array_diff($verifyVarMerge, $tipoVarArr) || array_diff($tipoVarArr, $verifyVarMerge))
                    return array(false, "Todas as variações devem conter os mesmos tipos. Exemplo: <br> (Var1 = Cor:Preto;Tamanho:42) (Var2 = Cor:Preto;Tamanho: 40)");
            }

            $varStr = "";
            if (in_array('TAMANHO', $tipoVarArr))   $varStr .= ";TAMANHO";
            if (in_array('Cor', $tipoVarArr))       $varStr .= ";Cor";
            if (in_array('VOLTAGEM', $tipoVarArr))  $varStr .= ";VOLTAGEM";

            $value_ok = array(
                true,
                array(
                    'tipos'     => $varStr != "" ? substr($varStr,1) : "",
                    'variacoes' => $varArr,
                    'codigos'   => $varCodes,
                    'estoque'   => $stockReal,
                    'varNotMultiLoja' => $varNotMultiLoja
                )
            );

        }

        return $value_ok;
    }
    /**
     * Recupera os dados da multiloja
     *
     * @param   string  $sku    Código SKU para consulta
     * @return  object|bool            Retorna os dados da multiloja, caso não encontre retornará false
     */
    public function getProductsMultiLoja($sku)
    {
        $url = "https://bling.com.br/Api/v2/produto/{$sku}";
        $data = "&loja={$this->_this->multiStore}&estoque=S&imagem=S";

        if (function_exists('sendREST'))
            $dataProduct = $this->_this->sendREST($url, $data);
        else
            $dataProduct = $this->_sendREST($url, $data);

        if ($dataProduct['httpcode'] != 200) {
            if ($dataProduct['httpcode'] == 999)
            echo "Erro para consultar o produto, retorno=" . json_encode($dataProduct) . "\n";
            return false;
        }
        $contentProduct = json_decode($dataProduct['content']);

        if (!isset($contentProduct->retorno->produtos[0]->produto)) return false;

        return $contentProduct->retorno->produtos[0]->produto;
    }

    /**
     * Verifica se o SKU já está em uso
     *
     * @param   string  $sku    SKU do produto
     * @return  bool            Retorna se está disponível
     */
    public function verifySKUAvailable($sku)
    {
        return $this->_this->db->get_where('products use index (store_sku)',
                array(
                    'store_id'  => $this->_this->store,
                    'sku'       => $sku
                )
            )->num_rows() === 0;
    }

    /**
     * Recupera ID de algum valor do banco de dados (se for fabricante e não existir, irá cadastrar)
     *
     * @param   string  $table  Tabela do banco
     * @param   string  $column Coluna para where
     * @param   string  $value  Valor para where
     * @return  bool|integer    Retorna o valor da cosnulta ou false se não encontrou resultado
     */
    public function getCodeInfo($table, $column, $value)
    {
        $query = $this->_this->db
            ->select('id')
            ->from($table)
            ->where(array($column => $value))
            ->get();

        if ($query->num_rows() === 0 && $table == "brands") {
        	if ($this->disable_brand_creation) {
        		return false; 
			}
			return $this->CI->model_brands->create(array('name' => $value, 'active' => 1));  
        }

        if ($query->num_rows() === 0 && $table != "brands") return false;

        $result = $query->first_row();

        return $result->id;
    }

    /**
     * Consulta string em uma parte de outra string
     *
     * @param   string  $needle     Valor a ser procurado
     * @param   string  $haystack   Valor real para comparação
     * @return  bool                Retorna o status da consulta
     */
    public function likeText($needle, $haystack)
    {
        $regex = '/' . str_replace('%', '.*?', $needle) . '/';

        return preg_match($regex, $haystack) > 0;
    }

    /**
     * Define um novo tipo a um valor
     *
     * @param   string                  $value  Valor a ser formatado
     * @param   string                  $type   Tipo do valor a ser formatado
     * @return  array|float|int|string          Retorno formatado
     */
    public function setValueFormat($value, $type)
    {
        switch ($type) {
            case 'S': return (string)$value;
            case 'A': return (array)$value;
            case 'F': return (float)$value;
            case 'I': return (int)$value;
            default:  return $value;
        }
    }

    /**
     * Recupera dados das variações de um produto pelo ID do produto
     *
     * @param   string      $prd_id ID do produto
     * @return  null|array          Retorna um array com dados da das variações ou null caso não encontre
     */
    public function getVariantProduct($prd_id)
    {
    	/*
    	return $this->_this->db->get_where('prd_variants',
            array(
                'prd_id' => $prd_id
            )
        )->result_array();
		 */
		return $this->_this->model_products->getVariants($prd_id);
        
    }

    /**
     * Recupera dados das variações de um produto pelo ID do produto e SKU da variação
     *
     * @param   string      $product_id ID do produto
     * @param   string      $sku_var    SKU da variação
     * @return  null|array              Retorna um array com dados da das variações ou null caso não encontre
     */
    public function getVariantsForIdAndSku($product_id, $sku_var)
    {
        return $this->_this->db->get_where('prd_variants',
            array(
                'prd_id' => $product_id,
                'sku'    => $sku_var
            )
        )->result_array();
    }

    /**
     * Recupera dados das variações de um produto pelo ID do produto, SKU da variação e Valores de variação
     *
     * @param   string      $product_id ID do produto
     * @param   string      $name_var   Valor da variação
     * @return  null|array              Retorna um array com dados da das variações ou null caso não encontre
     */
    public function getVariantsForIdAndName($product_id, $name_var)
    {
        return $this->_this->db->get_where('prd_variants',
            array(
                'prd_id' => $product_id,
                'name'   => $name_var
            )
        )->row_array();
    }

    /**
     * Recupera o estoque das variações do produto pelo ID do produto
     *
     * @param   string      $prd_id ID do produto
     * @return  null|int            Retorna a quantidade em estoque das variações
     */
    public function getStockVariation($prd_id)
    {
        $countVar = 0;

        $query = $this->_this->db->get_where('prd_variants', array('prd_id' => $prd_id));
        if ($query->num_rows() == 0) return false;

        foreach($query->result_array() as $var)
            $countVar += $var['qty'];

        return $countVar;
    }

    /**
     * Atualiza o estoque do produto
     *
     * @param   string  $sku        SKU do produto
     * @param   float   $qty        Novo saldo do estoque do produto
     * @return  bool                Retorna o status da atualização
     */
    public function updateStockProduct($sku, $qty)
    {
    	// rick 
    	$prd = $this->_this->model_products->getProductCompleteBySkyAndStore($sku, $this->_this->store); 
		
		return $this->_this->model_products->update(array('qty' => $qty),$prd['id'], "Alterado Integração Bling");
		/*
        $this->_this->db->where(
            array(
                'sku'       => $sku,
                'store_id'  => $this->_this->store,
            )
        );
        return $this->_this->db->update('products', array('qty' => $qty)) ? true : false;
		 * */
    }

    /**
     * Atualiza o estoque da variação
     *
     * @param   string  $sku        SKU da variação
     * @param   string  $skuPai     SKU do produto
     * @param   float   $qty        Novo saldo do estoque da variação
     * @return  bool                Retorna o status da atualização
     */
    public function updateStockVariation($sku, $skuPai, $qty)
    {
        $product = $this->getProductForSku($skuPai);
        if (!$product) return false;

        $variations = $this->_this->db->get_where('prd_variants', array('prd_id' => $product['id']))->result_array();

        // Atualiza o estoque da variação
        //rick $this->_this->db->where(array('prd_id' => $product['id'], 'sku' => $sku))->update('prd_variants', array('qty' => $qty));

        $newQty = 0;
        foreach ($variations as $variation) {

            if ($variation['sku'] == $sku) {
            	$variation['qty'] = $qty; // define a nova quantidade
			    $this->_this->model_products->updateProductVar(array('qty' => $qty), $variation['id'],  "Alterado Integração Bling" );
			}
            $newQty += (float)$variation['qty'];
        }

        return $this->updateStockProduct($skuPai, $newQty) ? true : false;
    }

    /**
     * Atualiza o preço de um produto
     *
     * @param   string  $sku    SKU do produto
     * @param   float   $price  Preço do produto
     * @return  bool            Retorna o status da atualização
     */
    public function updatePrice($sku, $price)
    {
        // Atualiza o preço do produto
        // rick  return $this->_this->db->where(array('sku' => $sku, 'store_id' => $this->_this->store))->update('products', array('price' => $price)) ? true : false;
        $prd = $this->_this->model_products->getProductCompleteBySkyAndStore($sku, $this->_this->store); 
		
		return $this->_this->model_products->update(array('price' => $price),$prd['id'], "Alterado Integração Bling");
        }

    /**
     * Atualização de produtos
     *
     * @param   object  $payload    Payload produto para atualização
     * @return  array               Retorna o status da atualização
     */
    public function updateProduct($payload)
    {
        if (isset($payload->codigoPai)) {
            echo "Esse produto é uma variação, aqui só entra produto pai ou simples";
            return array('success' => false, 'message' => array('Essa variação está tentando cadastrar como um produtos pai, verifique se o sku da variação é diferente do sku do produto pai, para corrigo-lo'));
        }
        if (isset($payload->tipo) && $payload->tipo != "P") {
            echo "Chegou um tipo que não é um produto. Chegou={$payload->tipo}";
            return array('success' => false, 'message' => array('Está tentando criar um tipo que não é um produto, recebemos o seguinte tipo: '.$payload->tipo));
        }

        // type - S=string, "F"=float, "I"=integer, "A"=array
        $product = array(
            'nome'              => array('value' => $payload->descricao, 'required' => true, 'type' => 'S', 'field_database' => 'name'),
//            'sku'               => array('value' => $payload->codigo, 'required' => true, 'type' => 'S', 'field_database' => 'sku'),
            'un'                => array('value' => $payload->unidade, 'required' => true, 'type' => 'S', 'field_database' => 'attribute_value_id'),
//            'preco'             => array('value' => $precoProd ?? $payload->preco, 'required' => true, 'type' => 'F', 'field_database' => 'price'),
            'ncm'               => array('value' => $payload->class_fiscal, 'required' => true, 'type' => 'S', 'field_database' => 'NCM'),
            'origem'            => array('value' => $payload->origem, 'required' => true, 'type' => 'I', 'field_database' => 'origin'),
            'ean'               => array('value' => $payload->gtin, 'required' => true, 'type' => 'S', 'field_database' => 'EAN'),
            'peso_liquido'      => array('value' => (float)$payload->pesoLiq, 'required' => true, 'type' => 'F', 'field_database' => 'peso_liquido'),
            'peso_bruto'        => array('value' => (float)$payload->pesoBruto, 'required' => true, 'type' => 'F', 'field_database' => 'peso_bruto'),
            'sku_fabricante'    => array('value' => $payload->codigoFabricante, 'required' => true, 'type' => 'S', 'field_database' => 'codigo_do_fabricante'),
//            'descricao'         => array('value' => $payload->descricaoCurta . $payload->descricaoComplementar, 'required' => true, 'type' => 'S', 'field_database' => 'description'),
            'descricao'         => array('value' => $payload->descricaoCurta, 'required' => true, 'type' => 'S', 'field_database' => 'description'),
            'garantia'          => array('value' => $payload->garantia, 'required' => true, 'type' => 'I', 'field_database' => 'garantia'),
            'fabricante'        => array('value' => $payload->marca, 'required' => true, 'type' => 'S', 'field_database' => 'brand_id'),
            'altura'            => array('value' => (float)$payload->alturaProduto, 'required' => true, 'type' => 'F', 'field_database' => 'altura'),
            'comprimento'       => array('value' => (float)$payload->profundidadeProduto, 'required' => true, 'type' => 'F', 'field_database' => 'profundidade'),
            'largura'           => array('value' => (float)$payload->larguraProduto, 'required' => true, 'type' => 'F', 'field_database' => 'largura'),
            'categoria'         => array('value' => $payload->categoria->descricao ?? '', 'required' => true, 'type' => 'S', 'field_database' => 'category_imported'),
            'imagesAnexo'       => array('value' => $payload->imagem, 'required' => true, 'type' => 'A', 'field_database' => NULL),
            'variacoes'         => array('value' => $payload->variacoes ?? array(), 'required' => true, 'type' => 'A', 'field_database' => 'has_variants'),
            'prazo_operacional_extra' => array('value' => $payload->crossdocking, 'required' => true, 'type' => 'I', 'field_database' => 'prazo_operacional_extra')
        );

        if ($payload->unidadeMedida != "Centímetros") {
            if ($payload->unidadeMedida == "Metros") { // multiplicar por 100 para obter o valor em centimetros
                if ($product['altura']['value'] != 0) $product['altura']['value'] *= 100;
                if ($product['comprimento']['value'] != 0) $product['comprimento']['value'] *= 100;
                if ($product['largura']['value'] != 0) $product['largura']['value'] *= 100;
            }
            if ($payload->unidadeMedida == "Milímetro") { // dividir por 10 para obter o valor em centimetros
                if ($product['altura']['value'] != 0) $product['altura']['value'] /= 10;
                if ($product['comprimento']['value'] != 0) $product['comprimento']['value'] /= 10;
                if ($product['largura']['value'] != 0) $product['largura']['value'] /= 10;
            }
        }

        // Validar e formatar campos
        $productFormat = $this->getDataFormat($product, false);

        // Encontrou erro na formatação dos dados
        if (isset($productFormat['success']) && !$productFormat['success'])
            return $productFormat;

        $productFormat['product_id_erp'] = $payload->id;

        $errosVar = array();
        $successVar = array();

        // recupera dados do produto original
        $dataProduct = $this->getProductForSku($payload->codigo);

        foreach ($productFormat['has_variants']['variacoes'] as $variant) {

            // verifica se o sku não está na multiloja
            if (in_array($variant['sku'], $productFormat['has_variants']['varNotMultiLoja'])) continue;

            $updateVar = $this->updateVariation($payload->variacoes, $payload->codigo, $variant['sku']);
            if ($updateVar['success'] === false) { // ocorreu um problema
                array_push($errosVar, array(
                    'title' => "Alerta para atualizar a variacao SKU {$variant['sku']}",
                    'description' => "Não foi possível atualizar a variação! <ul><li>{$updateVar['message']}</li></ul> <br> <strong>ID Bling</strong>:{$productFormat['product_id_erp']}<br><strong>SKU</strong>:{$payload->codigo}<br><strong>SKU Variação</strong>:{$productFormat['name']}"
                ));
                echo "Ocorreu um problema na alteração na variação {$variant['sku']}\n";
            } elseif ($updateVar['success'] === null) { // não teve alteração ou já está integrado com marketplace
                echo "Não ocorreu alteração na variação {$variant['sku']}\n";
            } else {
                array_push($successVar, array(
                    'title' => "Variação {$variant['sku']} do produto {$payload->codigo} atualizado",
                    'description' => "<h4>Variação atualizada com sucesso</h4> <ul><li>A variação {$variant['sku']} do produto {$payload->codigo}, foi atualizado com sucesso</li></ul><br><strong>ID Bling</strong>: {$productFormat['product_id_erp']}<br><strong>SKU</strong>: {$payload->codigo}<br><strong>Descrição</strong>: {$productFormat['name']}"
                ));
            }
        }

        $imagesERP = $productFormat['image'];

        // remove os itens que não derão ser atualizados
        foreach ($this->naoAtualizar as $field)
            unset($productFormat[$field]);
        // verificar se o produto já foi integrado
        if (!$this->getPrdIntegration($dataProduct['id']))
            $productFormat['has_variants'] = $productFormat['has_variants']['tipos'];
//        else unset($productFormat['has_variants']);
        else
            return array('success' => null, 'errorsVar' => $errosVar, 'successVar' => $successVar);

        if (!isset($productFormat['status']) && $dataProduct['status'] == 4) $productFormat['status'] = 1;

        // Faz upload e recupera os códigos
        if (count($imagesERP) && !$this->_this->uploadproducts->countImagesDir($dataProduct['image'])) {
            $path = $this->getPathNewImage($dataProduct['image']);
            $images = $this->getImages($imagesERP, $path['path_complet']);
            if (!$images['success']) {
                // encontrou erro no upload de imagem
                echo "erro upload imagem\n";
                return $images;
            }
            $productFormat['principal_image'] = $images['primary'];

            if (
                (($dataProduct['category_id'] != '[""]') &&
                ($dataProduct['brand_id'] != '[""]' || $productFormat['brand_id'] != '[""]') &&
                $this->_this->uploadproducts->countImagesDir($dataProduct['image'])  || (!empty($errosVar)))
            )
                $productFormat['situacao'] = 2;
            else
                $productFormat['situacao'] = 1;
        }

        if ($this->getProductForAttributes($productFormat))
            return array('success' => null, 'errorsVar' => $errosVar, 'successVar' => $successVar);

        // Inserção produto e pegar id para passar nas variações
        /* rick 
        $sqlProd = $this->_this->db->update_string('products', $productFormat, array('sku' => $payload->codigo, 'store_id' => $this->_this->store));
        $this->_this->db->query($sqlProd);
		*/
		$this->_this->model_products->update($productFormat, $dataProduct['id'] , "Alterado Integração Bling" );
			
        // bloqueia produto se necessário
        $dataProductUpdated = $this->getProductForSku($payload->codigo);
        if ($dataProductUpdated)
            $this->CI->blacklistofwords->updateStatusProductAfterUpdateOrCreate($dataProductUpdated, $dataProduct['id']);

        return array('success' => true, 'errorsVar' => $errosVar, 'successVar' => $successVar);
    }

    /**
     * Recupera produto por seus atributos
     *
     * @param   array   $data   Atributos para consulta
     * @return  mixed           Retorna o produto, caso não encontre retornará null
     */
    public function getProductForAttributes($data)
    {
        $data['store_id'] = $this->_this->store;
        $data['company_id'] = $this->_this->company;

        $query = $this->_this->db->get_where('products', $data);
        return $query->row_array();
    }

    /**
     * Atualização de uma variação
     *
     * @param   object  $payload    Payload da variação para cadastro
     * @param   string  $skuPai     SKU do produto PAI
     * @return  array               Retorna o status do cadastro
     */
    public function updateVariation($payload, $skuPai, $skuVar)
    {
        // Pegar tipos de variações
        //$skuPai = str_replace("/", "-", $skuPai);
        $dataProduct = $this->getProductForSku($skuPai);
        $typesVariations = $dataProduct['has_variants'] == "" ? array() : explode(";", $dataProduct['has_variants']);
        $idProd = $dataProduct['id'];
        $tipoVarArr = array();
        $varArr     = array();

        // não podeerá mais ser atualizado
        if ($this->getPrdIntegration($idProd))
            return array('success' => null);

        foreach ($payload as $var) {
            if ($var->variacao->codigo != $skuVar) continue;

            $variacoes = $var->variacao->nome;
            $variacoes = explode(';', $variacoes);

            foreach ($variacoes as $var) {

                $var = explode(':', $var);

                if (!isset($var[0]) || !isset($var[1])) continue;

                $tipo = $var[0];
                $valor = str_replace(';', ',', $var[1]);

                $realVarEnvia = false;

                foreach ($this->tipoVariacoes as $tipoVar => $realVar) {
                    if ($this->likeText("%{$tipoVar}%", strtolower($tipo))) {
                        $realVarEnvia = $realVar;
                        continue;
                    }
                }
                if (!$realVarEnvia) continue;

                if (!in_array($realVarEnvia, $tipoVarArr))
                    array_push($tipoVarArr, $realVarEnvia);

                $varArr[$realVarEnvia] = $valor;
            }
        }

        if (count($variacoes) != count($varArr))
            return array('success' => false, 'message' => "Foram encontradas variações não compatíveis com umas das variações (Variações aceitas: Cor/Tamanho/Voltagem).");

        if ((array_diff($typesVariations, $tipoVarArr) || array_diff($tipoVarArr, $typesVariations)) && count($typesVariations))
            return array('success' => false, 'message' => "Foram encontradas tipos de variações para essa variação que não estão cadastradas no produto. Todas as variações devem conter os mesmos tipos (Cor, Tamanho e Voltagem) ");

        // formatar variações para criação
        $varStr = "";
        if (array_key_exists('TAMANHO', $varArr))   $varStr .= ";{$varArr['TAMANHO']}";
        if (array_key_exists('Cor', $varArr))       $varStr .= ";{$varArr['Cor']}";
        if (array_key_exists('VOLTAGEM', $varArr)) {
            $varStr .= ";{$varArr['VOLTAGEM']}";
            // Se não tiver 'V' no número da voltagem, adicionar
            $unityVoltage = strpos( strtoupper($varArr['VOLTAGEM']), 'V' );
            if (!$unityVoltage) $varStr .= 'V';
        }
        $varStr = substr($varStr,1);

        $dataVar = $this->getVariantsForIdAndSku($idProd, $skuVar);
        // não teve alteração
        if (count($dataVar) && $dataVar[0]['name'] == $varStr)
            return array('success' => null);

		if (count($dataVar) === 0) {
			echo " NÂO ACHEI A VARIAÇÂO do PRODUTO ".$idProd." COM O SKU ".$skuVar."\n";
			return array('success' => false, 'message' => "Não foi encontrada variação com sku ".$skuVar." para atualizar.");
		} 
        // Inserir variação
        /* rick
        $sqlVar = $this->_this->db->update_string('prd_variants', array('name' => $varStr), array('prd_id' => $idProd, 'sku' => $skuVar));
        $updateVar = $this->_this->db->query($sqlVar); // status da variação atualizada
		*/
        $updateVar = $this->_this->model_products->updateProductVar(array('name' => $varStr), $dataVar[0]['id'], "Alterado Integração Bling" );

        if (!$updateVar)
            return array('success' => false, 'message' => "Não foi possível atualizar a variação.");

        return array('success' => true);
    }

    /**
     * Recupera o estoque no banco de dados de um produto ou variação
     *
     * @param   string      $sku    SKU do produto PAI
     * @param   null|string $skuVar SKU da variação
     * @return  false|int           Retorna o estoque do produto/variação ou false em falha
     */
    public function getStockForSku($sku, $skuVar = null)
    {
        $query = $this->_this->db->get_where('products use index (store_sku)', array('store_id' => $this->_this->store, 'sku' => $sku));
        if ($query->num_rows() == 0) return false;
        $result = $query->row_array();

        if (!$skuVar)
            return $result['qty'];

        $query = $this->_this->db->get_where('prd_variants', array('prd_id' => $result['id'], 'sku' => $skuVar));
        if ($query->num_rows() == 0) return false;

        $result = $query->row_array();

        return $result['qty'];
    }

    /**
     * Recupera o estoque no banco de dados de um produto ou variação pelo ID do erp
     *
     * @param   string      $idProduct  ID do produto PAI
     * @param   null|string $idVariant  ID da variação
     * @return  false|int               Retorna o estoque do produto/variação ou false em falha
     */
    public function getStockForIdErp($idProduct, $idVariant = null)
    {
        $queryProd = $this->_this->db->get_where('products use index (store_iderp)', array('store_id' => $this->_this->store, 'product_id_erp' => $idProduct));
        if ($queryProd->num_rows() == 0) return false;
        $resultProd = $queryProd->row_array();

        if (!$idVariant)
            return array('qty' => $resultProd['qty'], 'sku' => $resultProd['sku']);

        $queryVar = $this->_this->db->get_where('prd_variants', array('prd_id' => $resultProd['id'], 'variant_id_erp' => $idVariant));
        if ($queryVar->num_rows() == 0) return false;

        $resultVar = $queryVar->row_array();

        return array('qty' => $resultVar['qty'], 'skuProd' => $resultProd['sku'], 'skuVar' => $resultVar['sku']);
    }

    /**
     * Recupera dados da variação pelo código da Bling
     *
     * @param   integer     $idBling    Código da variação na bling
     * @return  null|array              Retorna um array com dados da variação ou null caso não encontre
     */
    public function getVariationForIdErp($idBling)
    {
        return $this->_this->db
            ->select('prd_variants.*')
            ->from('prd_variants')
            ->join('products', 'products.id = prd_variants.prd_id')
            ->where(
                array(
                    'prd_variants.variant_id_erp' => $idBling,
                    'products.store_id' => $this->_this->store
                )
            )
            ->get()
            ->row_array();
    }

    /**
     * Atualiza dados da variação pelo SKU do produto
     *
     * @param   string  $skuProduct SKU do produto
     * @param   string  $skuVar     SKU da variação
     * @param   array   $data       Dados para atualizar
     * @return  bool                Retorna o status da atualização
     */
    public function updateVariantForSku($skuProduct, $skuVar, $data = array())
    {
    	/* rick 
        $queryProd = $this->_this->db->get_where('products use index (store_sku)', array('store_id' => $this->_this->store, 'sku' => $skuProduct));
        $resultProd = $queryProd->row_array();
				
        $this->_this->db->where(
            array(
                'prd_id'    => $resultProd['id'],
                'sku'       => $skuVar,
            )
        );
		
        return $this->_this->db->update('prd_variants', $data) ? true : false;
		*/
		$resultProd = $this->_this->model_products->getProductCompleteBySkyAndStore($skuProduct, $this->_this->store); 
		return $this->_this->model_products->updateVarBySku($data, $resultProd['id'], $skuVar, 'Alterando Variação Integração Eccosys');
    }

    /**
     * Atualiza estoque via webhook do produto ou variação
     *
     * @param   object  $payload    Dados do produto
     * @return  bool                Retorna o status da atualização
     */
    public function updateStock($payload)
    {
        $qty        = $this->getGeneralStock($payload->depositos, $payload->estoqueAtual);
        $sku        = $payload->codigo;

        // verifica se é produto
        $getProduct = $this->getProductForSku($sku);
        if ($getProduct) {
            if (!$getProduct) return false;
            $tipoVariacao = "N";
        }
        // se produto não existe, verifica se é variação
        elseif ($getVariation = $this->getVariantForSku($sku)) {
            if (!$this->getVariationForSkuAndSkuVar($getVariation['sku_pai'], $sku)) return false;
            $tipoVariacao = "V";
        }
        // não encontrou do produto/variação
        else return false;

        // verificar se produto está na lista
        $productMultiLoja = $this->getProductsMultiLoja($sku);
        if ($productMultiLoja === null) return null;
        if ($productMultiLoja === false) return false;

        // atualizar
        if ($tipoVariacao == "N")
            return $this->updateStockProduct($sku, $qty);
        elseif ($tipoVariacao == "V")
            return $this->updateStockVariation($sku, $getVariation['sku_pai'], $qty);

        return false;
    }

    /**
     * Recupera dados das variações de um produto pelo ID do produto
     *
     * @param   string      $sku    SKU da variação
     * @return  null|array          Retorna um array com dados da das variações ou null caso não encontre
     */
    public function getVariantForSku($sku)
    {
        return $this->_this->db
            ->select('prd_variants.*, products.sku as sku_pai')
            ->from('products')
            ->join('prd_variants', 'products.id = prd_variants.prd_id')
            ->where(
                array(
                    'products.store_id' => $this->_this->store,
                    'prd_variants.sku' => $sku
                )
            )
            ->get()
            ->row_array();
    }

    /**
     * Request API
     *
     * @param string        $url    URL requisição
     * @param null|string   $data   Dados para envio no body
     * @param string        $method Metodo da requisição
     * @return mixed
     * @throws Exception
     *
     * Caso seja ultrapassado o limite a requisição retornará o status 429 (too many requests) e a mensagem:
     * O limite de requisições foi atingido.
     */
    public function _sendREST($url, $data = '', $method = 'GET', $newRequest = true)
    {
        if ($newRequest) {
            $url .= "/{$this->_this->formatReturn}/";
            $data = "?apikey={$this->_this->token}{$data}";
        }

        $curl_handle = curl_init();
        if ($method == "GET") {
            curl_setopt($curl_handle, CURLOPT_URL, $url . $data);
        }
        elseif ($method == "POST" || $method == "PUT") {
            $data =  substr($data, 1);
            $data = explode('&', $data);
            $arrPost = array();


            foreach ($data as $value) {
                $impPost = explode("=", $value);
                $value = str_replace("{$impPost[0]}=", '', $value);

                if ($impPost[0] == "xml") {
                    $value = rawurlencode($value);
                }

                $arrPost[$impPost[0]] = $value;
            }

            if ($method == "PUT")
                curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'PUT');

            curl_setopt($curl_handle, CURLOPT_URL, $url);
            curl_setopt($curl_handle, CURLOPT_POST, count($arrPost));
            curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $arrPost);
        }

        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, TRUE);
        $response = curl_exec($curl_handle);
        $httpcode = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
        curl_close($curl_handle);

        $header['httpcode'] = $httpcode;
        $header['content']  = $response;

        $header = $this->_apiBlockSleepBling($url, $data, $method, $header);

        return $header;
    }

    /**
     * Verifica se a API foi bloqueada por limite de requisição
     *
     * @param   string  $url        URL requisição
     * @param   string  $data       Dados para envio no body
     * @param   string  $method     Método da requisição
     * @param   string  $header     Resposta da requisição atual
     * @return  array               Retorno da requisição
     */
    public function _apiBlockSleepBling($url, $data, $method, $header)
    {
        $attempts = 15;
        // enquanto a api estiver bloqueada ficará tentando até encontrar o resultado
        while ($header['httpcode'] == 429) {
            $content = json_decode($header["content"]);
            if ($this->_this->countAttempt > $attempts) {
                return array('httpcode' => 999, 'content' => '{"retorno":{"erros":{"erro": {"cod": 3}}}}');;
            }

            if (
                !isset($content->retorno->erros->erro->msg) ||
                !likeText('%por segundo foi atingido%', strtolower($content->retorno->erros->erro->msg))
            ) {
                echo "API Bloqueada, vou esperar 5s e tentar novamente (Tentativas: {$this->_this->countAttempt}/$attempts)...\n";
                $this->_this->countAttempt++;
            } else {
                echo "Bloqueio por segundo. (Tentativas: {$this->_this->countAttempt}/$attempts).\n";
            }
            sleep(5); // espera 1 minuto

            $header = $this->_sendREST($url, $data, $method, false); // enviar uma nova requisição para ver se já liberou
        }

        return $header;
    }

    /**
     * Recupera estoque das variações de um produto Pai
     *
     * @param   array $payload  Dados do produto Pai
     * @return  array           Retorna o atual e o novo estoque
     */
    public function getStockProductPai($payload)
    {
        $product = array(
            'variacoes' => array(
                'value' => $payload->variacoes ?? array(),
                'required' => true,
                'type' => 'A',
                'field_database' => 'has_variants'
            ),
        );

        // Recupera dados da variação
        $productFormat = $this->getDataFormat($product);

        $stockProduct = 0;

        // Encontrou erro na formatação dos dados
        if (isset($productFormat['success']) && !$productFormat['success']) {
            return $productFormat;
        }

        foreach($productFormat['has_variants']['estoque'] as $stock)
            $stockProduct += $stock;

        return array('current' => (int)$this->getStockForSku($payload->codigo), 'new' => $stockProduct, 'allStock' => $productFormat['has_variants']['estoque']);
    }

    /**
     * Atualizar o código sku pelo ID da variação
     *
     * @param   int     $idVar  ID da variação
     * @param   string  $newSku Novo sku
     * @param   string  $newSku ID product ERP
     * @return  bool            Retorna o status da atualizaçao
     */
    public function updateSkuVariationForId($idVar, $newSku, $idProd)
    {
    	/* rick
		$this->_this->db->where('id', $idVar);
        return $this->_this->db->update('prd_variants', array('sku' => $newSku, 'variant_id_erp' => $idProd)) ? true : false;
		*/
		
		return $this->_this->model_products->updateProductVar(array('sku' => $newSku, 'variant_id_erp' => $idProd), $idVar, "Alterado Integração Bling" ); 
       
    }

    /**
     * Recupera se o produto já está integrado com algum maeketplace
     *
     * @param   int         $prd_id Código do produto
     * @return  array|null          Retorna um array se existir se nõa retorna nulo
     */
    public function getPrdIntegration($prd_id)
    {
        return $this->_this->db
            ->from('prd_to_integration')
            ->where('prd_id', $prd_id)
            ->get()
            ->result_array();
    }

    /**
     * Recupera o estoque de apenas um único estoque
     *
     * @param   array $depositos depositos de estoque
     * @param   float $stockReal estoque geral
     * @return  int              estoque
     */
    public function getGeneralStock($depositos, $stockReal)
    {
        if (!$this->_this->generalStock) return (int)$stockReal;

        foreach ($depositos as $deposito) {
            if ($deposito->deposito->nome == $this->_this->generalStock) return (int)$deposito->deposito->saldo;
        }

        return 0;
    }

    /**
     * @param   string      $sku    SKU a ser validado
     * @param   null|int    $prd_id Código do produto a ser ignorado
     * @return  bool
     */
    private function checkSkuAvailable($sku, $prd_id = null)
    {
        $where = '';
        if ($prd_id) $where .= " AND p.id <> {$prd_id}";

        $sql = "SELECT p.id,v.id FROM products as p LEFT JOIN prd_variants as v ON p.id = v.prd_id WHERE p.store_id = ? {$where} AND (p.sku = ? OR v.sku = ?) limit 1";
        $query = $this->_this->db->query($sql, array($this->_this->store, $sku, $sku));
        return $query->row_array() ? false : true;
    }

    /**
     * Remover todos os acentos
     *
     * @param   string  $string Texto para remover os acentos
     * @return  string
     */
    public function removeAccents($string){
        return preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/"),explode(" ","a A e E i I o O u U n N"),$string);
    }
}