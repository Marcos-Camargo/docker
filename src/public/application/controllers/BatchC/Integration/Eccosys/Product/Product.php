<?php
require APPPATH . "libraries/Traits/VerifyFieldsProduct.trait.php";
class Product
{
    use VerifyFieldsProduct;
    public $allowable_tags = null;

    // tipo de variação para comparar, INFORMAR EM MINÚSCULO A CHAVE
    private $tipoVariacoes = array(
        'tamanho'   => 'TAMANHO',
        'Tamanho'   => 'TAMANHO',
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

        'Cor'       => 'Cor',
        'cor'       => 'Cor',
        'color'     => 'Cor',

        'Voltagem'  => 'VOLTAGEM',
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
        'un' => 'UN'
    );

    private $naoAtualizar = array(
        'situacao',
        'status',
        'image',
        'store_id',
        'company_id',
        'category_id',
        'has_variants',
        'brand_id',
        'attribute_value_id'
    );

    private $CI;

    // Passagem de dados
    private $_this;

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

    public function getProductForIdErp($idEccosys, $sku = null)
    {
        $query = $this->_this->db->get_where('products use index (store_iderp)',
            array(
                'store_id'       => $this->_this->store,
                'product_id_erp' => $idEccosys
            )
        );

        if ($query->num_rows() == 0) return null;

        $result = $query->row_array();

        if ($sku && $result['sku'] != $sku) {
            $this->updateProductForSku($result['sku'], array('product_id_erp' => null));
            return null;
        }

        return $result;
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
		 * */
		$prd = $this->_this->model_products->getProductCompleteBySkyAndStore($sku, $this->_this->store); 
		
		return $this->_this->model_products->update($data,$prd['id'], "Alterado Integração Eccosys");
    }

    public function getUrlEccosys($store_id)
    {
        $dataIntegrationStore = $this->_this->db
                                ->from('api_integrations')
                                ->where('store_id', $store_id)
                                ->get()
                                ->result_array();

        if($dataIntegrationStore){
            $credentials = json_decode($dataIntegrationStore[0]['credentials']);
            return $credentials->url_eccosys;
        }
        return null;
    }

    /**
     * Recupera dados do produto pelo SKU do produto
     *
     * @param   string      $sku    SKU do produto
     * @return  null|array          Retorna um array com dados do produto ou null caso não encontre
     */
    public function getProductForSku($sku)
    {
		/* rick 
        return $this->_this->db->get_where('products',
            array(
                'store_id'  => $this->_this->store,
                'sku'       => $sku
            )
        )->row_array();
		*/
		return $this->_this->model_products->getProductCompleteBySkyAndStore($sku, $this->_this->store); 
    }

