<?php
require APPPATH . "libraries/Traits/VerifyFieldsProduct.trait.php";
class Product
{
    use VerifyFieldsProduct;
    public $allowable_tags = null;

    // tipo de variação para comparar, INFORMAR EM MINÚSCULO A CHAVE
    public $tipoVariacoes = array(
        'tamanho' => 'TAMANHO',
        'Tamanho' => 'TAMANHO',
        'size' => 'TAMANHO',
        'tam' => 'TAMANHO',
        'sapato' => 'TAMANHO',
        'calcado' => 'TAMANHO',
        'calçado' => 'TAMANHO',
        'short' => 'TAMANHO',
        'calca' => 'TAMANHO',
        'calça' => 'TAMANHO',
        'camisa' => 'TAMANHO',
        'mascara' => 'TAMANHO',
        'aro' => 'TAMANHO',
        'cor' => 'Cor',
        'color' => 'Cor',
        'Cores' => 'Cor',
        'voltagem' => 'VOLTAGEM',
        'Voltagem' => 'VOLTAGEM',
        'voltage' => 'VOLTAGEM',
        'volts' => 'VOLTAGEM'
    );
    // tipo de unidade para comparar, INFORMAR EM MINÚSCULO A CHAVE       
    // 1 = Un
    // 2 = Kg
    private $tipoUnidades = array(
        'CX' => '1', // Caixa
        'KT' => '1', // Kit
        'KG' => '2', // Kilo
        'M2' => '1', // Metro quadrado
        'PR' => '1', // Par
        'PT' => '1', // Pacote
        'PC' => '1', // Peça
        'SC' => '1', // Saco
        'UN' => '1'  // Unidae
    );
    private $naoAtualizar = array(
        'status',
        'image',
        'store_id',
        'company_id',
        'category_id',
        'has_variants'
    );
    // Passagem de dados
    private $_this;

    public function __construct($_this)
    {
        $this->_this = $_this;
        $this->_this->load->model('model_products');
        $this->_this->load->model('model_settings');

        if ($allowableTags = $this->_this->model_settings->getValueIfAtiveByName('products_allowable_tags')) {
            if (!empty($allowableTags)) {
                $this->allowable_tags = '<' . implode('><', explode(',', $allowableTags)) . '>';
            }
        }

        $this->disable_brand_creation = ($this->_this->model_settings->getStatusbyName('disable_brand_creation_by_seller') == 1);
        $this->loadLengthSettings();
    }

