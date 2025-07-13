<?php

require APPPATH . "libraries/REST_Controller.php";
require APPPATH . "controllers/BatchC/Integration/Tiny/Product/Product.php";

class Label extends REST_Controller
{
    public $job;
    public $unique_id = null;
    public $token;
    public $store;
    public $company;
    public $listPrice;
    public $formatReturn = "json";
    public $product;

    /**
     * Instantiate a new Label instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_stores');
        $this->load->library('JWT');
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
        $dataIntegration = $this->setDataIntegration($store);

        if ($dataIntegration === false) {
            ob_clean();
            return $this->response('apiKey não corresponde a nenhuma loja', REST_Controller::HTTP_UNAUTHORIZED);
        }

        // Recupera dados enviado pelo body
        $body = json_decode(file_get_contents('php://input'));

        if (!property_exists($body, 'labelType') || !property_exists($body, 'groupLabel') || !property_exists($body, 'orders')) {
            return $this->response('Campos requeridos incompletos!', REST_Controller::HTTP_BAD_REQUEST);
        }

        $labelType  = $body->labelType;
        $groupLabel = $body->groupLabel;
        $orders     = $body->orders;
        $labels     = array();

        if (!in_array($labelType, array('pdf', 'zpl', 'thermal'))) {
            return $this->response('O tipo de etiqueta está inválido, informa pdf, zpl ou thermal!', REST_Controller::HTTP_BAD_REQUEST);
        }

        switch ($labelType) {
            case 'pdf':
                $fieldLabel = 'file_a4';
                break;
            case 'zpl':
                $fieldLabel = 'file_zpl';
                break;
            case 'thermal':
                $fieldLabel = 'file_thermal';
                break;
            default:
                $fieldLabel = 'file_a4';
        }

        if (count($orders) > 100) {
            return $this->response('Não permitido consultar mais que 100 pedidos!', REST_Controller::HTTP_BAD_REQUEST);
        }

        foreach ($orders as $order) {

            if (!property_exists($order, 'id')) {
                return $this->response('Campos ID do pedido não localizado!', REST_Controller::HTTP_BAD_REQUEST);
            }

            $dataTracking = $this->createArrayTracking($order->id);
            if ($dataTracking === false) {
                return $this->response("Pedido ($order->id) não localizado ou etiqueta está indisponível.", REST_Controller::HTTP_BAD_REQUEST);
            }

            $labels[$order->id] = array();

            foreach ($dataTracking['label'] as $label) {
                $labels[$order->id] = array(
                    'label'         => empty($label[$fieldLabel]) ? null : $label[$fieldLabel],
                    'plp'           => empty($label['file_plp']) ? null : $label['file_plp'],
                    'number_plp'    => empty($label['number_plp']) ? null : $label['number_plp']
                );
            }
        }

        $labelGroup = array();
        $checkPlpGroup = array();
        $i = -1;
        foreach ($labels as $order => $label) {

            if ($groupLabel && $label['number_plp']) {
                if (array_key_exists($label['number_plp'], $checkPlpGroup)) {
                    $labelGroup['archives'][$checkPlpGroup[$label['number_plp']]]['orders'][] = $order;

                    $groupPlp = base_url("assets/images/etiquetas/P_T_{$label['number_plp']}_A4.pdf");
                    $labelGroup['archives'][$checkPlpGroup[$label['number_plp']]]['taglink'] = $groupPlp;
                    continue;
                }

                $checkPlpGroup[$label['number_plp']] = ++$i;
            } else {
                $i++;
            }

            $archive = array(
                "orders" => [$order],
                "taglink" => $label['label']
            );

            if ($label['plp']) {
                $archive["plplink"] = $label['plp'];
            }

            $labelGroup['archives'][] = $archive;

        }

        ob_clean();
        return $this->response($labelGroup, REST_Controller::HTTP_OK);
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
     * Define os dados para integração
     *
     * @param int $store_id Código da loja
     */
    public function setDataIntegration($store_id)
    {
        $dataIntegration = $this->db->get_where('api_integrations', array('store_id' => $store_id))->row_array();
        $dataStore       = $this->model_stores->getStoresData($store_id);

        $credentials = json_decode($dataIntegration['credentials']);

        if ($credentials === null) {
            return false;
        }

        $this->setStore($store_id);
        $this->setToken($credentials->token_tiny);
        $this->setCompany($dataStore['company_id']);
        $this->setListPrice($credentials->id_lista_tiny);

        return true;
    }

    private function createArrayTracking($order_id)
    {
        $tracking = $this->getDataTracking($order_id);

        if (count($tracking) == 0) {
            return false;
        }

        $order = $tracking[0];

        $codesTracking = array();
        $labels = array();

        $key = get_instance()->config->config['encryption_key'];

        foreach ($tracking as $codeTRacking) {
            if (in_array($codeTRacking['codigo_rastreio'], $codesTracking) || empty($codeTRacking['codigo_rastreio'])) {
                continue;
            }

            if (empty($codeTRacking['link_etiqueta_a4'])) {

                $tokenLabel = $this->jwt->encode(array(
                    'orders' => [ $order['order_id'] ],
                    'iat' =>  time(),
                    'exp' => time() + 60 * 60 * 24 // 24h
                ), $key);

                $codeTRacking['link_etiqueta_a4'] = base_url("Tracking/printLabel/$tokenLabel");
            }

            $labels[] = array(
                "file_a4"           => $codeTRacking['link_etiqueta_a4'],
                "file_thermal"      => $codeTRacking['link_etiqueta_termica'],
                "file_zpl"          => $codeTRacking['link_etiquetas_zpl'],
                "file_plp"          => $codeTRacking['link_plp'],
                "tracking_code"     => $codeTRacking['codigo_rastreio'],
                "number_plp"        => $codeTRacking['number_plp'] ?? null,
                "tracking_url"      => $codeTRacking['url_tracking'] ?? null
            );

            $codesTracking[] = $codeTRacking['codigo_rastreio'];
        }

        return array(
            "label" => $labels
        );
    }

    private function getDataTracking($order)
    {
        $sql = "SELECT orders.*, freights.*, correios_plps.number_plp  FROM orders JOIN freights ON orders.id = freights.order_id LEFT JOIN correios_plps ON orders.id = correios_plps.order_id WHERE orders.id = $order AND orders.store_id = $this->store";
        $query = $this->db->query($sql);
        return $query->result_array();
    }
}