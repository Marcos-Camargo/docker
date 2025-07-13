<?php
/*
 SW Serviços de Informática 2019
 
 Controller de Marcas/Fabricantes
 
 */
defined('BASEPATH') || exit('No direct script access allowed');

class Brands extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
        
        $this->not_logged_in();
        $this->data['page_title'] = $this->lang->line('application_brands');
        
        $this->load->model('model_brands');
        $this->load->model('model_integrations');
        $this->load->model('model_brands_marketplaces');
		
		$this->config->set_item('csrf_protection', false);
    }
    
    /*
     * It only redirects to the manage product page and
     */
    public function index()
    {
        if(!in_array('viewBrand', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        
        $result = $this->model_brands->getBrandData();
        
        $this->data['results'] = $result;
        
		$this->config->set_item('csrf_protection', false);
        $this->render_template('brands/index', $this->data);
    }
    
    /*
     * Fetches the brand data from the brand table
     * this function is called from the datatable ajax function
     */
    public function fetchBrandData()
    {
        ob_start();
		$postdata = $this->postClean(NULL, TRUE);
		$ini = $postdata['start'];
		$draw = $postdata['draw'];
		$busca = $postdata['search']; 
		$length = $postdata['length'];
		
		$procura = '';
        if (($busca['value']) && (strlen($busca['value'])>=1)) {  // Garantir no minimo 3 letras
            $procura= " WHERE ( name like '%".$busca['value']."%' OR id like '%".$busca['value']."%') ";
        } 

		if (isset($postdata['order'])) {
			if ($postdata['order'][0]['dir'] == "asc") {
				$direcao = "ASC";
			} else { 
				$direcao = "DESC";
		    }

			$campos = array('id','name','active','');

			$campo =  $campos[$postdata['order'][0]['column']];
			if ($campo != "") {
				$sOrder = " ORDER BY ".$campo." ".$direcao;
		    }
		}
        
        $data = $this->model_brands->getBrandsDataView($ini, $procura, $sOrder, $length );
		$filtered = $this->model_brands->getBrandsDataCount($procura);
		if ($procura == '') {
			$total_rec = $filtered;
		}
		else {
			$total_rec = $this->model_brands->getBrandsDataCount();
		}
		$result = array();
        foreach ($data as $key => $value) {
            
            // button
            $buttons = '';
            
            if(in_array('viewBrand', $this->permission)) {
                $buttons .= '<button type="button" class="btn btn-default" onclick="editBrand('.$value['id'].')" data-toggle="modal" data-target="#editBrandModal"><i class="fa fa-pencil"></i></button>';
            }
            
            if(in_array('deleteBrand', $this->permission)) {
                $buttons .= ' <button type="button" class="btn btn-default" onclick="removeBrand('.$value['id'].',\''.$value['name'].'\')" data-toggle="modal" data-target="#removeBrandModal"><i class="fa fa-trash"></i></button>
				';
            }
            if(in_array('linkBrandsMarketplaces', $this->permission)) {
                $buttons .= '<a href="'.base_url('brands/marketplacelink/'.$value['id']).'" class="btn btn-default"><i class="fa fa-link" aria-hidden="true"></i></a>';
            }
            
            $status = ($value['active'] == 1) ? '<span class="label label-success">'.$this->lang->line('application_active').'</span>' : '<span class="label label-warning">'.$this->lang->line('application_inactive').'</span>';
            
           $result[$key] = array(
                $value['id'],
                $value['name'],
                $status,
                $buttons
            );
        } // /foreach
        $output = array(
			"draw" => $draw,
		    "recordsTotal" => $total_rec,
		    "recordsFiltered" => $filtered,
		    "data" => $result
		);
		ob_clean();
		echo json_encode($output);
    }
    /*
    */
    public function marketplacelink($id, $int_to = null)
    {

        if (!in_array('linkBrandsMarketplaces', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        if ($int_to == null) {
            if (isset($_GET['created']) && $_GET['created']) {
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_created'));
            }

            $this->data['brand'] = $this->model_brands->getBrandData($id);
            $this->data['integrations'] = $this->model_integrations->get_integrations_list();
            $this->render_template('brands/create', $this->data);
        } else {
            $this->data['brand'] = $this->model_brands->getBrandData($id);
            $this->data['integrations'] = $this->model_integrations->get_integrations_list();
            $this->data['brand_marketplace'] = $this->model_brands_marketplaces->getBrandMktplace($int_to, $id);
            $this->render_template('brands/edit', $this->data);
        }
    }
    /**
     * Registration of brand_marketplace, if the integration and the brand already exist, it is overwritten.
     */
    public function brands_marketplaces()
    {
        if (!in_array('linkBrandsMarketplaces', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $data = $this->postClean(NULL, TRUE);
        unset($data['ci_csrf_token']);
        $data['id_marketplace'] = '';
        $brand = $this->model_brands_marketplaces->findByBrandIdAndIntTo($data['brand_id'], $data['int_to']);
        
        if ($brand) {
            $this->session->set_flashdata('error', $this->lang->line('messages_brand_marketplace_exist'));
        } else {
            $this->model_brands_marketplaces->create($data);
            
            $id = $data['brand_id'];
            $this->data['brand'] = $this->model_brands->getBrandData($id);
            $this->data['integrations'] = $this->model_integrations->get_integrations_list();
            redirect('brands/marketplacelink/' . $id . '?created=true', 'refresh');
        }

        $id = $data['brand_id'];
        $this->data['brand'] = $this->model_brands->getBrandData($id);
        $this->data['integrations'] = $this->model_integrations->get_integrations_list();
        redirect('brands/marketplacelink/' . $id, 'refresh');
    }
    
    /*
     * It checks if it gets the brand id and retreives
     * the brand information from the brand model and
     * returns the data into json format.
     * This function is invoked from the view page.
     */
    public function fetchBrandDataById($id)
    {
        if($id) {
            ob_start();
            $data = $this->model_brands->getBrandData($id);
            ob_clean();
            echo json_encode($data);
        }
        
        return false;
    }

    public function marketplacelink_delete($id, $int_to)
    {
        if (!in_array('linkBrandsMarketplaces', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $this->data['brand'] = $this->model_brands->getBrandData($id);
        $this->data['integrations'] = $this->model_integrations->get_integrations_list();
        $this->model_brands_marketplaces->remove($int_to,$id);
        redirect('brands/marketplacelink/'.$id, 'refresh');
    }

    public function fetchBrandsLinkData($id)
    {
        ob_start();
        $postdata = $this->postClean(NULL, TRUE);
		$draw = $postdata['draw'];

        $result = array();
        $brand = $this->model_brands->getBrandData($id);
        $brands_marketplaces = $this->model_brands_marketplaces->getDataByBrandId($id);
        $buttons = '';
        foreach ($brands_marketplaces as $key => $brand_marketplace) {
            $marketplaces = '<span class="label label-success">' . $brand_marketplace['int_to'] . '</span>';          
            $buttons = ' <a href="' . base_url('brands/marketplacelink/' . $id . '/' . $brand_marketplace['int_to']) . '" class="btn btn-default" data-toggle="tooltip" data-placement="top" title="link mktplace" ><i class="fa fa-pencil"></i></a>';
            $result[$key] = array(
                $brand['name'],
                $brand_marketplace['isActive'] ? $this->lang->line('application_active') : $this->lang->line('application_inactive'),
                $brand_marketplace['title'],
                $brand_marketplace['metaTagDescription'],
                $marketplaces,
                $buttons
            );
        }
        ob_clean();
        $output = array(
            "draw" => $draw,
            "recordsTotal" => count($result),
            "recordsFiltered" => count($result),
            "data" => $result
        );
        echo json_encode($output);
    }

    public function brands_marketplaces_update()
    {
        if (!in_array('linkBrandsMarketplaces', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $data = $this->postClean(NULL, TRUE);
        unset($data['ci_csrf_token']);
        $data['integrated']=0;
        $id=$data['brand_id'];
        $this->model_brands_marketplaces->update($data, $data['int_to'], $data['brand_id']);
        redirect('brands/marketplacelink/'.$id, 'refresh');
    }
    
    /*
     * Its checks the brand form validation
     * and if the validation is successfully then it inserts the data into the database
     * and returns the json format operation messages
     */
    public function create()
    {
        ob_start();
        if(!in_array('createBrand', $this->permission) && (!in_array('createProduct', $this->permission))) {
            redirect('dashboard', 'refresh');
        }

        $response = array();
        
        $this->form_validation->set_rules('brand_name', $this->lang->line('application_brand_name'), 'trim|required');
        $this->form_validation->set_rules('active', $this->lang->line('application_active'), 'trim|required');
        
        $this->form_validation->set_error_delimiters('<p class="text-danger">','</p>');
        
        if ($this->form_validation->run()) {
            
            $exist = $this->model_brands->getBrandbyName($this->postClean('brand_name',TRUE));
            
            if (!$exist) {
                $data = array(
                    'name' => $this->postClean('brand_name',TRUE),
                    'active' => $this->postClean('active',TRUE),
                );
                
                $create = $this->model_brands->create($data);
                if($create) {
                    $response['id'] = $create;
                    $response['brand_name'] = $this->postClean('brand_name',TRUE);
                    $response['success'] = true;
                    $response['messages'] = $this->lang->line('messages_successfully_created');
                    if ($this->postClean('fromproducts',TRUE) != 'fromproducts')  {
                        $this->session->set_flashdata('success', 'create_success');
                        redirect('brands', 'refresh');
                    }  
                }
                else {
                    $response['success'] = false;
                    $response['messages']['brand_name'] = '<p class="text-danger"</p>'.$this->lang->line('messages_error_database_create_brand').'</p>';
                    if ($this->postClean('fromproducts',TRUE) != 'fromproducts')  {
                        $this->session->set_flashdata('error', $this->lang->line('messages_error_database_create_brand'));
                        redirect('brands', 'refresh');
                    }  
                }
                
            } else {
                $response['success'] = false;
                $response['messages']['brand_name'] = '<p class="text-danger">'.$this->lang->line('application_brand_already_exists').'</p>';
                
                if ($this->postClean('fromproducts',TRUE) != 'fromproducts')  {
                    $this->session->set_flashdata('error', $this->lang->line('application_brand_already_exists'));
                    redirect('brands', 'refresh');
                }  
                
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
     * Its checks the brand form validation
     * and if the validation is successfully then it updates the data into the database
     * and returns the json format operation messages
     */
    public function update($id)
    {
        ob_start();
        if(!in_array('updateBrand', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        
        $response = array();
        
        if($id) {
            $this->form_validation->set_rules('edit_brand_name', $this->lang->line('application_brand_name'), 'trim|required');
            $this->form_validation->set_rules('edit_active', $this->lang->line('application_brand'), 'trim|required');
            
            $this->form_validation->set_error_delimiters('<p class="text-danger">','</p>');
            
            if ($this->form_validation->run()) {
                $data = array(
                    'name' => $this->postClean('edit_brand_name',TRUE),
                    'active' => $this->postClean('edit_active',TRUE),
                );
                
                $update = $this->model_brands->update($data, $id);
                if($update) {
                    $response['success'] = true;
                    $response['messages'] = $this->lang->line('messages_successfully_updated');
                    $this->session->set_flashdata('success', 'update_success');
                    redirect('brands', 'refresh');
                }
                else {
                    $response['success'] = false;
                    $response['messages'] = $this->lang->line('messages_error_database_update_brand');
                    $this->session->set_flashdata('error', $this->lang->line('messages_error_database_update_brand'));
                    redirect('brands', 'refresh');
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
     * It removes the brand information from the database
     * and returns the json format operation messages
     */
    public function remove()
    {
        ob_start();
        if(!in_array('deleteBrand', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        
        $brand_id = $this->postClean('brand_id',TRUE);
        $response = array();
        if($brand_id) {
            $delete = $this->model_brands->remove($brand_id);
            
            if($delete == 'Success') { // era true rick
                $response['success'] = true;
                $response['messages'] = $this->lang->line('messages_successfully_removed');
            }
            else {
                $response['success'] = false;
                $response['messages'] = $this->lang->line('messages_error_database_create_brand').": ".$delete;
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