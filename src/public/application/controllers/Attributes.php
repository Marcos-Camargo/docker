<?php
/*
SW Serviços de Informática 2019

Controller de Atributos

*/  
defined('BASEPATH') OR exit('No direct script access allowed');

class Attributes extends Admin_Controller 
{
	public function __construct()
	{
		parent::__construct();

		$this->not_logged_in();

		$this->data['page_title'] = $this->lang->line('application_attributes');

		$this->load->model('model_attributes');
	}

	/* 
	* redirect to the index page 
	*/
	public function index()
	{
		if(!in_array('viewAttribute', $this->permission)) {
			redirect('dashboard', 'refresh');
		}
        $attribute_info = $this->model_attributes->getActiveAttributeData('attributes');
        $attribute_types= $this->model_attributes->getAttributeValueData($attribute_info[0]['id']);
		$this->data['$attribute_types'] = $attribute_types;

		$this->render_template('attributes/index', $this->data);	
	}

	/* 
	* fetch the attribute data through attribute id 
	*/
	public function fetchAttributeDataById($id) 
	{
		if($id) {
			$data = $this->model_attributes->getAttributeDataById($id);
			echo json_encode($data);
		}
	}

	/* 
	* gets the attribute data from data and returns the attribute 
	*/
	public function fetchAttributeData()
	{
		
		$result = array('data' => array());

		$data = $this->model_attributes->getAttributeData();

		foreach ($data as $key => $value) {

			$count_attribute_value = $this->model_attributes->countAttributeValue($value['id']);

			// button
			$buttons = '<a href="'.base_url('attributes/addvalue/'.$value['id']).'" class="btn btn-default"><i class="fa fa-plus"></i> '.$this->lang->line('application_add_values').'</a> 
			<button type="button" class="btn btn-default" onclick="editFunc('.$value['id'].')" data-toggle="modal" data-target="#editModal"><i class="fa fa-pencil"></i></button>
			<button type="button" class="btn btn-default" onclick="removeFunc('.$value['id'].',\''.$value['name'].'\')" data-toggle="modal" data-target="#removeModal"><i class="fa fa-trash"></i></button>
			';

			$status = ($value['active'] == 1) ? '<span class="label label-success">Active</span>' : '<span class="label label-warning">Inactive</span>';

			if($value['name'] == "cancel_penalty_to"){
				$buttons = '<a href="'.base_url('attributes/addvalue/'.$value['id']).'" class="btn btn-default"><i class="fa fa-plus"></i> '.$this->lang->line('application_add_values').'</a> 
							<a href="'.base_url('attributes/historyattributes/'.$value['id']).'" class="btn btn-default"><i class="fa fa-history"></i> </a> 
							';
			}

			if($value['name'] == "cancel_reasons"){
				$buttons = '<a href="'.base_url('attributes/addvalue/'.$value['id']).'" class="btn btn-default"><i class="fa fa-plus"></i> '.$this->lang->line('application_add_values').'</a> 
							<a href="'.base_url('attributes/historyattributes/'.$value['id']).'" class="btn btn-default"><i class="fa fa-history"></i> </a> 
							';
			}

			$result['data'][$key] = array(
				$value['name'],
				$value['att_type'],
				$count_attribute_value,
				$status,
				$buttons
			);
		} // /foreach

		echo json_encode($result);
	}

	/* 
	* create the new attribute value 
	*/
	public function create()
	{
		if(!in_array('createAttribute', $this->permission)) {
			redirect('dashboard', 'refresh');
		}

		$response = array();

		$this->form_validation->set_rules('attribute_name', $this->lang->line('application_attribute_name'), 'trim|required');
		$this->form_validation->set_rules('active', $this->lang->line('application_active'), 'trim|required');
		$this->form_validation->set_rules('att_type', $this->lang->line('application_type'), 'trim|required');

		$this->form_validation->set_error_delimiters('<p class="text-danger">','</p>');

        if ($this->form_validation->run() == TRUE) {
        	$data = array(
        		'name' => $this->postClean('attribute_name'),
        		'active' => $this->postClean('active'),	
        		'att_type' => $this->postClean('att_type'),	
        	);
        	$create = $this->model_attributes->create($data);
			$atribute = $this->model_attributes->getAttribute($data);

			$atributeValue = array(
        		'value' => $atribute["name"],
        		'attribute_parent_id' => $atribute["id"]
        	);
			$this->model_attributes->createValue($atributeValue);
			
        	if($create == true) {
        		$response['success'] = true;
        		$response['messages'] = $this->lang->line('messages_successfully_created');
        	}
        	else {
        		$response['success'] = false;
        		$response['messages'] = $this->lang->line('messages_error_database_create_brand');
        	}
        }
        else {
        	$response['success'] = false;
        	foreach ($_POST as $key => $value) {
        		$response['messages'][$key] = form_error($key);
        	}
        }
		ob_clean();
        echo json_encode($response);
	}

