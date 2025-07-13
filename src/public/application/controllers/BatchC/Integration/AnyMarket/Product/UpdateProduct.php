<?php
require APPPATH . "controllers/Api/Integration/AnyMarket/traits/ValideMaiorPrazoOperacional.trait.php";
require APPPATH . "controllers/BatchC/Integration/Integration.php";
require APPPATH . "controllers/Api/Integration/AnyMarket/Validations/ParserOrderAnymarket.php";
require APPPATH . "controllers/Api/Integration/AnyMarket/Validations/ValidationProduct.php";
require APPPATH . "controllers/BatchC/Integration/tools/RemoveAccentsAndCedilla.trait.php";
require APPPATH . "controllers/BatchC/Integration/tools/LoadSizeToDescriptionAndNameProduct.trait.php";
require APPPATH . "controllers/Api/Integration/AnyMarket/traits/UpdatePrazoOperacionalExtra.trait.php";
require APPPATH . "controllers/Api/Integration/AnyMarket/traits/UpdateProductTrait.trait.php";

/**
 * @property CI_DB_query_builder $db
 * @property Model_atributos_categorias_marketplaces $model_atributos_categorias_marketplaces
 */
class UpdateProduct extends Integration
{

    use UpdateProductTrait;
    private $integration = null;
    private $orders_to_integration = null;

    protected $updateProductPriceStock = true;
    protected $updateProductCrossdocking = true;

    use removeAccentsAndCedilla;
    use LoadSizeToDescriptionAndNameProduct;
    public function __construct()
    {
        parent::__construct();
        $this->setTypeIntegration('AnyMarket');

        $logged_in_sess = array(
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'userstore' => 0,
            'logged_in' => true,
        );
        $this->session->set_userdata($logged_in_sess);

        $this->setJob('UpdateProduct');

        $this->load->model('model_api_integrations');
        $this->load->model('model_anymarket_temp_product');
        $this->load->model('model_integrations');
        $this->load->model('model_anymarket_log');
        $this->load->model('model_atributos_categorias_marketplaces');

        $this->validator = new ValidationProduct($this);
        $this->loadSizeToDescriptionAndNameProduct();
        $this->url_anymerket = $this->model_settings->getValueIfAtiveByName('url_anymarket');
        if (!$this->url_anymerket) {
            throw new Exception("\'url_anymerket\' não está definido no sistema");
        }
        $this->CI = $this;
    }

