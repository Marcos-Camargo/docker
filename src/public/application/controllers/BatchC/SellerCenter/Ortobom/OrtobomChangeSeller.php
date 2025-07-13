<?php

require APPPATH . "libraries/CalculoFrete.php";

/**
 * Class OrtobomChangeSeller
 */
class OrtobomChangeSeller extends BatchBackground_Controller
{
    /**
     * @var array
     */
    private $companyMain;

    /**
     * @var array
     */
    private $storeMain;

    /**
     * @var string
     */
    private $accessToken;

    /**
     * OrtobomChangeSeller constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $logged_in_sess = array(
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);

        // carrega os modulos necessários para o Job
        $this->load->model('model_orders');
        $this->load->model('model_stores');
        $this->load->model('model_settings');
        $this->load->model('model_products');
        $this->load->model('model_company');
        $this->load->model('model_products_catalog');
        $this->load->model('model_orders_to_integration');
        $this->load->library('RESTfulAPI');
    }

    /**
     * Início do job
     *
     * @param int|null     $id     Código do job que será executado
     * @param string|null  $params Parametro adicional
     */
    public function run(int $id = null, string $params = null)
    {
        /* inicia o job */
        $this->setIdJob($id);
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;
        $modulePath = (str_replace("BatchC/", '',$this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
            return;
        }
        $this->log_data('batch', $log_name, 'start ' . trim($id . " " . $params), "I");

        if ($this->getDataStoreMain()) $this->changeSeller();
        else echo "Não encontrou loja configurada no parametro 'publish_only_stores'\n";

        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();

    }

    /**
     * Recupera o código da loja e empresa Main para ser dona do produto publicado
     *
     * @return bool
     */
    private function getDataStoreMain(): bool
    {
        $log_name = $this->router->fetch_class().'/'.__FUNCTION__;

        // recupera loja main
        $onlyStorePublishedSetting = $this->model_settings->getSettingDatabyName('publish_only_one_store_company');
        if ($onlyStorePublishedSetting && $onlyStorePublishedSetting['status'] == 1) {
            $storePublished = $onlyStorePublishedSetting['value'];
        } else {
            echo "parametro 'publish_only_one_store_company' não existe ou não está ativo.\n";
            return false;
        }

        //pego a loja
        $this->storeMain = $this->model_stores->getStoresData($storePublished);
        if (is_null($this->storeMain)) {
            $erro = "Não encontrei nenhuma loja com o mesmo CNPJ da empresa {$this->companyMain['name']} garanta que a loja franqueadora master tenha o CNPJ {$this->companyMain['CNPJ']}\n";
            echo $erro."\n";
            $this->log_data('batch',$log_name, $erro ,"E");
            return false;
        }

        // pego a empresa
        $this->companyMain = $this->model_company->getCompanyData($this->storeMain['company_id']);
        if (is_null($this->companyMain)) {
            $erro = 'Não existe nenhuma empresa configurada.';
            echo "{$erro}\n";
            $this->log_data('batch',$log_name, $erro ,"E");
            return false;
        }

        return true;
    }

    /**
     * Recupera o access token para login
     *
     * @return bool
     */
    private function getAccessToken(): bool
    {
        $body = array(
            'Grant_type' => 'password',
            'Login'      => 'conectala',
            'Senha'      => 'conectala@2584'
        );

        $response = $this->restfulapi->sendRest('https://apimid.ortobom.com.br/api/v1/Token', array(), json_encode($body), 'POST');

        if ($response['httpcode'] != 200) return false;

        $responseDecode = json_decode($response['content']);

        return (bool)($this->accessToken = $responseDecode->data->access_token);
    }

    /**
     * Troca o pedido de loja
     *
     * @return void
     */
    private function changeSeller(): void
    {
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

        // Pego os pedidos da loja configurada
        $orders = $this->model_orders->getOrdersByFilter("store_id = {$this->storeMain['id']} AND paid_status = 3 AND incidence_user is null");

        if (!$this->getAccessToken()) {
            echo "Não possível obter o access_token para se autenticar.\n";
            return;
        }

        foreach ($orders as $order) {

            echo "\n--------------------------------\n\n";
            echo "Trocar o pedido {$order['id']} de seller \n";

            // monta matriz com os dados para achar o novo seller, com os dados para consultar o frete
            $zipCode = preg_replace('/[^0-9]/', '', $order['customer_address_zip']);
            $city    = $order['customer_address_city'];
            $state   = $order['customer_address_uf'];

            // recuperar o seller com o melhor preço(prioridade) e a entrega mais rápida
            $newSeller = $this->getNewSeller($order['id'], $zipCode, $city, $state);

            if ($newSeller === false) {
                $this->createIncidence($order, 'Não encontrou fábrica para atender o pedido!');
                echo "Pedido {$order['id']}, não encontrada para nenhuma loja para enviar o pedido.\n";
                return;
            }

            $dataNewStore = $this->model_stores->getStoreByCNPJ($newSeller['cnpj']);

            // não encontrou loja pelo cnpj
            if (!$dataNewStore) {
                echo "Pedido {$order['id']}, CNPJ={$newSeller['cnpj']} e Fábrica={$newSeller['factory']} não encontrada para nenhuma loja cadastrado no seller center.\n";
                $this->createIncidence($order, "Não encontrou fábrica com o cnpj {$newSeller['cnpj']} para atender o pedido!");
                return;
            }

            // loja inativa
            if ($dataNewStore['active'] != 1) {
                echo "Pedido {$order['id']}, não é possível enviar para a loja {$dataNewStore['id']} com cnpj {$dataNewStore['CNPJ']}, a loja está inativa.\n";
                $this->createIncidence($order, "Loja '{$dataNewStore['name']} - ({$newSeller['cnpj']})' com cnpj {$dataNewStore['CNPJ']}, está inativa!");
                return;
            }

            // data items
            $itens = $this->model_orders->getOrdersItemData($order['id']);

            // verifica se seller tem estoque dos itens vendidos
            if (!$this->verifyStockStore($itens, $dataNewStore['id'])) {
                echo "Pedido {$order['id']}, loja {$dataNewStore['id']} com cnpj {$dataNewStore['CNPJ']}, não vende o produto ou não tem estoque para atender todos os produtos.\n";
                $this->createIncidence($order, "Loja '{$dataNewStore['name']} - ({$newSeller['cnpj']})' não vende o produto ou não tem estoque para atender todos os produtos!");
                return;
            }

            // loja não pertence a empresa da loja dona do pedido
            if ($dataNewStore['company_id'] != $this->companyMain['id']) {
                echo "Pedido {$order['id']}, não percente a empresa {$this->companyMain['id']}, não pode receber esse pedido.\n";
                $this->createIncidence($order, "Loja '{$dataNewStore['name']}' com cnpj {$dataNewStore['CNPJ']}, não pertence a empresa '{$this->companyMain['name']}'!");
                return;
            }

            // Inicia transação
            $this->db->trans_begin();

            foreach ($itens as $item) {

                $prd = $this->model_products_catalog->getProductByProductCatalogIdAndStoreId($item['product_catalog_id'], $dataNewStore['id']);

                // reduz estoque do novo seller
                $this->model_products->reduzEstoque($prd['id'], $item['qty'], $item['variant'], $order['id']); // reduz estoque produto

                // trocar item de seller
                $dataChangeSeller = [
                    'sku'        => $prd['sku'],
                    'product_id' => $prd['id'],
                    'store_id'   => $dataNewStore['id']
                ];
                $this->model_orders->updateItenByOrderAndId($item['id'], $dataChangeSeller);

                echo "trocou o item de seller: ".json_encode($dataChangeSeller)."\n";
            }

            for ($x = 1; $x <= 3; $x++) {
                $data = array(
                    'order_id'      => $order['id'],
                    'company_id'    => $this->companyMain['id'],
                    'store_id'      => $dataNewStore['id'],
                    'paid_status'   => $x,
                    'new_order'     => $x === 1 ? 1 : 0,
                    'updated_at'    => date('Y-m-d H:i:s')
                );
                $this->model_orders_to_integration->create($data);
            }

            //trocar pedido de seller
            $this->model_orders->updateByOrigin($order['id'], ['store_id' => $dataNewStore['id']]);
            // deixar o pedido com o new_order=1, para ser integrado no erp do novo seller, caso use integração
            //$this->model_orders->updateOrderToIntegrationByOrderAndStatus($order['id'], $dataNewStore['id'], $order['paid_status'], ['new_order' => 1]);
            echo "Trocou o seller do pedido {$order['id']}, para o seller {$dataNewStore['id']}\n";
            $this->log_data('batch', $log_name, "Trocou o seller do pedido {$order['id']}, para o seller {$dataNewStore['id']} - dataNewSeller=" . json_encode($dataNewStore) . ' - order='.json_encode($order));


            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                $this->log_data('batch', $log_name, "Erro para executar as queries de troca de seller. PEDIDO={$order['id']} - dataNewSeller=" . json_encode($dataNewStore), "E");
                continue;
            }

            $this->db->trans_commit();
        }
    }

