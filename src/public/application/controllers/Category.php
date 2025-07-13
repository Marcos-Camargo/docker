<?php
/*  
SW Serviços de Informática 2019
 
Controller de Categorias

*/  
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property CI_Loader $load
 * @property CI_Session $session
 * @property CI_Lang $lang
 *
 * @property Model_csv_to_verifications $model_csv_to_verifications
 * @property Model_settings $model_settings
 * @property Model_category $model_category
 * @property Model_categorias_marketplaces $model_categorias_marketplaces
 * @property Model_products $model_products
 *
 * @property CSV_Validation $csv_validation
 */

class Category extends Admin_Controller 
{
	
	public function __construct()
	{
		parent::__construct();

		$this->not_logged_in();

		$this->data['page_title'] = $this->lang->line('application_category');

		$this->load->model('model_category');
		$this->load->model('model_categorias_marketplaces');
		$this->load->model('model_products');
		$this->load->model('model_settings');
        $this->load->model('model_csv_to_verifications');
        $this->load->library('CSV_Validation', [
            'permission' => $this->permission
        ]);
	}

	/* 
	* It only redirects to the manage category page
	*/
	public function index()
	{

		if(!in_array('viewCategory', $this->permission)) {
			redirect('dashboard', 'refresh');
		}
		//rick 
		$this->data['tipos_volumes'] = $this->model_category->getTiposVolumes();
		$this->render_template('category/index', $this->data);
	}	

	/*
	* It checks if it gets the category id and retreives
	* the category information from the category model and 
	* returns the data into json format. 
	* This function is invoked from the view page.
	*/
	public function fetchCategoryDataById($id) 
	{
		if($id) {
            $data = $this->model_category->getCategoryData($id);
            $data['qtd_products'] = $this->model_products->getCountProductsCategory($id);
			// SW - Log Update
			get_instance()->log_data('Category','edit before',json_encode($data),"I");
			echo json_encode($data);
		}

		return false;
	}

	/*
	* Fetches the category value from the category table 
	* this function is called from the datatable ajax function
	*/
	public function fetchCategoryDataNovo()
	{
        ob_start();
		$postdata = $this->postClean(NULL,TRUE);
        $ini = $postdata['start'];
        $draw = $postdata['draw'];
        $length = $postdata['length'];
		
		$dont_show_link_marketplace = $this->model_settings->getValueIfAtiveByName('category_dont_show_link_marketplace');
		
        $busca = $postdata['search'];
        $procura = '';
        if ($busca['value']) {
            if (strlen($busca['value'])>=2) {  // Garantir no minimo 3 letras
                $procura = " (c.id = ".$busca['value']." OR  c.name like '%".$busca['value']."%' OR tv.produto like '%".$busca['value']."%' ) ";
            }
        }
        
        $sOrder = "";
        if (isset($postdata['order'])) {
            if ($postdata['order'][0]['dir'] == "asc") {
                $direcao = "asc";
            } else {
                $direcao = "desc";
            }
            $campos = array('c.id','c.name','c.active','tv.produto','','usedby','','','');
            $campo =  $campos[$postdata['order'][0]['column']];
            if ($campo != "") {
                $sOrder = " ORDER BY ".$campo." ".$direcao;
            }
        }
        
        $result = array();
        
       	$data = $this->model_category->getFeatchCategoryData($ini,$sOrder, $procura, $length);
        
        $filtered = $this->model_category->getCountCategoryData($procura);

        foreach ($data as $key => $value) {

			$buttons = '';

			if(in_array('updateCategory', $this->permission)) {
				//$buttons .= '<button type="button" class="btn btn-default" onclick="editFunc('.$value['id'].')" data-toggle="modal" data-target="#editModal"><i class="fa fa-pencil"></i></button>';
				$buttons .= '<a class="btn btn-default" href="'.base_url('category/edit/'.$value['id']).'"><i class="fa fa-pencil"></i></a>';
			}

			if(in_array('deleteCategory', $this->permission)) {
				$buttons .= ' <button type="button" class="btn btn-default" onclick="removeFunc('.$value['id'].',\''.$value['name'].'\')" data-toggle="modal" data-target="#removeModal"><i class="fa fa-trash"></i></button>';
			}
			
			if(in_array('updateCategory', $this->permission) && (!$dont_show_link_marketplace)) {
				$buttons .= ' <a href="'.base_url('category/link/'.$value['id']).'" class="btn btn-default" data-toggle="tooltip" data-placement="top" title="link mktplace" ><i class="fa fa-link"></i></a>';
			}	
			
			$status = ($value['active'] == 1) ? '<span class="label label-success">Active</span>' : '<span class="label label-warning">Inactive</span>';
			$catlinks = $this->model_categorias_marketplaces->getDataByCategoryId($value['id']);
			$marketplaces = '';  
			foreach($catlinks as $catlink) {
				$marketplaces.='<span class="label label-success">'.$catlink['int_to'].'('.$catlink['category_marketplace_id'].')</span>';
			}

			$result[$key] = array(
				$value['id'],
				$value['name'],
				$status,
				$value['produto'],
				$marketplaces,
				$value['usedby'],
				$buttons
			);
        } // /foreach
        $output = array(
            "draw" => $draw,
            "recordsTotal" => $this->model_category->getCountCategoryData(),
            "recordsFiltered" => $filtered,
            "data" => $result
        );
        ob_clean(); 
        echo json_encode($output);
	}
	

