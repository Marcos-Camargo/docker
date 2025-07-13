<?php

require APPPATH . "libraries/REST_Controller.php";
require APPPATH . "controllers/BatchC/Integration/Tiny/Product/Product.php";

class UpdateNFe extends REST_Controller
{
    public $job;
    public $unique_id = null;
    public $token;
    public $store;
    public $company;
    public $listPrice;
    public $formatReturn = "json";
    public $product;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_stores');
        $this->product = new Product($this);
        header('Integration: v1');
    }

    /**
     * Atualização de estoque, receber via POST(eu acho, confirmar com Tiny)
     */
    public function index_post()
    {
        ob_start();
        // example payload
        // {"versao":"1.0.0","cnpj":"30120829000199","idEcommerce":8390,"tipo":"nota_fiscal","dados":{"chaveAcesso":"42201030120829000199550010000000821647945504","numero":"000082","serie":"1","urlDanfe":"https:\/\/erp.tiny.com.br\/pre-release\/doc.view?id=525cd327da9718cef2bfa53616e27ef2","idPedidoEcommerce":"Submarino-136895412650"}}


        if(!isset($_GET['apiKey'])) {
            $this->log_data('WebHook - Tiny', 'WebHookUpdateNFe - Valid', 'Não foi encontrado a parâmetro apiKey', "E");
            ob_clean();
            return $this->response("apiKey não encontrado", REST_Controller::HTTP_UNAUTHORIZED);
        }
        $apiKey = filter_var($_GET['apiKey'], FILTER_SANITIZE_STRING);
        $store = $this->getStoreForApiKey($apiKey);
        if (!$store) {
            $this->log_data('WebHook - Tiny', 'WebHookUpdateNFe - Valid', 'apiKey não localizado para nenhuma loja', "E");
            ob_clean();
            return $this->response('apiKey não corresponde a nenhuma loja', REST_Controller::HTTP_UNAUTHORIZED);
        }

        $this->setJob('WeebHook-UpdateNFe');
        // define configuração da integração
        $this->setDataIntegration($store);

        // Recupera dados enviado pelo body
        $product = json_decode(file_get_contents('php://input'));

        if (!isset($product->tipo) || $product->tipo != 'nota_fiscal') {
            $this->log_data('WebHook - Tiny', 'WebHookUpdateNFe - Valid', 'Chegou um tipo diferente de nota fiscal. RECEBIDO='.json_encode($product), "E");
            ob_clean();
            return $this->response('Chegou um tipo diferente de nota fiscal', REST_Controller::HTTP_UNAUTHORIZED);
        }

        $nfe = $product->dados;

        $numMkt     = $nfe->idPedidoEcommerce;
        $numNFe      = $nfe->numero;
        $serieNFe    = $nfe->serie;
        $dataEmissao = date('Y-m-d H:i:s');
        $chaveNFe    = str_replace(' ', '', $nfe->chaveAcesso);

        $dataOrder = $this->getDataOrderByNumMkt($numMkt);
        if (!$dataOrder) {
            //$msgError = "Pedido {$numMkt} não encontrado, foi enviado uma NF-e. payload_nfe=".json_encode($nfe);
            //echo "{$msgError}\n";
            //$this->log_data('batch', $log_name, $msgError, "W");
            ob_clean();
            return $this->response('Pedido não encontrado', REST_Controller::HTTP_NOT_FOUND);
        }
        $orderId    = $dataOrder['id'];
        $valorNFe   = $dataOrder['gross_amount'];

        $this->setUniqueId($orderId); // define novo unique_id

        // Pedido já tem uma NF-e, atualizar o status
        $orderWithNfe = $this->getOrderWithNfe($orderId);
        if ($orderWithNfe) {
            //$msgError = "Pedido já tem uma NF-e. Será atualizado apenas seu status para 52. PEDIDO_CONECTA={$orderId} para atualizar! ORDER_INTEGRATION=".json_encode($nfe);
            //echo "{$msgError}\n";
            //$this->log_data('batch', $log_name, $msgError, "W");
            // $this->updateStatusForOrder($orderId, 50, 3);  fLUXO ANTIGO 
            $this->updateStatusForOrder($orderId, 52, 3);
            $this->removeOrderIntegration($orderId);
            ob_clean();
            return $this->response(null, REST_Controller::HTTP_OK);
        }

        // Dados para inserir a NF-e
        $arrNfe = array(
            'order_id'      => $orderId,
            'company_id'    => $this->company,
            'store_id'      => $this->store,
            'date_emission' => date('d/m/Y H:i:s', strtotime($dataEmissao)),
            'nfe_value'     => $valorNFe,
            'nfe_serie'     => $serieNFe,
            'nfe_num'       => $numNFe,
            'chave'         => $chaveNFe
        );

        // Inserir NF-e
        $insertNfe = $this->createNfe($arrNfe);

        // Erro para iserir a NF-e
        if (!$insertNfe) {
            $msgError = "Não foi possível inserir dados de faturamento do pedido {$orderId}! DATA_NFE_INSERT=" . json_encode($arrNfe) . ", DATA_NFE_BLING=" . json_encode($nfe) . " RETORNO=" . json_encode($insertNfe);
            //echo "{$msgError}\n";
            $this->log_data('api', 'tiny/updateNFe/createNFe', $msgError, "E");
            $this->log_integration("Erro para atualizar o pedido {$orderId}", "<h4>Não foi possível atualizar dados de faturamento do pedido {$orderId}</h4>", "E");
            ob_clean();
            return $this->response(null, REST_Controller::HTTP_OK);
        }

        // Remover pedido com status=3 da fila
        $this->removeOrderIntegration($orderId);

        // Salvar XML
        try {
            $this->saveXML($dataOrder['order_id_integration'], $orderId);
        } catch (\Exception $e) {
            $this->log_data('api', 'tiny/updateNFe/saveXML', "Erro para salvar o XML: {$e->getMessage()}", "E");
        }

        $this->log_integration("Pedido {$orderId} atualizado",
            "<h4>Foi atualizado dados de faturamento do pedido {$orderId}</h4> 
                      <ul>
                        <li><strong>Chave:</strong> {$chaveNFe}</li>
                        <li><strong>Número:</strong> {$numNFe}</li>
                        <li><strong>Série:</strong> {$serieNFe}</li>
                        <li><strong>Data de Emissão:</strong> ".date('d/m/Y H:i:s', strtotime($dataEmissao))."</li>
                        <li><strong>Valor:</strong> " . number_format($valorNFe, 2, ',', '.') . "</li>
                      </ul>", "S");

        //echo "Pedido {$orderId} atualizado com sucesso!\n";

        ob_clean();
        return $this->response(null, REST_Controller::HTTP_OK);
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


    /**
     * Recupera dados do pedido pelo numero do pedido no marketplace
     *
     * @param   int     $num_mkt    Número do pedido no marketplace
     * @return  bool                Retorna os dados do pedido se encontrado
     */
    public function getDataOrderByNumMkt($num_mkt)
    {
        return $this->db->get_where('orders',
            array(
                'store_id'              => $this->store,
                'company_id'            => $this->company,
                'numero_marketplace'    => $num_mkt
            )
        )->row_array();
    }

    /**
     * Recupera se o pedido já tem uma NF-e
     *
     * @param   int     $orderId    Código do pedido
     * @return  bool                Retorna se o pedido tem NF-e
     */
    private function getOrderWithNfe($orderId)
    {
        return $this->db
            ->get_where('nfes',
                array(
                    'order_id'      => $orderId,
                    'store_id'      => $this->store,
                    'company_id'    => $this->company
                )
            )->num_rows() == 0 ? false : true;

    }

    /**
     * Cria dados de faturamento do pedido e atualiza o status do pedido para 50
     *
     * @param   array   $data   Dados da nfe para inserir
     * @return  bool            Retorna o status da criação
     */
    private function createNfe($data)
    {
        $sqlNfe     = $this->db->insert_string('nfes', $data);
        $insertNfe  = $this->db->query($sqlNfe) ? true : false;

        if (!$insertNfe) return false;
		
		//return $this->updateStatusForOrder($data['order_id'], 50, 3);
        return $this->updateStatusForOrder($data['order_id'], 52, 3);

    }

    /**
     * Atualiza status de um pedido
     *
     * @param   int     $orderId        Código do pedido
     * @param   int     $status         Código do status
     * @param   int     $verifyStatus   Código do status para verificação
     * @return  bool                    Retorna o status da atualização
     */
    private function updateStatusForOrder($orderId, $status, $verifyStatus = null)
    {
        $where = array(
            'id'        => $orderId,
            'store_id'  => $this->store,
        );
        if ($verifyStatus) $where['paid_status'] = $verifyStatus;

        return $this->db->where($where)->update('orders', array('paid_status' => $status)) ? true : false;
    }

    /**
     * Remove o pedido da fila de integração
     *
     * @param   int   $orderId  Código da integração
     * @return  bool            Retornar o status da exclusão
     */
    private function removeOrderIntegration($orderId)
    {
        return $this->db->delete(
            'orders_to_integration',
            array(
                'store_id'      => $this->store,
                'order_id'      => $orderId,
                'paid_status'   => 3
            )
        ) ? true : false;
    }

    /**
     * Salva um arquivo XML da NFe
     *
     * @param   int     $idOrderTiny    Código do pedido na Tiny
     * @param   int     $orderId        Código do pedido na Conecta Lá
     * @return  bool                    Retorna o status da importação do xml
     */
    private function saveXML($idOrderTiny, $orderId)
    {
        $url = 'https://api.tiny.com.br/api2/pedido.obter.php';
        $data = "&id={$idOrderTiny}";
        $dataOrder = $this->product->_sendREST($url, $data);

        $dataOrder = json_decode($dataOrder)->retorno;

        if ($dataOrder->status != "OK") return false;

        if (!isset($dataOrder->pedido->id_nota_fiscal) || empty($dataOrder->pedido->id_nota_fiscal)) return false;

        $idTiny = $dataOrder->pedido->id_nota_fiscal;

        $this->formatReturn = 'xml';
        $log_name = 'tiny/' . $this->router->fetch_class() . '/' . __FUNCTION__;

        $url = 'https://api.tiny.com.br/api2/nota.fiscal.obter.xml.php';
        $data = "&id={$idTiny}";
        $dataXml = $this->product->_sendREST($url, $data);

        $xmlNfe     = simplexml_load_string($dataXml);
        $jsonEncode = json_encode($xmlNfe);
        $arrayXml   = json_decode($jsonEncode,TRUE);

        if ($arrayXml['status'] != "OK") {
            $msgError = "Não foi possível localizar o xml do pedido {$orderId}! RETORNO=" . json_encode($dataXml);
            //echo "{$msgError}\n";
            $this->log_data('batch', $log_name, $msgError, "E");
            $this->log_integration("Erro para obter o XML do pedido {$orderId}", "<h4>Não foi possível obter o XML do pedido {$orderId}</h4>", "E");
            return false;
        }

        $dataXml = str_replace('<retorno><status_processamento>3</status_processamento><status>OK</status><xml_nfe>', '', $dataXml);
        $dataXml = str_replace('</xml_nfe></retorno>', '', $dataXml);

        $namePathStore = date('m-Y');

        $targetDir = 'assets/images/xml/';
        if (!file_exists($targetDir)) {
            $oldmask = umask(0);
            @mkdir($targetDir, 0775);
            umask($oldmask);
        }

        $targetDir .= $this->store.'/';
        if (!file_exists($targetDir)) {
            $oldmask = umask(0);
            @mkdir($targetDir, 0775);
            umask($oldmask);
        }

        $targetDir .= $namePathStore.'/';
        if (!file_exists($targetDir)) {
            $oldmask = umask(0);
            @mkdir($targetDir, 0775);
            umask($oldmask);
        }

        $arquivo = fopen($targetDir . $arrayXml['xml_nfe']['nfeProc']['protNFe']['infProt']['chNFe'] . ".xml",'w');

        if ($arquivo == false) return false;

        fwrite($arquivo, $dataXml);

        fclose($arquivo);

        return true;
    }
}