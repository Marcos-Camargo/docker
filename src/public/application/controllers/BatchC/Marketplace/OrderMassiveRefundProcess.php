<?php

use App\Libraries\Enum\OrderRefundMassiveStatus;

/**
 * @property Model_orders_to_process_commission $model_orders_to_process_commission
 * @property Model_orders $model_orders
 * @property Model_integrations $model_integrations
 * @property Model_products $model_products
 * @property Model_commissioning_products $model_commissioning_products
 * @property Model_commissioning_trade_policies $model_commissioning_trade_policies
 * @property Model_commissioning_categories $model_commissioning_categories
 * @property Model_commissioning_brands $model_commissioning_brands
 * @property Model_commissioning_orders_items $model_commissioning_orders_items
 * @property Model_commissioning_stores $model_commissioning_stores
 * @property Model_product_return $model_product_return
 * @property Model_reports $model_reports
 * @property Model_nfes $model_nfes
 * @property Model_stores $model_stores
 * @property Model_frete_ocorrencias $model_frete_ocorrencias
 * @property Model_clients $model_clients
 * @property Model_company $model_company
 * @property Model_freights $model_freights
 * @property Model_shipping_company $model_shipping_company
 * @property Model_settings $model_settings
 * @property Model_users $model_users
 * @property Model_attributes $model_attributes
 * @property Model_orders_with_problem $model_orders_with_problem
 * @property Model_products_catalog $model_products_catalog
 * @property Model_requests_cancel_order $model_requests_cancel_order
 * @property Model_orders_to_integration $model_orders_to_integration
 * @property Model_orders_item $model_orders_item
 * @property Model_notification_popup $model_notification_popup
 * @property Model_phases $model_phases
 * @property Model_order_refund_massive $model_order_refund_massive
 * @property Model_legal_panel $model_legal_panel
 *
 */
class OrderMassiveRefundProcess extends BatchBackground_Controller
{
    public function __construct()
    {
        parent::__construct();
        $logged_in_sess = array(
            'id' => 1,
            'username' => 'batch',
            'email' => 'batch@conectala.com.br',
            'usercomp' => 1,
            'userstore' => 0,
            'logged_in' => true
        );
        $this->session->set_userdata($logged_in_sess);


        $this->load->model('model_orders');
        $this->load->model('model_products');
        $this->load->model('model_product_return');
        $this->load->model('model_reports');
        $this->load->model('model_nfes');
        $this->load->model('model_stores');
        $this->load->model('model_frete_ocorrencias');
        $this->load->model('model_clients');
        $this->load->model('model_company');
        $this->load->model('model_freights');
        $this->load->model('model_shipping_company');
        $this->load->model('model_settings');
        $this->load->model('model_users');
        $this->load->model('model_attributes');
        $this->load->model('model_integrations');
        $this->load->model('model_orders_with_problem');
        $this->load->model('model_products_catalog');
        $this->load->model('model_requests_cancel_order');
        $this->load->model('model_orders_to_integration');
        $this->load->model('model_orders_item');
        $this->load->model('model_notification_popup');
        $this->load->model('model_phases');
        $this->load->model('model_order_refund_massive');
        $this->load->model('model_legal_panel');
        $this->load->model('model_legal_panel_fiscal');
    }

    function run($id = null, $params = null)
    {
        /* inicia o job */
        $this->setIdJob($id);
        $log_name = __CLASS__.'/'.__FUNCTION__;

        $modulePath = (str_replace("BatchC/", '', $this->router->directory)).__CLASS__;
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
            echo "Já tem um job rodando!\n";
            return;
        }
        $this->log_data('batch', $log_name, 'start '.trim($id." ".$params));

        $this->process();

