<?php
require APPPATH . "libraries/Traits/VerifyFieldsProduct.trait.php";
class Product
{
    use VerifyFieldsProduct;
    public $allowable_tags = null;

    // tipo de variação para comparar, INFORMAR EM MINÚSCULO A CHAVE
    public $tipoVariacoes = array(
        'tamanho'   => 'TAMANHO',
        'tamanhos'  => 'TAMANHO',
        'Tamanho'   => 'TAMANHO',
        'Tamanhos'  => 'TAMANHO',
        'size'      => 'TAMANHO',
        'sizes'     => 'TAMANHO',
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
        'cores'     => 'Cor',
        'color'     => 'Cor',
        'colors'    => 'Cor',
        'Cor'       => 'Cor',
        'Cores'     => 'Cor',
        'COR'       => 'Cor',

        'voltagem'  => 'VOLTAGEM',
        'voltagens' => 'VOLTAGEM',
        'Voltagem'  => 'VOLTAGEM',
        'Voltagens' => 'VOLTAGEM',
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
        'attribute_value_id'
    );

    private $CI;

    // Passagem de dados
    private $_this;

    public function __construct($_this)
    {
        $this->_this = $_this;

        $this->CI = &get_instance();
        $this->CI->load->library('BlacklistOfWords');
        $this->CI->load->library('ERPIntegration');
        $this->CI->load->model('model_api_integrations');
        $this->CI->load->model('Model_stores');
        $this->CI->load->model('Model_category');
        $this->CI->load->model('model_settings');

        if ($allowableTags = $this->CI->model_settings->getValueIfAtiveByName('products_allowable_tags')) {
            if (!empty($allowableTags)) {
                $this->allowable_tags = '<' . implode('><', explode(',', $allowableTags)) . '>';
            }
        }

        $this->loadLengthSettings();
    }
    public function getProductForIdErp($idPluggTo, $sku = null)
    {
        $query = $this->_this->db->get_where(
            'products',
            array(
                'store_id'       => $this->_this->store,
                'product_id_erp' => $idPluggTo
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


    /**
     * Recupera dados do produto pelo SKU do produto
     *
     * @param   string      $sku    SKU do produto
     * @return  null|array          Retorna um array com dados do produto ou null caso não encontre
     */
    public function getProductForSku($sku)
    {

        return $this->_this->db->get_where(
            'products use index (store_sku)',
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
            ->from('prd_variants')
            ->join('products', 'products.id = prd_variants.prd_id')
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
    public function getPathNewImage($path = NULL, $variacao = false)
    {
        $serverpath     = $_SERVER['SCRIPT_FILENAME'];
        $pos            = strpos($serverpath, 'assets');
        $serverpath     = substr($serverpath, 0, $pos);
        $targetDir      = $serverpath . 'assets/images/product_image/';

        if ($variacao) {
            $dirImage = $path . "/" . Admin_Controller::getGUID(false);
        } else {
            $dirImage = $path ?? Admin_Controller::getGUID(false); // gero um novo diretorio para as imagens
        }

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

        if (is_array($arrImages)) {
            array_multisort(array_map(function ($image) {
                return $image->order ?? 0;
            }, $arrImages), SORT_ASC, $arrImages);
            foreach ($arrImages as $image) {
                if ($iCount < 6) {
                    $url = str_replace("https", "http", $image->url);

                    // não encontrou imagem para upload
                    if (empty($url))
                        return array('success' => false, 'message' => array('Imagem invalida, não foi possível recuperar a imagem, chegou em branco!'));
                    $upload = $this->_this->uploadproducts->sendImageForUrl("{$path}/", $url);

                    if ($upload['success']) {
                        if ($primaryImage === null) {
                            $primaryImage = base_url("{$path}/{$upload['path']}");
                        }

                        array_push($arrNameImages, $upload['path']);
                        $iCount++;
                    } else // não conseguiu fazer upload da imagem
                    {
                        $this->_this->log_data('batch', 'Product/getImages', "Ocorreu um problema para cadastrar a imagem {" . json_encode($image) . "} retorno=", "E");
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
            case 'S':
                return (string)$value;
            case 'A':
                return (array)$value;
            case 'F':
                return (float)$value;
            case 'I':
                return (int)$value;
            default:
                return $value;
        }
    }


    /**
     * Recupera os dados formatado do produto
     *
     * @param   array $product  Produto para ser formatado
     * @return  array           Retorna o produto com a formatação para ser inserido
     */
    public function getDataFormat($product, $newProduct)
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
            if (in_array($key, ['unidade', 'categoria', 'fabricante', 'garantia','variacoes'])) {
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


    public function getStock($productSku, $access_token, $is_variation = false)
    {
        $qty = 0;
        $arrCodeQty = array();

        // Consulta endpoint par obter estoque
        // Começando a pegar os produtos para criar
        $url    = "https://api.plugg.to/skus/$productSku?access_token={$access_token}";
        $data   = "";

        $dataStockProduct = json_decode(json_encode($this->_this->sendREST($url, $data)));

        // Ocorreu um problema
        if ($dataStockProduct->httpcode != 200) {
            //echo "Ocorreu um problema para obter o estoque do produto sku = {$productSku} retorno=" . json_encode($dataStockProduct) . "\n";
            $this->_this->log_data('batch', 'Product/getStock', "Ocorreu um problema para obter o estoque do produto sku = {$productSku} retorno=" . json_encode($dataStockProduct), "E");
            return array('success' => false, 'message' => 'Ocorreu um problema para obter o estoque, caso use uma lista de preço verifique se o produto/variação está na lista!');
        }

        $prodRes = json_decode($dataStockProduct->content);
        if ($is_variation == true) {
            foreach ($prodRes->Product->variations as $var) {
                if ($var->sku == $productSku) {
                    $qtyProduct = $var->quantity ?? 0;
                    $qty += ($qtyProduct);
                    $arrCodeQty[$productSku] = $qtyProduct;

                    return array('success' => true, 'totalQty' => $qty, 'variationQty' => $arrCodeQty);
                }
            }
        }

        $qtyProduct = $prodRes->Product->quantity ?? 0;
        $qty += ($qtyProduct);
        $arrCodeQty[$productSku] = $qtyProduct;

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

        return $this->_this->db->get_where(
            'products use index (store_sku)',
            array(
                'store_id'  => $this->_this->store,
                'sku'       => $sku
            )
        )->num_rows() === 0;
    }


    public function getStoreBySKUProduct($skuProduct)
    {

        $ID_contaSeller = explode("-", $skuProduct);
        $ID_contaSeller = $ID_contaSeller[0];

        $credentials = $this->CI->model_api_integrations->getDataByIntegration('pluggto');
        foreach ($credentials as $credential) {
            if (!$credential || $credential['status'] != 1)
                return false;

            $data = json_decode($credential['credentials']);
            if (isset($data->user_id) && ($data->user_id == $ID_contaSeller)) {
                $store = $this->CI->Model_stores->getStoresById($credential['store_id']);
                $this->company = $store['company_id'];
                return $credential['store_id'];
            }
        }
    }



    /* Cria um novo produto
     *
     * @param   object  $payload    Payload produto para cadastro
     * @param   float   $precoProd  Preço diferenciado(lista de preço)
     * @param   bool    $webhook    Status de uso do webhook
     * @return  array               Retorna o status do cadastro ou array de mapemaneto caso seja webhook
     */

    public function createProduct($payload, $webhook = false)
    {

        $id_produto = $payload->id;
        $skuProduct = $payload->sku;

        if (!$this->_this->store) {
            //echo "Ocorreu um problema para criar produto id_PluggTo={$id_produto}. Loja nao encontrada, id Conta Pluggto: \n";
            $this->_this->log_data('batch', 'Product/Create', "Ocorreu um problema para criar produto id_PluggTo={$id_produto}. Loja nao encontrada, id Conta Pluggto: ", "E");
            return array('success' => false, 'message' => 'Loja não localizada');
        }

        if (isset($payload->codigoPai)) {
            //echo "Esse produto é uma variação, aqui só entra produto pai ou simples";
            return array('success' => false, 'message' => array('Essa variação está tentando cadastrar como um produtos pai, verifique se o sku da variação é diferente do sku do produto pai, para corrigo-lo'));
        }

        if (isset($payload->type) && $payload->type != "simple") {
            //echo "Chegou um tipo que não é um produto. Chegou={$payload->type}";
            return array('success' => false, 'message' => array('Está tentando criar um tipo que não é um produto, recebemos o seguinte tipo: ' . $payload->type));
        }

        $preco_produto = $payload->price;
        if (isset($payload->special_price) && ($payload->special_price > 0)) {
            $preco_produto = $payload->special_price;
        }

        $category_replace = "";
        foreach($payload->categories as $cat)
        {
            $category_replace = str_replace(' > ', '/', $cat->name);
        }      
               
        //echo $category_replace;die;

        // type - S=string, "F"=float, "I"=integer, "A"=array
        $product = array(
            'nome'              => array('value' => $payload->name, 'required' => true, 'type' => 'S', 'field_database' => 'name'),
            'sku'               => array('value' => $payload->sku, 'required' => true, 'type' => 'S', 'field_database' => 'sku'),
            'unidade'           => array('value' => '', 'required' => true, 'type' => 'S', 'field_database' => 'attribute_value_id'),
            'preco'             => array('value' => $preco_produto, 'required' => true, 'type' => 'F', 'field_database' => 'price'),
            'cest'              => array('value' => $payload->cest, 'required' => true, 'type' => 'S', 'field_database' => 'CEST'),
            'ncm'               => array('value' => $payload->ncm, 'required' => true, 'type' => 'S', 'field_database' => 'NCM'),
            'origem'            => array('value' => $payload->origin ?? 0, 'required' => true, 'type' => 'I', 'field_database' => 'origin'),
            'ean'               => array('value' => $payload->ean ?? '', 'required' => true, 'type' => 'S', 'field_database' => 'EAN'),
            'peso_liquido'      => array('value' => $payload->dimension->weight ?? 0, 'required' => true, 'type' => 'F', 'field_database' => 'peso_liquido'),
            'peso_bruto'        => array('value' => $payload->dimension->weight ?? 0, 'required' => true, 'type' => 'F', 'field_database' => 'peso_bruto'),
            'sku_fabricante'    => array('value' => $payload->codigoNoFabricante ?? '', 'required' => true, 'type' => 'S', 'field_database' => 'codigo_do_fabricante'),
            'descricao'         => array('value' => $payload->description ?? '', 'required' => false, 'type' => 'S', 'field_database' => 'description'),
            'garantia'          => array('value' => $payload->warranty_time, 'required' => true, 'type' => 'I', 'field_database' => 'garantia'),
            'fabricante'        => array('value' => $payload->brand ?? '', 'required' => true, 'type' => 'S', 'field_database' => 'brand_id'),
            'altura'            => array('value' => $payload->dimension->height, 'required' => true, 'type' => 'F', 'field_database' => 'altura'),
            'comprimento'       => array('value' => $payload->dimension->length, 'required' => true, 'type' => 'F', 'field_database' => 'profundidade'),
            'largura'           => array('value' => $payload->dimension->width, 'required' => true, 'type' => 'F', 'field_database' => 'largura'),
            'categoria'         => array('value' => $payload->categories ?? "", 'required' => true, 'type' => 'A', 'field_database' => 'category_imported'),
            'imagesAnexo'       => array('value' => $payload->photos, 'required' => true, 'type' => 'A', 'field_database' => null),
            'variacoes'         => array('value' => $payload->variations ?? array(), 'required' => true, 'type' => 'A', 'field_database' => 'has_variants'),
            'prazo_operacional_extra' => array('value' => $payload->manufacture_time ?? 0, 'required' => true, 'type' => 'I', 'field_database' => 'prazo_operacional_extra')
        );
        //dd($product);
        // Validar e formatar campos
        $productFormat = $this->getDataFormat($product, true);

        // Encontrou erro na formatação dos dados
        if (isset($productFormat['success']) && !$productFormat['success']) {
            return $productFormat;
        }

        if (isset($productFormat['image'][0]) && count($productFormat['image'][0]) == 1 && $productFormat['image'][0][0] == "")
            $productFormat['image'] = array([]);

        // upload de imagens
        $path = $this->getPathNewImage(); // folder que receberá as imagens

        if (isset($payload->photos)) {
            // Faz upload e recupera os códigos
            $images = $this->getImages($payload->photos, $path['path_complet']);
            if (!$images['success']) {
                // encontrou erro no upload de imagem
                //echo "erro upload imagem\n";
                return $images;
            }
        }
        //define nome da pasta com as imagens e a imagem principal
        $productFormat['image'] = $path['path_product'] ?? '';
        if (isset($images['primary'])) {
            $productFormat['principal_image'] = $images['primary'] ?? null;
        }

        $productFormat['has_variants'] = "";
        $productFormat['qty'] = $payload->quantity ?? 0;
        $productFormat['store_id'] = $this->_this->store;
        $productFormat['product_id_erp'] = $payload->id;

        //cadastro de medidas sem embalagem
        $productFormat['actual_depth']  = $payload->real_dimension->length ?? 0;
        $productFormat['actual_height'] = $payload->real_dimension->height ?? 0;
        $productFormat['actual_width']  = $payload->real_dimension->width  ?? 0;

        $productFormat["category_imported"] = str_replace(",", "", $category_replace);
        $categ = $this->CI->Model_category->getcategorybyName($productFormat["category_imported"]);
        
        if($categ)
            $productFormat["category_id"] = '["'.$categ.'"]';
        
        // Inserção produto e pegar id para passar nas variações
        $prd_id = $this->_this->model_products->create($productFormat);

        // bloqueia produto se necessário
        $this->CI->blacklistofwords->updateStatusProductAfterUpdateOrCreate($productFormat, $prd_id);
        
        if (isset($payload->variations) && (count($payload->variations) > 0) && isset($prd_id)) {
            $variant_count          = 0;
            $prd_id_anterior        = 0;
            $produtoPaiNovoEstoque  = 0;
    
            foreach ($payload->variations as $prodvar) {

                $variation_name     = array();
                $attr_variation     = array();
                $hasVariations      = array();
                $newHasVariations   = array();

                foreach ($prodvar->attributes as $attr) {
                    $variationType = $this->tipoVariacoes[strtolower($attr->code)];
                    $attr_variation[$variationType] = $attr->value->label;
                    if (!in_array($variationType, $hasVariations)) {
                        $hasVariations[] = $variationType;
                    }
                }

                /*
                $variation_name .= isset($attr_variation["TAMANHO"]) ? $attr_variation["TAMANHO"] . ";" : "";
                $variation_name .= isset($attr_variation["Cor"]) ? $attr_variation["Cor"] . ";" : "";
                $variation_name .= isset($attr_variation["VOLTAGEM"]) ? $attr_variation["VOLTAGEM"] . ";" : "";
                */

                foreach (['TAMANHO', 'Cor', 'VOLTAGEM'] as $hasVar) {
                    if (in_array($hasVar, $hasVariations)) {
                        array_push($newHasVariations, $hasVar);
                    }

                    if (array_key_exists($hasVar, $attr_variation)) {
                        array_push($variation_name, $attr_variation[$hasVar]);
                    }
                }
                $variation_name = implode(';', $variation_name);

                $variant_count = $prd_id == $prd_id_anterior ? $variant_count + 1 : 0;

                if (!empty($variation_name)) {
                    $pathVariation = $this->getPathNewImage($path['path_product'], true);

                    if (isset($prodvar->photos)) {
                        // Faz upload e recupera os códigos
                        $images = $this->getImages($prodvar->photos, $pathVariation['path_complet']);
                        if (!$images['success']) {
                            // encontrou erro no upload de imagem
                            // echo "erro upload imagem\n";
                            // return $images;
                        }
                    }

                    $arr_path_product = explode("/", $pathVariation['path_product']);

                    $preco_produto = $prodvar->price;
                    if (isset($prodvar->special_price) && ($prodvar->special_price > 0)) {
                        $preco_produto = $prodvar->special_price;
                    }

                    $this->_this->model_products->createvar(
                        array(
                            'prd_id'                => $prd_id ?? 0,
                            'variant'               => $variant_count,
                            'name'                  => $variation_name ?? '',
                            'sku'                   => $prodvar->sku ?? '',
                            'qty'                   => $prodvar->quantity ?? 0,
                            'variant_id_erp'        => $prodvar->id ?? 0,
                            'price'                 => number_format($preco_produto, 2, ".", "") ?? 0,
                            'image'                 => end($arr_path_product),
                            'status'                => 1,
                            'ean'                   => $prodvar->ean ?? '',
                            'codigo_do_fabricante'  => '',
                        )
                    );

                    $prd_id_anterior = $prd_id;
                    $produtoPaiNovoEstoque += $prodvar->quantity;
                }
            }
            if (!empty($newHasVariations)) {
                $this->updateHasVariations($prd_id, $newHasVariations);
                $this->updateStockProduct($payload->sku, $produtoPaiNovoEstoque);
            }
        }else{
            if (isset($payload->attributes) && (count($payload->attributes) > 0) && isset($prd_id)) {
                $this->CI->erpintegration->setAttributeProduct($prd_id, $payload->attributes);
                
                //$attributes = (array)$payload->attributes;
                //foreach($payload->attributes as $attr)
                //{
                //    $this->ERPIntegration->setAttributeProduct($prd_id, $attr);        
                //}
            }
        }

        // mapeamento para retorno via webhook
        $arrmapeamentopluggto = array();
        if ($webhook) {
            array_push($arrmapeamentopluggto, array("idmapeamento" => $payload->idmapeamento, "skumapeamento" => $payload->codigo));
        }
        // formatar variações para criação
        $newprice = 0;
        $newqty = 0;
        $variationsnotlist = array();

        // // atualiza preço
        /*if ($newprice) {
            $this->_this->db->where(array('sku' => $productformat['sku'], 'store_id' => $this->_this->store))->update('products', array('price' => $newprice, 'qty' => $newqty));
        }*/

        if ($webhook) {
            return array('success' => true, 'data' => $arrmapeamentopluggto, 'variations_not_list' => $variationsnotlist);
        }

        return array('success' => true);
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


    /* Verifica os campos para validação
     *
     * @param   integer     $idPluggTo  Código da variação no PluggTo
     * @return  null|array              Retorna um array com dados da variação ou null caso não encontre
     */
    public function getVariationForIdErp($idPluggTo)
    {


        return $this->_this->db
            ->select('prd_variants.*')


            ->from('prd_variants')
            ->join('products', 'products.id = prd_variants.prd_id')
            ->where(
                array(
                    'prd_variants.variant_id_erp' => $idPluggTo,
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

    public function verifyFields($key, $value, $required, $type, $newProduct)
    {
        $value_ok = array(true, $this->setValueFormat($value, $type));

        if ($key === 'unidade' && $required) {
            if ($value === "" || (int)$value <= 0) {
                $value_ok = array(true, '[""]');
            } elseif ((int)$value > 0) {
                $value_ok = array(true, '["' . $value . '"]');
            } else {
                return array(false, "A unidade tem que ser ajustada, é necesário ter um valor!");
            }
        }

        if ($key === 'categoria' && $required) {
            if ($value === "" || (int)$value <= 0) {
                $value_ok = array(true, '');
            } elseif (isset($value)) {
                $resultCat = '';
                foreach ($value as $Departamento) {
                    $resultCat .= $Departamento->name . ",";
                }
                $value_ok = array(true, $resultCat);
            } else {
                return array(false, "A categoria tem que ser ajustada, é necesário ter um valor!");
            }
        }

        if ($key === 'preco' && $required) {
            if ($value === "" || (float)$value <= 0) return array(false, "O preço do produto não pode ser zero ou negativo!");
            $value_ok = array(true, number_format($this->setValueFormat($value, $type), 2,".",""));

        } elseif ($key === 'sku' && $required) {
            if ($value === "") return array(false, "O codigo SKU do produto precisa ser informado!");
            if (!$this->verifySKUAvailable($value)) return array(false, "O codigo SKU ja esta em uso!");
            // testar o continue
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
        if ($key === 'nome') {
            if (strlen($this->removeAccents($value)) > $this->product_length_name) {
                return array(false, "O nome do produto não pode ter mais de ".$this->product_length_name." caracteres.");
            }
            $value_ok = array(true, trim($this->setValueFormat($value, $type)));
        }
        if ($key === 'descricao' && $required) {
            if ($value == "")
                return array(false, "A descrição do produto não pode estar em branco.");

            if (trim(strip_tags($value), " \t\n\r\0\x0B\xC2\xA0") == '')
                return array(false, "A descrição do produto não pode estar em branco.");

            if (strlen($this->removeAccents($value)) > $this->product_length_description) {
                return array(false, "A descrição do produto não pode ter mais de ".$this->product_length_description." caracteres.");
            }
            $value_ok = array(true, strip_tags_products($this->setValueFormat($value, $type), $this->allowable_tags));
        }
        if ($key === "origem" && ($value < 0 || $value > 8) && $required) {
            return array(false, "A origem do produto deve ser entre 0 e 8! http://legislacao.sef.sc.gov.br/html/regulamentos/icms/ricms_01_10.htm");

        }

        if ($key === "fabricante") {
            $value = trim($value);

            if (empty($value))
                return array(false, "Marca não pode estar em branco!");

            $codeInfoProduct = false;
            $codeInfoProduct = $this->getCodeInfoProduct('brands', 'name', $value);

            if ($codeInfoProduct) {
                $value_ok = array(true, $this->setValueFormat("[\"{$codeInfoProduct}\"]", $type));
            } else $value_ok = array(true, $this->setValueFormat('[""]', $type));
        }

        if ($key === 'garantia' && $required) {
            if ($value === "" || (int)$value <= 0) {
                $value_ok = array(true, 0);
            } else {
                $value_ok = array(true, $value);
            }
        }

        if ($key === 'variacoes' && $required) {
            // validar se é variação e se tem atributos, também se todas as variações tem os mesmo tipos
            $typeVariation = array();
            $firstVariation = true;
            foreach ($value as $key => $prodvar) {
                if ($key !== 0) {
                    $firstVariation = false;
                }
                $verifyProduct = $this->getVariationForSku($prodvar->sku);
                if (!empty($verifyProduct) && $newProduct) {
                    return array(false, "SKU ( {$prodvar->sku} ) da variação já está em uso no produto com id={$verifyProduct['prd_id']}!");
                }

                if(isset($prodvar->attributes) && (count($prodvar->attributes) == 0)){
                    return array(false, "Essa variação está sem atributo. Informe variações (Variações aceitas: Cor/Tamanho/Voltagem). SKU={$prodvar->sku}");
                }

                $verification_sku = $this->verifyFieldsProduct('sku', $prodvar->sku, true, 'S');
                if (!$verification_sku[0]) {
                    return array(false, $verification_sku[1]);
                }
                $verification_ean = $this->verifyFieldsProduct('ean', $prodvar->ean, true, 'S');
                if (!$verification_ean[0]) {
                    return array(false, $verification_ean[1]);
                }

                foreach ($prodvar->attributes as $attr) {
                    if (!array_key_exists($attr->code, $this->tipoVariacoes)) {
                        return array(false, "Foram encontradas variações não compatíveis com umas das variações (Variações aceitas: Cor/Tamanho/Voltagem). SKU={$prodvar->sku}");
                    } else {
                        if ($firstVariation) {
                            array_push($typeVariation, $attr->code);
                        } else {
                            if (!in_array($attr->code, $typeVariation)) {
                                return array(false, "Todas as variações devem conter os mesmos tipos. Exemplo: <br> (Var1 = Cor:Preto;Tamanho:42) (Var2 = Cor:Preto;Tamanho: 40)");
                            }
                        }
                    }
                }
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
     * @param   int     $prd_id             Id do produto
     * @param   array   $arrayVariations    Tipos de variações
     * @return  bool                        Retorna o status da atualização
     */
    public function updateHasVariations(int $prd_id, array $arrayVariations): bool
    {
        $has_variations = array();
        foreach ($arrayVariations as $value) {
            if ($value != "") {
                array_push($has_variations, $value);
            }
        }
        return (bool)$this->_this->db->where(array('id' => $prd_id))->update('products', array('has_variants' => implode(';', $has_variations)));
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
        if ($verifyProduct)
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

        // não podeerá mais ser atualizado
        if ($this->getPrdIntegration($idProd))
            return array('success' => false, "Produto {$skuPai}, não pode mais receber novas variações pois já está integrado com o marketplace.");

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

        if (count($payload->grade) != count($varArr)) {
            return array('success' => false, 'message' => "Foram encontradas variações não compatíveis com umas das variações (Variações aceitas: Cor/Tamanho/Voltagem).");
        }

        if ((array_diff($typesVariations, $tipoVarArr) || array_diff($tipoVarArr, $typesVariations)) && count($typesVariations)) {
            return array('success' => false, 'message' => "Foram encontradas tipos de variações para essa variação que não estão cadastradas no produto. Todas as variações devem conter os mesmos tipos (Cor, Tamanho e Voltagem) ");
        }

        // formatar variações para criação
        $varStr = "";
        if (array_key_exists('TAMANHO', $varArr))   $varStr .= ";{$varArr['TAMANHO']}";
        if (array_key_exists('Cor', $varArr))       $varStr .= ";{$varArr['Cor']}";
        if (array_key_exists('VOLTAGEM', $varArr)) {
            $varStr .= ";{$varArr['VOLTAGEM']}";
            // Se não tiver 'V' no número da voltagem, adicionar
            $unityVoltage = strpos(strtoupper($varArr['VOLTAGEM']), 'V');
            if (!$unityVoltage) $varStr .= 'V';
        }
        $varStr = substr($varStr, 1);

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
        $verification_sku = $this->verifyFieldsProduct('sku', $skuVar, true, 'S', true, $idProd);
        if (!$verification_sku[0]) {
            return array('success' => $verification_sku[0], 'message' => $verification_sku[1]);
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

            $varStr = $varStr != "" ? substr($varStr, 1) : "";

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
        $skuVar  = $payload->sku;
        $idProdPai = $prdId;

        $variationToUpdate = array(
            'EAN'               => $payload->ean ?? '',
            'variant_id_erp'    => $payload->id ?? '',
            'sku'               => $payload->sku ?? '',
            'price'             => $payload->price ?? '',
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
     * @return  array               Retorna o status da atualização
     */
    public function updateProduct($payload, $fullUpdate = false)
    {
        $id_produto = $payload->id;
        $skuProduct = $payload->sku;

        if (!$this->_this->store) {
            //echo "Ocorreu um problema para criar produto id_PluggTo={$id_produto}. Loja nao encontrada, id Conta Pluggto: " . $ID_contaSeller . "\n";
            $this->_this->log_data('batch', 'Product/Create', "Ocorreu um problema para criar produto id_PluggTo={$id_produto}. Loja nao encontrada, id Conta Pluggto: ", "E");
            return array('success' => false, 'message' =>  'Loja nao encontrada, id Conta Pluggto');
        }

        if (isset($payload->codigoPai)) {
            //echo "Esse produto é uma variação, aqui só entra produto pai ou simples";
            return array('success' => false, 'message' => array('Essa variação está tentando cadastrar como um produtos pai, verifique se o sku da variação é diferente do sku do produto pai, para corrigo-lo'));
        }

        if (isset($payload->tipo) && $payload->tipo != "P") {
            //echo "Chegou um tipo que não é um produto. Chegou={$payload->tipo}";
            return array('success' => false, 'message' => array('Está tentando criar um tipo que não é um produto, recebemos o seguinte tipo: ' . $payload->tipo));
        }

        if (!isset($payload->codigoNoFabricante)) {
            $payload->codigoNoFabricante = '';
        }

        $category_replace = "";
        foreach($payload->categories as $cat)
        {
            $category_replace = str_replace(' > ', '/', $cat->name);
        }      

        // type - S=string, "F"=float, "I"=integer, "A"=array
        $product = array(
            'nome'              => array('value' => $payload->name, 'required' => true, 'type' => 'S', 'field_database' => 'name'),
            'unidade'           => array('value' => '', 'required' => true, 'type' => 'S', 'field_database' => 'attribute_value_id'),
            'cest'              => array('value' => $payload->cest, 'required' => true, 'type' => 'S', 'field_database' => 'CEST'),
            'ncm'               => array('value' => $payload->ncm, 'required' => true, 'type' => 'S', 'field_database' => 'NCM'),
            'origem'            => array('value' => $payload->origin ?? 0, 'required' => true, 'type' => 'I', 'field_database' => 'origin'),
            'ean'               => array('value' => $payload->ean ?? '', 'required' => true, 'type' => 'S', 'field_database' => 'EAN'),
            'peso_liquido'      => array('value' => $payload->dimension->weight ?? 0, 'required' => true, 'type' => 'F', 'field_database' => 'peso_liquido'),
            'peso_bruto'        => array('value' => $payload->dimension->weight ?? 0, 'required' => true, 'type' => 'F', 'field_database' => 'peso_bruto'),
            'sku_fabricante'    => array('value' => $payload->codigoNoFabricante ?? '', 'required' => true, 'type' => 'S', 'field_database' => 'codigo_do_fabricante'),
            'descricao'         => array('value' => $payload->description ?? '', 'required' => false, 'type' => 'S', 'field_database' => 'description'),
            'garantia'          => array('value' => $payload->warranty_time, 'required' => true, 'type' => 'I', 'field_database' => 'garantia'),
            'fabricante'        => array('value' => $payload->brand ?? '', 'required' => true, 'type' => 'S', 'field_database' => 'brand_id'),
            'altura'            => array('value' => $payload->dimension->height, 'required' => true, 'type' => 'F', 'field_database' => 'altura'),
            'comprimento'       => array('value' => $payload->dimension->length, 'required' => true, 'type' => 'F', 'field_database' => 'profundidade'),
            'largura'           => array('value' => $payload->dimension->width, 'required' => true, 'type' => 'F', 'field_database' => 'largura'),
            'categoria'         => array('value' => $category_replace ?? "", 'required' => true, 'type' => 'A', 'field_database' => 'category_imported'),
            'variacoes'         => array('value' => $payload->variations ?? array(), 'required' => true, 'type' => 'A', 'field_database' => 'has_variants'),
            'prazo_operacional_extra' => array('value' => $payload->manufacture_time ?? 0, 'required' => true, 'type' => 'I', 'field_database' => 'prazo_operacional_extra')
        );

        // Validar e formatar campos
        $productFormat = $this->getDataFormat($product, false);

        // Encontrou erro na formatação dos dados
        if (isset($productFormat['success']) && !$productFormat['success']) {
            return $productFormat;
        }

        $productFormat['product_id_erp'] = $payload->id;
        $productFormat['sku'] = $payload->sku;
        $productFormat['store_id'] = $this->_this->store;

        //cadastro de medidas sem embalagem
        $productFormat['actual_depth']  = $payload->real_dimension->length ?? 0;
        $productFormat['actual_height'] = $payload->real_dimension->height ?? 0;
        $productFormat['actual_width']  = $payload->real_dimension->width  ?? 0;

        $errosVar = array();
        $successVar = array();

        $dataProduct = $this->getProductForSku($payload->sku);

        if (isset($payload->path_images) && isset($payload->photos)) {
            $path = $this->getPathNewImage($payload->path_images);
            $images = $this->getImages($payload->photos, $path['path_complet']);
            if (!$images['success']) {
                // encontrou erro no upload de imagem
                //echo "erro upload imagem\n";
                return $images;
            }

            //define nome da pasta com as imagens e a imagem principal
            $productFormat['principal_image'] = $images['primary'];

            if (
                ($dataProduct['category_id'] != '[""]' || $productFormat['category_id'] != '[""]') &&
                ($dataProduct['brand_id'] != '[""]' || $productFormat['brand_id'] != '[""]') &&
                $this->_this->uploadproducts->countImagesDir($dataProduct['image'])
            )
                $productFormat['situacao'] = 2;
            else
                $productFormat['situacao'] = 1;
        }


        //atualiza variações
        $prd_id = $dataProduct['id'];

        if (isset($payload->variations) && (count($payload->variations) > 0) && isset($prd_id)) {
            foreach ($payload->variations as $prodvar) {
                $this->updateVariation($prodvar, $prd_id);
            }
        }

        // remove os itens que não derão ser atualizados
        if ($fullUpdate == false) {
            foreach ($this->naoAtualizar as $field)
                unset($productFormat[$field]);
        }

        if ($this->getProductForAttributes($productFormat))
            return array('success' => null, 'errorsVar' => $errosVar, 'successVar' => $successVar);

        $sqlProd = $this->_this->db->update_string('products', $productFormat, array('sku' => $payload->sku, 'store_id' => $this->_this->store));
        $update = $this->_this->db->query($sqlProd);

        // bloqueia produto se necessário
        $this->CI->blacklistofwords->updateStatusProductAfterUpdateOrCreate($this->getProductForSku($payload->sku), $dataProduct['id']);

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
        return $this->_this->db->get_where(
            'prd_variants',
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
        return $this->_this->db->get_where(
            'prd_variants',
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
        return $this->_this->db->get_where(
            'prd_variants',
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
        $curl_handle = curl_init($url . $data);

        if ($method == "GET") {
            curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, 1);
        } elseif ($method == "POST" || $method == "PUT") {
            if ($method == "PUT")
                curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'PUT');

            if ($method == "POST")
                curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'POST');

            curl_setopt($curl_handle, CURLOPT_URL, $url);
            curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $data);
        }

        if ($header_opt == 'Content-Type: application/x-www-form-urlencoded') {
            curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array(
                "Content-Type: application/x-www-form-urlencoded",
            ));
        } else {
            curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array(
                "Content-Type: application/json",
            ));
        }

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
        if (!$this->_this->store) {
            //echo "Ocorreu um problema para criar produto SKU={$sku}. Loja nao encontrada, id Conta Pluggto: " . $this->id_user_pluggto . "\n";
            $this->_this->log_data('batch', 'Product/Create', "Ocorreu um problema para criar produto SKU={$sku}. Loja nao encontrada, id Conta Pluggto: " . $this->id_user_pluggto, "E");
            return false;
        }

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


    public function getStockVariationForSku($skuVar, $skuPai)
    {

        if (!$this->_this->store) {
            //echo "Ocorreu um problema para criar produto SKU={$skuPai}. Loja nao encontrada, id Conta Pluggto: " . $this->id_user_pluggto . "\n";
            $this->_this->log_data('batch', 'Product/Create', "Ocorreu um problema para criar produto SKU={$skuPai}. Loja nao encontrada, id Conta Pluggto: " . $this->id_user_pluggto, "E");
            return array('success' => false, 'message' => 'Loja nao encontrada');
        }

        $queryProd = $this->_this->db->get_where('products use index (store_sku)', array('store_id' => $this->_this->store, 'sku' => $skuPai));
        $resultProd = $queryProd->row_array();

        $this->_this->db->where(
            array(
                'prd_id'    => $resultProd['id'],
                'sku'       => $skuVar,
            )
        );

        $query = $this->_this->db->get_where('prd_variants', array('prd_id' => $resultProd['id'], 'sku' => $skuVar));
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
            //echo "API Bloqueada, vou esperar 30 segundos e tentar novamente (Tentativas: {$this->_this->countAttempt}/{$attempts})...\n";
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
        $this->_this->db->where(array('prd_id' => $product['id'], 'sku' => $sku))->update('prd_variants', array('qty' => $qty));

        $newQty = 0;
        foreach ($variations as $variation) {

            if ($variation['sku'] == $sku) $variation['qty'] = $qty; // define a nova quantidade

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
        $product = $this->getProductForSku($sku);
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

        $skuProduct = $payload->sku;



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
     
     /**
    public function getUseListPrice()
    {
        $query = $this->_this->db->get_where('api_integrations', array('store_id' => $this->_this->store))->row_array();

        $credentials = json_decode($query['credentials']);

        $list = $credentials->lista_PluggTo;

        if ($list == "" || !$list) return false;

        return true;
    }
     */

    /**
     * Recupera o preço do produto na lista de preço
     *
     * @param   int     $idProduct  Código do produto
     * @return  array   Retorna um array com o status da consulta e valor
     */
    /**
    public function getPriceVariationListPrice($products)
    {
        $PluggTo_URL = $this->getUrlPluggTo($this->_this->store);
        foreach ($products as $product) {
            // Consulta endpoint par obter estoque            
            $url = $PluggTo_URL.'/api/estoques/';
            $data = "{$product},";

            if (function_exists('sendREST'))
                $dataPrice = json_decode($this->_this->sendREST($url, $data));
            else
                $dataPrice = json_decode($this->_sendREST($url, $data));

            // Ocorreu um problema
            if ($dataPrice->retorno->status != "OK") {
                if ($dataPrice->retorno->codigo_erro != 200) {
                    echo "Ocorreu um problema para obter o preço da produto_id_PluggTo={$product} retorno=" . json_encode($dataPrice) . "\n";
                    $this->_this->log_data('batch', 'Product/getStock', "Ocorreu um problema para obter o estoque do produto_id_PluggTo={$product} retorno=" . json_encode($dataPrice), "E");
                }
                return array('success' => false);
            }

            return array('success' => true, 'value' => (float)$dataPrice->retorno->registros[0]->registro->preco);
        }
    }
     */

    /**
     * Remover todos os acentos
     *
     * @param   string  $string Texto para remover os acentos
     * @return  string
     */
    public function removeAccents($string)
    {
        return preg_replace(array("/(á|à|ã|â|ä)/", "/(Á|À|Ã|Â|Ä)/", "/(é|è|ê|ë)/", "/(É|È|Ê|Ë)/", "/(í|ì|î|ï)/", "/(Í|Ì|Î|Ï)/", "/(ó|ò|õ|ô|ö)/", "/(Ó|Ò|Õ|Ô|Ö)/", "/(ú|ù|û|ü)/", "/(Ú|Ù|Û|Ü)/", "/(ñ)/", "/(Ñ)/"), explode(" ", "a A e E i I o O u U n N"), $string);
    }
}
