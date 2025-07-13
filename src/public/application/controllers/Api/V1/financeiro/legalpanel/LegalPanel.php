<?php

require APPPATH . "controllers/Api/V1/API.php";

/**
 * @property CI_Loader $load
 *
 * @property Model_legal_panel $model_legal_panel
 *
 */
class LegalPanel extends API
{
	private $filters;

	public function __construct()
	{
		parent::__construct();

		$this->load->model('model_legal_panel');

		$this->max_per_page = 100;

		$this->store_id = 1;
	}

	public function api_auth()
	{
		$this->header = array_change_key_case(getallheaders());
		$check_auth = $this->checkAuth($this->header);

		if (!$check_auth[0])
		{
			return $this->response($check_auth[1], REST_Controller::HTTP_UNAUTHORIZED);
		}

		return;
	}


	/**
	 * List Legal Panel itens
	 *
	 * @return mixed
	 */
	public function index_get()
	{
		$this->api_auth();

		$this->filters = $this->cleanGet($_GET) ?? null;
		
		$legal_panel_items = $this->listItens();

		if (isset($legal_panel_items['error']) && $legal_panel_items['error'])
		{
			return $this->response($this->returnError($legal_panel_items['data']), REST_Controller::HTTP_BAD_REQUEST);
		}

		//informação redundante pois já ocorrerá success = true
		unset($legal_panel_items['error']);

		return $this->response(array($legal_panel_items), REST_Controller::HTTP_OK);

	}