    /*
     * php index.php BatchC/Integration/AnyMarket/Product/UpdateProduct run null 1
     * order(post-any)->initialImportDate(get-conecta)->order status(PUT-any)
     *
     * updateOrderStatusInMarketPlace(put-conectala)->order by id(get-any)
     */
    public function run($id = null, $store = null)
    {
        $log_name = $this->typeIntegration . '/' . $this->router->fetch_class() . '/' . __FUNCTION__;
        if (!$store) {
            $this->log_data('batch', $log_name, "Parametros informados incorretamente. ID={$id} - STORE={$store}", "E");
            echo ("Parametros informados incorretamente. ID={$id} - STORE={$store}");
            return;
        }
        $this->store=$store;

        $store_array=$this->model_stores->getStoresById($store);
        $this->company=$store_array['company_id'];
        /* inicia o job */
        $this->setIdJob($id);
        $modulePath = (str_replace("BatchC/", '', $this->router->directory)) . $this->router->fetch_class();
        // echo(."\n");
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $store)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado, job_id=' . $id . ' store_id=' . $store, "E");
            echo ("Já tem um job rodando com status 1\n");
            return;
        }
        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $store), "I");

        // $this->store_id = $store;
        // /* faz o que o job precisa fazer */
        // echo "Pegando pedidos para enviar... \n";

        // // Define a loja, para recuperar os dados para integração
        $response = $this->getIntegration($store);
        if ($response === true) {
            $data = [
                'checked' => 0,
                'integration_id' => $this->integration['id'],
            ];
            $itens = $this->db->select()->from('anymarket_queue')
                ->where($data)->order_by('date_update', 'asc')
                ->limit(1000)
                ->get()->result_array();
            foreach ($itens as $item) {
                $contentReceived = null;
                $dataValidation = null;
                $body = json_decode($item["received_body"], true);
                $tmpProd = (array)$this->db->select()
                    ->from('anymarket_temp_product')
                    ->where([
                        'anymarketId' => (string)$item['idSku'],
                        'integration_id' => $this->integration['id']
                    ])->order_by('id', 'desc')->limit(1)
                    ->get()->row();
                if ($tmpProd) {
                    $contentReceived = json_decode($tmpProd['json_received'], true);
                    $dataValidation = json_decode($tmpProd['data'], true);
                }
                try {
                    $response = $this->updateProductTrait($body, $this->integration, [
                        'adReceivedData' => $contentReceived ?? [],
                        'preValidationProdData' => $dataValidation ?? []
                    ]);
                    if (is_array($response)) {
                        $action = $response['action'] ?? '';
                        if ($action == 'remove') {
                            $this->db->delete('anymarket_queue', [
                                    'id' => $item['id']
                                ]
                            );
                        }
                    } else if ($response) {
                        $this->db->update('anymarket_queue', ['checked' => 1, "idSkuMarketplace" => $body["idSkuMarketplace"], 'idProduct' => $body["idProduct"]], ['id' => $item['id']]);
                    }
                } catch (Throwable $e) {
                    $this->db->delete('anymarket_queue', [
                            'id' => $item['id']
                        ]
                    );
                    echo "Throwable: {$e->getMessage()}\n";
                }
            }
            //     $this->getAllProductToUpdate($store);
            //     $this->updateProducts();
        }

        // // Grava a última execução
        $this->saveLastRun();

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }
    private function getIntegration($store)
    {
        echo ("Pegando dados para integração\n");
        $this->integration = $this->model_api_integrations->getUserByAnyByStore($store);
        $this->app_id_anymarket = $this->appId = $this->model_settings->getValueIfAtiveByName('app_id_anymarket');;
        if (!$this->appId) {
            return "appID não definida para este usuario, por favor solicite a devida configuração no site da anymarket.\nLoja: {$store}";;
        }
        $credentiais = json_decode($this->integration["credentials"], true);
        if (!isset($credentiais['token_anymarket'])) {
            return "token_anymarket não definida para este usuario, por favor solicite a devida configuração no site da anymarket.";
        }
        $this->setToken($credentiais['token_anymarket']);
        $app_id_anymarket = $this->model_settings->getValueIfAtiveByName('app_id_anymarket');
        if (!$app_id_anymarket) {
            return "appID não definida para este usuario, por favor solicite a devida configuração no site da anymarket.";
        }
        $this->setAppKey($app_id_anymarket);
        if (!$this->integration) {
            return "Mais de uma integração Anymarket para esta loja.";
        }
        echo ("Inciando procedimento para a integração {$this->integration['id']} na loja {$store} com OI AnyMarket {$this->integration['id_anymarket_oi']}\n");

        $this->updateProductPriceStock = $credentiais['updateProductPriceStock'] ?? true;
        $this->updateProductCrossdocking = $credentiais['updateProductCrossdocking'] ?? true;
        return true;
    }
    private function getAllProductToUpdate()
    {
        $where = [
            'integration_id' => $this->integration['id'],
            'need_update' => 1,
        ];
        $this->temp_products = $this->model_anymarket_temp_product->getManyData($where);
    }
    private function updateProducts()
    {

        if (count($this->temp_products) > 0) {
            foreach ($this->temp_products as $temp_product) {
                $body = json_decode($temp_product['json_received'], true);
                $update = $this->updateProductTrait($body, $this->integration);
                if ($update) {
                    $data = ['need_update' => 0,'skuInMarketplace'=>$body['idInMarketplace']];
                    $update = $this->model_anymarket_temp_product->update($temp_product['id'], $data);
                }
            }
        }
    }
}