    /**
     * Recupera a fabrica para qual o pedido será enviado
     *
     * @param   int         $order      Código do pedido (orders.id)
     * @param   string      $zipCode    CEP do cliente final (orders.customer_address_zip)
     * @param   string      $city       Cidade do cliente final (orders.customer_address_city)
     * @param   string      $state      Estado do cliente final (orders.customer_address_uf)
     * @return  array|false             Retorna a fabrica e cnpj para qual o pedido será enviado, caso ocorra algum problema, retornará false
     */
    private function getNewSeller(int $order, string $zipCode, string $city, string $state)
    {
        // troca espaços por '%20'
        $city   = str_replace(' ', '%20', $city);

        $url = 'https://apimid.ortobom.com.br/api/v1/obterfabricaareaentrega';
        $url .= "?CEP={$zipCode}";
        $url .= "&Cidade={$city}";
        $url .= "&Estado={$state}";

        $response = $this->restfulapi->sendRest($url, array("Authorization: Bearer {$this->accessToken}"));

        $responseDecode = json_decode($response['content']);

        if ($response['httpcode'] != 200 || !isset($responseDecode->data->Fabrica) || !isset($responseDecode->data->CNPJ)) {
            $this->log_data(
                'batch',
                __CLASS__.'/'.__FUNCTION__,
                "Não foi possível encontrar uma loja para o pedido {$order}\n
                URL={$url}\n
                HEADER=Authorization: Bearer {$this->accessToken}\n
                HTTP_CODE={$response['httpcode']}\n
                RESPONSE={$response['content']}",
                "E"
            );
            return false;
        }

        return [
            'factory'   => $responseDecode->data->Fabrica,
            'cnpj'      => $responseDecode->data->CNPJ
        ];
    }