    public function getProductForIdErp($id_erp, $sku = null)
    {
        $query = $this->_this->db->get_where(
            'products use index (store_iderp)',
            array(
                'store_id' => $this->_this->store,
                'product_id_erp' => $id_erp
            )
        );
        if ($query->num_rows() == 0)
            return null;

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
                'sku' => $sku,
                'store_id' => $this->_this->store,
            )
        );
        return $this->_this->db->update('products', $data) ? true : false;
    }

    public function getUrlBseller($store_id)
    {
        $dataIntegrationStore = $this->_this->db
            ->from('api_integrations')
            ->where('store_id', $store_id)
            ->get()
            ->result_array();

        if ($dataIntegrationStore) {
            $credentials = json_decode($dataIntegrationStore[0]['credentials']);
            return $credentials->url_bseller;
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

        return $this->_this->db->get_where(
            'products use index (store_sku)',
            array(
                'store_id' => $this->_this->store,
                'sku' => $sku
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
                    'products.store_id' => $this->_this->store,
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
    public function getPathNewImage($dirProdutoPai = null)
    {
        $serverpath = $_SERVER['SCRIPT_FILENAME'];
        $pos = strpos($serverpath, 'assets');
        $serverpath = substr($serverpath, 0, $pos);
        if ($dirProdutoPai !== null) {
            $targetDir = $serverpath . 'assets/images/product_image/' . $dirProdutoPai . '/';
        } else {
            $targetDir = $serverpath . 'assets/images/product_image/';
        }
        $dirImage = $path ?? Admin_Controller::getGUID(false); // gero um novo diretorio para as imagens
        $targetDir .= $dirImage;

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

        foreach ($arrImages as $image) {

            $url = $image;



            // não encontrou imagem para upload
            if (empty($url))
                return array('success' => false, 'message' => array('Imagem invalida, não foi possível recuperar a imagem, chegou em branco!'));
            // dd($path, $url);
            $upload = $this->_this->uploadproducts->sendImageForUrl("{$path}/", $url);

            // não conseguiu fazer upload da imagem
            if (!$upload['success'])
                return array('success' => false, 'message' => array("Não foi possível salvar a imagem. Imagem recebida: {$url} <br>{$upload['data']}"));

            if ($primaryImage === null)
                $primaryImage = base_url("{$path}/{$upload['path']}");

            array_push($arrNameImages, $upload['path']);
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
                return (string) $value;
            case 'A':
                return (array) $value;
            case 'F':
                return (float) $value;
            case 'I':
                return (int) $value;
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
    public function getDataFormat($product, $vendavel = true)
    {
        // cria array com valores pré-definidos
        $productFormat = array(
            //'image'                 => array(),
            'image' => 1,
            'status' => $vendavel ? 1 : 2, // ativo default
            'store_id' => $this->_this->store,
            'company_id' => $this->_this->company,
            'category_id' => '[""]',
            'brand_id' => '[""]',
            'garantia' => 0,
        );

        $erros = array();

        foreach ($product as $key => $field) {
            $field_format = $this->verifyFieldsProduct($key, $field['value'], $field['required'], $field['type']);
            // dd($field_format);
            // encontrou um erro, deve encerrar a criação do produto e apresentar o motivo
            if (!$field_format[0])
                array_push($erros, $field_format[1]);



            if ($key == "imagesAnexo" || $key == "imagesExterna" || $key == "imagens")
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
            $BSELLER_URL = $this->getUrlBseller($this->_this->store);
            $url = $BSELLER_URL . 'api/produtos/' . $product;
            $data = "{$product}";


            $dataStockProduct = json_decode(json_encode($this->_this->sendREST($url, $data)));

            // Ocorreu um problema
            if ($dataStockProduct->httpcode != 200) {
                echo "Ocorreu um problema para obter o estoque do produto_id={$product} retorno=" . json_encode($dataStockProduct) . "\n";
                $this->_this->log_data('batch', 'Product/getStock', "Ocorreu um problema para obter o estoque do produto_id_bseller={$product} retorno=" . json_encode($dataStockProduct), "E");
                return array('success' => false, 'message' => 'Ocorreu um problema para obter o estoque, caso use uma lista de preço verifique se o produto/variação está na lista!');
            }

            $prodRes = json_decode($dataStockProduct->content);

            //$qtyProductReserved = $dataStockProduct->retorno->produto->saldoReservado ?? 0;
            // Conferir Estoque no BSeller
            // $qtyProduct = $prodRes->_Estoque->estoqueDisponivel ?? 0;
            $qtyProduct = 0; // setado como zero

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
        return $this->_this->db->get_where(
            'products use index (store_sku)',
            array(
                'store_id' => $this->_this->store,
                'sku' => $sku
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
        // Consulta endpoint par obter estoque
        $BSELLER_URL = $this->getUrlBseller($this->_this->store);
        $itemPai = (int) $payload->codigoItemPai;
        $limite_imagens_aceitas_api = $this->_this->model_settings->getValueIfAtiveByName('limite_imagens_aceitas_api') ?? 6;
        if(!isset($limite_imagens_aceitas_api) || $limite_imagens_aceitas_api <= 0) { $limite_imagens_aceitas_api = 6;}
        //----------------------------------------------------------------------
        //é um produto variacao
        //----------------------------------------------------------------------
        if ($itemPai > 0) {
            $urlEsp = $BSELLER_URL . "api/itens/$itemPai?tipoInterface=" . $this->_this->interface;
            $dataProductPai = json_decode(json_encode($this->_this->sendREST($urlEsp, '')));

            if ($dataProductPai->httpcode != 200) {
                echo "Produto Bseller com ID={$payload->codigoTerceiro} encontrou um erro, retorno=" . json_encode($dataProductPai) . "\n";
                return array('success' => false, 'message' => array("Produto Bseller com ID={$payload->codigoTerceiro} encontrou um erro"));
            }

            $produtoCompleto = json_decode($dataProductPai->content);

            // Recupera o código do produto pai
            $skuProduct = $produtoCompleto->codigoFornecedor;
            $verifyProductPai = $this->getProductForIdErp($itemPai, $skuProduct);

            if (!empty($verifyProductPai)) {
                $urlEsp = $BSELLER_URL . "api/itens/" . $itemPai . "/filhos?tipoInterface=" . $this->_this->interface;
                $dataProduct = json_decode(json_encode($this->_this->sendREST($urlEsp, '')));

                if ($dataProduct->httpcode == 200) {
                    $filhos = json_decode($dataProduct->content);

                    //-----------------------------------------
                    //PERCORRE TODAS AS VARIAÇOES DE CADA FILHO
                    //-----------------------------------------
                    $hasVariations = array();
                    $itensFilho = 0;
                    foreach ($filhos as $filho) {
                        $verifyProduct = $this->getVariationForIdErp($filho->codigoTerceiro);
                        if (empty($verifyProduct)) {
                            $variation_name = '';
                            foreach ($filho->variacoes as $variacao) {
                                if (!array_key_exists($variacao->tipoVariacao, $this->tipoVariacoes)) {
                                    echo "O produto sku = $payload->sku, tem uma variação $variacao->tipoVariacao, não compatível com o sistema...\n";
                                    return array('success' => false);
                                } else {
                                    $variationType = $this->tipoVariacoes[$variacao->tipoVariacao];
                                    if ($variationType == 'VOLTAGEM') {
                                        if ($this->likeText("%220%", strtolower($variacao->variacao))) {
                                            if ($variation_name == '') {
                                                $variation_name .= '220V';
                                            } else {
                                                $variation_name .= ';220V';
                                            }
                                        } else {
                                            if ($variation_name == '') {
                                                $variation_name .= '110V';
                                            } else {
                                                $variation_name .= ';110V';
                                            }
                                        }
                                    } else {
                                        if ($variation_name == '') {
                                            $variation_name .= $variacao->variacao;
                                        } else {
                                            $variation_name .= ';' . $variacao->variacao;
                                        }
                                    }
                                    if (!in_array($variationType, $hasVariations)) {
                                        $hasVariations[] = $variationType;
                                    }
                                }
                            }

                            //tudo certo, inserir a variacao do filho
                            if (!empty($variation_name)) {
                                $urlEsp = $BSELLER_URL . "api/itens/$filho->codigoTerceiro?tipoInterface=" . $this->_this->interface;
                                $dataEsp = '';
                                $dataProduct = json_decode(json_encode($this->_this->sendREST($urlEsp, $dataEsp)));
                                if ($dataProduct->httpcode == 200) {
                                    $produtoFilho = json_decode($dataProduct->content);

                                    $eanUnicoFilho = '';
                                    if (isset($produtoFilho->ean)) {
                                        foreach ($produtoFilho->ean as $ean) {
                                            if ($ean->preferencial == true || $ean->preferencial == 1) {
                                                $eanUnicoFilho = $ean->codEan;
                                            }
                                        }
                                    }
                                    $estoqueFilho = $this->getEstoqueProduto($filho->codigoTerceiro);
                                    if ($estoqueFilho == 0) {
                                        return array('success' => false, 'message' => 'Ocorreu um problema para obter o estoque, caso use uma lista de preço verifique se o produto/variação está na lista!');
                                    }
                                    $preco = 0;
                                    $list_price = 0;
                                    // Preço de é obrigatório no BSeller
                                    if (!isset($produtoFilho->preco[0]->precoDe)) {
                                        echo "Não tem um preço cadastrado. \n";
                                        return array('success' => false, 'message' => array('Não tem um preço cadastrado.'));
                                    } else {
                                        if (isset($produtoFilho->preco[0]->precoDe) && $produtoFilho->preco[0]->precoDe > 0) {
                                            $preco = $produtoFilho->preco[0]->precoDe;
                                            $list_price = $produtoFilho->preco[0]->precoDe;
                                        }
                                        if (isset($produtoFilho->preco[0]->precoPor) && $produtoFilho->preco[0]->precoPor > 0) {
                                            $preco = $produtoFilho->preco[0]->precoPor;
                                        }

                                    }

                                    //--------------------------------------------------
                                    // upload de imagens, so é permitido 6 imagens;
                                    $imgArray = array();
                                    $qtdImage = 0;
                                    foreach ($produtoFilho->imagens as $img) {
                                        if ($qtdImage >= $limite_imagens_aceitas_api) {
                                            continue;
                                        }
                                        $linkImg = $img->link;
                                        $imgArray[] = $linkImg;
                                        $qtdImage++;
                                    }


                                    $pathFilho = $this->getPathNewImage($verifyProductPai['image']); // folder que receberá as imagens

                                    if (count($imgArray) > 0) {
                                        $produtoFilho->anexos = $imgArray;

                                        // Faz upload e recupera os códigos
                                        $images = $this->getImages($produtoFilho->anexos, $pathFilho['path_complet']);
                                        if (!$images['success']) {
                                            // encontrou erro no upload de imagem
                                            return array('success' => false, 'message' => '1 ou mais imagem do produto inválida');
                                        }
                                    }
                                    $variants = $this->_this->model_products->getVariantsByProd_id($verifyProductPai['id']);
                                    $estoqueFilho = $this->getEstoqueProduto($produtoFilho->codigoTerceiro);

                                    $this->_this->model_products->createvar(
                                        array(
                                            'prd_id' => $verifyProductPai['id'] ?? 0,
                                            'variant' => count($variants),
                                            'name' => $variation_name ?? '',
                                            'sku' => $produtoFilho->codigoFornecedor ?? '',
                                            'variant_id_erp' => $produtoFilho->codigoTerceiro ?? 0,
                                            'image' => (isset($pathFilho['path_product'])) ? $pathFilho['path_product'] : '',
                                            'status' => $produtoFilho->vendavel ? 1 : 2,
                                            'EAN' => $eanUnicoFilho,
                                            'codigo_do_fabricante' => $produtoFilho->codigoFornecedor,
                                            'qty' => $estoqueFilho,
                                            'price' => $preco,
                                            'list_price' => $list_price
                                        )
                                    );
                                    //atualiza o estoque o produto pai
                                    $this->_this->model_products->updateEstoqueProdutoPai($verifyProductPai['id']);
                                    $itensFilho = $itensFilho + 1;
                                }
                            }
                        } else {
                            $itensFilho = $itensFilho + 1;
                        }
                    }
                    if (!empty($hasVariations)) {
                        $this->updateHasVariations($verifyProductPai['id'], $hasVariations);
                    }
                }
            }
            return array('success' => true);
        }
        //----------------------------------------------------------------------
        //é um produto normal ou um produto pai
        //----------------------------------------------------------------------
        else {
            if (isset($payload->tipoItem) && $payload->tipoItem != "P") {
                echo "Chegou um tipo que não é um produto. Chegou={$payload->tipoItem}";
                return array('success' => false, 'message' => array('Está tentando criar um tipo que não é um produto, recebemos o seguinte tipo: ' . $payload->tipoItem));
            }

            if (!isset($payload->codigoNoFabricante)) {  // Conferir se tem no BSeller
                $payload->codigoNoFabricante = '';
            }
            $eanUnico = '';
            if (isset($payload->ean)) {
                foreach ($payload->ean as $ean) {
                    if ($ean->preferencial == true || $ean->preferencial == 1) {
                        $eanUnico = $ean->codEan;
                    }
                }
            }

            /*
             * Para o preço, pode haver 2 valores: "precoDe" e "precoPor"
             * No caso de existir valor para o "precoPor" ele deve sobrepor o "precoDe"
             */
            $preco = 0;
            $list_price = 0;
            // Preço de é obrigatório no BSeller
            if (!isset($payload->preco[0]->precoDe)) {
                echo "Não tem um preço cadastrado. \n";
                return array('success' => false, 'message' => array('Não tem um preço cadastrado.'));
            } else {
                if (isset($payload->preco[0]->precoDe) && $payload->preco[0]->precoDe > 0) {
                    $preco = $payload->preco[0]->precoDe;
                    $list_price = $payload->preco[0]->precoDe;
                }
                if (isset($payload->preco[0]->precoPor) && $payload->preco[0]->precoPor > 0) {
                    $preco = $payload->preco[0]->precoPor;
                }
            }

            $fabricante = '';
            if (isset($payload->marca->nome) && $payload->marca->nome) {
                $fabricante = '["' . $this->getCodeInfoProduct('brands', 'name', $payload->marca->nome) . '"]';
            }

            //---------------
            $categoria = '';
            if (isset($payload->categorias) && (count($payload->categorias) > 0)) {
                foreach ($payload->categorias as $key => $value) {
                    if ($key == 0) {
                        $categoriaID = $this->getCodeInfoProduct('categories', 'name', $value->nome);
                        if (!$categoriaID) {
                            //insere a categoria
                            $sqlBrand = $this->_this->db->insert_string('categories', array('name' => $value->nome, 'active' => 1));

                            $this->_this->db->query($sqlBrand);
                            $query = $this->_this->db
                                ->select('id')
                                ->from('categories')
                                ->where(array('name' => $value->nome))
                                ->get();
                            $result = $query->first_row();

                            $categoriaID = $result->id;
                        }
                        $categoria = '["' . $categoriaID . '"]';
                    }
                }
            }

            $fabricante = $payload->fornecedor->id;
            // type - S=string, "F"=float, "I"=integer, "A"=array
            $product = array(
                'nome' => array('value' => $payload->nome, 'required' => true, 'type' => 'S', 'field_database' => 'name'),
                'sku' => array('value' => $payload->codigoFornecedor, 'required' => true, 'type' => 'S', 'field_database' => 'sku'),
                'unidade' => array('value' => $payload->unidadeMedida->id, 'required' => true, 'type' => 'S', 'field_database' => 'attribute_value_id'),
                'preco' => array('value' => $preco, 'required' => true, 'type' => 'F', 'field_database' => 'price'),
                'list_price' => array('value' => $list_price, 'required' => true, 'type' => 'F', 'field_database' => 'list_price'),
                'ncm' => array('value' => $payload->ncm->codigoNcm, 'required' => true, 'type' => 'S', 'field_database' => 'NCM'),
                'origem' => array('value' => $payload->procedencia->id, 'required' => true, 'type' => 'I', 'field_database' => 'origin'),
                'EAN' => array('value' => '' . $eanUnico . '', 'required' => true, 'type' => 'S', 'field_database' => 'EAN'),
                'peso_liquido' => array('value' => $payload->peso->unitario, 'required' => true, 'type' => 'F', 'field_database' => 'peso_liquido'),
                'peso_bruto' => array('value' => $payload->peso->bruto, 'required' => true, 'type' => 'F', 'field_database' => 'peso_bruto'),
                'sku_fabricante' => array('value' => '', 'required' => true, 'type' => 'S', 'field_database' => 'codigo_do_fabricante'),
                'descricao' => array('value' => $payload->descricao, 'required' => false, 'type' => 'S', 'field_database' => 'description'),
                'garantia' => array('value' => $payload->prazoGarantiaFornecedor, 'required' => true, 'type' => 'I', 'field_database' => 'garantia'),
                'fabricante' => array('value' => $fabricante, 'required' => true, 'type' => 'S', 'field_database' => 'brand_id'),
                'altura' => array('value' => $payload->dimensoes->altura, 'required' => true, 'type' => 'F', 'field_database' => 'altura'),
                'comprimento' => array('value' => $payload->dimensoes->comprimento, 'required' => true, 'type' => 'F', 'field_database' => 'profundidade'),
                'largura' => array('value' => $payload->dimensoes->largura, 'required' => true, 'type' => 'F', 'field_database' => 'largura'),
                // Ajustes Categorias p.category_id, p.category_imported
                'categoria' => array('value' => $categoria, 'required' => true, 'type' => 'S', 'field_database' => 'category_id'),
                'prazo_operacional_extra' => array('value' => 0, 'required' => true, 'type' => 'I', 'field_database' => 'prazo_operacional_extra'),
                // 'vendavel'=>$payload->vendavel
            );

            // Validar e formatar campos
            $productFormat = $this->getDataFormat($product, $payload->vendavel);


            // Encontrou erro na formatação dos dados
            if (isset($productFormat['success']) && !$productFormat['success']) {
                return $productFormat;
            }

            $productFormat['product_id_erp'] = $payload->codigoTerceiro;

            if (isset($productFormat['image'][0]) && count($productFormat['image'][0]) == 1 && $productFormat['image'][0][0] == "")
                $productFormat['image'] = array([]);

            // upload de imagens, so é permitido 6 imagens;
            $imgArray = array();
            $qtdImage = 0;
            foreach ($payload->imagens as $img) {
                if ($qtdImage >= $limite_imagens_aceitas_api) {
                    continue;
                }
                $linkImg = $img->link;
                $imgArray[] = $linkImg;
                $qtdImage++;
            }


            $path = $this->getPathNewImage(); // folder que receberá as imagens

            if (count($imgArray) > 0) {
                $payload->anexos = $imgArray;

                // Faz upload e recupera os códigos
                $images = $this->getImages($payload->anexos, $path['path_complet']);
                if (!$images['success']) {
                    // encontrou erro no upload de imagem
                    return array('success' => false, 'message' => '1 ou mais imagem do produto inválida');
                }
            }

            //define nome da pasta com as imagens e a imagem principal
            $productFormat['image'] = $path['path_product'];

            if (isset($images['primary'])) {
                $productFormat['principal_image'] = $images['primary'];
            }

            // recupera as variações para inserção e define o has_variants com os tipos
            $productFormat['has_variants'] = "";


            //----------------------------------------------------------------------
            //get estoque do produto
            //----------------------------------------------------------------------
            if ($payload->controleItem->nome == 'Item Pai') {
                $stock = 0;
            } else {
                $stock = $this->getEstoqueProduto($payload->codigoTerceiro);
            }
            if ($stock === false) {
                return array('success' => false, 'message' => 'não foi possível recuperar o estoque na API do Bseller');
            }
            $productFormat['qty'] = $stock;
            $productFormat['store_id'] = $this->_this->store;


            // Inserção produto e pegar id para passar nas variações
            $this->analizeProductSituacao($productFormat, $productFormat);
            $prd_id = $this->_this->model_products->create($productFormat);

            //----------------------------------------
            //todas as variaçoes do PAI
            //----------------------------------------
            $variacoesPAI = array();
            $variation_namePAI = '';
            foreach ($payload->variacoes as $variacao) {
                if (!array_key_exists($variacao->tipoVariacao, $this->tipoVariacoes)) {
                    echo "O produto sku = $payload->sku, tem uma variação $variacao->tipoVariacao, não compatível com o sistema...\n";
                    return array('success' => false);
                } else {
                    $variationType = $this->tipoVariacoes[$variacao->tipoVariacao];
                    if ($variationType == 'VOLTAGEM') {
                        if ($this->likeText("%220%", strtolower($variacao->variacao))) {
                            $variation_namePAI .= '220V' . ";";
                        } else {
                            $variation_namePAI .= '110V' . ";";
                        }
                    } else {
                        $variation_namePAI .= $variacao->variacao . ";";
                    }

                    if (!in_array($variationType, $variacoesPAI)) {
                        $variacoesPAI[] = $variationType;
                    }
                }
            }
            //insere a variação do proprio pai
            if (!empty($variation_namePAI)) {
                $variants = $this->_this->model_products->getVariantsByProd_id($prd_id);
                $estoqueFilho = $this->getEstoqueProduto($payload->codigoTerceiro);
                if (!$this->validateSkuSpace('VAR' . $payload->codigoFornecedor)) {
                    return array('success' => false, 'message' => "O sku do produto não possui caracteres especiais ou espaço em branco.");
                }
                if (!$this->validateLengthSku('VAR' . $payload->codigoFornecedor)) {
                    return array('success' => false, 'message' => "O SKU da variação não pode ter mais do que {{$this->product_length_sku} caracteres.");
                }
                $this->_this->model_products->createvar(
                    array(
                        'prd_id' => $prd_id ?? 0,
                        'variant' => count($variants),
                        'name' => $variation_namePAI ?? '',
                        'sku' => 'VAR' . $payload->codigoFornecedor ?? '',
                        'variant_id_erp' => $payload->codigoTerceiro ?? 0,
                        'image' => '',
                        'status' => $payload->vendavel ? 1 : 2,
                        'EAN' => $eanUnico,
                        'codigo_do_fabricante' => $payload->codigoFornecedor,
                        'price' => $preco,
                        'list_price' => $list_price,
                        'qty' => $stock
                    )
                );
            }
            if (!empty($variacoesPAI)) {
                $this->updateHasVariations($prd_id, $variacoesPAI);
            }

            //------------------------------------------------
            //consulta se existem filhos
            //------------------------------------------------
            $urlEsp = $BSELLER_URL . "api/itens/" . $payload->codigoTerceiro . "/filhos?tipoInterface=" . $this->_this->interface;
            $dataEsp = '';
            $dataProduct = json_decode(json_encode($this->_this->sendREST($urlEsp, $dataEsp)));

            if ($dataProduct->httpcode == 200) {
                $filhos = json_decode($dataProduct->content);

                //-----------------------------------------
                //PERCORRE TODAS AS VARIAÇOES DE CADA FILHO
                //-----------------------------------------
                $hasVariations = array();
                $itensFilho = 0;
                foreach ($filhos as $filho) {
                    $verifyProduct = $this->getVariationForPrdIdAndIdErp($prd_id, $filho->codigoTerceiro);
                    if (empty($verifyProduct)) {
                        $variation_name = '';
                        foreach ($filho->variacoes as $variacao) {
                            if (!array_key_exists($variacao->tipoVariacao, $this->tipoVariacoes)) {
                                echo "O produto sku = $payload->sku, tem uma variação $variacao->tipoVariacao, não compatível com o sistema...\n";
                                return array('success' => false);
                            } else {
                                $variationType = $this->tipoVariacoes[$variacao->tipoVariacao];
                                if ($variationType == 'VOLTAGEM') {
                                    if ($this->likeText("%220%", strtolower($variacao->variacao))) {
                                        if ($variation_name == '') {
                                            $variation_name .= '220V';
                                        } else {
                                            $variation_name .= ';220V';
                                        }
                                    } else {
                                        if ($variation_name == '') {
                                            $variation_name .= '110V';
                                        } else {
                                            $variation_name .= ';110V';
                                        }
                                    }
                                } else {
                                    if ($variation_name == '') {
                                        $variation_name .= $variacao->variacao;
                                    } else {
                                        $variation_name .= ';' . $variacao->variacao;
                                    }
                                }
                                if (!in_array($variationType, $hasVariations)) {
                                    $hasVariations[] = $variationType;
                                }
                            }
                        }

                        //tudo certo, inserir a variacao do filho
                        if (!empty($variation_name)) {
                            $urlEsp = $BSELLER_URL . "api/itens/$filho->codigoTerceiro?tipoInterface=" . $this->_this->interface;
                            $dataEsp = '';
                            $dataProduct = json_decode(json_encode($this->_this->sendREST($urlEsp, $dataEsp)));
                            if ($dataProduct->httpcode == 200) {
                                $produtoFilho = json_decode($dataProduct->content);

                                $eanUnicoFilho = '';
                                if (isset($produtoFilho->ean)) {
                                    foreach ($produtoFilho->ean as $ean) {
                                        if ($ean->preferencial == true || $ean->preferencial == 1) {
                                            $eanUnicoFilho = $ean->codEan;
                                        }
                                    }
                                }
                                $estoqueFilho = $this->getEstoqueProduto($filho->codigoTerceiro);
                                if ($estoqueFilho == 0) {
                                    // return array('success' => false, 'message' => 'Ocorreu um problema para obter o estoque, caso use uma lista de preço verifique se o produto/variaçao está na lista!');
                                }
                                $preco = 0;
                                $list_price = 0;
                                // Preço de é obrigatório no BSeller
                                if (!isset($produtoFilho->preco[0]->precoDe)) {
                                    echo "Não tem um preço cadastrado. \n";
                                    return array('success' => false, 'message' => array('Não tem um preço cadastrado.'));
                                } else {
                                    if (isset($produtoFilho->preco[0]->precoDe) && $produtoFilho->preco[0]->precoDe > 0) {
                                        $preco = $produtoFilho->preco[0]->precoDe;
                                        $list_price = $produtoFilho->preco[0]->precoDe;
                                    }
                                    if (isset($produtoFilho->preco[0]->precoPor) && $produtoFilho->preco[0]->precoPor > 0) {
                                        $preco = $produtoFilho->preco[0]->precoPor;
                                    }
                                }

                                //--------------------------------------------------
                                // upload de imagens, so é permitido 6 imagens;
                                $imgArray = array();
                                $qtdImage = 0;
                                foreach ($produtoFilho->imagens as $img) {
                                    if ($qtdImage >= $limite_imagens_aceitas_api) {
                                        continue;
                                    }
                                    $linkImg = $img->link;
                                    $imgArray[] = $linkImg;
                                    $qtdImage++;
                                }


                                $pathFilho = $this->getPathNewImage($path['path_product']); // folder que receberá as imagens

                                if (count($imgArray) > 0) {
                                    $produtoFilho->anexos = $imgArray;

                                    // Faz upload e recupera os códigos
                                    $images = $this->getImages($produtoFilho->anexos, $pathFilho['path_complet']);
                                    if (!$images['success']) {
                                        // encontrou erro no upload de imagem
                                        return array('success' => false, 'message' => '1 ou mais imagem do produto inválida');
                                    }
                                }
                                $variants = $this->_this->model_products->getVariantsByProd_id($prd_id);
                                $estoqueFilho = $this->getEstoqueProduto($produtoFilho->codigoTerceiro);


                                $this->_this->model_products->createvar(
                                    array(
                                        'prd_id' => $prd_id ?? 0,
                                        'variant' => count($variants),
                                        'name' => $variation_name ?? '',
                                        'sku' => $produtoFilho->codigoFornecedor ?? '',
                                        'variant_id_erp' => $produtoFilho->codigoTerceiro ?? 0,
                                        'image' => (isset($pathFilho['path_product'])) ? $pathFilho['path_product'] : '',
                                        'status' => $produtoFilho->vendavel ? 1 : 2,
                                        'EAN' => $eanUnicoFilho,
                                        'codigo_do_fabricante' => $produtoFilho->codigoFornecedor,
                                        'qty' => $estoqueFilho,
                                        'price' => $preco,
                                        'list_price' => $list_price
                                    )
                                );
                                //atualiza o estoque o produto pai
                                $this->_this->model_products->updateEstoqueProdutoPai($prd_id);
                                $itensFilho = $itensFilho + 1;
                            }
                        }
                    }
                }
                if (!empty($hasVariations)) {
                    $this->updateHasVariations($prd_id, $hasVariations);
                }
            }

            if (isset($payload->codigoFornecedor)) {

                // Mapeamento para retorno via webhook
                $arrMapeamentoBseller = array();
                if ($webhook) {
                    array_push($arrMapeamentoBseller, array("idMapeamento" => $payload->idMapeamento, "skuMapeamento" => $payload->codigo));
                }

                // formatar variações para criação
                $newPrice = 0;
                $newQty = 0;

                // atualiza preço
                if ($newPrice) {
                    $this->_this->db->where(array('sku' => $productFormat['sku'], 'store_id' => $this->_this->store))->update('products', array('price' => $newPrice, 'qty' => $newQty));
                }
            }
            return array('success' => true);
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

        if ($query->num_rows() === 0 && $table == "brands") {
            $sqlBrand = $this->_this->db->insert_string('brands', array('name' => $value, 'active' => 1));

            $this->_this->db->query($sqlBrand);
            $query = $this->_this->db
                ->select('id')
                ->from($table)
                ->where(array($column => $value))
                ->get();
        }

        if ($query->num_rows() === 0 && $table != "brands")
            return false;

        $result = $query->first_row();

        return $result->id;
    }

    /* Verifica os campos para validação
     *
     * @param   integer     $idBseller  Código da variação no bseller
     * @return  null|array              Retorna um array com dados da variação ou null caso não encontre
     */

    public function getVariationForIdErp($idBseller)
    {
        return $this->_this->db
            ->select('prd_variants.*')
            ->from('prd_variants')
            ->join('products', 'products.id = prd_variants.prd_id')
            ->where(
                array(
                    'prd_variants.variant_id_erp' => $idBseller,
                    'products.store_id' => $this->_this->store,
                )
            )
            ->get()
            ->row_array();
    }
    public function getVariationForPrdIdAndIdErp($prd_id, $idBseller)
    {
        return $this->_this->db
            ->select('prd_variants.*')
            ->from('prd_variants')
            ->join('products', 'products.id = prd_variants.prd_id')
            ->where(
                array(
                    'prd_variants.variant_id_erp' => $idBseller,
                    'products.store_id' => $this->_this->store,
                    'products.id' => $prd_id
                )
            )
            ->get()
            ->row_array();
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
     * @param   float   $list_price  PreçoDe do produto
     * @return  bool    Retorna o status da atualização
     */
    public function updatePrice($sku, $price , $list_price)
    {
        $verifyProduct = $this->getVariationForSku($sku);
        if ($verifyProduct)
            return $this->_this->db->where(array('sku' => $sku, 'id' => $verifyProduct['id']))->update('prd_variants', array('price' => $price,'list_price' => $list_price)) ? true : false;

        // Atualiza o preço do produto
        return $this->_this->db->where(array('sku' => $sku, 'store_id' => $this->_this->store))->update('products', array('price' => $price,'list_price' => $list_price)) ? true : false;
    }


    /**
     * Atualização de uma variação
     *
     * @param   object  $payload    Payload da variação para cadastro
     * @param   string  $skuPai     SKU do produto PAI
     * @return  array               Retorna o status do cadastro
     */
    public function updateVariation($payload, $prdId, $preco, $stock, $list_price)
    {
        $skuVar = $payload->codigoFornecedor;
        $skuName = $payload->nome;
        $idProdPai = $payload->codigoTerceiro;

        if ($skuVar == "")
            return array('success' => false, 'message' => "O SKU da variação não pode ser em branco.");

        if ($skuName == "")
            return array('success' => false, 'message' => "O Nome da variação não pode ser em branco.");


        $variationToUpdate = array(
            'name' => $skuName,
            'sku' => $skuVar,
            'price' => number_format($preco, 2, ",", "."),
            'list_price' => number_format($list_price, 2, ",", "."),
            'qty' => $stock ?? 0,
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
        if (isset($payload->codigoItemPai) && $payload->codigoItemPai > 0) {
            echo "Esse produto é uma variação, aqui só entra produto pai ou simples";
            return array('success' => false, 'message' => array('Essa variação está tentando cadastrar como um produtos pai, verifique se o sku da variação é diferente do sku do produto pai, para corrigo-lo'));
        }

        if (isset($payload->tipoItem) && $payload->tipoItem != "P") {
            echo "Chegou um tipo que não é um produto. Chegou={$payload->tipoItem}";
            return array('success' => false, 'message' => array('Está tentando criar um tipo que não é um produto, recebemos o seguinte tipo: ' . $payload->tipoItem));
        }

        if (!isset($payload->garantia)) {
            $payload->garantia = 0;
        }

        if (!isset($payload->codigoNoFabricante)) {
            $payload->codigoNoFabricante = '';
        }

        $eanUnico = '';
        if (isset($payload->ean)) {
            foreach ($payload->ean as $ean) {
                if ($ean->preferencial == true || $ean->preferencial == 1) {
                    $eanUnico = $ean->codEan;
                }
            }
        }

        /*
         * Para o preço, pode haver 2 valores: "precoDe" e "precoPor"
         * No caso de existir valor para o "precoPor" ele deve sobrepor o "precoDe"
         */
        $preco = 0;
        $list_price = 0;
        // Preço de é obrigatório no BSeller
        if (!isset($payload->preco[0]->precoDe)) {
            echo "Não tem um preço cadastrado. \n";
            return array('success' => false, 'message' => array('Não tem um preço cadastrado.'));
        } else {
            if (isset($payload->preco[0]->precoDe) && $payload->preco[0]->precoDe > 0) {
                $preco = $payload->preco[0]->precoDe;
                $list_price = $payload->preco[0]->precoDe;
            }
            if (isset($payload->preco[0]->precoPor) && $payload->preco[0]->precoPor > 0) {
                $preco = $payload->preco[0]->precoPor;
            }
        }
        $fabricante = '';
        if (isset($payload->marca->nome) && $payload->marca->nome) {
            $fabricante = '["' . $this->getCodeInfoProduct('brands', 'name', $payload->marca->nome) . '"]';
        }

        $categoria = '';
        if (isset($payload->categorias) && (count($payload->categorias) > 0)) {
            foreach ($payload->categorias as $key => $value) {
                if ($key == 0) {
                    $categoriaID = $this->getCodeInfoProduct('categories', 'name', $value->nome);
                    if (!$categoriaID) {
                        //insere a categoria
                        $sqlBrand = $this->_this->db->insert_string('categories', array('name' => $value->nome, 'active' => 1));

                        $this->_this->db->query($sqlBrand);
                        $query = $this->_this->db
                            ->select('id')
                            ->from('categories')
                            ->where(array('name' => $value->nome))
                            ->get();
                        $result = $query->first_row();

                        $categoriaID = $result->id;
                    }
                    $categoria = '["' . $categoriaID . '"]';
                }
            }
        }
        // type - S=string, "F"=float, "I"=integer, "A"=array
        $product = array(
            'nome' => array('value' => $payload->nome, 'required' => true, 'type' => 'S', 'field_database' => 'name'),
            //'sku'               => array('value' => $payload->codigoFornecedor, 'required' => true, 'type' => 'S', 'field_database' => 'sku'),
            'unidade' => array('value' => $payload->unidadeMedida->id, 'required' => true, 'type' => 'S', 'field_database' => 'attribute_value_id'),
            'preco' => array('value' => $preco, 'required' => true, 'type' => 'F', 'field_database' => 'price'),
            'list_price' => array('value' => $list_price, 'required' => true, 'type' => 'F', 'field_database' => 'list_price'),
            'ncm' => array('value' => $payload->ncm->codigoNcm, 'required' => true, 'type' => 'S', 'field_database' => 'NCM'),
            'origem' => array('value' => $payload->procedencia->id, 'required' => true, 'type' => 'I', 'field_database' => 'origin'),
            'EAN' => array('value' => '' . $eanUnico . '', 'required' => true, 'type' => 'S', 'field_database' => 'EAN'),
            'peso_liquido' => array('value' => $payload->peso->unitario, 'required' => true, 'type' => 'F', 'field_database' => 'peso_liquido'),
            'peso_bruto' => array('value' => $payload->peso->bruto, 'required' => true, 'type' => 'F', 'field_database' => 'peso_bruto'),
            'sku_fabricante' => array('value' => '', 'required' => true, 'type' => 'S', 'field_database' => 'codigo_do_fabricante'),
            'descricao' => array('value' => $payload->descricao, 'required' => false, 'type' => 'S', 'field_database' => 'description'),
            'garantia' => array('value' => $payload->prazoGarantiaFornecedor, 'required' => true, 'type' => 'I', 'field_database' => 'garantia'),
            'fabricante' => array('value' => $fabricante, 'required' => true, 'type' => 'S', 'field_database' => 'brand_id'),
            'altura' => array('value' => $payload->dimensoes->altura, 'required' => true, 'type' => 'F', 'field_database' => 'altura'),
            'comprimento' => array('value' => $payload->dimensoes->comprimento, 'required' => true, 'type' => 'F', 'field_database' => 'profundidade'),
            'largura' => array('value' => $payload->dimensoes->largura, 'required' => true, 'type' => 'F', 'field_database' => 'largura'),
            'categoria' => array('value' => $categoria, 'required' => true, 'type' => 'S', 'field_database' => 'category_id'),
            'prazo_operacional_extra' => array('value' => 0, 'required' => true, 'type' => 'I', 'field_database' => 'prazo_operacional_extra')
        );



        // Validar e formatar campos
        $productFormat = $this->getDataFormat($product, $payload->vendavel);

        // Encontrou erro na formatação dos dados
        if (isset($productFormat['success']) && !$productFormat['success']) {
            return $productFormat;
        }

        $productFormat['product_id_erp'] = $payload->codigoTerceiro;
        $productFormat['sku'] = $payload->codigoFornecedor;
        $productFormat['store_id'] = $this->_this->store;

        $dataProduct = $this->getProductForSku($payload->codigoFornecedor);

        if (isset($dataProduct['image']) && $dataProduct['image'] != '') {
            if ($this->_this->uploadproducts->countImagesDir($dataProduct['image']) == 0) {
                if (isset($payload->anexos) && count($payload->anexos) > 0) {

                    $serverpath = $_SERVER['SCRIPT_FILENAME'];
                    $pos = strpos($serverpath, 'assets');
                    $serverpath = substr($serverpath, 0, $pos);
                    $targetDir = $serverpath . 'assets/images/product_image/';
                    $dirImage = $dataProduct['image'] ?? Admin_Controller::getGUID(false); // gero um novo diretorio para as imagens
                    $targetDir .= $dirImage;

                    $caminho = array('path_complet' => $targetDir, 'path_product' => $dirImage);
                    $images = $this->getImages($payload->anexos, $caminho['path_complet']);
                    if (!$images['success']) {
                        // encontrou erro no upload de imagem
                        return $images;
                    }

                    //define nome da pasta com as imagens e a imagem principal
                    $productFormat['principal_image'] = $images['primary'];
                }
            }
        }
        $this->analizeProductSituacao($dataProduct, $productFormat);



        // remove os itens que não derão ser atualizados

        if ($fullUpdate == false) {
            foreach ($this->naoAtualizar as $field)
                unset($productFormat[$field]);
        }


        if ($this->getProductForAttributes($productFormat))
            return array('success' => null);

        $sqlProd = $this->_this->db->update_string('products', $productFormat, array('sku' => $payload->codigoFornecedor, 'store_id' => $this->_this->store));
        $update = $this->_this->db->query($sqlProd);

        if ($update)
            return array('success' => true);

        return array('success' => false, 'message' => array('Não foi possível atualizar o produto.'));
    }

    public function analizeProductSituacao($dataProduct, &$productFormat)
    {
        if (isset($dataProduct['image'])) {
            if (
                ($dataProduct['category_id'] != '[""]' || $productFormat['category_id'] != '[""]') &&
                ($dataProduct['brand_id'] != '[""]' || $productFormat['brand_id'] != '[""]') &&
                $this->_this->uploadproducts->countImagesDir($dataProduct['image'])
            )
                $productFormat['situacao'] = 2;
            else
                $productFormat['situacao'] = 1;
        }
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
                'prd_id' => $resultProd['id'],
                'sku' => $skuVar,
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
                'sku' => $sku_var
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
                'name' => $name_var
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
        curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, TRUE);
        $response = curl_exec($curl_handle);
        $httpcode = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
        curl_close($curl_handle);

        $header['httpcode'] = $httpcode;
        $header['content'] = $response;

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
        if (!$product)
            return false;

        $variations = $this->_this->db->get_where('prd_variants', array('prd_id' => $product['id']))->result_array();

        // Atualiza o estoque da variação
        $this->_this->db->where(array('prd_id' => $product['id'], 'sku' => $sku))->update('prd_variants', array('qty' => $qty));

        $newQty = 0;
        foreach ($variations as $variation) {

            if ($variation['sku'] == $sku)
                $variation['qty'] = $qty; // define a nova quantidade

            $newQty += (float) $variation['qty'];
        }

        return $this->updateStockProduct($skuPai, $newQty) ? true : false;

        // Atualiza o estoque da variação
        //return $this->_this->db->where(array('prd_id' => $prd_id, 'sku' => $sku))->update('prd_variants', array('qty' => $qty));       
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
        $jsonEncode = json_encode($responseXml);

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
        /* echo '<pre>';
          print_r($sku );
          echo '</pre>';
          die('Debug'); */
        $this->_this->db->where(
            array(
                'sku' => $sku,
                'store_id' => $this->_this->store,
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
        $qty = $payload->saldo;
        $idProduto = $payload->idProduto; // id produto tiny
        $sku = $payload->sku; // geralmente é igual ao $skuMapeamento
        $skuMapeamento = $payload->skuMapeamento;
        $skuMapeamentoPai = $payload->skuMapeamentoPai;
        $tipoVariacao = $skuMapeamentoPai == "" ? "N" : "V";

        if ($tipoVariacao == "N")
            if (!$this->getProductForSku($skuMapeamento))
                return false;
            else if ($tipoVariacao == "V")
                if (!$this->getVariationForSkuAndSkuVar($skuMapeamentoPai, $skuMapeamento))
                    return false;
                else
                    return false;

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

        if ($list == "" || !$list)
            return false;

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
        $BSELLER_URL = $this->getUrlBseller($this->_this->store);
        foreach ($products as $product) {
            // Consulta endpoint par obter estoque            
            $url = $BSELLER_URL . 'api/estoques/';
            $data = "{$product},";

            if (function_exists('sendREST'))
                $dataPrice = json_decode($this->_this->sendREST($url, $data));
            else
                $dataPrice = json_decode($this->_sendREST($url, $data));

            // Ocorreu um problema
            if ($dataPrice->retorno->status != "OK") {
                if ($dataPrice->retorno->codigo_erro != 200) {
                    echo "Ocorreu um problema para obter o preço da produto_id_bseller={$product} retorno=" . json_encode($dataPrice) . "\n";
                    $this->_this->log_data('batch', 'Product/getStock', "Ocorreu um problema para obter o estoque do produto_id_bseller={$product} retorno=" . json_encode($dataPrice), "E");
                }
                return array('success' => false);
            }

            return array('success' => true, 'value' => (float) $dataPrice->retorno->registros[0]->registro->preco);
        }
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
        foreach ($arrayVariations as $key => $value) {
            if ($value != "") {
                if ($key == 0) {
                    $has_variations .= $value;
                } else {
                    $has_variations .= ';' . $value;
                }
            }
        }
        return $this->_this->db->where(array('id' => $prd_id))->update('products', array('has_variants' => $has_variations)) ? true : false;
    }

    public function getEstoqueProduto($produto)
    {
        $BSELLER_URL = $this->getUrlBseller($this->_this->store);

        $url = $BSELLER_URL . 'api/itens/' . $produto . '/estoque?tipoInterface=' . $this->_this->interface;
        $data = "";
        $dataStockProduct = json_decode(json_encode($this->_this->sendREST($url, $data)));

        // Ocorreu um problema
        if ($dataStockProduct->httpcode != 200) {
            $content = json_decode($dataStockProduct->content);
            echo $content->message . "\n";
            $this->_this->log_data('batch', 'Product/getStock', "Ocorreu um problema para obter o estoque do codigo_terceiro_bseller={$produto} retorno=" . json_encode($dataStockProduct), "E");
            return false;
        }

        $prodRes = json_decode($dataStockProduct->content);
        //----------------------------------------------------------------------
        $stock = isset($prodRes->estoqueEstabelecimento[0]->quantidade) ? $prodRes->estoqueEstabelecimento[0]->quantidade : 0;

        return $stock;
    }

    public function getTotalVariation($prd_id)
    {
        $countVar = 0;

        $query = $this->_this->db->get_where('prd_variants', array('prd_id' => $prd_id));
        if ($query->num_rows() == 0) {
            return $countVar;
        }
        $countVar += count($query->result_array());
        return $countVar;
    }

    public function updateVariacao($filho, $dataFilho)
    {
        $BSELLER_URL = $this->getUrlBseller($this->_this->store);
        $urlEsp = $BSELLER_URL . "api/itens/$filho->codigoItemPai?tipoInterface=" . $this->_this->interface;
        $dataEsp = '';
        $hasVariations = array();
        $limite_imagens_aceitas_api = $this->_this->model_settings->getValueIfAtiveByName('limite_imagens_aceitas_api') ?? 6;
        if(!isset($limite_imagens_aceitas_api) || $limite_imagens_aceitas_api <= 0) { $limite_imagens_aceitas_api = 6;}
        $dataProductPai = json_decode(json_encode($this->_this->sendREST($urlEsp, $dataEsp)));
        if ($dataProductPai->httpcode == 200) {
            $ProductPai = json_decode($dataProductPai->content);
            $variation_name = '';
            foreach ($filho->variacoes as $variacao) {
                if (!array_key_exists($variacao->tipoVariacao, $this->tipoVariacoes)) {
                    echo "O produto sku = $filho->codigoItemPai, tem uma variação $variacao->tipoVariacao, não compatível com o sistema...\n";
                    return array('success' => false);
                } else {
                    $variationType = $this->tipoVariacoes[$variacao->tipoVariacao];
                    if ($variationType == 'VOLTAGEM') {
                        if ($this->likeText("%220%", strtolower($variacao->variacao))) {
                            if ($variation_name == '') {
                                $variation_name .= '220V';
                            } else {
                                $variation_name .= ';220V';
                            }
                        } else {
                            if ($variation_name == '') {
                                $variation_name .= '110V';
                            } else {
                                $variation_name .= ';110V';
                            }
                        }
                    } else {
                        if ($variation_name == '') {
                            $variation_name .= $variacao->variacao;
                        } else {
                            $variation_name .= ';' . $variacao->variacao;
                        }
                    }
                    if (!in_array($variationType, $hasVariations)) {
                        $hasVariations[] = $variationType;
                    }
                }
            }

            //tudo certo, inserir a variacao do filho
            if (!empty($variation_name)) {
                $urlEsp = $BSELLER_URL . "api/itens/$filho->codigoTerceiro?tipoInterface=" . $this->_this->interface;
                $dataEsp = '';
                $dataProduct = json_decode(json_encode($this->_this->sendREST($urlEsp, $dataEsp)));
                if ($dataProduct->httpcode == 200) {
                    $produtoFilho = json_decode($dataProduct->content);

                    $eanUnicoFilho = '';
                    if (isset($produtoFilho->ean)) {
                        foreach ($produtoFilho->ean as $ean) {
                            if ($ean->preferencial == true || $ean->preferencial == 1) {
                                $eanUnicoFilho = $ean->codEan;
                            }
                        }
                    }

                    $estoqueFilho = $this->getEstoqueProduto($filho->codigoTerceiro);
                    if ($estoqueFilho == 0) {
                        // return array('success' => false, 'message' => 'Ocorreu um problema para obter o estoque, caso use uma lista de preço verifique se o produto/variaçao está na lista!');
                    }
                    $preco = 0;
                    $list_price = 0;
                    // Preço de é obrigatório no BSeller
                    if (!isset($produtoFilho->preco[0]->precoDe)) {
                        echo "Não tem um preço cadastrado. \n";
                        return array('success' => false, 'message' => array('Não tem um preço cadastrado.'));
                    } else {
                        if (isset($produtoFilho->preco[0]->precoDe) && $produtoFilho->preco[0]->precoDe > 0) {
                            $preco = $produtoFilho->preco[0]->precoDe;
                            $list_price = $produtoFilho->preco[0]->precoDe;
                        }
                        if (isset($produtoFilho->preco[0]->precoPor) && $produtoFilho->preco[0]->precoPor > 0) {
                            $preco = $produtoFilho->preco[0]->precoPor;
                        }
                    }

                    //--------------------------------------------------
                    // upload de imagens, so é permitido 6 imagens;
                    $imgArray = array();
                    $qtdImage = 0;
                    foreach ($produtoFilho->imagens as $img) {
                        if ($qtdImage >= $limite_imagens_aceitas_api) {
                            continue;
                        }
                        $linkImg = $img->link;
                        $imgArray[] = $linkImg;
                        $qtdImage++;
                    }


                    $dataProduct = $this->getProductForSku($ProductPai->codigoFornecedor);
                    $caminhoImagemFilha = $dataProduct['image'] . '/' . $dataFilho['image'];
                    $pathFilho = $dataFilho['image'];
                    $serverpath = $_SERVER['SCRIPT_FILENAME'];
                    $pos = strpos($serverpath, 'assets');
                    $serverpath = substr($serverpath, 0, $pos);
                    $pathFilhoPath_complet = $serverpath . 'assets/images/product_image/' . $caminhoImagemFilha;

                    if (count($imgArray) > 0) {
                        $produtoFilho->anexos = $imgArray;
                        if ($this->_this->uploadproducts->countImagesDir($caminhoImagemFilha) == 0) {
                            if (isset($produtoFilho->anexos) && count($produtoFilho->anexos) > 0) {
                                // Faz upload e recupera os códigos
                                $images = $this->getImages($produtoFilho->anexos, $pathFilhoPath_complet);
                                if (!$images['success']) {
                                    // encontrou erro no upload de imagem
                                    return array('success' => false, 'message' => '1 ou mais imagem do produto inválida');
                                }
                            }
                        }
                    }
                    $variants = $this->_this->model_products->getVariantsByProd_id($dataFilho['prd_id']);
                    $estoqueFilho = $this->getEstoqueProduto($produtoFilho->codigoTerceiro);

                    $novoData = array(
                        'prd_id' => $dataFilho['prd_id'] ?? 0,
                        'variant' => count($variants),
                        'name' => $variation_name ?? '',
                        'sku' => $produtoFilho->codigoFornecedor ?? '',
                        'variant_id_erp' => $produtoFilho->codigoTerceiro ?? 0,
                        'image' => $dataFilho['image'],
                        'status' => 1,
                        'EAN' => $eanUnicoFilho,
                        'codigo_do_fabricante' => $produtoFilho->codigoFornecedor,
                        'qty' => $estoqueFilho,
                        'price' => $preco,
                        'list_price' => $list_price
                    );
                    $arrayDiff = array_diff($novoData, $dataFilho);
                    if (count($arrayDiff) > 0) {
                        $this->_this->db->where(array('prd_id' => $dataFilho['prd_id'], 'variant' => $dataFilho['variant']));
                        $update = $this->_this->db->update('prd_variants', $novoData);
                        //atualiza o estoque o produto pai
                        $this->_this->model_products->updateEstoqueProdutoPai($dataFilho['prd_id']);
                        if (!empty($hasVariations)) {
                            $this->updateHasVariations($dataFilho['prd_id'], $hasVariations);
                        }
                        return ($update == true) ? true : false;
                    } else {
                        return false;
                    }
                }
            }
        }
    }
}
