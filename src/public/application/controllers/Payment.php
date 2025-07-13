<?php
/*
SW Serviços de Informática 2019

Controller de Recebimentos

*/
defined('BASEPATH') or exit('No direct script access allowed');
ini_set("memory_limit", "1024M");

use App\Libraries\Enum\PaymentGatewayEnum;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * @property Model_billet model_billet
 * @property Model_payment $model_payment
 * @property Model_iugu $model_iugu
 * @property Model_settings $model_settings
 * @property Model_stores $model_stores
 * @property Model_legal_panel $model_legal_panel
 * @property Model_banks $model_banks
 * @property Model_payment_gateway_store_logs $model_payment_gateway_store_logs
 * @property Model_gateway $model_gateway
 * @property Model_gateway_settings $model_gateway_settings
 * @property Model_orders $model_orders
 * @property Model_order_payment_transactions $model_order_payment_transactions
 * @property Model_users $model_users
 */
class Payment extends Admin_Controller
{
    public $negociacao_marketplace_campanha;
    public $gateway_id;
    public $api_url;
    public $app_token;
    public $app_account;
    public $balance_transfers_valid_updated_minutes;
    public $conciliation_data = [];
    public $rows_by_store = [];
    public $all_cycles = [];
    public $gateways_with_balance = [2, 4];
    public $allow_transfer_between_accounts = '';
    public $cost_transfer_tax_pagarme = '';
    public $change_date_fiscal_panel = '';

    public function __construct()
    {
        parent::__construct();

        $this->not_logged_in();

        $this->data['page_title'] = 'Parâmetros de Boletos';

        $this->load->model('model_payment');
        $this->load->model('model_billet');
        $this->load->model('model_iugu');
        $this->load->model('model_settings');
        $this->load->model('model_stores');
        $this->load->model('model_legal_panel');
        $this->load->model('model_banks');
        $this->load->model('model_payment_gateway_store_logs');
        $this->load->model('model_gateway');
        $this->load->model('model_gateway_settings');
        $this->load->model('model_orders');
        $this->load->model('model_order_payment_transactions');
        $this->load->model('model_users');

        $usercomp = $this->session->userdata('usercomp');
        $this->data['usercomp'] = $usercomp;
        $more = " company_id = ".$usercomp;

        $this->negociacao_marketplace_campanha          = $this->model_settings->getValueIfAtiveByName('negociacao_marketplace_campanha');
        $this->gateway_id                               = $this->model_settings->getValueIfAtiveByName('payment_gateway_id');
        $this->balance_transfers_valid_updated_minutes  = intVal($this->model_settings->getValueIfAtiveByName('balance_transfers_valid_updated_minutes'));
        $this->allow_transfer_between_accounts  		= intVal($this->model_settings->getValueIfAtiveByName('allow_transfer_between_accounts'));
        $this->change_date_fiscal_panel                 = $this->model_settings->getStatusbyName('change_date_fiscal_panel');

        $api_settings = $this->model_gateway_settings->getSettings($this->gateway_id);

        if (!empty($api_settings) && is_array($api_settings))
        {
            foreach ($api_settings as $key => $setting)
            {
                $this->{$setting['name']} = $setting['value'];
            }
        }

    }