	/**
	 * Post New Legal Panel item
	 *
	 * @return mixed
	 */
	public function index_post($put = null)
	{
		$this->api_auth();

		$insert_data = $this->cleanGet(json_decode(file_get_contents('php://input'), true));

		if (!is_array($insert_data['registers']) || empty($insert_data['registers']))
		{
			return $this->response($this->returnError($this->lang->line('api_legalpanel_post_error_empty')), REST_Controller::HTTP_NOT_FOUND);
		}

		$new_registers = $this->generatePostRequest($insert_data['registers'], $put);

		if ($new_registers['request_error'])
		{
			return $this->response($this->returnError(json_encode([
				$this->lang->line('api_legalpanel_post_error_empty'),
				$new_registers['request_error']
			], JSON_UNESCAPED_UNICODE)), REST_Controller::HTTP_NOT_ACCEPTABLE);
		}

		foreach ($new_registers as $key => $register)
		{

			$save = (!$put) ? $this->model_legal_panel->create($register) : $this->model_legal_panel->update($register, $key);;

			if (!$save)
			{
				$message_text = $this->lang->line('api_legalpanel_post_error_saving');

				if ($put)
				{
					$message_text = $this->lang->line('api_legalpanel_post_error_editing');
				}

				$message = [
					"success"  => false,
					"message"  => $message_text,
					"register" => $register
				];

				return $this->response(json_decode(json_encode($message)), REST_Controller::HTTP_EXPECTATION_FAILED);
			}
		}

		$this->log_data('api', __CLASS__ . "/" . __FUNCTION__, " Host: " . $this->header["host"] . " E-mail:" . $this->header["x-email"] . " 
        - Legal Panel Items Saved: " . json_encode($new_registers), "I");

		$success_text = $this->lang->line('api_legalpanel_post_success_saving');

		if ($put)
		{
			$success_text = $this->lang->line('api_legalpanel_post_success_editing');
		}

		return $this->response(array('success' => true, 'result' => $success_text), REST_Controller::HTTP_OK);
	}


	/**
	 * Put Legal Panel item
	 *
	 * @return mixed
	 */
	public function index_put()
	{
		$this->index_post(true);
	}

	public function listItens()
	{
		/*
		 * page = pagina
		 * per_page = itens por pagina
		 * start_date = data inicio
		 * end_date = data fim
		 * status = 'open' ou 'closed'
		 * notification_type = 'others' ou 'order'
		 * notification_title - disponivel somente se notification_type = 'others'
		 * store_id - mesmo que notification_title
		 * order_id - disponivel somente se notification_type = 'order'
		 * notification_id >= 3  texto agregado
		 * description >= 5 texto agregado
		 * attachment >= 3 texto agregado
		 * greater_amount = valor 'maior que', em centavos - o calculo decimal é feito na validação
		 * less_amount = valor 'menor que', em centavos - o calculo decimal é feito na validação
		 */

		$filters = $this->filters;
		$filters_array = [];

		// 1 - popula e valida o conteúdo dos filtros
		$filters_array['page'] = (filter_var($filters['page'], FILTER_SANITIZE_NUMBER_INT)) ? abs(intVal($filters['page'])) : 1;
		$filters_array['per_page'] = (filter_var($filters['per_page'], FILTER_SANITIZE_NUMBER_INT) && abs($filters['per_page']) <= $this->max_per_page) ? abs(intVal($filters['per_page'])) : $this->max_per_page;
		$filters_array['start_date'] = (strtotime($filters['start_date'])) ? date('Y-m-d', strtotime($filters['start_date'])) : null;
		$filters_array['end_date'] = (strtotime($filters['end_date'])) ? date('Y-m-d', strtotime($filters['end_date'])) : null;
		$filters_array['status'] = in_array($filters['status'], ['open', 'closed']) ? $filters['status'] : null;
		$filters_array['notification_id'] = ($filters['notification_id']) ? filter_var($filters['notification_id'], FILTER_SANITIZE_STRING) : null;
		$filters_array['description'] = ($filters['description']) ? filter_var($filters['description'], FILTER_SANITIZE_STRING) : null;
		$filters_array['attachment'] = ($filters['attachment']) ? filter_var($filters['attachment'], FILTER_SANITIZE_STRING) : null;
		$filters_array['greater_amount'] = ($filters['greater_amount']) ? round((filter_var($filters['greater_amount'], FILTER_SANITIZE_NUMBER_INT) / 100), 2) : null;
		$filters_array['less_amount'] = ($filters['less_amount']) ? round((filter_var($filters['less_amount'], FILTER_SANITIZE_NUMBER_INT) / 100), 2) : null;
		$filters_array['notification_type'] = in_array($filters['notification_type'], ['others', 'order']) ? $filters['notification_type'] : null;

		$filters_array['notification_title'] = $filters_array['store_id'] = $filters_array['order_id'] = null;

		if ($filters_array['notification_type'] == 'order')
		{
			$filters_array['order_id'] = $filters['order_id'];
		}
		else if ($filters_array['notification_type'] == 'others')
		{
			$filters_array['notification_title'] = $filters['notification_title'];
			$filters_array['store_id'] = $filters['store_id'];
		}

		// 2 - realiza a validação de regras de conteúdos dos filtros
		if ($filters_array['start_date'] && $filters_array['end_date'])
		{
			if (strtotime($filters_array['start_date']) > strtotime($filters_array['end_date']))
			{
				return array('error' => true, 'data' => $this->lang->line('api_final_date_greater'));
			}
		}

		if ($filters_array['greater_amount'] && $filters_array['less_amount'])
		{
			if ($filters_array['greater_amount'] > $filters_array['less_amount'])
			{
				return array('error' => true, 'data' => $this->lang->line('api_legalpanel_amount_greater'));
			}
		}

		// 3 - busca os itens com totais e paginaçao
		$legal_panel_items = $this->model_legal_panel->getAPiListItems($filters_array);

		if ($legal_panel_items)
		{
			return [
				'success'            => true,
				'error'              => false,
				'total_registers'    => $legal_panel_items['header']['total_registers'],
				'registers_count'    => $legal_panel_items['header']['registers_count'],
				'registers_per_page' => $filters_array['per_page'],
				'pages_count'        => $legal_panel_items['header']['pages_count'],
				'page'               => $filters_array['page'],
				'result'             => $legal_panel_items['result']
			];
		}

		return array('error' => true, 'data' => $this->lang->line('api_legalpanel_list_error'));
	}


	public function generatePostRequest($registers = [], $put = null)
	{
		$request = [];
		$request_error = [];

		foreach ($registers as $key => $register)
		{
			$formatted_date_time = null;

			foreach ($register as $str_key => $str_val)
			{
				$$str_key = $str_val;
			}

			//erros que vao invalidar o POST
			if ($notification_type && !in_array($notification_type, ['others', 'order']))
			{
				$request_error[] = $this->lang->line('api_legalpanel_post_error_notification_type');
			}

			if ($notification_type && $notification_type == 'others' && !$store_id)
			{
				$request_error[] = $this->lang->line('api_legalpanel_post_error_store_id');
			}

			if ($notification_type && $notification_type == 'order' && !$order_id)
			{
				$request_error[] = $this->lang->line('api_legalpanel_post_error_order_id');
			}

			if ($status && !in_array($status, ['open', 'closed']))
			{
				$request_error[] = $this->lang->line('api_legalpanel_post_error_status');
			}

			if ($amount && floatVal(round(filter_var($amount, FILTER_SANITIZE_NUMBER_INT), 2)) == 0.0)
			{
				$request_error[] = $this->lang->line('api_legalpanel_post_error_amount');
			}

			if (!$put)
			{
				if (!in_array(strlen($date_time), [10, 19]))
				{
					$request_error[] = $this->lang->line('api_legalpanel_post_error_date');
				}

				if (strlen($date_time) == 10 && DateTime::createFromFormat('Y-m-d', $date_time)->format('Y-m-d'))
				{
					$formatted_date_time = DateTime::createFromFormat('Y-m-d H:i:s', $date_time . ' 00:00:00')->format('Y-m-d H:i:s');
				}

				if (strlen($date_time) == 19 && DateTime::createFromFormat('Y-m-d H:i:s', $date_time)->format('Y-m-d H:i:s'))
				{
					$formatted_date_time = DateTime::createFromFormat('Y-m-d H:i:s', $date_time)->format('Y-m-d H:i:s');
				}

				if (!$formatted_date_time)
				{
					$request_error[] = $this->lang->line('api_legalpanel_post_error_date');
				}
			}
			else
			{
				if (!is_numeric($id) || $id < 1)
				{
					$request_error[] = $this->lang->line('api_legalpanel_put_error_id');
				}

				if (count($register) <= 1)
				{
					$request_error[] = $this->lang->line('api_legalpanel_put_error_empty');
				}
			}

			//Entrego o(s) erro(s) de validações obrigatorias
			if (count($request_error) > 0)
			{
				$request['request_error'] = $request_error;
				return $request;
			}

			//formatações extras sanitizar strings e traduzir para regras da tabela
			if ($notification_title)
			{
				$notification_title = filter_var($notification_title, FILTER_SANITIZE_STRING);
			}

			if ($notification_id)
			{
				$notification_id = filter_var($notification_id, FILTER_SANITIZE_STRING);
			}

			if ($description)
			{
				$description = filter_var($description, FILTER_SANITIZE_STRING);
			}

			if ($attachment)
			{
				$attachment = filter_var($attachment, FILTER_SANITIZE_STRING);
			}

			if ($status)
			{
				$status = (filter_var($status, FILTER_SANITIZE_STRING) == 'open') ? 'Chamado Aberto' : 'Chamado Fechado';
			}

			if ($amount)
			{
				$amount = floatVal(round((filter_var($amount, FILTER_SANITIZE_NUMBER_INT) / 100), 2));
			}

			//criação dos arrays de post e put
			if (!$put)
			{
				$request[] = [
					"notification_type"   => filter_var($notification_type, FILTER_SANITIZE_STRING),
					"notification_title"  => (strlen($notification_title) > 0) ? $notification_title : "",
					"orders_id"           => (intVal($order_id) > 0) ? filter_var($order_id, FILTER_SANITIZE_NUMBER_INT) : 0,
					"store_id"            => (intVal($store_id) > 0) ? filter_var($store_id, FILTER_SANITIZE_NUMBER_INT) : 0,
					"notification_id"     => (strlen($notification_id) > 0) ? $notification_id : '',
					"status"              => $status,
					"description"         => (strlen($description) > 0) ? $description : null,
					"balance_paid"        => $amount,
					"balance_debit"       => $amount,
					"attachment"          => (strlen($attachment) > 0) ? $attachment : null,
					"accountable_opening" => $this->header['x-email'],
					"accountable_update"  => null,
					"creation_date"       => $formatted_date_time
				];
			}
			else
			{
				foreach ($register as $put_key => $put_val)
				{
					if ($put_key == "id" || $put_key == "date_time")
					{
						continue;
					}
					else if ($put_key == 'amount')
					{
						$request[$id]['balance_paid'] = $amount;
						$request[$id]['balance_debit'] = $amount;
					}
					else if ($put_key == "order_id")
					{
						$request[$id]['orders_id'] = $$put_key;
					}
					else
					{
						$request[$id][$put_key] = $$put_key;
					}
				}
			}

			//reseto as variaveis dinamicas para nao entrarem no array do proximo put, caso o campo nao exista
			foreach ($register as $str_key => $str_val)
			{
				unset($$str_key);
			}

		}

		return $request;
	}

}