    /**
     * Recupera dados do produto pelo SKU do produto
     *
     * @param   string      $sku    SKU do produto
     * @return  null|array          Retorna um array com dados do produto ou null caso não encontre
     */
    public function getVariationForSku($sku)
    {
        return $this->_this->db
            ->select('prd_variants.*')
            ->from('products')
            ->join('prd_variants', 'products.id = prd_variants.prd_id')
            ->where(
                array(                    
                    'prd_variants.sku' => $sku,
                    'products.store_id'  => $this->_this->store,
                )
            )
            ->get()
            ->row_array();
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
     * Recupera nome das imagens e faz upload para pasta criada
     *
     * @param   array   $arrImages  URLs das imagen
     * @param   string  $path       Nome da pasta para upload
     * @return  array               Retorno o status dos uploads e o nome das imagens
     */
    public function getImages($arrImages, $path)
    {
        $arrNameImages = array();
        $primaryImage = null;
        $iCount = 0;

        if(is_array($arrImages)){
            foreach ($arrImages as $image) {
                if($iCount < 6){
                    $url = $image;

                    // não encontrou imagem para upload
                    if (empty($url))
                        return array('success' => false, 'message' => array('Imagem invalida, não foi possível recuperar a imagem, chegou em branco!'));

                    $upload = $this->_this->uploadproducts->sendImageForUrl("{$path}/", $url);
                                      
                    if ($upload['success']) {
                        if ($primaryImage === null){ 
                            $primaryImage = base_url("{$path}/{$upload['path']}");
                        }

                        array_push($arrNameImages, $upload['path']);
                        $iCount++;
                    }
                    else // não conseguiu fazer upload da imagem
                    {
                        $this->_this->log_data('batch', 'Product/getImages', "Ocorreu um problema para cadastrar a imagem {$image} retorno=" . json_encode($upload), "E");
                    }
                }
            }
        }

        return array('success' => true, 'images' => $arrNameImages, 'primary' => $primaryImage);
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
     * Recupera os dados formatado do produto
     *
     * @param   array   $product        Produto para ser formatado
     * @param   bool    $newProduct     Validação de novo produto
     * @return  array                   Retorna o produto com a formatação para ser inserido
     */
    public function getDataFormat($product, $newProduct = false)
    {
        // cria array com valores pré-definidos
        $productFormat  = array(
            'image'                 => array(),
            'situacao'              => 1, // incompleto pois nunca irá categoria
            'status'                => 1, // ativo default
            'store_id'              => $this->_this->store,
            'company_id'            => $this->_this->company,
            'category_id'           => '[""]',
            'brand_id'              => '[""]',
            'garantia'              => 0
        );

        $erros = array();

        foreach ($product as $key => $field) {
            if (in_array($key, ['categoria', 'unidade', 'fabricante', 'variacoes', 'garantia', 'comprimento', 'largura', 'altura'])) {
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


    public function getStock($products)
    {
        $qty = 0;
        $arrCodeQty = array();

        foreach ($products as $product) {
            // Consulta endpoint par obter estoque
            $ECCOSYS_URL = $this->getUrlEccosys($this->_this->store);
            $url = $ECCOSYS_URL.'/api/produtos/'.$product;
            $data = "{$product}";


            $dataStockProduct = json_decode(json_encode($this->_this->sendREST($url, $data)));

            // Ocorreu um problema
            if ($dataStockProduct->httpcode != 200) {
                echo "Ocorreu um problema para obter o estoque do produto_id={$product} retorno=" . json_encode($dataStockProduct) . "\n";
                $this->_this->log_data('batch', 'Product/getStock', "Ocorreu um problema para obter o estoque do produto_id_eccosys={$product} retorno=" . json_encode($dataStockProduct), "E");
                return array('success' => false, 'message' => 'Ocorreu um problema para obter o estoque, caso use uma lista de preço verifique se o produto/variação está na lista!');
            }

            $prodRes = json_decode($dataStockProduct->content);

            //$qtyProductReserved = $dataStockProduct->retorno->produto->saldoReservado ?? 0;
            $qtyProduct = $prodRes->_Estoque->estoqueDisponivel ?? 0;

            $qty += ($qtyProduct);

            $arrCodeQty[$product] = $qtyProduct;

        }

        return array('success' => true, 'totalQty' => $qty, 'variationQty' => $arrCodeQty);
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


     /* Cria um novo produto
     *
     * @param   object  $payload    Payload produto para cadastro
     * @param   float   $precoProd  Preço diferenciado(lista de preço)
     * @param   bool    $webhook    Status de uso do webhook
     * @return  array               Retorna o status do cadastro ou array de mapemaneto caso seja webhook
     */

    public function createProduct($payload, $precoProd = null, $webhook = false)
    {
        if (isset($payload->codigoPai)) {
            echo "Esse produto é uma variação, aqui só entra produto pai ou simples";
            return array('success' => false, 'message' => array('Essa variação está tentando cadastrar como um produtos pai, verifique se o sku da variação é diferente do sku do produto pai, para corrigo-lo'));
        }

        if (isset($payload->tipo) && $payload->tipo != "P") {
            echo "Chegou um tipo que não é um produto. Chegou={$payload->tipo}";
            return array('success' => false, 'message' => array('Está tentando criar um tipo que não é um produto, recebemos o seguinte tipo: '.$payload->tipo));
        }

        if(!isset($payload->garantia)){
            $payload->garantia = 0;
        }

        if(!isset($payload->codigoNoFabricante)){
            $payload->codigoNoFabricante = '';
        }

        // type - S=string, "F"=float, "I"=integer, "A"=array
        $product = array(
            'nome'              => array('value' => $payload->nome, 'required' => true, 'type' => 'S', 'field_database' => 'name'),
            'sku'               => array('value' => $payload->codigo, 'required' => true, 'type' => 'S', 'field_database' => 'sku'),
            'unidade'           => array('value' => $payload->unidade, 'required' => true, 'type' => 'S', 'field_database' => 'attribute_value_id'),
            'preco'             => array('value' => $precoProd ?? $payload->preco, 'required' => true, 'type' => 'F', 'field_database' => 'price'),
            'cest'              => array('value' => $payload->cest, 'required' => true, 'type' => 'S', 'field_database' => 'CEST'),
            'ncm'               => array('value' => $payload->cf, 'required' => true, 'type' => 'S', 'field_database' => 'NCM'),
            'origem'            => array('value' => $payload->origem, 'required' => true, 'type' => 'I', 'field_database' => 'origin'),
            'ean'               => array('value' => $payload->gtin, 'required' => true, 'type' => 'S', 'field_database' => 'EAN'),
            'peso_liquido'      => array('value' => $payload->pesoLiq, 'required' => true, 'type' => 'F', 'field_database' => 'peso_liquido'),
            'peso_bruto'        => array('value' => $payload->pesoBruto, 'required' => true, 'type' => 'F', 'field_database' => 'peso_bruto'),
            'sku_fabricante'    => array('value' => $payload->codigoNoFabricante, 'required' => true, 'type' => 'S', 'field_database' => 'codigo_do_fabricante'),
            'descricao'         => array('value' => $payload->_FichaTecnica[0]->descricaoDetalhada ?? $payload->descricaoEcommerce, 'required' => false, 'type' => 'S', 'field_database' => 'description'),
            'garantia'          => array('value' => $payload->garantia, 'required' => true, 'type' => 'I', 'field_database' => 'garantia'),
            'fabricante'        => array('value' => $payload->_Marca ?? '', 'required' => true, 'type' => 'O', 'field_database' => 'brand_id'),
            'altura'            => array('value' => $payload->altura, 'required' => true, 'type' => 'F', 'field_database' => 'altura'),
            'comprimento'       => array('value' => $payload->comprimento, 'required' => true, 'type' => 'F', 'field_database' => 'profundidade'),
            'largura'           => array('value' => $payload->largura, 'required' => true, 'type' => 'F', 'field_database' => 'largura'),

            // Ajustes Categorias p.category_id, p.category_imported
            'categoria'         => array('value' => $payload->_Departamentos ?? "", 'required' => true, 'type' => 'A', 'field_database' => 'category_imported'),
            'imagesAnexo'       => array('value' => $payload->anexos, 'required' => true, 'type' => 'A', 'field_database' => null),
            'variacoes'         => array('value' => $payload , 'required' => true, 'type' => 'A', 'field_database' => 'has_variants'),
            'prazo_operacional_extra' => array('value' => $payload->tempoProducao, 'required' => true, 'type' => 'I', 'field_database' => 'prazo_operacional_extra')
        );

        // Validar e formatar campos
        $productFormat = $this->getDataFormat($product, true);

        // Encontrou erro na formatação dos dados
        if (isset($productFormat['success']) && !$productFormat['success']) {
             // Encontrou erro na formatação dos dados
            echo "Erro na formatação do produto\n"; 
            return $productFormat;
        }

        $productFormat['product_id_erp'] = $payload->id;

        if (isset($productFormat['image'][0]) && count($productFormat['image'][0]) == 1 && $productFormat['image'][0][0] == "")
            $productFormat['image'] = array([]);

        // upload de imagens
        $path = $this->getPathNewImage(); // folder que receberá as imagens

        if (isset($payload->anexos)) {
            // Faz upload e recupera os códigos
            $images = $this->getImages($payload->anexos, $path['path_complet']);
            if (!$images['success']) {
                // encontrou erro no upload de imagem
                echo "erro upload imagem\n";
                //return $images;
            }
        }

        $productFormat['image'] = $path['path_product'] ?? '';
        //define nome da pasta com as imagens e a imagem principal

        if(isset($images['primary'])){
            $productFormat['principal_image'] = $images['primary'] ?? null;
        }
        
        $productFormat['qty'] = $payload->_Estoque->estoqueDisponivel ? $payload->_Estoque->estoqueDisponivel : 0;
        $productFormat['store_id'] = $this->_this->store;

		 // recupera as variações para inserção e define o has_variants com os tipos
        $variationsProduct = $productFormat['has_variants'];
		//var_dump($variationsProduct);
        $productFormat['has_variants'] = $productFormat['has_variants']['has_variants'];

        // Inserção produto e pegar id para passar nas variações
        $prd_id = $this->_this->model_products->create($productFormat, "Criado Integração Eccosys");

        // bloqueia produto se necessário
        $this->CI->blacklistofwords->updateStatusProductAfterUpdateOrCreate($productFormat, $prd_id);


		if ($variationsProduct['has_variants'] != '') {
		
			$variacoes = $variationsProduct['variacoes'];
			$newPrice = null; 
			$produtoPaiNovoEstoque=0;
            foreach ($variacoes as $keyvar => $variacao) {
                  
                $verifyProduct = $this->getVariationForSku($variacao['sku']);
                if (empty($verifyProduct)) {   // nunca cadastrei esta variação                    

					$imagesVar = $variacao['imagem'];
				    echo " variação ".$variacao['variacao']." variant ".$keyvar." com ".count($imagesVar)." imagens\n";
					$image_dir='';
			        if (count($imagesVar)) {
			        	$image_dir= $keyvar; 
			        	$path = $this->getPathNewImage($productFormat['image']."/".$image_dir);
			            $images = $this->getImages($imagesVar, $path['path_complet']);
			            if (!$images['success']) {
			                // encontrou erro no upload de imagem
			                echo "erro upload imagem\n";
			                return $images;
						}
					}
                    $this->_this->model_products->createvar(
                        array(
	                        'prd_id'                => $prd_id,
	                        'variant'               => $keyvar,
	                        'name'                  => $variacao['variacao'],
	                        'sku'                   => $variacao['sku'],
	                        'qty'                   => $variacao['qty'],
	                        'variant_id_erp'        => $variacao['variant_id_erp'],
	                        'price'                 => $variacao['preco'],
	                        'image'                 => $image_dir,
	                        'status'                => 1,
	                        'EAN'                   => $variacao['ean'],
	                        'codigo_do_fabricante'  => '',
                        )
                    );

                    $produtoPaiNovoEstoque += $variacao['qty'] ;
					if ((float)$variacao['preco'] > (float)$productFormat['price']) {
						$newPrice = (float)$variacao['preco'];
					}
                }else{
                    echo "Variação sku {$variacao['sku']} e id {$variacao['variant_id_erp']} já cadastrada..\n";                    
                }   
                 
            }
			$prdupdate = array();
			if ((int)$productFormat['qty'] != (int)$produtoPaiNovoEstoque) {
				$prdupdate['qty'] = (int)$produtoPaiNovoEstoque; 
			}
			if ($newPrice) {
				$prdupdate['price'] = $newPrice; 
			}
			if (!empty($prdupdate)) {
				// qty e preço  
				$this->_this->model_products->update($prdupdate, $prd_id , "Alterado Integração Eccosys");
			}
        }
        return array('success' => true);
       
    }

	/**
     * Busca as imagens de determinado produto no eccosys 
     *
     * @param   string  $id_produto  código do produto no eccosys
     * @return  array               Retorna status se conseguiu baixar ou não e o array com imagens 
     */
	function getEccoSysImages($id_produto) {

        echo "Buscado imagens do produto -> {$id_produto}............\n";
	 	$ECCOSYS_URL = $this->getUrlEccosys($this->_this->store);
       	$url = $ECCOSYS_URL."/api/produtos/$id_produto/imagens";
        $data = "";
		$dataProductImgs = json_decode(json_encode($this->_this->sendREST($url, $data)));
	
        if ($dataProductImgs->httpcode == 200) {
            $imgsResult = json_decode($dataProductImgs->content);
            if (count($imgsResult)>0) {
                echo "Total de imagens encontradas ".count($imgsResult)."\n";
            }
			return array('success' => true , 'imagens' => $imgsResult );
        } else {
            echo "Imagens não cadastradas ou com erro.\n";
			return array('success' => false, 'message' =>  array("Erro ao buscar imagens do produto {$id_produto}, resposta do Eccosys {$dataProductImgs->httpcode}" ));;
        }
	}
                    

    /**
     * Recupera ID de algum valor do banco de dados (se for fabricante e não existir, irá cadastrar)
     *
     * @param   string  $table  Tabela do banco
     * @param   string  $column Coluna para where
     * @param   string  $value  Valor para where
     * @return  bool|integer    Retorna o valor da cosnulta ou false se não encontrou resultado
     */
    public function getCodeInfoProduct($table, $column, $value)
    {
        $query = $this->_this->db
            ->select('id')
            ->from($table)
            ->where(array($column => $value))
            ->get();

//        echo '<br>L_503_query: '.$query;

        if ($query->num_rows() === 0 && $table == "brands") {
        	if ($this->disable_brand_creation) {
        		return false; 
			}
			return $this->CI->model_brands->create(array('name' => $value, 'active' => 1));
        }

        if ($query->num_rows() === 0 && $table != "brands") return false;

        $result = $query->first_row();

/*        echo '<br>L_521_result: <br>';
        var_dump($result); */
        //die;

        return $result->id;
    }


    /* Verifica os campos para validação
     *
     * @param   integer     $idEccosys  Código da variação no eccosys
     * @return  null|array              Retorna um array com dados da variação ou null caso não encontre
     */
    public function getVariationForIdErp($idEccosys)
    {
        return $this->_this->db
            ->select('prd_variants.*')
            ->from('prd_variants')
            ->join('products', 'products.id = prd_variants.prd_id')
            ->where(
                array(
                    'prd_variants.variant_id_erp' => $idEccosys,
                    'products.store_id' => $this->_this->store
                )
            )
            ->get()
            ->row_array();
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
    public function verifyFields($key,$value, $required, $type, $newProduct = true)
    {
        $value_ok = array(true, $this->setValueFormat($value, $type));

        if ($key === 'categoria' && $required) {
            if ($value === "" || (integer)$value <= 0) {
                $value_ok = array(true, '');
            } elseif(isset($value)){
                $value=(array)$value;
                foreach($value as $Departamento){
                    $resultCat = '';
                    foreach($Departamento->categorias as $categoria){
                        $resultCat .= $categoria->nome.",";
                        foreach($categoria->subcategorias as $subcat){
                            $resultCat .= $subcat->nome.",";
                        }
                    }
                    $value_ok = array(true, $resultCat);
                }
            } else {
                return array(false, "A categoria tem que ser ajustada, é necesário ter um valor!");
            }
        }
        
        if ($key === "origem" && ($value < 0 || $value > 8) && $required) {
            return array(false, "A origem do produto deve ser entre 0 e 8! http://legislacao.sef.sc.gov.br/html/regulamentos/icms/ricms_01_10.htm");

        }

        if ($key === "unidade") {
            $value = trim($value);
	        $codeInfoProduct = $this->getCodeInfoProduct('attribute_value', 'value', $value);
	
	        if($codeInfoProduct){
	            $existFromTo = false;
	            foreach ($this->tipoUnidades as $tipoUn => $realUn) {
	                if ($tipoUn == strtolower($value)) {
	                    $searchUn = $this->getCodeInfoProduct('attribute_value', 'value', $realUn);
	                    $value_ok = array(true, $this->setValueFormat("[\"{$searchUn}\"]", $type));
	                    $existFromTo = true;
	                    break;
	                }
	            }
	
	            if ($required && !$existFromTo)
	                return array(false, "Unidade informada do produto não encontrada, informe uma válida. (UN/Kg)");
	        }
	        else $value_ok = array(true, $this->setValueFormat('[""]', $type));
		} 
		
		if ($key === "fabricante" ) {
            if ($key === "fabricante" && is_object($value)) {
                //busca todas as variações do produto
				if (property_exists($value,'nome')) {  // verifica se veio o nome da marca
					$valorNome= $value->nome;
               	 	$codeInfoProduct = $this->getCodeInfoProduct('brands', 'name', $valorNome);
					if($codeInfoProduct) { // verifica se tem o nome da marca no sistema
                    	$value_ok = array(true, $this->setValueFormat("[\"{$codeInfoProduct}\"]", $type));
					} else {
						return array(false, "Fabricante informado do produto ({$valorNome})não encontrado, informe um válido ou abra chamado para cadastrar o fornecedor.");
					}
				} else { // não tinha a marca no payload
					if ($required) { // era obrigatório
            			return array(false, "Nenhum fabricante informado no Eccosys.");
					}else { // não era obrigatório
						$value_ok = array(true, $this->setValueFormat('[""]', $type));
					}
				}
            } elseif ($key === "fabricante" && $required) {
                return array(false, "Fabricante informado do produto não encontrado, informe um válido.");
            }
        }
		
		
        if ($key === "variacoes") {
        	$payload = (object)$value;
			if (!(isset($payload->_Skus) && (count($payload->_Skus) > 0))) { // não tem variação
				return array(true,array('has_variants' => ''));
			}
			//busca todas as variações do produto
            $ECCOSYS_URL = $this->getUrlEccosys($this->_this->store);
            $url = $ECCOSYS_URL.'/api/produtos?$filter=idProdutoMaster+eq+'.$payload ->id;
            $data = "";

            $variations = json_decode(json_encode($this->_this->sendREST($url, $data)));  
			if ($variations->httpcode != 200) {
			    echo "Erro {$variations->httpcode} ao tentar ler as variações do produto {$payload->codigo} na Eccosys\n";
				return array(false,"Erro {$variations->httpcode} ao tentar ler as variações do produto {$payload->codigo} na Eccosys");
			}      

            $prodResVar = json_decode($variations->content);
			
			$variation_name = '';   
			$varArr     = array();
            foreach ($prodResVar as $keyvar => $prodVar) {
            	$hasVariations = array();
            	echo "Lendo variação aqui ".$prodVar->id." \n";                                     
                $url = $ECCOSYS_URL.'/api/produtos/'.$prodVar->id;
                $resVariation = json_decode(json_encode($this->_this->sendREST($url, "")));
				if ($resVariation->httpcode != 200) {
					echo "Erro {$resVariation->httpcode} ao tentar ler a variação {$prodVar->id} do produto {$payload->codigo} na Eccosys\n";
                    return array(false, "Erro {$resVariation->httpcode} ao tentar ler a variação {$prodVar->id} do produto {$payload->codigo} na Eccosys");
				}
                $productVar = json_decode($resVariation->content);                        
                      
				if (empty ($productVar->_Atributos) || !isset($productVar->_Atributos) ) {
					echo "Variação sku = $productVar->codigo não possui atributos para determinar uma variação no eccosys\n";
                    return array(false, "Variação sku = {$productVar->codigo} e nome = {$productVar->name} não possui atributos cadastrados para determinar uma variação no eccosys");  
				}
					               
                foreach ($productVar->_Atributos as $attr){
                    if(!empty($attr->valor)){
                        if (array_key_exists(strtolower($attr->descricao), $this->tipoVariacoes)){
                            $variationType = $this->tipoVariacoes[strtolower($attr->descricao)];
                            if (!array_key_exists($variationType, $hasVariations)) {
                                $hasVariations[$variationType] = $attr->valor;
                            }       
                        }                                    
                    }                                                 
                }
           
                if(empty($hasVariations)) {
                	echo 'Nenhum attributo com variação '."\n";
           			return array(false, "Variação sku = {$productVar->codigo} e nome = {$productVar->nome} não possui atributos cadastrados para determinar uma variação. Variações validas: Tamanho, Cor e Voltagem ");      
				}
                
				if (!$this->_this->model_products->ean_check($productVar->gtin))
					return array(false, "Variação sku={$productVar->codigo} nome={$variation_name} com código de barra(EAN/GTIN) {$productVar->gtin} inválido ou inexistente!");
				
				$varStr     = "";
				$varValues  = "";
	            if (array_key_exists('TAMANHO', $hasVariations))  {
	            	$varStr .= ";TAMANHO";
					$varValues .= ";".$hasVariations['TAMANHO'];
				}
	            if (array_key_exists('Cor', $hasVariations))  {
	            	$varStr .= ";Cor";
					$varValues .= ";".$hasVariations['Cor'];
				}
	            if (array_key_exists('VOLTAGEM', $hasVariations)) {
	            	$varStr .= ";VOLTAGEM";
					$varValues .= ";".$hasVariations['VOLTAGEM'];
	            } 
	            $varStr = substr($varStr,1);
	            $varValues = substr($varValues,1);
	            echo "$varValues\n";
				if ($variation_name == '') 
					$variation_name = $varStr;
				if ($variation_name != $varStr) {
					echo 'Atributos incompatíveis entre skus '.$variation_name.' '.$varStr."\n";
           			return array(false, "Variação sku = {$productVar->codigo} e nome = {$productVar->name} não está com as mesmas variações do produto. Todos os SKUs devem ter definidos os mesmos atributos com valores de Tamanho, Cor e Voltagem");      
				}
				
				$imagesVar = $this->getEccoSysImages($productVar->id);  // busco o array de imagens 
				if (!$imagesVar['success']) { // deu algum erro
					return array(false,$imagesVar['message']); 
				}
                if(!$this->validateSkuSpace($productVar->codigo)){
                    return array(false, $this->getMessagemSkuFormatInvalid());
                }
                if(!$this->validateLengthSku($productVar->codigo)){
                    return array(false, $this->getMessageLenghtSkuInvalid());
                }
            	$preco = number_format($productVar->preco, 2, ".", "");
            	$estoque = (int)$productVar->_Estoque->estoqueDisponivel;

				$varArr[$keyvar] = array (
					'sku'    	     => $productVar->codigo,
                	'variant_id_erp' => $productVar->id,
                	'preco'  	     => $preco,
					'qty'   		 => $estoque,
                	'variacao'	     => $varValues,
					'imagem'	     => $imagesVar['imagens'], 
					'ean'		     => $productVar->gtin ? $productVar->gtin : '',
				);   
            }
            $value_ok = array( true, array (
            	'has_variants'  	=> $variation_name, 
            	'variacoes'			=> $varArr,
            ));
			
        }

        if ($key === 'largura' && $value < 11 && $required) {
            //return array(false, "A largura do produto não pode ser menor que 11(onze).");

            if ($value < 11) $value_ok = array(true, 11);
        }

        if ($key === 'altura' && $value < 2 && $required) {
            //return array(false, "A altura do produto não pode ser menor que 2(dois).");

            if ($value < 2) $value_ok = array(true, 2);
        }

        if ($key === 'comprimento' && $value < 16 && $required) {
            //return array(false, "A profundidade do produto não pode ser menor que 16(dezesseis).");

            if ($value < 16) $value_ok = array(true, 16);
        }

        if ($key === 'garantia' && $required) {
            if ($value === "" || (integer)$value <= 0) {
                $value_ok = array(true, 0);
            } else {
                $value_ok = array(true, $value);
            }
        }

        return $value_ok;
    }


    /**
     * Recupera se o produto já está integrado com algum maeketplace
     *
     * @param   int                 $prd_id Código do produto
     * @return  array|null          Retorna um array se existir se não retorna nulo
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
     * Atualiza a coluna has_variants de um produto
     *
     * @param   int  $prd_id Id do produto
     * @param   array   $array_variations
     * @return  bool    Retorna o status da atualização
     */
    public function updateHasVariations($prd_id, $arrayVariations)
    {    
        $has_variations = '';
        foreach ($arrayVariations as $value){
            if($value != "")
                $has_variations .= $value.';';
        }
        return $this->_this->db->where(array('id' => $prd_id))->update('products', array('has_variants' => $has_variations)) ? true : false;

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
        $verifyProduct = $this->getVariationForSku($sku);
        if($verifyProduct)
            return $this->_this->db->where(array('sku' => $sku, 'id' => $verifyProduct['id']))->update('prd_variants', array('price' => $price)) ? true : false;

        // Atualiza o preço do produto
        return $this->_this->db->where(array('sku' => $sku, 'store_id' => $this->_this->store))->update('products', array('price' => $price)) ? true : false;
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
        
        $verification_sku=$this->verifyFieldsProduct('sku',$payload->codigo,true,'S',true,$idProd);
        if(!$verification_sku[0]){
            return array('success' => $verification_sku[0], 'message' => $verification_sku[1]);
        }
        // não podeerá mais ser atualizado
        if ($this->getPrdIntegration($idProd))
            return array('success' => false, 'message' => array("Produto {$skuPai}, não pode mais receber novas variações pois já está integrado com o marketplace."));

		echo "Lendo variação ".$payload->id." \n";   
		$ECCOSYS_URL = $this->getUrlEccosys($this->_this->store);                                  
        $url = $ECCOSYS_URL.'/api/produtos/'.$payload->id;
        $resVariation = json_decode(json_encode($this->_this->sendREST($url, "")));
		if ($resVariation->httpcode != 200) {
			echo "Erro {$resVariation->httpcode} ao tentar ler a variação {$payload->id} do produto {$payload->codigo} na Eccosys\n";
            return array('success' => false, 'message' => array("Erro {$resVariation->httpcode} ao tentar ler a variação {$payload->id} do produto {$payload->codigo} na Eccosys"));
		}

		$productVar = json_decode($resVariation->content); 
		$verification_ean=$this->verifyFieldsProduct('ean',$productVar->gtin,true,'S',true);
        if(!$verification_ean[0]){
            return array('success' => $verification_ean[0], 'message' => $verification_ean[1]);
        }
		if (!$this->_this->model_products->ean_check($productVar->gtin))
           return array('success' => false, 'message' => array("Variação sku={$productVar->codigo} nome={$productVar->nome} com código de barra(EAN/GTIN) {$productVar->gtin} inválido ou inexistente!"));

		if (empty ($productVar->_Atributos) || !isset($productVar->_Atributos) ) {
			echo "Variação sku = $productVar->codigo não possui atributos para determinar uma variação no eccosys\n";
            return array('success' => false, 'message' => array("Variação sku = {$productVar->codigo} e nome = {$productVar->nome} não possui atributos cadastrados para determinar uma variação no eccosys"));  
		}
					
		$tipoVarArr = array();
        $varArr     = array();          
        foreach ($productVar->_Atributos as $attr){
            if(!empty($attr->valor)){
                if (array_key_exists(strtolower($attr->descricao), $this->tipoVariacoes)){
                	$realVarEnvia = $this->tipoVariacoes[strtolower($attr->descricao)];
                    if (!in_array($realVarEnvia, $tipoVarArr))
		                array_push($tipoVarArr, $realVarEnvia);
					$varArr[$realVarEnvia] = $attr->valor; 
                }                                    
            }                                                 
        }
        if (empty($tipoVarArr)) {
        	echo "Não foi encontrado um atributo Eccosys compatível com um tipo de variação conectalá\n";
        	return array('success' => false, 'message' => array("Não foi encontrada uma atributo compatível com umas das variações (Variações aceitas: Cor/Tamanho/Voltagem)."));
        }                

		if ((array_diff($typesVariations, $tipoVarArr) || array_diff($tipoVarArr, $typesVariations)) && count($typesVariations))
            return array('success' => false, 'message' => array("Foram encontradas tipos de variações para essa variação que não estão cadastradas no produto. Todas as variações devem conter os mesmos tipos (Cor, Tamanho e Voltagem) "));
	
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
		
		echo "{$varStr}\n";
						
		// recuperar todas as variações para definir o 'variant' e pegar o estoque 
		$qtyNew = 0;
        $variant = null;
        foreach ($this->getVariantProduct($idProd) as $varReal) {
            $variantReal = (int)$varReal['variant'];
			$qtyNew += $varReal['qty'];
			
            if ($variant && $variantReal > $variant) $variant = $variantReal;
            if (!$variant) $variant = $variantReal;
        }
        if ($variant === null) $variant = 0;
        else $variant++;
		
		// Somo o estoque desta variação
		$qtyNew += $productVar->_Estoque->estoqueDisponivel ?? 0; 
		
		// BUSCO AS IMAGENS 
		$imagesVar = $this->getEccoSysImages($productVar->id);  // busco o array de imagens 
		if (!$imagesVar['success']) { // deu algum erro
			return $imagesVar; 
		}
        $verification_sku=$this->verifyFieldsProduct('sku',$productVar->codigo,true,'S',true,$idProd);
        if(!$verification_sku[0]){
            return array('success' => $verification_sku[0], 'message' => $verification_sku[1]);
        }
        $verification_ean=$this->verifyFieldsProduct('ean',$productVar->gtin,true,'S',true);
        if(!$verification_ean[0]){
            return array('success' => $verification_ean[0], 'message' => $verification_ean[1]);
        }
		$imagesVar = $imagesVar['imagens'];
	    echo " variação ".$varStr." variant ".$variant." com ".count($imagesVar)." imagens\n";
		$image_dir='';
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
	            'prd_id'                => $dataProduct['id'],
	            'variant'               => $variant,
	            'name'                  => $varStr,
	            'sku'                   => $productVar->codigo ?? '',
	            'qty'                   => $productVar->_Estoque->estoqueDisponivel ?? 0,
	            'variant_id_erp'        => $productVar->id ?? 0,
	            'price'                 => number_format($productVar->preco, 2, ".", "") ?? 0,
	            'image'                 => $image_dir,
	            'status'                => 1,
	            'EAN'                   => $productVar->gtin ?? '',
	            'codigo_do_fabricante'  => '',
	        )
	  	);
                     
   		if (!$createVar)
            return array('success' => false, 'message' => array("Não foi possível inserir a variação."));

		$prd_update = array();
		if ((float)number_format($productVar->preco, 2, ".", "") > (float)$dataProduct['price'])
			$prd_update['price'] = number_format($productVar->preco, 2, ".", "") ; 
		if ((int)$qtyNew != (int)$dataProduct['qty'])
			$prd_update['qty'] = (int)$qtyNew; 

		if (!empty($prd_update)) {
			$updateProduct =  $this->_this->model_products->update($prd_update, $dataProduct['id'], "Alterado Integração Eccosys");

	        if (!$updateProduct)
	            return array('success' => false, 'message' => "Não foi possível atualizar o estoque do produto pai.");
		}		

        return array('success' => true);
    }


    /**
     * Atualização de uma variação
     *
     * @param   object  $payload    Payload da variação para cadastro
     * @param   string  $skuPai     SKU do produto PAI
     * @return  array               Retorna o status do cadastro
     */
    public function updateVariation($payload, $prdId)
    {         
        $skuVar  = $payload->codigo; 
        $skuName = $payload->nome;        
        $idProdPai = $prdId;       

        if ($skuVar == "")
            return array('success' => false, 'message' => array("O SKU da variação não pode ser em branco."));

        if ($skuName == "")
            return array('success' => false, 'message' => array("O Nome da variação não pode ser em branco."));

		$dataVar = $this->getVariantsForIdAndSku($prdId, $skuVar);
		
		if (count($dataVar) === 0) {
			echo " NÂO ACHEI A VARIAÇÂO do PRODUTO ".$prdId." COM O SKU ".$skuVar."\n";
			return array('success' => false, 'message' => array("Não foi encontrada variação com sku ".$skuVar." para atualizar."));
		} 
		
		$variationToUpdate = array();
		$new_price = false;
		$new_qty = false;
		$qty   = $payload->_Estoque->estoqueDisponivel ?? 0;
		$price = number_format($payload->preco, 2, ".", "");
				
        if ($dataVar[0]['sku'] != $skuVar)
			$variationToUpdate['sku'] = $skuVar; 
		if ((float)$dataVar[0]['price'] != (float)$price) {
			$variationToUpdate['price'] = $price; 
			$new_price= true;
		}
			
		if ((int)$dataVar[0]['qty'] != (int)$qty) {
			$variationToUpdate['qty'] = $qty; 
			$new_qty = true;
		}

        // não teve alteração
		if (empty($variationToUpdate))
		     return array('success' => null);
		
        $updateVar = $this->_this->model_products->updateProductVar($variationToUpdate, $dataVar[0]['id'], "Alterado Integração ECCOSYS" );
                
        if (!$updateVar)
            return array('success' => false, 'message' => "Não foi possível atualizar a variação.");
		
		$prd_up = array();
		if (($new_qty) || ($new_price)) {  // acerto o preço ou estoque do produto pai se houve alteração na  variação. 
			$prod = $this->_this->model_products->getProductData(0, $dataVar[0]['prd_id']) ; 
			$variants = $this->_this->model_products->getVariants($dataVar[0]['prd_id']);
			$prd_qty = $qty;
			$prd_price = $price;
			foreach($variants as $variant) {
				if ($variant['id'] !=  $dataVar[0]['id']){
					$prd_qty+= $variant['qty'];
					if ($variant['price'] > $prd_price)
						$prd_price = $variant['price']; 
				}
			} 
			if ((float)$prd_price > (float)$prod['price'])
				$prd_up['price'] = $prd_price;
			if ((int)$prd_qty != (int)$prod['qty'])
				$prd_up['qty'] = $prd_qty;
			
			if (!empty($prd_up)) {
				$updateprd = $this->_this->model_products->update($prd_up, $dataVar[0]['prd_id'], "Alterado Integração ECCOSYS" );
		        if (!$updateprd)
		            return array('success' => false, 'message' => "Não foi possível atualizar o produto.");
			}
		}		
       
        return array('success' => true);
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

        if(!isset($payload->garantia)){
            $payload->garantia = 0;
        }

        if(!isset($payload->codigoNoFabricante)){
            $payload->codigoNoFabricante = '';
        }

        // type - S=string, "F"=float, "I"=integer, "A"=array
        $product = array(
            'nome'              => array('value' => $payload->nome, 'required' => true, 'type' => 'S', 'field_database' => 'name'),
            'unidade'           => array('value' => $payload->unidade, 'required' => true, 'type' => 'S', 'field_database' => 'attribute_value_id'),
            'cest'              => array('value' => $payload->cest, 'required' => true, 'type' => 'S', 'field_database' => 'CEST'),
            'ncm'               => array('value' => $payload->cf, 'required' => true, 'type' => 'S', 'field_database' => 'NCM'),
            'origem'            => array('value' => $payload->origem, 'required' => true, 'type' => 'I', 'field_database' => 'origin'),
            'ean'               => array('value' => $payload->gtin, 'required' => true, 'type' => 'S', 'field_database' => 'EAN'),
            'peso_liquido'      => array('value' => $payload->pesoLiq, 'required' => true, 'type' => 'F', 'field_database' => 'peso_liquido'),
            'peso_bruto'        => array('value' => $payload->pesoBruto, 'required' => true, 'type' => 'F', 'field_database' => 'peso_bruto'),
            'sku_fabricante'    => array('value' => $payload->codigoNoFabricante, 'required' => true, 'type' => 'S', 'field_database' => 'codigo_do_fabricante'),
            'descricao'         => array('value' => $payload->_FichaTecnica[0]->descricaoDetalhada ?? $payload->descricaoEcommerce, 'required' => false, 'type' => 'S', 'field_database' => 'description'),
            'garantia'          => array('value' => $payload->garantia, 'required' => true, 'type' => 'I', 'field_database' => 'garantia'),
            'fabricante'        => array('value' => $payload->idFornecedor ?? '', 'required' => false, 'type' => 'S', 'field_database' => 'brand_id'),
            'altura'            => array('value' => $payload->altura, 'required' => true, 'type' => 'F', 'field_database' => 'altura'),
            'comprimento'       => array('value' => $payload->comprimento, 'required' => true, 'type' => 'F', 'field_database' => 'profundidade'),
            'largura'           => array('value' => $payload->largura, 'required' => true, 'type' => 'F', 'field_database' => 'largura'),
            // Ajustes Categorias p.category_id, p.category_imported
            'categoria'         => array('value' => $payload->_Departamentos ?? "", 'required' => true, 'type' => 'A', 'field_database' => 'category_imported'),
            'prazo_operacional_extra' => array('value' => $payload->tempoProducao, 'required' => true, 'type' => 'I', 'field_database' => 'prazo_operacional_extra')
        );

        // Validar e formatar campos
        $productFormat = $this->getDataFormat($product, false);

        // Encontrou erro na formatação dos dados
        if (isset($productFormat['success']) && !$productFormat['success'])
            return $productFormat;

        $dataProduct = $this->getProductForSku($payload->codigo);

        $productFormat['product_id_erp'] = $payload->id;
        $productFormat['sku'] = $payload->codigo;
        $productFormat['store_id'] = $this->_this->store;
        
        $errosVar = array();
        $successVar = array();

        // verificar se o produto já foi integrado
        if ($this->getPrdIntegration($dataProduct['id']))
            return array('success' => null, 'errorsVar' => array(), 'successVar' => $successVar);

        if (!isset($productFormat['status']) && $dataProduct['status'] == 4) $productFormat['status'] = 1;

        if (isset($payload->path_images) && isset($payload->anexos)){
            $path = $this->getPathNewImage($dataProduct['image']);
            $images = $this->getImages($payload->anexos, $path['path_complet']);
            if (!$images['success']) {
                // encontrou erro no upload de imagem
                echo "erro upload imagem\n";
                //return $images;
            }

            //define nome da pasta com as imagens e a imagem principal
            if(isset($images['primary'])){
                $productFormat['principal_image'] = $images['primary'] ?? null;
            }
            
            if (              
                ($dataProduct['category_id'] != '[""]' || $productFormat['category_id'] != '[""]') &&  
                ($dataProduct['brand_id'] != '[""]' || $productFormat['brand_id'] != '[""]') &&
                $this->_this->uploadproducts->countImagesDir($dataProduct['image'])
            )
                $productFormat['situacao'] = 2;
            else
                $productFormat['situacao'] = 1;

        }
        
        // remove os itens que não derão ser atualizados
        foreach ($this->naoAtualizar as $field)
            unset($productFormat[$field]);        
        
        if ($this->getProductForAttributes($productFormat))
            return array('success' => null, 'errorsVar' => $errosVar, 'successVar' => $successVar);


        $sqlProd = $this->_this->db->update_string('products', $productFormat, array('sku' => $payload->codigo, 'store_id' => $this->_this->store));
        $update = $this->_this->db->query($sqlProd);

        // bloqueia produto se necessário
        $this->CI->blacklistofwords->updateStatusProductAfterUpdateOrCreate($this->getProductForSku($payload->codigo), $dataProduct['id']);

        if ($update)
            return array('success' => true, 'errorsVar' => $errosVar, 'successVar' => $successVar);

        return array('success' => false, 'message' => array('Não foi possível atualizar o produto.'));

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
    public function getVariantsForIdAndSku($product_id, $sku_var) {
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

     /*
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
        $curl_handle = curl_init($url.$data);


        if ($method == "GET") {
            curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, 1);

        } elseif ($method == "POST" || $method == "PUT") {
            if ($method == "PUT") {
                curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'PUT');
            }

            curl_setopt($curl_handle, CURLOPT_POSTFIELDS, json_encode($data));
        }

        curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Authorization: {$this->token}",
        ));

        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, true);
        $response = curl_exec($curl_handle);
        $httpcode = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
        curl_close($curl_handle);

        $header['httpcode'] = $httpcode;
        $header['content']  = $response;

        $header = $this->_apiBlockSleep($url, $data, $method, $header);

        return $header;

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
        if ($query->num_rows() == 0) {
            return false;
        }
        $result = $query->row_array();

        if (!$skuVar) {
            return $result['qty'];
        }

        $query = $this->_this->db->get_where('prd_variants', array('prd_id' => $result['id'], 'sku' => $skuVar));
        if ($query->num_rows() == 0) {
            return false;
        }

        $result = $query->row_array();

        return $result['qty'];
    }


    public function getStockVariationForSku($skuVar)
    {        
        $query = $this->_this->db->get_where('prd_variants', array('sku' => $skuVar));
        if ($query->num_rows() == 0) {
            return false;
        }

        $result = $query->row_array();

        return $result['qty'];
    }


    /* Verifica se a API foi bloqueada por limite de requisição
    *
    * @param   string  $url        URL requisição
    * @param   string  $data       Dados para envio no body
    * @param   string  $method     Método da requisição
    * @param   string  $header     Resposta da requisição atual
    * @return  array               Retorno da requisição
    */
    public function _apiBlockSleep($url, $data, $method, $header)
    {
        $attempts = 3;
        // enquanto a api estiver bloqueada ficará tentando até encontrar o resultado
        while ($header['httpcode'] == 429) {
            if ($this->_this->countAttempt > $attempts) {
                return array('httpcode' => 999, 'content' => '{"retorno":{"erros":{"erro": {"cod": 3}}}}');
            };
            echo "API Bloqueada, vou esperar 30 segundos e tentar novamente (Tentativas: {$this->_this->countAttempt}/{$attempts})...\n";
            sleep(60); // espera 60 segundos
            $this->_this->countAttempt++;
            $header = $this->_sendREST($url, $data, $method, false); // enviar uma nova requisição para ver se já liberou
        }

        return $header;
    }

    /* Recupera produto por seus atributos
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

    /* Atualiza o estoque da variação
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
            	 $this->_this->model_products->updateProductVar(array('qty' => $qty), $variation['id'],  "Alterado Integração Eccosys" );
            }

            $newQty += (float)$variation['qty'];
        }

        return $this->updateStockProduct($skuPai, $newQty) ? true : false;
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

        return json_decode($jsonEncode, true);
    }


    /* Recupera o estoque das variações do produto pelo ID do produto
    *
    * @param   string      $prd_id ID do produto
    * @return  null|int    Retorna a quantidade em estoque das variações
    */
    public function getStockVariation($prd_id)
    {
        $countVar = 0;

        $query = $this->_this->db->get_where('prd_variants', array('prd_id' => $prd_id));
        if ($query->num_rows() == 0) {
            return false;
        }

        foreach ($query->result_array() as $var) {
            $countVar += $var['qty'];
        }

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
        $this->_this->db->where(
            array(
                'sku'       => $sku,
                'store_id'  => $this->_this->store,
            )
        );

        return $this->_this->db->update('products', array('qty' => $qty)) ? true : false;
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
     * Recupera estado da loja se usa lista de preço
     *
     * @return bool Retorna se a loja usa lista de preço
     */
    public function getUseListPrice()
    {
        $query = $this->_this->db->get_where('api_integrations', array('store_id' => $this->_this->store))->row_array();

        $credentials = json_decode($query['credentials']);

        $list = $credentials->lista_eccosys;

        if ($list == "" || !$list) return false;

        return true;
    }

    /**
     * Recupera o preço do produto na lista de preço
     *
     * @param   int     $idProduct  Código do produto
     * @return  array   Retorna um array com o status da consulta e valor
     */
    public function getPriceVariationListPrice($products)
    {
        $ECCOSYS_URL = $this->getUrlEccosys($this->_this->store);
        foreach ($products as $product) {
            // Consulta endpoint par obter estoque            
            $url = $ECCOSYS_URL.'/api/estoques/';
            $data = "{$product},";

            if (function_exists('sendREST'))
                $dataPrice = json_decode($this->_this->sendREST($url, $data));
            else
                $dataPrice = json_decode($this->_sendREST($url, $data));

            // Ocorreu um problema
            if ($dataPrice->retorno->status != "OK") {
                if ($dataPrice->retorno->codigo_erro != 200) {
                    echo "Ocorreu um problema para obter o preço da produto_id_eccosys={$product} retorno=" . json_encode($dataPrice) . "\n";
                    $this->_this->log_data('batch', 'Product/getStock', "Ocorreu um problema para obter o estoque do produto_id_eccosys={$product} retorno=" . json_encode($dataPrice), "E");
                }
                return array('success' => false);
            }

            return array('success' => true, 'value' => (float)$dataPrice->retorno->registros[0]->registro->preco);
        }
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