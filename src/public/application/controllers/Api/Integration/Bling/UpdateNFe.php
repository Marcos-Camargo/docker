<?php

require APPPATH . "libraries/REST_Controller.php";
require APPPATH . "controllers/BatchC/Integration/Bling/Product/Product.php";

class UpdateNFe extends REST_Controller
{
    public $job;
    public $unique_id = null;
    public $apiKey;
    public $token;
    public $store;
    public $company;
    public $multiStore;
    public $formatReturn = "json";

    public function __construct()
    {
        parent::__construct();

        $this->load->model('model_stores');
        header('Integration: v1');
    }

    /**
     * Atualização de estoque, deve ser recebido via POST
     */
    public function index_put()
    {
        $product = json_decode(file_get_contents('php://input'));
        $this->log_data('WebHook', 'WebHookUpdateNfe', 'Chegou PUT, não deveria - GET='.json_encode($_GET).' - PAYLOAD='.json_encode($product), "E");
    }

    /**
     * Atualização de estoque
     */
    public function index_post()
    {
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
        $this->setJob('WeebHook-UpdateNFe');

        //$this->log_data('WebHook', 'WebHookUpdateNfe', 'Novo registro para atualização de estoque', "I");
        if (!isset($_GET['apiKey'])) {
            $this->log_data('WebHook', 'WebHookUpdateNfe - Valid', 'Não foi encontrado a parâmetro apiKey, GETs='.json_encode($_GET), "E");
            $this->response("apiKey não encontrado", REST_Controller::HTTP_UNAUTHORIZED);
            return false;
        }

        $apiKey = filter_var($_GET['apiKey'], FILTER_SANITIZE_STRING);
        $store = $this->getStoreForApiKey($apiKey);
        if (!$store) {
            $this->log_data('WebHook', 'WebHookUpdateNfe - Valid', "apiKey não localizado para nenhuma loja, apiKey={$_GET['apiKey']}", "E");
            $this->response('apiKey não corresponde a nenhuma loja', REST_Controller::HTTP_UNAUTHORIZED);
            return false;
        }

        // define configuração da integração
        try {
            $this->setDataIntegration($store);
        } catch (Exception $exception) {
            $this->log_data('WebHook', 'WebHookUpdateNfe - Valid', $exception->getMessage(), "E");
            $this->response($exception->getMessage(), REST_Controller::HTTP_OK);
            return false;
        }

        // define multiloja de preço, caso tenha
        if ($this->multiStore === false) {
            $this->log_data('WebHook', 'WebHookUpdateNfe - Valid', "Multiloja Não Encontrada - A loja está configurada para usar uma Multiloja, mas não foi encontrada da bling, apiKey={$_GET['apiKey']}", "E");
            $this->response('A loja está configurada para usar uma Multiloja, mas não foi encontrada da bling', REST_Controller::HTTP_OK);
            return false;
        }

        // Recupera dados enviado pelo body
        $nfes = json_decode(str_replace('data=', '', file_get_contents('php://input')));

        //$this->log_data('WebHook', 'WebHookUpdateNfe - Start', json_encode($nfes), "I");

        if (!isset($nfes->retorno->notasfiscais)) return false;

        $nfes = $nfes->retorno->notasfiscais;

        foreach ($nfes as $nfe) {

            if (!isset($nfe->notafiscal)) continue;

            $nfe = $nfe->notafiscal;

            $orderId     = $nfe->numeroPedidoLoja;
            $numNFe      = $nfe->numero;
            $serieNFe    = $nfe->serie;
            $valorNFe    = $nfe->valorNota;
            $dataEmissao = $nfe->dataEmissao;
            $chaveNFe    = str_replace(' ', '', $nfe->chaveAcesso);

            if (!$chaveNFe) continue; // nfe provavelmente pendente

            $this->setUniqueId($orderId); // define novo unique_id

            if (!$this->getOrderExist($orderId)) {
                $msgError = "Pedido {$orderId} não encontrado, foi enviado uma NF-e. payload_nfe=".json_encode($nfe);
                //echo "{$msgError}\n";
                //$this->log_data('batch', $log_name, $msgError, "W");
                continue;
            }

            // Pedido já tem uma NF-e, atualizar o status
            $orderWithNfe = $this->getOrderWithNfe($orderId);
            if ($orderWithNfe) {
                $msgError = "Pedido já tem uma NF-e. Será atualizado apenas seu status para 52. PEDIDO_CONECTA={$orderId} para atualizar! ORDER_INTEGRATION=".json_encode($nfe);
                //echo "{$msgError}\n";
                //$this->log_data('batch', $log_name, $msgError, "W");
                // passar pedido para status 50
                // $this->updateStatusForOrder($orderId, 50, 3); // FLUXO ANTIGO
				$this->updateStatusForOrder($orderId, 52, 3);
                $this->removeOrderIntegration($orderId);
                continue;
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
                $this->log_data('batch', $log_name, $msgError, "E");
                $this->log_integration("Erro para atualizar o pedido {$orderId}", "<h4>Não foi possível atualizar dados de faturamento do pedido {$orderId}</h4>", "E");
                continue;
            }

            // Remover pedido com status=3 da fila
            $this->removeOrderIntegration($orderId);

            // Salvar XML
            try {
                $this->saveXML($chaveNFe, $orderId);
            } catch (\Exception $e) {
                $this->log_data('batch', $log_name, "Erro para salvar o XML: {$e->getMessage()}", "E");
                //continue;
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

        }
        return $this->response(null, REST_Controller::HTTP_OK);
    }

    /**
     * Recupera a loja pelo apiKey
     *
     * @param string    $apiKey ApiKey de callback
     * @return int|null         Retorna o código da loja, ou nulo caso não encontre
     */
    private function getStoreForApiKey($apiKey)
    {
        $query = $this->db->get_where('stores', array('token_callback'  => $apiKey))->row_array();
        return $query ? (int)$query['id'] : null;
    }

    /**
     * Define o token de integração
     *
     * @param string $token Token de integraçõa
     */
    private function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * Define a loja
     *
     * @param int $store Código da loja
     */
    private function setStore($store)
    {
        $this->store = $store;
    }

    /**
     * Define a empresa
     */
    private function setCompany($company)
    {
        $this->company = $company;
    }

    /**
     * Define a Multiloja, caso exista
     */
    private function setMultiStore($multiStore)
    {
        $this->multiStore = $multiStore;
    }

    /**
     * Define o job
     */
    private function setJob($job)
    {
        $this->job = $job;
    }

    /**
     * Define o unique id
     */
    private function setUniqueId($uniqueId)
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
    private function log_integration($title, $description, $type)
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
     * @throws Exception
     */
    private function setDataIntegration(int $store_id)
    {
        $dataIntegration = $this->db->get_where('api_integrations', array('store_id' => $store_id))->row_array();
        $dataStore       = $this->model_stores->getStoresData($store_id);

        $credentials = json_decode($dataIntegration['credentials']);

        if (!property_exists($credentials, 'apikey_bling') || !property_exists($credentials, 'loja_bling')) {
            throw new Exception("Credenciais não configurada para a loja $store_id");
        }

        $this->setStore($store_id);
        $this->setToken($credentials->apikey_bling);
        $this->setCompany($dataStore['company_id']);
        $this->setMultiStore($credentials->loja_bling);
    }

    /**
     * Cria dados de faturamento do pedido e atualiza o status do pedido para 52
     *
     * @param   array   $data   Dados da nfe para inserir
     * @return  bool            Retorna o status da criação
     */
    private function createNfe($data)
    {
        $sqlNfe     = $this->db->insert_string('nfes', $data);
        $insertNfe  = $this->db->query($sqlNfe) ? true : false;

        if (!$insertNfe) return false;
		
		// return $this->updateStatusForOrder($data['order_id'], 50, 3); // FLUXO ANTIGO 
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
     * Salva um arquivo XML da NFe
     *
     * @param   int     $chave      Chave da NFe
     * @param   int     $orderId    Código do pedido na Conecta Lá
     * @return  bool                Retorna o status da importação do xml
     */
    private function saveXML($chave, $orderId)
    {
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

        $url = "https://www.bling.com.br/relatorios/nfe.xml.php?chaveAcesso={$chave}";

        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, $url);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, TRUE);
        $response = curl_exec($curl_handle);
        $httpcode = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
        curl_close($curl_handle);
        $dataXml['httpcode'] = $httpcode;
        $dataXml['content'] = $response;


        if ($dataXml['httpcode'] != 200) {
            $msgError = "Não foi possível localizar o xml do pedido {$orderId}! RETORNO=" . json_encode($dataXml);
            echo "{$msgError}\n";
            $this->log_data('batch', $log_name, $msgError, "E");
            $this->log_integration("Erro para obter o XML do pedido {$orderId}", "<h4>Não foi possível obter o XML do pedido {$orderId}</h4>", "E");
            return false;
        }

        $dataXml = $dataXml['content'];

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

        $arquivo = fopen($targetDir . $chave . ".xml",'w');

        if ($arquivo == false) return false;

        fwrite($arquivo, $dataXml);

        fclose($arquivo);

        return true;
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
     * Recupera se o pedido existe na Conecta Lá
     *
     * @param   int   $orderId  Código do pedido Conecta Lá
     * @return  bool            Retorna se o pedido existe ou não
     */
    private function getOrderExist($orderId)
    {
        return $this->db
            ->get_where('orders',
                array(
                    'id'        => $orderId,
                    'store_id'  => $this->store
                )
            )->num_rows() == 0 ? false : true;
    }
}