        $this->log_data('batch', $log_name, 'finish');
        $this->gravaFimJob();
    }

    public function process()
    {
        $last_id = 0;

        $csvKey = 'ID do Pedido';
        $maximumDaysRefund = $this->model_settings->getValueIfAtiveByName('maximum_days_to_refund_comission');

        while ($data = $this->model_order_refund_massive->getNextRow($last_id)) {
            $last_id = $data['id'];
            $user = $this->model_users->getUserByEmailOrLogin($data['user']);

            $this->model_order_refund_massive->setStatus($last_id, OrderRefundMassiveStatus::PROCESSING);

            $return = [];
            $return['general'] = '';
            $return['itens'] = [];
            $hasErrors = false;
            try {

                $rows = readTempCsv($data['orders_file'], 0, [$csvKey]);

                if ($rows) {

                    foreach ($rows as $row) {

                        //Realizando todas as validações necessárias de cada pedido para garantir que ele pode ser realizado o extorno
                        $order = $this->model_orders->getOrdersData(0, $row[$csvKey]);

                        //pedido existe?
                        if (!$order) {
                            $return['itens'][$row[$csvKey]] = "Pedido não encontrado";
                            continue;
                        }

                        //pedido já estornado?
                        if ($this->model_legal_panel->isNotificationOrderComissionRefundAlreadyRegistered($row[$csvKey])) {
                            $return['itens'][$row[$csvKey]] = "Pedido já foi estornado";
                            continue;
                        }
                        //pedido com o status diferente de cancelado
                        if (!$order['date_cancel']) {
                            $return['itens'][$row[$csvKey]] = "Pedido não está cancelado";
                            continue;
                        }

                        //pedido com prazo de cancelamento superior ao prazo cadastrado na plataforma
                        $daysPassed = abs(dateDiffDays(new DateTime($order['date_cancel']), new DateTime()));
                        if ($daysPassed > $maximumDaysRefund) {
                            $return['itens'][$row[$csvKey]] = "Pedido já passou $daysPassed dias do cancelamento, do limite de $maximumDaysRefund para o reembolso cadastrado na plataforma.";
                            continue;
                        }

                        //Calculando a comissão
                        $retornoCalculo = $this->model_orders->getDetalheTaxas( $row[$csvKey] );
                        $valorComissaoCobrada = 0;
                        $valorComissaoCobradaFiscal = 0;
                        foreach($retornoCalculo as $conta){
                            if($this->model_settings->getValueIfAtiveByName('cancellation_commission_calculate_campaign')){
                                $valorComissaoCobrada += ($conta['comissao_produto'] + $conta['comissao_frete'] + $conta['comissao_campanha'])  ;
                            }else{
                                $valorComissaoCobrada += ($conta['comissao_produto'] + $conta['comissao_frete'] + $conta['comissao_campanha']) - ($conta['reembolso_mkt']) ;
                            }
                            
                            $valorComissaoCobradaFiscal += ($conta['comissao_produto'] + $conta['comissao_frete'] + $conta['comissao_campanha']) - ($conta['reembolso_mkt']) ; 

                        }
                        $valorComissaoCobrada = round($valorComissaoCobrada *-1,2);
                        $valorComissaoCobradaFiscal = round($valorComissaoCobradaFiscal *-1,2);

                        //pedido cancelado sem cobrança de comissão
                        if (!$this->model_orders->wasOrderChargedComission($row[$csvKey])) {
                            $return['itens'][$row[$csvKey]] = "Pedido não possui cobrança de comissão.";
                            continue;
                        }

                        //Realiza a criação do painel jurídico com o valor da comissão a ser cobrada
                        $dataJuridico = [
                            'notification_type' => 'order',
                            'orders_id' => $row[$csvKey],
                            'notification_id' => "Estorno de comissão Cobrada",
                            'notification_title' => "Estorno de comissão Cobrada",
                            'status' => "Chamado Aberto",
                            'description' => "Estorno de comissão Cobrada",
                            'balance_paid' => $valorComissaoCobrada,
                            'balance_debit' => $valorComissaoCobrada,
                            'attachment' => null,
                            'creation_date' => date_create()->format(DATETIME_INTERNATIONAL),
                            'update_date' => date_create()->format(DATETIME_INTERNATIONAL),
                            'accountable_opening' => $data['user'],
                            'accountable_update' => $data['user'],
                        ];

                        $this->model_legal_panel->create($dataJuridico);

                        //Realiza a criação do painel jurídico com o valor da comissão a ser cobrada
                        $dataJuridicoFiscal = [
                            'notification_type' => 'order',
                            'orders_id' => $row[$csvKey],
                            'notification_id' => "Estorno de comissão Cobrada",
                            'notification_title' => "Estorno de comissão Cobrada",
                            'status' => "Chamado Aberto",
                            'description' => "Estorno de comissão Cobrada",
                            'balance_paid' => $valorComissaoCobradaFiscal,
                            'balance_debit' => $valorComissaoCobradaFiscal,
                            'attachment' => null,
                            'creation_date' => date_create()->format(DATETIME_INTERNATIONAL),
                            'update_date' => date_create()->format(DATETIME_INTERNATIONAL),
                            'accountable_opening' => $data['user'],
                            'accountable_update' => $data['user'],
                        ];

                        $this->model_legal_panel_fiscal->create($dataJuridicoFiscal);
                        $idLegalPanelCreated = $this->model_legal_panel->createLegalPanelLastId();

                        $dataOrdersCommisionCharges = [
                            'order_id' => $row[$csvKey],
                            'observation' => 'Cancelamento Massivo',
                            'file' => $data['justify_file'],
                            'users_id' => $user['id'],
                            'legal_panel_id' => $idLegalPanelCreated,
                        ];

                        //Realiza o insert na OrdersCommisionCharges
                        $this->model_legal_panel->insertOrdersCommisionCharges($dataOrdersCommisionCharges);

                    }

                } else {
                    $return[] = 'Nenhum registro encontrado na planilha';
                }

                if (count($return['itens']) > 0) {
                    $this->model_order_refund_massive->setStatus($last_id, OrderRefundMassiveStatus::ERROR, $return);
                } else {
                    $this->model_order_refund_massive->setStatus($last_id, OrderRefundMassiveStatus::SUCCESS);
                }

                //enviar email de notificação
                $subject = "Estorno de pedido Massivo id {$last_id} concluído";
                if ($return) {
                    $subject .= " com erro";
                } else {
                    $subject .= " com sucesso";
                }
                $body = "Clique no link a seguir para acessar os detalhes: ".base_url('ProductsReturn/returnMassiveDetails/'.$last_id);
                $this->sendEmailMarketing($data['user'], $subject, $body);

            } catch (Throwable $exception) {
                $return['general'] = $exception->getMessage();
            }

        }
    }

}