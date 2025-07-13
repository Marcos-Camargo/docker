<?php
/*
SW Serviços de Informática 2019

Controller de Configurações de usuários

*/  
defined('BASEPATH') || exit('No direct script access allowed');

/* ATENÇÂO : AO COLOCAR UM NOVO MS, ALTERAR TAMBÈM O BATCHC/UpdateMSSetting.php */
require_once APPPATH . "libraries/Microservices/v1/Logistic/FreightTables.php";
require_once APPPATH . "libraries/Microservices/v1/Logistic/ShippingIntegrator.php";
require_once APPPATH . "libraries/Microservices/v1/Logistic/ShippingCarrier.php";
require_once APPPATH . "libraries/Microservices/v1/Logistic/PickupPoints.php";
require_once APPPATH . "libraries/Microservices/v1/Logistic/Shipping.php";
require_once APPPATH . "libraries/Microservices/v1/Integration/Price.php";
require_once APPPATH . "libraries/Microservices/v1/Integration/Stock.php";

/* ATENÇÂO : AO COLOCAR UM NOVO MS, ALTERAR TAMBÈM O BATCHC/UpdateMSSetting.php */
use Microservices\v1\Logistic\FreightTables;
use Microservices\v1\Logistic\PickupPoints;
use Microservices\v1\Logistic\ShippingIntegrator;
use Microservices\v1\Logistic\ShippingCarrier;
use Microservices\v1\Logistic\Shipping;
use Microservices\v1\Integration\Price;
use Microservices\v1\Integration\Stock;

/**
 * @property CI_Form_validation $form_validation
 * @property CI_Session $session
 * @property CI_Lang $lang
 * @property CI_Loader $load
 *
 * @property FreightTables $ms_freight_tables
 * @property ShippingIntegrator $ms_shipping_integrator
 * @property ShippingCarrier $ms_shipping_carrier
 * @property Shipping $ms_shipping
 * @property PickupPoints $ms_pickup_points
 * @property Price $ms_price
 * @property Stock $ms_stock
 * @property Model_stores $model_stores
 * @property Model_settings $model_settings
 */

class Settings extends Admin_Controller 
{
	public function __construct()
	{
		parent::__construct();

		$this->not_logged_in();

		$this->data['page_title'] = $this->lang->line('application_settings');

		$this->load->model('model_settings');
		$this->load->model('model_stores');
        $this->load->library("Microservices\\v1\\Logistic\\FreightTables", array(), 'ms_freight_tables');
        $this->load->library("Microservices\\v1\\Logistic\\ShippingIntegrator", array(), 'ms_shipping_integrator');
        $this->load->library("Microservices\\v1\\Logistic\\ShippingCarrier", array(), 'ms_shipping_carrier');
        $this->load->library("Microservices\\v1\\Logistic\\Shipping", array(), 'ms_shipping');
        $this->load->library("Microservices\\v1\\Logistic\\PickupPoints", array(), 'ms_pickup_points');

        $this->load->library("Microservices\\v1\\Integration\\Stock", array(), 'ms_stock');
        $this->load->library("Microservices\\v1\\Integration\\Price", array(), 'ms_price');
        /* ATENÇÂO : AO COLOCAR UM NOVO MS, ALTERAR TAMBÈM O BATCHC/UpdateMSSetting.php */
	}

	/* 
	* It only redirects to the manage product page and
	*/
	public function index()
	{
		if(!in_array('viewConfig', $this->permission)) {
			redirect('dashboard', 'refresh');
		}

        $categories = $this->model_settings->getCategoriesSettings();
		$result = $this->model_settings->getSettingData();

        $this->data['categories'] = $categories;
		$this->data['results'] = $result;

        // if(ENVIRONMENT === 'development'){
            $this->render_template('settings/index_new', $this->data);
        // }else{
        //     $this->render_template('settings/index', $this->data);
        // }

	}

