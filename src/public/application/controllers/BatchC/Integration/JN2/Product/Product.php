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
        //'situacao', atualiza situação conforme tiver informações da categoria, fabricante e imagem, conforme Pedro passou.
        'status',
        'image',
        'store_id',
        'company_id',
        'category_id'//,
        //'has_variants'  ** Ver com o Pedro, estou atualizando só se tds estiver o mesmo tipo da variação.
    );

    // Passagem de dados
    private $_this;

    private $product_length_name ;
    private $product_length_description ;

    public function __construct($_this)
    {
        $this->_this = $_this;

        $this->CI =& get_instance();
        $this->CI->load->library('BlacklistOfWords');
        $this->CI->load->model('model_settings');

        if ($allowableTags = $this->CI->model_settings->getValueIfAtiveByName('products_allowable_tags')) {
            if (!empty($allowableTags)) {
                $this->allowable_tags = '<' . implode('><', explode(',', $allowableTags)) . '>';
            }
        }

        $this->loadLengthSettings();
    }
    
    public function getProductForIdErp($idJN2, $sku = null)
    {
        $query = $this->_this->db->get_where('products use index (store_iderp)',
            array(
                'store_id'       => $this->_this->store,
                'product_id_erp' => $idJN2
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
        $this->_this->db->where(
            array(
                'sku'       => $sku,
                'store_id'  => $this->_this->store,
            )
        );
        return $this->_this->db->update('products', $data) ? true : false;
    }

    public function getUrlJN2($store_id)
    {
        $dataIntegrationStore = $this->_this->db
                                ->from('api_integrations')
                                ->where('store_id', $store_id)
                                ->get()
                                ->result_array();

        if($dataIntegrationStore){
            $credentials = json_decode($dataIntegrationStore[0]['credentials']);
            return $credentials->url_jn2;
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
        return $this->_this->db->get_where('products use index (store_sku)',
            array(
                'store_id'  => $this->_this->store,
                'sku'       => $sku
            )
        )->row_array();
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
    public function getPathNewImage($strProdImagePai = null)
    {
        $serverpath     = $_SERVER['SCRIPT_FILENAME'];
        $pos            = strpos($serverpath,'assets');
        $serverpath     = substr($serverpath,0,$pos);
        $targetDir      = $serverpath . 'assets/images/product_image/';
        $dirImage       = $path ?? Admin_Controller::getGUID(false); // gero um novo diretorio para as imagens
        
        if($strProdImagePai){
            $targetDir .= $strProdImagePai.'/';
        }
        
/*        echo "\nL_181_targetDir: ";
        print_r($targetDir);
         
        echo "\nL_180_dirImage: ";
        print_r($dirImage); */
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
/*                    echo "\nL_262_upload: ";
                    print_r($upload); */
                                      
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
        /*else {
            echo "\nL_278_ não é array";
            print_r($arrImages);
            die;
        } */

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
     * @param   array $product  Produto para ser formatado
     * @return  array           Retorna o produto com a formatação para ser inserido
     */
    public function getDataFormat($product)
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
            if (in_array($key, ['garantia','comprimento','altura','largura','variacoes','categoria','category_imported'])) {
                // Verificações que são diferentes dentro da integração.
                $field_format = $this->verifyFields($key, $field['value'], $field['required'], $field['type'], $newProduct);
            } else {
                $field_format = $this->verifyFieldsProduct($key, $field['value'], $field['required'], $field['type'], $newProduct);
            }

            // encontrou um erro, deve encerrar a criação do produto e apresentar o motivo
            if (!$field_format[0])
                array_push($erros, $field_format[1]);

            if ($key == "media_gallery_entries" || $key == "imagesExterna")
                array_push($productFormat['media_gallery_entries'], $field_format[1]);
            elseif ($key == "image"){
                array_push($productFormat['image'], $field_format[1]);
            }
            else {
                if($field['type'] == 'A' && $key != "categoria" && $key != "category_imported"){
                    if($field_format[0]){
                        $productFormat[$field['field_database']] = $field_format[0];
                    }
                } else {
                    $productFormat[$field['field_database']] = $field_format[1];
                }
            }
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
            $JN2_URL = $this->getUrlJN2($this->_this->store);
            $url = $JN2_URL.'/api/produtos/'.$product;
            $data = "{$product}";


            $dataStockProduct = json_decode(json_encode($this->_this->sendREST($url, $data)));

            // Ocorreu um problema
            if ($dataStockProduct->httpcode != 200) {
                echo "Ocorreu um problema para obter o estoque do produto_id={$product} retorno=" . json_encode($dataStockProduct) . "\n";
                $this->_this->log_data('batch', 'Product/getStock', "Ocorreu um problema para obter o estoque do produto_id_jn2={$product} retorno=" . json_encode($dataStockProduct), "E");
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
     * @param   object  $payload        Payload produto para cadastro
     * @param   array   $arrVariacoes   Arr com informações dos filhos (variações) se houver
     * @return  array               Retorna o status do cadastro ou array de mapemaneto caso seja webhook
     */
    public function createProduct($payload, $arrVariacoes = null)
    {
        $strCaminhoImgPai = null;
        $arrImagensVerifica = null;
        $images = null;
        $strPrincipalImage = null;
        $imgFilhoProprioPai = null;
        $arrImagensPaiFilho = null;
                
        // type - S=string, "F"=float, "I"=integer, "A"=array
        $product = array(
            'nome'              => array('value' => $payload->name, 'required' => true, 'type' => 'S', 'field_database' => 'name'),
            'sku'               => array('value' => $payload->sku, 'required' => true, 'type' => 'S', 'field_database' => 'sku'),
            'unidade'           => array('value' => 'UN', 'required' => true, 'type' => 'S', 'field_database' => 'attribute_value_id'),
            'preco '             => array('value' => $payload->price ?? $payload->price, 'required' => true, 'type' => 'F', 'field_database' => 'price'),
            'ncm'               => array('value' => isset($payload->ncm) ? $payload->ncm : 0, 'required' => true, 'type' => 'S', 'field_database' => 'NCM'),
            //'origem'            => array('value' => $payload->origem, 'required' => true, 'type' => 'I', 'field_database' => 'origin'),
            'ean'               => array('value' => isset($payload->ean) ? $payload->ean : '', 'required' => true, 'type' => 'S', 'field_database' => 'EAN'),
            'peso_liquido'      => array('value' => $payload->weight ?? 0, 'required' => true, 'type' => 'F', 'field_database' => 'peso_liquido'),
            'peso_bruto'        => array('value' => $payload->weight ?? 0, 'required' => true, 'type' => 'F', 'field_database' => 'peso_bruto'),
            'garantia'          => array('value' => isset($payload->garantia) ? $payload->garantia : 0, 'required' => true, 'type' => 'I', 'field_database' => 'garantia'),
            'fabricante'        => array('value' => isset($payload->manufacturer) ? $payload->manufacturer : '', 'required' => true, 'type' => 'S', 'field_database' => 'brand_id'),
            //'imagesAnexo'       => array('value' => $payload->anexos, 'required' => true, 'type' => 'A', 'field_database' => null),
            'altura'            => array('value' => $payload->altura, 'required' => true, 'type' => 'F', 'field_database' => 'altura'),
            'comprimento'       => array('value' => $payload->comprimento, 'required' => true, 'type' => 'F', 'field_database' => 'profundidade'),
            'largura'           => array('value' => $payload->largura, 'required' => true, 'type' => 'F', 'field_database' => 'largura'),
            'descricao'         => array('value' => isset($payload->descricao) ? $payload->descricao : "", 'required' => true, 'type' => 'S', 'field_database' => 'description'),
            'prazo_operacional_extra' => array('value' => $payload->prazo_operacional, 'required' => true, 'type' => 'I', 'field_database' => 'prazo_operacional_extra'),
            // Ajustes Categorias p.category_id, p.category_imported
            'categoria'         => array('value' => $payload->category_id ?? "", 'required' => true, 'type' => 'A', 'field_database' => 'category_id'),
            'category_imported' => array('value' => $payload->category_imported ?? "", 'required' => true, 'type' => 'A', 'field_database' => 'category_imported'),
            'quantidade'        => array('value' => $payload->quantidade, 'required' => true, 'type' => 'S', 'field_database' => 'qty')
        );

        // Validar e formatar campos
        $productFormat = $this->getDataFormat($product);

        // Encontrou erro na formatação dos dados
        if (isset($productFormat['success']) && !$productFormat['success']) {
            return $productFormat;
        }

        $productFormat['product_id_erp'] = $payload->id;
        
        // upload de imagens
        $path = $this->getPathNewImage(); // folder que receberá as imagens
        $strCaminhoImgPai = $path['path_product'];
        $productFormat['image'] = $strCaminhoImgPai;

        $urlImagens = 'https://beta1.boostcommerce.com.br/media/catalog/product';
        if(isset($payload->media_gallery_entries)){
            foreach($payload->media_gallery_entries as $arrImagens){
                $arrImagensVerifica[] = $urlImagens.$arrImagens->file;
                $arrImagensPaiFilho[] = $arrImagens->file;
            }
            
            if(isset($payload->media_gallery_entries[0]->file) && $payload->type_id == "simple"){
                $imgFilhoProprioPai = $arrImagensPaiFilho;
            }
        }

        if(isset($arrImagensVerifica)){
            $payload->anexos = $arrImagensVerifica;

            if (isset($payload->anexos)) {
                // Faz upload e recupera os códigos
                $images = $this->getImages($payload->anexos, $path['path_complet']);
                if (!$images['success']) {
                    // encontrou erro no upload de imagem
                    echo "\nErro carregar imagens: ".$images['message'][0];
                     return array('sucess'=>false, 'message'=>$images);
                }
            }
            
            //define nome da pasta com as imagens e a imagem principal
            if(isset($images['primary'])){
                $strPrincipalImage = $images['primary'];                
                $productFormat['principal_image'] = $strPrincipalImage ?? null;
            }
        }

        
        // recupera as variações para inserção e define o has_variants com os tipos
        $productFormat['has_variants'] = "";

        if(isset($payload->has_variants)){
            $productFormat['has_variants'] = $payload->has_variants;
        }
        
        $productFormat['store_id'] = $this->_this->store;
            
        // Inserção produto e pegar id para passar nas variações
        $prd_id = $this->_this->model_products->create($productFormat);

        // bloqueia produto se necessário
        $this->CI->blacklistofwords->updateStatusProductAfterUpdateOrCreate($productFormat, $prd_id);

        //Se houver variações vai cadastra o(s) filho(s) na prd_variants
        if(isset($payload->has_variants) && $arrVariacoes){
            //Se o próprio Pai for o filho, será as mesmas imagens.
            if($payload->type_id == "simple"){
                $arrVariacoes[$payload->id]['image_filho'] = $imgFilhoProprioPai ?? '';
            }
            
            $arrRetVariacao = $this->cadastrarVariacoes($arrVariacoes, $prd_id, $payload->id, $payload->sku, false, $strCaminhoImgPai);
            if($arrRetVariacao['success'] == false){
                return $arrRetVariacao;
            }
        }
        
        return array('success' => true, 'prd_id' => $prd_id);
    }

    /**
     * Recupera ID de algum valor do banco de dados (se for fabricante ou categorias e não existir, irá cadastrar)
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

        if ($query->num_rows() === 0 && $table == "brands") {
            $sqlBrand = $this->_this->db->insert_string('brands', array('name' => $value, 'active' => 1));

            $this->_this->db->query($sqlBrand);
            $query = $this->_this->db
                ->select('id')
                ->from($table)
                ->where(array($column => $value))
                ->get();
        }

        if ($query->num_rows() === 0 && $table != "brands" ) return false;

        $result = $query->first_row();

        return $result->id;
    }


    /* Verifica os campos para validação
     *
     * @param   integer     $idJN2  Código da variação no JN2
     * @return  null|array              Retorna um array com dados da variação ou null caso não encontre
     */
    public function getVariationForIdErp($idJN2)
    {
        return $this->_this->db
            ->select('prd_variants.*, products.product_id_erp, products.sku as sku_pai, products.has_variants')
            ->from('prd_variants')
            ->join('products', 'products.id = prd_variants.prd_id')
            ->where(
                array(
                    'prd_variants.variant_id_erp' => $idJN2,
                    'products.store_id' => $this->_this->store
                )
            )
            ->get()
            ->row_array();
    }


    /* Verifica os campos para validação
    *
    * @param   string  $key        Campo para validação
    * @param   string  $value      Valor do campo
    * @param   boolean $required   Se é um campo obrigatório ou não
    * @return  array               Retorna uma array com 2 posições, a primeira diz o status da validação, a segunda uma mensagem complementar
    */

    public function verifyFields($key, $value, $required, $type)
    {
/*        echo "\n L_723_key: ".$key;
        echo "\n value:";
        print_r($value);

        echo "\n required: ".$required . " -type: ".$type."\n";  */

        $value_ok = array(true, $this->setValueFormat($value, $type));

        /*if ($key === 'unidade' && $required) {
            if ($value === "" || (integer)$value <= 0) {
                $value_ok = array(true, '[""]');
            } elseif((integer)$value > 0){
                $value_ok = array(true, '["'.$value.'"]');
            } else {
                return array(false, "A unidade tem que ser ajustada, é necesário ter um valor!");
            }
        }*/

        if (($key === 'categoria' ||$key === 'category_imported')  && $required) {
/*            echo "\n L_742_key: ".$key;
            echo "\n L_743_required: ".$required;
            echo "\n L_744_value: ".print_r($value); */

            if (empty($value) || !is_int((integer)$value[0]) ) {
                $value_ok = array(true, '');
            } elseif(isset($value[0])){

                if(is_array($value) && count($value)>0){
                    $strCategorias = null;
                    foreach($value as $valoresCategoria){
                        if($valoresCategoria){
                            //busca informações da categoria
                            $JN2_URL = $this->getUrlJN2($this->_this->store);
                            $url = $JN2_URL.'rest/all/V1/categories/'.$valoresCategoria;
                            $dadosCategoria = json_decode(json_encode($this->_this->sendREST($url)));

                            if ($dadosCategoria->httpcode == 200) {
                                $objCategoriaDados = json_decode($dadosCategoria->content);
                                $nomeCategoria = $objCategoriaDados->name;

                                if($key === 'category_imported'){
                                    $strCategorias = !empty($strCategorias) ? ($strCategorias.','.$nomeCategoria) : $nomeCategoria;
                                } else {
                                    $numIdCategoria = $this->getCodeInfoProduct('categories', 'name', $nomeCategoria);
                                    $strIdCaterorias = !empty($strIdCaterorias) ? ($strIdCaterorias.','.$numIdCategoria) : $numIdCategoria;
                                }
                            }
                        } else {
                            $value_ok = array(true, '[""]');
                        }
                    }

                    if($key === 'category_imported'){
                        $value_ok = array(true, $this->setValueFormat($strCategorias, 'S'));
                    } else {
                        $value_ok = array(true, $this->setValueFormat("[\"{$strIdCaterorias}\"]", 'S'));
                    }
                    
                } else {
                    $value_ok = array(true, '[""]');
                }
            } else {
                return array(false, "A categoria tem que ser ajustada, é necesário ter um valor!");
            }
        }
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

                if ($sku == '')
                    return array(false, "Todas as variações precisam ter o código SKU preenchidos .");

                if (isset($type_v->estoqueAtual))
                    $stockReal[$id] = $type_v->estoqueAtual;

                // define o sku e id da variação
                $varArr[$keyVar]['sku']     = $sku;
                $varArr[$keyVar]['id']      = $id;
                $varArr[$keyVar]['preco']   = $preco;
                $varArr[$keyVar]['idMap']   = $type_v->idMapeamento ?? 0;

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
     * Atualiza o preço de um produto
     *
     * @param   string  $sku    SKU do produto
     * @param   float   $price  Preço do produto
     * @param   date    $dtAtualizacao  dtAtulização do produto
     * @param   string  $skuPai    SKU do produto Pai
     * @return  bool            Retorna o status da atualização
     */
    public function updatePrice($sku, $price, $dtAtualizacao, $skuPai=null)
    {
        $verifyProduct = $this->getVariationForSku($sku);
        if($verifyProduct)
            return $this->_this->db->where(array('sku' => $sku, 'id' => $verifyProduct['id']))->update('prd_variants', 
                array('price' => $price)) ? true : false;

        if($skuPai){
        // Atualiza o preço do produto
            return $this->_this->db->where(array('sku' => $skuPai, 'store_id' => $this->_this->store))->update('products', 
                array('price' => $price,
                        'date_update' => $dtAtualizacao)) ? true : false;
        }
    }

    /**
     * Atualiza o preço de um produto pai, vai atualizar pelo maior preço dos filhos por não ter no preço pai o configurável valor.
     *
     * @param   string  $sku    SKU do produto
     * @param   float   $price  Preço do produto
     * @param   date    $dtAtualizacao  dtAtulização do produto
     * @return  bool            Retorna o status da atualização
     */
    public function updatePricePai($sku, $price, $dtAtualizacao)
    {
        // Atualiza o preço do produto
        return $this->_this->db->where(array('sku' => $sku, 'store_id' => $this->_this->store))->update('products', 
            array('price' => $price,
                    'date_update' => $dtAtualizacao)) ? true : false;
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

        // não podeerá mais ser atualizado
        if ($this->getPrdIntegration($idProd))
            return array('success' => false, "Produto {$skuPai}, não pode mais receber novas variações pois já está integrado com o marketplace.");

        $tipoVarArr = array();
        $varArr     = array();
        $payload->grade = (array)$payload->grade;
        //$payload->grade = $typesVariations;
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

        $createVar = $this->_this->model_products->createvar(
            array(
                'prd_id'                => $idProd,
                'variant'               => $variant,
                'name'                  => $varStr,
                'sku'                   => $skuVar,
                'qty'                   => $qtyVar,
                'variant_id_erp'        => $payload->id,
                'price'                 => $preco,
                'image'                 => '',
                'status'                => 1,
                'EAN'                   => '',
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
            return array('success' => false, 'message' => "O SKU da variação não pode ser em branco.");

        if ($skuName == "")
            return array('success' => false, 'message' => "O Nome da variação não pode ser em branco.");

                
        $variationToUpdate = array(
            'name'  => $skuName,
            'sku'   => $skuVar,
            'price' => number_format($payload->preco, 2, ",", "."),
            'qty'   => $payload->_Estoque->estoqueDisponivel ?? 0,
        );

        // Inserir variação
        $sqlVar = $this->_this->db->update_string('prd_variants', $variationToUpdate, array('prd_id' => $idProdPai, 'sku' => $skuVar));
        $updateVar = $this->_this->db->query($sqlVar); // status da variação atualizada

        if (!$updateVar)
            return array('success' => false, 'message' => "Não foi possível atualizar a variação.");

        return array('success' => true);
    }

    /**
     * Atualização de produtos
     *
     * @param   object  $payload    Payload produto para atualização
     * @param   string  $skuPai     sku do Pai do produto filho
     * @param   object  $arrDadoReturnFilhos   variações para atualizar
     * @param   bolean  Se deve atualizar a variação, por ter filho.
     * @param   int     $prodIdConectala id banco conectala produto
     * @return  array               Retorna o status da atualização
     */
    public function updateProduct($payload, $skuPai = null, $arrDadoReturnFilhos = null, $bolTemFilho = false, $prodIdConectala = null)
    {
        if(!isset($payload->garantia)){
            $payload->garantia = 0;
        }

       // type - S=string, "F"=float, "I"=integer, "A"=array
       $product = array(
            'nome'              => array('value' => $payload->name, 'required' => true, 'type' => 'S', 'field_database' => 'name'),
            'sku'               => array('value' => $payload->sku, 'required' => true, 'type' => 'S', 'field_database' => 'sku'),
            //'unidade'           => array('value' => $payload->unidade, 'required' => true, 'type' => 'S', 'field_database' => 'attribute_value_id'),
            'cest'              => array('value' => '', 'required' => false, 'type' => 'S', 'field_database' => 'CEST'),
            'ncm'               => array('value' => isset($payload->ncm) ? $payload->ncm : 0, 'required' => true, 'type' => 'S', 'field_database' => 'NCM'),
            //'origem'            => array('value' => $payload->origem, 'required' => true, 'type' => 'I', 'field_database' => 'origin'),
            'ean'               => array('value' => isset($payload->ean) ? $payload->ean : '', 'required' => false, 'type' => 'S', 'field_database' => 'EAN'),
            'peso_liquido'      => array('value' => $payload->weight, 'required' => true, 'type' => 'F', 'field_database' => 'peso_liquido'),
            'peso_bruto'        => array('value' => $payload->weight, 'required' => true, 'type' => 'F', 'field_database' => 'peso_bruto'),
            'fabricante'        => array('value' => isset($payload->manufacturer) ? $payload->manufacturer : '', 'required' => true, 'type' => 'S', 'field_database' => 'brand_id'),
            'garantia'          => array('value' => $payload->garantia, 'required' => true, 'type' => 'I', 'field_database' => 'garantia'),
            // Ajustes Categorias p.category_id, p.category_imported
            //'imagesAnexo'       => array('value' => $payload->anexos, 'required' => true, 'type' => 'A', 'field_database' => null),
            //'variacoes'         => array('value' => $payload->variacoes ?? array(), 'required' => true, 'type' => 'A', 'field_database' => 'has_variants')
            'altura'            => array('value' => $payload->altura, 'required' => true, 'type' => 'F', 'field_database' => 'altura'),
            'comprimento'       => array('value' => $payload->comprimento, 'required' => true, 'type' => 'F', 'field_database' => 'profundidade'),
            'largura'           => array('value' => $payload->largura, 'required' => true, 'type' => 'F', 'field_database' => 'largura'),
            'descricao'         => array('value' => isset($payload->descricao) ? $payload->descricao: '' , 'required' => false, 'type' => 'S', 'field_database' => 'description'),
            'prazo_operacional_extra' => array('value' => $payload->prazo_operacional ?? 0, 'required' => true, 'type' => 'I', 'field_database' => 'prazo_operacional_extra'),
            //'categoria'         => array('value' => $payload->category_id ?? "", 'required' => true, 'type' => 'A', 'field_database' => 'category_id'),
            'category_imported' => array('value' => $payload->category_id ?? "", 'required' => true, 'type' => 'A', 'field_database' => 'category_imported'),
            'date_update'        => array('value' => $payload->updated_at, 'required' => true, 'type' => 'S', 'field_database' => 'date_update')
        );

        // Validar e formatar campos
        $productFormat = $this->getDataFormat($product);

        // Encontrou erro na formatação dos dados
        if (isset($productFormat['success']) && !$productFormat['success']) {            
            return array('success' => false, 'message' => array('Erro formatação dados do produto:', json_encode($productFormat)));
        }

        
        $productFormat['product_id_erp'] = $payload->id;
        $productFormat['sku'] = $payload->sku;
        $productFormat['store_id'] = $this->_this->store;
        
        if(isset($payload->has_variants)) {
            $productFormat['has_variants'] = $payload->has_variants;
        }
        
        $dataProduct = $this->getProductForSku($payload->sku);

        $strCaminhoImgPai = null;
        // upload de imagens
        if(isset($payload->path_images)){
            $path = $payload->path_images;

            if (isset($payload->anexos)) {
                // Faz upload e recupera os códigos
                $images = $this->getImages($payload->anexos, $path['path_complet']);
                if (!$images['success']) {
                    // encontrou erro no upload de imagem
                    echo $images['message'][0] . "\n";
                    return $images;
                }
            }

            $strCaminhoImgPai = $path['path_product'];
            $productFormat['image'] = $strCaminhoImgPai;
            
            if($payload->path_images && isset($payload->anexos[0])){
                $productFormat['principal_image'] = $images['primary'] ?? null;
            }
            
            if (($dataProduct['category_id'] != '[""]' || $productFormat['category_id'] != '[""]') &&  
                ($dataProduct['brand_id'] != '[""]' || $productFormat['brand_id'] != '[""]') &&
                $this->_this->uploadproducts->countImagesDir($dataProduct['image'])) {
                $productFormat['situacao'] = 2;
            } else {
                $productFormat['situacao'] = 1;
            }
        }
        
        
        // remove os itens que não deverão ser atualizados
        foreach ($this->naoAtualizar as $field)
            unset($productFormat[$field]);
        
//      Algum problema nessa função dos atributos que não entendi
/*        if ($this->getProductForAttributes($productFormat)){
            echo "\nL_1202_productFormat: ";
            print_r($productFormat);
            
            echo "\nL_1179_getprodcutForAttributes: ";
            print_r($this->getProductForAttributes($productFormat));
            
            return array('success' => false, 'message' => array('Problema nos atributos, verificar L_1397 - Produc.php', json_encode($productFormat)));
        } */
        
        $sqlProd = $this->_this->db->update_string('products', $productFormat, array('sku' => $payload->sku, 'store_id' => $this->_this->store));
        $update = $this->_this->db->query($sqlProd);

        // bloqueia produto se necessário
        $this->CI->blacklistofwords->updateStatusProductAfterUpdateOrCreate($this->getProductForSku($payload->sku), $dataProduct['id']);
        
        if ($update){
            if($bolTemFilho){
                $productUpdate = $this->atualizacaoCriacaoVariation($arrDadoReturnFilhos, $skuPai, $prodIdConectala, $strCaminhoImgPai);

                if(!$productUpdate['success']){                    
                    return array('success' => false, 'message' => array('Não foi possível atualizar o produto.', $productUpdate['message']));            
                }
            }
            
            if(!empty($productUpdate['message'])){
                return array('success' => true, 'message' =>$productUpdate['message']);    
            } else {
                return array('success' => true);
            }
        }

        
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
        $queryProd = $this->_this->db->get_where('products use index (store_sku)', array('store_id' => $this->_this->store, 'sku' => $skuProduct));
        $resultProd = $queryProd->row_array();

        $this->_this->db->where(
            array(
                'prd_id'    => $resultProd['id'],
                'sku'       => $skuVar,
            )
        );
        return $this->_this->db->update('prd_variants', $data) ? true : false;
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
            "Authorization: Bearer {$this->_this->token}",
        ));
        //"Authorization: {$this->token}",

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


    public function getStockVariationForSku($skuVar, $idProdConectala)
    {        
        $query = $this->_this->db->get_where('prd_variants', array('sku' => $skuVar, 'prd_id' => $idProdConectala));
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
    public function updateStockVariation($sku, $skuPai, $qty, $dtAtualizacao = null)
    {       
        $product = $this->getProductForSku($skuPai);

        if (!$product['id']){
            return false;
        }

        $variations = $this->_this->db->get_where('prd_variants', array('prd_id' => $product['id']))->result_array();

        // Atualiza o estoque da variação
        $this->_this->db->where(array('prd_id' => $product['id'], 'sku' => $sku))->update('prd_variants', array('qty' => $qty));
        //echo "\nL_1423_atualiza variação_qty: ".$qty." -product[id]: ".$product['id']." -sku: ".$sku;
        
        $newQty = 0;
        foreach ($variations as $variation) {
            if ($variation['sku'] == $sku) $variation['qty'] = $qty; // define a nova quantidade

            $newQty += (float)$variation['qty'];
        }

        //echo "\nL_1432_atualiza variação_newQty: ".$newQty." -skuPai: ".$skuPai;
        return $this->updateStockProduct($skuPai, $newQty, $dtAtualizacao) ? true : false;
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
    public function updateStockProduct($sku, $qty, $dtAtualizacao = null)
    {
        $this->_this->db->where(
            array(
                'sku'       => $sku,
                'store_id'  => $this->_this->store,
            )
        );

        return $this->_this->db->update('products', array('qty' => $qty, 'date_update' => $dtAtualizacao)) ? true : false;
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

        $list = $credentials->lista_JN2;

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
        $JN2_URL = $this->getUrlJN2($this->_this->store);
        foreach ($products as $product) {
            // Consulta endpoint par obter estoque            
            $url = $JN2_URL.'/api/estoques/';
            $data = "{$product},";

            if (function_exists('sendREST'))
                $dataPrice = json_decode($this->_this->sendREST($url, $data));
            else
                $dataPrice = json_decode($this->_sendREST($url, $data));

            // Ocorreu um problema
            if ($dataPrice->retorno->status != "OK") {
                if ($dataPrice->retorno->codigo_erro != 200) {
                    echo "Ocorreu um problema para obter o preço da produto_id_JN2={$product} retorno=" . json_encode($dataPrice) . "\n";
                    $this->_this->log_data('batch', 'Product/getStock', "Ocorreu um problema para obter o estoque do produto_id_JN2={$product} retorno=" . json_encode($dataPrice), "E");
                }
                return array('success' => false);
            }

            return array('success' => true, 'value' => (float)$dataPrice->retorno->registros[0]->registro->preco);
        }
    }

    public function validaCreateVariationJN2($payload, $skuPai)
    {
        // Pegar tipos de variações
        //$skuPai = str_replace("/", "-", $skuPai);
        $dataProduct = $this->getProductForSku($skuPai);
        $typesVariations = $dataProduct['has_variants'] == "" ? array() : explode(";", $dataProduct['has_variants']);
        $idProd = $dataProduct['id'];

        // não podeerá mais ser atualizado
        if ($this->getPrdIntegration($idProd))
            return array('success' => false, "Produto {$skuPai}, não pode mais receber novas variações pois já está integrado com o marketplace.");

        $tipoVarArr = array();
        $varArr     = array();
        $payload->grade = (array)$payload->grade;
        //$payload->grade = $typesVariations;
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

        if (count($payload->grade) != count($varArr)){
            return array('success' => false, 'message' => "Foram encontradas variações não compatíveis com umas das variações (Variações aceitas: Cor/Tamanho/Voltagem).");
        }

        if ((array_diff($typesVariations, $tipoVarArr) || array_diff($tipoVarArr, $typesVariations)) && count($typesVariations)){
            return array('success' => false, 'message' => "Foram encontradas tipos de variações para essa variação que não estão cadastradas no produto. Todas as variações devem conter os mesmos tipos (Cor, Tamanho e Voltagem) ");
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
            $this->updateSkuVariationForId($existVarSkuDiff[0]['id'], $payload->sku, $payload->id);
            //return array('success' => false, 'message' => "Foi encontrada uma variação de sku diferente, com os mesmos valores de uma variação já existente para esse produto.");
            return array('success' => true, 'message' => $varStr);
        }

        // Já existir esse sku, mas com valores diferentes
        if ($this->getVariantsForIdAndSku($idProd, $payload->sku))
            return array('success' => false, 'message' => "Foi encontrada uma variação com o mesmo sku, mas com valores diferentes para esse produto.");

        $skuVar = $payload->sku;
        if ($skuVar == "")
            return array('success' => false, 'message' => "O SKU da variação não pode ser em branco.");


        // recuperar todas as variações para definir o 'variant'
        $variant = null;
        foreach ($this->getVariantProduct($idProd) as $varReal) {
            $variantReal = (int)$varReal['variant'];

            if ($variant && $variantReal > $variant) $variant = $variantReal;
            if (!$variant) $variant = $variantReal;
        }
        if ($variant === null) $variant = 0;
        else $variant++;

        return array('success' => true, 'message' => $varStr, 'variant' => $variant);
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
        $this->_this->db->where('id', $idVar);
        return $this->_this->db->update('prd_variants', array('sku' => $newSku, 'variant_id_erp' => $idProd)) ? true : false;
    }
    
     public function ajustarDataHoraMenorMaior($dataHora, $strDiminuirAumentarHora){
        //strDiminuirAumentarHora = '-1 hour' #diminuir 1 hora da data hora passada.
        $dtFormatado = strtotime($dataHora);
        $dtHoraAjuste = date('Y-m-d H:i:s', strtotime($strDiminuirAumentarHora, $dtFormatado));

        return $dtHoraAjuste;
}
    
    public function maiorPrecoVariacaoProdutoPai($store_id, $product_id_erp){
        $sql = "select max(cast(prd_variants.price as decimal(10,2))) as maior_preco ";
        $sql .= "from	prd_variants inner join products on(products.id = prd_variants.prd_id) ";
        $sql .= "where	products.store_id = ? and products.product_id_erp = ?";
        
        $query = $this->_this->db->query($sql, array($store_id, $product_id_erp));
        $row = $query->row_array();
        return $row['maior_preco'];
    }
    
    
    /**
     * Atualização ou Criação da variação se for adicionada.
     *
     * @param   object  $payload    Payload da variação para cadastro
     * @return  array               Retorna o status do cadastro
     */
    public function atualizacaoCriacaoVariation($payload, $skuPai, $idProdPai, $strCaminhoImgPai = null)
    {
        $strMsgUpdate = null;
        
        $skuVar = $payload['sku'];
        if ($skuVar == "")
            return array('success' => false, 'message' => "O SKU da variação não pode ser em branco.");
            
        // recuperar todas as variações para definir o 'variant'
        $variant = null;
        foreach ($this->getVariantProduct($idProdPai) as $varReal) {
            $variantReal = (int)$varReal['variant'];

            if ($variant && $variantReal > $variant) $variant = $variantReal;
            if (!$variant) $variant = $variantReal;
        }
        if ($variant === null) $variant = 0;
        else $variant++;
        
        
        // Já existir esse sku, mas com valores diferentes, deve atualizar a variação.
        $retDadosVariacao = $this->getVariantsForIdAndSku($idProdPai, $payload['sku']);
        
        if ($retDadosVariacao){
            //Vai verificar se não tiver imagem nas variações e tiver no endopoint vai atualizar.
            if($payload['caminho_img_pai']){
                $dirImageVariacao = $payload['caminho_img_pai'].'/'.$retDadosVariacao[0]['image'];
                
                if ($this->_this->uploadproducts->countImagesDir($dirImageVariacao) == 0){
                    $path_complet = 'assets/images/product_image/'.$dirImageVariacao;

                    if (isset($payload['img_filho'])) {
                        // Faz upload e recupera os códigos
                        $urlImagens = 'https://beta1.boostcommerce.com.br/media/catalog/product';

                        foreach($payload['img_filho'] as $strImagem){
                            $arrImagemFilho[] = $urlImagens.$strImagem;
                        }

                        $images = $this->getImages($arrImagemFilho, $path_complet);

                        if (!$images['success']) {
                            $msgErro = "Erro ao gravar imagem: ".$images['message'][0];
                            echo $msgErro. "\n";
                            return array('success' => false, 'message' => $msgErro);
                        }
                    }
                }
            }
            
            $variationToUpdate = array(
                'name'  => $payload['strValorVariacao'],
                'sku'   => $skuVar,
                'EAN'   => $payload['ean'],
                'codigo_do_fabricante'   => $payload['codigo_do_fabricante']
            );


            // Inserir variação  $idProd -> id do Produto Pai
            $sqlVar = $this->_this->db->update_string('prd_variants', $variationToUpdate, array('prd_id' => $idProdPai, 'sku' => $skuVar));
            
            $updateVar = $this->_this->db->query($sqlVar); // status da variação atualizada

            if (!$updateVar){
                return array('success' => false, 'message' => "Não foi possível atualizar a variação. idProdPai={$idProdPai}, variant_id_erp={$payload['id']}");
            } else {
                $variationToUpdate = json_encode($variationToUpdate);
                $strMsgUpdate = "variationToUpdate: {$variationToUpdate}";
            }
        } else {
            $qtyVar = !empty($payload['qty']) ? $payload['qty'] : 0;
            
            if(isset($payload['price'])){
                $preco  = number_format($payload['price'], 2, ".", "") ?? 0;
            } else {
                $preco = 0;
            }
            
            if($strCaminhoImgPai){
                $strCaminhoImgFilho = $this->imagemFilho($strCaminhoImgPai, $payload['img_filho']);
            }
            
            //se não existe a variação, for nova irá acrescentar
            $createVar = $this->_this->model_products->createvar(
                array(
                    'prd_id'                => $idProdPai,
                    'variant'               => $variant,
                    'name'                  => $payload['strValorVariacao'],
                    'sku'                   => $skuVar,
                    'qty'                   => $qtyVar,
                    'variant_id_erp'        => $payload['id'],
                    'price'                 => $preco,
                    'image'                 => $strCaminhoImgFilho ?? '',
                    'status'                => 1,
                    'EAN'                   => $payload['ean'],
                    'codigo_do_fabricante'  => $payload['codigo_do_fabricante']
                )
            );

            if (!$createVar){
                return array('success' => false, 'message' => "Não foi possível inserir a variação. idProdPai={$idProdPai}, variant_id_erp={$payload['id']}");
            } else {
                $createVar = json_encode($createVar);
                $strMsgUpdate = "createVar: {$createVar}, idProdPai={$idProdPai}, variant_id_erp={$payload['id']}";
            }
        }

        // Recuperar o estoque das variações para atualizar o produto pai
        $qtyAllVariations = $this->getStockVariation($idProdPai);
        
        // atualiza estoque do produto, podendo ser alterado o has_variants também
        $dataUpdateProd['qty'] = $qtyAllVariations;
        $updateProduct = $this->updateProductForSku($skuPai, $dataUpdateProd);

        if (!$updateProduct)
            return array('success' => false, 'message' => "Não foi possível atualizar o estoque do produto pai.");

        return array('success' => true, 'message'=>$strMsgUpdate);
    }
    
     /* Consulta dados de campos da variação
     *
     * @param   integer     $idJN2  Código da variação no JN2
     * @return  null|array              Retorna um array com dados da variação ou null caso não encontre
     */
    public function getVariationForProductIdErp($idProductIdErp)
    {
        return $this->_this->db
            ->select('prd_variants.*, products.product_id_erp, products.sku as sku_pai, products.has_variants')
            ->from('prd_variants')
            ->join('products', 'products.id = prd_variants.prd_id')
            ->where(
                array(
                    'products.product_id_erp'   => $idProductIdErp,
                    'products.store_id'         => $this->_this->store
                )
            )
            ->get()
            ->row_array();
    }
    
    /* Busca dados da variação pelo variant_id_erp (id endpoint fiho), e prd_id (id do pai no banco existente).
     *
     * @param   integer     $idJN2      Código da variação no JN2
     * @param   integer     $prodIdConectala     Código do produto Pai conectala
     * @return  null|array              Retorna um array com dados da variação ou null caso não encontre
     */
    public function getVariationForIdErpPrdId($idJN2, $prodIdConectala)
    {
        return $this->_this->db
            ->select('prd_variants.*, products.product_id_erp, products.sku as sku_pai, products.has_variants, products.image as imagePai')
            ->from('prd_variants')
            ->join('products', 'products.id = prd_variants.prd_id')
            ->where(
                array(
                    'products.store_id' => $this->_this->store,
                    'prd_variants.variant_id_erp' => $idJN2,
                    'prd_variants.prd_id' => $prodIdConectala
                )
            )
            ->get()
            ->row_array();
    }
    
    /* Busca todos filhos da variação pelo products.product_id_erp
     *
     * @param   integer     $idProductIdErp      Código do produto Pai na JN2
     * @return  null|array              Retorna um array com dados da variação ou null caso não encontre
     */
    public function consultaFilhosProdutoPai($idProductIdErp){
        $sql = "select	pv.id, pv.prd_id, pv.variant, pv.name, pv.sku, pv.status, pv.variant_id_erp, products.image, ";
        $sql .= "products.product_id_erp, products.sku as sku_pai, products.has_variants, products.id as products_id, products.date_update ";
        $sql .= "from	prd_variants pv ";
        $sql .= "inner join products on(products.id = pv.prd_id)";
        $sql .= "where	products.product_id_erp   = ? ";
        $sql .= "and	products.store_id         = ".$this->_this->store;

        $query = $this->_this->db->query($sql, $idProductIdErp);
        return $query->result_array();
    }
    
    public function removeVariacaoDoProdutoDesvincular($idVariacao, $prdId, $variantIdErp){
        if($prdId) {
            $this->_this->db->where(array('id' => $idVariacao, 'prd_id' => $prdId, 'variant_id_erp' => $variantIdErp));
            $delete = $this->_this->db->delete('prd_variants');
            return ($delete == true) ? true : false;			
        }
    }
    
    /* Consulta tipos de variações do filho
     *
     * @param   $dadosCustomAttributes  Dados das variações do endPoint JN2
     * @param   $JN2_URL                url do jn2
     * @param   $strProdImagempai       caminho da pasta de imagens do produto pai
     * @return  null|array              Retorna um array com dados da variação ou null caso não encontre
     */
    public function buscaTiposVariacoesFilho($dadosCustomAttributes, $JN2_URL, $bolTemFilho = false){
        $hasVariantAtualiza = null;
        $arrAtributosCamposBuscar = null;
        $altura = null;
        $comprimento = null;
        $largura = null;
        $ean = '';
        $tamanhoCod = null;
        $fabricanteCod = null;
        $prazo_operacional = null;
        $descricao = '';
        $ncm = null;
        $meta_descricao = '';
        $garantia = 0;
        $arrCategorias = null;
        $imagem = null;
                
        foreach ($dadosCustomAttributes as $attributes){            
            switch ($attributes->attribute_code) {
                case "volume_height":
                    $altura = $attributes->value;
                    $registro['altura'] = $altura;
                    break;
                case "volume_length":
                    $comprimento = $attributes->value;
                    $registro['comprimento'] = $comprimento;
                    break;
                case "volume_width":
                    $largura = $attributes->value;
                    $registro['largura'] = $largura;
                    break;
                case "description":
                    $descricao = $attributes->value;
                    $registro['descricao'] = $descricao;
                    break;
                case "meta_description":
                    $registro['meta_descricao'] = $attributes->value;
                    break;
                case "lead_time":
                    $prazo_operacional = $attributes->value;
                    $registro['prazo_operacional'] = $prazo_operacional;
                    break;
                case "category_ids":
                    $arrCategorias = $attributes->value;
                    if(count($arrCategorias) >0){
                        $registro['category_id'] = $arrCategorias;
                        $registro['category_imported'] = $arrCategorias;
                    } else {
                        $registro['category_id'] = '[]';
                        $registro['category_imported'] = '';
                    }
                    break;
                case "ncm":
                    $ncm = $attributes->value;
                    $registro['ncm'] = $ncm;
                    break;
                case "garantia":
                    $garantia = $attributes->value;
                    $registro['garantia'] = $garantia;
                    break;
                case "ean":
                    $ean = $attributes->value;
                    $registro['ean'] = $ean;
                    break;

                case "color":
                    $CorCod = $attributes->value;
                    $arrAtributosCamposBuscar['color'] = $CorCod;
                    $hasVariantAtualiza = empty($hasVariantAtualiza) ? 'Cor' : $hasVariantAtualiza.';Cor';
                    break;
                case "tamanho":
                    $tamanhoCod = $attributes->value;
                    $arrAtributosCamposBuscar['tamanho'] = $tamanhoCod;
                    $hasVariantAtualiza = empty($hasVariantAtualiza) ? 'TAMANHO' : $hasVariantAtualiza.';TAMANHO';
                    break;
                case "voltagem":
                    $voltagem_cod = $attributes->value;
                    $arrAtributosCamposBuscar['voltagem'] = $voltagem_cod;
                    $hasVariantAtualiza = empty($hasVariantAtualiza) ? 'VOLTAGEM' : $hasVariantAtualiza.';VOLTAGEM';
                    break;
                case "manufacturer":
                    $fabricanteCod = $attributes->value;
                    $arrAtributosCamposBuscar['manufacturer'] = $fabricanteCod;
                    break;
            } 
        }
        
        if($arrAtributosCamposBuscar){
            $strValoresVariacao = null;
            $retornaTiposValoresVariacao = null;
            
            //Buca os valores dos atributos de campos
            foreach($arrAtributosCamposBuscar as $chave => $valor){
                $url = $JN2_URL .'rest/all/V1/products/attributes/'.$chave;
                $dataAtributos = json_decode(json_encode($this->_this->sendREST($url, '')));

                if ($dataAtributos->httpcode != 200) {
                    echo "\n Erro para buscar os atribudos da url={$url}, retorno=" . json_encode($dataAtributos) . "\n";
                    $this->_this->log_data('batch', 'Product/updateProduct', "Erro para buscar a lista de url={$url}, retorno=" . json_encode($dataAtributos), "E");
                    return array('success' => false, 'message' => 'Ocorreu um problema ao buscar os atributos do produto!');
                } else {
                    $infAtributosProduto = json_decode($dataAtributos->content);
                    $arrObjOptions = $infAtributosProduto->options;

                    if($arrObjOptions){
                        foreach($arrObjOptions as $chaveOption => $valueOption){
                            if($valueOption->value == $valor){
                                $$chave = $valueOption->label;
                                $strValor = trim($valueOption->label);

                                if($chave == 'color' || $chave == 'tamanho' || $chave == 'voltagem'){
                                    if($chave == 'voltagem'){
                                        // Se não tiver 'V' no número da voltagem, adicionar
                                        $unityVoltage = strpos($strValor, 'V');
                                        if (!$unityVoltage) $strValor .= 'V';
                                    }
                                    
                                    $strValoresVariacao = empty($strValoresVariacao) ? $strValor : $strValoresVariacao.';'.$strValor;
                                } else {
                                    $registro[$chave] = $strValor;
                                    //$retornaTiposValoresVariacao[$chave] = $strValor;
                                }
                            }
                        }
                    }
                }
            }
            
            $retornaTiposValoresVariacao[0] = $hasVariantAtualiza;
            $retornaTiposValoresVariacao[1] = $strValoresVariacao;
            $retornaTiposValoresVariacao[2] = $registro;
            return $retornaTiposValoresVariacao;
        } else {            
            if($registro){
                $retornaTiposValoresVariacao[0] = null;
                $retornaTiposValoresVariacao[1] = null;
                $retornaTiposValoresVariacao[2] = $registro;
                return $retornaTiposValoresVariacao;
            } else {
                return null;
            }
        }
    }
    
    public function verificarPrecoEPrecoEspecial($custom_attributes, $priceEndPoint){
        if(isset($custom_attributes))
        {
            foreach ($custom_attributes as $attributes){
                switch ($attributes->attribute_code) {
                    case "special_price":
                        $special_price = number_format($attributes->value, 2, ".", "");
                        break;
                    case "special_from_date":
                        $special_from_date = $attributes->value;
                        break;
                    case "special_to_date":
                        $special_to_date = $attributes->value;
                        break;
                }
            }

            if(isset($special_price)){
                $today = date("Y-m-d H:i:s");

                if (isset($special_from_date) && $today >= $special_from_date && $today <= $special_to_date){
                    $price = $special_price;
                } else {
                    $price  = number_format($priceEndPoint, 2, ".", "");
                }
            } else {
                $price  = number_format($priceEndPoint, 2, ".", "");
            }
        }
        else {
            $price  = number_format($priceEndPoint, 2, ".", "");
        }
        
        return $price;
    }
    
    public function consultarQuantidadeEstoque($skuProduct, $JN2_URL, $log_name){
        //vai fazer endpoint pra verificar a quantidade, Estoque
        $urlQtd = $JN2_URL .'rest/all/V1/stockItems/'.$skuProduct;
        $dataProductsQtd = json_decode(json_encode($this->_this->sendREST($urlQtd, '')));
        $quantidadeJn2 = null;
        
        if ($dataProductsQtd->httpcode != 200) {
            $msgQuantidade = "Erro para buscar a quantidade do produto: url={$urlQtd}, retorno=" . json_encode($dataProductsQtd);
            
            echo "Erro para buscar o estoque da lista.".$msgQuantidade. "\n";
            $this->_this->log_data('batch', $log_name, $msgQuantidade, "W");
            
            return array('success' => false, 'message' => $msgQuantidade);
        } else {
            $dataProductsContentQtd = json_decode($dataProductsQtd->content);

            if(isset($dataProductsContentQtd)){
                if(isset($dataProductsContentQtd->qty)){
                    $quantidadeJn2 = $dataProductsContentQtd->qty;
                } else {
                    $quantidadeJn2 = null;
                }
            } else {
                $quantidadeJn2 = null;
            }
        }
        
        return $quantidadeJn2;
    }
    
    public function cadastrarVariacoes($arrVariacoes, $prdId, $productIdErp, $skuPai, $bolSomenteVariacao = false, $strCaminhoImgPai){
        //Se houver variações vai cadastra o(s) filho(s) na prd_variants, se já houver Pai, cadastra só a vairação
        if($arrVariacoes){
            $produtoPaiNovoEstoque = 0;
            
            foreach ($arrVariacoes as $keyFilho => $prodVar) {
                // recuperar todas as variações para definir o 'variant'
                $variant = null;
                foreach ($this->getVariantProduct($prdId) as $varReal) {
                    $variantReal = (int)$varReal['variant'];

                    if ($variant && $variantReal > $variant) $variant = $variantReal;
                    if (!$variant) $variant = $variantReal;
                }
                if ($variant === null) $variant = 0;
                else $variant++;

                if($strCaminhoImgPai){
                    if(isset($prodVar['image_filho'])){
                        $imagensFilho = $prodVar['image_filho'];
                    } else {
                        $imagensFilho = null;
                    }
                    
                    $strPastaImgFilho = $this->imagemFilho($strCaminhoImgPai, $imagensFilho);
                    
                    if(!$strPastaImgFilho){
                        return array('success' => false, $strPastaImgFilho['message']);
                    }
                }

                //Continuar vendo dados do Array preenchendo dados para vairações.
                $createVar = $this->_this->model_products->createvar(
                    array(
                    'prd_id'                => $prdId ?? 0,
                    'variant'               => $variant,
                    'name'                  => $prodVar['name'] ?? '',
                    'sku'                   => $prodVar['sku'] ?? '',
                    'price'                 => $prodVar['price'] ?? 0,
                    'qty'                   => $prodVar['qty'] ?? 0,
                    'image'                 => $strPastaImgFilho ?? '',
                    'status'                => 1,
                    'EAN'                   => $prodVar['EAN'] ?? '',
                    'codigo_do_fabricante'  => $prodVar['codigo_do_fabricante'] ?? '',
                    'variant_id_erp'        => $keyFilho ?? 0
                    )
                );
                
                if (!$createVar){
                    return array('success' => false, 'message' => "Não foi possível inserir a variação, sku:".$prodVar['sku'].", keyFilho: ".$keyFilho.", prd_id:".$prdId);
                } else {
                    if($bolSomenteVariacao){
                        $dtHoraAtual = date("Y-m-d H:i:s");
                        $this->updateStockVariation($prodVar['sku'], $skuPai, $prodVar['qty'], $dtHoraAtual);
                    } else {
                        $produtoPaiNovoEstoque += $prodVar['qty'];
                    }
                }
            }
            
            if($bolSomenteVariacao == false){
                $this->updateStockProduct($skuPai, $produtoPaiNovoEstoque);
            }
            
            //vai verificar tds registro pelo produto pai, seus filos para ver o maior no banco.
            $maiorPreco = $this->maiorPrecoVariacaoProdutoPai($this->_this->store, $productIdErp);

            $dtHoraAtual = date("Y-m-d H:i:s");
            $this->updatePricePai($skuPai, $maiorPreco, $dtHoraAtual);
            
            return array('success' => true, 'prd_id' => $prdId);
        }
    }
    
    public function imagemFilho($strCaminhoImgPai, $arrImagem){
        $strImgPastaFilho = null;
        
        if($strCaminhoImgPai){
            // upload de imagens
            $pathFilho = null;
            $pathFilho = $this->getPathNewImage($strCaminhoImgPai); // folder que receberá as imagens

            if($arrImagem){
                if(isset($arrImagem)){
                    // Faz upload e recupera os códigos
                    $urlImagens = 'https://beta1.boostcommerce.com.br/media/catalog/product';
                    
                    foreach($arrImagem as $strImagem){
                        $arrImagemFilho[] = $urlImagens.$strImagem;
                    }
                    
                    $images = $this->getImages($arrImagemFilho, $pathFilho['path_complet']);
                    
                    if (!$images['success']) {
                        // encontrou erro no upload de imagem
                        echo "\nErro carregar imagens: ".$images['message'][0];
                         return array('sucess'=>false, 'message'=>$images);
                    }
                }

                $strImgPastaFilho = $pathFilho['path_product'];
            } else {
                // upload de imagens -- sempre grava a pasta se tem ou não imagens.               
                $strImgPastaFilho = $pathFilho['path_product'] ?? '';
            }
        }
        
        return $strImgPastaFilho;
    }
}