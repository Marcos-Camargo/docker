<?php 
/*
SW Serviços de Informática 2019

Controller de Grupos (Permissão de Acesso)

*/

/**
 * @property Model_groups $model_groups
 * @property Model_gateway_settings $model_gateway_settings
 * @property Model_settings $model_settings
 * @property Model_gateway $model_gateway
 */

class Groups extends Admin_Controller
{
	public function __construct()
	{
		parent::__construct();

		$this->not_logged_in();

		$this->data['page_title'] = $this->lang->line('application_groups');
		

		$this->load->model('model_groups');
        $this->load->model('model_gateway_settings');
        $this->load->model('model_settings');
        $this->load->model('model_gateway');
	}

	/* 
	* It redirects to the manage group page
	* As well as the group data is also been passed to display on the view page
	*/
	public function index()
	{

		if(!in_array('viewGroup', $this->permission)) {
			redirect('dashboard', 'refresh');
		}

		$groups_data = $this->model_groups->getGroupData();
		$this->data['groups_data'] = $groups_data;

		$this->render_template('groups/index', $this->data);
	}	

	/*
	* If the validation is not valid, then it redirects to the create page.
	* If the validation is for each input field is valid then it inserts the data into the database 
	* and it stores the operation message into the session flashdata and display on the manage group page
	*/
	public function create()
	{
		if (!in_array('createGroup', $this->permission)) {
			redirect('dashboard', 'refresh');
		}

        $this->data['use_ms_shipping'] = $this->model_settings->getValueIfAtiveByName('use_ms_shipping');

		$this->form_validation->set_rules('group_name', $this->lang->line('application_group_name'), 'required');

        $payment_gateway_id = $this->model_settings->getValueIfAtiveByName('payment_gateway_id');
        $this->data['gatewayCode'] = $payment_gateway_id ? $this->model_gateway->getGatewayCodeById($payment_gateway_id) : null;
		
        if ($this->form_validation->run()) {
            // true case
            $permission = serialize($this->postClean('permission',true));
            if  ($permission != 'N;') {  // representação de nulo
				
				$data = array(
					'group_name'        => $this->postClean('group_name', true ),
					'group_description' => $this->postClean('group_description', true ),
					'permission'        => $permission,
					'only_admin'        => $this->postClean('only_admin', true)
				);

				$create = $this->model_groups->create($data);
				if($create) {
					$group_data = array ('id' => $create);
					get_instance()->log_data('Groups', 'create', json_encode(array_merge($group_data,$data)), "I");
					$this->session->set_flashdata('success', $this->lang->line('messages_successfully_created'));
					redirect('groups/', 'refresh');
				}
				else {
					$this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
					redirect('groups/create', 'refresh');
				}
			}
			else {
				$this->session->set_flashdata('error', 'Um grupo tem que ter pelo menos 1 permissão ativa');
			}
			
        }
        
		$this->data['group_data'] = array (
			'id' 			=> 0 ,
			'group_name' 	=> is_null($this->postClean('group_name', true)) ? '' : $this->postClean('group_name', true),
			'permission' 	=> is_null($this->postClean('permission',true)) ? serialize(array()) : serialize($this->postClean('permission',true)),
			'only_admin' 	=> is_null($this->postClean('only_admin', true)) ? false : $this->postClean('only_admin', true),
		);

		$this->data['function'] = 'create';
		$this->render_template('groups/edit', $this->data);
    }

	/*
	* If the validation is not valid, then it redirects to the edit group page 
	* If the validation is successfully then it updates the data into the database 
	* and it stores the operation message into the session flashdata and display on the manage group page
	*/
	public function edit($id = null)
	{

		if(!in_array('updateGroup', $this->permission)) {
			redirect('dashboard', 'refresh');
		}
		if (is_null($id)) {
			redirect('groups/', 'refresh');
		}
		if (($id == 1) && ($this->data['user_group_id'] != 1)) {  // somente quem é do grupo 1 ediat o grupo 1
			redirect('groups/', 'refresh');
		}

        $this->data['use_ms_shipping'] = $this->model_settings->getValueIfAtiveByName('use_ms_shipping');
		
		$group_data = $this->model_groups->getGroupData($id);
		$this->form_validation->set_rules('group_name', $this->lang->line('application_group_name'), 'required');

        $payment_gateway_id = $this->model_settings->getValueIfAtiveByName('payment_gateway_id');
        $this->data['gatewayCode'] = $payment_gateway_id ? $this->model_gateway->getGatewayCodeById($payment_gateway_id) : null;
		
		if ($this->form_validation->run()) {
			// true case
			$permission = serialize($this->postClean('permission',true));
			if  ($permission != 'N;') {  // representação de nulo					
				$data = array(
					'group_name'        => $this->postClean('group_name'),
                    'group_description' => $this->postClean('group_description'),
					'permission'        => $permission,
					'only_admin'        => $this->postClean('only_admin')
				);
				get_instance()->log_data('Groups', 'edit before', json_encode($group_data), "I");

				$update = $this->model_groups->edit($data, $id);
				if($update) {
					get_instance()->log_data('Groups', 'edit after', json_encode(array_merge(array('id'=>$id),$data)), "I");
					$this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
					redirect('groups/', 'refresh');
				}
				else {
					$this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
					redirect('groups/edit/'.$id, 'refresh');
				}
		
			}
			else {
				$this->session->set_flashdata('error', 'Um grupo tem que ter pelo menos 1 permissão ativa');					
			}
		}
					
		
		$this->data['group_data'] = $group_data;
		$this->data['function'] = 'edit';
		$this->render_template('groups/edit', $this->data);	
	        
		
	}

	/*
	* It removes the removes information from the database 
	* and it stores the operation message into the session flashdata and display on the manage group page
	*/
	public function delete($id)
	{

		if(!in_array('deleteGroup', $this->permission)) {
			redirect('dashboard', 'refresh');
		}

		if($id) {
			if($this->postClean('confirm')) {

				$check = $this->model_groups->existInUserGroup($id);
				if($check) {
					$this->session->set_flashdata('error', $this->lang->line('messages_group_exists_users'));
	        		redirect('groups/', 'refresh');
				}
				else {
					$delete = $this->model_groups->delete($id);
					if($delete) {
		        		$this->session->set_flashdata('success', $this->lang->line('messages_successfully_removed'));
		        		redirect('groups/', 'refresh');
		        	}
		        	else {
		        		$this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
		        		redirect('groups/delete/'.$id, 'refresh');
		        	}
				}	
			}	
			else {
				$this->data['id'] = $id;
				$this->render_template('groups/delete', $this->data);
			}	
		}
	}

    public function view($id = null)
    {

        if(!in_array('viewGroup', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $allow_transfer_between_accounts = $this->model_gateway_settings->getGatewaySettingByName(2, 'allow_transfer_between_accounts');

        $this->data['use_ms_shipping'] = $this->model_settings->getValueIfAtiveByName('use_ms_shipping');

        if($id) {
            $group_data = $this->model_groups->getGroupData($id);
            $this->data['group_data'] = $group_data;
            $this->data['function'] = 'view';
            $this->data['allow_transfer_between_accounts'] = ($allow_transfer_between_accounts == '1') ? true : false;
            $this->render_template('groups/edit', $this->data);
        }else {
            redirect('dashboard', 'refresh');
        }
    }

}