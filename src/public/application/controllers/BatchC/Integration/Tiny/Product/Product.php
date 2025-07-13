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
        'und' => 'UN'
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
     * Recupera dados do produto pelo código da Tiny
     *
     * @param   integer     $idTiny Código do produto na tiny
     * @return  null|array          Retorna um array com dados do produto ou null caso não encontre
     */
    public function getProductForIdErp($idTiny, $sku = null)
    {
    	/*
        $query = $this->_this->db->get_where('products',
            array(
                'store_id'       => $this->_this->store,
                'product_id_erp' => $idTiny
            )
        );
		if (!$query->num_rows()) return null;

        $result = $query->row_array();
		
		*/
		$result  = $this->_this->model_products->getByProductIdErpAndStore($idTiny, $this->_this->store);
		
		if (!$result) return null;

        if ($sku && $result['sku'] != $sku) {
            // $this->updateProductForSku($result['sku'], array('product_id_erp' => null));
            $this->_this->model_products->update( array('product_id_erp' => null), $result['id'] , "Alterado Integração Tiny");
 
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
        return $this->_this->db->get_where('products use index (store_sku)',
            array(
                'store_id'  => $this->_this->store,
                'sku'       => $sku
            )
        )->row_array();
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
		
		return $this->_this->model_products->update($data,$prd['id'], "Alterado Integração Tiny");
    }
	
    /**
     * Cria um novo produto
     *
     * @param   object  $payload    Payload produto para cadastro
     * @param   float   $precoProd  Preço diferenciado(lista de preço)
     * @param   bool    $webhook    Status de uso do webhook
     * @return  array               Retorna o status do cadastro ou array de mapemaneto caso seja webhook
     */
    public function createProduct($payload, $precoProd = null, $webhook = false)
    {
        if (isset($payload->tipoVariacao) && $payload->tipoVariacao == "V") {
            echo "Esse produto é uma variação, aqui só entra produto pai ou simples\n";
            return array('success' => false, 'message' => array('Foi tentado criar uma variação no método de criar produtos'));
        }
        if (isset($payload->tipo) && $payload->tipo != "P") {
            echo "Chegou um tipo que não é um produto. Chegou={$payload->tipo}";
            return array('success' => false, 'message' => array('Está tentando criar um tipo que não é um produto, recebemos o seguinte tipo: '.$payload->tipo));
        }

        $descricao = '';
        if (!isset($payload->descricao_complementar) && isset($payload->descricaoComplementar))
            $descricao = $payload->descricaoComplementar;
        
        else if (isset($payload->descricao_complementar) && !isset($payload->descricaoComplementar))
            $descricao = $payload->descricao_complementar;

        // type - S=string, "F"=float, "I"=integer, "A"=array
        $product = array(
            'nome'              => array('value' => $payload->nome, 'required' => true, 'type' => 'S', 'field_database' => 'name'),
            'sku'               => array('value' => $payload->codigo, 'required' => true, 'type' => 'S', 'field_database' => 'sku'),
            'un'                => array('value' => $payload->unidade, 'required' => true, 'type' => 'S', 'field_database' => 'attribute_value_id'),
            'preco'             => array('value' => $precoProd ?? $payload->preco, 'required' => true, 'type' => 'F', 'field_database' => 'price'),
            'ncm'               => array('value' => $payload->ncm, 'required' => true, 'type' => 'S', 'field_database' => 'NCM'),
            'origem'            => array('value' => $payload->origem, 'required' => true, 'type' => 'I', 'field_database' => 'origin'),
            'ean'               => array('value' => $payload->gtin, 'required' => true, 'type' => 'S', 'field_database' => 'EAN'),
            'peso_liquido'      => array('value' => $payload->peso_liquido ?? $payload->pesoLiquido, 'required' => true, 'type' => 'F', 'field_database' => 'peso_liquido'),
            'peso_bruto'        => array('value' => $payload->peso_bruto ?? $payload->pesoBruto, 'required' => true, 'type' => 'F', 'field_database' => 'peso_bruto'),
            'sku_fabricante'    => array('value' => $payload->codigo_pelo_fornecedor ?? $payload->codigoPeloFornecedor, 'required' => true, 'type' => 'S', 'field_database' => 'codigo_do_fabricante'),
            'descricao'         => array('value' => $descricao, 'required' => true, 'type' => 'S', 'field_database' => 'description'),
            'garantia'          => array('value' => $payload->garantia, 'required' => true, 'type' => 'I', 'field_database' => 'garantia'),
            'fabricante'        => array('value' => $payload->marca, 'required' => true, 'type' => 'S', 'field_database' => 'brand_id'),
            'altura'            => array('value' => $payload->alturaEmbalagem, 'required' => true, 'type' => 'F', 'field_database' => 'altura'),
            'comprimento'       => array('value' => $payload->comprimentoEmbalagem, 'required' => true, 'type' => 'F', 'field_database' => 'profundidade'),
            'largura'           => array('value' => $payload->larguraEmbalagem, 'required' => true, 'type' => 'F', 'field_database' => 'largura'),
            'categoria'         => array('value' => $payload->categoria ?? $payload->descricaoArvoreCategoria, 'required' => true, 'type' => 'S', 'field_database' => 'category_imported'),
            'imagesAnexo'       => array('value' => $payload->anexos, 'required' => true, 'type' => 'A', 'field_database' => NULL),
            'imagesExterna'     => array('value' => $payload->imagens_externas ?? array(), 'required' => true, 'type' => 'A', 'field_database' => NULL),
            'variacoes'         => array('value' => $payload->variacoes, 'required' => true, 'type' => 'A', 'field_database' => 'has_variants'),
            'prazo_operacional_extra' => array('value' => $payload->dias_preparacao ?? 0, 'required' => true, 'type' => 'I', 'field_database' => 'prazo_operacional_extra')
        );

        // Validar e formatar campos
        $productFormat = $this->getDataFormat($product, true);

        // Encontrou erro na formatação dos dados
        if (isset($productFormat['success']) && !$productFormat['success']) {
            if ($webhook) {
                $arrMapeamentoTiny = array();
                array_push($arrMapeamentoTiny, array("idMapeamento" => $payload->idMapeamento, "skuMapeamento" => $payload->codigo, "error" => implode(" - ", $productFormat['message'])));

                if (count($product['variacoes']['value'])) {
                    foreach ($product['variacoes']['value'] as $varError)
                        array_push($arrMapeamentoTiny, array("idMapeamento" => $varError->idMapeamento, "skuMapeamento" => $varError->codigo, "error" => implode(" - ", $productFormat['message'])));
                }
                return array('success' => false, 'message' => $productFormat['message'], 'data' => $arrMapeamentoTiny);
            }

            return $productFormat;
        }

        $productFormat['product_id_erp'] = $payload->id;

        if ($webhook) {
            if (count($productFormat['has_variants']['estoque'])) {
                $qtyProduct = array_sum($productFormat['has_variants']['estoque']);
                $qtyVariations = $productFormat['has_variants']['estoque'];
            } else {
                $qtyProduct = $payload->estoqueAtual;
            }
        } else { // obter estoque
            $getQty = $this->getStock(
                isset($productFormat['has_variants']['codigos']) && count($productFormat['has_variants']['codigos']) > 0 ?
                    $productFormat['has_variants']['codigos'] :
                    array($payload->id)
            );

            if (!$getQty['success']) {
                return array('success' => false, 'message' => array($getQty['message']));
            }

            $qtyProduct     = $getQty['totalQty'];
            $qtyVariations  = $getQty['variationQty'];
        }

        // upload de imagens
        $path = $this->getPathNewImage(); // folder que receberá as imagens
        // Faz upload e recupera os códigos
        $images = $this->getImages($productFormat['image'], $path['path_complet']);
        if (!$images['success']) {
            // encontrou erro no upload de imagem
//            echo "erro upload imagem\n";
            return $images;
        }

        //define nome da pasta com as imagens e a imagem principal
        $productFormat['image'] = $path['path_product'];
        $productFormat['principal_image'] = $images['primary'];

        // remove imagens
        //$this->uploadproducts->deleteImgError($arrImages, $path['path_product']);

        $productFormat['qty'] = $qtyProduct;

        // recupera as variações para inserção e define o has_variants com os tipos
        $variationsProduct = $productFormat['has_variants'];
        $productFormat['has_variants'] = $productFormat['has_variants']['tipos'];

        // Inserção produto e pegar id para passar nas variações
        $prd_id = $this->_this->model_products->create($productFormat, "Criado Integração Tiny");

        // bloqueia produto se necessário
        $this->CI->blacklistofwords->updateStatusProductAfterUpdateOrCreate($productFormat, $prd_id);

        // Mapeamento para retorno via webhook
        $arrMapeamentoTiny = array();
        if ($webhook)
            array_push($arrMapeamentoTiny, array("idMapeamento" => $payload->idMapeamento, "skuMapeamento" => $payload->codigo));

        // formatar variações para criação
        $newPrice = 0;
        $newQty = 0;
        $variationsNotList = array();
        foreach ($variationsProduct['variacoes'] as $key => $variacao) {
            $skuVar     = $variacao['sku'];
            $idVar      = $variacao['id'];
            $idMap      = $variacao['idMap'];
            $precoVar   = $variacao['preco'];
            $qtyVar     = $qtyVariations[$variacao['id']];
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

            if ($webhook)
                array_push($arrMapeamentoTiny, array("idMapeamento" => $idMap, "skuMapeamento" => $skuVar));

            // Ler produto da lista para pegar da lista
            if ($this->getUseListPrice() && $this->_this->listPrice) {
                $getPrice = $this->getPriceVariationListPrice($idVar);
                // Ocorreu um problema para recuperar o preço da variação
                if (!$getPrice['success']) {
                    array_push($variationsNotList, $skuVar);
                    continue;
                }

                if ($getPrice['value'] > $newPrice)
                    $newPrice = $getPrice['value'];
            } else {
                if ($newPrice < $precoVar) $newPrice = $precoVar;
            }
            $newQty += $qtyVar;
			
			$image_dir = ''; 
			//$imagesVar = $this->checkImageIsEqualFather($payload->imagem, $variacao['imagem'], $variacao['clonardadospai']=='S'); 
	       	$imagesVar = array($variacao['imagem']); 
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
            $verification_sku = $this->verifyFieldsProduct('sku', $skuVar, true, 'S', true, $prd_id);
            if (!$verification_sku[0]) {
                return array('success' => $verification_sku[0], 'message' => $verification_sku[1]);
            }
            $verification_ean = $this->verifyFieldsProduct('ean', $variacao['ean'], true, 'S', true);
            if (!$verification_ean[0]) {
                return array('success' => $verification_ean[0], 'message' => $verification_ean[1]);
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
        }

        if (count($variationsProduct['variacoes']) > 0 && count($variationsNotList) == count($variationsProduct['variacoes'])) {
            return array('success' => false, 'message' => array('As variações do produto não estão na lista de preço.'));
        }

        // atualiza preço
        if ($newPrice)
            $this->_this->db->where(array('sku' => $productFormat['sku'], 'store_id' => $this->_this->store))->update('products', array('price' => $newPrice, 'qty' => $newQty));

        if ($webhook)
            return array('success' => true, 'data' => $arrMapeamentoTiny, 'variations_not_list' => $variationsNotList);

        return array('success' => true, 'variations_not_list' => $variationsNotList);
    }

    /**
     * Recupera nome das imagens e faz upload para pasta criada
     *
     * @param   array   $arrImages  URLs das imagen
     * @param   string  $type       Tipo de ERP
     * @param   string  $path       Nome da pasta para upload
     * @return  array               Retorno o satus dos uploads e o nome das imagens
     */
    public function getImages($arrImages, $path)
    {
        $arrNameImages = array();
        $primaryImage = null;

        foreach ($arrImages as $images) {
            foreach ($images as $image) {
                $url = "";
                if (isset($image->anexo)) $url = $image->anexo;
                elseif (isset($image->imagem_externa->url)) $url = $image->imagem_externa->url;
                elseif (isset($image->url)) $url = $image->url;
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
        }

        return array('success' => true, 'images' => $arrNameImages, 'primary' => $primaryImage);
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
            if (in_array($key, ['variacoes'])) {
                // Verificações que são diferentes dentro da integração.
                $field_format = $this->verifyFields($key, $field['value'], $field['required'], $field['type'], $newProduct);
            } else {
                $field_format = $this->verifyFieldsProduct($key, $field['value'], $field['required'], $field['type'], $newProduct);
            }

            // encontrou um erro, deve encerrar a criação do produto e apresentar o motivo
            if (!$field_format[0])
                array_push($erros, $field_format[1]);

            if ($key == "imagesAnexo" || $key == "imagesExterna")
                array_push($productFormat['image'], $field_format[1]);
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
     * Recupera o estoque de produto(s)
     *
     * @param   array $products IDs dos produtos para serem consultado estoque
     * @return  array           Retorna um array com o estoque total e estoque separado por ID
     */
    public function getStock($products)
    {
        $qty = 0;
        $arrCodeQty = array();

        foreach ($products as $product) {

            // Consulta endpoint par obter estoque
            $url = "https://api.tiny.com.br/api2/produto.obter.estoque.php";
            $data = "&id={$product}";
            if (function_exists('sendREST'))
                $dataStockProduct = json_decode($this->_this->sendREST($url, $data));
            else
                $dataStockProduct = json_decode($this->_sendREST($url, $data));

            // Ocorreu um problema
            if ($dataStockProduct->retorno->status != "OK") {
                echo "Ocorreu um problema para obter o estoque da produto_id_tiny={$product} retorno=" . json_encode($dataStockProduct) . "\n";
                $this->_this->log_data('batch', 'Product/getStock', "Ocorreu um problema para obter o estoque da produto_id_tiny={$product} retorno=" . json_encode($dataStockProduct), "E");
                return array('success' => false, 'message' => 'Ocorreu um problema para obter o estoque, caso use uma lista de preço verifique se o produto/variação está na lista!');
            }

            $qtyProductReserved = $dataStockProduct->retorno->produto->saldoReservado ?? 0;
            $qtyProduct         = (float)$dataStockProduct->retorno->produto->saldo;

            $qty += ($qtyProduct - (float)$qtyProductReserved);

            $arrCodeQty[$product] = $qtyProduct;

        }

        return array('success' => true, 'totalQty' => $qty, 'variationQty' => $arrCodeQty);
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
        $preco  = $payload->preco ?? 0;

        if (!$this->checkSkuAvailable($payload->codigo, $idProd))
            return array('success' => false, 'message' => "O código SKU ( {$payload->codigo} ) já está em uso!");

        // não poderá mais ser atualizado
        if ($this->getPrdIntegration($idProd))
            return array('success' => false, 'message' => "Produto {$skuPai}, não pode mais receber novas variações pois já está integrado com o marketplace.");

        $tipoVarArr = array();
        $varArr     = array();
        $payload->grade = (array)$payload->grade;
        foreach ($payload->grade as $tipo => $valor) {
            if (is_object($valor)) {
                $tipo = $valor->chave;
                $valor = $valor->valor;
            }
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

        if (count($payload->grade) != count($varArr))
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

        // Existe alguma variação com esses valores
        // atualizar o sku pro sku do erp
        $existVarSkuDiff = $this->getVariantsForIdAndName($idProd, $varStr);
        if ($existVarSkuDiff) {
            $this->updateSkuVariationForId($existVarSkuDiff[0]['id'], $payload->codigo, $payload->id);
            //return array('success' => false, 'message' => "Foi encontrada uma variação de sku diferente, com os mesmos valores de uma variação já existente para esse produto.");
            return array('success' => true);
        }

        // Já existir esse sku, mas com valores diferentes
        if ($this->getVariantsForIdAndSku($idProd, $payload->codigo))
            return array('success' => false, 'message' => "Foi encontrada uma variação com o mesmo sku, mas com valores diferentes para esse produto.");

        $skuVar = $payload->codigo;
        if ($skuVar == "")
            return array('success' => false, 'message' => "O SKU da variação não pode ser em branco.");

        $verification_sku=$this->verifyFieldsProduct('sku',$skuVar,true,'S',true,$idProd);
        if(!$verification_sku[0]){
            return array('success' => $verification_sku[0], 'message' => $verification_sku[1]);
        }
        $verification_ean=$this->verifyFieldsProduct('ean',$payload->gtin,true,'S',true);
        if(!$verification_ean[0]){
            return array('success' => $verification_ean[0], 'message' => $verification_ean[1]);
        }
        // consultar estoque
        $qtyVar = $this->getStock(array($payload->id));
        // Erro na consulta do estoque
        if (!$qtyVar['success'])
            return array('success' => false, 'message' => "Não foi possível obter o estoque da variação.");

        // define o valor do estoque
        $qtyVar = $qtyVar['totalQty'];

        // recuperar todas as variações para definir o 'variant'
        $variant = null;
        foreach ($this->getVariantProduct($idProd) as $varReal) {
            $variantReal = (int)$varReal['variant'];

            if ($variant && $variantReal > $variant) $variant = $variantReal;
            if (!$variant) $variant = $variantReal;
        }
        if ($variant === null) $variant = 0;
        else $variant++;
        
        //rick
        echo " pegando o produto Pai ".$payload->idProdutoPai."\n";
		$payloadPai = $this->getProductTiny($payload->idProdutoPai);
		if ($payloadPai=== false) {
			return array('success' =>false, 'message' => "Não foi possível ler o produto pai de de id {$payload->idProdutoPai}.");
		}
		// Faz upload da images por variação rick
        $image_dir = ''; 
		//$imagesVar = $this->checkImageIsEqualFather($payloadPai->imagem,$payload->imagem); 
		$imagesVar = array(array_merge($payload->anexos, $payload->imagens_externas));
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
        $verification_sku = $this->verifyFieldsProduct('sku', $skuVar, true, 'S', true, $idProd);
        if (!$verification_sku[0]) {
            return array('success' => $verification_sku[0], 'message' => $verification_sku[1]);
        }
        $verification_ean = $this->verifyFieldsProduct('ean', $payload->gtin, true, 'S', true);
        if (!$verification_ean[0]) {
            return array('success' => $verification_ean[0], 'message' => $verification_ean[1]);
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
            )
        );

        if (!$createVar)
            return array('success' => false, 'message' => "Não foi possível inserir a variação.");

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
        $updateProduct = $this->updateProductForSku($skuPai, $dataUpdateProd);

        if (!$updateProduct)
            return array('success' => false, 'message' => "Não foi possível atualizar o estoque do produto pai.");

        return array('success' => true);
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
    public function verifyFields($key, $value, $required, $type, $newProduct)
    {
        $value_ok = array(true, $this->setValueFormat($value, $type));

        if ($key === "variacoes") {
            $varArr     = array();
            $tipoVarArr = array();
            $varCodes   = array();
            $stockReal  = array();
            $value = $value == "" ? array() : $value;

            foreach ($value as $keyVar => $type_v) {
                $id         = $type_v->variacao->id ?? $type_v->id;
                $sku        = $type_v->variacao->codigo ?? $type_v->codigo;
                $preco      = $type_v->variacao->preco ?? $type_v->preco;
                $variacoes  = $type_v->variacao->grade ?? $type_v->grade;

				//rick
				echo "Lendo a variacao ".$id."\n";
				$payloadVar = $this->getProductTiny($id);
				if ($payloadVar=== false) {
					return array(false, "Não foi possível ler o produto de id {$id}.");
				}

                if ($sku == '')
                    return array(false, "Todas as variações precisam ter o código SKU preenchidos .");

                if (isset($type_v->estoqueAtual))
                    $stockReal[$id] = $type_v->estoqueAtual;

                // define o sku e id da variação
                $varArr[$keyVar]['sku']     = $sku;
                $varArr[$keyVar]['id']      = $id;
                $varArr[$keyVar]['preco']   = $preco;
                $varArr[$keyVar]['idMap']   = $type_v->idMapeamento ?? 0;
                $varArr[$keyVar]['variacao']= array();
				
				//rick
				$varArr[$keyVar]['imagem']	= array_merge($payloadVar->anexos, $payloadVar->imagens_externas);  //rick
				$varArr[$keyVar]['ean']		= $payloadVar->gtin; //rick 

                array_push($varCodes, $id);

                $variacoes = (array)$variacoes;

                foreach ($variacoes as $tipo => $valor) {
                    if (is_object($valor)) {
                        $tipo = $valor->chave;
                        $valor = $valor->valor;
                    }
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
                    'estoque'   => $stockReal
                )
            );

        }

        return $value_ok;
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
        return $this->_this->db->get_where('prd_variants',
            array(
                'prd_id' => $prd_id
            )
        )->result_array();
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
        )->result_array();
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
     * Atualiza estoque via webhook do produto ou variação
     *
     * @param   object  $payload    Dados do produto
     * @return  bool                Retorna o status da atualização
     */
    public function updateStock($payload)
    {
        $qty                = $payload->saldo;
        $idProduto          = $payload->idProduto; // id produto tiny
        $sku                = $payload->sku; // geralmente é igual ao $skuMapeamento
        $skuMapeamento      = $payload->skuMapeamento;
        $skuMapeamentoPai   = $payload->skuMapeamentoPai;

        $tipoVariacao = $skuMapeamentoPai == "" ? "N" : "V";

        if ($tipoVariacao == "N")
            if (!$this->getProductForSku($skuMapeamento)) return false;
        else if ($tipoVariacao == "V")
            if (!$this->getVariationForSkuAndSkuVar($skuMapeamentoPai, $skuMapeamento)) return false;
        else return false;

        if ($tipoVariacao == "N")
            return $this->updateStockProduct($skuMapeamento, $qty, $idProduto);

        return $this->updateStockVariation($skuMapeamento, $skuMapeamentoPai, $qty, $idProduto);
    }

    /**
     * Atualiza o estoque do produto
     *
     * @param   string  $sku        SKU do produto
     * @param   float   $qty        Novo saldo do estoque do produto
     * @param   int     $idProduto  ID do produto
     * @param   bool    $verify     Verifica lista, caso venha da atualização da variação, não terá como verificar
     * @return  bool                Retorna o status da atualização
     */
    public function updateStockProduct($sku, $qty, $idProduto, $verify = true)
    {
        if ($this->getUseListPrice() && $verify && $this->_this->listPrice){
            // Ler produto da lista para pegar da lista
            $getPrice = $this->getPriceVariationListPrice($idProduto);

            // Ocorreu um problema para recuperar o preço da variação
            if (!$getPrice['success'])
                return null;
        }
		/* rick 
        $this->_this->db->where(
            array(
                'sku'       => $sku,
                'store_id'  => $this->_this->store,
            )
        );
        return $this->_this->db->update('products', array('qty' => $qty)) ? true : false;
		*/
    	$prd = $this->_this->model_products->getProductCompleteBySkyAndStore($sku, $this->_this->store); 
		
		return $this->_this->model_products->update(array('qty' => $qty),$prd['id'], "Alterado Integração Tiny");
    }

    /**
     * Atualiza o estoque da variação
     *
     * @param   string  $sku        SKU da variação
     * @param   string  $skuPai     SKU do produto
     * @param   float   $qty        Novo saldo do estoque da variação
     * @param   int     $idProduto  ID do produto
     * @return  bool                Retorna o status da atualização
     */
    public function updateStockVariation($sku, $skuPai, $qty, $idProduto)
    {
        $product = $this->getProductForSku($skuPai);
        if (!$product) return false;

        if ($this->getUseListPrice() && $this->_this->listPrice){
            // Ler produto da lista para pegar da lista
            $getPrice = $this->getPriceVariationListPrice($idProduto);

            // Ocorreu um problema para recuperar o preço da variação
            if (!$getPrice['success'])
                return null;
        }

        $variations = $this->_this->db->get_where('prd_variants', array('prd_id' => $product['id']))->result_array();

        // Atualiza o estoque da variação
        // rick $this->_this->db->where(array('prd_id' => $product['id'], 'sku' => $sku))->update('prd_variants', array('qty' => $qty));

        $newQty = 0;
        foreach ($variations as $variation) {

            if ($variation['sku'] == $sku) {
            	$variation['qty'] = $qty; // define a nova quantidade
				$this->_this->model_products->updateProductVar(array('qty' => $qty), $variation['id'],  "Alterado Integração Tiny" );
			}
            $newQty += (float)$variation['qty'];
        }

        return $this->updateStockProduct($skuPai, $newQty, $idProduto, false) ? true : false;
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
        // rick return $this->_this->db->where(array('sku' => $sku, 'store_id' => $this->_this->store))->update('products', array('price' => $price)) ? true : false;
    	$prd = $this->_this->model_products->getProductCompleteBySkyAndStore($sku, $this->_this->store); 
		
		return $this->_this->model_products->update(array('price' => $price),$prd['id'], "Alterado Integração Tiny");
	}

    /**
     * Recupera o preço do produto na lista de preço
     *
     * @param   int     $idProduct  Código do produto
     * @return  array               Retorna um array com o status da consulta e valor
     */
    public function getPriceVariationListPrice($idProduct)
    {
        // Consulta endpoint par obter estoque
        $url = "https://api.tiny.com.br/api2/listas.precos.excecoes.php";
        $data = "&idListaPreco={$this->_this->listPrice}&idProduto={$idProduct}";

        if (function_exists('sendREST'))
            $dataPrice = json_decode($this->_this->sendREST($url, $data));
        else
            $dataPrice = json_decode($this->_sendREST($url, $data));

        // Ocorreu um problema
        if ($dataPrice->retorno->status != "OK") {
            if ($dataPrice->retorno->codigo_erro != 20) {
                echo "Ocorreu um problema para obter o preço da produto_id_tiny={$idProduct} retorno=" . json_encode($dataPrice) . "\n";
                $this->_this->log_data('batch', 'Product/getStock', "Ocorreu um problema para obter o estoque da produto_id_tiny={$idProduct} retorno=" . json_encode($dataPrice), "E");
            }
            return array('success' => false);
        }

        return array('success' => true, 'value' => (float)$dataPrice->retorno->registros[0]->registro->preco);
    }

    /**
     * Verifica se a API foi bloqueada por limite de requisição
     *
     * @param   string      $url                URL requisição
     * @param   null|string $data               Dados para envio no body
     * @param   null|array  $optional_headers   HEADER request
     * @param   string      $response           Resposta da requisição atual
     * @return  string                          Retorno da requisição
     */
    public function _apiBlockSleep($url, $data, $optional_headers, $response)
    {
        // Converte caso não chegue em xml
        if ($this->_this->formatReturn == 'json')
            $responseDecode = json_decode($response);

        // enquanto a api estiver bloqueada ficará tentando até encontrar o resultado
        if ($this->_this->formatReturn == 'json') {
            while ($responseDecode->retorno->status == "Erro" && isset($responseDecode->retorno->codigo_erro) && $responseDecode->retorno->codigo_erro == 6) {
                echo "API Bloqueada, vou esperar 15s e tentar novamente...\n";
                sleep(15); // espera 15 segundos
                $response = $this->_sendREST($url, $data); // enviar uma nova requisição para ver se já liberou
                $responseDecode = json_decode($response);
            }
        } elseif ($this->_this->formatReturn == 'xml') {

            // Converte caso chegue em xml
            $responseArr = $this->_convertXmlArray($response);

            while ($responseArr['status'] == "Erro" && isset($responseArr['codigo_erro']) && $responseArr['codigo_erro'] == 6) {
                echo "API Bloqueada, vou esperar 15s e tentar novamente...\n";
                sleep(15); // espera 15 segundos
                $response = $this->_sendREST($url, $data); // enviar uma nova requisição para ver se já liberou

                // Converte caso chegue em xml
                $responseArr = $this->_convertXmlArray($response);
            }
        }

        return $response;
    }

    /**
     * Converter um XML em array
     *
     * @param   string $xml String de uma xml
     * @return  array       Retorna um array convertido do xml
     */
    public function _convertXmlArray($xml)
    {
        $responseXml = simplexml_load_string($xml);
        $jsonEncode  = json_encode($responseXml);

        return json_decode($jsonEncode,TRUE);
    }

    /**
     * Request API
     *
     * @param string        $url                URL requisição
     * @param null|string   $data               Dados para envio no body
     * @param null|array    $optional_headers   HEADER request
     * @return mixed
     * @throws Exception
     */
    public function _sendREST($url, $data, $optional_headers = null)
    {
        $params = array('http' => array(
            'method' => 'POST',
            'content' => "token={$this->_this->token}&formato={$this->_this->formatReturn}{$data}"
        ));

        if ($optional_headers !== null) {
            $params['http']['header'] = $optional_headers;
        }

        $ctx = stream_context_create($params);
        $fp = @fopen($url, 'rb', false, $ctx);
        if (!$fp)
            return '{"retorno":{"status_processamento":1,"status":"Erro","codigo_erro":"99","erros":[{"erro":"Nao foi possivel acessar a URL(fopen): '.$url.' "}]}}';

        $response = @stream_get_contents($fp);
        if ($response === false)
            return '{"retorno":{"status_processamento":1,"status":"Erro","codigo_erro":"99","erros":[{"erro":"Nao foi possivel acessar a URL(stream_get_contents): '.$url.' "}]}}';

        $response = $this->_apiBlockSleep($url, $data, $optional_headers, $response);

        return $response;
    }

    /**
     * Atualização de produtos
     *
     * @param   object  $payload    Payload produto para atualização
     * @return  array               Retorna o status da atualização
     */
    public function updateProduct($payload)
    {
        if (isset($payload->tipoVariacao) && $payload->tipoVariacao == "V") {
            echo "Esse produto é uma variação, aqui só entra produto pai ou simples\n";
            return array('success' => false, 'message' => array('Foi tentado criar uma variação no método de criar produtos'));
        }
        if (isset($payload->tipo) && $payload->tipo != "P") {
            echo "Chegou um tipo que não é um produto. Chegou={$payload->tipo}\n";
            return array('success' => false, 'message' => array('Está tentando criar um tipo que não é um produto, recebemos o seguinte tipo: '.$payload->tipo));
        }

        // type - S=string, "F"=float, "I"=integer, "A"=array
        $product = array(
            'nome'              => array('value' => $payload->nome, 'required' => true, 'type' => 'S', 'field_database' => 'name'),
//            'sku'               => array('value' => $payload->codigo, 'required' => true, 'type' => 'S', 'field_database' => 'sku'),
            'un'                => array('value' => $payload->unidade, 'required' => true, 'type' => 'S', 'field_database' => 'attribute_value_id'),
//            'preco'             => array('value' => $precoProd ?? $payload->preco, 'required' => true, 'type' => 'F', 'field_database' => 'price'),
            'ncm'               => array('value' => $payload->ncm, 'required' => true, 'type' => 'S', 'field_database' => 'NCM'),
            'origem'            => array('value' => $payload->origem, 'required' => true, 'type' => 'I', 'field_database' => 'origin'),
            'ean'               => array('value' => $payload->gtin, 'required' => true, 'type' => 'S', 'field_database' => 'EAN'),
            'peso_liquido'      => array('value' => $payload->peso_liquido, 'required' => true, 'type' => 'F', 'field_database' => 'peso_liquido'),
            'peso_bruto'        => array('value' => $payload->peso_bruto, 'required' => true, 'type' => 'F', 'field_database' => 'peso_bruto'),
            'sku_fabricante'    => array('value' => $payload->codigo_pelo_fornecedor, 'required' => true, 'type' => 'S', 'field_database' => 'codigo_do_fabricante'),
            'descricao'         => array('value' => $payload->descricao_complementar, 'required' => true, 'type' => 'S', 'field_database' => 'description'),
            'garantia'          => array('value' => $payload->garantia, 'required' => true, 'type' => 'I', 'field_database' => 'garantia'),
            'fabricante'        => array('value' => $payload->marca, 'required' => true, 'type' => 'S', 'field_database' => 'brand_id'),
            'altura'            => array('value' => $payload->alturaEmbalagem, 'required' => true, 'type' => 'F', 'field_database' => 'altura'),
            'comprimento'       => array('value' => $payload->comprimentoEmbalagem, 'required' => true, 'type' => 'F', 'field_database' => 'profundidade'),
            'largura'           => array('value' => $payload->larguraEmbalagem, 'required' => true, 'type' => 'F', 'field_database' => 'largura'),
            'categoria'         => array('value' => $payload->categoria, 'required' => true, 'type' => 'S', 'field_database' => 'category_imported'),
            'imagesAnexo'       => array('value' => $payload->anexos, 'required' => true, 'type' => 'A', 'field_database' => NULL),
            'imagesExterna'     => array('value' => $payload->imagens_externas ?? array(), 'required' => true, 'type' => 'A', 'field_database' => NULL),
            'variacoes'         => array('value' => $payload->variacoes, 'required' => true, 'type' => 'A', 'field_database' => 'has_variants'),
            'prazo_operacional_extra' => array('value' => $payload->dias_preparacao ?? 0, 'required' => true, 'type' => 'I', 'field_database' => 'prazo_operacional_extra')
        );

        // Validar e formatar campos
        $productFormat = $this->getDataFormat($product);

        // Encontrou erro na formatação dos dados
        if (isset($productFormat['success']) && !$productFormat['success'])
            return $productFormat;

        // recupera dados do produto original
        $dataProduct = $this->getProductForSku($payload->codigo);

        $productFormat['product_id_erp'] = $payload->id;

        // verificar se o produto já foi integrado
        if (!$this->getPrdIntegration($dataProduct['id']))
            $productFormat['has_variants'] = $productFormat['has_variants']['tipos'];
//        else unset($productFormat['has_variants']);
        else
            return array('success' => null, 'errorsVar' => array(), 'successVar' => array());

        if (!isset($productFormat['status']) && $dataProduct['status'] == 4) $productFormat['status'] = 1;

        $imagesERP = $productFormat['image'];

        // remove os itens que não derão ser atualizados
        foreach ($this->naoAtualizar as $field)
            unset($productFormat[$field]);

        // Faz upload e recupera os códigos
        if ((count($imagesERP[0]) || count($imagesERP[1])) && !$this->_this->uploadproducts->countImagesDir($dataProduct['image'])) {
            $path = $this->getPathNewImage($dataProduct['image']);
            $images = $this->getImages($imagesERP, $path['path_complet']);

            if (!$images['success']) {
                // encontrou erro no upload de imagem
                echo "erro upload imagem\n";
                return $images;
            }
            $productFormat['principal_image'] = $images['primary'];

            if (
                ($dataProduct['category_id'] != '[""]') &&
                ($dataProduct['brand_id'] != '[""]' || $productFormat['brand_id'] != '[""]') &&
                $this->_this->uploadproducts->countImagesDir($dataProduct['image'])
            )
                $productFormat['situacao'] = 2;
            else
                $productFormat['situacao'] = 1;
        }

        if ($this->getProductForAttributes($productFormat))
            return array('success' => null);

        // Inserção produto e pegar id para passar nas variações
		/* rick 
        $sqlProd = $this->_this->db->update_string('products', $productFormat, array('sku' => $payload->codigo, 'store_id' => $this->_this->store));
        $this->_this->db->query($sqlProd);
		*/
		$this->_this->model_products->update($productFormat, $dataProduct['id'] , "Alterado Integração Tiny" );
		
        // bloqueia produto se necessário
        $dataProductUpdated = $this->getProductForSku($payload->codigo);
        if ($dataProductUpdated)
            $this->CI->blacklistofwords->updateStatusProductAfterUpdateOrCreate($dataProductUpdated, $dataProduct['id']);

        return array('success' => true);
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
    public function updateVariation($payload, $skuPai)
    {
        // Pegar tipos de variações
        //$skuPai = str_replace("/", "-", $skuPai);
        $dataProduct = $this->getProductForSku($skuPai);
        $typesVariations = $dataProduct['has_variants'] == "" ? array() : explode(";", $dataProduct['has_variants']);
        $idProd = $dataProduct['id'];

        // não podeerá mais ser atualizado
        if ($this->getPrdIntegration($idProd))
            return array('success' => null , 'message' => 'Produto já integrado, não pode mais sofrer alteração');

        $tipoVarArr = array();
        $varArr     = array();
        $payload->grade = (array)$payload->grade;
        foreach ($payload->grade as $tipo => $valor) {
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

        if (count($payload->grade) != count($varArr))
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

        $skuVar = $payload->codigo;
        if ($skuVar == "")
            return array('success' => false, 'message' => "Não foi possível atualizar a variação, não foi encontrado o código SKU da variação.");
			
		// Inserir variação
        /* rick
        $sqlVar = $this->_this->db->update_string('prd_variants', array('name' => $varStr), array('prd_id' => $idProd, 'sku' => $skuVar));
        $updateVar = $this->_this->db->query($sqlVar); // status da variação atualizada
		*/
		$dataVar = $this->getVariantsForIdAndSku($idProd, $skuVar);
        // não teve alteração
        if (count($dataVar) && $dataVar[0]['name'] == $varStr)
            return array('success' => null, 'message' => 'Não teve alteração');
        $updateVar = $this->_this->model_products->updateProductVar(array('name' => $varStr), $dataVar[0]['id'], "Alterado Integração Tiny" );
		
        if (!$updateVar)
            return array('success' => false, 'message' => "Não foi possível atualizar a variação.");

        return array('success' => true);
    }

    /**
     * Recupera o estoque no banco de dados de um produto ou variação
     *
     * @param   string      $sku    SKU do produto PAI
     * @param   null|string $skuVar SKU do produto PAI
     * @return  false|int           Retorna o estoque do produto/variação ou false em falha
     */
    public function getStockForSku($sku, $skuVar = null)
    {
        $query = $this->_this->db->get_where('products use index (store_sku)', array('store_id' => $this->_this->store, 'sku' => $sku));
        if ($query->num_rows() == 0) return false;
        $result = $query->row_array();

        if (!$skuVar) {
            return $result['qty'];
        }

        $query = $this->_this->db->get_where('prd_variants', array('prd_id' => $result['id'], 'sku' => $skuVar));
        if ($query->num_rows() == 0) return false;

        $result = $query->row_array();

        return $result['qty'];
    }

    /**
     * Recupera estado da loja se usa lista de preço
     *
     * @return bool Retorna se a loja usa lista de preço
     */
    public function getUseListPrice()
    {
        $query = $this->_this->db->get_where('api_integrations', array('store_id' => $this->_this->store))->row_array();

        $credentials = json_decode($query['credentials']);

        $list = $credentials->lista_tiny;

        if ($list == "" || !$list) return false;

        return true;
    }

    /**
     * Recupera dados da variação pelo código da Tiny
     *
     * @param   integer     $idTiny     Código da variação na tiny
     * @return  null|array              Retorna um array com dados da variação ou null caso não encontre
     */
    public function getVariationForIdErp($idTiny)
    {
        return $this->_this->db
            ->select('prd_variants.*')
            ->from('prd_variants')
            ->join('products', 'products.id = prd_variants.prd_id')
            ->where(
                array(
                    'prd_variants.variant_id_erp' => $idTiny,
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
		return $this->_this->model_products->updateVarBySku($data, $resultProd['id'], $skuVar, 'Alterando Variação Integração Tiny');
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
    	
        if (!$idVar || !$newSku) return false;
        /* rick 
        $this->_this->db->where('id', $idVar);
        return $this->_this->db->update('prd_variants', array('sku' => $newSku, 'variant_id_erp' => $idProd)) ? true : false;
    	*/
		return $this->_this->model_products->updateProductVar(array('sku' => $newSku, 'variant_id_erp' => $idProd), $idVar, 'Alterado Variação Tiny');
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
	
	function getProductTiny($id_produto) {
		$url    = "https://api.tiny.com.br/api2/produto.obter.php";
        $data   = "&id={$id_produto}";

        if (function_exists('sendREST')) {
            $dataProduct = json_decode($this->_this->sendREST($url, $data));
        } else {
            $dataProduct = json_decode($this->_sendREST($url, $data));
        }

        //$log_name = $this->_this->typeIntegration . '/' . $this->_this->router->fetch_class() . '/' . __FUNCTION__;

        if ($dataProduct->retorno->status != "OK") {
            echo "Produto tiny com ID={$id_produto} encontrou um erro. retorno=" . json_encode($dataProduct) . "\n";
            //$this->db->trans_rollback();
            if ($dataProduct->retorno->codigo_erro != 99) {
                //$this->_this->log_data('batch', $log_name, "Produto tiny com ID={$id_produto} encontrou um erro, dados_item_lista=" . json_encode($id_produto) . " retorno=" . json_encode($dataProduct), "W");
                $this->_this->log_integration("Erro para integrar produto - ID Tiny {$id_produto}", "Não foi possível obter informações do produto! <br> <strong>ID Tiny</strong>:{$id_produto}", "E");
            }
            return false; 
        }

        return $dataProduct->retorno->produto; // Dados produto/variação
	}
	
	
}