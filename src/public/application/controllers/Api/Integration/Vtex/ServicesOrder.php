<?php

require APPPATH . "libraries/REST_Controller.php";

class ServicesOrder extends REST_Controller
{
    public $accountName;
    public $environment;
    public $salesChannel;
    public $affiliateId;
    public $appKey;
    public $token;
    public $store;
    public $company;
    public $order;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_nfes');
        $this->load->model('model_orders');
        $this->load->model('model_integrations');
    }

    /**
     * Endpoint destinado apenas para responder a VTEX quando
     * realizamos a chama de cancelamento e recebimento de NFe.
     *
     * É preciso enviar um endpoint no pedido e caso realize um
     * cancelamento ou faturamento, a VTEX chamará esse endpoint
     * e devemos responder com status 200. Caso sej cancelamento
     * o pedido não será cancelado na VTEX, caso for faturamento
     * grava os dados da NFe.
     */
    public function index_post($pub, $orders, $orderId, $type)
    {
        if ($type === 'invoice') {
            $log_name = __CLASS__ . '/' . __FUNCTION__;

            $dataOrder  = $this->model_orders->getOrdersData(0, $orderId);
            if (!$dataOrder) { // não encontrou pedido
                $this->log_data('api', $log_name, "Pedido VTEX {$orderId}, não encontrado.\nURL={$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}", "E");
                return $this->response('Order not found', REST_Controller::HTTP_BAD_REQUEST);
            }
            $orderIdVtex = $dataOrder['order_id_integration'];
            $this->store = $dataOrder['store_id'];
            $this->company = $dataOrder['company_id'];
            $this->order = $dataOrder['id'];
            $this->setDataIntegration($dataOrder['store_id']);
            $dataNfes   = $this->model_nfes->getNfesDataByOrderId($orderId, true);
            if (count($dataNfes)) { // pedido já tem nfe
                // passar pedido para status 52
                $this->updateStatusForOrder($orderId, 52, 3);
                $this->model_integrations->removeOrderToIntegrationByOrderAndStatus($orderId, 3);
                return $this->response(NULL, REST_Controller::HTTP_OK);
            }

            if (!$orderIdVtex) { // pedido não tem registro de integração
                $this->log_data('api', $log_name, "Pedido:{$orderId} | tipo:{$type} | Não encontrado registro de integração.\nURL={$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}\nBODY=".file_get_contents('php://input'), "E");
                return $this->response('Order not found', REST_Controller::HTTP_BAD_REQUEST);
            }

            // Obter dados do pedido
            $dataOrderVtex = $this->getOrderERP($orderIdVtex);
            $contentOrder = json_decode($dataOrderVtex['content']);

            if ($dataOrderVtex['httpcode'] != 200) {
                $this->log_data('api', $log_name, "Não foi possível localizar o pedido {$orderId}! RETORNO=" . json_encode($dataOrderVtex), "E");
                return $this->response('Order not found on vtex', REST_Controller::HTTP_BAD_REQUEST);
            }

            $contentOrder = $contentOrder->packageAttachment->packages;

            // verifica se existe chave de acesso, caso não tenha não foi faturado ainda
            if (!count($contentOrder) || (isset($nfe->invoiceNumber) && $nfe->invoiceNumber === null)) {
                $this->log_data('api', $log_name, "Pedido {$orderId} ainda não faturado! RETORNO=" . json_encode($dataOrderVtex), "E");
                return $this->response('Uninvoiced order', REST_Controller::HTTP_BAD_REQUEST);
            }

            // Dados da NF-e
            $nfe = $contentOrder[0];

            // Dados para inserir a NF-e
            $arrNfe = array(
                'order_id'      => $orderId,
                'company_id'    => $dataOrder['company_id'],
                'store_id'      => $dataOrder['store_id'],
                'date_emission' => date('d/m/Y H:i:s', strtotime($nfe->issuanceDate)),
                'nfe_value'     => substr_replace($nfe->invoiceValue, '.', -2, 0),
                'nfe_serie'     => substr($nfe->invoiceKey, 22, 3),
                'nfe_num'       => $nfe->invoiceNumber,
                'chave'         => str_replace(' ', '', $nfe->invoiceKey)
            );

            $insertNfe = $this->db->query($this->db->insert_string('nfes', $arrNfe));

            // Erro para iserir a NF-e
            if (!$insertNfe) {
                $this->log_data('batch', $log_name, "Não foi possível inserir dados de faturamento do pedido {$orderId}! DATA_NFE_INSERT=" . json_encode($arrNfe) . ", DATA_NFE_VTEX=" . json_encode($contentOrder) . " RETORNO=" . json_encode($insertNfe), "E");
                return $this->response('internal error', REST_Controller::HTTP_BAD_REQUEST);
            }

            $this->updateStatusForOrder($orderId, 52, 3);
            $this->model_integrations->removeOrderToIntegrationByOrderAndStatus($orderId, 3);

            $this->log_integration(
                "Pedido {$orderId} atualizado",
                "<h4>Foi atualizado dados de faturamento do pedido {$orderId}</h4> 
                          <ul>
                            <li><strong>Chave:</strong> {$nfe->invoiceKey}</li>
                            <li><strong>Número:</strong> {$nfe->invoiceNumber}</li>
                            <li><strong>Série:</strong> ".substr($nfe->invoiceKey, 22, 3)."</li>
                            <li><strong>Data de Emissão:</strong> ".date('d/m/Y H:i:s', strtotime($nfe->issuanceDate))."</li>
                            <li><strong>Valor:</strong> " . number_format(substr_replace($nfe->invoiceValue, '.', -2, 0), 2, ',', '.') . "</li>
                          </ul>",
                "S"
            );
        }

        return $this->response(NULL, REST_Controller::HTTP_OK);
    }
    /**
     * Request API
     *
     * @param   string      $url    URL requisição
     * @param   string|null $data   Dados para envio no body
     * @param   string      $method Metodo da requisição
     * @return  array
     * @throws  Exception
     *
     * BLING:   Caso seja ultrapassado o limite a requisição retornará o status 429 (too many requests) e a mensagem:
     *          O limite de requisições foi atingido.
     *
     * TINY:    Entrará no método apiBlockSleep() e fará a validação se foi bloqueado a requisição para aguarda
     */
    public function sendREST(string $url, ?string $data = '', string $method = 'GET'): array
    {
        if (!preg_match('/http/', $url))
            $url = "https://{$this->accountName}.{$this->environment}.com.br/{$url}";

        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, $url);

        $arr_opt = array(
            "accept: application/vnd.vtex.ds.v10+json",
            "content-type: application/json",
            "x-vtex-api-apptoken: {$this->token}",
            "x-vtex-api-appkey: {$this->appKey}"
        );

        if ($method == "POST" || $method == "PUT" || $method == "PATCH") {

            if ($method == "PUT")
                curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'PUT');
            elseif ($method == "PATCH")
                curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, 'PATCH');

            curl_setopt($curl_handle, CURLOPT_POSTFIELDS, json_encode($data));
        }

        curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $arr_opt);

        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, TRUE);

        $response = curl_exec($curl_handle);
        $httpcode = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
        curl_close($curl_handle);

        $header['httpcode'] = $httpcode;
        $header['content']  = $response;

        return $header;
    }

    /**
     * Define os dados para integração
     *
     * @param int $store_id Código da loja
     */
    public function setDataIntegration(int $store_id)
    {
        $dataIntegration    = $this->db->get_where('api_integrations', array('store_id' => $store_id))->row_array();
        $credentials        = json_decode($dataIntegration['credentials']);

        $this->token        = $credentials->token_vtex;
        $this->appKey       = $credentials->appkey_vtex;
        $this->accountName  = $credentials->account_name_vtex;
        $this->environment  = $credentials->environment_vtex;
        $this->salesChannel = $credentials->sales_channel_vtex;
        $this->affiliateId  = $credentials->affiliate_id_vtex;
    }

    /**
     * Recupera dados do pedido no ERP
     *
     * @param   string      $orderIdVtex    Código VTEX
     * @return  array
     * @throws  Exception
     */
    public function getOrderERP(string $orderIdVtex): array
    {
        return $this->sendREST("api/oms/pvt/orders/{$orderIdVtex}");
    }

    /**
     * Atualiza status de um pedido
     *
     * @param   int      $orderId       Código do pedido
     * @param   int      $status        Código do status
     * @param   int|null $verifyStatus  Código do status para verificação
     * @return  bool                    Retorna o status da atualização
     */
    public function updateStatusForOrder(int $orderId, int $status, int $verifyStatus = null): bool
    {
        $where = array(
            'id'        => $orderId,
            'store_id'  => $this->store,
        );
        if ($verifyStatus) $where['paid_status'] = $verifyStatus;

        return (bool)$this->db->where($where)->update('orders', array('paid_status' => $status));
    }

    /**
     * Cria um log da integração para ser mostrada ao usuário
     *
     * @param   string      $title          Título do log
     * @param   string      $description    Descrição do log
     * @param   string      $type           Tipo de log
     * @return  bool                        Retornar o status da criação do log
     */
    public function log_integration(string $title, string $description, string $type): bool
    {
        $data = array(
            'store_id'      => $this->store,
            'company_id'    => $this->company,
            'title'         => $title,
            'description'   => $description,
            'type'          => $type,
            'job'           => 'UpdateStatus',
            'unique_id'     => $this->order,
            'status'        => 1
        );

        // verifica se o log já existe, para não ser duplicado
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
}