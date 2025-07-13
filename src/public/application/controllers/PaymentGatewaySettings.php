<?php
defined('BASEPATH') or exit('No direct script access allowed');
use App\Libraries\Enum\PaymentGatewayEnum;

/**
 * @property Model_gateway_settings $model_gateway_settings
 * @property Model_settings $model_settings
 */
class PaymentGatewaySettings extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->not_logged_in();

        $this->data['page_title'] = $this->lang->line('application_payment_gateway_settings');

        $this->load->model('model_gateway_settings');
        $this->load->model('model_settings');
        $this->load->model('model_gateway');

    }

    /*
    * It only redirects to the manage product page and
    */
    public function index()
    {
        if (!in_array('viewPaymentGatewayConfig', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $result = $this->model_gateway_settings->getSettingData();
        $subaccounts = $this->model_gateway->countSubAccounts();

        $this->data['results'] = $result;

        // if(ENVIRONMENT === 'development'){
            $selected_gateway_id = $this->model_settings->getValueIfAtiveByName('payment_gateway_id');
            $this->data['selected_gateway_id'] = !empty($selected_gateway_id) ? $selected_gateway_id : '';
            $this->data['gateways'] = $this->model_gateway_settings->getGateways();
            $this->data['has_subaccounts'] = $subaccounts > 0;
            $this->render_template('payment_gateway_settings/index_new', $this->data);
        // }else{
        //     $this->render_template('payment_gateway_settings/index', $this->data);
        // }

    }

    /*
    * Fetches the Setting data from the Setting table
    * this function is called from the datatable ajax function
    */
    public function fetchSettingData()
    {
        $result = array('data' => array());

        $data = $this->model_gateway_settings->getSettingData();
        foreach ($data as $key => $value) {

            // button
            $buttons = '';

            if (in_array('viewPaymentGatewayConfig', $this->permission)) {
                $buttons .= '<button type="button" class="btn btn-default" onclick="editSetting(' . $value['id'] . ')" data-toggle="modal" data-target="#editSettingModal"><i class="fa fa-pencil"></i></button>';
            }

            $result['data'][$key] = array(
                '<span style="word-break:break-all;">' . $value['name'] . '</span>',
                '<span style="word-break:break-all;">' . $value['value'] . '</span>',
                '<span style="word-break:break-all;">' . $value['gateway_id'] . '</span>',
                $buttons
            );
        } // /foreach

        echo json_encode($result);
    }

    /*
    * It checks if it gets the Setting id and retreives
    * the Setting information from the Setting model and
    * returns the data into json format.
    * This function is invoked from the view page.
    */
    public function fetchSettingDataById($id)
    {
        if ($id) {
            $data = $this->model_gateway_settings->getSettingData($id);
            echo json_encode($data);
        }

        return false;
    }

    /*
    * Its checks the Setting form validation
    * and if the validation is successfully then it inserts the data into the database
    * and returns the json format operation messages
    */
    public function create()
    {

        if (!in_array('createPaymentGatewayConfig', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $response = array();

        $this->form_validation->set_rules('setting_name', $this->lang->line('application_setting_name'), 'trim|required');
        $this->form_validation->set_rules('setting_value', $this->lang->line('application_setting_value'), 'trim|required');
        $this->form_validation->set_rules('setting_gateway_id', $this->lang->line('application_setting_gateway_id'), 'trim|required');

        $this->form_validation->set_error_delimiters('<p class="text-danger">', '</p>');

        if ($this->form_validation->run() == TRUE) {
            $data = array(
                'name' => $this->postClean('setting_name', TRUE),
                'value' => $this->postClean('setting_value', TRUE),
                'gateway_id' => $this->postClean('setting_gateway_id', TRUE),
            );

            $create = $this->model_gateway_settings->create($data);
            if ($create == true) {
                $response['success'] = true;
                $response['messages'] = $this->lang->line('messages_successfully_created');
            } else {
                $response['success'] = false;
                $response['messages'] = $this->lang->line('messages_error_database_create_setting');
            }
        } else {
            $response['success'] = false;
            foreach ($_POST as $key => $value) {
                $response['messages'][$key] = form_error($key);
            }
        }
        ob_clean();
        echo json_encode($response);

    }

    /*
    * Its checks the Setting form validation
    * and if the validation is successfully then it updates the data into the database
    * and returns the json format operation messages
    */
    public function update($id)
    {
        if (!in_array('updatePaymentGatewayConfig', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $response = array();

        if ($id) {
            $this->form_validation->set_rules('edit_setting_name', $this->lang->line('application_setting_name'), 'trim|required');
            $this->form_validation->set_rules('edit_setting_value', $this->lang->line('application_setting_value'), 'trim|required');
            $this->form_validation->set_rules('edit_setting_gateway_id', $this->lang->line('application_setting_gateway_id'), 'trim|required');

            $this->form_validation->set_error_delimiters('<p class="text-danger">', '</p>');

            if ($this->form_validation->run() == TRUE) {
                $data = array(
                    'name' => $this->postClean('edit_setting_name', TRUE),
                    'value' => $this->postClean('edit_setting_value', TRUE),
                    'gateway_id' => $this->postClean('edit_setting_gateway_id', TRUE),
                );

                $update = $this->model_gateway_settings->update($data, $id);
                if ($update == true) {
                    $response['success'] = true;
                    $response['messages'] = $this->lang->line('messages_successfully_updated');
                } else {
                    $response['success'] = false;
                    $response['messages'] = $this->lang->line('messages_error_database_update_setting');
                }
            } else {
                $response['success'] = false;
                foreach ($_POST as $key => $value) {
                    $response['messages'][$key] = form_error($key);
                }
            }
        } else {
            $response['success'] = false;
            $response['messages'] = $this->lang->line('messages_refresh_page_again');
        }
        ob_clean();
        echo json_encode($response);
    }

    public function getSettingByGatewayId(){

        if (!in_array('viewPaymentGatewayConfig', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $stream_clean = utf8_encode($this->security->xss_clean($this->input->raw_input_stream));
        $request = json_decode($stream_clean, true);

        $selectedGateway = $request;

        $canEdit = true; //!($this->model_gateway_settings->getGatewaySubaccountsData(null) > 0);
        $response = ['canEdit' => $canEdit];

        $gatewayData = $this->model_gateway_settings->getGatewayByGatewayId($selectedGateway);
        if($gatewayData){
            foreach($gatewayData as $gateway) {
                if($gateway['value'] == ""){
                    $response['canEdit'] = true;
                }
                $response['gateway'][$gateway['name']] = ['value' => $gateway['value'], 'id' => $gateway['id']];
            }
        }

        header('Content-type: application/json');
        exit(json_encode($response));

    }

    public function saveSetting($selected_gateway = 0){

        if (!in_array('updatePaymentGatewayConfig', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $stream_clean = $this->security->xss_clean($this->input->raw_input_stream);
        $request = json_decode($stream_clean, true);

        $ignore_values = [
            // 'pagarme_subaccounts_api_version',
            'banks_with_zero_fee',
            'url_api_v1',
            'url_api_v5',
            'app_key_mgm',
            'access_token_mgm',
            'url_api_v2',
            'app_key_oob',
            'access_token_oob',
            'ymi_url',
            'api_url',
			'reset_negative'
        ];

        if($selected_gateway == PaymentGatewayEnum::PAGARME){
            if ($request['pagarme_subaccounts_api_version']['value'] == 4){
                $ignore_values[] = 'app_key_v5';
                $ignore_values[] = 'primary_account_v5';
            }else{
                $ignore_values[] = 'app_key';
                $ignore_values[] = 'primary_account';
            }
        }

        $this->model_settings->updateByName(['value' => $selected_gateway, 'status' => 1], 'payment_gateway_id');

		if($selected_gateway == PaymentGatewayEnum::EXTERNO && isset($request['reset_negative']))
		{
			$this->load->model('model_calendar');

			$module_path = 'ExternalGateway/ExternalGatewayBatch';
			$module_method = 'resetNegativePayments';

			if ($request['reset_negative']['value'])
			{
				$date_now = dateNow(TIMEZONE_DEFAULT)->format(DATE_INTERNATIONAL);
				$datetime_now = $date_now.' 08:30:00';
				$datetime_now = new DateTime($datetime_now);
				$tomorrow = $datetime_now->modify('+1 day');
				$the_end = new DateTime('2200-01-01 23:59:59');

				$job_data = [
					'title'         => 'Ativa Reset Negativo Liberação EXTERNO',
					'event_type'    => 71,
					'module_path'   => $module_path,
					'module_method' => $module_method,
					'start'         => $tomorrow->format('Y-m-d H:i:s'),
					'end'           => $the_end->format('Y-m-d H:i:s'),
				];

				$this->model_calendar->add_event($job_data);
			}
			else
			{
				$this->model_calendar->delete_eventByModuleMethod($module_path, $module_method);
			}
		}


        $keys = array_keys($request);
        foreach($keys as $key){

            if($request[$key]['value'] == "" && !in_array($key, $ignore_values)){
                $this->handleResponse('error', 'O campo ' . $key . ' é obrigatório.');
            }

            if($selected_gateway == PaymentGatewayEnum::PAGARME){

                if(in_array($key, ['primary_account', 'primary_account_v5']) && !in_array($key, $ignore_values)){
                    if(!in_array(substr($request[$key]['value'], 0, 3), ['re_', 'rp_'])){
                        $this->handleResponse('error', 'O código da conta primária deve iniciar com re_ ou rp_.');
                    }else if($this->model_gateway_settings->existsPrimaryAccount($request[$key]['value'])){
                        // VERIFICA SE ACONTA PRIMARIA EXISTE NA TABELA GATEWAY SUBACCOUNTS
                        $this->handleResponse('error', 'O código da conta primária informado já está sendo utilizando por uma subconta.');
                    }
                }
            }

			if ($selected_gateway == PaymentGatewayEnum::EXTERNO && $key == 'reset_negative')
			{
				switch ($request[$key]['value'])
				{
					case true: $request[$key]['value'] = 1; break;
					default: $request[$key]['value'] = '';
				}

				$data = array('value' => $request[$key]['value']);
				$this->model_gateway_settings->update($data, $request[$key]['id']);
			}

            if($key == 'banks_with_zero_fee' || !in_array($key, $ignore_values)) {

                if($key == 'cost_transfer_tax_pagarme'){
                    $request[$key]['value'] = floatval($request[$key]['value']);
                }

                if($key == 'banks_with_zero_fee'){
                    $request[$key]['value'] = mb_convert_encoding($request[$key]['value'], 'UTF-8');
                }

                $data = array(
                    'value' => $request[$key]['value']
                );
                $id = $request[$key]['id'];
                $this->model_gateway_settings->update($data, $id);
            }

            // ATUALIZANDO O GATEWAY SALVO
            $this->db->where('name', 'payment_gateway_id');
            $this->db->where('value', '');
            $this->db->update('settings', ['value' => $selected_gateway]);

        }

        $this->handleResponse('success');

    }

    public function validateData($selected_gateway = 0){

        if (!in_array('updatePaymentGatewayConfig', $this->permission)) {
            $this->handleResponse('error', 'Você não tem permissão para salvar alterações na tela de parametros do Gateway');
            return;
        }

        $stream_clean = $this->security->xss_clean($this->input->raw_input_stream);
        $request = json_decode($stream_clean, true);

        if($selected_gateway == PaymentGatewayEnum::PAGARME){
            $this->validatePagarmeData($request);
        }
        if($selected_gateway == PaymentGatewayEnum::GETNET){
            $this->validateGetnetData($request);
        }
        if($selected_gateway == PaymentGatewayEnum::MAGALUPAY){
            $this->validateMagaluPayData($request);
        }
        if($selected_gateway == PaymentGatewayEnum::TUNA){
            $this->validateTunaData($request);
        }
        if($selected_gateway == PaymentGatewayEnum::EXTERNO){
            $this->validateExternoData($request);
        }

        $this->handleResponse('success');

    }

    private function validatePagarmeData($request): void
    {

        if ($request['pagarme_subaccounts_api_version']['value'] == 4){
            if (substr($request['primary_account']['value'], 0, 3) != 're_'){
                $this->handleResponse('error', 'O código da conta primária v4 deve iniciar com re_.');
            }
            if ($this->model_gateway_settings->existsPrimaryAccount($request['primary_account']['value'])){
                $this->handleResponse('error', 'O código da conta primária v4 informado já está sendo utilizando por uma subconta.');
            }
        }
        if ($request['pagarme_subaccounts_api_version']['value'] == 5){
            if (!in_array(substr($request['primary_account_v5']['value'], 0, 3), ['re_', 'rp_'])){
                $this->handleResponse('error', 'O código da conta primária v5 deve iniciar com re_ ou rp_.');
            }
            if ($this->model_gateway_settings->existsPrimaryAccount($request['primary_account_v5']['value'])){
                $this->handleResponse('error', 'O código da conta primária v5 informado já está sendo utilizando por uma subconta.');
            }
        }

        $this->load->library('PagarmeLibrary');

        //Starting Pagar.me integration library
        $integration = new PagarmeLibrary();

        foreach ($request as $key => $requestItem){
            $integration->{$key} = $requestItem['value'];
        }

        header('Content-type: application/json');
        exit(json_encode($integration->validateAuthData()));

    }

    private function validateGetnetData($request): void
    {

        $this->load->library('Getnetlibrary');

        //Starting Getnet integration library
        $integration = new GetnetLibrary();

        foreach ($request as $key => $requestItem){
            $integration->{$key} = $requestItem['value'];
        }

        header('Content-type: application/json');
        exit(json_encode($integration->validateAuthData()));

    }
    
    private function validateMagaluPayData($request): void
    {

        // Retornando sempre sucesso no primeiro momento

        header('Content-type: application/json');
        exit(json_encode( [
            'result' => 'success',
            'message' => ''
        ]));

    }

    private function validateTunaData($request): void
    {

        // Retornando sempre sucesso no primeiro momento

        header('Content-type: application/json');
        exit(json_encode( [
            'result' => 'success',
            'message' => ''
        ]));

    }
    
    private function validateExternoData($request): void
    {

        // Retornando sempre sucesso no primeiro momento

        header('Content-type: application/json');
        exit(json_encode( [
            'result' => 'success',
            'message' => ''
        ]));

    }

    private function handleResponse($result = "", $message = ""){
        header('Content-type: application/json');
        exit(json_encode(['result' => $result, 'message' => $message]));
    }



}