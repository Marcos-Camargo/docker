<?php

// REGRAS PARA O PREENCHIMENTO DA GERAÇÃO DE PEDIDOS

// É possível gerar mais de um pedido ao mesmo tempo;
// Caso sejam pedidos de lojas diferentes, devem ser inseridos arrays para cada loja em $ordersParameters;
// Caso sejam pedidos da mesma loja, basta informar a quantidade de pedidos dentro de um mesmo array;
// Apenas preencher os campos dentro do array e executar o programa;

class CreateTestOrders extends BatchBackground_Controller
{
    private const ACCEPTED_STATUS = [
        '1', '2', '3', '4', '5', '6', 
        '50', '51', '52', '53', '54', '55', '56', '57',
        '60',
        '95', '96', '97', '98', '99',
        '101'
    ];

    // $orderParameters recebe um array porque podem ser gerados pedidos para lojas diferentes
    private $ordersParameters = [
        [ // pedido para a loja 17, por exemplo;
            'store'            => 10,  // loja que será criado o pedido
            'ordersQuantity'   => 7,   // quantidade de pedidos para essa determinada loja
            'productsQuantity' => 1,   // quantidade de produtos (itens) que cada pedido deverá ter
            'ordersStatus'     => '2;3;51;57;96;3;1', // informar o número dos status dos pedidos separados por ponto-e-vírgula (caso seja informado uma quantidade de status menor que a quantidade de pedidos, os demais pedidos serão gerados com status 1)
            'storeData'        => [],  // NÃO ALTERAR!!! - receberá os dados da loja direto do banco
            'productData'      => []   // NÃO ALTERAR!!! - receberá os dados do produto da loja direto do banco
        ], /*
        [ // pedido para a loja 25;
            'store'            => 25,
            'ordersQuantity'   => 1,
            'productsQuantity' => 1,
            'ordersStatus'     => '3',
            'storeData'        => [],
            'productData'      => []
        ] */
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->model("model_stores");
        $this->load->model("model_products");
        $this->load->model("model_clients");
    }

    public function run()
    {
        $this->validations();

        foreach ($this->ordersParameters as $orderParameters) {
            $allStatus = explode(';', $orderParameters['ordersStatus']);
            for ($c = 0; $c < $orderParameters['ordersQuantity']; $c++) {
                $status = isset($allStatus[$c]) ? $allStatus[$c] : 1;
                $this->createOrder($orderParameters, $status);
            }
        }

        echo 'Pedido(s) criado(s) com sucesso!';
    }

    private function validations()
    {
        $this->checkEnvironment();
        $this->emptyParameters();
        $this->validateStatus();
        $this->storesExists();
        $this->thereIsProductForTheStores();
    }

    private function checkEnvironment()
    {
        if (ENVIRONMENT == 'production') {
            echo 'Não é permitido criar pedidos no ambiente de produção!!!';
            exit;
        }
    }

    private function emptyParameters()
    {
        if (empty($this->ordersParameters)) {
            echo 'Favor preencher os parâmetros para a criação do(s) pedido(s)';
            exit;
        }
    }

    private function validateStatus()
    {
        foreach ($this->ordersParameters as $key => $orderParameters) {
            $status = explode(';', $orderParameters['ordersStatus']);
            foreach ($status as $statu) {
                if (!in_array($statu, self::ACCEPTED_STATUS)) {
                    echo "Não foi possível criar pedido com status $statu";
                    exit;
                }
            }
        }
    }

    private function storesExists()
    {
        foreach ($this->ordersParameters as $key => $orderParameters) {
            $this->ordersParameters[$key]['storeData'] = $this->model_stores->getStoresData($orderParameters['store']);
            if (!$this->ordersParameters[$key]['storeData']) {
                echo "Loja $orderParameters[store] não foi encontrada na base de dados";
                exit;
            }
        }
    }

    private function thereIsProductForTheStores()
    {
        foreach ($this->ordersParameters as $key => $orderParameters) {
            $this->ordersParameters[$key]['productData'] = $this->model_products->getProductsByStore($orderParameters['store']);
            if (!$this->ordersParameters[$key]['productData']) {
                echo "Não foi encontrado produto para a loja $orderParameters[store]";
                exit;
            }
            if (count($this->ordersParameters[$key]['productData']) < $orderParameters['productsQuantity']) {
                echo "A quantidade encontrada de produtos para a loja $orderParameters[store] é menor do que a desejada para constar no pedido";
                exit;
            }
        }
    }

    private function createOrder($orderData, $status)
    {
        $customerData = null;
        while (!$customerData) {
            $customerData = $this->model_clients->getClientsData(rand(1, 50));
        }

        $order = array(
            'bill_no'                => 'BILPR-'.strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4)),
            'numero_marketplace'     => rand(11111111111, 99999999999),
            'customer_id'            => $customerData['id'],
            'customer_name'          => $customerData['customer_name'],
            'customer_address'       => $customerData['customer_address'],
            'customer_address_num'   => $customerData['addr_num'],
            'customer_address_compl' => '',
            'customer_address_neigh' => $customerData['addr_neigh'],
            'customer_address_city'  => $customerData['addr_city'],
            'customer_address_uf'    => $customerData['addr_uf'],
            'customer_address_zip'   => $customerData['zipcode'],
            'customer_phone'         => $customerData['phone_1'],
            'date_time'              => date('Y-m-d'),
            'total_order'            => 0,
            'discount'               => 0,
            'net_amount'             => 0,
            'total_ship'             => 0,
            'gross_amount'           => 0,
            'service_charge_rate'    => 0,
            'service_charge_freight_value'    => 0,
            'service_charge'         => 0,
            'vat_charge'             => 0,
            'paid_status'            => $status,
            'user_id'                => 1,
            'company_id'             => $orderData['storeData']['company_id'],
            'origin'                 => 'BatchTeste',
            'store_id'               => $orderData['store']
        );

        $insertOrder = $this->db->insert('orders', $order);
        $order_id    = $this->db->insert_id();

        $amount = 0;

        for ($c = 0; $c < $orderData['productsQuantity']; $c++) {
            $productData = array_rand($orderData['productData']);
            $amount += $this->createItem($orderData['productData'][$productData], $order_id);
        }

        $orderValues = [
            'total_order'  => $amount,
            'net_amount'   => $amount,
            'gross_amount' => $amount
        ];

        $this->db->where('id', $order_id);
        $update = $this->db->update('orders', $orderValues);

        // $this->insertToQueue($order, $order_id);
    }

    private function createItem($productData, $order_id)
    {
        $item = [
            'order_id'     => $order_id,
            'product_id'   => $productData['id'],
            'sku'          => $productData['sku'],
            'name'         => $productData['name'],
            'qty'          => 1,
            'rate'         => $productData['price'],
            'amount'       => $productData['price'],
            'discount'     => 0, 
            'un'           => 'Un',
            'pesobruto'    => $productData['peso_bruto'],
            'largura'      => $productData['largura'],
            'altura'       => $productData['altura'],
            'profundidade' => $productData['profundidade'],
            'unmedida'     => 'cm',
            'company_id'   => $productData['company_id'],
            'store_id'     => $productData['store_id']
        ];

        $this->db->insert('orders_item', $item);

        return $item['amount'];
    }

    // private function insertToQueue($order, $order_id)
    // {
    //     $queue = [
    //         'order_id' => $order_id,
    //         'company_id' => $order['company_id'],
    //         'store_id' => $order['store_id'],
    //         'paid_status' => $order['paid_status'],
    //         'new_order' => 1
    //     ];

    //     $this->db->insert('orders_to_integration', $queue);
    // }







}