	/* 
	* update the attribute value via attribute id 
	*/
	public function update($id)
	{
		if(!in_array('updateAttribute', $this->permission)) {
			redirect('dashboard', 'refresh');
		}

		$response = array();

		if($id) {
			$this->form_validation->set_rules('edit_attribute_name', $this->lang->line('application_attribute_name'), 'trim|required');
			$this->form_validation->set_rules('edit_active', $this->lang->line('application_active'), 'trim|required');
//    		$this->form_validation->set_rules('att_type', $this->lang->line('application_type'), 'trim|required');

			$this->form_validation->set_error_delimiters('<p class="text-danger">','</p>');

	        if ($this->form_validation->run() == TRUE) {
	        	$data = array(
	        		'name' => $this->postClean('edit_attribute_name'),
	        		'active' => $this->postClean('edit_active'),	
					'att_type' => $this->postClean('edit_att_type'),	
	        	);

	        	$update = $this->model_attributes->update($data, $id);
	        	if($update == true) {
	        		$response['success'] = true;
	        		$response['messages'] = $this->lang->line('messages_successfully_updated');
	        	}
	        	else {
	        		$response['success'] = false;
	        		$response['messages'] = $this->lang->line('messages_error_database_update_brand');
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
	* remove the attribute value via attribute id 
	*/
	public function remove()
	{
		if(!in_array('deleteAttribute', $this->permission)) {
			redirect('dashboard', 'refresh');
		}

		$attribute_id = $this->postClean('attribute_id');

		$response = array();
		if($attribute_id) {
			$delete = $this->model_attributes->remove($attribute_id);
			if($delete == true) {
				$response['success'] = true;
				$response['messages'] = $this->lang->line('messages_successfully_removed');
			}
			else {
				$response['success'] = false;
				$response['messages'] = $this->lang->line('messages_error_database_create_brand');
			}
		}
		else {
			$response['success'] = false;
			$response['messages'] = $this->lang->line('messages_refresh_page_again');
		}
		ob_clean();
		echo json_encode($response);
	}

	/* ATTRIBUTE VALUE SECTION */

	/* 
	* this function redirects to the addvalue page with the parent attribute id 
	*/
	public function addvalue($attribute_id = null)
	{
		
		if(!$attribute_id) {
			redirect('dashboard', 'refresh');
		}

		$attribute = $this->model_attributes->getAttributeData($attribute_id);

		$this->data['attribute_data'] = $attribute;


		if($attribute['name'] == "cancel_penalty_to"){
			$this->render_template('attributes/addvaluecancelpenalty', $this->data);
		}elseif($attribute['name'] == "cancel_reasons"){
			$this->render_template('attributes/addvaluecancelreasons', $this->data);
		}else{
			$this->render_template('attributes/addvalue', $this->data);	
		}
	}

	public function historyattributes($attribute_id = null)
	{
		
		if(!$attribute_id) {
			redirect('dashboard', 'refresh');
		}

		$attribute = $this->model_attributes->getAttributeData($attribute_id);

		$this->data['attribute_data'] = $attribute;

		$this->render_template('attributes/historyattribute', $this->data);	

	}

	public function fetchAttributeHistoryValueData($attribute_parent_id)
	{
		$result = array('data' => array());

		$data = $this->model_attributes->getAttributeHistoryValueData($attribute_parent_id);

		$i = 0;
		foreach ($data as $key => $value) {

			$cobraComissao = "";
			$motivoPadrao = "";

			if($value['commission_charges'] == 0){
				$cobraComissao = "Não";
			}else{
				$cobraComissao = "Sim";
			}

			if($value['default_reason'] == 0){
				$motivoPadrao = "Não";
			}else{
				$motivoPadrao = "Sim";
			}

			$result['data'][$i] = array(
				$value['attribute_value_id'],
				$value['value'],
				$cobraComissao,
				$motivoPadrao,
				$value['date_insert_format'],
				$value['usuario'],
				$value['action']
			);
			$i++;
		} // /foreach
		
		echo json_encode($result);
	}


	/* 
	* fetch the attribute value based on the attribute parent id 
	*/
	public function fetchAttributeValueData($attribute_parent_id)
	{
		$result = array('data' => array());

		$data = $this->model_attributes->getAttributeValueData($attribute_parent_id);
		$attribute = $this->model_attributes->getAttributeData($attribute_parent_id);


		if($attribute['name'] == "cancel_penalty_to"){
			$i = 0;
			foreach ($data as $key => $value) {

				if($value['active'] != 1){
					continue;
				}

				// button
				$buttons = '
				<button type="button" class="btn btn-default" onclick="editFunc('.$value['id'].')" data-toggle="modal" data-target="#editModal"><i class="fa fa-pencil"></i></button>
				<button type="button" class="btn btn-default" onclick="removeFunc('.$value['id'].',\''.$value['value'].'\')" data-toggle="modal" data-target="#removeModal"><i class="fa fa-trash"></i></button>
				';

				$cobraComissao = "";
				$motivoPadrao = "";

				if($value['commission_charges'] == 0){
					$cobraComissao = "Não";
				}else{
					$cobraComissao = "Sim";
				}

				if($value['default_reason'] == 0){
					$motivoPadrao = "Não";
				}else{
					$motivoPadrao = "Sim";
				}

				$result['data'][$i] = array(
					$value['value'],
					$cobraComissao,
					$motivoPadrao,
					$buttons
				);
				$i++;
			} // /foreach
			
		}elseif($attribute['name'] == "cancel_reasons"){

			$i = 0;
			foreach ($data as $key => $value) {

				if($value['active'] != 1){
					continue;
				}

				// button
				$buttons = '
				<button type="button" class="btn btn-default" onclick="editFunc('.$value['id'].')" data-toggle="modal" data-target="#editModal"><i class="fa fa-pencil"></i></button>
				<button type="button" class="btn btn-default" onclick="removeFunc('.$value['id'].',\''.$value['value'].'\')" data-toggle="modal" data-target="#removeModal"><i class="fa fa-trash"></i></button>
				';

				
				$motivoPadrao = "";


				if($value['default_reason'] == 0){
					$motivoPadrao = "Não";
				}else{
					$motivoPadrao = "Sim";
				}

				$result['data'][$i] = array(
					$value['value'],
					$motivoPadrao,
					$buttons
				);
				$i++;
			} // /foreach

		}else{

			foreach ($data as $key => $value) {

				// button
				$buttons = '
				<button type="button" class="btn btn-default" onclick="editFunc('.$value['id'].')" data-toggle="modal" data-target="#editModal"><i class="fa fa-pencil"></i></button>
				<button type="button" class="btn btn-default" onclick="removeFunc('.$value['id'].',\''.$value['value'].'\')" data-toggle="modal" data-target="#removeModal"><i class="fa fa-trash"></i></button>
				';

				$result['data'][$key] = array(
					$value['value'],
					$buttons
				);
			} // /foreach
		}

		echo json_encode($result);
	}

	/* 
	* fetch the attribute value by the attritute value id  
	*/
	public function fetchAttributeValueById($id) 
	{
		if($id) {
			$data = $this->model_attributes->getAttributeValueDataById($id);
			echo json_encode($data);
		}
	}

	/* 
	* this function only creates the value 
	*/ 
	public function createValue()
	{
		$response = array();

		$this->form_validation->set_rules('attribute_value_name', $this->lang->line('application_attribute_name'), 'trim|required');

		$this->form_validation->set_error_delimiters('<p class="text-danger">','</p>');

        if ($this->form_validation->run() == TRUE) {
        	
			$attribute_parent_id = $this->postClean('attribute_parent_id');
			$attribute = $this->model_attributes->getAttributeData($attribute_parent_id);

			if($attribute['name'] == "cancel_penalty_to"){

				$campos = $this->postClean();

				$commission_charges = 1;
				$default_reason = 0;

				if( array_key_exists('ck_commission_charges',$campos) ){
					$commission_charges = 0;
				}

				if( array_key_exists('ck_default_reason',$campos) ){
					$default_reason = 1;

					//Inativa o outro motivo que possa ser o padrão para manter apenas 1 como padrão na tela
					$this->model_attributes->updateValueByParentIdDefaultReason($attribute_parent_id);

				}

				$data = array(
					'value' => $this->postClean('attribute_value_name'),
					'commission_charges' => $commission_charges,
					'default_reason' => $default_reason,
					'attribute_parent_id' => $attribute_parent_id
				);
				
			}elseif($attribute['name'] == "cancel_reasons"){

				$campos = $this->postClean();

				$default_reason = 0;

				if( array_key_exists('ck_default_reason',$campos) ){
					$default_reason = 1;

					//Inativa o outro motivo que possa ser o padrão para manter apenas 1 como padrão na tela
					$this->model_attributes->updateValueByParentIdDefaultReason($attribute_parent_id);

				}

				$data = array(
					'value' => $this->postClean('attribute_value_name'),
					'default_reason' => $default_reason,
					'attribute_parent_id' => $attribute_parent_id
				);

			}else{
				
				$data = array(
					'value' => $this->postClean('attribute_value_name'),
					'attribute_parent_id' => $attribute_parent_id
				);
			}

        	$create = $this->model_attributes->createValue($data);

			if($attribute['name'] == "cancel_penalty_to"){
				$idInserido = $this->model_attributes->createValuelastId();
				$this->model_attributes->insertLogAttributesValues("Criação", $idInserido);
			}

        	if($create == true) {
        		$response['success'] = true;
        		$response['messages'] = $this->lang->line('messages_successfully_created');
        	}
        	else {
        		$response['success'] = false;
        		$response['messages'] = $this->lang->line('messages_error_database_create_brand');
        	}
        }
        else {
        	$response['success'] = false;
        	foreach ($_POST as $key => $value) {
        		$response['messages'][$key] = form_error($key);
        	}
        }
		ob_clean();
        echo json_encode($response);
	}

	/* 
	* It updates the attribute value based on the attribute value id 
	*/
	public function updateValue($id)
	{

		$response = array();

		if($id) {
			$this->form_validation->set_rules('edit_attribute_value_name', $this->lang->line('application_attribute_name'), 'trim|required');

			$this->form_validation->set_error_delimiters('<p class="text-danger">','</p>');

	        if ($this->form_validation->run() == TRUE) {
	        	$attribute_parent_id = $this->postClean('attribute_parent_id');
				$attribute = $this->model_attributes->getAttributeData($attribute_parent_id);

			if($attribute['name'] == "cancel_penalty_to"){

				$campos = $this->postClean();

				$commission_charges = 1;
				$default_reason = 0;

				if( array_key_exists('edit_ck_commission_charges',$campos) ){
					$commission_charges = 0;
				}

				if( array_key_exists('edit_ck_default_reason',$campos) ){
					$default_reason = 1;

					//Inativa o outro motivo que possa ser o padrão para manter apenas 1 como padrão na tela
					$this->model_attributes->updateValueByParentIdDefaultReason($attribute_parent_id);
				}

				$data = array(
					'value' => $this->postClean('edit_attribute_value_name'),
					'commission_charges' => $commission_charges,
					'default_reason' => $default_reason,
					'attribute_parent_id' => $attribute_parent_id
				);
				
				}elseif($attribute['name'] == "cancel_reasons"){

					$campos = $this->postClean();

					$default_reason = 0;

					if( array_key_exists('edit_ck_default_reason',$campos) ){
						$default_reason = 1;

						//Inativa o outro motivo que possa ser o padrão para manter apenas 1 como padrão na tela
						$this->model_attributes->updateValueByParentIdDefaultReason($attribute_parent_id);
					}

					$data = array(
						'value' => $this->postClean('edit_attribute_value_name'),
						'default_reason' => $default_reason,
						'attribute_parent_id' => $attribute_parent_id
					);

				}else{

					$data = array(
						'value' => $this->postClean('edit_attribute_value_name'),
						'attribute_parent_id' => $attribute_parent_id
					);
				}

	        	$update = $this->model_attributes->updateValue($data, $id);
				$this->model_attributes->insertLogAttributesValues("Alteração", $id);

	        	if($update == true) {
	        		$response['success'] = true;
	        		$response['messages'] = $this->lang->line('messages_successfully_updated');
	        	}
	        	else {
	        		$response['success'] = false;
	        		$response['messages'] = $this->lang->line('messages_error_database_update_brand');
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
	* it removes the attribute value id based on the attribute value id 
	*/
	public function removeValue()
	{
		

		$attribute_value_id = $this->postClean('attribute_value_id');
		$attribute = $this->model_attributes->getAttributeDataByAttributeValue($attribute_value_id);
		
		$response = array();
		if($attribute_value_id) {

			if($attribute['name'] == "cancel_penalty_to" || $attribute['name'] == "cancel_reasons" ){
				$data['active'] = 0;
				$delete = $this->model_attributes->updateValue($data, $attribute_value_id);
				$this->model_attributes->insertLogAttributesValues("Exclusão", $attribute_value_id);
			}else{
				$delete = $this->model_attributes->removeValue($attribute_value_id);
			}


			if($delete == true) {
				$response['success'] = true;
				$response['messages'] = $this->lang->line('messages_successfully_removed');
			}
			else {
				$response['success'] = false;
				$response['messages'] = $this->lang->line('messages_error_database_create_brand');
			}
		}
		else {
			$response['success'] = false;
			$response['messages'] = $this->lang->line('messages_refresh_page_again');
		}
		ob_clean();
		echo json_encode($response);
	}

}
