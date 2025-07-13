<?php
require APPPATH . "controllers/BatchC/GenericBatch.php";

class GeracaoConciliacoesSellerCenterBatch extends GenericBatch
{

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

        //Models
        $this->load->model('model_gateway');
        $this->load->model('model_transfer');
        $this->load->model('model_banks');
        $this->load->model('model_conciliation');
        $this->load->model('model_stores');
        $this->load->model('model_company');

        $this->load->model('model_orders');
        $this->load->model('model_conciliacao_sellercenter');

    }

    public function run(): void
    {

        /**
         * @var Model_conciliacao_sellercenter $modelConciliationSellerCenter
         */
        $modelConciliationSellerCenter = $this->model_conciliacao_sellercenter;

        //Busca todos os repasses
        $transfers = $this->model_transfer->getAll();

        foreach ($transfers as $transfer) {

            $orders = $this->model_orders->getOrdersByStoreId($transfer['store_id'], 0, 3);

            if ($orders) {

                foreach ($orders as $order) {

                    $modelConciliationSellerCenter->insert(
                        $transfer['lote'],
                        dateNow()->format(DATETIME_INTERNATIONAL),
                        $transfer['store_id'],
                        $transfer['name'],
                        $order['id'],
                        $order['numero_marketplace'],
                        $order['date_updated'],
                        $order['data_entrega'],
                        dateNow()->format('d'),
                        'Conciliação Ciclo',
                        $order['total_order'],
                        $order['total_order'],
                        '0.0',
                        '1',
                        '2',
                        '3',
                        '4',
                        '5',
                        $transfer['valor_seller'],
                        $transfer['valor_seller'],
                        'Conecta Lá',
                        '1',
                        'Observação teste'
                    );

                }

            }

        }

    }

}
