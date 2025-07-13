<?php
/** @noinspection PhpUndefinedFieldInspection */

require APPPATH . "controllers/BatchC/GenericBatch.php";

/**
 * Class ConciliationInstallmentsBatch
 */
class ConciliationInstallmentsBatch extends GenericBatch
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
        $this->load->model('model_settings');
        $this->load->model('model_conciliation');
        $this->load->model('model_orders');
        $this->load->model('model_billet');

    }

    /**
     * @param null $id
     * @param null $params
     */
    public function run($id = null,$date_start = null): void
    {

        echo "Starting Job" . PHP_EOL;

        $this->startJob(__FUNCTION__ , $id);

        if($date_start == null || $date_start == 'null'){
            $date_start = null;
        }

        $setting_api_comission = $this->model_settings->getSettingDatabyName('api_comission');
        $setting_api_comission = $setting_api_comission['status'];

        $ordersWithoutPaymentDate = $this->model_orders->findOrdersWithoutOrdersPaymentDate($date_start);

        $inputs = [];
        $inputs['hdnLote'] = date('YmdHis') . rand(1, 1000000);

        foreach ($ordersWithoutPaymentDate as $order) {

            $result = $this->model_orders->runSelectDataPagamentoMarketplaceByOrderId($order['id']);

            echo "Running Order id: {$order['id']}, payment date: {$result['data_pagamento_marketplace']}" . PHP_EOL;

            if (!isset($result['data_pagamento_marketplace']) || !$result['data_pagamento_marketplace']){
                continue;
            }

            $date = DateTime::createFromFormat('d/m/Y', $result['data_pagamento_marketplace']);

            $inputs['id_pedido'] = $order['id'];
            $inputs['txt_ano_mes'] = $date->format('m-Y');
            $inputs['data_ciclo'] = $result['data_pagamento_marketplace'];

            $this->model_billet->generateInstallmentsByInputs(
                $inputs,
                $setting_api_comission,
                null,
                true
            );

        }

        $ordersWithoutPaymentDate = $this->model_orders->findOrdersWithoutOrdersCancelDate($date_start);

        foreach ($ordersWithoutPaymentDate as $order) {

            $result = $this->model_orders->runSelectDataCancelamentoMarketplaceByOrderId($order['id']);

            echo "Running Order id: {$order['id']}, cancel date: {$result['data_cancelamento_marketplace']}" . PHP_EOL;

            if (!isset($result['data_cancelamento_marketplace']) || !$result['data_cancelamento_marketplace']){
                continue;
            }

            $date = DateTime::createFromFormat('d/m/Y', $result['data_cancelamento_marketplace']);

            $inputs['id_pedido'] = $order['id'];
            $inputs['txt_ano_mes'] = $date->format('m-Y');
            $inputs['data_ciclo'] = $result['data_cancelamento_marketplace'];

            $this->model_billet->generateInstallmentsByInputs(
                $inputs,
                $setting_api_comission,
                null,
                true
            );

        }


        // Pegar todos os pedidos com data de ciclo calculada mas que não existe na installments

        echo "Job Ended" . PHP_EOL;

        $this->endJob();
        
    }

    public function runciclofiscal($id = null,$date_start = null): void
    {

        echo "Starting Job" . PHP_EOL;

        $this->startJob(__FUNCTION__ , $id);

        if($date_start == null || $date_start == 'null'){
            $date_start = null;
        }

        $setting_api_comission = $this->model_settings->getSettingDatabyName('api_comission');
        $setting_api_comission = $setting_api_comission['status'];

        $ordersWithoutPaymentDate = $this->model_orders->findOrdersWithoutOrdersPaymentDateFechamentoFiscal($date_start);

        $inputs = [];
        $inputs['hdnLote'] = date('YmdHis') . rand(1, 1000000);

        foreach ($ordersWithoutPaymentDate as $order) {

            $result = $this->model_orders->runSelectDataFechamentoFiscalByOrderId($order['id']);

            echo "Running Order id: {$order['id']}, payment date: {$result['data_fechamento_fiscal']}" . PHP_EOL;

            if (!isset($result['data_fechamento_fiscal']) || !$result['data_fechamento_fiscal']){
                continue;
            }
            
            
            $date = DateTime::createFromFormat('d/m/Y', $result['data_fechamento_fiscal']);

            $inputs['id_pedido'] = $order['id'];
            $inputs['txt_ano_mes'] = $date->format('m-Y');
            $inputs['data_ciclo'] = $result['data_fechamento_fiscal'];

            $this->model_billet->generateInstallmentsByInputsFiscal(
                $inputs,
                $setting_api_comission
            );

        }

        $ordersWithoutPaymentDate = $this->model_orders->findOrdersWithoutOrdersCancelDateFiscal($date_start);

        foreach ($ordersWithoutPaymentDate as $order) {

            $result = $this->model_orders->runSelectDataFechamentoFiscalCancelamentoByOrderId($order['id']);

            echo "Running Order id: {$order['id']}, payment date: {$result['data_fechamento_fiscal_cancelamento']}" . PHP_EOL;

            if (!isset($result['data_fechamento_fiscal_cancelamento']) || !$result['data_fechamento_fiscal_cancelamento']){
                continue;
            }
            
            
            $date = DateTime::createFromFormat('d/m/Y', $result['data_fechamento_fiscal_cancelamento']);

            $inputs['id_pedido'] = $order['id'];
            $inputs['txt_ano_mes'] = $date->format('m-Y');
            $inputs['data_ciclo'] = $result['data_fechamento_fiscal_cancelamento'];

            $this->model_billet->generateInstallmentsByInputsFiscal(
                $inputs,
                $setting_api_comission
            );

        }

        echo "Job Ended" . PHP_EOL;

        $this->endJob();
        
    }

}