	public function fetchCategoryData()
	{
        ob_start();
        $result = array('data' => array());
        $data = $this->model_category->getCategoryData();
		$dont_show_link_marketplace = $this->model_settings->getValueIfAtiveByName('category_dont_show_link_marketplace');
		
		foreach ($data as $key => $value) {

			// button
			$buttons = '';

			if(in_array('updateCategory', $this->permission)) {
                            //$buttons .= '<button type="button" class="btn btn-default" onclick="editFunc('.$value['id'].')" data-toggle="modal" data-target="#editModal"><i class="fa fa-pencil"></i></button>';
                            $buttons .= '<a class="btn btn-default" href="'.base_url('category/edit/'.$value['id']).'"><i class="fa fa-pencil"></i></a>';
			}

			if(in_array('deleteCategory', $this->permission)) {
				$buttons .= ' <button type="button" class="btn btn-default" onclick="removeFunc('.$value['id'].',\''.$value['name'].'\')" data-toggle="modal" data-target="#removeModal"><i class="fa fa-trash"></i></button>';
			}
			if(in_array('updateCategory', $this->permission) && (!$dont_show_link_marketplace)) {
				$buttons .= ' <a href="'.base_url('category/link/'.$value['id']).'" class="btn btn-default" data-toggle="tooltip" data-placement="top" title="link mktplace" ><i class="fa fa-link"></i></a>';
			}	
			
			$status = ($value['active'] == 1) ? '<span class="label label-success">'.$this->lang->line('application_active').'</span>' : '<span class="label label-warning">'.$this->lang->line('application_inactive').'</span>';
			$catlinks = $this->model_categorias_marketplaces->getDataByCategoryId($value['id']);
			$marketplaces = '';
			foreach($catlinks as $catlink) {
				$marketplaces.='<span class="label label-success">'.$catlink['int_to'].'('.$catlink['category_marketplace_id'].')</span>';
			}

            $result['data'][$key] = array(
				$value['name'],
				$status,
				$value['produto'],
				$marketplaces,
                $value['qty_products'],
				$buttons
			);
		} // /foreach

        ob_clean();
        echo json_encode($result);
	}

	
	/*
	* Its checks the category form validation 
	* and if the validation is successfully then it inserts the data into the database 
	* and returns the json format operation messages
	*/
	public function create()
	{
        ob_start();
		if(!in_array('createCategory', $this->permission)) {
			redirect('dashboard', 'refresh');
		}
		
		/* NAO MAIS USADO> APAGAR DEPOIS DE MARCO DE 2021 e apagar nas views também */
		redirect('dashboard', 'refresh');
		
		$response = array();

		$this->form_validation->set_rules('category_name', $this->lang->line('application_name'), 'trim|required');
		$this->form_validation->set_rules('active', $this->lang->line('application_active'), 'trim|required');
		//rick
		$this->form_validation->set_rules('tipo_volume', $this->lang->line('application_volume_type'), 'trim|required');
		$this->form_validation->set_error_delimiters('<p class="text-danger">','</p>');
		$this->form_validation->set_rules('edit_cross_docking', $this->lang->line('application_cross_docking_in_days'), 'trim|numeric');
		$this->form_validation->set_rules('days_invoice_limit', $this->lang->line('application_limit_invoice_days'), 'trim|numeric');
				
		
		$category_name = $this->postClean('category_name',TRUE);
		
		$category_name = str_replace('&gt;', '>', $category_name);

        if ($this->form_validation->run() == TRUE) {
        	$data = array(

        		'name' => $category_name,

        		'active' => $this->postClean('active',TRUE),
        		'tipo_volume_id' => $this->postClean('tipo_volume',TRUE),
                'days_cross_docking' => $this->postClean('cross_docking',TRUE) == "" ? null : $this->postClean('cross_docking',TRUE),
        		'days_invoice_limit' => $this->postClean('days_invoice_limit',TRUE) == "" ? null : $this->postClean('days_invoice_limit',TRUE),
				'blocked_cross_docking' => $this->postClean('blocked_cross_docking',TRUE) == "" ? 0 : $this->postClean('blocked_cross_docking',TRUE),
		
			);

        	$create = $this->model_category->create($data);
        	if($create == true) {
        		$response['success'] = true;
        		$response['messages'] = $this->lang->line('messages_successfully_created');
				// SW - Log Update
				get_instance()->log_data('Category','create',json_encode($data),"I");
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
	* Its checks the category form validation 
	* and if the validation is successfully then it updates the data into the database 
	* and returns the json format operation messages
	*/
	public function update($id)
	{
        ob_start();
		if(!in_array('updateCategory', $this->permission)) {
			redirect('dashboard', 'refresh');
		}
		
		/* NAO MAIS USADO> APAGAR DEPOIS DE MARCO DE 2021 e apagar nas views Também */
		redirect('dashboard', 'refresh');
		
		$response = array();
		
		if($id) {
			$this->form_validation->set_rules('edit_category_name', $this->lang->line('application_category_name'), 'trim|required');
			$this->form_validation->set_rules('edit_active', $this->lang->line('application_active'), 'trim|required');
			//rick
			$this->form_validation->set_rules('edit_tipo_volume', $this->lang->line('application_volume_type'), 'trim|required');
			$this->form_validation->set_rules('edit_cross_docking', $this->lang->line('application_cross_docking_in_days'), 'trim|numeric');
			$this->form_validation->set_rules('days_invoice_limit', $this->lang->line('application_limit_invoice_days'), 'trim|numeric');
			
			$this->form_validation->set_error_delimiters('<p class="text-danger">','</p>');

			 
			$edit_category_name = $this->postClean('edit_category_name',TRUE);
			$edit_category_name = str_replace('&gt;', '>', $edit_category_name);


	        if ($this->form_validation->run() == TRUE) {
	        	$data = array(

	        		'name' => $edit_category_name,
	        		'active' => $this->postClean('edit_active',TRUE),	
	        		'tipo_volume_id' => $this->postClean('edit_tipo_volume',TRUE),
                    'days_cross_docking' => $this->postClean('edit_cross_docking',TRUE) == "" ? null : $this->postClean('edit_cross_docking',TRUE),
	        		'days_invoice_limit' => $this->postClean('days_invoice_limit',TRUE) == "" ? null : $this->postClean('days_invoice_limit',TRUE),
			        'blocked_cross_docking' => $this->postClean('blocked_cross_docking',TRUE) ,

				);

	        	$update = $this->model_category->update($data, $id);
	        	if($update == true) {
	        		$data['id'] = $id;
					get_instance()->log_data('Category','edit after',json_encode($data),"I");
					$this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
					die;
					redirect('category', 'refresh');
	        	}
	        	else {
					$this->session->set_flashdata('error', $this->lang->line('messages_error_database_update_brand'));
	        		redirect('category/edit/'.$id, 'refresh');
	        	}
	        }
	        else {
	        	$response['success'] = false;
	        	foreach ($_POST as $key => $value) {
	        		$response['messages'][$key] = form_error($key);
	        	}
				$this->render_template('category/edit/'.$id, $this->data);
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
	* It removes the category information from the database 
	* and returns the json format operation messages
	*/
	public function remove()
	{
        ob_start();
		if(!in_array('deleteCategory', $this->permission)) {
			redirect('dashboard', 'refresh');
		}
		
		$category_id = $this->postClean('category_id',TRUE);

		$response = array();
		if($category_id) {
			$delete = $this->model_category->remove($category_id);
			if($delete == true) {
				$response['success'] = true;
				$response['messages'] = $this->lang->line('messages_successfully_removed');
				// SW - Log Remove
				get_instance()->log_data('Category','remove',$category_id,"I");
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


    /**
     * Aplicaçao de Estilo de Código e engessamento de marketplaces no array
     * por Augusto Braun - Conecta Lá
     * em 22-06-21
     */
	public function link($id)
	{  
        if (!in_array('updateCategory', $this->permission))
            redirect('dashboard', 'refresh');
		
		$list_int_to = $this->model_categorias_marketplaces->getListIntTo();
		
		$marketplaces = array();
		foreach ($list_int_to as $i) {
			array_push($marketplaces, $i['int_to']);
		}
        // $marketplaces = array('ML', 'VIA', 'NM', 'ORT'); 
		$todos_limitado = $this->postClean('todos_limitado',TRUE);

		if ($this->input->server('REQUEST_METHOD') == 'POST')
        {    
        	$error = false;
			$achou = false;

        	foreach ($marketplaces as $int_to)
            {
	        	if ($todos_limitado == 'on')
	        		$catmktid = $this->postClean('link_cat_'.$int_to,TRUE);
				else
					$catmktid = $this->postClean('link_cat_limitado_'.$int_to,TRUE);
				
				if (!is_null($catmktid) && ($catmktid != ''))
                {
					$achou = true;

					$data = array(
						'int_to' => $int_to,
						'category_id' => $id,
						'category_marketplace_id' => $catmktid		
					);

					$replace = $this->model_categorias_marketplaces->replace($data);

					if (!$replace)
						$error = true;
				}
			}

			if ($achou)
            {
				if($error != true)
                {
	        		$this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
	        		redirect('category/', 'refresh');
	        	}
	        	else
                {
	        		$this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
	        		redirect('category/link/'.$id, 'refresh');
	        	}
			}
			else
            {
				redirect('category/', 'refresh');
			}
        	
        }
        else
        {
        	$categoria = $this->model_category->getCategoryData($id);
        	
			$categorias_mkt = array();
			$categorias_mkt_limitado = array();

			foreach ($marketplaces as $int_to) 
            {
				$categorias_mkt[$int_to] = $this->model_categorias_marketplaces->getAllCategoriesByMarketplace($int_to);
				$categorias_mkt_limitado[$int_to] = $this->model_categorias_marketplaces->getLimitedCategoriesByMarketplace($int_to,$categoria['name']);
			}

        	$category_link = $this->model_categorias_marketplaces->getDataByCategoryId($id);

			foreach ($category_link as $catlink)
            {
				$existe = false;

				foreach ($categorias_mkt_limitado[$catlink['int_to']] as $catlimi)
                {
					if ($catlink['category_marketplace_id'] == $catlimi['id'])
                    {
						$existe = true;
						break;
					}
				}

				if (!$existe)
					$categorias_mkt_limitado[$catlink['int_to']][] = $this->model_categorias_marketplaces->getAllCategoriesById($catlink['int_to'], $catlink['category_marketplace_id']);				
			}
			
        	$this->data['category_link'] = $category_link;
			$this->data['category'] = $categoria;
			$this->data['marketplaces'] = $marketplaces;
			$this->data['categorias_mkt'] = $categorias_mkt;
			$this->data['categorias_mkt_limitado'] = $categorias_mkt_limitado;
			$this->render_template('category/link', $this->data);		
        }	

	}
	
	public function getLinkCategory()
	{
        ob_start();
		
		if(!in_array('viewProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $idcat = $this->input->get('idcat');

		$category_link = $this->model_categorias_marketplaces->getDataCompleteByCategoryId($idcat);
		ob_clean();
		echo json_encode($category_link,JSON_UNESCAPED_UNICODE);
	}
	
        
    public function create_new()
    {
        if(!in_array('createCategory', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->data['tipos_volumes'] = $this->model_category->getTiposVolumes();

        $this->form_validation->set_rules('category_name', $this->lang->line('application_name'), 'trim|required');
        $this->form_validation->set_rules('active', $this->lang->line('application_active'), 'trim|required');
        $this->form_validation->set_rules('tipo_volume', $this->lang->line('application_volume_type'), 'trim|required');
        $this->form_validation->set_error_delimiters('<p class="text-danger">','</p>');

		$category_name = $this->postClean('category_name',TRUE);
		$category_name = str_replace('&gt;', '>', $category_name);

        if ($this->form_validation->run() == TRUE) {
            $data = array(
                'name'                  => $category_name,
                'active'                => $this->postClean('active',TRUE),
                'tipo_volume_id'        => $this->postClean('tipo_volume',TRUE),
                'days_cross_docking'    => $this->postClean('cross_docking',TRUE) == "" ? null : $this->postClean('cross_docking',TRUE),
				'days_invoice_limit'    => $this->postClean('days_invoice_limit',TRUE) == "" ? null : $this->postClean('days_invoice_limit',TRUE),
				'blocked_cross_docking' => $this->postClean('blocked_cross_docking',TRUE) == "" ? 0 : $this->postClean('blocked_cross_docking',TRUE),
			);

            $create = $this->model_category->create($data);
            if($create == true) {
                $this->log_data('Category','create',json_encode($data));
                $this->session->set_flashdata('success', 'create_success');
                redirect('category/', 'refresh');
            } else {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                redirect('category/create_new', 'refresh');
            }
        }
            
        $this->render_template('category/create_new', $this->data);
    }
        
	public function edit($id)
    {
		if (!in_array('updateCategory', $this->permission)) {
			redirect('dashboard', 'refresh');
		}
                    
        $this->form_validation->set_rules('edit_category_name', $this->lang->line('application_category_name'), 'trim|required');
        $this->form_validation->set_rules('edit_active', $this->lang->line('application_active'), 'trim|required');
		$this->form_validation->set_rules('edit_tipo_volume', $this->lang->line('application_volume_type'), 'trim|required');
		$this->form_validation->set_rules('edit_cross_docking', $this->lang->line('application_cross_docking_in_days'), 'trim|numeric');
		$this->form_validation->set_rules('days_invoice_limit', $this->lang->line('application_limit_invoice_days'), 'trim|numeric');
		
		$edit_category_name = $this->postClean('edit_category_name',TRUE);
		$edit_category_name = str_replace('&gt;', '>', $edit_category_name);

        if ($this->form_validation->run() == TRUE) {
            $data = array( 
				'name'                  => $edit_category_name,
                'active'                => $this->postClean('edit_active',TRUE),
                'tipo_volume_id'        => $this->postClean('edit_tipo_volume',TRUE),
                'days_cross_docking'    => $this->postClean('edit_cross_docking',TRUE) == "" ? null : $this->postClean('edit_cross_docking',TRUE),
                'days_invoice_limit'    => $this->postClean('days_invoice_limit',TRUE) == "" ? null : $this->postClean('days_invoice_limit',TRUE),
				'blocked_cross_docking' => $this->postClean('blocked_cross_docking',TRUE) == "" ? 0 : $this->postClean('blocked_cross_docking',TRUE),
                'force_update'          => 1
			);

            $update = $this->model_category->update($data, $id);
            if ($update) {
                $this->session->set_flashdata('success', 'update_success');
                $data['id'] = $id;
                $this->log_data('Category','edit after',json_encode($data));
                redirect('category', 'refresh');
            } else {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                redirect('category/edit/'.$id, 'refresh');
            }
		} else {
			$this->data['category'] = (object) $this->model_category->getCategoryData($id);
		    $this->data['tipos_volumes'] = $this->model_category->getTiposVolumes();
		    $this->render_template('category/edit', $this->data);
		}
	}

    public function changeProductCategory()
    {
        // Somente administrador, pode usar.
        if (!$this->data['only_admin']) {
            redirect('dashboard', 'refresh');
        }

        $this->render_template('category/change_product_category', $this->data);
    }

    public function onlyVerify()
    {
        // Somente administrador, pode usar.
        if (!$this->data['only_admin']) {
            redirect('dashboard', 'refresh');
        }

        $dirPath     = "assets/files/change_product_category/";
        $dirPathTemp = "assets/files/change_product_category/temp/";

        // Se não existir o diretório temporário, será criado.
        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0775);
        }
        if (!is_dir($dirPathTemp)) {
            mkdir($dirPathTemp, 0775);
        }

        $file = uploadFile($dirPathTemp);
        if ($file === false) {
            $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
            redirect('Category/changeProductCategory', 'refresh');
        }

        $count_rows_file = count($this->csv_validation->convertCsvToArray($file));
        $new_file        = $dirPath.$this->getGUID(false).'.csv';

        // Verifica se o arquivo tem mais linhas que o permitido.
        if ($count_rows_file > 50) {
            $this->session->set_flashdata('error', sprintf($this->lang->line('messages_only_x_lines_are_allowed'), 50));
            redirect('Category/changeProductCategory', 'refresh');
        }

        // Faz a cópia do arquivo para outro local e salva o registro no banco de dados.
        if (copy($file, $new_file)){
            $csv_to_verification = array(
                'upload_file'   => $new_file,
                'user_id'       => $this->session->userdata('id'),
                'username'      => $this->session->userdata('username'),
                'user_email'    => $this->session->userdata('email'),
                'usercomp'      => $this->data['usercomp'],
                'allow_delete'  => 0,
                'module'        => 'ChangeProductCategory'
            );

            if ($this->model_csv_to_verifications->create($csv_to_verification)) {
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_created'));
            } else {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred') . ' .' . $this->lang->line('messages_reload_page_try_again'));
            }
        } else {
            $this->session->set_flashdata('error', $this->lang->line('messages_save_media_error') . ' .' . $this->lang->line('messages_reload_page_try_again'));
        }

        redirect('Category/changeProductCategory', 'refresh');
    }
}