    /**
     * Cria incidência no pedido
     *
     * @param array   $order    Dados do pedidos (orders)
     * @param string  $reason   Motivo da incidência
     */
    private function createIncidence(array $order, string $reason = "Não foi encontrado seller para o pedido.")
    {
        $log_name = $this->router->fetch_class() . '/' . __FUNCTION__;

        echo "Criou incidencia para o pedido {$order['id']}\n";

        $comment    = "ADD incidencia: {$reason}";
        $arrComment = array();

        $dataOrder = $this->model_orders->getOrdersData(0, $order['id']);

        if($dataOrder['comments_adm'])
            $arrComment = json_decode($dataOrder['comments_adm']);

        array_push($arrComment, array(
            'order_id'  => $order['id'],
            'comment'   => $comment,
            'user_id'   => 1,
            'user_name' => 'admin_batch',
            'date'      => date('Y-m-d H:i:s')
        ));

        $sendComment = json_encode($arrComment);

        $this->model_orders->createCommentOrderInProgress($sendComment, $order['id']);
        $this->model_orders->updateOrderIncidence($order['id'], 1, $reason);
        $this->log_data('batch', $log_name, "Criou incidencia para o pedido {$order['id']} - order=".json_encode($order), "I");

    }

    /**
     * Verifica se o seller vende o produto e se pode atender pela quantidade em estoque
     *
     * @param   array   $items  Itens do pedido (orders_item)
     * @param   int     $store  Código da loja (stores.id)
     * @return  bool
     */
    private function verifyStockStore(array $items, int $store): bool
    {
        foreach ($items as $iten) {
            $qtyItemPrd   = $iten['qty'];
            $prdCatalogId = $iten['product_catalog_id'];
            $variant      = $iten['variant'];

            $prd        = $this->model_products_catalog->getProductByProductCatalogIdAndStoreId($prdCatalogId, $store);
            $qtyNewPrd  = $prd['qty'];

            // é variação, recupero o estoque dela
            if (!empty($prd['has_variants'])) {
                $rowVar = $this->model_products->getVariants($prd['id'], $variant);
                $qtyNewPrd = $rowVar['qty'];
                echo "É variação: {$variant}, pegar estoque da variação.\n";
            }

            // não encontrou o produto ou o estoque não é suficiente
            if (!$prd || $qtyNewPrd < $qtyItemPrd) {
                echo "store {$store} não pode vender o item do catálogo:{$prdCatalogId}, id_prod:{$prd['id']} \n";
                return false;
            }

            echo "store {$store} pode vender o item do catálogo:{$prdCatalogId}, id_prod:{$prd['id']} \n";
        }

        return true;
    }
}