<?php

require APPPATH . "libraries/REST_Controller.php";
require APPPATH . "controllers/BatchC/Integration/Tiny/Product/Product.php";

class CreateProduct extends REST_Controller
{
    public $job;
    public $unique_id = null;
    public $token;
    public $store;
    public $company;
    public $product;
    public $listPrice;
    public $formatReturn = "json";

    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_products');
        $this->load->model('model_stores');
        $this->load->library('UploadProducts'); // carrega lib de upload de imagens

        $this->product = new Product($this);
        header('Integration: v1');
    }

    /**
     * Atualização de estoque, receber via POST(eu acho, confirmar com Tiny)
     */
    public function index_post()
    {
        ob_start();
        if(!isset($_GET['apiKey'])) {
            $this->log_data('WebHook - Tiny', 'WebHookCreateProd - Valid', 'Não foi encontrado a parâmetro apiKey', "E");
            ob_clean();
            return $this->response("apiKey não encontrado", REST_Controller::HTTP_UNAUTHORIZED);
        }
        $apiKey = filter_var($_GET['apiKey'], FILTER_SANITIZE_STRING);
        $store = $this->getStoreForApiKey($apiKey);
        if (!$store) {
            $this->log_data('WebHook - Tiny', 'WebHookCreateProd - Valid', 'apiKey não localizado para nenhuma loja', "E");
            ob_clean();
            return $this->response('apiKey não corresponde a nenhuma loja', REST_Controller::HTTP_UNAUTHORIZED);
        }

        $this->setJob('WeebHook-CreateProduct');
        // define configuração da integração
        $this->setDataIntegration($store);

        // Recupera dados enviado pelo body
        $product = json_decode(file_get_contents('php://input'));
        $this->log_data('WebHook - Tiny', 'WebHookCreateProd - Start', json_encode($product), "I");

        $idProduct = $product->dados->id;
        $verifyProduct = $this->product->getProductForIdErp($idProduct);
        $skuProduct = $product->dados->codigo;
        $nameProduct = $product->dados->nome;
        $priceProduct = null;
        $this->setUniqueId($idProduct); // define novo unique_id

        // usa lista de preço, verificar se produto está na lista
        if ($this->listPrice && $this->product->getUseListPrice() && !count($product->dados->variacoes)) {
            $productList = $this->product->getPriceVariationListPrice($idProduct);

            if (!$productList['success']) {
                $skuId = $product->dados->codigo;
                $isMapeamento = false;

                if ($product->dados->codigo == "") {
                    $skuId = $product->dados->id;
                } else {
                    $verifyProduct = $this->product->getProductForSku($skuProduct);
                    if (!empty($verifyProduct)) $isMapeamento = true;
                }
                $mapeamento = $this->getMapeamento($product->dados, "Produto não existente na lista de preço");

                $this->log_integration($isMapeamento ? "Foi tentado mapear o produto {$skuId}" : "Foi tentado integrar o produto {$skuId}", "<h4>Não foi encontrado o produto na lista de preço</h4> <strong>SKU</strong>: {$product->dados->codigo}<br><strong>ID Tiny</strong>: {$idProduct}", "E");
                //$this->log_data('WebHook - Tiny', 'WebHookCreateProd - Finish', "Não encontrou produto na lista. LISTA={$this->listPrice}. RECEBIDO=".json_encode($product), "W");
                ob_clean();
                return $this->response($mapeamento, REST_Controller::HTTP_OK);
            }
            $priceProduct = $productList['value'];
        }

        // Inicia transação
        $this->db->trans_begin();
        // Não encontrou o produto pelo código da tiny
        if (empty($verifyProduct)) {

            // verifica se sku já existe
            $verifyProduct = $this->product->getProductForSku($skuProduct);

            // existe o sku na loja, mas não esá com o registro do id da tiny
            if (!empty($verifyProduct)) {
                $this->db->trans_rollback();
                $this->product->updateProductForSku($skuProduct, array('product_id_erp' => $idProduct)); // produto atualizado com o ID Tiny
                $mapeamento = $this->getMapeamento($product->dados);
                //$this->log_data('WebHook - Tiny', 'WebHookCreateProd - Finish', 'Produto mapeado - ' .json_encode($mapeamento), "I");
                $this->log_integration("Produto {$skuProduct} mapeado", "<h4>Novo produto mapeado com sucesso</h4> <ul><li>O produto {$skuProduct}, foi enviado da Tiny para a Conecta Lá, mas já existe o cadastro, foi realizado apenas o mapeamento</li></ul><br><strong>ID Tiny</strong>: {$idProduct}<br><strong>SKU</strong>: {$skuProduct}<br><strong>Descrição</strong>: {$product->dados->nome}", "S");
                ob_clean();
                return $this->response($mapeamento, REST_Controller::HTTP_OK);
            }
            else { // produto ainda não cadastrado, cadastrar
                $productCreate = $this->product->createProduct($product->dados, $priceProduct, true);
                if (!$productCreate['success']) {
                    $this->db->trans_rollback();
                    //$this->log_data('API', 'CreateProduct/index_post', "Produto PAI tiny com ID={$idProduct} encontrou um erro, dados_item_lista=" . json_encode($product) . " retorno=" . json_encode($productCreate), "W");

                    $messageError = $productCreate['message'];

                    if (is_string($messageError)) {
                        $messageError = array($messageError);
                    }

                    $skuId = $product->dados->codigo;
                    if ($product->dados->codigo == "") {
                        $skuId = $product->dados->id;
                    }
                    $this->log_integration("Erro para integrar produto {$skuId}", '<h4>Existem algumas pendências no cadastro do produto enviado para integração<h4/> <ul><li>' . implode('</li><li>', $messageError) . '</li></ul>', "E");
                    $mapeamento = $this->getMapeamento($product->dados, implode(' - ', $messageError));

                    ob_clean();
                    return $this->response($mapeamento, REST_Controller::HTTP_OK);
                }
            }
        } else { // encontrou o produto pelo código da tiny, precisa atualizar ?? por enquanto só ignora
            if (count($product->dados->variacoes) > 0 && count($this->product->getVariantProduct($verifyProduct['id'])) != count($product->dados->variacoes)) {
                foreach ($product->dados->variacoes as $var) {

                    $getPrice = $this->product->getPriceVariationListPrice($var->id);
                    if ($this->product->getVariantsForIdAndSku($verifyProduct['id'], $var->codigo) || !$getPrice['success']) continue;

                    $createVariation = $this->product->createVariation($var, $skuProduct);

                    // Ocorreu problema na criação da variação
                    if (!$createVariation['success']) {
                        $this->log_integration("Erro para integrar variação SKU {$skuProduct}", "<h4>Não foi possível criar a variação</h4> <ul><li>{$createVariation['message']}</li></ul> <strong>ID Bling</strong>: {$product->dados->id}<br><strong>SKU</strong>: {$skuProduct}<br><strong>SKU Variação</strong>: {$var->codigo}<br><strong>Descrição</strong>: {$nameProduct}", "E");
                        continue;
                    }
                }

                if ($this->db->trans_status() === FALSE){
                    $this->db->trans_rollback();
                    $mapeamento = $this->getMapeamento($product->dados, "Ocorreu um problema para enviar o produto, tente mais tarde");
                    ob_clean();
                    return $this->response($mapeamento, REST_Controller::HTTP_OK);
                }

                $this->db->trans_commit();
            } else $this->db->trans_rollback();

            $mapeamento = $this->getMapeamento($product->dados);
            //$this->log_data('WebHook - Tiny', 'WebHookCreateProd - Finish', 'Produto mapeado - ' .json_encode($mapeamento), "I");
            $this->log_integration("Produto {$skuProduct} mapeado", "<h4>Novo produto mapeado com sucesso</h4> <ul><li>O produto {$skuProduct}, foi enviado da Tiny para a Conecta Lá, mas já existe o cadastro, foi realizado apenas o mapeamento</li></ul><br><strong>ID Tiny</strong>: {$idProduct}<br><strong>SKU</strong>: {$skuProduct}<br><strong>Descrição</strong>: {$product->dados->nome}", "S");
            ob_clean();
            return $this->response($mapeamento, REST_Controller::HTTP_OK);

        }

        if ($this->db->trans_status() === FALSE){
            $this->db->trans_rollback();
            ob_clean();
            return $this->response(array('success' => false, 'message' => 'Ocorreu um problema para inserir o produto'), REST_Controller::HTTP_OK);
        }

        $this->db->trans_commit();

        $excessoesVarListPreco = $productCreate['variations_not_list'];
        $messageAdd = "";
        if (count($excessoesVarListPreco)) {
            $messageAdd = '<br><br>Foram encontradas variações que não estão na lista, não foram cadastradas: <ul><li>' . implode('</li><li>', $excessoesVarListPreco) . '</li></ul>';
        }

        $this->log_integration("Produto {$skuProduct} integrado", "<h4>Novo produto integrado com sucesso</h4> <ul><li>O produto {$skuProduct}, foi criado com sucesso</li></ul><br><strong>ID Tiny</strong>: {$idProduct}<br><strong>SKU</strong>: {$skuProduct}<br><strong>Descrição</strong>: {$product->dados->nome} {$messageAdd}", "S");
        $this->log_data('WebHook - Tiny', 'WebHookCreateProd - Finish', json_encode($productCreate) . $messageAdd, "I");
        ob_clean();
        return $this->response($productCreate['data'], REST_Controller::HTTP_OK);
    }

    /**
     * Recupera a loja pelo apiKey
     *
     * @param   string  $apiKey ApiKey de callback
     * @return  int|null        Retorna o código da loja, ou nulo caso não encontre
     */
    public function getStoreForApiKey($apiKey)
    {
        $query = $this->db->get_where('stores', array('token_callback'  => $apiKey))->row_array();
        return $query ? (int)$query['id'] : null;
    }

    /**
     * Define o token de integração
     *
     * @param string $token Token de integraçõa
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * Define a loja
     *
     * @param int $store Código da loja
     */
    public function setStore($store)
    {
        $this->store = $store;
    }

    /**
     * Define a empresa
     */
    public function setCompany($company)
    {
        $this->company = $company;
    }

    /**
     * Define a lista de preço, caso exista
     */
    public function setListPrice($list)
    {
        if ($list == "") $list = null;

        $this->listPrice = $list;
    }

    /**
     * Define o job
     */
    public function setJob($job)
    {
        $this->job = $job;
    }

    /**
     * Define o unique id
     */
    public function setUniqueId($uniqueId)
    {
        $this->unique_id = $uniqueId;
    }

    /**
     * Cria um log da integração para ser mostrada ao usuário
     *
     * @param   string      $title          Título do log
     * @param   string      $description    Descrição do log
     * @param   string      $type           Tipo de log
     * @return  bool                        Retornar o status da criação do log
     */
    public function log_integration($title, $description, $type)
    {
        $data = array(
            'store_id'      => $this->store,
            'company_id'    => $this->company,
            'title'         => $title,
            'description'   => $description,
            'type'          => $type,
            'job'           => $this->job,
            'unique_id'     => $this->unique_id,
            'status'        => 1
        );

        // verifica se existe algum log para não duplicar
        $logExist = $this->db->get_where('log_integration',
            array(
                'store_id'      => $this->store,
                'company_id'    => $this->company,
                'description'   => $description,
                'title'         => $title
            )
        )->row_array();
        if ($logExist && ($type == 'E' || $type == 'W')) {
            $data['id'] = $logExist['id'];
            $data['type'] = $type;
        }

        return $this->db->replace('log_integration', $data);
    }

    /**
     * Define os dados para integração
     *
     * @param int $store_id Código da loja
     */
    public function setDataIntegration($store_id)
    {
        $dataIntegration = $this->db->get_where('api_integrations', array('store_id' => $store_id))->row_array();
        $dataStore       = $this->model_stores->getStoresData($store_id);

        $credentials = json_decode($dataIntegration['credentials']);

        $this->setStore($store_id);
        $this->setToken($credentials->token_tiny);
        $this->setCompany($dataStore['company_id']);
        $this->setListPrice($credentials->id_lista_tiny);
    }

    public function getMapeamento($payload, $erroAll = null)
    {
        $arrMapeamentoTiny = array();
        $arrMap = array("idMapeamento" => $payload->idMapeamento, "skuMapeamento" => $payload->codigo);
        if ($erroAll) $arrMap["error"] = $erroAll;
        array_push($arrMapeamentoTiny, $arrMap);

        foreach ($payload->variacoes as $mapeamento) {
            $arrMap = array("idMapeamento" => $mapeamento->idMapeamento, "skuMapeamento" => $mapeamento->codigo);
            if ($erroAll) $arrMap["error"] = $erroAll;

            array_push($arrMapeamentoTiny, $arrMap);
        }

        return $arrMapeamentoTiny;
    }
}