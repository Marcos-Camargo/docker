<?php
defined('BASEPATH') or exit('No direct script access allowed');

use App\Libraries\Enum\AnticipationStatusEnum;
use App\Libraries\Enum\AnticipationStatusFilterEnum;

/**
 * @property Model_stores $model_stores
 * @property Model_orders $model_orders
 * @property Model_orders_conciliation_installments $model_orders_conciliation_installments
 * @property Model_anticipation_limits_store $model_anticipation_limits_store
 * @property Model_simulations_anticipations_store $model_simulations_anticipations_store
 * @property Model_orders_simulations_anticipations_store $orders_simulations_anticipations_store
 */
class AnticipationsManagement extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->not_logged_in();

        $this->data['page_title'] = $this->lang->line('application_payment_gateway_settings');

        $this->load->model('model_stores');
        $this->load->model('model_orders');
        $this->load->model('model_gateway');
        $this->load->model('model_orders_conciliation_installments');
        $this->load->model('model_anticipation_limits_store');
        $this->load->model('model_simulations_anticipations_store');
        $this->load->model('model_orders_simulations_anticipations_store');

        //Libraries
        $this->load->library('parser');
        $this->load->library('PagarmeLibrary');

        //Starting Pagar.me integration library
        $this->integration = new PagarmeLibrary();

    }

    /*
    * It only redirects to the manage product page and
    */
    public function index()
    {

        if (!in_array('viewAnticipationSimulation', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->data['page_title'] = $this->lang->line('application_payment_anticipation_management_title');
        $this->data['pageinfo'] = $this->data['pageinfo'] ?? 'application_payment_anticipation_management_title';

        $userStores = $this->model_stores->getStoresData();

        $this->data['filter_stores'] = $userStores;
        $this->data['filterable_status'] = AnticipationStatusFilterEnum::generateList();

        $entry = [];
        $entry['orders'] = [];
        $entry['store'] = count($this->data['filter_stores']) == 1 ? (string)$userStores[0]['id'] : '';
        $entry['order_id'] = '';
        $entry['installments_number'] = [];
        $entry['status'] = AnticipationStatusFilterEnum::NORMAL;
        $entry['order_date'] = ['start' => '', 'end' => ''];
        $entry['simulation_id'] = '';
        $entry['anticipated_only'] = 0;
        $this->data['entry'] = $entry;

        $this->render_template('anticipationsmanagement/anticipationsmanagement_index', $this->data);

    }

    public function load_index_data()
    {

        if (!in_array('viewAnticipationSimulation', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $request = json_decode($this->input->raw_input_stream, true);

        $storeIds = [];

        if ($request['store']) {
            $storeIds[] = $request['store'];
            $this->cancelAllBuildingSimulationsByStoreId((int)$request['store']);
        } elseif ($request['anticipated_only']) {
            $stores = $this->model_stores->getStoresData();
            foreach ($stores as $store) {
                $storeIds[] = $store['id'];
            }
        }

        $orders = $this->model_orders->getOrdersNotPaidConciliationByStore(
            $storeIds,
            $request['order_id'],
            $request['installments_number'],
            $request['status'],
            $request['order_date'],
            (bool)$request['anticipated_only'],
            (int)$request['simulation_id']
        );

        $valueReceiveNextCycle = 0;
        $totalValueNotPaid = 0;

        foreach ($orders as &$order) {

            if (!$order['anticipated']) {
                $valueReceiveNextCycle += $order['installment_value'];
            }
            if ($order['value_not_paid']) {
                $totalValueNotPaid += $order['value_not_paid'];
            }

            $simulation = $this->model_orders_simulations_anticipations_store->findByOrderId($order['id']);

            $order['order_id_link'] = $order['anticipated'] && in_array('viewAnticipationSimulation', $this->permission)
                ? '<a onclick="app.getOrderDetailsSimulated(' . $order['id'] . ')" data-toggle="modal" data-target="#orderAnticipated" title="Ver Detalhes"><i class="fa fa-eye"></i> ' . $order['id'] . '</a>'
                : '<a onclick="app.getOrderDetailsNotSimulated(' . $order['id'] . ')" data-toggle="modal" data-target="#orderAnticipated" title="Ver Detalhes"><i class="fa fa-eye"></i> ' . $order['id'] . '</a>';
            $order['order_date'] = datetimeBrazil($order['date_time']);
            $order['marketplace_order_id'] = $order['numero_marketplace'];
            $order['next_payment_date'] = $order['anticipated'] ? dateBrazil($simulation['payment_date']) : dateBrazil($order['data_ciclo']);
            $order['next_payment_date'] = "<span title='Data do Pedido: {$order['order_date']}'>{$order['next_payment_date']}</span>";
            $order['value_next_payment_formated'] = $order['anticipated'] ? '-' : money($order['installment_value']);
            $order['installment_value_formated'] = $order['anticipated'] ? '-' : money($order['installment_value']);
            $order['value_not_paid_formated'] = $order['anticipated'] ? '-' : money($order['value_not_paid']);
            $order['value_paid_formated'] = money($order['value_paid'] + $order['value_paid_anticipated']);
            $order['initial_transfer_formated'] = money($order['valor_repasse_ajustado'] * $order['total_installments']);
            $order['anticipation_taxes_formated'] = $order['anticipated'] && $order['anticipation_taxes'] ? money($order['anticipation_taxes']) : '-';
            $order['status'] = $simulation && in_array($simulation['anticipation_status'], [AnticipationStatusEnum::APPROVED, AnticipationStatusEnum::PENDING]) ? lang('application_anticipated') : lang('application_anticipation_normal');

            //Status
            $order['status'] = AnticipationStatusFilterEnum::getName(AnticipationStatusFilterEnum::NORMAL);
            if ($simulation){
                if (AnticipationStatusEnum::APPROVED == $simulation['anticipation_status']){
                    $order['status'] = AnticipationStatusFilterEnum::getName(AnticipationStatusFilterEnum::APPROVED);
                }elseif (AnticipationStatusEnum::REFUSED == $simulation['anticipation_status']){
                    $order['status'] = AnticipationStatusFilterEnum::getName(AnticipationStatusFilterEnum::REFUSED);
                }elseif (AnticipationStatusEnum::PENDING == $simulation['anticipation_status']){
                    $order['status'] = AnticipationStatusFilterEnum::getName(AnticipationStatusFilterEnum::IN_ANTICIPATION);
                }
            }

        }

        $limits = [];
        if ($request['store']) {
            $limits = (array)$this->model_anticipation_limits_store->findByStoreId($request['store']);
        }

        $data = [];
        $data['orders'] = $orders;
        $data['storeLimits'] = $limits;
        $data['storeLimits']['valueReceiveNextCycle'] = $valueReceiveNextCycle;
        $data['storeLimits']['totalValueNotPaid'] = $totalValueNotPaid;
        $data['storeLimits']['totalsAnticipated'] = $this->model_simulations_anticipations_store->getTotalsAnticipatedByStoreId($storeIds);

        ob_clean();
        header('Content-type: application/json');
        exit(json_encode($data));

    }

    private function cancelAllBuildingSimulationsByStoreId(int $storeId): void
    {

        $oldSimulationsBuilding = $this->model_simulations_anticipations_store->findBuildingByStoreId($storeId);

        if ($oldSimulationsBuilding) {
            foreach ($oldSimulationsBuilding as $oldSimulation) {
                $cancelation = $this->cancelSimulation($oldSimulation);
                if (!$cancelation['success']) {
                    throw new Exception("Não foi possível cancelar a simulação {$oldSimulation['anticipation_id']} na Pagarme");
                }
            }
        }

    }

    protected function cancelSimulation(array $simulation): array
    {

        $data = [];
        $data['success'] = false;
        $data['error'] = '';

        $gateway_name = Model_gateway::PAGARME;

        $gatewayId = $this->model_gateway->getGatewayId($gateway_name);

        $gatewaySubaccount = $this->model_gateway->getSubAccountByStoreId($simulation['store_id'], $gatewayId);

        $cancelation = $this->integration->cancelAnticipation($gatewaySubaccount['gateway_account_id'], $simulation['anticipation_id']);

        $responseCode = $cancelation["httpcode"];
        $response_data = json_decode($cancelation['content'], true);

        if (200 == $responseCode) {

            $data['success'] = true;

            //Marking the simulation as approved
            $simulation['anticipation_status'] = AnticipationStatusEnum::CANCELED;
            $this->model_simulations_anticipations_store->update($simulation, $simulation['id']);

        } else {

            $data['error'] .= "Ocorreu um erro ao realizar sua solicitação na Pagarme: ";
            foreach ($response_data['errors'] as $i => $error) {
                if ($i > 0) {
                    $data['error'] .= ', ';
                }
                $data['error'] .= $error['message'] . ' ';
            }

        }

        return $data;

    }

    public function export_orders(): void
    {

        if (!in_array('viewAnticipationSimulation', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $stream_clean = $this->postClean('entry', TRUE);

        $request = json_decode($stream_clean, true);

        $storeIds = [];

        if ($request['store']) {
            $storeIds[] = $request['store'];
        } elseif ($request['anticipated_only']) {
            $stores = $this->model_stores->getStoresData();
            foreach ($stores as $store) {
                $storeIds[] = $store['id'];
            }
        }

        $orders = $this->model_orders->getOrdersNotPaidConciliationByStore(
            $storeIds,
            $request['order_id'],
            $request['installments_number'],
            $request['status'],
            $request['order_date'],
            (bool)$request['anticipated_only'],
            (int)$request['simulation_id']
        );

        foreach ($orders as &$order) {

            $simulation = $this->model_orders_simulations_anticipations_store->findByOrderId($order['id']);

            $order['order_date'] = datetimeBrazil($order['date_time']);
            $order['anticipation_date'] = $order['anticipated'] && $simulation['payment_date'] ? dateBrazil($simulation['payment_date']) : '';
            $order['value_next_payment_formated'] = $order['anticipated'] ? '-' : money($order['installment_value']);
            $order['installment_value_formated'] = $order['anticipated'] ? '-' : money($order['installment_value']);
            $order['value_not_paid_formated'] = $order['anticipated'] ? '-' : money($order['value_not_paid']);
            $order['value_paid_formated'] = money($order['value_paid'] + $order['value_paid_anticipated']);
            $order['initial_transfer_formated'] = money($order['valor_repasse_ajustado'] * $order['total_installments']);
            $order['anticipation_taxes_formated'] = $order['anticipated'] && $order['anticipation_taxes'] ? money($order['anticipation_taxes']) : '-';
            $order['status'] = $simulation && in_array($simulation['anticipation_status'], [AnticipationStatusEnum::APPROVED, AnticipationStatusEnum::PENDING]) ? lang('application_anticipated') : lang('application_anticipation_normal');

            //Status
            $order['status_code'] = AnticipationStatusFilterEnum::NORMAL;
            if (AnticipationStatusEnum::APPROVED == $simulation['anticipation_status']){
                $order['status_code'] = AnticipationStatusFilterEnum::APPROVED;
            }elseif (AnticipationStatusEnum::REFUSED == $simulation['anticipation_status']){
                $order['status_code'] = AnticipationStatusFilterEnum::REFUSED;
            }elseif (AnticipationStatusEnum::PENDING == $simulation['anticipation_status']){
                $order['status_code'] = AnticipationStatusFilterEnum::IN_ANTICIPATION;
            }

            $order['user_name'] = $simulation['user_name'];

        }

        $this->data['orders'] = $orders;

        $this->parser->parse('anticipationsmanagement/anticipationsmanagement_export', $this->data);

    }

    public function simulate_anticipation()
    {

        if (!in_array('createAnticipationSimulation', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $data = [];
        $data['error'] = '';
        $data['simulation'] = [];
        $data['confirmed'] = false;

        $request = json_decode($this->input->raw_input_stream, true);

        //Canceling old simulations building
        $this->cancelAllBuildingSimulationsByStoreId((int)$request['store']);

        $ordersId = [];
        $totalNotPaidCalculated = 0;
        foreach ($request['orders'] as $order) {
            $ordersId[] = $order['id'];
            $totalNotPaidCalculated += $order['value_not_paid'];

            //Checking if order is under 11 days before next payment cicle
            $next_installment = $this->model_orders_conciliation_installments->findNextUnpaidInstallment($order['id']);

            $days_to_next_cicle = dateDiffDays(new DateTime($next_installment['data_ciclo']), new DateTime());
            $days_delivered = dateDiffDays(new DateTime(), new DateTime($next_installment['data_entrega']));

            if ($days_to_next_cicle > 0 && $days_to_next_cicle < 11){
                $data['error'] = "O pedido {$order['id']} falta apenas $days_to_next_cicle dias para a liberação de pagamento, não é possível solicitar antecipação de pagamento com menos de 11 dias para a liberação do pagamento";
            }elseif($days_to_next_cicle < 11){
                $data['error'] = "O pedido {$order['id']} já passou da data para liberação de pagamento, não é possível solicitar antecipação de pagamento.";
            }elseif($days_delivered < 8){
                $data['error'] = "O pedido {$order['id']} não pode ser antecipado com menos de 8 dias após a entrega.";
            }

        }

        $limits = (array)$this->model_anticipation_limits_store->findByStoreId($request['store']);

        $sumTotalNotPaidByOrdersIdStoreId = $this->model_orders_conciliation_installments->sumTotalNotPaidByOrdersIdStoreId($ordersId, (int)$request['store']);

        $gateway_name = Model_gateway::PAGARME;

        $gatewayId = $this->model_gateway->getGatewayId($gateway_name);

        $gatewaySubaccount = $this->model_gateway->getSubAccountByStoreId($request['store'], $gatewayId);

        //Custom validate of totals selected to anticipate
        if (!$data['error']){
            if (number_format($sumTotalNotPaidByOrdersIdStoreId, 2, '', '') != number_format($totalNotPaidCalculated, 2, '', '')) {
                $data['error'] = 'O valor total do pedido: ' . money($totalNotPaidCalculated) . ' está divergente do valor total que será antecipado: ' . money($totalNotPaidCalculated);
            } elseif ($totalNotPaidCalculated < $limits['minimum_amount'] || $totalNotPaidCalculated > $limits['maximum_amount']) {
                $data['error'] = "O valor total do pedido: " . money($totalNotPaidCalculated) . " não pode ser menor que: " . money($limits['minimum_amount']) . " ou maior que: " . money($limits['maximum_amount']);
            } elseif (!$gatewaySubaccount) {
                $data['error'] = "Sua conta id: {$request['store']} ainda não está cadastrada na Pagar.me.";
            } else {

                $percentualsFromTotal = [];
                foreach ($request['orders'] as $order) {
                    $percentualsFromTotal[$order['id']] = ($order['value_not_paid'] / $sumTotalNotPaidByOrdersIdStoreId) * 100;
                }

                //Calcula a antecipação na pagarme
                $resultPagarmeSimulation = $this->integration->simulateAnticipation(
                    $gatewaySubaccount['gateway_account_id'],
                    (int)round(($totalNotPaidCalculated * 100), 0)
                );

                $responseCode = $resultPagarmeSimulation["httpcode"];
                $response_data = json_decode($resultPagarmeSimulation['content'], true);

                if ($responseCode == 200) {

                    $simulationAnticipation = [
                        'store_id' => $request['store'],
                        'anticipation_id' => $response_data['id'],
                        'amount' => number_format(round(($response_data['amount']) / 100, 2), 2, '.', ''),
                        'anticipation_fee' => number_format(round(($response_data['anticipation_fee']) / 100, 2), 2, '.', ''),
                        'fee' => number_format(round(($response_data['fee']) / 100, 2), 2, '.', ''),
                        'payment_date' => parseDateFromFormatToDateFormat($response_data['payment_date'], 'Y-m-d\TH:i:s.000\Z', DATETIME_INTERNATIONAL),
                        'anticipation_status' => $response_data['status'],
                        'timeframe' => $response_data['timeframe'],
                        'type' => $response_data['type'],
                        'user_id' => $this->session->userdata['id'],
                    ];

                    $simulationAnticipationId = $this->model_simulations_anticipations_store->create($simulationAnticipation);

                    $simulationAnticipation = $this->model_simulations_anticipations_store->findByPk($simulationAnticipationId);

                    $totalAnticipationFee = 0;
                    $totalFee = 0;

                    $totalOrders = count($request['orders']);

                    foreach ($request['orders'] as $i => $order) {

                        $totalAnticipationFee += $anticipation_fee = roundDecimalsDown($simulationAnticipation['anticipation_fee'] / 100 * $percentualsFromTotal[$order['id']]);
                        $totalFee += $fee = roundDecimalsDown($simulationAnticipation['fee'] / 100 * $percentualsFromTotal[$order['id']]);

                        if ($i + 1 == $totalOrders) {

                            if ($totalAnticipationFee < $simulationAnticipation['anticipation_fee']) {
                                $difference = $simulationAnticipation['anticipation_fee'] - $totalAnticipationFee;
                                $anticipation_fee = $anticipation_fee + $difference;
                                $totalAnticipationFee += $difference;
                            }

                            if ($totalFee < $simulationAnticipation['fee']) {
                                $difference = $simulationAnticipation['fee'] - $totalFee;
                                $fee = $fee + $difference;
                                $totalFee += $difference;
                            }

                        }

                        $orderSimulationAnticipation = [
                            'order_id' => $order['id'],
                            'amount' => $order['value_not_paid'],
                            'simulations_anticipations_store_id' => $simulationAnticipationId,
                            'anticipation_fee' => $anticipation_fee,
                            'fee' => $fee,
                        ];

                        $this->model_orders_simulations_anticipations_store->create($orderSimulationAnticipation);

                    }

                    $simulationAnticipation['total_with_tax'] = $simulationAnticipation['anticipation_fee'] + $simulationAnticipation['fee'];
                    $simulationAnticipation['payment_date'] = dateBrazil($simulationAnticipation['payment_date']);
                    $data['simulation'] = $simulationAnticipation;

                } else {

                    $data['error'] .= "Ocorreu um erro ao realizar sua solicitação na Pagarme: ";
                    foreach ($response_data['errors'] as $i => $error) {
                        if ($i > 0) {
                            $data['error'] .= ', ';
                        }
                        $data['error'] .= $error['message'] . ' ';
                    }
                }

            }
        }

        ob_clean();
        header('Content-type: application/json');
        exit(json_encode($data));

    }

    public function confirm_simulate_anticipation()
    {

        if (!in_array('createAnticipationSimulation', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $data = [];
        $data['error'] = '';
        $data['success'] = false;
        $data['simulation'] = '';

        $request = json_decode($this->input->raw_input_stream, true);

        $simulationId = $request['simulation']['id'] ?? null;

        $simulation = $simulationId ? $this->model_simulations_anticipations_store->findByPk($simulationId) : null;

        if ($simulationId && $simulation) {

            $data['simulation'] = $simulation;

            $gateway_name = Model_gateway::PAGARME;

            $gatewayId = $this->model_gateway->getGatewayId($gateway_name);

            $gatewaySubaccount = $this->model_gateway->getSubAccountByStoreId($simulation['store_id'], $gatewayId);

            $confirmation = $this->integration->confirmAnticipation($gatewaySubaccount['gateway_account_id'], $simulation['anticipation_id']);

            $responseCode = $confirmation["httpcode"];
            $response_data = json_decode($confirmation['content'], true);

            if (200 == $responseCode) {

                $data['success'] = true;

                //Marking the simulation as approved
                $simulation['anticipation_status'] = AnticipationStatusEnum::PENDING;
                $this->model_simulations_anticipations_store->update($simulation, $simulationId);

                $ordersId = [];
                $orders = $this->model_orders_simulations_anticipations_store->getAllBySimulationId($simulationId);
                foreach ($orders as $order) {
                    $ordersId[] = $order['order_id'];
                }

                //Marking all building conciliation installments as anticipated
                $this->model_orders_conciliation_installments->markOrdersAsAnticipated($ordersId, $simulation['store_id']);

                //Loading anticipation limits again
                $this->integration->loadAnticipationsLimitsByRecipientId($gatewaySubaccount['gateway_account_id'], $simulation['store_id']);

            } else {

                $data['error'] .= "Ocorreu um erro ao realizar sua solicitação na Pagarme: ";
                foreach ($response_data['errors'] as $i => $error) {
                    if ($i > 0) {
                        $data['error'] .= ', ';
                    }
                    $data['error'] .= $error['message'] . ' ';
                }

            }

        } else {
            $data['error'] = "Simulation not found";
        }

        ob_clean();
        header('Content-type: application/json');
        exit(json_encode($data));

    }

    public function cancel_simulate_anticipation()
    {

        if (!in_array('createAnticipationSimulation', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $data = [];
        $data['error'] = '';
        $data['success'] = false;
        $data['simulation'] = '';
        $data['confirmed'] = false;

        $request = json_decode($this->input->raw_input_stream, true);

        $simulationId = $request['simulation']['id'] ?? null;

        $simulation = $simulationId ? $this->model_simulations_anticipations_store->findByPk($simulationId) : null;

        if ($simulationId && $simulation) {

            $cancelation = $this->cancelSimulation($simulation);
            $data = array_merge($data, $cancelation);
            $data['success'] = true;

        } else {
            $data['error'] = "Simulação não encontrada";
        }

        ob_clean();
        header('Content-type: application/json');
        exit(json_encode($data));

    }

    public function load_order_details_simulated($orderId)
    {

        if (!in_array('viewAnticipationSimulation', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $data = [];
        $data['data'] = [];
        $data['error'] = '';
        $data['mode'] = 'anticipation';

        $orderAnticipationDetails = $this->model_orders_simulations_anticipations_store->findByOrderId($orderId);

        if ($orderAnticipationDetails) {

            $data['details'] = [
                'order_id' => $orderAnticipationDetails['order_id'],
                'marketplace_order_id' => $orderAnticipationDetails['numero_marketplace'],
                'total_order' => $orderAnticipationDetails['total_order'],
                'transfer_value' => $orderAnticipationDetails['valor_repasse'] - $orderAnticipationDetails['anticipation_fee'] - $orderAnticipationDetails['fee'],
                'inicial_transfer_value' => $orderAnticipationDetails['valor_repasse'],
                'order_date' => datetimeBrazil($orderAnticipationDetails['order_date']),
                'order_delivered_date' => dateBrazil($orderAnticipationDetails['order_delivered_date']),
                'amount' => $orderAnticipationDetails['anticipation_status'] == AnticipationStatusEnum::APPROVED ? $orderAnticipationDetails['amount'] : 0,
                'taxes' => number_format($orderAnticipationDetails['anticipation_fee'] + $orderAnticipationDetails['fee'], 2, '.', ''),
                'status' => AnticipationStatusEnum::getName($orderAnticipationDetails['anticipation_status']),
                'user_name' => $orderAnticipationDetails['user_name'],
            ];

            $store = $this->model_stores->getStoreAndCompanyNameByPk($orderAnticipationDetails['store_id']);

            $data['details'] = array_merge($data['details'], $store);

            $data['details']['installments'] = $this->model_orders_conciliation_installments->findByOrderId($orderId);
            $data['details']['total_paid'] = 0;
            $data['details']['amount_pending'] = 0;
            foreach ($data['details']['installments'] as &$installment) {
                $installment['data_ciclo'] = dateBrazil($installment['data_ciclo']);
                $installment['payment_status'] = 'Não Pago';
                $installment['anticipation_date'] = '';
                if ($installment['anticipated']) {
                    if (AnticipationStatusEnum::APPROVED == $orderAnticipationDetails['anticipation_status']){
                        $installment['payment_status'] = 'Pagamento Antecipado';
                    }
                    if (AnticipationStatusEnum::PENDING == $orderAnticipationDetails['anticipation_status']){
                        $installment['payment_status'] = 'Em Antecipação';
                    }
                    $installment['anticipation_date'] = datetimeBrazil($orderAnticipationDetails['payment_date']);
                    $data['details']['total_paid'] += $installment['installment_value'];
                } elseif ($installment['paid']) {
                    $installment['payment_status'] = 'Pago';
                    $data['details']['total_paid'] += $installment['installment_value'];
                } else {
                    $data['details']['amount_pending'] += $installment['installment_value'];
                }
            }

            if (in_array($orderAnticipationDetails['anticipation_status'], [AnticipationStatusEnum::APPROVED, AnticipationStatusEnum::PENDING])) {
                $data['details']['total_paid'] -= ($orderAnticipationDetails['anticipation_fee'] + $orderAnticipationDetails['fee']);
            }

        } else {
            $data['error'] = "Pedido $orderId não encontrado";
        }

        ob_clean();
        header('Content-type: application/json');
        exit(json_encode($data));

    }

    public function load_order_details_not_simulated($orderId)
    {

        $data = [];
        $data['data'] = [];
        $data['error'] = '';
        $data['mode'] = 'view';

        $orderDetails = $this->model_orders->findOrderWithInstallments($orderId);

        if ($orderDetails) {

            $data['details'] = [
                'order_id' => $orderDetails['id'],
                'marketplace_order_id' => $orderDetails['numero_marketplace'],
                'total_order' => $orderDetails['total_order'],
                'transfer_value' => $orderDetails['valor_repasse'],
                'inicial_transfer_value' => $orderDetails['valor_repasse'],
                'order_date' => datetimeBrazil($orderDetails['order_date']),
                'order_delivered_date' => dateBrazil($orderDetails['order_delivered_date']),
                'amount' => 0,
                'taxes' => 0,
                'status' => AnticipationStatusFilterEnum::getDescription(AnticipationStatusFilterEnum::NORMAL),
            ];

            $store = $this->model_stores->getStoreAndCompanyNameByPk($orderDetails['store_id']);

            $data['details'] = array_merge($data['details'], $store);

            $data['details']['installments'] = $this->model_orders_conciliation_installments->findByOrderId($orderId);
            $data['details']['total_paid'] = 0;
            $data['details']['amount_pending'] = 0;
            foreach ($data['details']['installments'] as &$installment) {
                $installment['data_ciclo'] = dateBrazil($installment['data_ciclo']);
                $installment['payment_status'] = 'Não Pago';
                $installment['anticipation_date'] = '';
                if ($installment['paid']) {
                    $installment['payment_status'] = 'Pago';
                    $data['details']['total_paid'] += $installment['installment_value'];
                } else {
                    $data['details']['amount_pending'] += $installment['installment_value'];
                }
            }

        } else {
            $data['error'] = "Pedido $orderId não encontrado";
        }

        ob_clean();
        header('Content-type: application/json');
        exit(json_encode($data));

    }

}