    /*
    * It only redirects to the manage order page
    */
    public function index()
    {
        if(!in_array('viewPayment', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->data['page_title'] = 'Administrar os boletos';
        $this->render_template('payment/list', $this->data);
    }

    public function list()
    {
        if (!in_array('viewPayment', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->data['page_title'] = 'Conulta de Boletos';

        if (in_array('createPayment', $this->permission)) {
            $this->data['categs'] = "";
            $this->data['mktPlaces'] = "";
        }

        $this->render_template('payment/list', $this->data);
    }


    public function balanceTransfers()
    {
        if (!in_array('balanceTransfers', $this->permission))
            redirect('dashboard', 'refresh');

        $get = $_GET;

        if ($get)
        {
            $get = base64_decode($get['a']);
            $get = json_decode($get, true);
        }

        $this->data['page_title'] = $this->lang->line('application_balances_transfers');

        $filter_status = [
            'p' => $this->lang->line('payment_balance_transfers_status_pending'),
            't' => $this->lang->line('payment_balance_transfers_status_transfered'),
            'r' => $this->lang->line('payment_balance_transfers_status_returned')
        ];

        $this->data['filter_status'] = $filter_status;

        $mktplace_data = $this->model_payment->getMktPlaceBalance($this->gateway_id);

        if (!$mktplace_data)
        {
            $mktplace_data = [
                'id' => 0,
                'gateway_id' => $this->gateway_id,
                'available' => 0,
                'future' => 0,
                'unavailable' => 0,
                'date_edit' => date('Y-m-d H:i:s',(strtotime("-2 weeks")))
            ];
        }

        $diff = abs(strtotime(date('Y-m-d H:i:s')) - strtotime($mktplace_data['date_edit']));

        $years          = floor($diff / (365*60*60*24));
        $months         = floor(($diff - $years * 365*60*60*24) / (30*60*60*24));
        $days           = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24));
        $hours          = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24 - $days*60*60*24)/ (60*60));
        $minutes        = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24 - $days*60*60*24 - $hours*60*60)/ 60);
        $seconds        = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24 - $days*60*60*24 - $hours*60*60 - $minutes*60)/ 60);
        $total_minutes  = floor($diff / 60);

        $last_update[]  = ($days > 0) ? $days.' '.$this->lang->line('application_Days') : null;
        $last_update[]  = ($hours > 0) ? $hours.' '.$this->lang->line('application_Hours') : null;
        $last_update[]  = ($minutes > 0) ? $minutes.' '.$this->lang->line('application_Minutes') : null;
        $last_update[]  = ($diff <= 60) ? $diff.' '.$this->lang->line('application_Seconds') : null;

        $last_update = array_filter($last_update);

        $this->data['last_update']                  = implode(', ', $last_update);
        $this->data['last_update_minutes']          = ($total_minutes > 0) ? $total_minutes : 0;
        $this->data['last_update_minutes_limit']    = $this->balance_transfers_valid_updated_minutes;

        $this->data['mktplace_balance']             = round(($mktplace_data['available'] / 100), 2);
        $this->data['mktplace_totalreturn']         = round(($this->model_payment->getMktPlaceTotalReturn($this->gateway_id) / 100), 2);

        $this->data['allowed_tranfers']		        = $this->allow_transfer_between_accounts;
        $this->data['chargeback_adjustments']       = (isset($get['cid']) && isset($get['stores'])) ? true : false;

        $total_boxes = ['total_missing' => 0];
        $total_boxes = ['returning' => 0];

        $filter_stores = [];

        $cycles_array = [];
        $total_status = ['p' => 0, 't' => 0, 'r' => 0];

        if(!isset($get['stores'])){ $get['stores'] = null; }
        if(!isset($get['cid'])){ $get['cid'] = null; }

        $pagarme_fee = 0;
        $pagarme_fee_seller = 0;

        if ($this->gateway_id == 2)
        {
            $this->load->library('PagarmeLibrary');

            $configurations = $this->pagarmelibrary->getConfigurations();
            $pagarme_fee = isset($configurations['pricing']['transfers']['doc']) ? floatVal($configurations['pricing']['transfers']['doc']) : floatVal($this->pagarmelibrary->getTransferCost());

            //se os parametros de custo nao estiveram ativos, custo é do seller, senão é do marketplace
            $charge_seller_tax_pagarme_active = $this->model_gateway_settings->getGatewaySettingByName($this->gateway_id, 'charge_seller_tax_pagarme');

            if ($charge_seller_tax_pagarme_active == 0) {
                $this->cost_transfer_tax_pagarme = $this->model_gateway_settings->getGatewaySettingByName($this->gateway_id, 'cost_transfer_tax_pagarme');

                if (floatVal($this->cost_transfer_tax_pagarme) > 0) {
                    $pagarme_fee_seller = floatVal(round($this->cost_transfer_tax_pagarme * 100));
                }

            } else {

                $pagarme_fee = 0;
                $pagarme_fee_seller = 0; //caso o parametro esteja inativo nao indicamos qot o seller paga, ele simplesmente paga full a taxa

            }

        }

        $grid_balance = $this->model_payment->getUnderscoredBalances($this->gateway_id, $get['cid'], $get['stores'], $pagarme_fee, $pagarme_fee_seller);

        if (!empty($grid_balance))
        {
            $banks = [];

            $banks_with_zero_fee = $this->model_gateway_settings->getGatewaySettingByName($this->gateway_id, 'banks_with_zero_fee');

            if ($banks_with_zero_fee)
            {
                $banks = explode(';', $banks_with_zero_fee);
                $banks = array_filter($banks);
            }

            foreach ($grid_balance as $k => $v)
            {
                $grid_balance[$k]['transfer_status'] = ucfirst($filter_status[$v['transfer_status']]);
                $grid_balance[$k]['transfer_status_flag'] = $v['transfer_status'];
                $filter_stores[$v['store_id']] = $v['name'];

                $data_ciclo = $v['data_ciclo'];

                if (false !== strpos($v['data_ciclo'], '-'))
                {
                    $date_array = explode('-', $v['data_ciclo']);
                    $data_ciclo = $date_array[2].'/'.$date_array[1].'/'.$date_array[0];
                }

                $cycles_array[] = $data_ciclo;
                $grid_balance[$k]['data_ciclo'] = $data_ciclo;
                $total_status[$v['transfer_status']]++;

                //removo a taxa caso esteja entre os bancos com taxa zero
                if (in_array($v['bank'], $banks))
                {
                    $grid_balance[$k]['pagarme_fee'] = 0;
                }
                else
                {
                    $grid_balance[$k]['pagarme_fee'] = round($v['pagarme_fee'], 2);
                }
            }
        }

        $this->data['pagarme_fee']        = $pagarme_fee;
        $this->data['pagarme_fee_seller'] = $pagarme_fee_seller;
        $this->data['filter_stores']      = $filter_stores;
        $this->data['cycles_filter']      = array_unique($cycles_array);
        $this->data['grid_balance']       = $grid_balance;
        $this->data['total_status']       = $total_status;
        //		$this->data['banks_with_zero_fee'] = (isset($this->banks_with_zero_fee)) ? array_filter(explode(';', $this->banks_with_zero_fee)) : [];

        $total_missing = 0;
        $missing_repeats = [];

        //gerando o valor total de repasse considerando que caso a mesma loja se apresente mais de 1 vez, o saldo só seja utilizado 1 vez
        foreach ($grid_balance as $k => $v)
        {
            if (!in_array($v['store_id'], $missing_repeats))
            {
                $missing_repeats[] = $v['store_id'];
                $total_missing += (floatVal($v['valor_seller_total']) - floatVal($v['available']));
            }
            else
            {
                $total_missing += floatVal($v['valor_seller_total']);
            }
        }

        $total_boxes['total_missing'] = round($total_missing, 2);
        $this->data['total_boxes'] = $total_boxes;

        $this->data['$allow_transfer_between_accounts'] = $this->model_gateway_settings->getGatewaySettingByName($this->gateway_id, 'allow_transfer_between_accounts');

        $this->render_template('payment/balancetransfers', $this->data);
    }


    public function balanceTransfersHistory()
    {
        if (!in_array('balanceTransfers', $this->permission))
            redirect('dashboard', 'refresh');

        $history_data = $this->model_payment->getBalanceTransferHistory($this->gateway_id);

        $this->data['history_data'] = $history_data;

        $this->render_template('payment/balancetransfershistory', $this->data);
    }


    public function getBalanceTransferHistory($store_id)
    {
        $pendency_data = $this->model_payment->getPendencyData($store_id);

        if ($pendency_data)
        {
            ob_clean();
            // header('Content-type: application/json');
            exit(json_encode($pendency_data));
        }

        ob_clean();
        echo 'ERROR';
    }


    public function updateBalances()
    {
        if (!in_array($this->gateway_id, $this->gateways_with_balance))
        {
            return false;
        }

        $library = 'PagarmeLibrary';

        if ($this->gateway_id == 4) //Moip
        {
            $library = 'Moiplibrary';
        }

        $this->load->library($library);

        try
        {
            return $this->{strtolower($library)}->gatewayUpdateBalance();
        }
        catch (Exception $e)
        {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }


    public function executeTransfers()
    {
        if (!in_array('balanceTransfers', $this->permission))
            redirect('dashboard', 'refresh');

        if (!in_array($this->gateway_id, $this->gateways_with_balance))
        {
            return false;
        }

        if ($this->gateway_id == 4)
        {
            return $this->executeTransfersMoip();
        }

        if ($this->gateway_id == PaymentGatewayEnum::PAGARME)
        {
            return $this->executeTransfersPagarme();
        }
    }

    public function markAsPaid()
    {

        if (!in_array('balanceTransfers', $this->permission)){
            redirect('dashboard', 'refresh');
        }

        if (!in_array($this->gateway_id, $this->gateways_with_balance)) {
            return false;
        }

        $this->load->model('model_repasse');

        $post = $this->postClean(NULL, TRUE);
        $user_data = $this->model_users->getUserById($this->session->userdata['id']);

        $this->model_repasse->markAsPaid($post['selected_id'], $post['conciliation_id'], $user_data['username']);

        echo json_encode(['success' => true]);

    }

    public function executeTransfersPagarme($chargeback_adjustments = null)
    {

        ob_start();

        $this->load->library('PagarmeLibrary');

        $post = $this->postClean(NULL, TRUE);

        $chargeback_adjustments = (isset($post['chargeback_adjustments'])) ? $post['chargeback_adjustments'] : 'false';
        $chargeback_adjustments = filter_var($chargeback_adjustments, FILTER_VALIDATE_BOOLEAN);

        $chargeback_adjustments_rep_row = (isset($post['chargeback_adjustments_rep_row'])) ? $post['chargeback_adjustments_rep_row'] : 'false';
//		$chargeback_adjustments_rep_row = filter_var($chargeback_adjustments_rep_row, FILTER_VALIDATE_BOOLEAN);

        $chargeback_stores = [];
        $conciliation_id = 0;

        if (!empty($post['selected_ids'])) {
            if ($post['selected_ids'] !== 'transfer') //caso em que nao existem transferencias e pula direto para o repasse
            {
                foreach ($post['selected_ids'] as $val) {
                    $result = json_decode($val, true);
                    $conciliation_id = $result['conciliation_id'];

                    if (is_array($result)) {
                        $amount = '' . ('' . $result['value'] * 100);
                        $store_id = intVal($result['store_id']);

                        if ($amount <= 0) {
                            if ($chargeback_adjustments) {
                                $chargeback_stores[] = $store_id;
                            }

                            continue;
                        }

                        if (!$chargeback_adjustments) {
                            $chargeback_stores[] = $store_id;
                        }

                        $subaccount = $this->model_gateway->getSubAccountByStoreId($store_id, PaymentGatewayEnum::PAGARME);
                        $receiver = $subaccount['gateway_account_id'];

                        if ($this->pagarme_subaccounts_api_version == "5" && !empty($subaccount['secondary_gateway_account_id'])) {
                            $receiver = $subaccount['secondary_gateway_account_id'];
                        }

                        if (!$receiver) {
                            $error = "Subconta não encontrada para store_id: {$store_id}" . PHP_EOL;
                            $this->log_data('batch', __FUNCTION__, $error, "E");
                            $this->model_payment_gateway_store_logs->insertLog($store_id, $this->gateway_id, $error);
                            continue;
                        }

                        $transfer = [
                            'id' => $store_id,
                            'conciliacao_id' => $result['conciliation_id']
                        ];

                        $primary_account = $this->primary_account;

                        if ($this->pagarme_subaccounts_api_version == "5") {
                            $primary_account = $this->primary_account_v5;
                        }

                        if ($this->pagarmelibrary->transferFundsLegal($transfer, $primary_account, $receiver, $amount)) {
                            if ($chargeback_adjustments) {
                                $chargeback_stores[] = $store_id;
                            }

                            $date_cycle = explode('-', $result['cycle']);
                            $date_cycle = $date_cycle[2] . '-' . $date_cycle[1] . '-' . $date_cycle[0];
                            $date_cycle_formatted = str_replace('-', '/', $result['cycle']);

                            $user_data = $this->model_users->getUserById($this->session->userdata['id']);

                            $result_array = [
                                'gateway_id' => $this->gateway_id,
                                'store_id' => $store_id,
                                'conciliation_id' => intVal($result['conciliation_id']),
                                'cycle_date' => $date_cycle_formatted,
                                'amount' => $amount,
                                'status' => 't',
                                'user_id' => intVal($user_data['id']),
                                'user_email' => $user_data['email']
                            ];

                            $pendency_id = $this->model_payment->createBalanceTransfer($result_array);
                        } else {
                            $error = "Não foi possível relizar a Transferencia do MktPlace para a conta " . $receiver . " de store_id = " . $store_id . " no valor de " . $result_array['amount'];
                            $this->log_data('batch', __FUNCTION__, $error, "E");
                        }
                    }
                }//foreach
            }

            if (!empty($chargeback_stores)) {
                $chargeback_object = '{"conciliation_id": "' . $conciliation_id . '", "stores": "' . implode(',', $chargeback_stores) . '", "rep_row": ' . $chargeback_adjustments_rep_row . '}';
            } else {
                $chargeback_object = null;
            }

            //apos as transferencias roda o repasse
            $this->pagarmelibrary->processTransfers(null, $chargeback_object, true);

            $resultado = ob_get_contents();
            ob_end_clean();

            $this->load->model('model_conciliation_transfers_log');
            $this->model_conciliation_transfers_log->create(['data' => $resultado]);

            ob_clean();
            echo json_encode(['success' => true]);

        } else {
            ob_clean();
            echo 'empty';
            return false;
        }
    }

    public function executeTransfersMoip($chargeback_adjustments = null)
    {
        $this->load->model('model_moip');
        $this->load->library('MoipLibrary');

        if ($chargeback_adjustments)
            return false;

        $post = $this->postClean(NULL,TRUE);

        if (!empty($post['selected_ids']))
        {
            if ($post['selected_ids'] !== 'transfer') //caso em que nao existem transferencias e pula direto para o repasse
            {
                foreach ($post['selected_ids'] as $val)
                {
                    $result = json_decode($val, true);

                    if (is_array($result))
                    {
                        $store_id = intVal($result['store_id']);
                        $date_cycle = explode('-', $result['cycle']);
                        $date_cycle = $date_cycle[2].'-'.$date_cycle[1].'-'.$date_cycle[0];
                        $date_cycle_formatted = str_replace('-', '/', $result['cycle']);

                        $user_data = $this->model_users->getUserById($this->session->userdata['id']);

                        $result_array = [
                            'gateway_id' => $this->gateway_id,
                            'store_id' => $store_id,
                            'conciliation_id' => intVal($result['conciliation_id']),
                            'cycle_date' => $date_cycle_formatted,
                            'amount' => $amount,
                            'status' => 't',
                            'user_id' => intVal($user_data['id']),
                            'user_email' => $user_data['email']
                        ];

                        $pendency_id = $this->model_payment->createBalanceTransfer($result_array);

                        //realiza a transferencia de fundos
                        $sender     = $this->app_account;
                        $receiver   = $this->model_moip->getStoreMoipData($store_id)['moip_id'];

                        //braun hack
                        if (ENVIRONMENT === 'development')
                        {
                            $result_array['amount'] = 1;
                            $this->model_moip->mockMethod();
                        }

                        $create_transfer = $this->moiplibrary->createTransfer(array('id' => $pendency_id), $sender, $receiver, abs($result_array['amount']), 'MOIP');

                        if (empty($create_transfer))
                        {
                            $error = "Não foi possível relizar a Transferencia do MktPlace para a conta ".$receiver." de store_id = ".$store_id." no valor de ".$result_array['amount'];

                            $this->log_data(
                                'batch',
                                __FUNCTION__,
                                $error,
                                "E"
                            );

                            $this->model_payment_gateway_store_logs->insertLog(
                                $store_id,
                                $this->gateway_id ,
                                $error
                            );

                            // return false;
                        }

                        //grava as transacoes com cartao para devolucao
                        $card_transactions = $this->model_payment->getModalBalanceOrdersData($store_id, $date_cycle_formatted);

                        if (!empty($card_transactions))
                        {
                            $creditcard_amount = 0;

                            if (is_array($pendency_id) && !empty($pendency_id))
                                $pendency_id = $pendency_id['id'];

                            foreach ($card_transactions as $transaction)
                            {
                                if ($transaction['payment_status'] == 'r')
                                {
                                    continue;
                                }

                                $amount_seller = floatVal($transaction['valor_pedido']) * 100;

                                $refund_array = [
                                    'pendency_id' => $pendency_id,
                                    'statement_id' => $transaction['store_id'],
                                    'order_number' => $transaction['numero_marketplace'],
                                    'amount_seller' => $amount_seller,
                                    'date_scheduled' => $transaction['date_scheduled_plus']
                                ];

                                //grava na agenda de pagamentos
                                if ($this->model_payment->saveScheduledRefunds($refund_array))
                                    $creditcard_amount += $amount_seller;
                            }

                            if ($creditcard_amount > 0)
                                $this->model_payment->updateCreditCardPendency($pendency_id, $creditcard_amount);
                        }
                        else
                        {
                            //caso nao tenha debitos em cartao significa que todos os outros tipos de pagto foram repassados e naada a devolver
                            $this->model_payment->updateCreditCardPendency($pendency_id, 0);
                            // $this->model_payment->endPendency($pendency_id);
                        }
                    }
                }
            }

            $this->load->library('MoipLibrary');
            return $this->moiplibrary->runPayments();

            // ob_clean();
            // echo 'ok';
            // return true;
        }
        else
        {
            ob_clean();
            echo 'empty';
            return false;
        }
    }


    public function getModalBalanceOrders()
    {
        $post = $this->postClean(NULL,TRUE);

        $modal_data_id = explode('-', $post['modal_data_id']);

        $store_id = $modal_data_id[1];
        $cycle    = str_replace('-', '/', $post['modal_data_cycle']);

        $orders_list = $this->model_payment->getModalBalanceOrdersData($store_id, $cycle);

        ob_clean();
        echo json_encode($orders_list);
    }


    public function getModalTransferHistory()
    {
        $pendencies = $this->model_payment->getPendenciesList();

        if (is_array($pendencies) && !empty($pendencies))
        {
            $stores = [];
            $users = [];
            $cycles = [];

            foreach ($pendencies as $key => $pendency)
            {
                $stores[$pendency['store_id']] = $pendency['store_name'];

                $users[$pendency['user_id']] = $pendency['firstname'].' '.$pendency['lastname'];

                $cycle = explode('/', $pendency['cycle_date']);
                $pendencies[$key]['cycle_straightdate'] = $cycle = $cycle[2].$cycle[1].$cycle[0];
                $cycles[$cycle] = $pendency['cycle_date'];

                $pendencies[$key]['amount'] = round(($pendency['amount'] / 100), 2);
                $pendencies[$key]['creditcard_amount'] = round(($pendency['creditcard_amount'] / 100), 2);
                $pendencies[$key]['total_paid'] = ($pendency['total_paid'] > 0) ? round(($pendency['total_paid'] / 100), 2) : 0;
            }
        }

        $json_result = [
            'pendencies' => $pendencies,
            'stores' => $stores,
            'users' => $users,
            'cycles' => $cycles
        ];

        ob_clean();
        echo json_encode($json_result);
    }


    public function paymentReports($ano_mes = null, $lote = null)
    {

        $this->data['page_title'] = 'Relatórios de Pagamentos';

        $cycle_data = $this->paymentReportCycle($ano_mes, $lote);

        $this->data['conciliation_data']    = $cycle_data['conciliation_data'];
        $this->data['all_cycles']           = $cycle_data['all_cycles'];
        $this->data['transfer_rows']        = $cycle_data['transfer_rows'];


        $mktplace_data = $this->model_payment->getMktPlaceBalance($this->gateway_id);

        if (!$mktplace_data)
        {
            $mktplace_data = [
                'id' => 0,
                'gateway_id' => $this->gateway_id,
                'available' => 0,
                'future' => 0,
                'unavailable' => 0,
                'date_edit' => date('Y-m-d H:i:s',(strtotime("-2 weeks")))
            ];
        }

        $diff = abs(strtotime(date('Y-m-d H:i:s')) - strtotime($mktplace_data['date_edit']));

        $years          = floor($diff / (365*60*60*24));
        $months         = floor(($diff - $years * 365*60*60*24) / (30*60*60*24));
        $days           = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24));
        $hours          = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24 - $days*60*60*24)/ (60*60));
        $minutes        = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24 - $days*60*60*24 - $hours*60*60)/ 60);
        $seconds        = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24 - $days*60*60*24 - $hours*60*60 - $minutes*60)/ 60);
        $total_minutes  = floor($diff / 60);

        $last_update[]  = ($days > 0) ? $days.' '.$this->lang->line('application_Days') : null;
        $last_update[]  = ($hours > 0) ? $hours.' '.$this->lang->line('application_Hours') : null;
        $last_update[]  = ($minutes > 0) ? $minutes.' '.$this->lang->line('application_Minutes') : null;
        $last_update[]  = ($diff <= 60) ? $diff.' '.$this->lang->line('application_Seconds') : null;

        $last_update = array_filter($last_update);

        $this->data['last_update']                  = implode(', ', $last_update);

        $this->render_template('payment/paymentreports', $this->data);
    }

    public function transferReports($ano_mes = null)
    {
        $this->data['page_title'] = $this->lang->line('transfer_report_page_title');

        $viable_transfers = $this->model_payment->getViableGatewayTransfers();

        if (!$ano_mes)
        {
            $ano_mes = $viable_transfers[0]['ano_mes'];
        }

        $this->data['current_ano_mes']  = $ano_mes;
        $this->data['current_mes_ano']  = date_format(date_create($ano_mes),"m/Y");
        $this->data['all_periods']      = $viable_transfers;

        $this->render_template('payment/transferreports', $this->data);
    }


    public function transferReportResults($ano_mes = null)
    {
        $result = ['data' => []];

        $results = $this->model_payment->getPeriodGatewayTransfers($ano_mes);

        if ($results)
        {
            foreach ($results as $key => $value)
            {
                $result['data'][$key] = array(
                    $value['id'],
                    $value['gateway_name'],
                    $value['sender'],
                    $value['receiver'],
                    $value['amount'],
                    $value['datetime']
                );
            }
        }
        else
        {
            $result = ['data' => [[
                '','','','','',''
            ]]];
        }

        ob_clean();
        echo json_encode($result);
    }


    public function paymentReportCycle($ano_mes = null, $lote = null)
    {
        $all_cycles     = [];
        $rows_by_store  = [];
        $liquid_amount  = [];
        $current_report = $this->model_payment->paymentReportByCycle($ano_mes, $lote, $this->gateway_id);

        if ($current_report) {

            $this->conciliation_data['id']                         = $current_report[0]['id'];
            $this->conciliation_data['ano_mes']                    = $current_report[0]['ano_mes'];
            $this->conciliation_data['lote']                       = $current_report[0]['lote'];
            $this->conciliation_data['data_inicio']                = $current_report[0]['data_inicio'];
            $this->conciliation_data['data_fim']                   = $current_report[0]['data_fim'];
            $this->conciliation_data['conciliation_status']        = $current_report[0]['status_conciliacao_text'];
            $this->conciliation_data['data_pagamento']             = $current_report[0]['data_pagamento'];
            $this->conciliation_data['conciliation_status_number'] = $current_report[0]['status_conciliacao'];

            $all_cycles = $this->model_payment->getPaymentCycles();

            error_reporting(E_ALL ^ E_NOTICE);

            foreach ($current_report as $report)
            {
                $rows_by_store[$report['store_id']]['store_name'] = $report['name'];
                $rows_by_store[$report['store_id']]['status']     = $report['status_text'];
                $rows_by_store[$report['store_id']]['balance']    = $report['balance'];
                $rows_by_store[$report['store_id']]['withdraw']   = $report['total_repasse'];
                $rows_by_store[$report['store_id']]['bank'] 	  = $report['bank'];

                if ($this->gateway_id == 2)	{
                    $banks = (isset($this->banks_with_zero_fee)) ? array_filter(explode(';', $this->banks_with_zero_fee)) : [];

                    if (!in_array($report['bank'], $banks) && floatVal($this->cost_transfer_tax_pagarme) == 0.0) {
                        $rows_by_store[$report['store_id']]['withdraw'] = $report['total_repasse'] + 3.67;
                    }
                }

                $index = '';

                if (!isset($liquid_amount[$report['store_id']]))
                {
                    $liquid_amount[$report['store_id']] = 0;
                }

                $liquid_amount[$report['store_id']] += floatVal($report['valor_seller']);

                if ($report['valor_seller'] < 0 && $report['refund'] !== NULL)
                {
                    $index = 'negative';
                }
                else if ($report['valor_seller'] < 0 && $report['refund'] == NULL)
                {
                    $index = 'legal_negative';
                }
                else if ($report['valor_seller'] > 0 && $report['refund'] == NULL)
                {
                    $index = 'legal_positive';
                }
                else if ($report['valor_seller'] > 0)
                {
                    $index = 'positive';
                }

                switch ($report['status_repasse'])
                {
                    case '21':
                        $status_icon    = 'hourglass-end';
                        $status_message = $this->lang->line('payment_report_list_icon_status_21');
                        break;
                    case '23':
                    case '33':
                        $status_icon    = 'check';
                        $status_message = $this->lang->line('payment_report_list_icon_status_23');
                        if (isset($report['paid_status_responsible']) && $report['paid_status_responsible']){
                            $status_message .= " pelo Usuário {$report['paid_status_responsible']}";
                        }
                        break;
                    case '25':
                        $status_icon    = 'bolt';
                        $status_message = $this->lang->line('payment_report_list_icon_status_25');
                        break;
                    case '26':
                        $status_icon    = 'ban';
                        $status_message = $this->lang->line('payment_report_list_icon_status_26');
                        break;
                    default:
                        $status_icon    = 'warning';
                        $status_message = $this->lang->line('payment_report_list_icon_status_0');
                }

                if (!isset($rows_by_store[$report['store_id']][$index . '_status']))
                {
                    $rows_by_store[$report['store_id']][$index . '_status'] = [];
                }

                if (!in_array($report['status_repasse'], $rows_by_store[$report['store_id']][$index . '_status']))
                {
                    $rows_by_store[$report['store_id']][$index . '_status'][] = $report['status_repasse'];
                }

                $rows_by_store[$report['store_id']]['paid_status_responsible'] = $report['paid_status_responsible'];
                $rows_by_store[$report['store_id']][$index]                     += floatVal($report['valor_seller']);
                $rows_by_store[$report['store_id']][$index . '_status_icon']    = (count($rows_by_store[$report['store_id']][$index . '_status']) == 1) ? '<i class="fa fa-' . $status_icon . '" aria-hidden="true"></i>' : '<i class="fa fa-warning" aria-hidden="true"></i>';
                $rows_by_store[$report['store_id']][$index . '_status_message'] = (count($rows_by_store[$report['store_id']][$index . '_status']) == 1) ? $status_message : $this->lang->line('payment_report_list_icon_status_mixed');
                $rows_by_store[$report['store_id']]['liquid']                   = $liquid_amount[$report['store_id']];
                $rows_by_store[$report['store_id']]['liquid_status_message']    = $this->lang->line('payment_report_list_icon_status_liquid');

                $rows_by_store[$report['store_id']][$index . '_status'] = array_unique($rows_by_store[$report['store_id']][$index . '_status']);
            }
        }

        $cycle_data = [
            'conciliation_data' => $this->conciliation_data,
            'all_cycles'        => $all_cycles,
            'transfer_rows'     => $rows_by_store
        ];

        return $cycle_data;
    }




    /*
    * Fetches the orders data from the orders table
    * this function is called from the datatable ajax function
    */
    public function fetchBilletListsPaymentData()
    {

        $result = array('data' => array());

        $data = $this->model_billet->getBilletsData();

        setlocale(LC_MONETARY, "pt_BR", "ptb");

        foreach ($data as $key => $value) {

            // button
            $buttons = '';
            $status_split = "";

            if (in_array('viewPayment', $this->permission)) {
                $buttons .= ' <a href="' . base_url('payment/view/' . $value['id']) . '" class="btn btn-default"><i class="fa fa-eye"></i></a>';
            }

            if (in_array('createPayment', $this->permission)) {
                if ($value['status_split_payment'] == "0") {
                    $buttons .= ' <button type="button" class="btn btn-default" onclick="gerarsplit(' . $value['id'] . ')"><i class="fa fa-plus"></i></button>';
                } else {
                    $buttons .= ' <button type="button" class="btn btn-default" onclick="#" disabled ><i class="fa fa-plus"></i></button>';
                }
                if ($value['status_iugu_id'] == "5" && $value['status_split_payment'] == "1") {
                    $buttons .= ' <button type="button" class="btn btn-default" onclick="aprovarsplit(' . $value['id'] . ')"><i class="fa fa-check-circle"></i></button>';
                } else {
                    $buttons .= ' <button type="button" class="btn btn-default" onclick="#" disabled ><i class="fa fa-check-circle"></i></button>';
                }
            }

            if ($value['status_split_payment'] == "0") {
                $status_split = "Split não realizado";
            } else {
                $status_split = "Split realizado";
            }

            $result['data'][$key] = array(
                $value['id'],
                $value['marketplace'],
                $value['id_boleto_iugu'],
                $value['data_geracao'],
                "R$ " . str_replace(".", ",", $value['valor_total']),
                $value['status_billet'],
                $value['status_iugu'],
                $status_split,
                $buttons
            );

// 			echo '<pre>';print_r($result);die;

        } // /foreach

        echo json_encode($result);
    }

    public function view($id = null)
    {

        $group_data1 = $this->model_billet->getBilletsData($id);
        $group_data2 = $this->model_payment->getBilletsPaymentSplitId($id);
        $this->data['billets'] = $group_data1[0];
        $this->data['billetsData'] = $group_data2;
        $this->render_template('billet/view', $this->data);

    }

    public function createpayment()
    {

        $group_data1 = $this->model_payment->getMktPlacesData();

        $this->data['mktplaces'] = $group_data1;

        $this->render_template('payment/create', $this->data);
    }

    public function fetchOrdersListData($idMktPlace = null, $dataInicio = null, $dataFim = null, $retirados = null)
    {

        $data['mktPlace'] = $idMktPlace;
        $data['dtInicio'] = $dataInicio;
        $data['dtFim'] = $dataFim;
        $data['retirados'] = str_replace("-", ",", $retirados);

        if ($data['mktPlace'] <> "") {
            $data = $this->model_payment->getOrdersData($data);
            if ($data) {
                foreach ($data as $key => $value) {
                    // button
                    $buttons = '';

                    $buttons .= ' <button type="button" class="btn btn-default" onclick="addbillet(' . $value['id'] . ',\'Add\')"><i class="fa fa-plus"></i></button>';
                    $buttons .= ' <button type="button" class="btn btn-default" onclick="addbillet(' . $value['id'] . ',\'Remove\')"><i class="fa fa-minus"></i></button>';

                    $result['data'][$key] = array(
                        $value['id'],
                        $value['mktplace'],
                        $value['num_pedido'],
                        $value['data_pedido'],
                        $value['status'],
                        "R$ " . str_replace(".", ",", $value['valor']),
                        $buttons
                    );
                } // /foreach
            } else {
                $result['data'][0] = array("", "", "", "", "", "", "");
            }
        } else {
            $result['data'][0] = array("", "", "", "", "", "", "");
        }
        echo json_encode($result);
    }

    public function fetchOrdersListAddedData($idOrders = null, $idFuncoes = null)
    {

        $data['idOrders'] = str_replace("-", ",", $idOrders);

        if ($data['idOrders'] <> "") {

            // Array com funções e Ids
            $arrayId = explode("-", $idOrders);
            $arrayFunction = explode("-", $idFuncoes);

            $arrayFinal = array();

            $i = 0;
            foreach ($arrayId as $id) {
                $arrayFinal[$id] = $arrayFunction[$i];
                $i++;
            }

            $data = $this->model_payment->getOrdersAddedData($data);
            if ($data) {
                foreach ($data as $key => $value) {

                    $função = "";
                    if ($arrayFinal[$value['id']] == "Add") {
                        $função = "Somar ao Boleto";
                    } else {
                        $função = "Não somar ao Boleto o pedido";
                    }

                    $result['data'][$key] = array(
                        $value['id'],
                        $value['mktplace'],
                        $value['num_pedido'],
                        "R$ " . str_replace(".", ",", $value['valor']),
                        $função
                    );
                } // /foreach
            } else {
                $result['data'][0] = array("", "", "", "", "", "", "");
            }
        } else {
            $result['data'][0] = array("", "", "", "", "", "", "");
        }
        echo json_encode($result);
    }

    public function totalBillet()
    {

        $idOrders = $this->postClean('id_orders');
        $idFuncoes = $this->postClean('id_function');

        // Array com funções e Ids
        $arrayId = explode("-", $idOrders);
        $arrayFunction = explode("-", $idFuncoes);

        $idSomatorio = "";
        $i = 0;
        foreach ($arrayId as $id) {
            if ($arrayFunction[$i] == "Add") {
                if ($idSomatorio == "") {
                    $idSomatorio = $id;
                } else {
                    $idSomatorio .= "," . $id;
                }
            }
            $i++;
        }

        if ($idSomatorio <> "") {
            $total = $this->model_payment->getSumOrdersAdded($idSomatorio);
        } else {
            $total[0]['valor'] = "0";
        }

        echo "R$ " . $total[0]['valor'];
    }

    public function create()
    {
        try {
            $inputs = $this->postClean(NULL,TRUE);

            $idOrders = $inputs['hdn_id_orders'];
            $idFuncoes = $inputs['hdn_id_function'];

            // Array com funções e Ids
            $arrayId = explode("-", $idOrders);
            $arrayFunction = explode("-", $idFuncoes);

            //Insere o valor em billet e retorna o ID
            $idBillet = $this->model_payment->insertBillet($inputs);
            if ($idBillet == false) {
                echo "1;Erro ao cadastrar o boleto";
                die;
            }

            //Prepara o array de pedidos a serem associados ao boleto
            $arraySaida = array();
            $i = 0;
            foreach ($arrayId as $id) {
                if ($arrayFunction[$i] == "Add") {
                    $arraySaida[$i]['billet_id'] = $idBillet;
                    $arraySaida[$i]['order_id'] = $id;
                    $arraySaida[$i]['ativo'] = 1;
                } else {
                    $arraySaida[$i]['billet_id'] = $idBillet;
                    $arraySaida[$i]['order_id'] = $id;
                    $arraySaida[$i]['ativo'] = 0;
                }
                $i++;
            }

            //Insere os pedidos associados ao boleto
            foreach ($arraySaida as $ArrayBilletOrder) {
                $salvar = $this->model_payment->insertBilletOrder($ArrayBilletOrder);
                if ($idBillet == false) {
                    echo "1;Erro ao cadastrar o boleto";
                    die;
                }
            }

            echo "0;Boleto cadastrado com sucesso!";

        } catch (Exception $e) {
            echo "1;Erro ao cadastrar o boleto";
        }


    }


    public function gerarboletoiugu()
    {

        $id = $inputs = $this->postClean('id');

        //Gera o boleto no webservice IUGU
        $retorno = $this->model_payment->gerarBoletoIUGU($id);

        if ($retorno['ret']) {

            //Atualiza os status de geração
            $retorno2 = $this->model_payment->atualizaStatus($id, 3, 6, $retorno['num_billet'], $retorno['url']);

            if ($retorno2) {
                echo "0;Boleto IUGU gerado com sucesso";
            } else {
                echo "1;Erro ao gerar Boleto IUGU";
            }

        } else {
            echo "1;Erro ao gerar Boleto IUGU";
        }

    }


    /******************************************************/

    public function paymentforecast($data = null)
    {

        if(!in_array('viewPaymentForecast', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->data['page_title'] = 'Previsão de Recebimento';

        //Nova previsão
        $datasASeremPagas = $this->model_billet->getDatasPagamentoPrevisaoExtratoConciliado("tela");

        $dataSaida = array();
        $i = 0;
        foreach ($datasASeremPagas as $datasASeremPaga) {
            $flagAchou = false;

            foreach ($dataSaida as $buscaData) {
                if ($buscaData['data'] == $datasASeremPaga['data_pagamento_conecta']) {
                    $flagAchou = true;
                }
            }

            if ($flagAchou == false) {
                $dataSaida[$i]['data'] = $datasASeremPaga['data_pagamento_conecta'];
                $i++;
            }

        }

        $ndata = array();
        foreach ($dataSaida as $key => $row) {
            //Inverte a data
            $ndata[$key] = \DateTime::createFromFormat('d/m/Y', $row['data'])->format('Y-m-d');
        }
        //ordena
        array_multisort($ndata, SORT_DESC, $dataSaida);

        $dataSaida = array();
        foreach($ndata as $key => $row) {
            $dataSaida[$key]['data'] = \DateTime::createFromFormat('Y-m-d', $row)->format('d/m/Y');
        }
        $this->data['dataSaida'] = $dataSaida;

        $this->render_template('payment/paymentforecast', $this->data);
    }

    public function extratopaymentforecast(){

        $datasASeremPagas      = $this->model_billet->getDatasPagamentoPrevisaoExtratoConciliado();
        $datasvaloresPrevisao  = $this->model_billet->extratopaymentforecast();

        $result = array('data' => array());
        $aux = 0;
        foreach ($datasASeremPagas as $key => $value) {

            $result['data'][$aux] = array(
                $value['marktplace'],
                $value['data_inicio'],
                $value['data_fim'],
                $value['data_pagamento_conecta'],
                "R$ 0,00"
            );
            $aux++;

        }

        $index = 0;
        foreach ($result['data'] as $value) {
            foreach($datasvaloresPrevisao as $resultadoValores){
                if($value[0] == $resultadoValores['marketplace'] && $value[3] == $resultadoValores['data_pagamento']){
                    $result['data'][$index][4] = "R$ ".number_format( $resultadoValores['expectativaReceb'], 2, ",", ".");
                }
            }
            $index++;
        }

        echo json_encode($result);


    }

    public function exportextratopaymentforecast(){

        /*set_time_limit(0);
        ini_set('memory_limit', '1024M');

        header("Pragma: public");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: pre-check=0, post-check=0, max-age=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        header("Content-Transfer-Encoding: none");
        header("Content-Type: application/vnd.ms-excel;");
        header("Content-type: application/x-msexcel;");
        header("Content-Disposition: attachment; filename=Previsão de Pagamento.xls");*/

        $datasvaloresPrevisao  = $this->model_billet->extratopaymentforecast("detalhado");
        $datasASeremPagas      = $this->model_billet->getDatasPagamentoPrevisaoExtratoConciliado();

        $pedidosASeremPagos = array();
        $indicePedidos = 0;

        foreach($datasASeremPagas as $datasASeremPagas){
            foreach($datasvaloresPrevisao as $pedidosTotal){
                if($datasASeremPagas['marktplace'] == $pedidosTotal['marketplace'] && $datasASeremPagas['data_pagamento_conecta'] == $pedidosTotal['data_pagamento']){
                    $pedidosASeremPagos[$indicePedidos] = $pedidosTotal;
                    $indicePedidos++;
                }

            }

        }

        $result['data'] = array();
        $aux = 0;

        foreach ($pedidosASeremPagos as $key => $value) {

            $buttons    = '';

            $status = $this->model_iugu->statuspedido($value['paid_status']);

            $valoPedido = "";
            if($value['valor_pedido'] <> '-'){
                $valoPedido = number_format( $value['valor_pedido'], 2, ",", ".");
            }else{
                $valoPedido = $value['valor_pedido'];
            }

            $valorProduto = "";
            if($value['valor_produto'] <> '-'){
                $valorProduto = number_format( $value['valor_produto'], 2, ",", ".");
            }else{
                $valorProduto = $value['valor_produto'];
            }

            $valorFrete = "";
            if($value['valor_frete'] <> '-'){
                $valorFrete = number_format( $value['valor_frete'], 2, ",", ".");
            }else{
                $valorFrete = $value['valor_frete'];
            }

            $expectativaReceb = "";

            $observacao = "";

            if($value['flag_status_soma'] == 'erro'){
                $expectativaReceb = "0,00";
                $observacao = "Valor do pedido não calculado por conta de divergência de status com o marketplace";
            }else{
                if($value['expectativaReceb'] <> '-'){
                    $expectativaReceb = number_format( $value['expectativaReceb'], 2, ",", ".");
                }else{
                    $expectativaReceb = $value['expectativaReceb'];
                }
            }


            $result['data'][$key] = array(
                $value['id'],
                $value['nome_loja'],
                $value['marketplace'],
                $value['numero_marketplace'],
                $status,
                $value['data_pedido'],
                $value['data_entrega'],
                $value['data_pagamento'],
                "<b>R$ ".$valoPedido."</b>",
                "<b>R$ ".$valorProduto."</b>",
                "<b>R$ ".$valorFrete."</b>",
                "<b>R$ ".$expectativaReceb."</b>"
            );
        } // /foreach

        ob_end_clean();

        echo utf8_decode("<table border=\"1\">
				<tr>
					<th colspan=\"7\"></th>
					<th colspan=\"1\">PREVISTO (Considerando as previsões de entrega do pedido e o ciclo de pagamento correspondente)</th>
					<th colspan=\"4\"></th>
					
				</tr>
				<tr>
					<th>" . $this->lang->line('application_id') . " - Pedido</th>
					<th>" . $this->lang->line('application_store') . "</th>
					<th>" . $this->lang->line('application_marketplace') . "</th>
					<th>" . $this->lang->line('application_purchase_id') . "</th> 
					<th>" . $this->lang->line('application_status') . "</th>
					<th>" . $this->lang->line('application_date') . " Pedido</th>
					<th>" . $this->lang->line('application_date') . " de Entrega</th>
					<th>" . $this->lang->line('application_payment_date_conecta') . "</th>
					<th>" . $this->lang->line('application_purchase_total') . "</th>
					<th>" . $this->lang->line('application_value_products') . "</th>
					<th>" . $this->lang->line('application_ship_value') . "</th>
					<th>Expectativa Recebimento</th>
			</tr>");
        if($result['data']){
            foreach($result['data'] as $value){

                echo utf8_decode("<tr>");
                echo utf8_decode("<td>".$value[0]."</td>");
                echo utf8_decode("<td>".$value[1]."</td>");
                echo utf8_decode("<td>".$value[2]."</td>");
                echo utf8_decode("<td>".$value[3]."</td>");
                echo utf8_decode("<td>".$value[4]."</td>");
                echo utf8_decode("<td>".$value[5]."</td>");
                echo utf8_decode("<td>".$value[6]."</td>");
                echo utf8_decode("<td>".$value[7]."</td>");
                echo utf8_decode("<td>".$value[8]."</td>");
                echo utf8_decode("<td>".$value[9]."</td>");
                echo utf8_decode("<td>".$value[10]."</td>");
                echo utf8_decode("<td>".$value[11]."</td>");
                echo utf8_decode("</tr>");

            }
        }
        echo "</table>";

    }

    public function listprevisao($data = null)
    {

        if(!in_array('viewPaymentForecast', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->data['page_title'] = 'Previsão de Recebimento';

        //Nova previsão
        $datasASeremPagas = $this->model_billet->getDatasPagamentoPrevisaoExtratoConciliado("tela");

        $dataSaida = array();
        $i = 0;
        foreach ($datasASeremPagas as $datasASeremPaga) {
            $flagAchou = false;

            foreach ($dataSaida as $buscaData) {
                if ($buscaData['data'] == $datasASeremPaga['data_pagamento_conecta']) {
                    $flagAchou = true;
                }
            }

            if ($flagAchou == false) {
                $dataSaida[$i]['data'] = $datasASeremPaga['data_pagamento_conecta'];
                $i++;
            }

        }

        $this->data['dataSaida'] = $dataSaida;
        $this->render_template('payment/listprevisao2', $this->data);
    }

    public function extratoprevisao()
    {

        if (!in_array('viewPaymentForecast', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $inputs = cleanArray($this->input->get());

        $data = $inputs['data'];
        $status = $inputs['status'];

        $result = array('data' => array());

        $retorno = $this->model_payment->getPrevisaoPagamentoGridSomatorioLoja(null, $data, $status);

        $retornoOutCiclo = $this->model_payment->getordersforadeciclo(null, $data, $status);

        if ($data == null or $data == "") {

            switch (date("M")) {
                case "Jan":
                    $mes = "Janeiro";
                    break;
                case "Fev":
                    $mes = "Fevereiro";
                    break;
                case "Mar":
                    $mes = "Março";
                    break;
                case "Apr":
                    $mes = "Abril";
                    break;
                case "May":
                    $mes = "Maio";
                    break;
                case "June":
                    $mes = "Junho";
                    break;
                case "Jul":
                    $mes = "Julho";
                    break;
                case "Aug":
                    $mes = "Agosto";
                    break;
                case "Sept":
                    $mes = "Setembro";
                    break;
                case "Oct":
                    $mes = "Outubro";
                    break;
                case "Nov":
                    $mes = "Novembro";
                    break;
                case "Dec":
                    $mes = "Dezembro";
                    break;
            }

            $dataGrid = $mes . "/" . date("Y");

        } else {

            $mesComparacao = substr($data, -2, 2);
            switch ($mesComparacao) {
                case "01":
                    $mes = "Janeiro";
                    break;
                case "02":
                    $mes = "Fevereiro";
                    break;
                case "03":
                    $mes = "Março";
                    break;
                case "04":
                    $mes = "Abril";
                    break;
                case "05":
                    $mes = "Maio";
                    break;
                case "06":
                    $mes = "Junho";
                    break;
                case "07":
                    $mes = "Julho";
                    break;
                case "08":
                    $mes = "Agosto";
                    break;
                case "09":
                    $mes = "Setembro";
                    break;
                case "10":
                    $mes = "Outubro";
                    break;
                case "11":
                    $mes = "Novembro";
                    break;
                case "12":
                    $mes = "Dezembro";
                    break;
            }

            $dataGrid = $mes . "/" . substr($data, 0, 4);

        }


        /*$aux = "";
        $j = -1;
        foreach($retorno as $resultado){
            if($resultado['data_pagamento'] <> $aux){
                $aux = $resultado['data_pagamento'];
                $j++;
                $arraySaida[$j]['data_pagamento'] = $resultado['data_pagamento'];

                $arraySaida[$j]['valor_total_ciclo'] = $resultado['valor_total_ciclo'];
                $arraySaida[$j]['recebido'] = $resultado['recebido'];
                $arraySaida[$j]['diferenca'] = $resultado['diferenca'];

                $arraySaida[$j]['loja'] = $resultado['loja'];
                $arraySaida[$j]['origin'] = $resultado['origin'];
                $arraySaida[$j]['data'] = $dataGrid;
                $arraySaida[$j]['observacao'] = $resultado['observacao'];


                if($resultado['data_pagamento'] == "0"){
                    $arraySaida[$j]['data_pagamento'] = "Ainda não contabilizados em nenhum Ciclo";
                }else{
                    $arraySaida[$j]['data_pagamento'] = $resultado['data_pagamento'];
                }

            }else{
                $arraySaida[$j]['valor_total_ciclo']   = $arraySaida[$j]['valor_total_ciclo'] + $resultado['valor_total_ciclo'];
                $arraySaida[$j]['recebido']            = $arraySaida[$j]['recebido'] + $resultado['recebido'];
                $arraySaida[$j]['diferenca']           = $arraySaida[$j]['diferenca'] + $resultado['diferenca'];
            }

        }*/
        $aux = 0;
        if (is_array($retornoOutCiclo)) {

            foreach ($retornoOutCiclo as $key2 => $value2) {

                $valor_total_ciclo = "";
                if ($value2['valor_total_ciclo'] <> '-') {
                    $valor_total_ciclo = number_format($value2['valor_total_ciclo'], 2, ",", ".");
                } else {
                    $valor_total_ciclo = $value2['valor_total_ciclo'];
                }

                $result['data'][$aux] = array(
                    '-',
                    $value2['loja'],
                    $value2['nome_mktplace'],
                    $value2['data_inicio'],
                    $value2['data_fim'],
                    $value2['data_pagamento'],
                    $value2['data_pagamento_conecta'],
                    $value2['ciclo'],
                    "<b>R$ " . $valor_total_ciclo . "</b>",
                    '',
                    '',
                    ''
                );
                $aux++;
            }// /foreach

            if (is_array($retorno)) {

                foreach ($retorno as $key => $value) {

                    $valor_total_ciclo = "";
                    if ($value['valor_total_ciclo'] <> '-') {
                        $valor_total_ciclo = number_format($value['valor_total_ciclo'], 2, ",", ".");
                    } else {
                        $valor_total_ciclo = $value['valor_total_ciclo'];
                    }

                    $valor_recebido = "";
                    if ($value['recebido'] <> '-') {
                        $valor_recebido = number_format($value['recebido'], 2, ",", ".");
                    } else {
                        $valor_recebido = $value['recebido'];
                    }

                    $valor_diferenca = "";
                    if ($value['diferenca'] <> '-') {
                        $valor_diferenca = number_format($value['diferenca'], 2, ",", ".");
                    } else {
                        $valor_diferenca = $value['diferenca'];
                    }


                    $observacao = "";
                    if ($value['observacao'] <> "") {
                        $observacao = ' <button type="button" class="btn btn-default" onclick="listarObservacao(\'' . $value['lote'] . '\', \'' . $value['store_id'] . '\')" data-toggle="modal" data-target="#listaObs"><i class="fa fa-eye"></i></button>';
                    }

                    $result['data'][$aux] = array(
                        $value['lote'],
                        $value['loja'],
                        $value['nome_mktplace'],
                        $value['data_inicio'],
                        $value['data_fim'],
                        $value['data_pagamento'],
                        $value['data_pagamento_conecta'],
                        $value['ciclo'],
                        "<b>R$ " . $valor_total_ciclo . "</b>",
                        "<b>R$ " . $valor_recebido . "</b>",
                        "<b>R$ " . $valor_diferenca . "</b>",
                        $observacao
                    );
                    $aux++;
                } // /foreach

            }
        } else {

            if (is_array($retorno)) {

                foreach ($retorno as $key => $value) {

                    $valor_total_ciclo = "";
                    if ($value['valor_total_ciclo'] <> '-') {
                        $valor_total_ciclo = number_format($value['valor_total_ciclo'], 2, ",", ".");
                    } else {
                        $valor_total_ciclo = $value['valor_total_ciclo'];
                    }

                    $valor_recebido = "";
                    if ($value['recebido'] <> '-') {
                        $valor_recebido = number_format($value['recebido'], 2, ",", ".");
                    } else {
                        $valor_recebido = $value['recebido'];
                    }

                    $valor_diferenca = "";
                    if ($value['diferenca'] <> '-') {
                        $valor_diferenca = number_format($value['diferenca'], 2, ",", ".");
                    } else {
                        $valor_diferenca = $value['diferenca'];
                    }

                    $observacao = "";
                    if ($value['observacao'] <> "") {
                        $observacao = ' <button type="button" class="btn btn-default" onclick="listarObservacao(\'' . $value['lote'] . '\', \'' . $value['store_id'] . '\')" data-toggle="modal" data-target="#listaObs"><i class="fa fa-eye"></i></button>';
                    }

                    $result['data'][$aux] = array(
                        $value['lote'],
                        $value['loja'],
                        $value['nome_mktplace'],
                        $value['data_inicio'],
                        $value['data_fim'],
                        $value['data_pagamento'],
                        $value['data_pagamento_conecta'],
                        $value['ciclo'],
                        "<b>R$ " . $valor_total_ciclo . "</b>",
                        "<b>R$ " . $valor_recebido . "</b>",
                        "<b>R$ " . $valor_diferenca . "</b>",
                        $observacao
                    );
                    $aux++;
                } // /foreach

            } else {
                $result['data'][0] = array("", "", "", "", "", "", "", "", "", "", "");
            }


        }
        echo json_encode($result);


    }

    public function extratoprevisao2()
    {

        $result = array('data' => array());
        $aux = 0;

        $dataValores = $this->model_billet->getPrevisaoExtratoConciliado();
        //Busca as datas
        $datasASeremPagas = $this->model_billet->getDatasPagamentoPrevisaoExtratoConciliado("tela");

        $dataSaida = array();
        $i = 0;
        $aux = "";
        $mktplaces = array();
        $j = 0;

        //Coloca as datas
        foreach ($datasASeremPagas as $datasASeremPaga) {
            $flagAchou = false;

            foreach ($dataSaida as $buscaData) {
                if ($buscaData['data'] == $datasASeremPaga['data_pagamento_conecta']) {
                    $flagAchou = true;
                }
            }

            if ($flagAchou == false) {
                $dataSaida[$i]['data'] = $datasASeremPaga['data_pagamento_conecta'];
                $i++;
            }

        }


        //Coloca os Marketplaces
        foreach ($datasASeremPagas as $datasASeremPaga) {
            if ($datasASeremPaga['marktplace'] <> $aux) {
                $i = 0;
                $flagMktplace = false;
                foreach ($dataSaida as $datas) {
                    $dataSaida[$i][$datasASeremPaga['marktplace']] = "-";
                    $i++;
                }

                if ($flagMktplace == false) {
                    if (!in_array($datasASeremPaga['marktplace'], $mktplaces)) {
                        $mktplaces[$j] = $datasASeremPaga['marktplace'];
                        $j++;
                        $flagMktplace = true;
                    }
                }

            }
            $aux = $datasASeremPaga['marktplace'];
        }

        //ajusta o - para 0 as datas que tem repasse no mktplace
        foreach ($datasASeremPagas as $datasASeremPaga) {
            $i = 0;
            foreach ($dataSaida as $datas) {
                if ($datasASeremPaga['data_pagamento_conecta'] == $datas['data']) {
                    $dataSaida[$i][$datasASeremPaga['marktplace']] = "R$ 0,00";
                }
                $i++;
            }
        }

        //Adiciona os valores a serem pagos por data e mktplace
        foreach ($dataValores as $dataValore) {
            $i = 0;
            foreach ($dataSaida as $datas) {
                if ($datas['data'] == $dataValore['data_pagamento_conectala']) {
                    $dataSaida[$i][$dataValore['marketplace']] = "R$ " . number_format($dataValore['expectativaReceb'], 2, ",", ".");
                }
                $i++;
            }
        }

        $arrayTela = array();
        $z = 0;
        //Monta o array para a tela
        foreach ($mktplaces as $mktplace) {
            $arrayTela[$z][0] = $mktplace;
            $controle = 1;
            foreach($dataSaida as $valores){
                $arrayTela[$z][$controle] = $valores[$mktplace];
                $controle++;
            }
            $z++;
        }

        $result['data'] = $arrayTela;

        //echo '<pre>';print_r($result);die;

        echo json_encode($result);



    }

    public function extratoprevisaodataspagamento(){

        $datasASeremPagas  = $this->model_billet->getDatasPagamentoPrevisaoExtratoConciliado();

        $result = array('data' => array());
        $aux = 0;
        foreach ($datasASeremPagas as $key => $value) {
            $result['data'][$aux] = array(
                'marktplace' => $value['marktplace'],
                'data_inicio' => $value['data_inicio'],
                'data_fim' => $value['data_fim'],
                'data_pagamento_mktplace' => $value['data_pagamento_mktplace'],
                'data_pagamento_conecta' => $value['data_pagamento_conecta'],
                'data_corte' => $value['data_corte']
            );
            $aux++;

        }

        echo json_encode($result);


    }

    public function extrato(){

        if(!in_array('viewExtract', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $valor = $this->model_settings->getSettingDatabyNameEmptyArray('gsoma_painel_financeiro');
        $valorNM = $this->model_settings->getSettingDatabyNameEmptyArray('novomundo_painel_financeiro');
        $valorORTB = $this->model_settings->getSettingDatabyNameEmptyArray('ortobom_painel_financeiro');
        $valorSellercenter = $this->model_settings->getSettingDatabyName('sellercenter');
        $data_transferencia_gateway_extrato = $this->model_settings->getStatusbyName('data_transferencia_gateway_extrato');
        $valor_pagamento_gateway_extrato = $this->model_settings->getStatusbyName('valor_pagamento_gateway_extrato');

        if($valorSellercenter['value'] <> "conectala"){
            $valorSellercenter['status'] = 1;
        }else{
            $valorSellercenter['status'] = 0;
        }


        if($valor['status'] == "1" || $valorNM['status'] == "1" || (isset($valorORTB['status']) && $valorORTB['status'] == "1") || $valorSellercenter['status'] == "1"){

            $dadosWS[0] = 1;
        }else{
            //Busca informações financeiras da conta IUGU
            $dadosWS = $this->model_iugu->buscadadosfinanceirosiuguWS();
        }

        $group_data1 = $this->model_billet->getMktPlacesData();

        $group_loja = $this->model_payment->buscalojafiltro();

        $j = 0;
        $statusSaida = array();
        for($i=0;$i<=101;$i++){
            $status = $this->model_iugu->statuspedido($i);
            if($status <> false){

                $flag = false;
                foreach($statusSaida as $verifica){
                    if($verifica['status'] == $status){
                        $flag = true;
                    }
                }

                if($flag == false){
                    $statusSaida[$j]['id'] = $i;
                    $statusSaida[$j]['status'] = $status;
                    $j++;
                }

            }
        }

        if($valorNM['status'] == "1"){
            $this->data['page_title'] = $this->lang->line('application_extract_novomundo');
        }else{
            $this->data['page_title'] = $this->lang->line('application_extract');
        }

        $this->data['mktplaces'] = $group_data1;
        $this->data['perfil'] = $_SESSION['group_id'];
        $this->data['dataws'] = $dadosWS;
        $this->data['filtrosts'] = $statusSaida;
        $this->data['filtrostore'] = $group_loja;
        $this->data['gsoma'] = $valor['status'];
        $this->data['nmundo'] = $valorNM['status'];
        $this->data['ortobom'] = $valorORTB['status'] ?? null;
        $this->data['sellercenter'] = $valorSellercenter['status'];
        $this->data['data_transferencia_gateway_extrato'] = $data_transferencia_gateway_extrato;
        $this->data['valor_pagamento_gateway_extrato'] = $valor_pagamento_gateway_extrato;
        $this->data['show_date_conectala_in_payment'] = $this->model_settings->getValueIfAtiveByName('show_date_conectala_in_payment');
        $this->data['show_new_columns_fin_46'] = $this->model_settings->getStatusbyName('show_new_columns_fin_46');
        $this->data['show_new_columns_fin_46_temp'] = $this->model_settings->getStatusbyName('show_new_columns_fin_46_temp');

        $this->render_template('payment/extrato', $this->data);

    }

    public function extratonovo(){

        if(!in_array('viewExtract', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $valor = $this->model_settings->getSettingDatabyNameEmptyArray('gsoma_painel_financeiro');
        $valorNM = $this->model_settings->getSettingDatabyNameEmptyArray('novomundo_painel_financeiro');
        $valorORTB = $this->model_settings->getSettingDatabyNameEmptyArray('ortobom_painel_financeiro');

        if($valor['status'] == "1" || $valorNM['status'] == "1"){
            $dadosWS[0] = 1;
        }else{
            //Busca informações financeiras da conta IUGU
            $dadosWS = $this->model_iugu->buscadadosfinanceirosiuguWS();
        }

        $group_data1 = $this->model_billet->getMktPlacesData();

        $group_loja = $this->model_payment->buscalojafiltro();

        $j = 0;
        $statusSaida = array();
        for($i=0;$i<=101;$i++){
            $status = $this->model_iugu->statuspedido($i);
            if($status <> false){

                $flag = false;
                foreach($statusSaida as $verifica){
                    if($verifica['status'] == $status){
                        $flag = true;
                    }
                }

                if($flag == false){
                    $statusSaida[$j]['id'] = $i;
                    $statusSaida[$j]['status'] = $status;
                    $j++;
                }

            }
        }

        if($valorNM['status'] == "1"){
            $this->data['page_title'] = $this->lang->line('application_extract_novomundo');
        }else{
            $this->data['page_title'] = $this->lang->line('application_extract');
        }

        $this->data['mktplaces'] = $group_data1;
        $this->data['perfil'] = $_SESSION['group_id'];
        $this->data['dataws'] = $dadosWS;
        $this->data['filtrosts'] = $statusSaida;
        $this->data['filtrostore'] = $group_loja;
        $this->data['gsoma'] = $valor['status'];
        $this->data['nmundo'] = $valorNM['status'];
        $this->data['ortobom'] = $valorORTB['status'];


        $this->render_template('payment/extratonovo', $this->data);

    }

    public function extratopedidosresumo()
    {

        if (!in_array('viewExtract', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $result = array('data' => array());

        $inputs = $this->input;
        $post = $inputs->post();
        $inputs = $inputs->get();

        if ($inputs['slc_status'] <> "") {
            //Busca todos os status pelo selecionado na tela
            $statusTela = $this->model_iugu->statuspedido($inputs['slc_status']);
            $j = 0;
            $statusFiltro = array();
            for ($i = 0; $i <= 101; $i++) {
                $status = $this->model_iugu->statuspedido($i);
                if ($status <> false) {
                    if ($status == $statusTela) {
                        $statusFiltro[$j] = $i;
                        $j++;
                    }
                }
            }
            $filtroFinal = implode(",", $statusFiltro);
            $inputs['slc_status'] = $filtroFinal;
        }

        // order
        $sOrder = null;
        if (isset($post['order'])) {
            if ($post['order'][0]['dir'] == "asc") $direction = "ASC";
            else $direction = "DESC";

            $fields = array('SML.descloja', 'IR.data_transferencia', 'SUM(IR.valor_parceiro)');

            $field = $fields[$post['order'][0]['column']];

            if ($field != "") $sOrder = " ORDER BY {$field} {$direction} ";
        }

        $data = $this->model_billet->getPedidosExtratoConciliadoResumo($inputs, $sOrder, $post);

        $valor = $this->model_settings->getSettingDatabyNameEmptyArray('gsoma_painel_financeiro');
        $valorNM = $this->model_settings->getSettingDatabyNameEmptyArray('novomundo_painel_financeiro');

        $count = $data['count'];
        $data = $data['data'];

        foreach ($data as $key => $value) {

            $valor_parceiro = "";
            if ($value['valor_parceiro'] <> '-') {
                $valor_parceiro = number_format($value['valor_parceiro'], 2, ",", ".");
            } else {
                $valor_parceiro = $value['valor_parceiro'];
            }

            if($valor['status'] == "1" || $valorNM['status'] == "1"){
                $result['data'][$key] = array(
                    $value['seller'],
                    $value['data_transferencia'],
                    "<b>R$ " . $valor_parceiro . "</b>"
                );
            }else{
                $result['data'][$key] = array(
                    $value['marketplace'],
                    $value['data_transferencia'],
                    "<b>R$ " . $valor_parceiro . "</b>"
                );
            }

            /*if ($this->model_settings->getSettingDatabyNameEmptyArray('gsoma_painel_financeiro')) {
                $result['data'][$key] = array(
                    $value['seller'],
                    $value['data_transferencia'],
                    "<b>R$ " . $valor_parceiro . "</b>"
                );
            } else {
                $result['data'][$key] = array(
                    $value['marketplace'],
                    $value['data_transferencia'],
                    "<b>R$ " . $valor_parceiro . "</b>"
                );
            }*/
        } // /foreach

        $count = (!$count) ? 0 : $count;

        $result = [
            'draw' => $post['draw'],
            'recordsTotal' => $count,
            'recordsFiltered' => $count,
            'data' => $result['data']
        ];

        echo json_encode($result);

    }

    public function extratopedidos()
    {

        error_reporting(-1);
        ini_set('display_errors', 1);

        set_time_limit(0);
        ini_set('memory_limit', '2048M');

        if (!in_array('viewExtract', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->load->model('model_campaigns_v2');
        $valor = $this->model_settings->getSettingDatabyNameEmptyArray('gsoma_painel_financeiro');
        $valorNM = $this->model_settings->getSettingDatabyNameEmptyArray('novomundo_painel_financeiro');
        $valorORTB = $this->model_settings->getSettingDatabyNameEmptyArray('ortobom_painel_financeiro');
        $valorSellercenter = $this->model_settings->getSettingDatabyName('sellercenter');

        $data_transferencia_gateway_extrato = $this->model_settings->getStatusbyName('data_transferencia_gateway_extrato');
        $valor_pagamento_gateway_extrato = $this->model_settings->getStatusbyName('valor_pagamento_gateway_extrato');

        if($valorSellercenter['value'] <> "conectala"){
            $valorSellercenter['status'] = 1;
        }else{
            $valorSellercenter['status'] = 0;
        }

        $result = array('data' => array());

        $inputs = $this->input;
        $post = $inputs->post();
        $inputs = $inputs->get();

        $inputs = cleanArray($inputs);

        if ($inputs['slc_status'] <> "") {
            $statusFiltro = array();
            $inputs['slc_status'] = explode(',', $inputs['slc_status']);
            $j = 0;
            foreach ($inputs['slc_status'] as $item) {
                //Busca todos os status pelo selecionado na tela
                $statusTela = $this->model_iugu->statuspedido($item);
                for ($i = 0; $i <= 101; $i++) {
                    $status = $this->model_iugu->statuspedido($i);
                    if ($status <> false) {
                        if ($status == $statusTela) {
                            $statusFiltro[$j] = $i;
                            $j++;
                        }
                    }
                }
            }

            $filtroFinal = implode(",", $statusFiltro);
            $inputs['slc_status'] = $filtroFinal;
        }

        // order
        $sOrder = null;
        if (isset($post['order'])) {
            if ($post['order'][0]['dir'] == "asc") $direction = "ASC";
            else $direction = "DESC";

            if ($valor['status'] == "1") {
                $fields = array(
                    'E.id',
                    'paid_status',
                    "IFNULL( STR_TO_DATE(date_time,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                    "IFNULL( STR_TO_DATE(data_entrega,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                    "IFNULL( STR_TO_DATE(data_pagamento_mktplace,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                    "IFNULL( STR_TO_DATE(data_transferencia,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                    'expectativaReceb',
                    'discount',
                    'comission',
                    '',
                    'store_name',
                    "IFNULL( STR_TO_DATE(data_envio,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )"
                );
            } elseif (isset($valorNM['status']) && $valorNM['status'] == "1") {
                $fields = array(
                    'store_name',
                    'paid_status',
                    "IFNULL( STR_TO_DATE(date_time,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                    "IFNULL( STR_TO_DATE(data_entrega,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                    "IFNULL( STR_TO_DATE(data_envio,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                    "IFNULL( STR_TO_DATE(data_pagamento_mktplace,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                    "IFNULL( STR_TO_DATE(data_transferencia,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                    'expectativaReceb',
                    'discount',
                    'comission',
                    ''
                );
            } elseif ((isset($valorORTB['status']) && $valorORTB['status'] == "1")) {
                $fields = array(
                    'E.id',
                    'store_name',
                    'paid_status',
                    "IFNULL( STR_TO_DATE(date_time,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                    "IFNULL( STR_TO_DATE(data_entrega,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                    "IFNULL( STR_TO_DATE(data_envio,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                    "IFNULL( STR_TO_DATE(data_pagamento_mktplace,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                    "IFNULL( STR_TO_DATE(data_transferencia,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                    'expectativaReceb',
                    'discount',
                    'comission',
                    ''
                );
            } elseif ($valorSellercenter['status'] == "1") {
                $fields = array(
                    'E.id',
                    'store_name',
                    'paid_status',
                    "IFNULL( STR_TO_DATE(date_time,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                    "IFNULL( STR_TO_DATE(data_entrega,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                    "IFNULL( STR_TO_DATE(data_envio,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                    "IFNULL( STR_TO_DATE(data_pagamento_mktplace,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                    "IFNULL( STR_TO_DATE(data_transferencia,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                    'expectativaReceb',
                    'discount',
                    'comission',
                    ''
                );
            } else {
                if (array_key_exists("extratonovo", $inputs)) {
                    $fields = array(
                        'E.id',
                        'paid_status',
                        "IFNULL( STR_TO_DATE(date_time,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                        "IFNULL( STR_TO_DATE(data_envio,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                        "IFNULL( STR_TO_DATE(data_entrega,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                        'data_caiu_na_conta',
                        'tratado',
                        "IFNULL( STR_TO_DATE(data_transferencia,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                        'valor_parceiro',
                        ''
                    );
                } else {
                    $fields = array(
                        'E.id',
                        'paid_status',
                        "IFNULL( STR_TO_DATE(date_time,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                        "IFNULL( STR_TO_DATE(data_entrega,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                        "IFNULL( STR_TO_DATE(data_pagamento_mktplace,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                        "IFNULL( STR_TO_DATE(data_pagamento_conectala,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                        "IFNULL( STR_TO_DATE(data_transferencia,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                        "IFNULL( STR_TO_DATE(data_transferencia,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                        'expectativaReceb',
                        'valor_parceiro',
                        'tratado',
                        '',
                        "IFNULL( STR_TO_DATE(data_envio,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )"
                    );
                }
            }

            $field = $fields[$post['order'][0]['column']];
            if ($field != "") $sOrder = " ORDER BY {$field} {$direction} ";
        }
        $show_date_conectala_in_payment = $this->model_settings->getValueIfAtiveByName('show_date_conectala_in_payment');
        $data = $this->model_billet->getPedidosExtratoConciliado($inputs, $sOrder, $post);
        $count = $data['count'];
        $data = $data['data'];
        foreach ($data as $key => $value) {

            $buttons = '';

            $status = $this->model_iugu->statuspedido($value['paid_status']);

            $observacao = "";

            if ($value['observacao'] <> "") {
                $observacao = ' <button type="button" class="btn btn-default" onclick="listarObservacao(\'' . $value['numero_pedido'] . '\', \'' . $value['lote'] . '\')" data-toggle="modal" data-target="#listaObs"><i class="fa fa-eye"></i></button>';
            }

            if ($value['numero_chamado'] <> "" && $observacao <> "") {
                $observacao = ' <button type="button" class="btn btn-default" onclick="listarObservacao(\'' . $value['numero_pedido'] . '\', \'' . $value['lote'] . '\')" data-toggle="modal" data-target="#listaObs"><i class="fa fa-eye"></i></button>';
            }

            $expectativaReceb = "";
            if ($value['expectativaReceb'] <> '-') {
                $expectativaReceb = number_format($value['expectativaReceb'], 2, ",", ".");
            } else {
                $expectativaReceb = $value['expectativaReceb'];
            }

            $valor_parceiro = "";
            if ($value['valor_parceiro'] <> '-') {
                $valor_parceiro = number_format($value['valor_parceiro'], 2, ",", ".");
            } else {
                $valor_parceiro = $value['valor_parceiro'];
            }

            $checkOrder = $this->model_orders->checkIfOrderCanBeAnticipated($value['order_id']);
            if($checkOrder){
                $valorAnticipationValue = $this->model_orders->getAnticipationTransferOrderValue($value['order_id']);
                if(is_null($valorAnticipationValue)){
                    $valor_parceiro = 0;
                }
            }

            if ($value['data_envio']) {
                $dataEnvio = date('d/m/Y', strtotime($value['data_envio']));
            } else {
                $dataEnvio = '';
            }

            $discount = "";
            if ($value['discount'] <> '-') {
                $discount = number_format($value['discount'], 2, ",", ".");
            } else {
                $discount = $value['discount'];
            }

            $comission = "";
            if ($value['comission'] <> '-') {
                $comission = number_format($value['comission'], 2, ",", ".");
            } else {
                $comission = $value['comission'];
            }

            $comissao_descontada = "";
            if($value['comissao_descontada'] <> '-'){
                $comissao_descontada = number_format( $value['comissao_descontada'], 2, ",", ".");
            }else{
                $comissao_descontada = $value['comissao_descontada'];
            }

            if (!$value['current_installment']){
                $value['current_installment'] = 1;
            }
            if (!$value['total_installments']){
                $value['total_installments'] = 1;
            }

            // Thiago
            $show_new_columns_fin_46 = $this->model_settings->getStatusbyName('show_new_columns_fin_46');
            $show_new_columns_fin_46_temp = $this->model_settings->getStatusbyName('show_new_columns_fin_46_temp');
            if($show_new_columns_fin_46 == "1") {

                $campaigns_data                 = $this->model_campaigns_v2->getCampaignsTotalsByOrderId($value['id']);
                $campaigns_pricetags            = (!empty($campaigns_data)) ? $campaigns_data['total_pricetags'] : 0;

                $campaigns_mktplace             = (!empty($campaigns_data)) ? $campaigns_data['total_channel'] : 0;

                $campaigns_rebate               = (!empty($campaigns_data)) ? $campaigns_data['total_rebate'] : 0;
                $campaigns_comission_reduction  = (!empty($campaigns_data)) ? $campaigns_data['comission_reduction'] : 0;

                $comissao = str_replace(",", ".", $comission);
                $expecReceb = $comissao = str_replace(",", ".", $expectativaReceb);
                $expecReceb = removeExtraDots($expecReceb);
                if ($campaigns_mktplace > 0){
                    $valor_repasse = $expecReceb + $campaigns_comission_reduction + ($campaigns_mktplace - ($campaigns_mktplace * ($comissao / 100)));
                }else{
                    $valor_repasse = $expecReceb + $campaigns_comission_reduction;
                }

                $valor_repasse += $campaigns_rebate;

                $percentualProduto = "";

                if ($value['percentual_produto'] <> '-')
                    $percentualProduto = number_format( $value['percentual_produto'], 2, ",", ".").'%';
                else
                    $percentualProduto = $value['percentual_produto'].'%';

                $tipo_de_frete = $value['tipo_frete'] > 0 ? 'Seller' : 'Conecta Lá';
                $valor_recebido = $value['valor_repasse'];
                $comissao_produto = ($value['gross_amount'] - $value['total_ship']) * ($value['percentual_produto'] / 100);
                $valor_produtos = $value['gross_amount'] - $value['total_ship'];
                $comissao_frete = $value['total_ship'] * ($value['percentual_produto'] / 100);
                $valor_frete = $value['total_ship'];
                $reducao_comissao = $value['comission_reduction'];
                $cupons = $campaigns_pricetags;
                $campanha_mkt_paga = $value['total_channel'];
                //$valor_pedido = $value['total_order'];

                $campaigns_comission_reduction_products = $value['comission_reduction_products'];
                $setting_api_comission = $this->model_settings->getSettingDatabyName('api_comission');
                $setting_api_comission = $setting_api_comission['status'];
                if ($setting_api_comission != "1") {
                    $reembolso = abs($valor_repasse - $value['valor_repasse']);
                } else {
                    $reembolso = abs($valor_repasse - $value['valor_repasse'] - $campaigns_comission_reduction_products);
                }

                $service_charge_freight_value = $value['service_charge_freight_value'];
                if($value['service_charge_freight_value'] == 100){
                    $service_charge_freight_value = 0;
                }

                if ($value['tipo_frete'] == 0) {
                    // sellercenter
                    $valor_comissao = (((($value['service_charge_rate']/100) * $value['total_order']) + (($service_charge_freight_value/100) * $value['total_ship'])) - $campaigns_comission_reduction + ($cupons + ($campaigns_mktplace) * ((($value['service_charge_rate']/100)))));

                    $expectativa_recebimento = ($value['gross_amount'] - ((($value['service_charge_rate']/100) * $value['total_order']) + (($service_charge_freight_value/100) * $value['total_ship'])) + $reembolso + ($cupons - ($cupons * ($value['service_charge_rate']/100))) - $value['total_ship']);
                } else {
                    // lojista
                    $valor_comissao = (((($value['service_charge_rate']/100) * $value['total_order']) + (($service_charge_freight_value/100) * $value['total_ship'])) - $campaigns_comission_reduction + ($cupons + ($campaigns_mktplace) * ((($value['service_charge_rate']/100)))));

                    $expectativa_recebimento = ($value['gross_amount'] - ((($value['service_charge_rate']/100) * $value['total_order']) + (($service_charge_freight_value/100) * $value['total_ship'])) + $reembolso + ($cupons - ($cupons * ($value['service_charge_rate']/100))));
                }

            }

            $sql = "SELECT SUM(valor_parceiro) AS valor_parceiro_iugu FROM iugu_repasse WHERE order_id = ?";
            $iugu = $this->db->query($sql, array($value['order_id']));
            if($iugu->num_rows() > 0){
                $valor_parceiro_iugu = $iugu->row()->valor_parceiro_iugu;
            }else{
                $valor_parceiro_iugu = 0;
            }
            // Thiago // FIN-46


            if ($valor['status'] == "1") {

                $result['data'][$key][] = $value['id'];
                $result['data'][$key][] = $this->model_iugu->statuspedido($value['paid_status']);
                $result['data'][$key][] = $value['date_time'];
                $result['data'][$key][] = $value['data_entrega'];
                $result['data'][$key][] = $value['data_pagamento_mktplace'];
                if ($data_transferencia_gateway_extrato == "1"){
                    $result['data'][$key][] = $this->model_iugu->getDataTransferencia($value['id']);
                }

                $result['data'][$key][] = $this->model_payment->getAnticipationTransferOrder($value['order_id']);

                if ($this->model_settings->getValueIfAtiveByName('allow_payment_reconciliation_installments')){
                    $result['data'][$key][] = $value['current_installment'] ? "{$value['current_installment']}/{$value['total_installments']}&nbsp;" : ' 1/1&nbsp;';
                    $result['data'][$key][] = money($value['expectativaReceb']/$value['total_installments']);
                    $result['data'][$key][] = $value['total_paid'] > 0 ? money($value['total_paid']) : money('0.00');
                }

                if($show_new_columns_fin_46 == "1") {
                    $result['data'][$key][] = $value['tipo_frete'] > 0 ? 'Seller' : 'Conecta Lá';
                    if($show_new_columns_fin_46_temp == "1") {
                        $result['data'][$key][] = money($valor_comissao);
                        $result['data'][$key][] = money($expectativa_recebimento);
                    }
                    $result['data'][$key][] = money($valor_parceiro_iugu);
                }

                $result['data'][$key][] = $value['pago'];
                $result['data'][$key][] = "<b>R$ " . $expectativaReceb . "</b>";
                $result['data'][$key][] = "<b>R$ " . $discount . "</b>";
                $result['data'][$key][] = "<b>R$ " . $comission . "</b>";
                if ($valor_pagamento_gateway_extrato == "1"){
                    $result['data'][$key][] = $this->model_iugu->getValorTransferencia($value['id']);
                }

                $result['data'][$key][] = $observacao;
                $result['data'][$key][] = $value['store_name'];
                $result['data'][$key][] = $value['data_envio'];

            } elseif (isset($valorNM['status']) && $valorNM['status'] == "1") {

                $result['data'][$key][] = $value['store_name'];
                $result['data'][$key][] = $this->model_iugu->statuspedido($value['paid_status']);
                $result['data'][$key][] = $value['date_time'];
                $result['data'][$key][] = $value['data_entrega'];
                $result['data'][$key][] = $value['data_envio'];
                $result['data'][$key][] = $value['data_pagamento_mktplace'];
                if ($data_transferencia_gateway_extrato == "1"){
                    $result['data'][$key][] = $this->model_iugu->getDataTransferencia($value['id']);
                }

                $result['data'][$key][] = $this->model_payment->getAnticipationTransferOrder($value['order_id']);

                if ($this->model_settings->getValueIfAtiveByName('allow_payment_reconciliation_installments')){
                    $result['data'][$key][] = $value['current_installment'] ? "{$value['current_installment']}/{$value['total_installments']}&nbsp;" : ' 1/1&nbsp;';
                    $result['data'][$key][] = money($value['expectativaReceb']/$value['total_installments']);
                    $result['data'][$key][] = $value['total_paid'] > 0 ? money($value['total_paid']) : money('0.00');
                }

                if($show_new_columns_fin_46 == "1") {
                    $result['data'][$key][] = $value['tipo_frete'] > 0 ? 'Seller' : 'Conecta Lá';
                    if($show_new_columns_fin_46_temp == "1") {
                        $result['data'][$key][] = money($valor_comissao);
                        $result['data'][$key][] = money($expectativa_recebimento);
                    }
                    $result['data'][$key][] = money($valor_parceiro_iugu);
                }

                $result['data'][$key][] = $value['pago'];
                $result['data'][$key][] = "<b>R$ " . $expectativaReceb . "</b>";
                $result['data'][$key][] = "<b>R$ " . $discount . "</b>";
                $result['data'][$key][] = "<b>R$ " . $comission . "</b>";
                if ($valor_pagamento_gateway_extrato == "1"){
                    $result['data'][$key][] = $this->model_iugu->getValorTransferencia($value['id']);
                }
                $result['data'][$key][] = $observacao;

            } elseif ((isset($valorORTB['status']) && $valorORTB['status'] == "1")) {

                $result['data'][$key][] = $value['id'];
                $result['data'][$key][] = $value['store_name'];
                $result['data'][$key][] = $this->model_iugu->statuspedido($value['paid_status']);
                $result['data'][$key][] = $value['date_time'];
                $result['data'][$key][] = $value['data_entrega'];
                $result['data'][$key][] = $value['data_envio'];
                $result['data'][$key][] = $value['data_pagamento_mktplace'];
                if ($data_transferencia_gateway_extrato == "1"){
                    $result['data'][$key][] = $this->model_iugu->getDataTransferencia($value['id']);
                }

                $result['data'][$key][] = $this->model_payment->getAnticipationTransferOrder($value['order_id']);

                if ($this->model_settings->getValueIfAtiveByName('allow_payment_reconciliation_installments')){
                    $result['data'][$key][] = $value['current_installment'] ? "{$value['current_installment']}/{$value['total_installments']}&nbsp;" : ' 1/1&nbsp;';
                    $result['data'][$key][] = money($value['expectativaReceb']/$value['total_installments']);
                    $result['data'][$key][] = $value['total_paid'] > 0 ? money($value['total_paid']) : money('0.00');
                }

                if($show_new_columns_fin_46 == "1") {
                    $result['data'][$key][] = $value['tipo_frete'] > 0 ? 'Seller' : 'Conecta Lá';
                    if($show_new_columns_fin_46_temp == "1") {
                        $result['data'][$key][] = money($valor_comissao);
                        $result['data'][$key][] = money($expectativa_recebimento);
                    }
                    $result['data'][$key][] = money($valor_parceiro_iugu);
                }

                $result['data'][$key][] = $value['pago'];
                $result['data'][$key][] = "<b>R$ " . $expectativaReceb . "</b>";
                $result['data'][$key][] = "<b>R$ " . $discount . "</b>";
                $result['data'][$key][] = "<b>R$ " . $comission . "</b>";
                if ($valor_pagamento_gateway_extrato == "1"){
                    $result['data'][$key][] = $this->model_iugu->getValorTransferencia($value['id']);
                }
                $result['data'][$key][] = $observacao;

            } elseif ($valorSellercenter['status'] == "1") {

                $result['data'][$key][] = $value['id'];
                $result['data'][$key][] = $value['store_name'];
                $result['data'][$key][] = $this->model_iugu->statuspedido($value['paid_status']);
                $result['data'][$key][] = $value['date_time'];
                $result['data'][$key][] = $value['data_entrega'];
                $result['data'][$key][] = $value['data_envio'];
                $result['data'][$key][] = $value['data_pagamento_mktplace'];
                if ($data_transferencia_gateway_extrato == "1"){
                    $result['data'][$key][] = $this->model_iugu->getDataTransferencia($value['id']);
                }

                if ( !(isset($inputs['output']) && $inputs['output'] == 'excell') ){
                    $result['data'][$key][] = $this->model_payment->getAnticipationTransferOrder($value['order_id']);
                }

                if ($this->model_settings->getValueIfAtiveByName('allow_payment_reconciliation_installments')){
                    $result['data'][$key][] = $value['current_installment'] ? "{$value['current_installment']}/{$value['total_installments']}&nbsp;" : ' 1/1&nbsp;';
                    $result['data'][$key][] = money($value['expectativaReceb']/$value['total_installments']);
                    $result['data'][$key][] = $value['total_paid'] > 0 ? money($value['total_paid']) : money('0.00');
                }

                if($show_new_columns_fin_46 == "1") {
                    $result['data'][$key][] = $value['tipo_frete'] > 0 ? 'Seller' : 'Conecta Lá';
                    if($show_new_columns_fin_46_temp == "1") {
                        $result['data'][$key][] = money($valor_comissao);
                        $result['data'][$key][] = money($expectativa_recebimento);
                    }
                    $result['data'][$key][] = money($valor_parceiro_iugu);
                }

                $result['data'][$key][] = $value['pago'];
                $result['data'][$key][] = "<b>R$ " . $expectativaReceb . "</b>";
                $result['data'][$key][] = "<b>R$ " . $discount . "</b>";
                $result['data'][$key][] = "<b>R$ " . $comission . "</b>";
                if ($valor_pagamento_gateway_extrato == "1"){
                    $result['data'][$key][] = $this->model_iugu->getValorTransferencia($value['id']);
                }
                $result['data'][$key][] = $observacao;

            } else {
                if (array_key_exists("extratonovo", $inputs)) {

                    $result['data'][$key][] = $value['id'];
                    $result['data'][$key][] = $this->model_iugu->statuspedido($value['paid_status']);
                    $result['data'][$key][] = $value['date_time'];
                    $result['data'][$key][] = $value['data_envio'];
                    $result['data'][$key][] = $value['data_entrega'];
                    $result['data'][$key][] = $value['data_caiu_na_conta'];
                    if ($data_transferencia_gateway_extrato == "1"){
                        $result['data'][$key][] = $this->model_iugu->getDataTransferencia($value['id']);
                    }

                    $result['data'][$key][] = $this->model_payment->getAnticipationTransferOrder($value['order_id']);

                    $result['data'][$key][] = $value['tratado'];
                    $result['data'][$key][] = $value['pago'];
                    $result['data'][$key][] = "<b>R$ " . $valor_parceiro . "</b>";
                    if ($valor_pagamento_gateway_extrato == "1"){
                        $result['data'][$key][] = $this->model_iugu->getValorTransferencia($value['id']);
                    }
                    $result['data'][$key][] = $observacao;

                }else{

                    $result['data'][$key] = [];
                    $result['data'][$key][] =$value['id'] ;
                    $result['data'][$key][] =$this->model_iugu->statuspedido($value['paid_status']) ;
                    $result['data'][$key][] =$value['date_time'] ;
                    $result['data'][$key][] =$value['data_entrega'] ;
                    $result['data'][$key][] =$value['data_pagamento_mktplace'] ;
                    if ($data_transferencia_gateway_extrato == "1"){
                        $result['data'][$key][] = $this->model_iugu->getDataTransferencia($value['id']);
                    }

                    $result['data'][$key][] = $this->model_payment->getAnticipationTransferOrder($value['order_id']);

                    if($show_date_conectala_in_payment){
                        $result['data'][$key][] =$value['data_pagamento_conectala'] ;
                    }
                    if ($this->model_settings->getValueIfAtiveByName('allow_payment_reconciliation_installments')){
                        $result['data'][$key][] = $value['current_installment'] ? "{$value['current_installment']}/{$value['total_installments']}&nbsp;" : ' 1/1&nbsp;';
                        $result['data'][$key][] = money($value['expectativaReceb']/$value['total_installments']);
                        $result['data'][$key][] = $value['total_paid'] > 0 ? money($value['total_paid']) : money('0.00');
                    }

                    if($show_new_columns_fin_46 == "1") {
                        $result['data'][$key][] = $value['tipo_frete'] > 0 ? 'Seller' : 'Conecta Lá';
                        if($show_new_columns_fin_46_temp == "1") {
                            $result['data'][$key][] = money($valor_comissao);
                            $result['data'][$key][] = money($expectativa_recebimento);
                        }
                        $result['data'][$key][] = money($valor_parceiro_iugu);
                    }

                    $result['data'][$key][] =$value['pago'] ;
                    $result['data'][$key][] =$value['data_transferencia'] ;
                    $result['data'][$key][] ="<b>R$ " . $expectativaReceb . "</b>" ;
                    $result['data'][$key][] ="<b>R$ " . $valor_parceiro . "</b>" ;
                    $result['data'][$key][] =$value['tratado'] ;
                    if ($valor_pagamento_gateway_extrato == "1"){
                        $result['data'][$key][] = $this->model_iugu->getValorTransferencia($value['id']);
                    }
                    $result['data'][$key][] =$observacao ;
                    $result['data'][$key][] =$value['data_envio'] ;
                }

            }


        } // /foreach

        $count = (!$count) ? 0 : $count;

        if(!$result['data']){
            if ($valor['status'] == "1") {
                if ($this->model_settings->getValueIfAtiveByName('allow_payment_reconciliation_installments')){
                    $result['data'][0] = array("","","","","","","","","","","","","","","","","","","","");
                }else{
                    $result['data'][0] = array("","","","","","","","","","","","","","","","");
                }
            }else{
                if ($this->model_settings->getValueIfAtiveByName('allow_payment_reconciliation_installments')){
                    $result['data'][0] = array("","","","","","","","","","","","","","","","","","","","");
                }else{
                    $result['data'][0] = array("","","","","","","","","","","","","","","","","");
                }

            }
        }

        array_push($result['data'][0], ...array(""));

        if ($data_transferencia_gateway_extrato == "1")
            array_push($result['data'][0], ...array(""));
        if ($valor_pagamento_gateway_extrato == "1")
            array_push($result['data'][0], ...array(""));

        if (isset($inputs['output']) && $inputs['output'] == 'excell'){

            header("Pragma: public");
            header("Cache-Control: no-store, no-cache, must-revalidate");
            header("Cache-Control: pre-check=0, post-check=0, max-age=0");
            header("Pragma: no-cache");
            header("Expires: 0");
            header("Content-Transfer-Encoding: none");
            header("Content-Type: application/vnd.ms-excel");
            header("Content-type: application/x-msexcel");
            header("Content-Disposition: attachment; filename=Extrato.xls");

            ob_end_clean();

            $columnsShow = [];
            if ($valor['status'] == "1") {

                $columnsShow['id'] = lang('application_id').' - '.lang('application_order');
                $columnsShow['status'] = lang('application_status');
                $columnsShow['order_date'] = lang('application_date').' - '.lang('application_order');
                $columnsShow['delivery_date'] = lang('application_delivered_date');
                $columnsShow['payment_date'] = lang('application_payment_date');
                if ($data_transferencia_gateway_extrato == "1"){
                    $columnsShow['data_transferencia_gateway_extrato'] = lang('data_transferencia_gateway_extrato');
                }
                if ($this->model_settings->getValueIfAtiveByName('allow_payment_reconciliation_installments')){
                    $columnsShow['parcels'] = lang('application_parcels');
                    $columnsShow['cicle_total_transfer'] = lang('application_cicle_total_transfer');
                    $columnsShow['value_paid'] = lang('application_value_paid');
                }

                if($show_new_columns_fin_46 == "1") {
                    $columnsShow['tipo_frete'] = "Tipo de Frete";
                    if($show_new_columns_fin_46_temp == "1") {
                        $columnsShow['valor_comissao'] = "Valor Comissão";
                        $columnsShow['expectativa_recebimento'] = "Expectativa de Recebimento";
                    }
                    $columnsShow['valor_recebido'] = "Valor Recebido";
                }

                $columnsShow['paid'] = lang('application_order_2'); //pago
                $columnsShow['receipt_expectation'] = lang('application_receipt_expectation');
                $columnsShow['discount'] = lang('application_discount');
                $columnsShow['comission'] = lang('application_commission');
                if ($valor_pagamento_gateway_extrato == "1"){
                    $columnsShow['valor_pagamento_gateway_extrato'] = lang('valor_pagamento_gateway_extrato');
                }
                $columnsShow['extract_obs'] = lang('application_extract_obs');
                $columnsShow['store'] = lang('application_store');
                $columnsShow['ship_date'] = lang('application_ship_date');

            } elseif ($valorNM['status'] == "1") {

                $columnsShow['store'] = lang('application_store');
                $columnsShow['status'] = lang('application_status');
                $columnsShow['order_date'] = lang('application_date').' - '.lang('application_order');
                $columnsShow['delivery_date'] = lang('application_delivered_date');
                $columnsShow['ship_date'] = lang('application_ship_date');
                $columnsShow['payment_date'] = lang('application_payment_date');
                if ($data_transferencia_gateway_extrato == "1"){
                    $columnsShow['data_transferencia_gateway_extrato'] = lang('data_transferencia_gateway_extrato');
                }
                if ($this->model_settings->getValueIfAtiveByName('allow_payment_reconciliation_installments')){
                    $columnsShow['parcels'] = lang('application_parcels');
                    $columnsShow['cicle_total_transfer'] = lang('application_cicle_total_transfer');
                    $columnsShow['value_paid'] = lang('application_value_paid');
                }

                if($show_new_columns_fin_46 == "1") {
                    $columnsShow['tipo_frete'] = "Tipo de Frete";
                    if($show_new_columns_fin_46_temp == "1") {
                        $columnsShow['valor_comissao'] = "Valor Comissão";
                        $columnsShow['expectativa_recebimento'] = "Expectativa de Recebimento";
                    }
                    $columnsShow['valor_recebido'] = "Valor Recebido";
                }

                $columnsShow['paid'] = lang('application_order_2'); //pago
                $columnsShow['receipt_expectation'] = lang('application_receipt_expectation');
                $columnsShow['discount'] = lang('application_discount');
                $columnsShow['comission'] = lang('application_commission');
                if ($valor_pagamento_gateway_extrato == "1"){
                    $columnsShow['valor_pagamento_gateway_extrato'] = lang('valor_pagamento_gateway_extrato');
                }
                $columnsShow[] = lang('application_extract_obs');

            } elseif (isset($valorORTB['status']) && $valorORTB['status'] == "1") {

                $columnsShow['id'] = lang('application_id').' - '.lang('application_order');
                $columnsShow['store'] = lang('application_store');
                $columnsShow['status'] = lang('application_status');
                $columnsShow['order_date'] = lang('application_date').' - '.lang('application_order');
                $columnsShow['delivery_date'] = lang('application_delivered_date');
                $columnsShow['ship_date'] = lang('application_ship_date');
                $columnsShow['payment_date'] = lang('application_payment_date');
                if ($data_transferencia_gateway_extrato == "1"){
                    $columnsShow['data_transferencia_gateway_extrato'] = lang('data_transferencia_gateway_extrato');
                }
                if ($this->model_settings->getValueIfAtiveByName('allow_payment_reconciliation_installments')){
                    $columnsShow['parcels'] = lang('application_parcels');
                    $columnsShow['cicle_total_transfer'] = lang('application_cicle_total_transfer');
                    $columnsShow['value_paid'] = lang('application_value_paid');
                }

                if($show_new_columns_fin_46 == "1") {
                    $columnsShow['tipo_frete'] = "Tipo de Frete";
                    if($show_new_columns_fin_46_temp == "1") {
                        $columnsShow['valor_comissao'] = "Valor Comissão";
                        $columnsShow['expectativa_recebimento'] = "Expectativa de Recebimento";
                    }
                    $columnsShow['valor_recebido'] = "Valor Recebido";
                }

                $columnsShow['paid'] = lang('application_order_2'); //pago
                $columnsShow['receipt_expectation'] = lang('application_receipt_expectation');
                $columnsShow['discount'] = lang('application_discount');
                $columnsShow['comission'] = lang('application_commission');
                if ($valor_pagamento_gateway_extrato == "1"){
                    $columnsShow['valor_pagamento_gateway_extrato'] = lang('valor_pagamento_gateway_extrato');
                }
                $columnsShow['extract_obs'] = lang('application_extract_obs');

            } elseif ($valorSellercenter['status'] == "1") {

                $columnsShow['id'] = lang('application_id').' - '.lang('application_order');
                $columnsShow['store'] = lang('application_store');
                $columnsShow['status'] = lang('application_status');
                $columnsShow['order_date'] = lang('application_date').' - '.lang('application_order');
                $columnsShow['delivery_date'] = lang('application_delivered_date');
                $columnsShow['ship_date'] = lang('application_ship_date');
                $columnsShow['payment_date'] = lang('application_payment_date');
                if ($data_transferencia_gateway_extrato == "1"){
                    $columnsShow['data_transferencia_gateway_extrato'] = lang('data_transferencia_gateway_extrato');
                }
                if ($this->model_settings->getValueIfAtiveByName('allow_payment_reconciliation_installments')){
                    $columnsShow['parcels'] = lang('application_parcels');
                    $columnsShow['cicle_total_transfer'] = lang('application_cicle_total_transfer');
                    $columnsShow['value_paid'] = lang('application_value_paid');
                }

                if(isset($show_new_columns_fin_46) && $show_new_columns_fin_46 == "1") {
                    $columnsShow['tipo_frete'] = "Tipo de Frete";
                    if($show_new_columns_fin_46_temp == "1") {
                        $columnsShow['valor_comissao'] = "Valor Comissão";
                        $columnsShow['expectativa_recebimento'] = "Expectativa de Recebimento";
                    }
                    $columnsShow['valor_recebido'] = "Valor Recebido";
                }

                $columnsShow['paid'] = lang('application_order_2'); //pago
                $columnsShow['receipt_expectation'] = lang('application_receipt_expectation');
                $columnsShow['discount'] = lang('application_discount');
                $columnsShow['comission'] = lang('application_commission');
                if ($valor_pagamento_gateway_extrato == "1"){
                    $columnsShow['valor_pagamento_gateway_extrato'] = lang('valor_pagamento_gateway_extrato');
                }
                $columnsShow['extract_obs'] = lang('application_extract_obs');

            } else {
                if (!array_key_exists("extratonovo", $inputs)) {
                    $columnsShow['id'] = lang('application_id').' - '.lang('application_order');
                    $columnsShow['status'] = lang('application_status');
                    $columnsShow['order_date'] = lang('application_date').' - '.lang('application_order');
                    $columnsShow['delivery_date'] = lang('application_delivered_date');
                    $columnsShow['payment_date'] = lang('application_payment_date').' - Marketplace';
                    if($show_date_conectala_in_payment){
                        $columnsShow['payment_date_conecta'] = lang('application_payment_date_conecta');
                    }
                    if ($data_transferencia_gateway_extrato == "1"){
                        $columnsShow['data_transferencia_gateway_extrato'] = lang('data_transferencia_gateway_extrato');
                    }
                    if ($this->model_settings->getValueIfAtiveByName('allow_payment_reconciliation_installments')){
                        $columnsShow['parcels'] = lang('application_parcels');
                        $columnsShow['cicle_total_transfer'] = lang('application_cicle_total_transfer');
                        $columnsShow['value_paid'] = lang('application_value_paid');
                    }

                    if($show_new_columns_fin_46 == "1") {
                        $columnsShow['tipo_frete'] = "Tipo de Frete";
                        if($show_new_columns_fin_46_temp == "1") {
                            $columnsShow['valor_comissao'] = "Valor Comissão";
                            $columnsShow['expectativa_recebimento'] = "Expectativa de Recebimento";
                        }
                        $columnsShow['valor_recebido'] = "Valor Recebido";
                    }

                    $columnsShow['paid'] = lang('application_order_2'); //pago
                    $columnsShow['bank_transfer_date'] = lang('application_date').' - '.lang('application_bank_transfer');
                    $columnsShow['receipt_expectation'] = lang('application_receipt_expectation');
                    $columnsShow['extract_paid_marketplace'] = lang('application_extract_paid_marketplace');
                    $columnsShow['extract_conciliado'] = lang('application_extract_conciliado');
                    if ($valor_pagamento_gateway_extrato == "1"){
                        $columnsShow['valor_pagamento_gateway_extrato'] = lang('valor_pagamento_gateway_extrato');
                    }
                    $columnsShow['extract_obs'] = lang('application_extract_obs');
                    $columnsShow['ship_date'] = lang('application_ship_date');
                }
            }

            if ($this->model_settings->getValueIfAtiveByName('notificacao_painel_juridico_extrato')){
                $columnsShow['legal_panel_notification_title'] = lang('application_legal_panel_notification_title');
                $columnsShow['notification_date'] = lang('application_notification_date');
            }

            $table = "<table border='1'><tr>";
            foreach ($columnsShow as $column){
                $table.= utf8_decode("<th>{$column}</th>");
            }
            $table.= "</tr>";


            //Se é para mostrar as notificações do painel jurídico no extrado XLS, vamos sempre mostrar as notificações primeiro
            if ($this->model_settings->getValueIfAtiveByName('notificacao_painel_juridico_extrato')){

                $dataInicio = $inputs['txt_data_inicio'] ?: null;
                $dataFim = $inputs['txt_data_fim'] ?: null;

                $legalPanelNotifications = $this->model_legal_panel->getAllBetweenDate($dataInicio, $dataFim);

                if ($legalPanelNotifications){
                    foreach ($legalPanelNotifications as $legalPanelNotification){

                        $table.= "<tr>";
                        foreach ($columnsShow as $columnKey => $columnName){
                            $table.= "<td>";
                            if ($columnKey == 'id' && $legalPanelNotification['orders_id']){
                                $table.= $legalPanelNotification['orders_id'];
                            }
                            if ($columnKey == 'store'){
                                $table.= utf8_decode($legalPanelNotification['store_name']);
                                // $table.= $legalPanelNotification['store_name'];
                            }
                            if ($columnKey == 'status'){
                                $table.= $legalPanelNotification['status'];
                            }
                            if ($columnKey == 'order_date' && $legalPanelNotification['order_datetime']){
                                $table.= $legalPanelNotification['order_datetime'];
                            }
                            if ($columnKey == 'delivery_date' && $legalPanelNotification['order_date_delivery']){
                                $table.= $legalPanelNotification['order_date_delivery'];
                            }
                            if ($columnKey == 'payment_date' && $legalPanelNotification['data_pagamento_mktplace'] && !strstr($legalPanelNotification['data_pagamento_mktplace'], '00/')){
                                $table.= $legalPanelNotification['data_pagamento_mktplace'];
                            }
                            if ($columnKey == 'payment_date_conecta' && $show_date_conectala_in_payment && $legalPanelNotification['data_pagamento_conectala'] && !strstr($legalPanelNotification['data_pagamento_conectala'], '00/')){
                                $table.= $legalPanelNotification['data_pagamento_conectala'];
                            }
                            if (in_array($columnKey, ['cicle_total_transfer', 'receipt_expectation']) && ($legalPanelNotification['valor_repasse'] || $legalPanelNotification['balance_paid'])){
                                $table.= '-'.money($legalPanelNotification['valor_repasse'] ?: $legalPanelNotification['balance_paid']);
                            }
                            if ($columnKey == 'ship_date' && $legalPanelNotification['data_envio']){
                                $table.= $legalPanelNotification['data_envio'];
                            }
                            if ($columnKey == 'legal_panel_notification_title' && $legalPanelNotification['notification_title']){
                                $table.= utf8_decode($legalPanelNotification['notification_title']);
                                // $table.= $legalPanelNotification['notification_title'];
                            }
                            if ($columnKey == 'notification_date' && $legalPanelNotification['notification_date']){
                                $table.= $legalPanelNotification['notification_date'];
                            }
                            if ($columnKey == 'parcels' && $legalPanelNotification['current_installment'] && $legalPanelNotification['total_installments']){
                                $table.= "{$legalPanelNotification['current_installment']}/{$legalPanelNotification['total_installments']}&nbsp;";
                            }
                            if ($columnKey == 'paid'){
                                $table.= utf8_decode(is_null($legalPanelNotification['data_transferencia']) ? 'Não' : 'Sim');
                            }
                            if ($columnKey == 'extract_obs'){
                                $json = json_decode($legalPanelNotification['description'], true);
                                $table.= utf8_decode("Notificação: {$legalPanelNotification['notification_id']}, ");
                                // $table.= "Notificação: {$legalPanelNotification['notification_id']}, ";
                                if ($json){
                                    $table.= "Produto: {$json['product']}, ";
                                    $table.= "Bandeira: {$json['card_brand']}";
                                }else{
                                    // $table.= $legalPanelNotification['description'];
                                    $table.= utf8_decode($legalPanelNotification['description']);
                                }

                            }
                            $table.= "</td>";
                        }
                        $table.= "</tr>";

                    }
                }

            }

            foreach ($result['data'] as $data){
                $table.= "<tr>";
                foreach ($data as $column){
                    $table.= utf8_decode("<td>{$column}</td>");
                }
                if ($this->model_settings->getValueIfAtiveByName('notificacao_painel_juridico_extrato')){
                    $table.= utf8_decode("<td></td>");
                    $table.= utf8_decode("<td></td>");
                }
                $table.= "</tr>";
            }

            $table.= "</table>";

            exit($table);

        }

        $result = [
            'draw' => $post['draw'] ?? null,
            'recordsTotal' => $count,
            'recordsFiltered' => $count,
            'data' => $result['data']
        ];


        echo json_encode($result);

    }

    public function extratopedidosexcel()
    {
        $params = array('source' => 'tela');
        $this->load->library('Extrato/ExtratoLibrary', $params);
        $extrato = $this->extratolibrary->extratopedidos();
        //echo json_encode($extrato);
    }


// IMPORTANTE: ESSA FUNÇÃO SERÁ REMOVIDA APÓS A API DE EXTRATO ESTIVER EM PRODUÇÃO
    public function extratopedidosexcel_atual()
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');

        $this->load->model('model_campaigns_v2');

        $valor = $this->model_settings->getSettingDatabyNameEmptyArray('gsoma_painel_financeiro');
        $valorNM = $this->model_settings->getSettingDatabyNameEmptyArray('novomundo_painel_financeiro');
        $valorORTB = $this->model_settings->getSettingDatabyNameEmptyArray('ortobom_painel_financeiro');
        $valorSellercenter = $this->model_settings->getSettingDatabyName('sellercenter');

        $dataRepasseSellerCenter = $this->model_settings->getSettingDatabyName('painel_financeiro_data_repasse_sellercenter');
        $valorRealRepasseSellerCenter = $this->model_settings->getSettingDatabyName('painel_financeiro_valor_real_repasse_sellercenter');

        $data_transferencia_gateway_extrato = $this->model_settings->getStatusbyName('data_transferencia_gateway_extrato');
        $valor_pagamento_gateway_extrato = $this->model_settings->getStatusbyName('valor_pagamento_gateway_extrato');

        if(!$dataRepasseSellerCenter){
            $dataRepasseSellerCenter['status'] == "0";
        }

        if(!$valorRealRepasseSellerCenter){
            $valorRealRepasseSellerCenter = array();
            $valorRealRepasseSellerCenter['status'] = 0;
        }

        if ($valorSellercenter['value'] <> "conectala"){
            $valorSellercenter['status'] = 1;
        }else{
            $valorSellercenter['status'] = 0;
        }

        header("Pragma: public");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: pre-check=0, post-check=0, max-age=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        header("Content-Transfer-Encoding: none");
        header("Content-Type: application/vnd.ms-excel;");
        header("Content-type: application/x-msexcel;");


        if ($valorNM['status'] == "1")
            header("Content-Disposition: attachment; filename=Extrato - Novo Mundo.xls");
        else
            header("Content-Disposition: attachment; filename=Extrato.xls");

        $result = array('data' => array());

        $inputs = $this->input;
        $post = $inputs->post();
        $inputs = $inputs->get();

        $inputs = cleanArray($inputs);

        if ($inputs['slc_status'] <> "")
        {
            $statusFiltro = array();
            $inputs['slc_status'] = explode(',', $inputs['slc_status']);
            $j = 0;

            foreach ($inputs['slc_status'] as $item)
            {
                //Busca todos os status pelo selecionado na tela
                $statusTela = $this->model_iugu->statuspedido($item);

                for ($i = 0; $i <= 101; $i++)
                {
                    $status = $this->model_iugu->statuspedido($i);

                    if ($status <> false)
                    {
                        if ($status == $statusTela)
                        {
                            $statusFiltro[$j] = $i;
                            $j++;
                        }
                    }
                }
            }

            $filtroFinal = implode(",", $statusFiltro);
            $inputs['slc_status'] = $filtroFinal;
        }

        $data = $this->model_billet->getPedidosExtratoConciliado($inputs, null, $post,"excel");

        foreach ($data as $key => $value)
        {
            $campaigns_data                 = $this->model_campaigns_v2->getCampaignsTotalsByOrderId($value['id']);

            $campaigns_pricetags            = (!empty($campaigns_data)) ? $campaigns_data['total_pricetags'] : 0;
            $campaigns_campaigns            = (!empty($campaigns_data)) ? $campaigns_data['total_campaigns'] : 0;
            $campaigns_mktplace             = (!empty($campaigns_data)) ? $campaigns_data['total_channel'] : 0;
            $campaigns_seller               = (!empty($campaigns_data)) ? $campaigns_data['total_seller'] : 0;
            $campaigns_promotions           = (!empty($campaigns_data)) ? $campaigns_data['total_promotions'] : 0;
            $campaigns_rebate               = (!empty($campaigns_data)) ? $campaigns_data['total_rebate'] : 0;
            $campaigns_comission_reduction  = (!empty($campaigns_data)) ? $campaigns_data['comission_reduction'] : 0;
            $refund                         = 0;

            $campaigns_comissionreduxchannel    = (!empty($campaigns_data)) ? $campaigns_data['comission_reduction_marketplace'] : 0;
            $campaigns_rebatechannel            = (!empty($campaigns_data)) ? $campaigns_data['total_rebate_marketplace'] : 0;
            $campaigns_channelrefund            = 0;

            $comission = $value['service_charge_value'];

            if ($campaigns_campaigns > 0)
                $campaigns_promotions = 0;

            if ($campaigns_mktplace > 0)
                $valor_repasse = $value['expectativaReceb'] + $campaigns_comission_reduction + ($campaigns_mktplace - ($campaigns_mktplace * ($comission / 100)));
            else
                $valor_repasse = $value['expectativaReceb'] + $campaigns_comission_reduction;

            $valor_repasse += $campaigns_rebate;

            if ($campaigns_mktplace > 0)
                $valor_comissao = $value['comission'] - $campaigns_comission_reduction + ($campaigns_mktplace * ($comission / 100));
            else
                $valor_comissao = $value['comission'] - $campaigns_comission_reduction;

            $refund = $valor_repasse - $value['expectativaReceb'];

            $campaigns_pricetags            = number_format($campaigns_pricetags, 2, ",", ".");
            $campaigns_campaigns            = number_format($campaigns_campaigns, 2, ",", ".");
            $campaigns_mktplace             = number_format($campaigns_mktplace, 2, ",", ".");
            $campaigns_seller               = number_format($campaigns_seller, 2, ",", ".");
            $campaigns_promotions           = number_format($campaigns_promotions, 2, ",", ".");
            $campaigns_rebate               = number_format($campaigns_rebate, 2, ",", ".");
            $campaigns_comission_reduction  = number_format($campaigns_comission_reduction, 2, ",", ".");
            $refund                         = number_format($refund, 2, ",", ".");

            //fazer os calculos para o refund channel
            $campaigns_comissionreduxchannel    = number_format($campaigns_comissionreduxchannel, 2, ",", ".");
            $campaigns_rebatechannel            = number_format($campaigns_rebatechannel, 2, ",", ".");
            $campaigns_channelrefund            = number_format($campaigns_channelrefund, 2, ",", ".");

            $status = $this->model_iugu->statuspedido($value['paid_status']);
            $observacao = "";

            if ($value['observacao'] <> "")
                $observacao .= $value['observacao'];

            if ($value['numero_chamado'] <> "")
            {
                $observacao .= ' <br>Aberto o chamado '.$value['numero_chamado'].' junto ao marketplace '.$value['marketplace'].' para esclarecimento do pedido';

                if ($value['previsao_solucao'] <> "" && $value['previsao_solucao'] <> "00/00/0000")
                    $observacao .= ', com previsao de retorno em '.$value['previsao_solucao'];
            }

            $valoPedido = "";

            if ($value['gross_amount'] <> '-')
                $valoPedido = number_format( $value['gross_amount'], 2, ",", ".");
            else
                $valoPedido = $value['gross_amount'];

            $valorProduto = "";

            if ($value['total_order'] <> '-')
                $valorProduto = number_format( $value['total_order'], 2, ",", ".");
            else
                $valorProduto = $value['total_order'];

            $valorFrete = "";

            if($value['total_ship'] <> '-' && $value['total_ship'] <> '')
                $valorFrete = number_format( $value['total_ship'], 2, ",", ".");
            else
                $valorFrete = $value['total_ship'];

            $expectativaReceb = number_format($valor_repasse, 2, ",", ".");

            $valor_parceiro = "";

            if ($value['valor_parceiro'] <> '-')
                $valor_parceiro = number_format( $value['valor_parceiro'], 2, ",", ".");
            else
                $valor_parceiro = $value['valor_parceiro'];

            $comissao_descontada = "";

            if ($value['comissao_descontada'] <> '-')
                $comissao_descontada = number_format( $value['comissao_descontada'], 2, ",", ".");
            else
                $comissao_descontada = $value['comissao_descontada'];

            $calc_comissao_descontada = "";

            if ($value['calc_comissao_descontada'] <> '-')
                $calc_comissao_descontada = number_format( $value['calc_comissao_descontada'], 2, ",", ".");
            else
                $calc_comissao_descontada = $value['calc_comissao_descontada'];

            $imposto_renda_comissao_descontada = "";

            if ($value['imposto_renda_comissao_descontada'] <> '-')
                $imposto_renda_comissao_descontada = number_format( $value['imposto_renda_comissao_descontada'], 2, ",", ".");
            else
                $imposto_renda_comissao_descontada = $value['imposto_renda_comissao_descontada'];

            $total_comissao_descontada = number_format($valor_comissao, 2, ",", ".");;

            $percentualFrete = "";

            if ($value['percentual_frete'] <> '-')
                $percentualFrete = number_format( $value['percentual_frete'], 2, ",", ".").'%';
            else
                $percentualFrete = $value['percentual_frete'].'%';

            $percentualProduto = "";

            if ($value['percentual_produto'] <> '-')
                $percentualProduto = number_format( $value['percentual_produto'], 2, ",", ".").'%';
            else
                $percentualProduto = $value['percentual_produto'].'%';

            $transferencia_gateway_extrato = $this->model_iugu->getDataTransferencia($value['id']);;
            $pagamento_gateway_extrato = $this->model_iugu->getValorTransferencia($value['id']);;

            // Thiago
            $show_new_columns_fin_46 = $this->model_settings->getStatusbyName('show_new_columns_fin_46');
            if($show_new_columns_fin_46 == "1") {
                $tipo_de_frete = $value['tipo_frete'] > 0 ? 'Seller' : 'Conecta Lá';
                $valor_recebido = $value['valor_repasse'];
                $comissao_produto = ($value['gross_amount'] - $value['total_ship']) * ($value['percentual_produto'] / 100);
                $valor_produtos = $value['gross_amount'] - $value['total_ship'];
                $comissao_frete = $value['total_ship'] * ($value['percentual_produto'] / 100);
                $valor_frete = $value['total_ship'];
                $reducao_comissao = $value['comission_reduction'];
                $cupons = $value['total_pricetags'];
                $campanha_mkt_paga = $value['total_channel'];
                $valor_pedido = $value['total_order'];

                $campaigns_comission_reduction_products = $value['comission_reduction_products'];
                $setting_api_comission = $this->model_settings->getSettingDatabyName('api_comission');
                $setting_api_comission = $setting_api_comission['status'];
                if ($setting_api_comission != "1") {
                    $reembolso = abs($valor_repasse - $value['valor_repasse']);
                } else {
                    $reembolso = abs($valor_repasse - $value['valor_repasse'] - $campaigns_comission_reduction_products);
                }

                $service_charge_freight_value = $value['service_charge_freight_value'];
                if($value['service_charge_freight_value'] == 100){
                    $service_charge_freight_value = 0;
                }

                if ($value['tipo_frete'] == 0) {
                    // sellercenter
                    $valor_comissao = (((($value['service_charge_rate']/100) * $value['total_order']) + (($service_charge_freight_value/100) * $value['total_ship'])) - $campaigns_comission_reduction + ($cupons + ($campaigns_mktplace) * ((($value['service_charge_rate']/100)))));

                    $expectativa_recebimento = ($value['gross_amount'] - ((($value['service_charge_rate']/100) * $value['total_order']) + (($service_charge_freight_value/100) * $value['total_ship'])) + $reembolso + ($cupons - ($cupons * ($value['service_charge_rate']/100))) - $value['total_ship']);
                } else {
                    // lojista
                    $valor_comissao = (((($value['service_charge_rate']/100) * $value['total_order']) + (($service_charge_freight_value/100) * $value['total_ship'])) - $campaigns_comission_reduction + ($cupons + ($campaigns_mktplace) * ((($value['service_charge_rate']/100)))));

                    $expectativa_recebimento = ($value['gross_amount'] - ((($value['service_charge_rate']/100) * $value['total_order']) + (($service_charge_freight_value/100) * $value['total_ship'])) + $reembolso + ($cupons - ($cupons * ($value['service_charge_rate']/100))));
                }
            }
            // Thiago // FIN-46

            $show_new_columns_fin_46_temp = $this->model_settings->getStatusbyName('show_new_columns_fin_46_temp');

            $result['data'][$key] = array(
                $value['id'],
                $value['marketplace'],
                $value['numero_pedido'],
                $status,
                $value['date_time'],
                $value['data_entrega'],
                $value['data_pagamento_mktplace'],
                $value['data_pagamento_conectala'],
                $value['data_recebimento_mktpl'],
                $value['data_caiu_na_conta'],
                $value['pago'],
                $value['data_transferencia'],
                "<b>R$ ".$valoPedido."</b>",
                "<b>R$ ".$valorProduto."</b>",
                "<b>R$ ".$valorFrete."</b>",
                "<b>R$ ".$expectativaReceb."</b>",
                "<b>R$ ".$valor_parceiro."</b>",
                $observacao,
                $value['nome_loja'],
                "<b>R$ ".$comissao_descontada."</b>",
                $value['raz_social'],
                $value['erp_customer_supplier_code'],
                $value['id_loja'],
                $percentualFrete,
                "<b>R$ ".$calc_comissao_descontada."</b>",
                "<b>R$ ".$imposto_renda_comissao_descontada."</b>",
                "<b>R$ ".$total_comissao_descontada."</b>",
                $percentualProduto,
                "<b>R$ ".$campaigns_pricetags."</b>", //28
                "<b>R$ ".$campaigns_campaigns."</b>",
                "<b>R$ ".$campaigns_mktplace."</b>",
                "<b>R$ ".$campaigns_seller."</b>",
                "<b>R$ ".$campaigns_promotions."</b>",
                "<b>R$ ".$campaigns_comission_reduction."</b>",
                "<b>R$ ".$campaigns_rebate."</b>",
                "<b>R$ ".$refund."</b>",

                // "<b>R$ ".$campaigns_comissionreduxchannel."</b>",
                // "<b>R$ ".$campaigns_rebatechannel."</b>",
                // "<b>R$ ".$campaigns_channelrefund."</b>"
            );

            if($show_new_columns_fin_46 == "1") {
                array_push($result['data'][$key], "<b>".$tipo_de_frete."</b>");
                if($show_new_columns_fin_46_temp == "1") {
                    array_push($result['data'][$key], "<b>".money($valor_comissao)."</b>");
                    array_push($result['data'][$key], "<b>".money($expectativa_recebimento)."</b>");
                }
                array_push($result['data'][$key], "<b>".money($valor_recebido)."</b>");
            }

        } // /foreach
        // 23 26 27

        ob_end_clean();

        if ($valor['status'] == "1")
        {
            echo utf8_decode( "<table border=\"1\">
                <tr>
                    <th colspan=\"8\"></th>
                    <th colspan=\"1\">PREVISTO (Considerando as previsões de entrega do pedido e o ciclo de pagamento correspondente)</th>
                    <th colspan=\"1\">PAGAMENTO AO SELLER (Informa se o Pedido foi \"Pago ou Não Pago\" (Pago  = Repasse Realizado ao Lojista) e em que data foi realizada a transferência bancária)</th>	
                    <th colspan=\"7\"></th>
                </tr>
                <tr>
                    <th>".$this->lang->line('application_id')." - Pedido</th>
                    <th>Marca</th>
                    <th>Clifor</th>
                    <th>".$this->lang->line('application_store')."</th>
                    <th>Razão Social</th>
                    <th>".$this->lang->line('application_purchase_id')."</th>
                    <th>".$this->lang->line('application_status')."</th>
                    <th>".$this->lang->line('application_date')." Pedido</th>
                    <th>".$this->lang->line('application_date')." de Entrega</th>
                    <th>".$this->lang->line('application_payment_date')." Marca</th>

                    ".($show_new_columns_fin_46 == "1" ?
                    "<th>Tipo de frete</th>
                            ".($show_new_columns_fin_46_temp == "1" ? "<th>Valor comissão</th>
                            <th>Expectativa de recebimento</th>" : "")."
                            <th>Valor recebido</th>" : "")."

                    ".($data_transferencia_gateway_extrato == "1" ? "<th>Data Transferência Bancária Gateway</th>" : "")."
                    <th>".$this->lang->line('application_order_2')."</th>
                    <th>".$this->lang->line('application_purchase_total')."</th>
                    <th>".$this->lang->line('application_value_products')."</th>
                    <th>".$this->lang->line('application_ship_value')."</th>
                    <th>Expectativa Recebimento</th>
                    <th>Comissão</th>                    
                    ".($valor_pagamento_gateway_extrato == "1" ? "<th>Valor Real Repasse</th>" : "")."
                    <th>".$this->lang->line('application_extract_obs')."</th>
               </tr>");

            foreach ($result['data'] as $value)
            {
                echo utf8_decode( "<tr>");
                echo utf8_decode("<td>".$value[0]."</td>");
                echo utf8_decode("<td>".$value[1]."</td>");
                echo utf8_decode("<td>".$value[21]."</td>");
                echo utf8_decode("<td>".$value[18]."</td>");
                echo utf8_decode("<td>".$value[20]."</td>");
                echo utf8_decode("<td>".$value[2]."</td>");
                echo utf8_decode("<td>".$value[3]."</td>");
                echo utf8_decode("<td>".$value[4]."</td>");
                echo utf8_decode("<td>".$value[5]."</td>");
                echo utf8_decode("<td>".$value[6]."</td>");

                if($show_new_columns_fin_46 == "1") {
                    $index = 37;
                    echo utf8_decode("<td>" . $value[36] . "</td>");
                    if($show_new_columns_fin_46_temp == "1") {
                        echo utf8_decode("<td>" . $value[37] . "</td>");
                        echo utf8_decode("<td>" . $value[38] . "</td>");
                        $index = 39;
                    }
                    echo utf8_decode("<td>" . $value[$index] . "</td>");
                }

                if ($data_transferencia_gateway_extrato == "1"){
                    echo utf8_decode("<td>".$transferencia_gateway_extrato."</td>");
                }
                echo utf8_decode("<td>".$value[10]."</td>");
                echo utf8_decode("<td>".$value[12]."</td>");
                echo utf8_decode("<td>".$value[13]."</td>");
                echo utf8_decode("<td>".$value[14]."</td>");
                echo utf8_decode("<td>".str_replace("ã","a",$value[15])."</td>");
                echo utf8_decode("<td>".$value[19]."</td>");
                if ($valor_pagamento_gateway_extrato == "1"){
                    echo utf8_decode("<td>".$pagamento_gateway_extrato."</td>");
                }
                echo utf8_decode("<td>".$value[17]."</td>");
                echo utf8_decode( "</tr>");
            }

            echo "</table>";

        }
        else if(isset($valorNM['status']) && $valorNM['status'] == "1")
        {
            echo utf8_decode( "<table border=\"1\">
                            <!-- <tr>
                                <th colspan=\"7\"></th>
                                <th colspan=\"1\">PREVISTO (Considerando as previsões de entrega do pedido e o ciclo de pagamento correspondente)</th>
                                <th colspan=\"1\">PAGAMENTO AO SELLER (Informa se o Pedido foi \"Pago ou Não Pago\" (Pago  = Repasse Realizado ao Lojista) e em que data foi realizada a transferência bancária)</th>	
                                <th colspan=\"7\"></th>
                            </tr> -->
                            <tr>
                                <th>".$this->lang->line('application_quotation_id')." - ".$this->lang->line('application_store')."</th>
                                <th>".$this->lang->line('application_store')."</th>
                                <th>Pedido Novo Mundo</th>
                                <th>Pedido Seller</th>
                                <th>".$this->lang->line('application_category')."</th>
                                <th>".$this->lang->line('application_status')."</th>
                                <th>".$this->lang->line('application_date')." ".$this->lang->line('application_order')."</th> 
                                <th>".$this->lang->line('application_date')." de Entrega</th>
                                <th>".$this->lang->line('application_payment_date')."</th>

                                ".($show_new_columns_fin_46 == "1" ?
                    "<th>Tipo de frete</th>
                                ".($show_new_columns_fin_46_temp == "1" ? "<th>Valor comissão</th>
                                <th>Expectativa de recebimento</th>" : "")."
                                <th>Valor recebido</th>" : "")."

                                ".($data_transferencia_gateway_extrato == "1" ? "<th>Data Transferência Bancária Gateway</th>" : "")."
                                <th>".$this->lang->line('application_Paid')."</th>
                                <th>".$this->lang->line('application_parameter_mktplace_value_ciclo')."</th>
                                <th>".$this->lang->line('application_purchase_total')."</th>
                                <th>".$this->lang->line('application_value_products')."</th>
                                <th>Valor Serviço</th>
                                <th>".$this->lang->line('application_ship_value')."</th>
                                <th>".$this->lang->line('application_charge_amount')."</th>
                                <th>Comissão sobre o Serviço (%)</th>
                                <th>".$this->lang->line('application_charge_amount_freight')."</th>
                                <th>".$this->lang->line('application_total')." ".$this->lang->line('application_commission')."</th>
                                <th>Imposto de Renda</th>
                                <th>Total do repasse</th>                                
                                <th>".$this->lang->line('application_campaigns_pricetags')."</th>
                                <th>".$this->lang->line('application_campaigns_campaigns')."</th>
                                <th>".$this->lang->line('application_campaigns_marketplace')."</th>
                                <th>".$this->lang->line('application_campaigns_seller')."</th>
                                <th>".$this->lang->line('application_campaigns_promotions')."</th>
                                <th>".$this->lang->line('application_campaigns_comission_reduction')."</th>
                                <th>".$this->lang->line('application_campaigns_rebate')."</th>
                                <th>".$this->lang->line('application_campaigns_refund')."</th>
                                ".($valor_pagamento_gateway_extrato == "1" ? "<th>Valor Real Repasse</th>" : "")."
                                <th>".$this->lang->line('application_extract_obs')."</th>
                        </tr>");

            foreach ($result['data'] as $value)
            {
                echo utf8_decode( "<tr>");
                echo utf8_decode("<td>".$value[22]."</td>");
                echo utf8_decode("<td>".$value[18]."</td>");
                echo utf8_decode("<td></td>");
                echo utf8_decode("<td>".$value[2]."</td>");
                echo utf8_decode("<td></td>");
                echo utf8_decode("<td>".$value[3]."</td>");
                echo utf8_decode("<td>".$value[4]."</td>");
                echo utf8_decode("<td>".$value[5]."</td>");

                if($show_new_columns_fin_46 == "1") {
                    $index = 37;
                    echo utf8_decode("<td>" . $value[36] . "</td>");
                    if($show_new_columns_fin_46_temp == "1") {
                        echo utf8_decode("<td>" . $value[37] . "</td>");
                        echo utf8_decode("<td>" . $value[38] . "</td>");
                        $index = 39;
                    }
                    echo utf8_decode("<td>" . $value[$index] . "</td>");
                }

                if ($data_transferencia_gateway_extrato == "1"){
                    echo utf8_decode("<td>".$transferencia_gateway_extrato."</td>");
                }
                echo utf8_decode("<td>".$value[6]."</td>");
                echo utf8_decode("<td>".$value[10]."</td>");
                echo utf8_decode("<td></td>");
                echo utf8_decode("<td>".$value[12]."</td>");
                echo utf8_decode("<td>".$value[13]."</td>");
                echo utf8_decode("<td></td>");
                echo utf8_decode("<td>".$value[14]."</td>");
                echo utf8_decode("<td>".$value[27]."</td>");
                echo utf8_decode("<td></td>");
                echo utf8_decode("<td>".$value[23]."</td>");
                echo utf8_decode("<td>".$value[24]."</td>");
                echo utf8_decode("<td>".$value[25]."</td>");
                echo utf8_decode("<td>".$value[26]."</td>");
                echo utf8_decode("<td>".$value[28]."</td>");
                echo utf8_decode("<td>".$value[29]."</td>");
                echo utf8_decode("<td>".$value[30]."</td>");
                echo utf8_decode("<td>".$value[31]."</td>");
                echo utf8_decode("<td>".$value[32]."</td>");
                echo utf8_decode("<td>".$value[33]."</td>");
                echo utf8_decode("<td>".$value[34]."</td>");
                echo utf8_decode("<td>".$value[35]."</td>");
                if ($valor_pagamento_gateway_extrato == "1"){
                    echo utf8_decode("<td>".$pagamento_gateway_extrato."</td>");
                }
                echo utf8_decode("<td>".$value[17]."</td>");
                echo utf8_decode( "</tr>");
            }

            echo "</table>";

        }
        else if(isset($valorORTB['status']) && $valorORTB['status'] == "1")
        {
            echo utf8_decode( "<table border=\"1\">
                            <!-- <tr>
                                <th colspan=\"6\"></th>
                                <th colspan=\"1\">PREVISTO (Considerando as previsões de entrega do pedido e o ciclo de pagamento correspondente)</th>
                                <th colspan=\"1\">PAGAMENTO AO SELLER (Informa se o Pedido foi \"Pago ou Não Pago\" (Pago  = Repasse Realizado ao Lojista) e em que data foi realizada a transferência bancária)</th>	
                                <th colspan=\"7\"></th>
                            </tr> -->
                            <tr>
                                <th>".$this->lang->line('application_quotation_id')." - ".$this->lang->line('application_store')."</th>
                                <th>".$this->lang->line('application_store')."</th>
                                <th>Pedido Seller</th>
                                <th>".$this->lang->line('application_category')."</th>
                                <th>".$this->lang->line('application_status')."</th>
                                <th>".$this->lang->line('application_date')." ".$this->lang->line('application_order')."</th> 
                                <th>".$this->lang->line('application_date')." de Entrega</th>
                                <th>".$this->lang->line('application_payment_date')."</th>
                                ".($data_transferencia_gateway_extrato == "1" ? "<th>Data Transferência Bancária Gateway</th>" : "")."
                                <th>".$this->lang->line('application_Paid')."</th>
                                <th>".$this->lang->line('application_parameter_mktplace_value_ciclo')."</th>
                                <th>".$this->lang->line('application_purchase_total')."</th>
                                <th>".$this->lang->line('application_value_products')."</th>
                                <th>Valor Serviço</th>
                                <th>".$this->lang->line('application_ship_value')."</th>
                                <th>".$this->lang->line('application_charge_amount')."</th>
                                <th>Comissão sobre o Serviço (%)</th>
                                <th>".$this->lang->line('application_charge_amount_freight')."</th>

                                ".($show_new_columns_fin_46 == "1" ?
                    "<th>Tipo de frete</th>
                                ".($show_new_columns_fin_46_temp == "1" ? "<th>Valor comissão</th>
                                <th>Expectativa de recebimento</th>" : "")."
                                <th>Valor recebido</th>" : "")."

                                <th>".$this->lang->line('application_total')." ".$this->lang->line('application_commission')."</th>
                                <th>Imposto de Renda</th>
                                <th>Total do repasse</th>                                
                                <th>".$this->lang->line('application_campaigns_pricetags')."</th>
                                <th>".$this->lang->line('application_campaigns_campaigns')."</th>
                                <th>".$this->lang->line('application_campaigns_marketplace')."</th>
                                <th>".$this->lang->line('application_campaigns_seller')."</th>
                                <th>".$this->lang->line('application_campaigns_promotions')."</th>
                                <th>".$this->lang->line('application_campaigns_comission_reduction')."</th>
                                <th>".$this->lang->line('application_campaigns_rebate')."</th>
                                <th>".$this->lang->line('application_campaigns_refund')."</th>
                                ".($valor_pagamento_gateway_extrato == "1" ? "<th>Valor Real Repasse</th>" : "")."
                                <th>".$this->lang->line('application_extract_obs')."</th>
                        </tr>");

            foreach ($result['data'] as $value)
            {
                echo utf8_decode( "<tr>");
                echo utf8_decode("<td>".$value[22]."</td>");
                echo utf8_decode("<td>".$value[18]."</td>");
                echo utf8_decode("<td>".$value[2]."</td>");
                echo utf8_decode("<td></td>");
                echo utf8_decode("<td>".$value[3]."</td>");
                echo utf8_decode("<td>".$value[4]."</td>");
                echo utf8_decode("<td>".$value[5]."</td>");
                echo utf8_decode("<td>".$value[6]."</td>");
                if ($data_transferencia_gateway_extrato == "1"){
                    echo utf8_decode("<td>".$transferencia_gateway_extrato."</td>");
                }
                echo utf8_decode("<td>".$value[10]."</td>");
                echo utf8_decode("<td></td>");
                echo utf8_decode("<td>".$value[12]."</td>");
                echo utf8_decode("<td>".$value[13]."</td>");
                echo utf8_decode("<td></td>");
                echo utf8_decode("<td>".$value[14]."</td>");
                echo utf8_decode("<td>".$value[27]."</td>");
                echo utf8_decode("<td></td>");
                echo utf8_decode("<td>".$value[23]."</td>");

                if($show_new_columns_fin_46 == "1") {
                    $index = 37;
                    echo utf8_decode("<td>" . $value[36] . "</td>");
                    if($show_new_columns_fin_46_temp == "1") {
                        echo utf8_decode("<td>" . $value[37] . "</td>");
                        echo utf8_decode("<td>" . $value[38] . "</td>");
                        $index = 39;
                    }
                    echo utf8_decode("<td>" . $value[$index] . "</td>");
                }

                echo utf8_decode("<td>".$value[24]."</td>");
                echo utf8_decode("<td>".$value[25]."</td>");
                echo utf8_decode("<td>".$value[15]."</td>");
                echo utf8_decode("<td>".$value[28]."</td>");
                echo utf8_decode("<td>".$value[29]."</td>");
                echo utf8_decode("<td>".$value[30]."</td>");
                echo utf8_decode("<td>".$value[31]."</td>");
                echo utf8_decode("<td>".$value[32]."</td>");
                echo utf8_decode("<td>".$value[33]."</td>");
                echo utf8_decode("<td>".$value[34]."</td>");
                echo utf8_decode("<td>".$value[35]."</td>");
                if ($valor_pagamento_gateway_extrato == "1"){
                    echo utf8_decode("<td>".$pagamento_gateway_extrato."</td>");
                }
                echo utf8_decode("<td>".$value[17]."</td>");
                echo utf8_decode( "</tr>");
            }

            echo "</table>";

        }
        else if($valorSellercenter['status'] == "1")
        {
            echo utf8_decode( "<table border=\"1\">
                            <!-- <tr>
                                <th colspan=\"6\"></th>
                                <th colspan=\"1\">PREVISTO (Considerando as previsões de entrega do pedido e o ciclo de pagamento correspondente)</th>
                                <th colspan=\"1\">PAGAMENTO AO SELLER (Informa se o Pedido foi \"Pago ou Não Pago\" (Pago  = Repasse Realizado ao Lojista) e em que data foi realizada a transferência bancária)</th>	
                                <th colspan=\"7\"></th>
                            </tr> -->
                            <tr>
                                <th>".$this->lang->line('application_quotation_id')." - ".$this->lang->line('application_store')."</th>
                                <th>".$this->lang->line('application_store')."</th>
                                <th>".$this->lang->line('application_numero_marketlace')."</th>
                                <th>".$this->lang->line('application_category')."</th>
                                <th>".$this->lang->line('application_status')."</th>
                                <th>".$this->lang->line('application_date')." ".$this->lang->line('application_order')."</th> 
                                <th>".$this->lang->line('application_date')." de Entrega</th>
                                <th>".$this->lang->line('application_payment_date')."</th>
                                ".($data_transferencia_gateway_extrato == "1" ? "<th>Data Transferência Bancária Gateway</th>" : "")."");
            if($dataRepasseSellerCenter['status'] == "1"){
                echo utf8_decode( "<th>" . $this->lang->line('application_date') . " " . $this->lang->line('application_bank_transfer') . "</th>");
            }

            echo utf8_decode( "     <th>".$this->lang->line('application_Paid')."</th>
                                <th>".$this->lang->line('application_parameter_mktplace_value_ciclo')."</th>
                                <th>".$this->lang->line('application_purchase_total')."</th>
                                <th>".$this->lang->line('application_value_products')."</th>
                                <th>".$this->lang->line('application_service_value')."</th>
                                <th>".$this->lang->line('application_ship_value')."</th>
                                <th>".$this->lang->line('application_charge_amount')."</th>
                                <th>".$this->lang->line('application_service_charge_amount')."</th>
                                <th>".$this->lang->line('application_charge_amount_freight')."</th>
                                <th>".$this->lang->line('application_total')." ".$this->lang->line('application_commission')."</th>
                                <th>".$this->lang->line('application_ir_value')."</th>
                                <th>".$this->lang->line('application_total_to_transfer')."</th> ");

            if($valorRealRepasseSellerCenter['status'] == "1"){
                echo utf8_decode( "<th>" . $this->lang->line('application_total_to_transfer') . " Real </th>");
            }



            echo utf8_decode( "      ".($show_new_columns_fin_46 == "1" ?
                    "<th>Tipo de frete</th>
                                ".($show_new_columns_fin_46_temp == "1" ? "<th>Valor comissão</th>
                                <th>Expectativa de recebimento</th>" : "")."
                                <th>Valor recebido</th>" : "")."

                                <th>".$this->lang->line('application_campaigns_pricetags')."</th>
                                <th>".$this->lang->line('application_campaigns_campaigns')."</th>
                                <th>".$this->lang->line('application_campaigns_marketplace')."</th>
                                <th>".$this->lang->line('application_campaigns_seller')."</th>
                                <th>".$this->lang->line('application_campaigns_promotions')."</th>
                                <th>".$this->lang->line('application_campaigns_comission_reduction')."</th>
                                <th>".$this->lang->line('application_campaigns_rebate')."</th>
                                <th>".$this->lang->line('application_campaigns_refund')."</th>
                                ".($valor_pagamento_gateway_extrato == "1" ? "<th>Valor Real Repasse</th>" : "")."
                                <th>".$this->lang->line('application_extract_obs')."</th>
                        </tr>");

            foreach ($result['data'] as $value)
            {
                echo utf8_decode( "<tr>");
                echo utf8_decode("<td>".$value[22]."</td>");
                echo utf8_decode("<td>".$value[18]."</td>");
                echo utf8_decode("<td>".$value[2]."</td>");
                echo utf8_decode("<td></td>");
                echo utf8_decode("<td>".$value[3]."</td>");
                echo utf8_decode("<td>".$value[4]."</td>");
                echo utf8_decode("<td>".$value[5]."</td>");
                echo utf8_decode("<td>".$value[6]."</td>");
                if ($data_transferencia_gateway_extrato == "1"){
                    echo utf8_decode("<td>".$transferencia_gateway_extrato."</td>");
                }
                if($dataRepasseSellerCenter['status'] == "1"){
                    echo utf8_decode("<td>".$value[11]."</td>");
                }
                echo utf8_decode("<td>".$value[10]."</td>");
                echo utf8_decode("<td></td>");
                echo utf8_decode("<td>".$value[12]."</td>");
                echo utf8_decode("<td>".$value[13]."</td>");
                echo utf8_decode("<td></td>");
                echo utf8_decode("<td>".$value[14]."</td>");
                echo utf8_decode("<td>".$value[27]."</td>");
                echo utf8_decode("<td></td>");
                echo utf8_decode("<td>".$value[23]."</td>");
                echo utf8_decode("<td>".$value[24]."</td>");
                echo utf8_decode("<td>".$value[25]."</td>");
                echo utf8_decode("<td>".$value[15]."</td>");
                if($valorRealRepasseSellerCenter['status'] == "1"){
                    echo utf8_decode("<td>".$value[16]."</td>");
                }

                if($show_new_columns_fin_46 == "1") {
                    $index = 37;
                    echo utf8_decode("<td>" . $value[36] . "</td>");
                    if($show_new_columns_fin_46_temp == "1") {
                        echo utf8_decode("<td>" . $value[37] . "</td>");
                        echo utf8_decode("<td>" . $value[38] . "</td>");
                        $index = 39;
                    }
                    echo utf8_decode("<td>" . $value[$index] . "</td>");
                }

                echo utf8_decode("<td>".$value[28]."</td>");
                echo utf8_decode("<td>".$value[29]."</td>");
                echo utf8_decode("<td>".$value[30]."</td>");
                echo utf8_decode("<td>".$value[31]."</td>");
                echo utf8_decode("<td>".$value[32]."</td>");
                echo utf8_decode("<td>".$value[33]."</td>");
                echo utf8_decode("<td>".$value[34]."</td>");
                echo utf8_decode("<td>".$value[35]."</td>");
                if ($valor_pagamento_gateway_extrato == "1"){
                    echo utf8_decode("<td>".$pagamento_gateway_extrato."</td>");
                }
                echo utf8_decode("<td>".$value[17]."</td>");
                echo utf8_decode( "</tr>");
            }

            echo "</table>";

        }
        else
        {
            if (array_key_exists("extratonovo", $inputs))
            {
                echo utf8_decode("<table border=\"1\">
                    <tr>
                        <th colspan=\"7\"></th>
                        <th colspan=\"2\">REALIZADO (Considerando todos os pedidos que foram pagos pelos marketplaces e passaram pelo processo de conciliação)</th>
                        <th colspan=\"2\">PAGAMENTO AO SELLER (Informa se o Pedido foi \"Pago ou Não Pago\" (Pago  = Repasse Realizado ao Lojista) e em que data foi realizada a transferência bancária)</th>	
                        <th colspan=\"5\"></th>
                        
                    </tr>
                    <tr>
                        <th>" . $this->lang->line('application_id') . " - Pedido</th>
                        <th>" . $this->lang->line('application_store') . "</th>
                        <th>" . $this->lang->line('application_marketplace') . "</th>
                        <th>" . $this->lang->line('application_purchase_id') . "</th> 
                        <th>" . $this->lang->line('application_status') . "</th>
                        <th>" . $this->lang->line('application_date') . " Pedido</th>
                        <th>" . $this->lang->line('application_date') . " de Entrega</th>
                        <th>Data que recebemos o repasse</th>
                        <th>Data em que pagamos</th>
                        <th>" . $this->lang->line('application_order_2') . "</th>
                        <th>" . $this->lang->line('application_date') . " " . $this->lang->line('application_bank_transfer') . "</th>
                        <th>" . $this->lang->line('application_purchase_total') . "</th>
                        <th>" . $this->lang->line('application_value_products') . "</th>
                        <th>" . $this->lang->line('application_ship_value') . "</th>

                        ".($show_new_columns_fin_46 == "1" ?
                        "<th>Tipo de frete</th>
                            ".($show_new_columns_fin_46_temp == "1" ? "<th>Valor comissão</th>
                            <th>Expectativa de recebimento</th>" : "")."
                            <th>Valor recebido</th>" : "")."

                        <th>Valor pago pelo Marketplace</th>
                        <th>" . $this->lang->line('application_extract_obs') . "</th>
                </tr>");
                // <th>Pedido Conciliado</th>
                foreach ($result['data'] as $value)
                {
                    echo utf8_decode("<tr>");
                    echo utf8_decode("<td>".$value[0]."</td>");
                    echo utf8_decode("<td>".$value[18]."</td>");
                    echo utf8_decode("<td>".$value[1]."</td>");
                    echo utf8_decode("<td>".$value[2]."</td>");
                    echo utf8_decode("<td>".$value[3]."</td>");
                    echo utf8_decode("<td>".$value[4]."</td>");
                    echo utf8_decode("<td>".$value[5]."</td>");
                    echo utf8_decode("<td>".$value[8]."</td>");
                    echo utf8_decode("<td>".str_replace("ã","a",$value[9])."</td>");
                    echo utf8_decode("<td>".$value[10]."</td>");
                    echo utf8_decode("<td>".$value[11]."</td>");
                    echo utf8_decode("<td>".$value[12]."</td>");
                    echo utf8_decode("<td>".$value[13]."</td>");
                    echo utf8_decode("<td>".$value[14]."</td>");

                    if($show_new_columns_fin_46 == "1") {
                        $index = 37;
                        echo utf8_decode("<td>" . $value[36] . "</td>");
                        if($show_new_columns_fin_46_temp == "1") {
                            echo utf8_decode("<td>" . $value[37] . "</td>");
                            echo utf8_decode("<td>" . $value[38] . "</td>");
                            $index = 39;
                        }
                        echo utf8_decode("<td>" . $value[$index] . "</td>");
                    }

                    echo utf8_decode("<td>".$value[16]."</td>");
                    echo utf8_decode("<td>".$value[17]."</td>");
                    echo utf8_decode("</tr>");
                }

                echo "</table>";

            }
            else
            {
                $negociacao_marketplace_campanha = '';
                $colspan = 8;

                // if ($this->negociacao_marketplace_campanha)
                // {
                //     $negociacao_marketplace_campanha = "
                //         <th>".$this->lang->line('conciliation_sc_gridok_comissionreduxchannel')."</th>
                //         <th>".$this->lang->line('conciliation_sc_gridok_rebatechannel')."</th>
                //         <th>".$this->lang->line('conciliation_sc_gridok_channelrefund')."</th>";

                //     $colspan = 11;
                // }


                echo utf8_decode("<table border=\"1\">
                    <tr>
                        <th colspan=\"7\"></th>
                        <th colspan=\"2\">PREVISTO (Considerando as previsões de entrega do pedido e o ciclo de pagamento correspondente)</th>
                        <th colspan=\"2\">REALIZADO (Considerando todos os pedidos que foram pagos pelos marketplaces e passaram pelo processo de conciliação)</th>
                        <th colspan=\"2\">PAGAMENTO AO SELLER (Informa se o Pedido foi \"Pago ou Não Pago\" (Pago  = Repasse Realizado ao Lojista) e em que data foi realizada a transferência bancária)</th>	
                        <th colspan=\"5\"></th>
                        <th colspan=\"".$colspan."\">".$this->lang->line('conciliation_sc_gridok_campaigns')."</th>
                        
                    </tr>
                    <tr>
                        <th>" . $this->lang->line('application_id') . " - Pedido</th>
                        <th>" . $this->lang->line('application_store') . "</th>
                        <th>" . $this->lang->line('application_marketplace') . "</th>
                        <th>" . $this->lang->line('application_purchase_id') . "</th> 
                        <th>" . $this->lang->line('application_status') . "</th>
                        <th>" . $this->lang->line('application_date') . " Pedido</th>
                        <th>" . $this->lang->line('application_date') . " de Entrega</th>
                        <th>" . $this->lang->line('application_payment_date') . " Marketplace</th>
                        <th>" . $this->lang->line('application_payment_date_conecta') . "</th>
                        <th>Data que recebemos o repasse</th>
                        <th>Data em que pagamos</th>
                        <th>" . $this->lang->line('application_order_2') . "</th>
                        <th>" . $this->lang->line('application_date') . " " . $this->lang->line('application_bank_transfer') . "</th>
                        ".($data_transferencia_gateway_extrato == "1" ? "<th>Data Transferência Bancária Gateway</th>" : "")."  
                        <th>" . $this->lang->line('application_purchase_total') . "</th>
                        <th>" . $this->lang->line('application_value_products') . "</th>
                        <th>" . $this->lang->line('application_ship_value') . "</th>
                        <th>Expectativa Recebimento</th>
                        <th>Valor pago pelo Marketplace</th>
                        
                        ".($show_new_columns_fin_46 == "1" ?
                        "<th>Tipo de frete</th>
                            ".($show_new_columns_fin_46_temp == "1" ? "<th>Valor comissão</th>
                            <th>Expectativa de recebimento</th>" : "")."
                            <th>Valor recebido</th>" : "")."
                        
                        <th>".$this->lang->line('application_campaigns_pricetags')."</th>
                        <th>".$this->lang->line('application_campaigns_campaigns')."</th>
                        <th>".$this->lang->line('application_campaigns_marketplace')."</th>
                        <th>".$this->lang->line('application_campaigns_seller')."</th>
                        <th>".$this->lang->line('application_campaigns_promotions')."</th>
                        <th>".$this->lang->line('application_campaigns_comission_reduction')."</th>
                        <th>".$this->lang->line('application_campaigns_rebate')."</th>
                        <th>".$this->lang->line('application_campaigns_refund')."</th>
                        ".$negociacao_marketplace_campanha."
                        ".($valor_pagamento_gateway_extrato == "1" ? "<th>Valor Real Repasse</th>" : "")."
                        <th>" . $this->lang->line('application_extract_obs') . "</th>
                </tr>");
                // <th>Pedido Conciliado</th>
                foreach ($result['data'] as $value)
                {
                    echo utf8_decode("<tr>");
                    echo utf8_decode("<td>".$value[0]."</td>");
                    echo utf8_decode("<td>".$value[18]."</td>");
                    echo utf8_decode("<td>".$value[1]."</td>");
                    echo utf8_decode("<td>".$value[2]."</td>");
                    echo utf8_decode("<td>".$value[3]."</td>");
                    echo utf8_decode("<td>".$value[4]."</td>");
                    echo utf8_decode("<td>".$value[5]."</td>");
                    echo utf8_decode("<td>".$value[6]."</td>");
                    echo utf8_decode("<td>".$value[7]."</td>");
                    echo utf8_decode("<td>".$value[8]."</td>");
                    echo utf8_decode("<td>".str_replace("ã","a",$value[9])."</td>");
                    echo utf8_decode("<td>".$value[10]."</td>");
                    echo utf8_decode("<td>".$value[11]."</td>");
                    if ($data_transferencia_gateway_extrato == "1"){
                        echo utf8_decode("<td>".$transferencia_gateway_extrato."</td>");
                    }
                    echo utf8_decode("<td>".$value[12]."</td>");
                    echo utf8_decode("<td>".$value[13]."</td>");
                    echo utf8_decode("<td>".$value[14]."</td>");
                    echo utf8_decode("<td>".str_replace("ã","a",$value[15])."</td>");
                    echo utf8_decode("<td>".$value[16]."</td>");

                    if($show_new_columns_fin_46 == "1") {
                        $index = 37;
                        echo utf8_decode("<td>" . $value[36] . "</td>");
                        if($show_new_columns_fin_46_temp == "1") {
                            echo utf8_decode("<td>" . $value[37] . "</td>");
                            echo utf8_decode("<td>" . $value[38] . "</td>");
                            $index = 39;
                        }
                        echo utf8_decode("<td>" . $value[$index] . "</td>");
                    }

                    echo utf8_decode("<td>".$value[28]."</td>");
                    echo utf8_decode("<td>".$value[29]."</td>");
                    echo utf8_decode("<td>".$value[30]."</td>");
                    echo utf8_decode("<td>".$value[31]."</td>");
                    echo utf8_decode("<td>".$value[32]."</td>");
                    echo utf8_decode("<td>".$value[33]."</td>");
                    echo utf8_decode("<td>".$value[34]."</td>");
                    echo utf8_decode("<td>".$value[35]."</td>");
                    echo utf8_decode("<td></td>");
                    // if ($this->negociacao_marketplace_campanha)
                    // {
                    //     echo utf8_decode("<td>".$value[36]."</td>");
                    //     echo utf8_decode("<td>".$value[37]."</td>");
                    //     echo utf8_decode("<td>".$value[38]."</td>");
                    // }
                    if ($valor_pagamento_gateway_extrato == "1"){
                        echo utf8_decode("<td>".$pagamento_gateway_extrato."</td>");
                    }
                    echo utf8_decode("<td>".$value[17]."</td>");
                    echo utf8_decode("</tr>");
                }

                echo "</table>";
            }
        }
    }

    public function extratopedidosexceltestenovo()
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');

        $this->load->model('model_campaigns_v2');

        $valor = $this->model_settings->getSettingDatabyNameEmptyArray('gsoma_painel_financeiro');
        $valorNM = $this->model_settings->getSettingDatabyNameEmptyArray('novomundo_painel_financeiro');
        $valorORTB = $this->model_settings->getSettingDatabyNameEmptyArray('ortobom_painel_financeiro');
        $valorSellercenter = $this->model_settings->getSettingDatabyName('sellercenter');

        $dataRepasseSellerCenter = $this->model_settings->getSettingDatabyName('painel_financeiro_data_repasse_sellercenter');

        if(!$dataRepasseSellerCenter){
            $dataRepasseSellerCenter['status'] == "0";
        }

        if ($valorSellercenter['value'] <> "conectala"){
            $valorSellercenter['status'] = 1;
        }else{
            $valorSellercenter['status'] = 0;
        }

        $result = array('data' => array());

        $inputs = $this->input;
        $post = $inputs->post();
        $inputs = $inputs->get();

        if ($inputs['slc_status'] <> "")
        {
            $statusFiltro = array();
            $inputs['slc_status'] = explode(',', $inputs['slc_status']);
            $j = 0;

            foreach ($inputs['slc_status'] as $item)
            {
                //Busca todos os status pelo selecionado na tela
                $statusTela = $this->model_iugu->statuspedido($item);

                for ($i = 0; $i <= 101; $i++)
                {
                    $status = $this->model_iugu->statuspedido($i);

                    if ($status <> false)
                    {
                        if ($status == $statusTela)
                        {
                            $statusFiltro[$j] = $i;
                            $j++;
                        }
                    }
                }
            }

            $filtroFinal = implode(",", $statusFiltro);
            $inputs['slc_status'] = $filtroFinal;
        }

        $data = $this->model_billet->getPedidosExtratoConciliado($inputs, null, $post,"excel");

        foreach ($data as $key => $value)
        {
            //$campaigns_data                 = $this->model_campaigns_v2->getCampaignsTotalsByOrderId($value['id']);

            $campaigns_pricetags            = (!empty($value)) ? $value['total_pricetags'] : 0;
            $campaigns_campaigns            = (!empty($value)) ? $value['total_campaigns'] : 0;
            $campaigns_mktplace             = (!empty($value)) ? $value['total_channel'] : 0;
            $campaigns_seller               = (!empty($value)) ? $value['total_seller'] : 0;
            $campaigns_promotions           = (!empty($value)) ? $value['total_promotions'] : 0;
            $campaigns_rebate               = (!empty($value)) ? $value['total_rebate'] : 0;
            $campaigns_comission_reduction  = (!empty($value)) ? $value['comission_reduction'] : 0;
            $refund                         = 0;

            $campaigns_comissionreduxchannel    = (!empty($value)) ? $value['comission_reduction_marketplace'] : 0;
            $campaigns_rebatechannel            = (!empty($value)) ? $value['total_rebate_marketplace'] : 0;
            $campaigns_channelrefund            = 0;

            $comission = $value['service_charge_value'];

            if ($campaigns_campaigns > 0)
                $campaigns_promotions = 0;

            if ($campaigns_mktplace > 0)
                $valor_repasse = $value['expectativaReceb'] + $campaigns_comission_reduction + ($campaigns_mktplace - ($campaigns_mktplace * ($comission / 100)));
            else
                $valor_repasse = $value['expectativaReceb'] + $campaigns_comission_reduction;

            $valor_repasse += $campaigns_rebate;

            if ($campaigns_mktplace > 0)
                $valor_comissao = $value['comission'] - $campaigns_comission_reduction + ($campaigns_mktplace * ($comission / 100));
            else
                $valor_comissao = $value['comission'] - $campaigns_comission_reduction;

            $refund = $valor_repasse - $value['expectativaReceb'];

            $campaigns_pricetags            = number_format($campaigns_pricetags, 2, ",", ".");
            $campaigns_campaigns            = number_format($campaigns_campaigns, 2, ",", ".");
            $campaigns_mktplace             = number_format($campaigns_mktplace, 2, ",", ".");
            $campaigns_seller               = number_format($campaigns_seller, 2, ",", ".");
            $campaigns_promotions           = number_format($campaigns_promotions, 2, ",", ".");
            $campaigns_rebate               = number_format($campaigns_rebate, 2, ",", ".");
            $campaigns_comission_reduction  = number_format($campaigns_comission_reduction, 2, ",", ".");
            $refund                         = number_format($refund, 2, ",", ".");

            //fazer os calculos para o refund channel
            $campaigns_comissionreduxchannel    = number_format($campaigns_comissionreduxchannel, 2, ",", ".");
            $campaigns_rebatechannel            = number_format($campaigns_rebatechannel, 2, ",", ".");
            $campaigns_channelrefund            = number_format($campaigns_channelrefund, 2, ",", ".");

            $status = $this->model_iugu->statuspedido($value['paid_status']);
            $observacao = "";

            if ($value['observacao'] <> "")
                $observacao .= $value['observacao'];

            if ($value['numero_chamado'] <> "")
            {
                $observacao .= ' <br>Aberto o chamado '.$value['numero_chamado'].' junto ao marketplace '.$value['marketplace'].' para esclarecimento do pedido';

                if ($value['previsao_solucao'] <> "" && $value['previsao_solucao'] <> "00/00/0000")
                    $observacao .= ', com previsao de retorno em '.$value['previsao_solucao'];
            }

            $valoPedido = "";

            if ($value['gross_amount'] <> '-')
                $valoPedido = number_format( $value['gross_amount'], 2, ",", ".");
            else
                $valoPedido = $value['gross_amount'];

            $valorProduto = "";

            if ($value['total_order'] <> '-')
                $valorProduto = number_format( $value['total_order'], 2, ",", ".");
            else
                $valorProduto = $value['total_order'];

            $valorFrete = "";

            if($value['total_ship'] <> '-')
                $valorFrete = number_format( $value['total_ship'], 2, ",", ".");
            else
                $valorFrete = $value['total_ship'];

            $expectativaReceb = number_format($valor_repasse, 2, ",", ".");

            $valor_parceiro = "";

            if ($value['valor_parceiro'] <> '-')
                $valor_parceiro = number_format( $value['valor_parceiro'], 2, ",", ".");
            else
                $valor_parceiro = $value['valor_parceiro'];

            $comissao_descontada = "";

            if ($value['comissao_descontada'] <> '-')
                $comissao_descontada = number_format( $value['comissao_descontada'], 2, ",", ".");
            else
                $comissao_descontada = $value['comissao_descontada'];

            $calc_comissao_descontada = "";

            if ($value['calc_comissao_descontada'] <> '-')
                $calc_comissao_descontada = number_format( $value['calc_comissao_descontada'], 2, ",", ".");
            else
                $calc_comissao_descontada = $value['calc_comissao_descontada'];

            $imposto_renda_comissao_descontada = "";

            if ($value['imposto_renda_comissao_descontada'] <> '-')
                $imposto_renda_comissao_descontada = number_format( $value['imposto_renda_comissao_descontada'], 2, ",", ".");
            else
                $imposto_renda_comissao_descontada = $value['imposto_renda_comissao_descontada'];

            $total_comissao_descontada = number_format($valor_comissao, 2, ",", ".");;

            $percentualFrete = "";

            if ($value['percentual_frete'] <> '-')
                $percentualFrete = number_format( $value['percentual_frete'], 2, ",", ".").'%';
            else
                $percentualFrete = $value['percentual_frete'].'%';

            $percentualProduto = "";

            if ($value['percentual_produto'] <> '-')
                $percentualProduto = number_format( $value['percentual_produto'], 2, ",", ".").'%';
            else
                $percentualProduto = $value['percentual_produto'].'%';

            $result['data'][$key] = array(
                $value['id'],
                $value['marketplace'],
                $value['numero_pedido'],
                $status,
                $value['date_time'],
                $value['data_entrega'],
                $value['data_pagamento_mktplace'],
                $value['data_pagamento_conectala'],
                $value['data_recebimento_mktpl'],
                $value['data_caiu_na_conta'],
                $value['pago'],
                $value['data_transferencia'],
                "<b>R$ ".$valoPedido."</b>",
                "<b>R$ ".$valorProduto."</b>",
                "<b>R$ ".$valorFrete."</b>",
                "<b>R$ ".$expectativaReceb."</b>",
                "<b>R$ ".$valor_parceiro."</b>",
                $observacao,
                $value['nome_loja'],
                "<b>R$ ".$comissao_descontada."</b>",
                $value['raz_social'],
                $value['erp_customer_supplier_code'],
                $value['id_loja'],
                $percentualFrete,
                "<b>R$ ".$calc_comissao_descontada."</b>",
                "<b>R$ ".$imposto_renda_comissao_descontada."</b>",
                "<b>R$ ".$total_comissao_descontada."</b>",
                $percentualProduto,
                "<b>R$ ".$campaigns_pricetags."</b>", //28
                "<b>R$ ".$campaigns_campaigns."</b>",
                "<b>R$ ".$campaigns_mktplace."</b>",
                "<b>R$ ".$campaigns_seller."</b>",
                "<b>R$ ".$campaigns_promotions."</b>",
                "<b>R$ ".$campaigns_comission_reduction."</b>",
                "<b>R$ ".$campaigns_rebate."</b>",
                "<b>R$ ".$refund."</b>"

                // "<b>R$ ".$campaigns_comissionreduxchannel."</b>",
                // "<b>R$ ".$campaigns_rebatechannel."</b>",
                // "<b>R$ ".$campaigns_channelrefund."</b>"
            );
        } // /foreach
        // 23 26 27

        ob_end_clean();
        echo "chegou aqui campanha";die;
        if ($valor['status'] == "1")
        {
            $resultadoTela =  ( "<table border=\"1\">
                <tr>
                    <th colspan=\"8\"></th>
                    <th colspan=\"1\">PREVISTO (Considerando as previsões de entrega do pedido e o ciclo de pagamento correspondente)</th>
                    <th colspan=\"1\">PAGAMENTO AO SELLER (Informa se o Pedido foi \"Pago ou Não Pago\" (Pago  = Repasse Realizado ao Lojista) e em que data foi realizada a transferência bancária)</th>	
                    <th colspan=\"7\"></th>
                </tr>
                <tr>
                    <th>".$this->lang->line('application_id')." - Pedido</th>
                    <th>Marca</th>
                    <th>Clifor</th>
                    <th>".$this->lang->line('application_store')."</th>
                    <th>Razão Social</th>
                    <th>".$this->lang->line('application_purchase_id')."</th>
                    <th>".$this->lang->line('application_status')."</th>
                    <th>".$this->lang->line('application_date')." Pedido</th>
                    <th>".$this->lang->line('application_date')." de Entrega</th>
                    <th>".$this->lang->line('application_payment_date')." Marca</th>
                    <th>".$this->lang->line('application_order_2')."</th>
                    <th>".$this->lang->line('application_purchase_total')."</th>
                    <th>".$this->lang->line('application_value_products')."</th>
                    <th>".$this->lang->line('application_ship_value')."</th>
                    <th>Expectativa Recebimento</th>
                    <th>Comissão</th>
                    <th>".$this->lang->line('application_extract_obs')."</th>
               </tr>");

            foreach ($result['data'] as $value)
            {
                $resultadoTela .= ( "<tr>");
                $resultadoTela .= ("<td>".$value[0]."</td>");
                $resultadoTela .= ("<td>".$value[1]."</td>");
                $resultadoTela .= ("<td>".$value[21]."</td>");
                $resultadoTela .= ("<td>".$value[18]."</td>");
                $resultadoTela .= ("<td>".$value[20]."</td>");
                $resultadoTela .= ("<td>".$value[2]."</td>");
                $resultadoTela .= ("<td>".$value[3]."</td>");
                $resultadoTela .= ("<td>".$value[4]."</td>");
                $resultadoTela .= ("<td>".$value[5]."</td>");
                $resultadoTela .= ("<td>".$value[6]."</td>");
                $resultadoTela .= ("<td>".$value[10]."</td>");
                $resultadoTela .= ("<td>".$value[12]."</td>");
                $resultadoTela .= ("<td>".$value[13]."</td>");
                $resultadoTela .= ("<td>".$value[14]."</td>");
                $resultadoTela .= ("<td>".str_replace("ã","a",$value[15])."</td>");
                $resultadoTela .= ("<td>".$value[19]."</td>");
                $resultadoTela .= ("<td>".$value[17]."</td>");
                $resultadoTela .= ( "</tr>");
            }

            $resultadoTela .= "</table>";

        }
        else if($valorNM['status'] == "1")
        {
            $resultadoTela = ( "<table border=\"1\">
                            <!-- <tr>
                                <th colspan=\"7\"></th>
                                <th colspan=\"1\">PREVISTO (Considerando as previsões de entrega do pedido e o ciclo de pagamento correspondente)</th>
                                <th colspan=\"1\">PAGAMENTO AO SELLER (Informa se o Pedido foi \"Pago ou Não Pago\" (Pago  = Repasse Realizado ao Lojista) e em que data foi realizada a transferência bancária)</th>	
                                <th colspan=\"7\"></th>
                            </tr> -->
                            <tr>
                                <th>".$this->lang->line('application_quotation_id')." - ".$this->lang->line('application_store')."</th>
                                <th>".$this->lang->line('application_store')."</th>
                                <th>Pedido Novo Mundo</th>
                                <th>Pedido Seller</th>
                                <th>".$this->lang->line('application_category')."</th>
                                <th>".$this->lang->line('application_status')."</th>
                                <th>".$this->lang->line('application_date')." ".$this->lang->line('application_order')."</th> 
                                <th>".$this->lang->line('application_date')." de Entrega</th>
                                <th>".$this->lang->line('application_payment_date')."</th>
                                <th>".$this->lang->line('application_Paid')."</th>
                                <th>".$this->lang->line('application_parameter_mktplace_value_ciclo')."</th>
                                <th>".$this->lang->line('application_purchase_total')."</th>
                                <th>".$this->lang->line('application_value_products')."</th>
                                <th>Valor Serviço</th>
                                <th>".$this->lang->line('application_ship_value')."</th>
                                <th>".$this->lang->line('application_charge_amount')."</th>
                                <th>Comissão sobre o Serviço (%)</th>
                                <th>".$this->lang->line('application_charge_amount_freight')."</th>
                                <th>".$this->lang->line('application_total')." ".$this->lang->line('application_commission')."</th>
                                <th>Imposto de Renda</th>
                                <th>Total do repasse</th>
                                
                                <th>".$this->lang->line('application_campaigns_pricetags')."</th>
                                <th>".$this->lang->line('application_campaigns_campaigns')."</th>
                                <th>".$this->lang->line('application_campaigns_marketplace')."</th>
                                <th>".$this->lang->line('application_campaigns_seller')."</th>
                                <th>".$this->lang->line('application_campaigns_promotions')."</th>
                                <th>".$this->lang->line('application_campaigns_comission_reduction')."</th>
                                <th>".$this->lang->line('application_campaigns_rebate')."</th>
                                <th>".$this->lang->line('application_campaigns_refund')."</th>

                                <th>".$this->lang->line('application_extract_obs')."</th>
                        </tr>");

            foreach ($result['data'] as $value)
            {
                $resultadoTela .= ( "<tr>");
                $resultadoTela .= ("<td>".$value[22]."</td>");
                $resultadoTela .= ("<td>".$value[18]."</td>");
                $resultadoTela .= ("<td></td>");
                $resultadoTela .= ("<td>".$value[2]."</td>");
                $resultadoTela .= ("<td></td>");
                $resultadoTela .= ("<td>".$value[3]."</td>");
                $resultadoTela .= ("<td>".$value[4]."</td>");
                $resultadoTela .= ("<td>".$value[5]."</td>");
                $resultadoTela .= ("<td>".$value[6]."</td>");
                $resultadoTela .= ("<td>".$value[10]."</td>");
                $resultadoTela .= ("<td></td>");
                $resultadoTela .= ("<td>".$value[12]."</td>");
                $resultadoTela .= ("<td>".$value[13]."</td>");
                $resultadoTela .= ("<td></td>");
                $resultadoTela .= ("<td>".$value[14]."</td>");
                $resultadoTela .= ("<td>".$value[27]."</td>");
                $resultadoTela .= ("<td></td>");
                $resultadoTela .= ("<td>".$value[23]."</td>");
                $resultadoTela .= ("<td>".$value[24]."</td>");
                $resultadoTela .= ("<td>".$value[25]."</td>");
                $resultadoTela .= ("<td>".$value[26]."</td>");

                $resultadoTela .= ("<td>".$value[28]."</td>");
                $resultadoTela .= ("<td>".$value[29]."</td>");
                $resultadoTela .= ("<td>".$value[30]."</td>");
                $resultadoTela .= ("<td>".$value[31]."</td>");
                $resultadoTela .= ("<td>".$value[32]."</td>");
                $resultadoTela .= ("<td>".$value[33]."</td>");
                $resultadoTela .= ("<td>".$value[34]."</td>");
                $resultadoTela .= ("<td>".$value[35]."</td>");

                $resultadoTela .= ("<td>".$value[17]."</td>");
                $resultadoTela .= ( "</tr>");
            }

            $resultadoTela .= "</table>";

        }
        else if($valorORTB['status'] == "1")
        {
            $resultadoTela = ( "<table border=\"1\">
                            <!-- <tr>
                                <th colspan=\"6\"></th>
                                <th colspan=\"1\">PREVISTO (Considerando as previsões de entrega do pedido e o ciclo de pagamento correspondente)</th>
                                <th colspan=\"1\">PAGAMENTO AO SELLER (Informa se o Pedido foi \"Pago ou Não Pago\" (Pago  = Repasse Realizado ao Lojista) e em que data foi realizada a transferência bancária)</th>	
                                <th colspan=\"7\"></th>
                            </tr> -->
                            <tr>
                                <th>".$this->lang->line('application_quotation_id')." - ".$this->lang->line('application_store')."</th>
                                <th>".$this->lang->line('application_store')."</th>
                                <th>Pedido Seller</th>
                                <th>".$this->lang->line('application_category')."</th>
                                <th>".$this->lang->line('application_status')."</th>
                                <th>".$this->lang->line('application_date')." ".$this->lang->line('application_order')."</th> 
                                <th>".$this->lang->line('application_date')." de Entrega</th>
                                <th>".$this->lang->line('application_payment_date')."</th>
                                <th>".$this->lang->line('application_Paid')."</th>
                                <th>".$this->lang->line('application_parameter_mktplace_value_ciclo')."</th>
                                <th>".$this->lang->line('application_purchase_total')."</th>
                                <th>".$this->lang->line('application_value_products')."</th>
                                <th>Valor Serviço</th>
                                <th>".$this->lang->line('application_ship_value')."</th>
                                <th>".$this->lang->line('application_charge_amount')."</th>
                                <th>Comissão sobre o Serviço (%)</th>
                                <th>".$this->lang->line('application_charge_amount_freight')."</th>
                                <th>".$this->lang->line('application_total')." ".$this->lang->line('application_commission')."</th>
                                <th>Imposto de Renda</th>
                                <th>Total do repasse</th>
                                
                                <th>".$this->lang->line('application_campaigns_pricetags')."</th>
                                <th>".$this->lang->line('application_campaigns_campaigns')."</th>
                                <th>".$this->lang->line('application_campaigns_marketplace')."</th>
                                <th>".$this->lang->line('application_campaigns_seller')."</th>
                                <th>".$this->lang->line('application_campaigns_promotions')."</th>
                                <th>".$this->lang->line('application_campaigns_comission_reduction')."</th>
                                <th>".$this->lang->line('application_campaigns_rebate')."</th>
                                <th>".$this->lang->line('application_campaigns_refund')."</th>

                                <th>".$this->lang->line('application_extract_obs')."</th>
                        </tr>");

            foreach ($result['data'] as $value)
            {
                $resultadoTela .= ( "<tr>");
                $resultadoTela .= ("<td>".$value[22]."</td>");
                $resultadoTela .= ("<td>".$value[18]."</td>");
                $resultadoTela .= ("<td>".$value[2]."</td>");
                $resultadoTela .= ("<td></td>");
                $resultadoTela .= ("<td>".$value[3]."</td>");
                $resultadoTela .= ("<td>".$value[4]."</td>");
                $resultadoTela .= ("<td>".$value[5]."</td>");
                $resultadoTela .= ("<td>".$value[6]."</td>");
                $resultadoTela .= ("<td>".$value[10]."</td>");
                $resultadoTela .= ("<td></td>");
                $resultadoTela .= ("<td>".$value[12]."</td>");
                $resultadoTela .= ("<td>".$value[13]."</td>");
                $resultadoTela .= ("<td></td>");
                $resultadoTela .= ("<td>".$value[14]."</td>");
                $resultadoTela .= ("<td>".$value[27]."</td>");
                $resultadoTela .= ("<td></td>");
                $resultadoTela .= ("<td>".$value[23]."</td>");
                $resultadoTela .= ("<td>".$value[24]."</td>");
                $resultadoTela .= ("<td>".$value[25]."</td>");
                $resultadoTela .= ("<td>".$value[15]."</td>");

                $resultadoTela .= ("<td>".$value[28]."</td>");
                $resultadoTela .= ("<td>".$value[29]."</td>");
                $resultadoTela .= ("<td>".$value[30]."</td>");
                $resultadoTela .= ("<td>".$value[31]."</td>");
                $resultadoTela .= ("<td>".$value[32]."</td>");
                $resultadoTela .= ("<td>".$value[33]."</td>");
                $resultadoTela .= ("<td>".$value[34]."</td>");
                $resultadoTela .= ("<td>".$value[35]."</td>");

                $resultadoTela .= ("<td>".$value[17]."</td>");
                $resultadoTela .= ( "</tr>");
            }

            $resultadoTela .= "</table>";

        }
        else if($valorSellercenter['status'] == "1")
        {
            $resultadoTela = ( "<table border=\"1\">
                            <!-- <tr>
                                <th colspan=\"6\"></th>
                                <th colspan=\"1\">PREVISTO (Considerando as previsões de entrega do pedido e o ciclo de pagamento correspondente)</th>
                                <th colspan=\"1\">PAGAMENTO AO SELLER (Informa se o Pedido foi \"Pago ou Não Pago\" (Pago  = Repasse Realizado ao Lojista) e em que data foi realizada a transferência bancária)</th>	
                                <th colspan=\"7\"></th>
                            </tr> -->
                            <tr>
                                <th>".$this->lang->line('application_quotation_id')." - ".$this->lang->line('application_store')."</th>
                                <th>".$this->lang->line('application_store')."</th>
                                <th>".$this->lang->line('application_numero_marketlace')."</th>
                                <th>".$this->lang->line('application_category')."</th>
                                <th>".$this->lang->line('application_status')."</th>
                                <th>".$this->lang->line('application_date')." ".$this->lang->line('application_order')."</th> 
                                <th>".$this->lang->line('application_date')." de Entrega</th>
                                <th>".$this->lang->line('application_payment_date')."</th>");

            if($dataRepasseSellerCenter['status'] == "1"){
                $resultadoTela .= ( "<th>" . $this->lang->line('application_date') . " " . $this->lang->line('application_bank_transfer') . "</th>");
            }

            $resultadoTela .= ( "     <th>".$this->lang->line('application_Paid')."</th>
                                <th>".$this->lang->line('application_parameter_mktplace_value_ciclo')."</th>
                                <th>".$this->lang->line('application_purchase_total')."</th>
                                <th>".$this->lang->line('application_value_products')."</th>
                                <th>".$this->lang->line('application_service_value')."</th>
                                <th>".$this->lang->line('application_ship_value')."</th>
                                <th>".$this->lang->line('application_charge_amount')."</th>
                                <th>".$this->lang->line('application_service_charge_amount')."</th>
                                <th>".$this->lang->line('application_charge_amount_freight')."</th>
                                <th>".$this->lang->line('application_total')." ".$this->lang->line('application_commission')."</th>
                                <th>".$this->lang->line('application_ir_value')."</th>
                                <th>".$this->lang->line('application_total_to_transfer')."</th>

                                <th>".$this->lang->line('application_campaigns_pricetags')."</th>
                                <th>".$this->lang->line('application_campaigns_campaigns')."</th>
                                <th>".$this->lang->line('application_campaigns_marketplace')."</th>
                                <th>".$this->lang->line('application_campaigns_seller')."</th>
                                <th>".$this->lang->line('application_campaigns_promotions')."</th>
                                <th>".$this->lang->line('application_campaigns_comission_reduction')."</th>
                                <th>".$this->lang->line('application_campaigns_rebate')."</th>
                                <th>".$this->lang->line('application_campaigns_refund')."</th>

                                <th>".$this->lang->line('application_extract_obs')."</th>
                        </tr>");

            foreach ($result['data'] as $value)
            {
                $resultadoTela .= ( "<tr>");
                $resultadoTela .= ("<td>".$value[22]."</td>");
                $resultadoTela .= ("<td>".$value[18]."</td>");
                $resultadoTela .= ("<td>".$value[2]."</td>");
                $resultadoTela .= ("<td></td>");
                $resultadoTela .= ("<td>".$value[3]."</td>");
                $resultadoTela .= ("<td>".$value[4]."</td>");
                $resultadoTela .= ("<td>".$value[5]."</td>");
                $resultadoTela .= ("<td>".$value[6]."</td>");
                if($dataRepasseSellerCenter['status'] == "1"){
                    $resultadoTela .= ("<td>".$value[11]."</td>");
                }
                $resultadoTela .= ("<td>".$value[10]."</td>");
                $resultadoTela .= ("<td></td>");
                $resultadoTela .= ("<td>".$value[12]."</td>");
                $resultadoTela .= ("<td>".$value[13]."</td>");
                $resultadoTela .= ("<td></td>");
                $resultadoTela .= ("<td>".$value[14]."</td>");
                $resultadoTela .= ("<td>".$value[27]."</td>");
                $resultadoTela .= ("<td></td>");
                $resultadoTela .= ("<td>".$value[23]."</td>");
                $resultadoTela .= ("<td>".$value[24]."</td>");
                $resultadoTela .= ("<td>".$value[25]."</td>");
                $resultadoTela .= ("<td>".$value[15]."</td>");

                $resultadoTela .= ("<td>".$value[28]."</td>");
                $resultadoTela .= ("<td>".$value[29]."</td>");
                $resultadoTela .= ("<td>".$value[30]."</td>");
                $resultadoTela .= ("<td>".$value[31]."</td>");
                $resultadoTela .= ("<td>".$value[32]."</td>");
                $resultadoTela .= ("<td>".$value[33]."</td>");
                $resultadoTela .= ("<td>".$value[34]."</td>");
                $resultadoTela .= ("<td>".$value[35]."</td>");

                $resultadoTela .= ("<td>".$value[17]."</td>");
                $resultadoTela .= ( "</tr>");
            }

            $resultadoTela .= "</table>";

        }
        else
        {
            if (array_key_exists("extratonovo", $inputs))
            {
                $resultadoTela = ("<table border=\"1\">
                    <tr>
                        <th colspan=\"7\"></th>
                        <th colspan=\"2\">REALIZADO (Considerando todos os pedidos que foram pagos pelos marketplaces e passaram pelo processo de conciliação)</th>
                        <th colspan=\"2\">PAGAMENTO AO SELLER (Informa se o Pedido foi \"Pago ou Não Pago\" (Pago  = Repasse Realizado ao Lojista) e em que data foi realizada a transferência bancária)</th>	
                        <th colspan=\"5\"></th>
                        
                    </tr>
                    <tr>
                        <th>" . $this->lang->line('application_id') . " - Pedido</th>
                        <th>" . $this->lang->line('application_store') . "</th>
                        <th>" . $this->lang->line('application_marketplace') . "</th>
                        <th>" . $this->lang->line('application_purchase_id') . "</th> 
                        <th>" . $this->lang->line('application_status') . "</th>
                        <th>" . $this->lang->line('application_date') . " Pedido</th>
                        <th>" . $this->lang->line('application_date') . " de Entrega</th>
                        <th>Data que recebemos o repasse</th>
                        <th>Data em que pagamos</th>
                        <th>" . $this->lang->line('application_order_2') . "</th>
                        <th>" . $this->lang->line('application_date') . " " . $this->lang->line('application_bank_transfer') . "</th>
                        <th>" . $this->lang->line('application_purchase_total') . "</th>
                        <th>" . $this->lang->line('application_value_products') . "</th>
                        <th>" . $this->lang->line('application_ship_value') . "</th>
                        <th>Valor pago pelo Marketplace</th>
                        <th>" . $this->lang->line('application_extract_obs') . "</th>
                </tr>");
                // <th>Pedido Conciliado</th>
                foreach ($result['data'] as $value)
                {
                    $resultadoTela .= ("<tr>");
                    $resultadoTela .= ("<td>".$value[0]."</td>");
                    $resultadoTela .= ("<td>".$value[18]."</td>");
                    $resultadoTela .= ("<td>".$value[1]."</td>");
                    $resultadoTela .= ("<td>".$value[2]."</td>");
                    $resultadoTela .= ("<td>".$value[3]."</td>");
                    $resultadoTela .= ("<td>".$value[4]."</td>");
                    $resultadoTela .= ("<td>".$value[5]."</td>");
                    $resultadoTela .= ("<td>".$value[8]."</td>");
                    $resultadoTela .= ("<td>".str_replace("ã","a",$value[9])."</td>");
                    $resultadoTela .= ("<td>".$value[10]."</td>");
                    $resultadoTela .= ("<td>".$value[11]."</td>");
                    $resultadoTela .= ("<td>".$value[12]."</td>");
                    $resultadoTela .= ("<td>".$value[13]."</td>");
                    $resultadoTela .= ("<td>".$value[14]."</td>");
                    $resultadoTela .= ("<td>".$value[16]."</td>");
                    $resultadoTela .= ("<td>".$value[17]."</td>");
                    $resultadoTela .= ("</tr>");
                }

                $resultadoTela .= "</table>";

            }
            else
            {
                $negociacao_marketplace_campanha = '';
                $colspan = 8;

                // if ($this->negociacao_marketplace_campanha)
                // {
                //     $negociacao_marketplace_campanha = "
                //         <th>".$this->lang->line('conciliation_sc_gridok_comissionreduxchannel')."</th>
                //         <th>".$this->lang->line('conciliation_sc_gridok_rebatechannel')."</th>
                //         <th>".$this->lang->line('conciliation_sc_gridok_channelrefund')."</th>";

                //     $colspan = 11;
                // }


                $resultadoTela = ("<table border=\"1\">
                    <tr>
                        <th colspan=\"7\"></th>
                        <th colspan=\"2\">PREVISTO (Considerando as previsões de entrega do pedido e o ciclo de pagamento correspondente)</th>
                        <th colspan=\"2\">REALIZADO (Considerando todos os pedidos que foram pagos pelos marketplaces e passaram pelo processo de conciliação)</th>
                        <th colspan=\"2\">PAGAMENTO AO SELLER (Informa se o Pedido foi \"Pago ou Não Pago\" (Pago  = Repasse Realizado ao Lojista) e em que data foi realizada a transferência bancária)</th>	
                        <th colspan=\"5\"></th>
                        <th colspan=\"".$colspan."\">".$this->lang->line('conciliation_sc_gridok_campaigns')."</th>
                        
                    </tr>
                    <tr>
                        <th>" . $this->lang->line('application_id') . " - Pedido</th>
                        <th>" . $this->lang->line('application_store') . "</th>
                        <th>" . $this->lang->line('application_marketplace') . "</th>
                        <th>" . $this->lang->line('application_purchase_id') . "</th> 
                        <th>" . $this->lang->line('application_status') . "</th>
                        <th>" . $this->lang->line('application_date') . " Pedido</th>
                        <th>" . $this->lang->line('application_date') . " de Entrega</th>
                        <th>" . $this->lang->line('application_payment_date') . " Marketplace</th>
                        <th>" . $this->lang->line('application_payment_date_conecta') . "</th>
                        <th>Data que recebemos o repasse</th>
                        <th>Data em que pagamos</th>
                        <th>" . $this->lang->line('application_order_2') . "</th>
                        <th>" . $this->lang->line('application_date') . " " . $this->lang->line('application_bank_transfer') . "</th>
                        <th>" . $this->lang->line('application_purchase_total') . "</th>
                        <th>" . $this->lang->line('application_value_products') . "</th>
                        <th>" . $this->lang->line('application_ship_value') . "</th>
                        <th>Expectativa Recebimento</th>
                        <th>Valor pago pelo Marketplace</th>

                        <th>".$this->lang->line('application_campaigns_pricetags')."</th>
                        <th>".$this->lang->line('application_campaigns_campaigns')."</th>
                        <th>".$this->lang->line('application_campaigns_marketplace')."</th>
                        <th>".$this->lang->line('application_campaigns_seller')."</th>
                        <th>".$this->lang->line('application_campaigns_promotions')."</th>
                        <th>".$this->lang->line('application_campaigns_comission_reduction')."</th>
                        <th>".$this->lang->line('application_campaigns_rebate')."</th>
                        <th>".$this->lang->line('application_campaigns_refund')."</th>

                        ".$negociacao_marketplace_campanha."

                        <th>" . $this->lang->line('application_extract_obs') . "</th>
                </tr>");
                // <th>Pedido Conciliado</th>
                foreach ($result['data'] as $value)
                {
                    $resultadoTela .= ("<tr>");
                    $resultadoTela .= ("<td>".$value[0]."</td>");
                    $resultadoTela .= ("<td>".$value[18]."</td>");
                    $resultadoTela .= ("<td>".$value[1]."</td>");
                    $resultadoTela .= ("<td>".$value[2]."</td>");
                    $resultadoTela .= ("<td>".$value[3]."</td>");
                    $resultadoTela .= ("<td>".$value[4]."</td>");
                    $resultadoTela .= ("<td>".$value[5]."</td>");
                    $resultadoTela .= ("<td>".$value[6]."</td>");
                    $resultadoTela .= ("<td>".$value[7]."</td>");
                    $resultadoTela .= ("<td>".$value[8]."</td>");
                    $resultadoTela .= ("<td>".str_replace("ã","a",$value[9])."</td>");
                    $resultadoTela .= ("<td>".$value[10]."</td>");
                    $resultadoTela .= ("<td>".$value[11]."</td>");
                    $resultadoTela .= ("<td>".$value[12]."</td>");
                    $resultadoTela .= ("<td>".$value[13]."</td>");
                    $resultadoTela .= ("<td>".$value[14]."</td>");
                    $resultadoTela .= ("<td>".str_replace("ã","a",$value[15])."</td>");
                    $resultadoTela .= ("<td>".$value[16]."</td>");

                    $resultadoTela .= ("<td>".$value[28]."</td>");
                    $resultadoTela .= ("<td>".$value[29]."</td>");
                    $resultadoTela .= ("<td>".$value[30]."</td>");
                    $resultadoTela .= ("<td>".$value[31]."</td>");
                    $resultadoTela .= ("<td>".$value[32]."</td>");
                    $resultadoTela .= ("<td>".$value[33]."</td>");
                    $resultadoTela .= ("<td>".$value[34]."</td>");
                    $resultadoTela .= ("<td>".$value[35]."</td>");

                    // if ($this->negociacao_marketplace_campanha)
                    // {
                    //     $resultadoTela .= ("<td>".$value[36]."</td>");
                    //     $resultadoTela .= ("<td>".$value[37]."</td>");
                    //     $resultadoTela .= ("<td>".$value[38]."</td>");
                    // }

                    $resultadoTela .= ("<td>".$value[17]."</td>");
                    $resultadoTela .= ("</tr>");
                }

                $resultadoTela .= "</table>";
            }

        }
        echo "chegou aqui";die;
        //echo $resultadoTela;

        error_reporting(1);
        require_once (APPPATH . '/third_party/PHPExcel/IOFactory.php');
        $filename = "Extrato.xlsx";
        $table    = $resultadoTela;

        // save $table inside temporary file that will be deleted later
        $tmpfile = tempnam(sys_get_temp_dir(), 'html');
        file_put_contents($tmpfile, $table);

        // insert $table into $objPHPExcel's Active Sheet through $excelHTMLReader
        $objPHPExcel     = new PHPExcel();
        $excelHTMLReader = PHPExcel_IOFactory::createReader('HTML');
        $excelHTMLReader->loadIntoExisting($tmpfile, $objPHPExcel);
        $objPHPExcel->getActiveSheet()->setTitle('Extrato'); // Change sheet's title if you want

        unlink($tmpfile); // delete temporary file because it isn't needed anymore

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); // header for .xlxs file
        header('Content-Disposition: attachment;filename='.$filename); // specify the download file name
        header('Cache-Control: max-age=0');

        // Creates a writer to output the $objPHPExcel's content
        $writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $writer->save('php://output');
        exit;

    }


    public function extratopedidosexcelconloja(){

        header("Pragma: public");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: pre-check=0, post-check=0, max-age=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        header("Content-Transfer-Encoding: none");
        header("Content-Type: application/vnd.ms-excel;");
        header("Content-type: application/x-msexcel;");
        header("Content-Disposition: attachment; filename=Extrato.xls");

        $result = array('data' => array());

        $valor = $this->model_settings->getSettingDatabyNameEmptyArray('gsoma_painel_financeiro');

        $inputs = $this->input;
        $post = $inputs->post();
        $inputs = $inputs->get();
        if ($inputs['slc_status'] <> "") {
            $statusFiltro = array();
            $inputs['slc_status'] = explode(',', $inputs['slc_status']);
            $j = 0;
            foreach ($inputs['slc_status'] as $item) {
                //Busca todos os status pelo selecionado na tela
                $statusTela = $this->model_iugu->statuspedido($item);
                for ($i = 0; $i <= 101; $i++) {
                    $status = $this->model_iugu->statuspedido($i);
                    if ($status <> false) {
                        if ($status == $statusTela) {
                            $statusFiltro[$j] = $i;
                            $j++;
                        }
                    }
                }
            }

            $filtroFinal = implode(",", $statusFiltro);
            $inputs['slc_status'] = $filtroFinal;
        }
        $data = $this->model_billet->getPedidosExtratoConciliado($inputs, null, $post,"excel","loja");

        foreach ($data as $key => $value) {

            $observacao = "";

            $valoPedido = "";
            if($value['gross_amount'] <> '-'){
                $valoPedido = number_format( $value['gross_amount'], 2, ",", ".");
            }else{
                $valoPedido = $value['gross_amount'];
            }

            $valorProduto = "";
            if($value['total_order'] <> '-'){
                $valorProduto = number_format( $value['total_order'], 2, ",", ".");
            }else{
                $valorProduto = $value['total_order'];
            }

            $valorFrete = "";
            if($value['total_ship'] <> '-'){
                $valorFrete = number_format( $value['total_ship'], 2, ",", ".");
            }else{
                $valorFrete = $value['total_ship'];
            }

            $expectativaReceb = "";
            if($value['expectativaReceb'] <> '-'){
                $expectativaReceb = number_format( $value['expectativaReceb'], 2, ",", ".");
            }else{
                $expectativaReceb = $value['expectativaReceb'];
            }

            $comissao_descontada = "";
            if($value['comissao_descontada'] <> '-'){
                $comissao_descontada = number_format( $value['comissao_descontada'], 2, ",", ".");
            }else{
                $comissao_descontada = $value['comissao_descontada'];
            }



            $result['data'][$key] = array(
                $value['nome_loja'],
                $value['data_pagamento_mktplace'],
                $value['pago'],
                "<b>R$ ".$valoPedido."</b>",
                "<b>R$ ".$valorProduto."</b>",
                "<b>R$ ".$valorFrete."</b>",
                "<b>R$ ".$expectativaReceb."</b>",
                "<b>R$ ".$comissao_descontada."</b>",
                $observacao,
                $value['raz_social'],
                $value['clifor'],
                $value['banco'],
                $value['agencia'],
                $value['conta_bancaria']
            );
        } // /foreach

        if($valor['status'] == "1"){

            echo utf8_decode( "<table border=\"1\">
                    <tr>
                        <th colspan=\"6\"></th>
                    	<th colspan=\"1\">PREVISTO (Considerando as previsões de entrega do pedido e o ciclo de pagamento correspondente)</th>
                    	<th colspan=\"1\">PAGAMENTO AO SELLER (Informa se o Pedido foi \"Pago ou Não Pago\" (Pago  = Repasse Realizado ao Lojista) e em que data foi realizada a transferência bancária)</th>	
                        <th colspan=\"6\"></th>
                        
                    </tr>
                    <tr>
						<th>Clifor</th>
						<th>".$this->lang->line('application_store')."</th>
						<th>".$this->lang->line('application_bank')."</th>
						<th>".$this->lang->line('application_agency')."</th>
						<th>".$this->lang->line('application_account')."</th>
						<th>Razão Social</th>
                        <th>".$this->lang->line('application_payment_date')." Marca</th>
                        <th>".$this->lang->line('application_order_2')."</th>
                        <th>".$this->lang->line('application_purchase_total')."</th>
                        <th>".$this->lang->line('application_value_products')."</th>
                        <th>".$this->lang->line('application_ship_value')."</th>
                        <th>Expectativa Recebimento</th>
                        <th>Comissão</th>
                        <th>".$this->lang->line('application_extract_obs')."</th>
    	           </tr>");

            foreach($result['data'] as $value){

                echo utf8_decode( "<tr>");
                echo utf8_decode("<td>".$value[10]."</td>");
                echo utf8_decode("<td>".$value[0]."</td>");
                echo utf8_decode("<td>".$value[11]."</td>");
                echo utf8_decode("<td>".$value[12]."</td>");
                echo utf8_decode("<td>".$value[13]."</td>");
                echo utf8_decode("<td>".$value[9]."</td>");
                echo utf8_decode("<td>".$value[1]."</td>");
                echo utf8_decode("<td>".$value[2]."</td>");
                echo utf8_decode("<td>".$value[3]."</td>");
                echo utf8_decode("<td>".$value[4]."</td>");
                echo utf8_decode("<td>".$value[5]."</td>");
                echo utf8_decode("<td>".$value[6]."</td>");
                echo utf8_decode("<td>".$value[7]."</td>");
                echo utf8_decode("<td>".$value[8]."</td>");
                echo utf8_decode( "</tr>");

            }

            echo "</table>";

        }


    }

    public function externalantecipation(){

        include "./system/libraries/Vendor/dompdf/autoload.inc.php";

        $inputs = $this->input;
        $post = $inputs->post();
        $inputs = $inputs->get();

        //Buscar antecipações dentro do periodo selecionado
        $antecipacoes = $this->model_legal_panel->getAllOthersBetweenDateByStore(
            $inputs['slc_loja'],
            $inputs['txt_data_inicio_repasse'],
            $inputs['txt_data_fim_repasse']
        );
        $store = $this->model_stores->getStoresById($inputs['slc_loja']);

        if ($antecipacoes){
            $firstAntecipation = $antecipacoes[0];
            $response = json_decode($firstAntecipation['description'], true);
            $documentNumber = cnpj($response['document_number']);
        }else{
            $documentNumber = cnpj($store['CNPJ']);
        }

        $bankNumber = $this->model_banks->getBankNumber($store['bank']);

        $referencePeriod = '';
        if ($inputs['txt_data_inicio_repasse']){
            $referencePeriod = date(DATE_BRAZIL, strtotime($inputs['txt_data_inicio_repasse']));
            if (!$inputs['txt_data_fim_repasse']){
                $referencePeriod.= " a ".dateNow()->format(DATE_BRAZIL);
            }
        }
        if ($inputs['txt_data_inicio_repasse'] && $inputs['txt_data_fim_repasse']){
            $referencePeriod.= " a ";
        }
        if ($inputs['txt_data_fim_repasse']){
            if (!$inputs['txt_data_inicio_repasse']){
                $referencePeriod.= " desde o início até ";
            }
            $referencePeriod.= date(DATE_BRAZIL, strtotime($inputs['txt_data_fim_repasse']));
        }
        if (!$inputs['txt_data_inicio_repasse'] && !$inputs['txt_data_fim_repasse']){
            $referencePeriod = 'Todo o Período';
        }

        $dompdf = new Dompdf();

        $html = "<html><head><meta http-equiv='content-type' content='text/html; charset=utf-8'><style>
                        body{
                            font-family: 'Microsoft YaHei','Source Sans Pro', sans-serif;
                            font-size:13px;
                        }
                        .table {
                            border-collapse:collapse;
                            font-size: 11px;
                            border: 0;
                            width: 100%;
                            margin: 0px;
                            cellspacing: 0;
                        }
                        .table.border td {
                            border: 1px #000 solid; 
                        }
                        .table.border {
                            border: 1px solid #000;
                        }
                        .table.padding td{
                            padding-left:10px
                        }
                        .table.padding th{
                            padding-left:10px;
                            padding-top: 5px;
                            padding-bottom:10px;
                        }
                        .table.padding td{
                            padding-top:5px
                        }
                        .table.padding td{
                            padding-bottom:10px
                        }
                        .table.header span{
                            font-size: 15px;
                        }
                        .mt-05 {
                            margin-top: 5px
                        }
                        .mt-1 {
                            margin-top: 10px
                        }
                        thead:before, thead:after { display: none; }
                        tbody:before, tbody:after { display: none; }
                    </style></head><body>";

        $html.= "<table class='table padding'>
                <tbody>
                <tr><td colspan='99'><h2 style='margin: 0px;'>Comprovante de Liquidação Externa</h2></td></tr>
                <tr><td colspan='99' style='padding-top: 5px; padding-bottom: 5px;'>&nbsp;</td></tr>
                <tr><td colspan='99'>Dados do Beneficiário:</td></tr>
                <tr><td colspan='99' style='padding-top: 5px; padding-bottom: 5px;'>&nbsp;</td></tr>
                <tr>
                    <td colspan='5' style='padding-top: 5px; padding-bottom: 5px;'>Número do documento: {$documentNumber}</td>
                    <td colspan='99' style='padding-top: 5px; padding-bottom: 5px;'>ISPB: $bankNumber - {$store['bank']}</td>
                </tr>
                <tr>
                    <td colspan='4' style='padding-top: 5px; padding-bottom: 5px;'>Tipo de conta: {$store['account_type']}</td>
                    <td style='padding-top: 5px; padding-bottom: 5px;'>Agência: {$store['agency']}</td>	
                    <td colspan='99' style='padding-top: 5px; padding-bottom: 5px;'>Conta: {$store['account']}</td>
                </tr>
                <tr><td colspan='99' style='padding-top: 5px; padding-bottom: 5px;'>&nbsp;</td></tr>
                <tr><td colspan='99' style='padding-top: 5px; padding-bottom: 5px;'>&nbsp;</td></tr>
                <tr><td colspan='99' style='padding-top: 5px; padding-bottom: 5px;'>Período de referência: $referencePeriod</td></tr>
                <tr><td colspan='99' style='padding-top: 5px; padding-bottom: 5px;'>&nbsp;</td></tr>
                </tbody>
                </table>
                ";

        $html.= "<table class='table border padding header'>
                    <tbody>
                    <tr style='border: 1px #000 solid;'>
                        <th style='border: 1px #000 solid; padding-top: 15px;'>Data de Pagamento</th>
                        <th style='border: 1px #000 solid; padding-top: 15px;'>Valor</th>
                        <th style='border: 1px #000 solid; padding-top: 15px;'>Bandeira</th>
                        <th style='border: 1px #000 solid; padding-top: 15px;'>Modalidade</th>
                        <th style='border: 1px #000 solid; padding-top: 15px;'>ID do Pagamento</th>
                        <th style='border: 1px #000 solid; padding-top: 15px;'>Status</th>
                   </tr>";

        if ($antecipacoes) foreach ($antecipacoes as $antecipacao){

            $response = json_decode($antecipacao['description'], true);
            $paymentDate = date(DATE_BRAZIL, strtotime($response['payment_date']));
            $value = money($response['amount']/100);
            $brand = $response['card_brand'];
            $product = $response['product'];
            $paymentId = $response['id'];
            $status = $response['status'];

            $html.=  "<tr style='border: 1px #000 solid;'>";
            $html.= utf8_decode("<td style='width: 15%; padding-top: 15px;'>$paymentDate</td>");
            $html.= utf8_decode("<td style='width: 15%; padding-top: 15px;'>$value</td>");
            $html.= utf8_decode("<td style='width: 10%; padding-top: 15px;'>$brand</td>");
            $html.= utf8_decode("<td style='width: 10%; padding-top: 15px;'>$product</td>");
            $html.= utf8_decode("<td style='width: 40%; padding-top: 15px;'>$paymentId</td>");
            $html.= utf8_decode("<td style='width: 10%; padding-top: 15px;'>$status</td>");
            $html.= utf8_decode( "</tr>");

        }

        $html.= "</tbody></table>";

        $html.= '</body></html>';

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream(
            "liquidacao_externa.pdf", /* Nome do arquivo de saída */
            array(
                "Attachment" => true
            )
        );

    }

    public function extratopedidosexcelconlojamarca(){

        header("Pragma: public");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: pre-check=0, post-check=0, max-age=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        header("Content-Transfer-Encoding: none");
        header("Content-Type: application/vnd.ms-excel;");
        header("Content-type: application/x-msexcel;");
        header("Content-Disposition: attachment; filename=Extrato.xls");

        $result = array('data' => array());

        $valor = $this->model_settings->getSettingDatabyNameEmptyArray('gsoma_painel_financeiro');

        $inputs = $this->input;
        $post = $inputs->post();
        $inputs = $inputs->get();
        if ($inputs['slc_status'] <> "") {
            $statusFiltro = array();
            $inputs['slc_status'] = explode(',', $inputs['slc_status']);
            $j = 0;
            foreach ($inputs['slc_status'] as $item) {
                //Busca todos os status pelo selecionado na tela
                $statusTela = $this->model_iugu->statuspedido($item);
                for ($i = 0; $i <= 101; $i++) {
                    $status = $this->model_iugu->statuspedido($i);
                    if ($status <> false) {
                        if ($status == $statusTela) {
                            $statusFiltro[$j] = $i;
                            $j++;
                        }
                    }
                }
            }

            $filtroFinal = implode(",", $statusFiltro);
            $inputs['slc_status'] = $filtroFinal;
        }
        $data = $this->model_billet->getPedidosExtratoConciliado($inputs, null, $post,"excel","lojamarca");

        foreach ($data as $key => $value) {

            $observacao = "";

            $valoPedido = "";
            if($value['gross_amount'] <> '-'){
                $valoPedido = number_format( $value['gross_amount'], 2, ",", ".");
            }else{
                $valoPedido = $value['gross_amount'];
            }

            $valorProduto = "";
            if($value['total_order'] <> '-'){
                $valorProduto = number_format( $value['total_order'], 2, ",", ".");
            }else{
                $valorProduto = $value['total_order'];
            }

            $valorFrete = "";
            if($value['total_ship'] <> '-'){
                $valorFrete = number_format( $value['total_ship'], 2, ",", ".");
            }else{
                $valorFrete = $value['total_ship'];
            }

            $expectativaReceb = "";
            if($value['expectativaReceb'] <> '-'){
                $expectativaReceb = number_format( $value['expectativaReceb'], 2, ",", ".");
            }else{
                $expectativaReceb = $value['expectativaReceb'];
            }

            $comissao_descontada = "";
            if($value['comissao_descontada'] <> '-'){
                $comissao_descontada = number_format( $value['comissao_descontada'], 2, ",", ".");
            }else{
                $comissao_descontada = $value['comissao_descontada'];
            }



            $result['data'][$key] = array(
                $value['nome_loja'],
                $value['data_pagamento_mktplace'],
                $value['pago'],
                "<b>R$ ".$valoPedido."</b>",
                "<b>R$ ".$valorProduto."</b>",
                "<b>R$ ".$valorFrete."</b>",
                "<b>R$ ".$expectativaReceb."</b>",
                "<b>R$ ".$comissao_descontada."</b>",
                $observacao,
                $value['marketplace'],
                $value['raz_social'],
                $value['clifor']
            );
        } // /foreach
        // 23 26 27

        if($valor['status'] == "1"){

            echo utf8_decode( "<table border=\"1\">
                    <tr>
                        <th colspan=\"4\"></th>
                    	<th colspan=\"1\">PREVISTO (Considerando as previsões de entrega do pedido e o ciclo de pagamento correspondente)</th>
                    	<th colspan=\"1\">PAGAMENTO AO SELLER (Informa se o Pedido foi \"Pago ou Não Pago\" (Pago  = Repasse Realizado ao Lojista) e em que data foi realizada a transferência bancária)</th>	
                        <th colspan=\"6\"></th>
                        
                    </tr>
                    <tr>
                        <th>Marca</th>
						<th>Clifor</th>
						<th>".$this->lang->line('application_store')."</th>
						<th>Razão Social</th>
                        <th>".$this->lang->line('application_payment_date')." Marca</th>
                        <th>".$this->lang->line('application_order_2')."</th>
                        <th>".$this->lang->line('application_purchase_total')."</th>
                        <th>".$this->lang->line('application_value_products')."</th>
                        <th>".$this->lang->line('application_ship_value')."</th>
                        <th>Expectativa Recebimento</th>
                        <th>Comissão</th>
                        <th>".$this->lang->line('application_extract_obs')."</th>
    	           </tr>");

            foreach($result['data'] as $value){

                echo utf8_decode( "<tr>");
                echo utf8_decode("<td>".$value[9]."</td>");
                echo utf8_decode("<td>".$value[11]."</td>");
                echo utf8_decode("<td>".$value[0]."</td>");
                echo utf8_decode("<td>".$value[10]."</td>");
                echo utf8_decode("<td>".$value[1]."</td>");
                echo utf8_decode("<td>".$value[2]."</td>");
                echo utf8_decode("<td>".$value[3]."</td>");
                echo utf8_decode("<td>".$value[4]."</td>");
                echo utf8_decode("<td>".$value[5]."</td>");
                echo utf8_decode("<td>".$value[6]."</td>");
                echo utf8_decode("<td>".$value[7]."</td>");
                echo utf8_decode("<td>".$value[8]."</td>");
                echo utf8_decode( "</tr>");

            }

            echo "</table>";

        }


    }

    public function buscaobservacaopedido(){

        $inputs = $this->postClean(NULL,TRUE);

        if($inputs['pedido'] <> "" && $inputs['lote'] <> "" ){
            $data = $this->model_billet->buscaobservacaopedidofixo($inputs['lote'], $inputs['pedido'], 1);
        }else{
            $data[0] = array("","","");
        }

        echo json_encode($data);

    }

    public function buscaobservacaopedidolote(){

        $inputs = $this->postClean(NULL,TRUE);

        if($inputs['lote'] <> "" && $inputs['store_id'] <> "" ){
            $data = $this->model_billet->buscaobservacaopedidofixolote($inputs['lote'], $inputs['store_id'], 1);
        }else{
            $data[0] = array("","","");
        }

        echo json_encode($data);

    }

    public function listprevisaocontrole()
    {
        if(!in_array('viewPayment', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->data['page_title'] = 'Prevsão de Recebimento';

        $group_data = $this->model_payment->getPrevisaoPagamentoGridSomatorioLojaControle();
        $this->data['grid_valores_pagamentos'] = $group_data;

        $this->render_template('payment/listprevisaocontrole', $this->data);
    }

    public function extratoprevisaocontrole(){

        /*$result['data'][0] = array("","","","","","","","","");
         echo json_encode($result);
         die;*/

        $result = array('data' => array());

        $data = $this->model_orders->getOrdersData();
        // 	    echo '<pre>';print_r($data);die;
        foreach ($data as $key => $value) {

            $result['data'][$key] = array(
                $value['id'],
                $value['numero_marketplace'],
                $value['date_time'],
                $value['data_entrega'],
                "<b>R$ ".str_replace(".",",",$value['total_order'])."</b>"
            );
        } // /foreach

        echo json_encode($result);


    }

    /************************************************************/

    public function listfiscal(){

        if(!in_array('viewNFS', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $painelFiscal = $this->model_settings->getSettingDatabyName('painel_fiscal_url_manual');

        if($painelFiscal){
            if($painelFiscal['value'] == "URL"){
                $checkVariavel = true;
            }else{
                $checkVariavel = false;
            }
        }

        $this->data['page_title'] = $this->lang->line('application_panel_fiscal');

        $this->data['checkVariavel'] = $checkVariavel;
        $this->render_template('payment/listfiscal', $this->data);
    }

    public function createnfs(){

        if(!in_array('createNFS', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $painelFiscal = $this->model_settings->getSettingDatabyName('painel_fiscal_url_manual');

        if($painelFiscal){
            if($painelFiscal['value'] == "URL"){
                redirect('payment/createnfsurl', 'refresh');
            }
        }

        $dataStores = $this->model_stores->getStoresData();

        $result['store_id'] = "";
        $result['data_ciclo'] = "";

        $this->data['page_title'] = "Cadastrar - ".$this->lang->line('application_panel_fiscal');
        $this->data['hdnLote'] = date('YmdHis').rand(1,1000000);
        $this->data['hdnId'] = 0;
        $this->data['stores'] = $dataStores;
        $this->data['dados'] = $result;

        if ($this->change_date_fiscal_panel == '1')
        {
            $this->render_template('payment/createnfiscal_date', $this->data);
        }
        else
        {
            $this->render_template('payment/createnfiscal', $this->data);
        }
    }

    public function editnfs($id){

        if(!in_array('updateNFS', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $painelFiscal = $this->model_settings->getSettingDatabyName('painel_fiscal_url_manual');

        if($painelFiscal){
            if($painelFiscal['value'] == "URL"){
                redirect('payment/editnfsurl/'.$id, 'refresh');
            }
        }

        //Busca os valores no banco
        $result = $this->model_payment->getnfsgroup($id);

        //Limpa a temp
        $this->model_payment->limpatabelanfsservico($result['lote'],"temp");

        //Salva os dados da tabela final na temp
        $this->model_payment->inseredadostabelanfs($result['lote'],"temp");

        $dataStores = $this->model_stores->getStoresData();

        $this->data['page_title'] = "Editar - ".$this->lang->line('application_panel_fiscal');
        $this->data['hdnLote'] = $result['lote'];
        $this->data['hdnId'] = $id;
        $this->data['stores'] = $dataStores;
        $this->data['dados'] = $result;

        if ($this->change_date_fiscal_panel == '1')
        {
            $this->render_template('payment/createnfiscal_date', $this->data);
        }
        else
        {
            $this->render_template('payment/createnfiscal', $this->data);
        }

    }

    public function viewnfs($id){


        $painelFiscal = $this->model_settings->getSettingDatabyName('painel_fiscal_url_manual');

        if($painelFiscal){
            if($painelFiscal['value'] == "URL"){
                redirect('payment/viewnfsurl/'.$id, 'refresh');
            }
        }

        //Busca os valores no banco
        $result = $this->model_payment->getnfsgroup($id);

        //Limpa a temp
        $this->model_payment->limpatabelanfsservico($result['lote'],"temp");

        //Salva os dados da tabela final na temp
        $this->model_payment->inseredadostabelanfs($result['lote'],"temp");

        $dataStores = $this->model_stores->getStoresData();

        $this->data['page_title'] = "Editar - ".$this->lang->line('application_panel_fiscal');
        $this->data['hdnLote'] = $result['lote'];
        $this->data['hdnId'] = $id;
        $this->data['stores'] = $dataStores;
        $this->data['dados'] = $result;

        $this->render_template('payment/createnfiscal', $this->data);

    }

    public function viewnfsurl($id){


        $painelFiscal = $this->model_settings->getSettingDatabyName('painel_fiscal_url_manual');

        if($painelFiscal){
            if($painelFiscal['value'] == "Manual"){
                redirect('payment/viewnfs/'.$id, 'refresh');
            }
        }

        //Busca os valores no banco
        $result = $this->model_payment->getnfsgroup($id);

        //Limpa a temp
        $this->model_payment->limpatabelanfsservicourl($result['lote'],"temp");

        //Salva os dados da tabela final na temp
        $this->model_payment->inseredadostabelanfsurl($result['lote'],"temp");

        $dataStores = $this->model_stores->getStoresData();

        $this->data['page_title'] = "Editar - ".$this->lang->line('application_panel_fiscal');
        $this->data['hdnLote'] = $result['lote'];
        $this->data['hdnId'] = $id;
        $this->data['stores'] = $dataStores;
        $this->data['dados'] = $result;

        $this->render_template('payment/createnfiscalurl', $this->data);

    }

    public function getciclopagamentoseller(){

        if(in_array('createNFS', $this->permission) || in_array('updateNFS', $this->permission)) {

        }else{
            redirect('dashboard', 'refresh');
        }

        $store_id = $this->postClean('store_id');

        $valorSellercenter = $this->model_settings->getSettingDatabyName('sellercenter');

        // if($valorSellercenter['value'] <> "conectala"){
        //	$result = $this->model_billet->getstoresfromconciliacaosellercenter($store_id);
        //}else{
        $result = $this->model_billet->getdatapagamentoseller($store_id);
        //}

        echo json_encode($result);

    }

    public function testediretorio(){

        $root = getcwd();
        $caminhoMapeado = $root.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'nfs';
        $lote = "123456789";

        if(!is_dir($caminhoMapeado."/")){
            mkdir($caminhoMapeado, 0755, true);
        }

        $caminhoMapeado.= DIRECTORY_SEPARATOR.$lote."/";
        if(!is_dir($caminhoMapeado."/")){
            mkdir($caminhoMapeado, 0755);
        }

    }

    public function uploadArquivoNf(){

        if (!empty($_FILES)) {

            //$group_data1 = $this->model_billet->getMktPlacesDataID($this->postClean('id'));

            $lote=$this->postClean('lote');

            $idBanco = $this->model_payment->getmaxidtemp($lote);

            $apelido = $idBanco['id']."_store_".$this->postClean('store');

            $exp_extens = explode( ".", $_FILES['product_upload']['name']) ;
            $extensao = $exp_extens[count($exp_extens)-1];

            $tempFile = $_FILES['product_upload']['tmp_name'];

            /*if($_SERVER['SERVER_NAME'] == "localhost") {
                $caminhoMapeado="D:/xampp/htdocs/aplicacao/nfs/".$lote."/";

                if(!is_dir($caminhoMapeado."/")){
                    mkdir($caminhoMapeado, 0777);
                }
            }else{
                $caminhoMapeado="/var/www/html/conectala/app/assets/docs/nfs/".$lote."/";

                if(!is_dir($caminhoMapeado."/")){
                    mkdir($caminhoMapeado, 0777);
                }
            }*/

            $root = getcwd();
            $caminhoMapeado = $root.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'nfs';

            if(!is_dir($caminhoMapeado."/")){
                mkdir($caminhoMapeado, 0755, true);
            }

            $caminhoMapeado.= DIRECTORY_SEPARATOR.$lote."/";
            if(!is_dir($caminhoMapeado."/")){
                mkdir($caminhoMapeado, 0755);
            }

            $targetPath = $caminhoMapeado;
            $targetFile =  str_replace('//','/',$targetPath) . $apelido .'-'. $lote . '.' . $extensao;

            move_uploaded_file($tempFile,$targetFile);

            $retorno['ret'] = "sucesso";
            $retorno['extensao'] = $extensao;

            $arquivo = $apelido .'-'. $lote . '.' . $extensao;

            // salva na temp o nome do arquivo
            $this->model_payment->insertnfs($this->postClean('lote'), $this->postClean('store'), $this->postClean('data_ciclo'), $arquivo);

            echo json_encode($retorno);
        }

    }

    public function fetchNfs($hdnLote){

        $result = array('data' => array());

        $data = $this->model_payment->getnfs($hdnLote,null);

        if($data){
            foreach ($data as $key => $value) {

                $buttons = "";

                $saida = explode(".",$value['nome_arquivo']);
                if(in_array('viewNFS', $this->permission)) {
                    $buttons .= ' <a href="' . base_url('assets/docs/nfs/' . $value['lote'] . '/' . $value['nome_arquivo']) . '" class="btn btn-default" download="Loja ' . $value['name'] . ' NF.' . $saida[1] . '"><i class="fa fa-download"></i></a>';
                }
                if(in_array('deletetNFS', $this->permission)) {
                    $buttons .= ' <button type="button" class="btn btn-default" onclick="apaganfs(\'' . $value['id'] . '\')"><i class="fa fa-trash"></i></button>';
                }
                $result['data'][$key] = array(
                    $value['id'],
                    $value['name'],
                    $value['data_ciclo_tratado'],
                    $buttons
                );
            } // /foreach
        }else{
            $result['data'][0] = array("","","","");
        }
        echo json_encode($result);


    }

    public function apaganfsgroup(){

        $id = $this->postClean('id');
        $lote = $this->postClean('lote');

        if($id){
            $data = $this->model_payment->desativaNFSGroup($id);
            if($data){
                echo "0;Nota fiscal excluída com sucesso";
            }else{
                echo "1;Erro ao excluir nota fiscal $data";
            }
        }else{
            echo "1;Erro ao excluir nota fiscal";
        }
    }

    public function apaganfs(){

        $id = $this->postClean('id');
        $lote = $this->postClean('lote');

        if($id){
            $data = $this->model_payment->desativaNFS($id);
            if($data){
                echo "0;Nota fiscal excluída com sucesso";
            }else{
                echo "1;Erro ao excluir nota fiscal $data";
            }
        }else{
            echo "1;Erro ao excluir nota fiscal";
        }
    }

    public function salvarnfs(){

        $inputs = $this->postClean(NULL,TRUE);

        // Caso seja zero é um cadastro novo
        if($inputs['hdnId'] == "0"){
            //Cadastra nfs groups
            $cadastroNFGroup = $this->model_payment->insertnfsgroup($inputs);
            if($cadastroNFGroup){
                //Limpa a tabela final de NFs e sobe os valores novos da temp
                $limpa = $this->model_payment->limpatabelanfsservico($inputs['hdnLote'], null);

                if($limpa){
                    //Insere os dados na tabela final
                    $save = $this->model_payment->inseredadostabelanfs($inputs['hdnLote'],null);
                    if($save){
                        $this->model_payment->limpatabelanfsservico($inputs['hdnLote'],"temp");
                        echo "0;Nota fiscal salva com sucesso";
                    }else{
                        echo "1;Erro ao salvar Nota fiscal $save";
                    }
                }else{
                    echo "1;Erro ao salvar Nota fiscal $limpa";
                }
            }else{
                echo "1;Erro ao salvar Nota fiscal $cadastroNFGroup";
            }
        }else{
            //Atualiza nfs groups
            $cadastroNFGroup = $this->model_payment->updatenfsgroup($inputs);
            if($cadastroNFGroup){
                //Limpa a tabela final de NFs e sobe os valores novos da temp
                $limpa = $this->model_payment->limpatabelanfsservico($inputs['hdnLote'], null);
                if($limpa){
                    //Insere os dados na tabela final
                    $save = $this->model_payment->inseredadostabelanfs($inputs['hdnLote'],null);
                    if($save){
                        $this->model_payment->limpatabelanfsservico($inputs['hdnLote'],"temp");
                        echo "0;Nota fiscal salva com sucesso";
                    }else{
                        echo "1;Erro ao salvar Nota fiscal $save";
                    }
                }else{
                    echo "1;Erro ao salvar Nota fiscal $limpa";
                }
            }else{
                echo "1;Erro ao salvar Nota fiscal $cadastroNFGroup";
            }
        }

    }

    public function fetchnfsgroup(){

        if(!in_array('viewNFS', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $buttons    = '';

        $data = $this->model_payment->getnfsgroup();

        $result = array('data' => array());

        if($data){
            foreach ($data as $key => $value) {

                $buttons    = '';

                if(in_array('updateNFS', $this->permission) || in_array('createNFS', $this->permission)) {
                    $buttons .= '<a href="' . base_url('payment/editnfs/' . $value['id']) . '" class="btn btn-default" data-toggle="tooltip" title="' . $this->lang->line('application_edit') . '"><i class="fa fa-pencil-square-o"></i></a>';
                }elseif(in_array('viewNFS', $this->permission)) {
                    $buttons .= ' <a href="' . base_url('payment/viewnfs/' . $value['id']) . '" class="btn btn-default"><i class="fa fa-eye"></i></a>';
                }
                if(in_array('deletetNFS', $this->permission)) {
                    $buttons .= ' <button type="button" class="btn btn-default" onclick="apaganfs(\'' . $value['id'] . '\')"><i class="fa fa-trash"></i></button>';
                }

                $result['data'][$key] = array(
                    $value['id'],
                    $value['loja'],
                    $value['data_ciclo_tratado'],
                    $buttons
                );

            }
        }else{
            $result['data'][0] = array("","","","");
        }

        echo json_encode($result);
    }

    public function createnfsurl(){

        if(!in_array('createNFS', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $painelFiscal = $this->model_settings->getSettingDatabyName('painel_fiscal_url_manual');

        if($painelFiscal){
            if($painelFiscal['value'] == "Manual"){
                redirect('payment/editnfs/'.$id, 'refresh');
            }
        }

        $dataStores = $this->model_stores->getStoresData();

        $result['store_id'] = "";
        $result['data_ciclo'] = "";

        $this->data['page_title'] = "Cadastrar - ".$this->lang->line('application_panel_fiscal');
        $this->data['hdnLote'] = date('YmdHis').rand(1,1000000);
        $this->data['hdnId'] = 0;
        $this->data['stores'] = $dataStores;
        $this->data['dados'] = $result;

        if ($this->change_date_fiscal_panel == '1')
        {
            $this->render_template('payment/createnfiscalurl_date', $this->data);
        }
        else
        {
            $this->render_template('payment/createnfiscalurl', $this->data);
        }
    }

    public function editnfsurl($id){

        if(!in_array('updateNFS', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $painelFiscal = $this->model_settings->getSettingDatabyName('painel_fiscal_url_manual');

        if($painelFiscal){
            if($painelFiscal['value'] == "Manual"){
                redirect('payment/editnfs/'.$id, 'refresh');
            }
        }

        //Busca os valores no banco
        $result = $this->model_payment->getnfsgroup($id);

        //Limpa a temp
        $this->model_payment->limpatabelanfsservicourl($result['lote'],"temp");

        //Salva os dados da tabela final na temp
        $this->model_payment->inseredadostabelanfsurl($result['lote'],"temp");

        $dataStores = $this->model_stores->getStoresData();

        $this->data['page_title'] = "Editar - ".$this->lang->line('application_panel_fiscal');
        $this->data['hdnLote'] = $result['lote'];
        $this->data['hdnId'] = $id;
        $this->data['stores'] = $dataStores;
        $this->data['dados'] = $result;

        $url_exist = $this->model_payment->checkIfExistNfsUrlByStoreIdAndLote($result['store_id'], $result['lote']);
        $invoice_number_exist = $this->model_payment->checkIfExistNfsInvoiceNumberByStoreIdAndLote($result['store_id'], $result['lote']);
        $this->data['show_extra_details_nfs'] = !$url_exist && $invoice_number_exist;

        if ($this->change_date_fiscal_panel == '1')
        {
            $this->render_template('payment/createnfiscalurl_date', $this->data);
        }
        else
        {
            $this->render_template('payment/createnfiscalurl', $this->data);
        }

    }

    public function fetchnfsurl($hdnLote){

        $result = array('data' => array());

        $data = $this->model_payment->getnfsurl($hdnLote,null);

        $stores = array_unique(array_map(function($item){
            return (int)$item['store_id'];
        }, $data));
        sort($stores);

        $url_exist = $this->model_payment->checkIfExistNfsUrlByStoreIdAndLote($stores, $hdnLote);
        $invoice_number_exist = $this->model_payment->checkIfExistNfsInvoiceNumberByStoreIdAndLote($stores, $hdnLote);
        $show_extra_details_nfs = !$url_exist && $invoice_number_exist;

        if($data){
            foreach ($data as $key => $value) {
                $field = array(
                    $value['id'],
                    $value['name'],
                    $value['data_ciclo_tratado']
                );

                if ($show_extra_details_nfs) {
                    $field[] = $value['invoice_number'];
                    $field[] = dateBrazil($value['invoice_emission_date']);
                    $field[] = money($value['invoice_amount_total']);
                    $field[] = money($value['invoice_amount_irrf']);
                } else {
                    $buttons = "";

                    // $buttons .= ' <a href="'.$value['url'].'" class="btn btn-default" target="_blank"><i class="fa fa-download"></i></a>';
                    if (in_array('viewNFS', $this->permission)) {
                        $buttons .= ' <button type="button" class="btn btn-default" onclick="abrelink(\'' . $value['url'] . '\')"><i class="fa fa-download"></i></button>';
                    }

                    if (in_array('deletetNFS', $this->permission)) {
                        $buttons .= ' <button type="button" class="btn btn-default" onclick="apaganfs(\'' . $value['id'] . '\')"><i class="fa fa-trash"></i></button>';
                    }

                    $field[] = $buttons;
                }

                $result['data'][$key] = $field;
            }
        }else{
            $result['data'][0] = array("","","","");
        }
        echo json_encode($result);


    }

    public function uploadarquivonfurl(){

        if (!empty($_FILES)) {

            //$group_data1 = $this->model_billet->getMktPlacesDataID($this->postClean('id'));

            $lote=date('YmdHis').rand(1,1000000);
            // $this->postClean('lote');

            $idBanco = $this->model_payment->getmaxidtemp($lote);

            $apelido = $idBanco['id'];

            $exp_extens = explode( ".", $_FILES['phase_upload']['name']) ;
            $extensao = $exp_extens[count($exp_extens)-1];

            $tempFile = $_FILES['phase_upload']['tmp_name'];

            $root = getcwd();
            $caminhoMapeado = $root.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'nfs';

            if(!is_dir($caminhoMapeado."/")){
                mkdir($caminhoMapeado, 0755, true);
            }

            $caminhoMapeado.= DIRECTORY_SEPARATOR.$lote."/";
            if(!is_dir($caminhoMapeado."/")){
                mkdir($caminhoMapeado, 0755);
            }

            $targetPath = $caminhoMapeado;
            $targetFile =  str_replace('//','/',$targetPath) . $apelido .'-'. $lote . '.' . $extensao;

            move_uploaded_file($tempFile,$targetFile);

            $retorno['ret'] = "sucesso";
            $retorno['extensao'] = $extensao;

            $arquivo = $apelido .'-'. $lote . '.' . $extensao;

            $inputs['lote'] = $lote;
            $inputs['arquivo'] = $arquivo;
            $inputs['caminho'] = $caminhoMapeado;

            //Le arquivo
            if (file_exists($caminhoMapeado.$arquivo)){

                $checkArquivo = true;

                error_reporting(1);
                //import lib excel
                require_once (APPPATH . '/third_party/PHPExcel/IOFactory.php');
                $objPHPExcel = PHPExcel_IOFactory::load($caminhoMapeado.$arquivo);

                $testeLinha = 1;

                foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {

                    $worksheetTitle     = $worksheet->getTitle();
                    $highestRow         = $worksheet->getHighestRow(); // e.g. 10
                    $highestColumn      = $worksheet->getHighestColumn(); // e.g 'F'
                    $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
                    $nrColumns = ord($highestColumn) - 64;

                    //lê a linha
                    for ($row = 1; $row <= $highestRow; ++ $row) {

                        $valorColuna = 1;
                        $arrayValores = array();

                        //lê a coluna
                        for ($col = 0; $col < $highestColumnIndex; ++ $col) {
                            $cell = $worksheet->getCellByColumnAndRow($col, $row);
                            $val = $cell->getValue();
                            $arrayValores[$valorColuna] = $val;
                            $valorColuna++;
                        }


                        if($testeLinha == "1"){

                            $testeLinha ++;

                            $cabecalho[1] = "Id Loja";
                            $cabecalho[2] = "Ciclo";
                            $cabecalho[3] = "Url";

                            $count = 1;
                            foreach ($arrayValores as $colunas){
                                if( $colunas <> $cabecalho[$count]){
                                    $checkArquivo = false;
                                }
                                $count++;
                            }

                        }else{

                            $testeLinha ++;

                            if($checkArquivo){

                                if(strpos($arrayValores[2], "/") == false){
                                    $UNIX_DATE = ($arrayValores[2] - 25569) * 86400;
                                    $arrayValores[2] = gmdate("d/m/Y", $UNIX_DATE);
//                                    $arrayValores[2] = date('Y-m-d', PHPExcel_Shared_Date::ExcelToPHP($arrayValores[2]));
                                }

                                $arrayValores[2] = date('Y-m-d', strtotime(str_replace('/', '-', $arrayValores[2])));

                                $save = $this->model_payment->salvavaloresnfsurlmassa($inputs,$arrayValores);
                                if ($save == false){
                                    $this->session->set_flashdata('error', $this->lang->line('application_insert_invoice_massa_error'));
                                    redirect('payment/createnfsurlmassa', 'refresh');
                                }

                            }
                        }
                    }
                }


            }else{
                $this->session->set_flashdata('error', $this->lang->line('application_insert_invoice_massa_error'));
                redirect('payment/createnfsurlmassa', 'refresh');
            }

            //Após a carga é feito o salvamento do arquivo
            $inputs['hdnLote'] = $lote;

            $cadastroNFGroup = $this->model_payment->insertnfsgroupurl($inputs);

            if ($cadastroNFGroup) {
                $this->session->set_flashdata('success', $this->lang->line('application_insert_invoice_massa_sucess'));
                redirect('payment/createnfsurlmassa', 'refresh');
            } else {
                $this->session->set_flashdata('error', $this->lang->line('application_insert_invoice_massa_error'));
                redirect('payment/createnfsurlmassa', 'refresh');
            }

        }else{
            $this->session->set_flashdata('error', $this->lang->line('application_insert_invoice_massa_error'));
            redirect('payment/createnfsurlmassa', 'refresh');
        }

    }

    public function apaganfsurl(){

        $id = $this->postClean('id');
        $lote = $this->postClean('lote');

        if($id){
            $data = $this->model_payment->desativaNFSurl($id);
            if($data){
                echo "0;Nota fiscal excluída com sucesso";
            }else{
                echo "1;Erro ao excluir nota fiscal $data";
            }
        }else{
            echo "1;Erro ao excluir nota fiscal";
        }
    }

    public function addnfsurl(){

        $post = $this->postClean(NULL,TRUE);

        if($post['hdnLote'] <> "" && $post['slc_store'] <> "" && $post['slc_ciclo_fiscal'] <> "" && $post['txt_url'] <> ""){

            $input['lote'] = $post['hdnLote'];
            $arrayValores[1] = $post['slc_store'];
            $arrayValores[2] = $post['slc_ciclo_fiscal'];
            $arrayValores[3] = $post['txt_url'];

            $data = $this->model_payment->salvavaloresnfsurlmassa($input, $arrayValores);
            if($data){
                echo "0;Nota fiscal cadastrada com sucesso";
            }else{
                echo "1;Erro ao cadastrar nota fiscal $data";
            }
        }else{
            echo "1;Erro ao cadastrar nota fiscal";
        }
    }

    public function salvarnfsurl(){

        $inputs = $this->postClean(NULL,TRUE);

        // Caso seja zero é um cadastro novo
        if($inputs['hdnId'] == "0"){
            //Cadastra nfs groups
            $cadastroNFGroup = $this->model_payment->insertnfsgroupurl($inputs);
            if($cadastroNFGroup){
                echo "0;Nota fiscal salva com sucesso";
            }else{
                echo "1;Erro ao salvar Nota fiscal $cadastroNFGroup";
            }
        }else{
            //Atualiza nfs groups
            $cadastroNFGroup = $this->model_payment->updatenfsgroupurl($inputs);
            if($cadastroNFGroup){
                echo "0;Nota fiscal salva com sucesso";
            }else{
                echo "1;Erro ao salvar Nota fiscal $cadastroNFGroup";
            }
        }

    }

    public function createnfsurlmassa(){

        if(!in_array('createNFS', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $painelFiscal = $this->model_settings->getSettingDatabyName('painel_fiscal_url_manual');
        if($painelFiscal){
            if($painelFiscal['value'] == "Manual"){
                redirect('payment/editnfs/'.$id, 'refresh');
            }
        }

        $this->data['page_title'] = "Cadastrar - ".$this->lang->line('application_panel_fiscal');
        $this->data['hdnLote'] = date('YmdHis').rand(1,1000000);
        $this->data['hdnId'] = 0;

        $this->render_template('payment/createnfsurlmassa', $this->data);

    }


    /************************************************************/


    public function extratoparceiro(){

        if(!in_array('viewExtract', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $valor = $this->model_settings->getSettingDatabyNameEmptyArray('gsoma_painel_financeiro');

        $group_data1 = $this->model_billet->getMktPlacesData();

        $group_loja = $this->model_payment->buscalojafiltro();
        $j = 0;
        $statusSaida = array();
        for($i=0;$i<=101;$i++){
            $status = $this->model_iugu->statuspedido($i);
            if($status <> false){

                $flag = false;
                foreach($statusSaida as $verifica){
                    if($verifica['status'] == $status){
                        $flag = true;
                    }
                }

                if($flag == false){
                    $statusSaida[$j]['id'] = $i;
                    $statusSaida[$j]['status'] = $status;
                    $j++;
                }

            }
        }

        $this->data['page_title'] = 'Extrato';
        $this->data['mktplaces'] = $group_data1;
        $this->data['perfil'] = $_SESSION['group_id'];
        $this->data['filtrosts'] = $statusSaida;
        $this->data['filtrostore'] = $group_loja;
        $this->data['gsoma'] = $valor['status'];

        $this->render_template('payment/extratoparceiro', $this->data);

    }

    public function extratopedidosparceiro(){

        if (!in_array('viewExtract', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $valor = $this->model_settings->getSettingDatabyNameEmptyArray('gsoma_painel_financeiro');

        $result = array('data' => array());

        $inputs = cleanArray($this->input->get());
        $inputs = $this->input;
        $post = $inputs->post();
        $inputs = cleanArray($inputs->get());

        if($inputs['slc_status'] <> ""){
            //Busca todos os status pelo selecionado na tela
            $statusTela = $this->model_iugu->statuspedido($inputs['slc_status']);
            $j = 0;
            $inputs['slc_status'] = $inputs['slc_status'];
            $statusFiltro = array();
            for($i=0;$i<=101;$i++){
                $status = $this->model_iugu->statuspedido($i);
                if($status <> false){
                    if($status == $statusTela){
                        $statusFiltro[$j] = $i;
                        $j++;
                    }
                }
            }
            $filtroFinal = implode(",", $statusFiltro);
            $inputs['slc_status'] = $filtroFinal;
        }
        $sOrder = null;
        if (isset($post['order'])) {
            if ($post['order'][0]['dir'] == "asc") $direction = "ASC";
            else $direction = "DESC";

            if ($valor['status'] == "1") {
                $fields = array(
                    'E.id',
                    'paid_status',
                    "IFNULL( STR_TO_DATE(date_time,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                    "IFNULL( STR_TO_DATE(data_entrega,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                    "IFNULL( STR_TO_DATE(data_pagamento_mktplace,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                    "IFNULL( STR_TO_DATE(data_transferencia,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                    'expectativaReceb',
                    'discount',
                    'comission',
                    '',
                    'store_name',
                    "IFNULL( STR_TO_DATE(data_envio,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )"
                );
            } else {
                if (array_key_exists("extratonovo", $inputs)) {
                    $fields = array(
                        'E.id',
                        'paid_status',
                        "IFNULL( STR_TO_DATE(date_time,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                        "IFNULL( STR_TO_DATE(data_envio,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                        "IFNULL( STR_TO_DATE(data_entrega,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                        'data_caiu_na_conta',
                        'tratado',
                        "IFNULL( STR_TO_DATE(data_transferencia,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                        'valor_parceiro',
                        ''
                    );
                } else {
                    $fields = array(
                        'E.id',
                        'paid_status',
                        "IFNULL( STR_TO_DATE(date_time,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                        "IFNULL( STR_TO_DATE(data_entrega,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                        "IFNULL( STR_TO_DATE(data_pagamento_mktplace,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                        "IFNULL( STR_TO_DATE(data_pagamento_conectala,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                        "IFNULL( STR_TO_DATE(data_transferencia,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                        "IFNULL( STR_TO_DATE(data_transferencia,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )",
                        'expectativaReceb',
                        'valor_parceiro',
                        'tratado',
                        '',
                        "IFNULL( STR_TO_DATE(data_envio,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )"
                    );
                }
            }

            $field = $fields[$post['order'][0]['column']];
            if ($field != "") $sOrder = " ORDER BY {$field} {$direction} ";
        }
        $data = $this->model_billet->getPedidosExtratoConciliadoParceiro($inputs, $sOrder, $post);
        $count = $data['count'];
        $data = $data['data'];
        foreach ($data as $key => $value) {

            $buttons    = '';

            $status = $this->model_iugu->statuspedido($value['paid_status']);

            $observacao = "";

            if ( $value['observacao'] <> "" ){
                $observacao = ' <button type="button" class="btn btn-default" onclick="listarObservacao(\''.$value['numero_pedido'].'\', \''.$value['lote'].'\')" data-toggle="modal" data-target="#listaObs"><i class="fa fa-eye"></i></button>';
            }

            if ( $value['numero_chamado'] <> "" && $observacao <> ""){
                $observacao = ' <button type="button" class="btn btn-default" onclick="listarObservacao(\''.$value['numero_pedido'].'\', \''.$value['lote'].'\')" data-toggle="modal" data-target="#listaObs"><i class="fa fa-eye"></i></button>';
            }

            $expectativaReceb = "";
            if($value['expectativaReceb'] <> '-'){
                $expectativaReceb = number_format( $value['expectativaReceb'], 2, ",", ".");
            }else{
                $expectativaReceb = $value['expectativaReceb'];
            }

            $valor_parceiro = "";
            if($value['valor_parceiro'] <> '-'){
                $valor_parceiro = number_format( $value['valor_parceiro'], 2, ",", ".");
            }else{
                $valor_parceiro = $value['valor_parceiro'];
            }


            if($valor['status'] == "1"){
                $result['data'][$key] = array(
                    $value['id'],
                    $status,
                    $value['date_time'],
                    $value['data_entrega'],
                    $value['data_pagamento_mktplace'],
                    $value['pago'],
                    "<b>R$ ".$expectativaReceb."</b>",
                    $observacao
                );

            }else{
                $result['data'][$key] = array(
                    $value['id'],
                    $status,
                    $value['date_time'],
                    $value['data_entrega'],
                    $value['data_pagamento_mktplace'],
                    $value['data_pagamento_conectala'],
                    $value['pago'],
                    $value['data_transferencia'],
                    "<b>R$ ".$expectativaReceb."</b>",
                    "<b>R$ ".$valor_parceiro."</b>",
                    $value['tratado'],
                    $observacao
                );
            }



        } // /foreach

        $count = (!$count) ? 0 : $count;

        if(!$result['data']){
            if ($valor['status'] == "1") {
                $result['data'][0] = array("","","","","","","","","","","","");
            }else{
                $result['data'][0] = array("","","","","","","","","","","","","");
            }
        }

        $result = [
            'draw' => $post['draw'],
            'recordsTotal' => $count,
            'recordsFiltered' => $count,
            'data' => $result['data']
        ];


        echo json_encode($result);

    }

    public function extratopedidosresumoparceiro(){

        if(!in_array('viewExtract', $this->permission)) {
            echo json_encode([]);
            die;
        }
        $out = [];
        $result = array('data' => array());

        $inputs = cleanArray($this->input->get());

        if($inputs['slc_status'] <> ""){
            //Busca todos os status pelo selecionado na tela
            $statusTela = $this->model_iugu->statuspedido($inputs['slc_status']);
            $j = 0;
            $statusFiltro = array();
            for($i=0;$i<=101;$i++){
                $status = $this->model_iugu->statuspedido($i);
                if($status <> false){
                    if($status == $statusTela){
                        $statusFiltro[$j] = $i;
                        $j++;
                    }
                }
            }
            $filtroFinal = implode(",", $statusFiltro);
            $inputs['slc_status'] = $filtroFinal;
        }

        $data = $this->model_billet->getPedidosExtratoConciliadoResumoParceiro($inputs);
        $count = $data['count'];
        $data = $data['data'];
        if (!empty($data)) {
            # code...
            foreach ($data as $key => $value) {

                $valor_parceiro = "";
                if($value['valor_parceiro'] <> '-'){
                    $valor_parceiro = number_format( $value['valor_parceiro'], 2, ",", ".");
                }else{
                    $valor_parceiro = $value['valor_parceiro'];
                }

                $out[$key] = array(
                    $value['marketplace'],
                    $value['data_transferencia'],
                    "<b>R$ ".$valor_parceiro."</b>"
                );

                $result = [
                    'draw' => 0,
                    'recordsTotal' => $count,
                    'recordsFiltered' => $count,
                    'data' => $out
                ];

            } // /foreach
        } else {
            $result = [
                'draw' => 0,
                'recordsTotal' => $count,
                'recordsFiltered' => $count,
                'data' => []
            ];
        }

        echo json_encode($result);

    }

    public function extratopedidosexcelparceiro(){

        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        header("Pragma: public");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: pre-check=0, post-check=0, max-age=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        header("Content-Transfer-Encoding: none");
        header("Content-Type: application/vnd.ms-excel;");
        header("Content-type: application/x-msexcel;");
        header("Content-Disposition: attachment; filename=Extrato.xls");

        $result = array('data' => array());

        $valor = $this->model_settings->getSettingDatabyNameEmptyArray('gsoma_painel_financeiro');

        $inputs = cleanArray($this->input->get());

        if($inputs['slc_status'] <> ""){
            //Busca todos os status pelo selecionado na tela
            $statusTela = $this->model_iugu->statuspedido($inputs['slc_status']);
            $j = 0;
            $statusFiltro = array();
            for($i=0;$i<=101;$i++){
                $status = $this->model_iugu->statuspedido($i);
                if($status <> false){
                    if($status == $statusTela){
                        $statusFiltro[$j] = $i;
                        $j++;
                    }
                }
            }
            $filtroFinal = implode(",", $statusFiltro);
            $inputs['slc_status'] = $filtroFinal;
        }

        $data = $this->model_billet->getPedidosExtratoConciliadoParceiro($inputs,"excel");
        ob_end_clean();
        foreach ($data['data']  as $key => $value) {

            $buttons    = '';

            $status = $this->model_iugu->statuspedido($value['paid_status']);

            $observacao = "";

            if ( $value['observacao'] <> "" ){
                $observacao .= $value['observacao'];
            }

            if ( $value['numero_chamado'] <> "" ){
                $observacao .= ' <br>Aberto o chamado '.$value['numero_chamado'].' junto ao marketplace '.$value['marketplace'].' para esclarecimento do pedido';
                if($value['previsao_solucao'] <> "" && $value['previsao_solucao'] <> "00/00/0000"){
                    $observacao .= ', com previsao de retorno em '.$value['previsao_solucao'];
                }
            }

            $valoPedido = "";
            if($value['gross_amount'] <> '-'){
                $valoPedido = number_format( $value['gross_amount'], 2, ",", ".");
            }else{
                $valoPedido = $value['gross_amount'];
            }

            $valorProduto = "";
            if($value['total_order'] <> '-'){
                $valorProduto = number_format( $value['total_order'], 2, ",", ".");
            }else{
                $valorProduto = $value['total_order'];
            }

            $valorFrete = "";
            if($value['total_ship'] <> '-'){
                $valorFrete = number_format( $value['total_ship'], 2, ",", ".");
            }else{
                $valorFrete = $value['total_ship'];
            }

            $expectativaReceb = "";
            if($value['expectativaReceb'] <> '-'){
                $expectativaReceb = number_format( $value['expectativaReceb'], 2, ",", ".");
            }else{
                $expectativaReceb = $value['expectativaReceb'];
            }

            $valor_parceiro = "";
            if($value['valor_parceiro'] <> '-'){
                $valor_parceiro = number_format( $value['valor_parceiro'], 2, ",", ".");
            }else{
                $valor_parceiro = $value['valor_parceiro'];
            }

            $comissao_descontada = "";
            if($value['comissao_descontada'] <> '-'){
                $comissao_descontada = number_format( $value['comissao_descontada'], 2, ",", ".");
            }else{
                $comissao_descontada = $value['comissao_descontada'];
            }



            $result['data'][$key] = array(
                $value['id'],
                $value['marketplace'],
                $value['numero_pedido'],
                $status,
                $value['date_time'],
                $value['data_entrega'],
                $value['data_pagamento_mktplace'],
                $value['data_pagamento_conectala'],
                $value['data_recebimento_mktpl'],
                $value['data_caiu_na_conta'],
                $value['pago'],
                $value['data_transferencia'],
                "<b>R$ ".$valoPedido."</b>",
                "<b>R$ ".$valorProduto."</b>",
                "<b>R$ ".$valorFrete."</b>",
                "<b>R$ ".$expectativaReceb."</b>",
                "<b>R$ ".$valor_parceiro."</b>",
                $observacao,
                $value['nome_loja'],
                "<b>R$ ".$comissao_descontada."</b>"
            );
        } // /foreach
        // 23 26 27

        if($valor['status'] == "1"){

            echo utf8_decode( "<table border=\"1\">
                        <tr>
                            <th colspan=\"7\"></th>
                        	<th colspan=\"1\">PREVISTO (Considerando as previsões de entrega do pedido e o ciclo de pagamento correspondente)</th>
                        	<th colspan=\"1\">PAGAMENTO AO SELLER (Informa se o Pedido foi \"Pago ou Não Pago\" (Pago  = Repasse Realizado ao Lojista) e em que data foi realizada a transferência bancária)</th>
                            <th colspan=\"6\"></th>
    	        
                        </tr>
                        <tr>
                    	    <th>".$this->lang->line('application_id')." - Pedido</th>
                            <th>Marca</th>
                            <th>".$this->lang->line('application_store')."</th>
                            <th>".$this->lang->line('application_purchase_id')."</th>
                            <th>".$this->lang->line('application_status')."</th>
                    		<th>".$this->lang->line('application_date')." Pedido</th>
                            <th>".$this->lang->line('application_date')." de Entrega</th>
                            <th>".$this->lang->line('application_payment_date')." Marca</th>
                            <th>".$this->lang->line('application_order_2')."</th>
                            <th>".$this->lang->line('application_purchase_total')."</th>
                            <th>".$this->lang->line('application_value_products')."</th>
                            <th>".$this->lang->line('application_ship_value')."</th>
                            <th>Expectativa Recebimento</th>
                            <th>Comissão</th>
                            <th>".$this->lang->line('application_extract_obs')."</th>
        	           </tr>");

            foreach($result['data'] as $value){

                echo utf8_decode( "<tr>");
                echo utf8_decode("<td>".$value[0]."</td>");
                echo utf8_decode("<td>".$value[1]."</td>");
                echo utf8_decode("<td>".$value[18]."</td>");
                echo utf8_decode("<td>".$value[2]."</td>");
                echo utf8_decode("<td>".$value[3]."</td>");
                echo utf8_decode("<td>".$value[4]."</td>");
                echo utf8_decode("<td>".$value[5]."</td>");
                echo utf8_decode("<td>".$value[6]."</td>");
                echo utf8_decode("<td>".$value[10]."</td>");
                echo utf8_decode("<td>".$value[12]."</td>");
                echo utf8_decode("<td>".$value[13]."</td>");
                echo utf8_decode("<td>".$value[14]."</td>");
                echo utf8_decode("<td>".str_replace("ã","a",$value[15])."</td>");
                echo utf8_decode("<td>".$value[19]."</td>");
                echo utf8_decode("<td>".$value[17]."</td>");
                echo utf8_decode( "</tr>");

            }

            echo "</table>";

        }else{

            echo "<table border=\"1\">
                        <tr>
                            <th colspan=\"6\"></th>
                        	<th colspan=\"2\">PREVISTO (Considerando as previsões de entrega do pedido e o ciclo de pagamento correspondente)</th>
                        	<th colspan=\"2\">REALIZADO (Considerando todos os pedidos que foram pagos pelos marketplaces e passaram pelo processo de conciliação)</th>
                        	<th colspan=\"2\">PAGAMENTO AO SELLER (Informa se o Pedido foi \"Pago ou Não Pago\" (Pago  = Repasse Realizado ao Lojista) e em que data foi realizada a transferência bancária)</th>
                            <th colspan=\"6\"></th>
    	        
                        </tr>
                        <tr>
                    	    <th>".$this->lang->line('application_id')." - Pedido</th>
                            <th>".$this->lang->line('application_marketplace')."</th>
                            <th>".$this->lang->line('application_purchase_id')."</th>
                            <th>".$this->lang->line('application_status')."</th>
                    		<th>".$this->lang->line('application_date')." Pedido</th>
                            <th>".$this->lang->line('application_date')." de Entrega</th>
                            <th>".$this->lang->line('application_payment_date')." Marketplace</th>
                            <th>".$this->lang->line('application_payment_date_conecta')."</th>
                            <th>Data que recebemos o repasse</th>
                            <th>Data em que pagamos</th>
                            <th>".$this->lang->line('application_order_2')."</th>
                            <th>".$this->lang->line('application_date')." ".$this->lang->line('application_bank_transfer')."</th>
                            <th>".$this->lang->line('application_purchase_total')."</th>
                            <th>".$this->lang->line('application_value_products')."</th>
                            <th>".$this->lang->line('application_ship_value')."</th>
                            <th>Expectativa Recebimento</th>
                            <th>Valor pago pelo Marketplace</th>
                            <th>".$this->lang->line('application_extract_obs')."</th>
        	           </tr>";
            // <th>Pedido Conciliado</th>
            foreach($result['data'] as $value){

                echo "<tr>";
                echo"<td>".$value[0]."</td>";
                echo"<td>".$value[1]."</td>";
                echo"<td>".$value[2]."</td>";
                echo"<td>".$value[3]."</td>";
                echo"<td>".$value[4]."</td>";
                echo"<td>".$value[5]."</td>";
                echo"<td>".$value[6]."</td>";
                echo"<td>".$value[7]."</td>";
                echo"<td>".$value[8]."</td>";
                echo"<td>".str_replace("ã","a",$value[9])."</td>";
                echo"<td>".$value[10]."</td>";
                echo"<td>".$value[11]."</td>";
                echo"<td>".$value[12]."</td>";
                echo"<td>".$value[13]."</td>";
                echo"<td>".$value[14]."</td>";
                echo"<td>".str_replace("ã","a",$value[15])."</td>";
                echo"<td>".$value[16]."</td>";
                echo"<td>".$value[17]."</td>";
                echo "</tr>";

            }

            echo "</table>";

        }


    }


    /***********************************************************/

    public function listprevisaosellercenter($data = null)
    {

        if(!in_array('viewPaymentForecast', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        //Nova previsão
        $datasASeremPagas  = $this->model_billet->getDatasPagamentoPrevisaoExtratoConciliado("tela");

        $dataSaida = array();
        $i = 0;
        foreach($datasASeremPagas as $datasASeremPaga){
            $flagAchou = false;

            foreach($dataSaida as $buscaData){
                if($buscaData['data'] == $datasASeremPaga['data_pagamento_mktplace']){
                    $flagAchou = true;
                }
            }

            if($flagAchou == false){
                $dataSaida[$i]['data'] = $datasASeremPaga['data_pagamento_mktplace'];
                $i++;
            }

        }

        $ndata = array();
        foreach ($dataSaida as $key => $row) {
            //Inverte a data
            $ndata[$key] = \DateTime::createFromFormat('d/m/Y', $row['data'])->format('Y-m-d');
        }

        //ordena
        array_multisort($ndata, SORT_ASC, $dataSaida);

        $valorNM = $this->model_settings->getSettingDatabyNameEmptyArray('novomundo_painel_financeiro');

        $this->data['nmundo'] = $valorNM['status'];
        $this->data['dataSaida'] = $dataSaida;

        $this->render_template('payment/listprevisao2gsoma', $this->data);
    }

    public function extratoprevisao2gsoma(){

        $result = array('data' => array());
        $aux = 0;

        $dataValores       = $this->model_billet->getPrevisaoExtratoConciliadogsoma();
        //Busca as datas
        $datasASeremPagas  = $this->model_billet->getDatasPagamentoPrevisaoExtratoConciliado("tela");

        $dataSaida = array();
        $i = 0;
        $aux = "";
        $mktplaces = array();
        $j = 0;

        //Coloca as datas
        foreach($datasASeremPagas as $datasASeremPaga){
            $flagAchou = false;

            foreach($dataSaida as $buscaData){
                if($buscaData['data'] == $datasASeremPaga['data_pagamento_mktplace']){
                    $flagAchou = true;
                }
            }

            if($flagAchou == false){
                $dataSaida[$i]['data'] = $datasASeremPaga['data_pagamento_mktplace'];
                $i++;
            }

        }

        $ndata = array();
        foreach ($dataSaida as $key => $row) {
            //Inverte a data
            $ndata[$key] = \DateTime::createFromFormat('d/m/Y', $row['data'])->format('Y-m-d');
        }

        //ordena
        array_multisort($ndata, SORT_ASC, $dataSaida);

        $dataSaida = array();
        foreach($ndata as $key => $row) {
            $dataSaida[$key]['data'] = \DateTime::createFromFormat('Y-m-d', $row)->format('d/m/Y');
        }

        //Coloca os Marketplaces
        foreach($datasASeremPagas as $datasASeremPaga){
            if($datasASeremPaga['marktplace'] <> $aux){
                $i = 0;
                $flagMktplace = false;
                foreach($dataSaida as $datas){
                    $dataSaida[$i][$datasASeremPaga['marktplace']] = "-";
                    $i++;
                }

                if($flagMktplace == false){
                    if (!in_array($datasASeremPaga['marktplace'], $mktplaces)) {
                        $mktplaces[$j] = $datasASeremPaga['marktplace'];
                        $j++;
                        $flagMktplace = true;
                    }
                }

            }
            $aux = $datasASeremPaga['marktplace'];
        }

        //ajusta o - para 0 as datas que tem repasse no mktplace
        foreach($datasASeremPagas as $datasASeremPaga){
            $i = 0;
            foreach($dataSaida as $datas){
                if($datasASeremPaga['data_pagamento_mktplace'] == $datas['data']){
                    $dataSaida[$i][$datasASeremPaga['marktplace']] = "R$ 0,00";
                }
                $i++;
            }
        }

        //Adiciona os valores a serem pagos por data e mktplace
        foreach($dataValores as $dataValore){
            $i=0;
            foreach($dataSaida as $datas){
                if($datas['data'] == $dataValore['data_pagamento_mktplace']){
                    $dataSaida[$i][$dataValore['marketplace']] = "R$ ".number_format($dataValore['expectativaReceb'], 2, ",", ".");
                }
                $i++;
            }
        }

        $arrayTela = array();
        $z = 0;
        //Monta o array para a tela
        foreach($mktplaces as $mktplace){
            $arrayTela[$z][0] = $mktplace;
            $controle = 1;
            foreach($dataSaida as $valores){
                $arrayTela[$z][$controle] = $valores[$mktplace];
                $controle++;
            }
            $z++;
        }

        $result['data'] = $arrayTela;

        echo json_encode($result);



    }

    public function extratoprevisaodataspagamentogsoma()
    {

        $datasASeremPagas = $this->model_billet->getDatasPagamentoPrevisaoExtratoConciliado();

        $result = array('data' => array());
        $aux = 0;
        foreach ($datasASeremPagas as $key => $value) {

            $result['data'][$aux] = array(
                'marktplace' => $value['marktplace'],
                'data_inicio' => $value['data_inicio'],
                'data_fim' => $value['data_fim'],
                'data_pagamento_mktplace' => $value['data_pagamento_mktplace'],
                'data_corte' => $value['data_corte']
            );
            $aux++;

        }

        echo json_encode($result);


    }

    public function getInteractionsPaymentByOrder(int $order, int $payment)
    {
        $response = array();
        if ($this->model_orders->verifyOrderOfStore($order)) {
            $response = $this->model_order_payment_transactions->getTransaction(array('order_id' => $order, 'payment_id' => $payment), 'id, status, description, interaction_date');
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

}