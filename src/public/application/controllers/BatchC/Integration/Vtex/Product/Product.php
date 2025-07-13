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
        'medidas'   => 'TAMANHO',

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
        'vd' => 'UN'
    );

    // tipo de voltagens aceitas para comparar, INFORMAR APENAS NÚMERO E BIVOLT EM MINÚSCULO
    private $valuesAcceptVoltageVariation = array(
        110      => 110,
        120      => 110,
        127      => 110,
        220      => 220,
        230      => 220,
        240      => 220,
        'bivolt' => 'Bivolt',
        'biv'    => 'Bivolt'
    );

    private $naoAtualizar = array(
        'situacao',
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
        set_time_limit(600);
    }

    /**
     * Recupera dados do produto pelo código da Tiny
     *
     * @param   integer     $idTiny Código do produto na tiny
     * @return  null|array          Retorna um array com dados do produto ou null caso não encontre
     */
    public function getProductForIdErp($idTiny, $sku = null)
    {
        $query = $this->_this->db->get_where('products',
            array(
                'store_id'       => $this->_this->store,
                'product_id_erp' => $idTiny
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
     * Recupera dados do produto pelo SKU do produto
     *
     * @param   string      $sku    SKU do produto
     * @return  null|array          Retorna um array com dados do produto ou null caso não encontre
     */
    public function getProductForSku($sku)
    {
        return $this->_this->db->get_where('products',
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
        $this->_this->db->where(
            array(
                'sku'       => $sku,
                'store_id'  => $this->_this->store,
            )
        );
        return $this->_this->db->update('products', $data) ? true : false;
    }

    /**
     * Cria um novo produto
     *
     * @param   object  $payload    Payload produto para cadastro
     * @return  array               Retorna o status do cadastro ou array de mapemaneto caso seja webhook
     */
    public function createProduct($payload, $countSku = null)
    {
        // type - S=string, "F"=float, "I"=integer, "A"=array
        // peso sempre em grama
        // medidas sempre em centímetros

        $productSku = $this->getDataProductERP($countSku === null ? $payload->items[0]->itemId : $payload->items[$countSku]->itemId);

        if (!isset($productSku->SalesChannels) || !in_array($this->_this->salesChannel, (array)$productSku->SalesChannels))
            return array('success' => null);

        // garantia
        $guarantee = 0;
        if (isset($payload->Garantia[0]) || isset($payload->garantia[0])) {
            $guaranteeValid = $payload->Garantia[0] ?? $payload->garantia[0];
            $guarantee = (int)filter_var($guaranteeValid, FILTER_SANITIZE_NUMBER_INT);

            if ($this->likeText("%ano%", strtolower($guaranteeValid))) {
                $guarantee *= 12;
            } elseif ($this->likeText("%mes%", strtolower($guaranteeValid))) {
                //$guarantee *= 1;
            }
        }

        $priceERP = $this->getPriceErp($countSku === null ? $payload->items : $payload->items[$countSku]->itemId);

        if (!$priceERP) $priceERP = 0;

        $category = (array)$payload->categories;
        $product = array(
            'nome'              => array('value' => $countSku === null ? $payload->productName : $payload->items[$countSku]->nameComplete, 'required' => true, 'type' => 'S', 'field_database' => 'name'),
            'status'            => array('value' => 1, 'required' => true, 'type' => 'I', 'field_database' => 'status'),
            'sku'               => array('value' => $countSku === null ? 'P_'.$payload->productId : $payload->items[$countSku]->itemId, 'required' => true, 'type' => 'S', 'field_database' => 'sku'),
            'un'                => array('value' => $countSku === null ? $payload->items[0]->measurementUnit : $payload->items[$countSku]->measurementUnit , 'required' => true, 'type' => 'S', 'field_database' => 'attribute_value_id'),
            'preco'             => array('value' => $priceERP, 'required' => true, 'type' => 'F', 'field_database' => 'price'),
            'ncm'               => array('value' => '', 'required' => true, 'type' => 'S', 'field_database' => 'NCM'), // não tenho certeza
            'origem'            => array('value' => 0, 'required' => true, 'type' => 'I', 'field_database' => 'origin'),
            'ean'               => array('value' => $countSku === null ? $payload->items[0]->ean : $payload->items[$countSku]->ean, 'required' => true, 'type' => 'S', 'field_database' => 'EAN'),
            'peso_liquido'      => array('value' => $productSku->Dimension->weight/1000, 'required' => true, 'type' => 'F', 'field_database' => 'peso_liquido'),
            'peso_bruto'        => array('value' => $productSku->Dimension->weight/1000, 'required' => true, 'type' => 'F', 'field_database' => 'peso_bruto'),
            'sku_fabricante'    => array('value' => $productSku->ManufacturerCode, 'required' => true, 'type' => 'S', 'field_database' => 'codigo_do_fabricante'),
            'descricao'         => array('value' => $payload->description, 'required' => true, 'type' => 'S', 'field_database' => 'description'),
            'garantia'          => array('value' => $guarantee, 'required' => true, 'type' => 'I', 'field_database' => 'garantia'),
            'fabricante'        => array('value' => $payload->brand, 'required' => true, 'type' => 'S', 'field_database' => 'brand_id'),
            'altura'            => array('value' => $productSku->Dimension->height, 'required' => true, 'type' => 'F', 'field_database' => 'altura'),
            'comprimento'       => array('value' => $productSku->Dimension->length, 'required' => true, 'type' => 'F', 'field_database' => 'profundidade'),
            'largura'           => array('value' => $productSku->Dimension->width, 'required' => true, 'type' => 'F', 'field_database' => 'largura'),
            //'categoria'         => array('value' => implode(' > ', array_reverse((array)$payload->categories)), 'required' => true, 'type' => 'S', 'field_database' => 'category_imported'),
            'categoria'         => array('value' => ltrim(rtrim($category[0] ?? '','/'),'/'), 'required' => true, 'type' => 'S', 'field_database' => 'category_imported'),
            'images'            => array('value' => $productSku->Images, 'required' => true, 'type' => 'A', 'field_database' => NULL),
            'variacoes'         => array('value' => $countSku === null ? $payload->items : array(), 'required' => true, 'type' => 'A', 'field_database' => 'has_variants'),
            'prazo_operacional_extra' => array('value' => 1, 'required' => true, 'type' => 'I', 'field_database' => 'prazo_operacional_extra')
        );

        // Validar e formatar campos
        $productFormat = $this->getDataFormat($product, true);

        // Encontrou erro na formatação dos dados
        if (isset($productFormat['success']) && !$productFormat['success'])
            return $productFormat;

        $productFormat['product_id_erp'] = $productFormat['sku'];

        // upload de imagens
        $path = $this->getPathNewImage(); // folder que receberá as imagens
        // Faz upload e recupera os códigos
        $images = $this->getImages($productFormat['image'], $path['path_complet']);
        if (!$images['success'])
            return $images;

        //define nome da pasta com as imagens e a imagem principal
        $productFormat['image'] = $path['path_product'];
        $productFormat['principal_image'] = $images['primary'];
        //$productFormat['qty'] = $qtyProduct;

        if ($countSku === null) {
            $productFormat['qty'] = array_sum($productFormat['has_variants']['estoque']);
        } else {
            $qtyProduct = $this->getStock($payload->items[$countSku]->itemId);
            $productFormat['qty'] = $qtyProduct === false ? 0 : $qtyProduct;
        }

        // recupera as variações para inserção e define o has_variants com os tipos
        $variationsProduct = $productFormat['has_variants'];
        $productFormat['has_variants'] = $productFormat['has_variants']['tipos'];

        // Inserção produto e pegar id para passar nas variações
        $prd_id = $this->_this->model_products->create($productFormat);

        // bloqueia produto se necessário
        $this->CI->blacklistofwords->updateStatusProductAfterUpdateOrCreate($productFormat, $prd_id);

        // formatar variações para criação
        foreach ($variationsProduct['variacoes'] as $key => $variacao) {
            $skuVar         = $variacao['sku'];
            $idVar          = $variacao['id'];
            $precoVar       = $variacao['preco'] == 0 ? $productFormat['price'] : $variacao['preco'];
            $eanVar         = $variacao['ean'];
            $qtyVar         = $variationsProduct['estoque'][$variacao['id']];
            $varStr         = "";
            $images         = $variacao['image'];
            $pathImageVar   = md5(round(microtime(true) * 100000).rand(11111111,99999999));

            if (empty($precoVar)) $precoVar = $productFormat['price'];

            if (array_key_exists('TAMANHO', $variacao['variacao']))   $varStr .= ";{$variacao['variacao']['TAMANHO']}";
            if (array_key_exists('Cor', $variacao['variacao']))       $varStr .= ";{$variacao['variacao']['Cor']}";
            if (array_key_exists('VOLTAGEM', $variacao['variacao'])) {
                $varStr .= ";{$variacao['variacao']['VOLTAGEM']}";
                // Se não tiver 'V' no número da voltagem, adicionar
                $unityVoltage = strpos( strtoupper($variacao['variacao']['VOLTAGEM']), 'V' );
                if (!$unityVoltage) $varStr .= 'V';
            }

            $varStr = substr($varStr,1);
            $verification_sku=$this->verifyFieldsProduct('sku',$skuVar,true,'S',true,$prd_id);
            if(!$verification_sku[0]){
                return array('success' => $verification_sku[0], 'message' => array($verification_sku[1]));
            }
            $verification_ean=$this->verifyFieldsProduct('ean',$eanVar,true,'S',true);
            if(!$verification_ean[0]){
                if ($this->CI->model_settings->getStatusbyName('products_require_ean') == 1) {
                    return array('success' => $verification_ean[0], 'message' => array($verification_ean[1]));
                }
                $eanVar = '';
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
                    'image'                 => $pathImageVar,
                    'status'                => 1,
                    'EAN'                   => $eanVar,
                    'codigo_do_fabricante'  => '',
                )
            );

            // Upload de imagens
            $uploadImgVar = $this->uploadImageVariation($images, $productFormat['image'], $pathImageVar, true);

            // Erro em upload de imagem
            if ($uploadImgVar['error'] != 0) {
                if ($uploadImgVar['error'] == 1 || $uploadImgVar['error'] == 2)
                    return array('success' => false, 'message' => array($uploadImgVar['data']));

                return array('error' => true);
            }
        }

        if ($prd_id)
            return array('success' => true);

        return array('success' => false, 'message' => array('Não foi possível integrar o produto, em breve será integrado.'));
    }

    private function uploadImageVariation($arrImg, $pathProducts, $pathVariation, $newImage)
    {
        $errorImage = array('error' => 0);
        $serverpath = $_SERVER['SCRIPT_FILENAME'];
        $pos = strpos($serverpath,'assets');
        $serverpath = substr($serverpath,0,$pos);
        $targetDir = $serverpath . "assets/images/product_image/{$pathProducts}/{$pathVariation}";
        if ($newImage && !file_exists($targetDir)) {
            // cria o diretorio para o produto receber as imagens
            $oldmask = umask(0);
            mkdir($targetDir, 0775);
            umask($oldmask);
        }

        if ($arrImg === null) return $errorImage;

        foreach ($arrImg as $image) {
            if ($image == "") continue;

            $upload = $this->_this->uploadproducts->sendImageForUrl("{$targetDir}/", $image);
            if ($upload['success'] == false) {
                $errorImage = array('error' => 2, 'data' => $upload['data']);
                break;
            }
        }

        return $errorImage;
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
        $primaryImage = null;
        $arrNameImages = array();

        foreach ($arrImages as $images) {
            foreach ($images as $image) {
                $url =  $image->ImageUrl;
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
            mkdir($targetDir, 0775);
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
        echo(json_encode($product['nome'])."\n");
        foreach ($product as $key => $field) {
            if (in_array($key, ['categoria','un','unidade', 'fabricante', 'variacoes', 'garantia', 'comprimento', 'largura', 'altura'])) {
                $field_format = $this->verifyFields($key, $field['value'], $field['required'], $field['type'], $newProduct);
            }else{
                $field_format = $this->verifyFieldsProduct($key, $field['value'], $field['required'], $field['type'], $newProduct);
            }
            // encontrou um erro, deve encerrar a criação do produto e apresentar o motivo
            if (!$field_format[0]) {
                if (strtolower($key) == 'ean') {
                    if ($this->CI->model_settings->getStatusbyName('products_require_ean') == 1) {
                        array_push($erros, $field_format[1]);
                    }
                    $field_format[1] = '';
                } else {
                    array_push($erros, $field_format[1]);
                }
            }

            if ($key == "images")
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
     * @param   int $product    ID do produto para serem consultado estoque
     * @return  int             Retorna um array com o estoque total e estoque separado por ID
     */
    public function getStock(int $product): int
    {
        // Consulta endpoint par obter estoque
        $url = "api/fulfillment/pvt/orderForms/simulation?affiliateId={$this->_this->affiliateId}&sc={$this->_this->salesChannel}";
        $body = array(
            'items' => array(
                array(
                    'id'        => $product,
                    'quantity'  => 1,
                    'seller'    => 1
                )
            )
        );
        if (function_exists('sendREST'))
            $dataStockProduct = $this->_this->sendREST($url, $body, 'POST');
        else
            $dataStockProduct = $this->_sendREST($url, $body, 'POST');

        if ($dataStockProduct['httpcode'] != 200) return 0;

        $dataStock = json_decode($dataStockProduct['content']);

        //if (!isset($dataStock->logisticsInfo[0])) return false;

        return $dataStock->logisticsInfo[0]->stockBalance ?? 0;
    }

    /**
     * Cria uma nova variação
     *
     * @param   object  $payload    Payload da variação para cadastro
     * @return  array               Retorna o status do cadastro
     */
    public function createVariation($payload, $skuPai)
    {
        // Pegar dados e tipos de variações
        $dataProduct     = $this->getProductForSku($skuPai);
        $typesVariations = $dataProduct['has_variants'] == "" ? array() : explode(";", $dataProduct['has_variants']);
        $idProd          = $dataProduct['id'];
        $id              = $payload->itemId;
        $sku             = $payload->itemId;
        $pricePai        = $dataProduct['price'];
        $validationn_sku=$this->verifyFieldsProduct('sku',$payload->itemId,true,'S',true,$idProd);
        if(!$validationn_sku[0]){
            return array('success' => $validationn_sku[0], 'message' => $validationn_sku[1]);
        }
        // não poderá mais ser atualizado
        if ($this->getPrdIntegration($idProd))
            return array('success' => false, 'message' => "Produto {$skuPai}, não pode mais receber novas variações pois já está integrado com o marketplace.");

        $varArr     = array();
        $tipoVarArr = array();

        foreach ($payload->variations as $typesVar) {
            $existVar = [false];
            foreach ($this->tipoVariacoes as $tipoVar => $realVar)
                if ($this->likeText("%{$tipoVar}%", strtolower($typesVar)))
                    $existVar = [true, $realVar];

            if (!$existVar[0])
                return array(false, "Foram encontradas variações não compatíveis com umas das variações (Variações aceitas: Cor/Tamanho/Voltagem). Variação original: ".strtolower($typesVar));


            if (!in_array($existVar[1], $tipoVarArr))
                array_push($tipoVarArr, $existVar[1]);

            $varArr['variacao'][$existVar[1]] = $payload->$typesVar[0];
        }

        $preco = $this->getPriceErp($sku);

        if ($preco === null || $preco === false) {
            return array('success' => false, 'message' => "Não foi possível obter o preço da variação {$sku}.");
        }

        if (empty($preco)) $preco = $pricePai;

        $stockReal  = $this->getStock($sku);
        if (!$stockReal) {
            return array('success' => false, 'message' => "Não foi possível obter o estoque da variação.");
        }

        $pathImageVar = md5(round(microtime(true) * 100000).rand(11111111,99999999));
        $imagesVar = array();
        foreach ($payload->images as $image) {
            array_push($imagesVar, $image->imageUrl);
        }

        // formatar variações para criação
        $varStr = "";
        if (in_array('TAMANHO', $tipoVarArr))   $varStr .= ";{$varArr['variacao']['TAMANHO']}";
        if (in_array('Cor', $tipoVarArr))       $varStr .= ";{$varArr['variacao']['Cor']}";
        if (in_array('VOLTAGEM', $tipoVarArr)) {
            $varStr .= ";{$varArr['variacao']['VOLTAGEM']}";
            // Se não tiver 'V' no número da voltagem, adicionar
            $unityVoltage = strpos( strtoupper($varArr['variacao']['VOLTAGEM']), 'V' );
            if (!$unityVoltage) $varStr .= 'V';
        }
        $varStr = substr($varStr,1);

        if ((array_diff($typesVariations, $tipoVarArr) || array_diff($tipoVarArr, $typesVariations)) && count($typesVariations))
            return array('success' => false, 'message' => "Foram encontradas tipos de variações para essa variação que não estão cadastradas no produto Pai. Todas as variações devem conter os mesmos tipos (Cor, Tamanho e Voltagem) <br> Esse produto contém os tipo: " . implode($typesVariations, ','));

        // Já existir esse sku, mas com valores diferentes
        if ($this->getVariantsForIdAndSku($idProd, $sku))
            return array('success' => false, 'message' => "Foi encontrada uma variação com o mesmo sku, mas com valores diferentes para esse produto.");

        $verification_sku=$this->verifyFieldsProduct('sku',$sku,true,'S',true,$idProd);
        if(!$verification_sku[0]){
            return array('success' => $verification_sku[0], 'message' => $verification_sku[1]);
        }
        $verification_ean=$this->verifyFieldsProduct('ean',$payload->ean,true,'S',true);
        if(!$verification_ean[0]){
            if ($this->CI->model_settings->getStatusbyName('products_require_ean') == 1) {
                return array('success' => $verification_ean[0], 'message' => $verification_ean[1]);
            }
            $payload->ean = '';
        }
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
                'sku'                   => $sku,
                'qty'                   => $stockReal,
                'variant_id_erp'        => $id,
                'price'                 => $preco,
                'image'                 => $pathImageVar,
                'status'                => 1,
                'EAN'                   => $payload->ean ?? '',
                'codigo_do_fabricante'  => '',
            )
        );

        if (!$createVar)
            return array('success' => false, 'message' => "Não foi possível inserir a variação.");

        $uploadImg = $this->uploadImageVariation($imagesVar, $dataProduct['image'], $pathImageVar, true);

        // Erro em upload de imagem
        if ($uploadImg['error'] != 0) {
            if ($uploadImg['error'] == 1) return array('success' => true, 'message' => $uploadImg['data']);
            if ($uploadImg['error'] == 2) return array('success' => true, 'message' => $uploadImg['data']);
            return array('success' => true, 'message' => 'Erro desconhecido.');
        }

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
        if ($key === 'preco' && $required) {

            if ($value === "" || (float)$value <= 0) return array(false, "O preço do produto não pode ser zero ou negativo!");
            $value_ok = array(true, number_format($this->setValueFormat($value, $type), 2, '.', ''));

        } elseif ($key === 'sku' && $required && $newProduct) {

            if ($value === "") return array(false, "O código SKU do produto precisa ser informado!");
            if (!$this->checkSkuAvailable($value)) return array(false, "O código SKU já está em uso!");
            if(!$this->validateSkuSpace($value)){
                return array(false, $this->getMessagemSkuFormatInvalid());
            }
            if(!$this->validateLengthSku($value)){
                return array(false, $this->getMessageLenghtSkuInvalid());
            }
            $value_ok = array(true, $this->setValueFormat($value, $type));

        } elseif ($key === "ean" && $required) {

            if (!$this->_this->model_products->ean_check($value))
                return array(false, "Código EAN inválido!");

        } elseif ($key === "ncm" && $required) {

            $value = filter_var(preg_replace('~[.-]~', '', $value), FILTER_SANITIZE_NUMBER_INT);
            if (strlen($value) != 8 && $value != "")
                return array(false, "Código NCM do produto está inválido, deve conter 8 caracteres!");

            $value_ok = array(true, trim($this->setValueFormat($value, $type)));

        }
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
                elseif ($key === "fabricante" && $required) return array(false, "Fabricante informado do produto não encontrado, informe um válido.");
                else $value_ok = array(true, $this->setValueFormat('[""]', $type));
            }

        }
        if ($key === "variacoes") {
            $varArr     = array();
            $tipoVarArr = array();
            $varCodes   = array();
            $stockReal  = array();
            $value = $value == "" ? array() : $value;
            $qtdVar     = null;

            foreach ($value as $keyVar => $type_v) {

                $qtdVar_v = count($type_v->variations);

                if ($qtdVar === null)
                    $qtdVar = $qtdVar_v;

                if ($qtdVar != $qtdVar_v)
                    return array(false, "Todas as variações devem conter os mesmos tipos. Exemplo: <br> (Var1 = Cor:Preto;Tamanho:42) (Var2 = Cor:Preto;Tamanho: 40)");

                foreach ($type_v->variations as $typesVar) {
                    $existVar = [false];
                    foreach ($this->tipoVariacoes as $tipoVar => $realVar)
                        if ($this->likeText("%{$tipoVar}%", strtolower($typesVar)))
                            $existVar = [true, $realVar];

                    if (!$existVar[0])
                        return array(false, "Foram encontradas variações não compatíveis com umas das variações (Variações aceitas: Cor/Tamanho/Voltagem). Variação original: ".strtolower($typesVar));

                    if (!in_array($existVar[1], $tipoVarArr))
                        array_push($tipoVarArr, $existVar[1]);

                    $valueVariation = $type_v->$typesVar[0];

                    // se for voltagem vejo se o valor da voltagem está mapeado como 110, 220 ou bivolt
                    if ($existVar[1] === 'VOLTAGEM') {
                        if (
                            !array_key_exists(strtolower($valueVariation), $this->valuesAcceptVoltageVariation) &&
                            !array_key_exists((int)filter_var($valueVariation, FILTER_SANITIZE_NUMBER_INT), $this->valuesAcceptVoltageVariation)
                        ) return array(false, "Foram encontrados valores na variações 'Voltagem' que não estão previsto. Informe valor de 110, 220 ou bivolt");

                        if (array_key_exists(strtolower($valueVariation), $this->valuesAcceptVoltageVariation))
                            $valueVariation = $this->valuesAcceptVoltageVariation[strtolower($valueVariation)];
                        elseif (array_key_exists((int)filter_var($valueVariation, FILTER_SANITIZE_NUMBER_INT), $this->valuesAcceptVoltageVariation)) {
                            $valueVariation = $this->valuesAcceptVoltageVariation[(int)filter_var($valueVariation, FILTER_SANITIZE_NUMBER_INT)];
                        }
                    }

                    $varArr[$keyVar]['variacao'][$existVar[1]] = $valueVariation;
                }

                $id         = $type_v->itemId;
                $sku        = $type_v->itemId;
                $preco      = $this->getPriceErp($sku);

                if ($preco === null || $preco === false) {
                    return array(false, "Não foi possível obter o preço da variação {$sku}.");
                }

                $stockReal[$id] = $this->getStock($sku);

                $imagesVar = array();
                foreach ($type_v->images as $image) {
                    array_push($imagesVar, $image->imageUrl);
                }

                // define o sku e id da variação
                $varArr[$keyVar]['sku']     = $sku;
                $varArr[$keyVar]['id']      = $id;
                $varArr[$keyVar]['preco']   = $preco;
                $varArr[$keyVar]['image']   = $imagesVar;
                $varArr[$keyVar]['ean']     = $type_v->ean;

                array_push($varCodes, $id);
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
        if ($key === 'peso_liquido' && $value <= 0 && $required) {
            return array(false, "O peso líquido do produto não pode ser zero.");
        }
        if ($key === 'peso_bruto' && $value <= 0 && $required) {
            return array(false, "O peso bruto do produto não pode ser zero.");
        }
        if ($key === 'largura' && $value <= 0 && $required) {
            return array(false, "A largura do produto não pode ser menor que 0(zero) cm.");
        }
        if ($key === 'altura' && $value <= 0 && $required) {
            return array(false, "A altura do produto não pode ser menor que 0(zero) cm.");
        }
        if ($key === 'comprimento' && $value <= 0 && $required) {
            return array(false, "A profundidade do produto não pode ser menor que 0(zero) cm.");
        }
        if ($key === 'nome') {
            if (!$this->validateLengthName($value)) {
                return array(false, $this->getMessageLenghtNameInvalid());
            }
            $value_ok = array(true, trim($this->setValueFormat($value, $type)));
        }
        if ($key === 'descricao' && $required) {
            if ($value == "")
                return array(false, "A descrição do produto não pode estar em branco.");

            if (trim(strip_tags($value), " \t\n\r\0\x0B\xC2\xA0") == '')
                return array(false, "A descrição do produto não pode estar em branco.");

            if (!$this->validateLengthDescription($value)) {
                return array(false,$this->getMessageLenghtDescriptionInvalid());
            }
            $value_ok = array(true, strip_tags_products($this->setValueFormat($value, $type), $this->allowable_tags));
        }
        if ($key === 'status') {
            $value_ok = array(true, trim($this->setValueFormat($value, $type)));
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
        return $this->_this->db->get_where('products',
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
            $sqlBrand = $this->_this->db->insert_string('brands', array('name' => $value, 'active' => 1));
            $this->_this->db->query($sqlBrand);
            $query = $this->_this->db
                ->select('id')
                ->from($table)
                ->where(array($column => $value))
                ->get();
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
     * @param   object  $idProduto  Sku do produto
     * @param   object  $qty        Quantidade do novo estoque
     * @return  bool                Retorna o status da atualização
     */
    public function updateStock($idProduto, $qty)
    {
        return $this->updateStockProduct($idProduto, $qty);
    }

    /**
     * Atualiza o estoque do produto
     *
     * @param   string  $skuProduct SKU do produto
     * @param   float   $qty        Novo saldo do estoque do produto
     * @return  bool                Retorna o status da atualização
     */
    public function updateStockProduct($skuProduct, $qty)
    {
        $this->_this->db->where(
            array(
                'sku'       => $skuProduct,
                'store_id'  => $this->_this->store,
            )
        );
        return (bool)$this->_this->db->update('products', array('qty' => $qty));
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
        $this->_this->db->where(array('prd_id' => $product['id'], 'sku' => $sku))->update('prd_variants', array('qty' => $qty));

        $newQty = 0;
        foreach ($variations as $variation) {

            if ($variation['sku'] == $sku) $variation['qty'] = $qty; // define a nova quantidade

            $newQty += (float)$variation['qty'];
        }

        return $this->updateStockProduct($skuPai, $newQty);
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
        return $this->_this->db->where(array('sku' => $sku, 'store_id' => $this->_this->store))->update('products', array('price' => $price)) ? true : false;
    }

    /**
     * Request API
     *
     * @param string        $url        URL requisição
     * @param null|string   $data       Dados para envio no body
     * @param string        $method     Metodo da requisição
     * @param bool          $newRequest Nova requisição que naõ se repetiu?
     * @param array         $header_opt Header adicional
     * @return  mixed
     */
    public function _sendREST($url, $data = '', $method = 'GET', $newRequest = true, $header_opt = array())
    {
        $url = str_replace(' ', '%20', $url);

        if (!preg_match('/http/', $url))
            $url = "https://{$this->_this->accountName}.{$this->_this->environment}.com.br/{$url}";

        $curl_handle = curl_init();

        if ($method == "GET") {
            curl_setopt($curl_handle, CURLOPT_URL, $url);
        } elseif ($method == "POST" || $method == "PUT") {

            if ($method == "PUT")
                curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'PUT');

            curl_setopt($curl_handle, CURLOPT_URL, $url);
            curl_setopt($curl_handle, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $arr_opt = array(
            "accept: application/vnd.vtex.ds.v10+json",
            "content-type: application/json",
            "x-vtex-api-apptoken: {$this->_this->token}",
            "x-vtex-api-appkey: {$this->_this->appKey}"
        );
        $arr_opt = array_merge($arr_opt, $header_opt);
        curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $arr_opt);

        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, TRUE);
        curl_setopt($curl_handle, CURLOPT_HEADER, TRUE);

        $response = curl_exec($curl_handle);
        $httpcode = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
        curl_close($curl_handle);

        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $matches);
        $cookies = array();
        foreach($matches[1] as $item) {
            parse_str($item, $cookie);
            $cookies = array_merge($cookies, $cookie);
        }

        $header['httpcode'] = $httpcode;
        $header['content']  = explode("\r\n\r\n", $response)[1];
        $header['cookie']  = $cookies;

        return $header;
    }

    /**
     * Atualização de produtos
     *
     * @param   object  $payload    Payload produto para atualização
     * @return  array               Retorna o status da atualização
     */
    public function updateProduct($payload, $countSku = null)
    {
        // type - S=string, "F"=float, "I"=integer, "A"=array
        // peso sempre em grama
        // medidas sempre em centímetros

        $productSku = $this->getDataProductERP($countSku === null ? $payload->items[0]->itemId : $payload->items[$countSku]->itemId);

        if (!property_exists($productSku, 'SalesChannels') || !in_array($this->_this->salesChannel, (array)$productSku->SalesChannels)) {
            return array('success' => 'SalesChannels', 'debug' => $productSku);
        }

        // garantia
        $guarantee = 0;
        if (isset($payload->Garantia[0]) || isset($payload->garantia[0])) {
            $guaranteeValid = $payload->Garantia[0] ?? $payload->garantia[0];
            $guarantee = (int)filter_var($guaranteeValid, FILTER_SANITIZE_NUMBER_INT);

            if ($this->likeText("%ano%", strtolower($guaranteeValid))) {
                $guarantee *= 12;
            } elseif ($this->likeText("%mes%", strtolower($guaranteeValid))) {
                //$guarantee *= 1;
            }
        }

        $category = (array)$payload->categories;
        $product = array(
            'nome'              => array('value' => $countSku === null ? $payload->productName : $payload->items[$countSku]->nameComplete, 'required' => true, 'type' => 'S', 'field_database' => 'name'),
            'un'                => array('value' => $countSku === null ? $payload->items[0]->measurementUnit : $payload->items[$countSku]->measurementUnit , 'required' => true, 'type' => 'S', 'field_database' => 'attribute_value_id'),
            'ean'               => array('value' => $countSku === null ? $payload->items[0]->ean : $payload->items[$countSku]->ean, 'required' => true, 'type' => 'S', 'field_database' => 'EAN'),
            'peso_liquido'      => array('value' => $productSku->Dimension->weight/1000, 'required' => true, 'type' => 'F', 'field_database' => 'peso_liquido'),
            'peso_bruto'        => array('value' => $productSku->Dimension->weight/1000, 'required' => true, 'type' => 'F', 'field_database' => 'peso_bruto'),
            'sku_fabricante'    => array('value' => $productSku->ManufacturerCode, 'required' => true, 'type' => 'S', 'field_database' => 'codigo_do_fabricante'),
            'descricao'         => array('value' => $payload->description, 'required' => true, 'type' => 'S', 'field_database' => 'description'),
            'garantia'          => array('value' => $guarantee, 'required' => true, 'type' => 'I', 'field_database' => 'garantia'),
            'fabricante'        => array('value' => $payload->brand, 'required' => true, 'type' => 'S', 'field_database' => 'brand_id'),
            'altura'            => array('value' => $productSku->Dimension->height, 'required' => true, 'type' => 'F', 'field_database' => 'altura'),
            'comprimento'       => array('value' => $productSku->Dimension->length, 'required' => true, 'type' => 'F', 'field_database' => 'profundidade'),
            'largura'           => array('value' => $productSku->Dimension->width, 'required' => true, 'type' => 'F', 'field_database' => 'largura'),
            //'categoria'         => array('value' => implode(' > ', array_reverse((array)$payload->categories)), 'required' => true, 'type' => 'S', 'field_database' => 'category_imported'),
            'categoria'         => array('value' => ltrim(rtrim($category[0] ?? '','/'),'/'), 'required' => true, 'type' => 'S', 'field_database' => 'category_imported'),
            'images'            => array('value' => $productSku->Images, 'required' => true, 'type' => 'A', 'field_database' => NULL),
            'variacoes'         => array('value' => $countSku === null ? $payload->items : array(), 'required' => true, 'type' => 'A', 'field_database' => 'has_variants')
        );

        // Validar e formatar campos
        $productFormat = $this->getDataFormat($product);

        // Encontrou erro na formatação dos dados
        if (isset($productFormat['success']) && !$productFormat['success'])
            return $productFormat;

        $sku = $countSku === null ? 'P_'.$payload->productId : $payload->items[$countSku]->itemId;
        // recupera as variações para definir o has_variants com os tipos
        $dataProduct = $this->getProductForSku($sku);

        $productFormat['product_id_erp'] = $sku;

        $imagesERP = $productFormat['image'];

        // remove os itens que não derão ser atualizados
        foreach ($this->naoAtualizar as $field)
            unset($productFormat[$field]);

        // verificar se o produto já foi integrado
        if (!$this->getPrdIntegration($dataProduct['id'])) {
            $dataVariants = $productFormat['has_variants']['variacoes'] ?? array();
            $productFormat['has_variants'] = $productFormat['has_variants']['tipos'];
        } else { 
            $updatedVariation = false;
            $dataVariants = $productFormat['has_variants']['variacoes'] ?? array();
            $productFormat['has_variants'] = $productFormat['has_variants']['tipos'];
            foreach ($dataVariants as $variant) {
                $updateVar = $this->updateVariation($dataProduct, $productFormat['has_variants'], $variant);
                if ($updateVar['success']) $updatedVariation = true;
            }
            return array('success' => $updatedVariation ? true : null);
        }

        $updatedVariation = false;
        foreach ($dataVariants as $variant) {
            $updateVar = $this->updateVariation($dataProduct, $productFormat['has_variants'], $variant);
            if ($updateVar['success']) $updatedVariation = true;
        }

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
                ($dataProduct['category_id'] != '[""]') &&
                ($dataProduct['brand_id'] != '[""]' || $productFormat['brand_id'] != '[""]') &&
                $this->_this->uploadproducts->countImagesDir($dataProduct['image'])
            )
                $productFormat['situacao'] = 2;
            else
                $productFormat['situacao'] = 1;
        }

        if ($this->getProductForAttributes($productFormat))
            return array('success' => $updatedVariation ? true : null);

        // Inserção produto e pegar id para passar nas variações
        $sqlProd = $this->_this->db->update_string('products', $productFormat, array('sku' => $sku, 'store_id' => $this->_this->store));
        $this->_this->db->query($sqlProd);

        // bloqueia produto se necessário
        $dataProductUpdated = $this->getProductForSku($sku);
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
     * @param   array   $dataProduct    Dados do produto pai
     * @param   string  $typeVariant    Dados do produto VTEX
     * @param   array   $dataVariation  SKU do produto filho
     * @return  array                   Retorna o status do cadastro
     */
    public function updateVariation(array $dataProduct, string $typeVariant, array $dataVariation): array
    {
        if ($dataProduct['has_variants'] != $typeVariant)
            return array('success' => false, 'message' => "Variação está com tipos diferentes do que já está no produto pai.");

        $varStr = "";
        if (array_key_exists('TAMANHO', $dataVariation['variacao']))   $varStr .= ";{$dataVariation['variacao']['TAMANHO']}";
        if (array_key_exists('Cor', $dataVariation['variacao']))       $varStr .= ";{$dataVariation['variacao']['Cor']}";
        if (array_key_exists('VOLTAGEM', $dataVariation['variacao'])) {
            $varStr .= ";{$dataVariation['variacao']['VOLTAGEM']}";
            // Se não tiver 'V' no número da voltagem, adicionar
            $unityVoltage = strpos( strtoupper($dataVariation['variacao']['VOLTAGEM']), 'V' );
            if (!$unityVoltage) $varStr .= 'V';
        }
        $varStr = !empty($varStr) ? substr($varStr,1) : '';

        //consulta tipo de variação atual
        $dataVarDb = $this->_this->db->get_where('prd_variants', array('prd_id' => $dataProduct['id'], 'sku' => $dataVariation['sku']))->row_array();

        if (!$dataVarDb)
            return array('success' => false, 'message' => "Variação {$dataVariation['sku']} não encontrada.");

        if ($dataVarDb['name'] == $varStr)
            return array('success' => null);

        // Inserir variação
        $sqlVar = $this->_this->db->update_string(
            'prd_variants',
            array('name' => $varStr),
            array('prd_id' => $dataProduct['id'], 'sku' => $dataVariation['sku'])
        );
        $updateVar = $this->_this->db->query($sqlVar); // status da variação atualizada

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
        $query = $this->_this->db->get_where('products', array('store_id' => $this->_this->store, 'sku' => $sku));
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
     * @param   integer     $idVtex     Código da variação na tiny
     * @return  null|array              Retorna um array com dados da variação ou null caso não encontre
     */
    public function getVariationForIdErp($idVtex)
    {
        return $this->_this->db
            ->select('prd_variants.*')
            ->from('prd_variants')
            ->join('products', 'products.id = prd_variants.prd_id')
            ->where(
                array(
                    'prd_variants.sku' => $idVtex,
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
        $queryProd = $this->_this->db->get_where('products', array('store_id' => $this->_this->store, 'sku' => $skuProduct));
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
        
        $this->_this->db->where('id', $idVar);
        return $this->_this->db->update('prd_variants', array('sku' => $newSku, 'variant_id_erp' => $idProd)) ? true : false;
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
     * Recupera se o preço do produto
     *
     * @param   array|int $product  Código do sku do produto
     * @return  int|bool            Retorna o preço do produto. (
     *                                                              NULL  = Produto indisponível, não será apresentado o preço.
     *                                                              FALSE = Ocorreu um erro, talvez instabilidade na VTEX.
     *                                                          )
     */
    public function getPriceErp($product)
    {
        $priceProd = 0;
        $skuProduct = array();
        $body = array();

        if (is_numeric($product)) $skuProduct = [$product];
        else {
            foreach ($product as $prd) {
                array_push($skuProduct, $prd->itemId);
            }
        }

        foreach ($skuProduct as $sku) {
            array_push($body,
                array(
                    'id' => $sku,
                    'quantity' => 1,
                    'seller' => 1
                )
            );
        }

        // Consulta endpoint par obter estoque
        $url = "api/fulfillment/pvt/orderForms/simulation?affiliateId={$this->_this->affiliateId}&sc={$this->_this->salesChannel}";
        $body = array('items' => $body);

        if (function_exists('sendREST')) {
            $dataPriceProduct = $this->_this->sendREST($url, $body, 'POST');
        } else {
            $dataPriceProduct = $this->_sendREST($url, $body, 'POST');
        }

        // requisição não deu sucesso
        if ($dataPriceProduct['httpcode'] != 200) {
            return false;
        }

        $dataPrice = json_decode($dataPriceProduct['content']);

        // encontrou um erro de produto indisponível
        // Ex.: "Item Cama Sofá Noah com Pés em Madeira Natural – Branco Fosco não encontrado ou indisponível"
        if (isset($dataPrice->messages[0]->code) && $dataPrice->messages[0]->code === 'ORD027') {
            return null;
        }

        // não encontrou o preço na resposta
        if (!isset($dataPrice->items)) {
            return false;
        }

        foreach ($dataPrice->items as $iten) {

            // não encontrou o preço na resposta ou está vazio
            if (!isset($iten->price) || empty($iten->price)) {
                continue;
            }

            // recupera o maior preço de todos os itens
            if ($iten->price > $priceProd) {
                $priceProd = substr_replace($iten->price, '.', -2, 0);
            }
        }

        // Não foi encontrado o valor para o produto.
        if (empty($priceProd)) {
            return null;
        }

        return $priceProd;
    }

    /**
     * Recupera dados do produto
     *
     * @param   int         $idProduct  Código do sku do produto
     * @return  object|null             Retorna dados do produto, caso exista
     */
    public function getDataProductERP($idProduct)
    {
        $url = "api/catalog_system/pvt/sku/stockkeepingunitbyid/{$idProduct}";

        if (function_exists('sendREST'))
            $dataProduct = $this->_this->sendREST($url);
        else
            $dataProduct = $this->_sendREST($url);

        if ($dataProduct['httpcode'] != 200) return null;

        return json_decode($dataProduct['content']);
    }

    /**
     * Recupera o estoque no banco de dados de um produto ou variação pelo ID do erp
     *
     * @param   string      $idProduct  ID do produto PAI
     * @param   null|string $idVariant  ID da variação
     * @return  bool|array              Retorna o estoque do produto/variação ou false em falha
     */
    public function getStockForIdErp($idProduct, $idVariant = null)
    {
        $queryProd = $this->_this->db->get_where('products', array('store_id' => $this->_this->store, 'sku' => $idProduct));
        if ($queryProd->num_rows() == 0) {
            return false;
        }
        $resultProd = $queryProd->row_array();

        if (!$idVariant) {
            return array('qty' => $resultProd['qty'], 'sku' => $resultProd['sku']);
        }

        $queryVar = $this->_this->db->get_where('prd_variants', array('prd_id' => $resultProd['id'], 'sku' => $idVariant));
        if ($queryVar->num_rows() == 0) return false;

        $resultVar = $queryVar->row_array();

        return array('qty' => $resultVar['qty'], 'skuProd' => $resultProd['sku'], 'skuVar' => $resultVar['sku']);
    }

    /**
     * Recupera o preço no banco de dados de um produto ou variação pelo sku
     *
     * @param   string      $skuProduct SKU do produto PAI
     * @param   null|string $skuVar     SKU da variação
     * @return  bool|array              Retorna o estoque do produto/variação ou false em falha
     */
    public function getPriceForSku(string $skuProduct, string $skuVar = null)
    {
        $queryProd = $this->_this->db->get_where('products', array('store_id' => $this->_this->store, 'sku' => $skuProduct));
        if ($queryProd->num_rows() == 0) {
            return false;
        }
        $resultProd = $queryProd->row_array();

        if (!$skuVar) {
            return array('price' => $resultProd['price'], 'sku' => $resultProd['sku']);
        }

        $queryVar = $this->_this->db->get_where('prd_variants', array('prd_id' => $resultProd['id'], 'sku' => $skuVar));
        if ($queryVar->num_rows() == 0) return false;

        $resultVar = $queryVar->row_array();

        return array('price' => $resultVar['price'], 'skuProd' => $resultProd['sku'], 'skuVar' => $resultVar['sku']);
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
     * Atualiza o preço de um produto
     *
     * @param   string  $sku    SKU do produto
     * @param   float   $price  Preço do produto
     * @return  bool            Retorna o status da atualização
     */
    public function updatePriceVariation($sku, $skuPai, $price)
    {
        $product = $this->getProductForSku($skuPai);
        if (!$product) return false;

        $variations = $this->_this->db->get_where('prd_variants', array('prd_id' => $product['id']))->result_array();

        // Atualiza o estoque da variação
        $this->_this->db->where(array('prd_id' => $product['id'], 'sku' => $sku))->update('prd_variants', array('price' => $price));

        $newPrice = null;
        foreach ($variations as $variation) {

            if ($variation['sku'] == $sku) $variation['price'] = $price; // define a novo preço

            if ($newPrice === null) $newPrice = $variation['price'];
            else
                if ($variation['price'] > $newPrice) $newPrice = $variation['price'];
        }

        return (bool)$this->_this->db->where(
            array(
                'sku' => $skuPai,
                'store_id' => $this->_this->store
            )
        )->update('products', array('price' => $newPrice));
    }
}