	/*
	* Fetches the Setting data from the Setting table 
	* this function is called from the datatable ajax function
	*/
	public function fetchSettingData()
	{
        ob_start();
		$result = array('data' => array());

		$data = $this->model_settings->getSettingData();
		foreach ($data as $key => $value) {

			// button
			$buttons = '';

			if(in_array('viewConfig', $this->permission)) {
				$buttons .= '<button type="button" class="btn btn-default" onclick="editSetting('.$value['id'].')" data-toggle="modal" data-target="#editSettingModal"><i class="fa fa-pencil"></i></button>';	
			}
			
			if(in_array('deleteConfig', $this->permission)) {
				$buttons .= ' <button type="button" class="btn btn-default" onclick="removeSetting('.$value['id'].',\''.$value['name'].'\')" data-toggle="modal" data-target="#removeSettingModal"><i class="fa fa-trash"></i></button>';
			}				

			$status = ($value['status'] == 1) ? '<span class="label label-success">'.$this->lang->line('application_active').'</span>' : '<span class="label label-warning">'.$this->lang->line('application_inactive').'</span>';

			$result['data'][$key] = array(
				'<span style="word-break:break-all;">'.$value['name'].'</span>',
				'<span style="word-break:break-all;">'.$value['value'].'</span>',
				$status,
				$buttons
			);
		} // /foreach
		
		ob_clean();
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
		if($id) {
			$data = $this->model_settings->getSettingData($id);
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
        ob_start();
		if(!in_array('createConfig', $this->permission)) {
			redirect('dashboard', 'refresh');
		}

		$response = array();

		$this->form_validation->set_rules('setting_name', $this->lang->line('application_setting_name'), 'trim|required');
		$this->form_validation->set_rules('setting_value', $this->lang->line('application_setting_value'), 'trim|required');
		$this->form_validation->set_rules('active', $this->lang->line('application_active'), 'trim|required');

		$this->form_validation->set_error_delimiters('<p class="text-danger">','</p>');

		$all_ok = $this->form_validation->run();
		$setting_name = $this->postClean('setting_name',TRUE);
		if ($setting_name!= '') {
			$exist = $this->model_settings->getSettingDatabyName($setting_name);
			if ($exist) {
				$all_ok = false;
			}
		}
        if ($all_ok) {
        	$data = array(
        		'name' => $setting_name,
        		'value' => $this->postClean('setting_value',TRUE),
        		'status' => $this->postClean('active',TRUE),
        		'user_id' => $this->session->userdata['id'],	
        	);
			$create = $this->model_settings->create($data);
			if($create) {
				$response['success'] = true;
				$response['messages'] = $this->lang->line('messages_successfully_created');

                // Ajustes para o microsserviço.
                $data['active'] = $data['status'] == 1;
                unset($data['status']);
                unset($data['user_id']);
                try {
                    if ($this->ms_shipping_carrier->use_ms_shipping) {
                        $this->ms_shipping_carrier->createSetting($data);
                    }
                } catch (Exception $exception) {}

                try {
                    if ($this->ms_shipping_integrator->use_ms_shipping) {
                        $this->ms_shipping_integrator->createSetting($data);
                    }
                } catch (Exception $exception) {}

                try {
                    if ($this->ms_freight_tables->use_ms_shipping) {
                        $this->ms_freight_tables->createSetting($data);
                    }
                } catch (Exception $exception) {}

                try {
                    if ($this->ms_shipping->use_ms_shipping) {
                        $this->ms_shipping->createSetting($data);
                    }
                } catch (Exception $exception) {}
			}
			else {
				$response['success'] = false;
				$response['messages'] = $this->lang->line('messages_error_database_create_setting');
			}
        }
        else {
        	$response['success'] = false;
        	foreach ($_POST as $key => $value) {
        		$response['messages'][$key] = form_error($key);
        	}
			if ($exist) {
                $response['messages']['setting_name'] = '<p class="text-danger">'.$this->lang->line('application_setting_already_exists').'</p>';  
			}
        }
        ob_clean();
        echo json_encode($response);

	}

    /* ATENÇÂO : AO COLOCAR UM NOVO MS, ALTERAR TAMBÈM O BATCHC/UpdateMSSetting.php */
    private function microServicesSettings($data, $setting_name_old)
    {
        $data = array_merge($data, ['name' => $setting_name_old]);
        if (strcasecmp($setting_name_old, 'use_ms_shipping') === 0) {
            foreach ($this->model_stores->getActiveStore() ?? [] as $store) {
                $this->model_stores->setDateUpdateNow($store['id']);
            }
        }
        try {
            if ($this->ms_shipping_carrier->use_ms_shipping) {
                $this->ms_shipping_carrier->updateSetting($data, $setting_name_old);
            }
        } catch (Exception $exception) {
            $message = $this->ms_shipping_carrier->getErrorFormatted($exception->getMessage());
            if (($message[0] ?? '') === 'Parâmetro não localizado.') {
                // Tentar criar
                try {
                    $this->ms_shipping_carrier->createSetting($data);
                } catch (Exception $exception) {}
            }
        }

        try {
            if ($this->ms_shipping_integrator->use_ms_shipping) {
                $this->ms_shipping_integrator->updateSetting($data, $setting_name_old);
            }
        } catch (Exception $exception) {
            $message = $this->ms_shipping_integrator->getErrorFormatted($exception->getMessage());
            if (($message[0] ?? '') === 'Parâmetro não localizado.') {
                // Tentar criar
                try {
                    $this->ms_shipping_integrator->createSetting($data);
                } catch (Exception $exception) {}
            }
        }

        try {
            if ($this->ms_freight_tables->use_ms_shipping) {
                $this->ms_freight_tables->updateSetting($data, $setting_name_old);
            }
        } catch (Exception $exception) {
            $message = $this->ms_freight_tables->getErrorFormatted($exception->getMessage());
            if (($message[0] ?? '') === 'Parâmetro não localizado.') {
                // Tentar criar
                try {
                    $this->ms_freight_tables->createSetting($data);
                } catch (Exception $exception) {}
            }
        }

        try {
            if ($this->ms_shipping->use_ms_shipping) {
                $this->ms_shipping->updateSetting($data, $setting_name_old);
            }
        } catch (Exception $exception) {
            $message = $this->ms_shipping->getErrorFormatted($exception->getMessage());
            if (($message[0] ?? '') === 'Parâmetro não localizado.') {
                // Tentar criar
                try {
                    $this->ms_shipping->createSetting($data);
                } catch (Exception $exception) {}
            }
        }

        try {
            if ($this->ms_price->use_ms_price) {
                $this->ms_price->updateSetting($data, $setting_name_old);
            }
        } catch (Exception $exception) {
            $message = $this->ms_price->getErrorFormatted($exception->getMessage());
            if (($message[0] ?? '') === 'Parâmetro não localizado.') {
                // Tentar criar
                try {
                    $this->ms_price->createSetting($data);
                } catch (Exception $exception) {
                }
            }
        }

        try {
            if ($this->ms_stock->use_ms_stock) {
                $this->ms_stock->updateSetting($data, $setting_name_old);
            }
        } catch (Exception $exception) {
            $message = $this->ms_stock->getErrorFormatted($exception->getMessage());
            if (($message[0] ?? '') === 'Parâmetro não localizado.') {
                // Tentar criar
                try {
                    $this->ms_stock->createSetting($data);
                } catch (Exception $exception) {
                }
            }
        }

        try {
            if ($this->ms_pickup_points->use_ms_shipping) {
                $this->ms_pickup_points->updateSetting($data, $setting_name_old);
            }
        } catch (Exception $exception) {
            $message = $this->ms_pickup_points->getErrorFormatted($exception->getMessage());
            if (($message[0] ?? '') === 'Parâmetro não localizado.') {
                // Tentar criar
                try {
                    $this->ms_pickup_points->createSetting($data);
                } catch (Exception $exception) {}
            }
        }
        /* ATENÇÂO : AO COLOCAR UM NOVO MS, ALTERAR TAMBÈM O BATCHC/UpdateMSSetting.php */

    }

	/*
	* Its checks the Setting form validation 
	* and if the validation is successfully then it updates the data into the database 
	* and returns the json format operation messages
	*/
	public function update($id)
	{
        ob_start();
		if(!in_array('updateConfig', $this->permission)) {
			redirect('dashboard', 'refresh');
		}

		$response = array();

		if($id) {
            $this->form_validation->set_rules('edit_setting_name', $this->lang->line('application_setting_name'), 'trim|required');
            $this->form_validation->set_rules('edit_setting_value', $this->lang->line('application_setting_value'), 'trim|required');
            $this->form_validation->set_rules('edit_active', $this->lang->line('application_active'), 'trim|required');

			$this->form_validation->set_error_delimiters('<p class="text-danger">','</p>');

	        if ($this->form_validation->run()) {
	        	$data = array(
	        		'name' => $this->postClean('edit_setting_name',TRUE),
	        		'value' => $this->postClean('edit_setting_value',TRUE),
	        		'status' => $this->postClean('edit_active',TRUE),	
	        	);

	        	$update = $this->model_settings->update($data, $id);
	        	if($update) {
	        		$response['success'] = true;
	        		$response['messages'] = $this->lang->line('messages_successfully_updated');
                    if (strcasecmp($data['name'], 'use_ms_shipping') === 0) {
                        foreach ($this->model_stores->getActiveStore() ?? [] as $store) {
                            $this->model_stores->setDateUpdateNow($store['id']);
                        }
                    }

                    // Ajustes para o microsserviço.
                    $setting_name_old = $this->postClean('edit_setting_name_old');
                    $data['active'] = $data['status'] == 1;
                    unset($data['status']);
                    unset($data['user_id']);

                    $this->microServicesSettings($data, $setting_name_old);

	        	}
	        	else {
	        		$response['success'] = false;
	        		$response['messages'] = $this->lang->line('messages_error_database_update_setting');
	        	}
	        }
	        else {
	        	$response['success'] = false;
	        	foreach ($_POST as $key => $value) {
	        		$response['messages'][$key] = form_error($key);
	        	}
	        }
		}
		else {
			$response['success'] = false;
    		$response['messages'] = $this->lang->line('messages_refresh_page_again');
		}
        ob_clean();
		echo json_encode($response);
	}

	/*
	* It removes the Setting information from the database 
	* and returns the json format operation messages
	*/
	public function remove()
	{
        ob_start();
		if(!in_array('deleteConfig', $this->permission)) {
			redirect('dashboard', 'refresh');
		}
		
		$setting_id = $this->postClean('setting_id',TRUE);
		$response = array();
		if($setting_id) {
			$delete = $this->model_settings->remove($setting_id);

			if($delete) {
				$response['success'] = true;
				$response['messages'] = $this->lang->line('messages_successfully_removed');
			}
			else {
				$response['success'] = false;
				$response['messages'] = $this->lang->line('messages_error_database_remove_setting');
			}
		}
		else {
			$response['success'] = false;
			$response['messages'] = $this->lang->line('messages_refresh_page_again');
		}
        ob_clean();
		echo json_encode($response);
	}

    public function insertSetting(){

        $stream_clean = $this->security->xss_clean($this->input->raw_input_stream);
        $request = json_decode($stream_clean, true);

        $response = [];

        $name = $request['name'];
        $friendly_name = $request['friendly_name'];
        $description = $request['description'];
        $value = $request['value'];
        $status = $request['status'];

        if(empty($name)){
            $response = ['status' => false, 'message' => 'O nome do parâmetro é obrigatório!'];
            header('Content-type: application/json');
            exit(json_encode($response));
        }

        if(empty($value)){
            $response = ['status' => false, 'message' => 'O valor do parâmetro é obrigatório!'];
            header('Content-type: application/json');
            exit(json_encode($response));
        }

        if(empty($status)){
            $response = ['status' => false, 'message' => 'O campo status é obrigatório!'];
            header('Content-type: application/json');
            exit(json_encode($response));
        }

        $exist = $this->model_settings->getSettingDatabyName($name);
        if ($exist) {
            $response = ['status' => false, 'message' => 'Já existe um parâmetro com o nome fornecido!'];
            header('Content-type: application/json');
            exit(json_encode($response));
        }

        $setting_category = $this->model_settings->getSettingCategoryByName('Personalizado');
        if(!$setting_category){
            $response = ['status' => false, 'message' => 'Não foi possível encontrar a categoria "Personalizado" no banco de dados!'];
            header('Content-type: application/json');
            exit(json_encode($response));
        }

        $this->load->helper('url');

        $data = array(
            'name' => url_title($name, 'underscore'),
            'value' => $value,
            'status' => $status,
            'friendly_name' => $friendly_name,
            'description' => $description,
            'setting_category_id' => $setting_category->id,
        );
        $create = $this->model_settings->create($data);

        if($create) {
            // Ajustes para o microsserviço.
            $data['active'] = $data['status'] == 1;
            unset($data['status']);
            unset($data['user_id']);
            try {
                if ($this->ms_shipping_carrier->use_ms_shipping) {
                    $this->ms_shipping_carrier->createSetting($data);
                }
            } catch (Exception $exception) {}

            try {
                if ($this->ms_shipping_integrator->use_ms_shipping) {
                    $this->ms_shipping_integrator->createSetting($data);
                }
            } catch (Exception $exception) {}

            try {
                if ($this->ms_freight_tables->use_ms_shipping) {
                    $this->ms_freight_tables->createSetting($data);
                }
            } catch (Exception $exception) {}

            try {
                if ($this->ms_shipping->use_ms_shipping) {
                    $this->ms_shipping->createSetting($data);
                }
            } catch (Exception $exception) {}

            try {
                if ($this->ms_price->use_ms_price) {
                    $this->ms_price->createSetting($data);
                }
            } catch (Exception $exception) {
            }
            try {
                if ($this->ms_stock->use_ms_stock) {
                    $this->ms_stock->createSetting($data);
                }
            } catch (Exception $exception) {
            }
        } else {
            foreach ($_POST as $key => $value) {
                $response['messages'][$key] = form_error($key);
            }
            $response['status'] = false;
            $response['message'] = 'Ocorreu um erro ao criar o parâmetro!';
        }
        ob_clean();

        header('Content-type: application/json');
        exit(json_encode($response));

    }

    public function saveSetting(){

        $stream_clean = $this->security->xss_clean($this->input->raw_input_stream);
        $request = json_decode($stream_clean, true);

        $response = [];

        $id = $request['id'];
        $name = $request['name'];
        $friendly_name = $request['friendly_name'];
        $description = $request['description'];
        $value = $request['value'];
        $status = $request['status'];

        $setting = $this->model_settings->getSettingData($id);
        $old_name = $setting['name'];

        $data = array(
            // 'name' => $name,
            'friendly_name' => $friendly_name,
            'description' => $description,
            'value' => $value,
            'status' => $status,
        );
        $update = $this->model_settings->update($data, $id);

        if($update) {
            $data['active'] = $data['status'] == 1;
            unset($data['status']);
            unset($data['user_id']);

            $this->microServicesSettings($data, $old_name);
            $response['status'] = true;
        }else{
            $response['status'] = false;
        }

        header('Content-type: application/json');
        exit(json_encode($response));

    }

    public function getSettings(){

        $stream_clean = $this->security->xss_clean($this->input->raw_input_stream);
        $request = json_decode($stream_clean, true);

        $cat = $request['category'];

        if($cat == 'all'){

            $response = [];

            $settings = $this->model_settings->getAllSettings();

            foreach($settings as $key => $setting){

                $category = $setting['category_name'];

                $exists = $this->checkCategoryExistsInArray($response, $category);
                if(!$exists) {
                    $response[] = [
                        'category' => $category,
                        'settings' => []
                    ];
                }

                foreach($response as $key => $r){
                    if($r['category'] == $category){
                        $response[$key]['settings'][] = $setting;
                    }
                }

            }

        }else{
            $settings = $this->model_settings->getSettingsByCategory($cat);
            $response = [
                'category' => $cat,
                'settings' => $settings
            ];
        }

        header('Content-type: application/json');
        exit(json_encode($response));

    }

    private function checkCategoryExistsInArray($array, $category): bool
    {
        if(count($array) == 0){
            return false;
        }

        foreach ($array as $k => $a) {
            if($a['category'] == $category){
                return true;
            }
        }

        return false;
    }

}