<?php
/*
 
Controller de Catalogos de Produtos 

*/  
defined('BASEPATH') OR exit('No direct script access allowed');

$price_min = '';
$price_max = '';

/**
 * @property Model_products $model_products
 * @property Model_brands $model_brands
 * @property Model_category $model_category
 * @property Model_attributes $model_attributes
 * @property Model_atributos_categorias_marketplaces $model_atributos_categorias_marketplaces
 * @property Model_categorias_marketplaces $model_categorias_marketplaces
 * @property Model_settings $model_settings
 * @property Model_stores $model_stores
 * @property Model_catalogs $model_catalogs
 * @property Model_integrations $model_integrations
 * @property Model_products_catalog_associated $model_products_catalog_associated
 * @property Model_catalogs_associated $model_catalogs_associated
 */

class Catalogs extends Admin_Controller 
{
    public $allowable_tags = null;
	
	public function __construct()
	{
		parent::__construct();

		$this->not_logged_in();

		$this->data['page_title'] = $this->lang->line('application_catalogs');

		$this->load->model('model_products');
        $this->load->model('model_brands');
        $this->load->model('model_category');
        $this->load->model('model_attributes');
        $this->load->model('model_atributos_categorias_marketplaces');
		$this->load->model('model_categorias_marketplaces');
		$this->load->model('model_settings');
		$this->load->model('model_stores');
		$this->load->model('model_catalogs');
        $this->load->model('model_integrations');
        $this->load->model('model_products_catalog_associated');
        $this->load->model('model_catalogs_associated');

        if ($allowableTags = $this->model_settings->getValueIfAtiveByName('catalogs_allowable_tags')) {
            if (!empty($allowableTags)) {
                $this->allowable_tags = '<' . implode('><', explode(',', $allowableTags)) . '>';
            }
        }
	}

	/* 
	* It only redirects to the manage category page
	*/
	
	public function create()
	{
		if(!in_array('createCatalog', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
		$this->form_validation->set_rules('name', $this->lang->line('application_name'), 'trim|required');
        $this->form_validation->set_rules('description', $this->lang->line('application_description'), 'trim|required');
		$this->form_validation->set_rules('price_min', $this->lang->line('application_price_min'),'trim');
		$this->form_validation->set_rules('price_max', $this->lang->line('application_price_max'),'trim');
        $this->form_validation->set_rules('marketplaces', $this->lang->line('application_marketplaces'),'trim|required|is_unique[catalogs.int_to]');

		if (strlen($this->postClean('price_min',TRUE)) <= 6) {
			$price_min = (float) str_replace(',', '.', $this->postClean('price_min',TRUE));
		}else{
			$price_min = (float) str_replace(',','.',str_replace('.','',$this->postClean('price_min',TRUE)));
		}

		if (strlen($this->postClean('price_max',TRUE)) <= 6) {
			$price_max = (float) str_replace(',', '.', $this->postClean('price_max',TRUE));
		}else{
			$price_max = (float) str_replace(',','.',str_replace('.','',$this->postClean('price_max',TRUE)));
		}

        if (!empty($this->postClean('associate_skus_between_catalogs')) && empty($this->postClean('fields_to_link_catalogs'))) {
            $this->session->set_flashdata(['fields_to_link_catalogs' => '&nbsp;']);
            $this->session->set_flashdata('error', sprintf($this->lang->line('messages_field_required_when_informed_another_field'), $this->lang->line('application_fields_for_linking_catalogs'), $this->lang->line('application_associate_skus_between_catalogs')));
            redirect('catalogs/create', 'refresh');
        }

        if (empty($this->postClean('associate_skus_between_catalogs')) && !empty($this->postClean('fields_to_link_catalogs'))) {
            $this->session->set_flashdata(['associate_skus_between_catalogs' => '&nbsp;']);
            $this->session->set_flashdata('error', sprintf($this->lang->line('messages_field_required_when_informed_another_field'), $this->lang->line('application_associate_skus_between_catalogs'), $this->lang->line('application_fields_for_linking_catalogs')));
            redirect('catalogs/create', 'refresh');
        }

		if(!empty($price_min) && !empty($price_max)){
			if($price_min >= $price_max){
				$this->session->set_flashdata(['valid_price_min' => '&nbsp;', 'valid_price_max' => '&nbsp;']);
				$this->session->set_flashdata('error', $this->lang->line('application_price_min_msg'));
				redirect('catalogs/create', 'refresh');
			}
		}

        if(empty($price_min)){
            $price_min = null;
        }
        if(empty($price_max)) {
            $price_max = null;
        }

		if ($this->form_validation->run() == TRUE) {

            $data = array(
		        'name' => $this->postClean('name',TRUE),
		        'status' => $this->postClean('status',TRUE),
		        'price_min' =>  $price_min,
		        'price_max' =>  $price_max,
		        'description' => strip_tags_products($this->postClean('description',TRUE, false, false), $this->allowable_tags),
                'fields_to_link_catalogs' => $this->postClean('fields_to_link_catalogs') ? implode(',', $this->postClean('fields_to_link_catalogs')) : null,
                'int_to' => $this->postClean('marketplaces') ? $this->postClean('marketplaces') : null,
                'inactive_products_with_inactive_brands' => (bool)$this->postClean('inactive_products_with_inactive_brands'),
                'integrate_products_that_exist_in_other_catalogs' => (bool)$this->postClean('integrate_products_that_exist_in_other_catalogs'),
			);

            $this->db->trans_begin();
			$create = $this->model_catalogs->create($data, $this->postClean('stores',TRUE));
            if (!empty($this->postClean('associate_skus_between_catalogs'))) {
                foreach ($this->postClean('associate_skus_between_catalogs') as $catalog) {

                    $catalog_data = $this->model_catalogs->getCatalogData($catalog);
                    if (!$catalog_data) {
                        continue;
                    }

                    $fields_to_link_catalogs = $catalog_data['fields_to_link_catalogs'];

                    $associate_skus_between_catalogs = explode(',', $catalog_data['associate_skus_between_catalogs']);

                    foreach ($associate_skus_between_catalogs as $associate_skus_between_catalog) {
                        $this->model_catalogs_associated->create(array(
                            'catalog_id_from' => $create,
                            'catalog_id_to' => $associate_skus_between_catalog
                        ));
                    }

                    $data = array(
                        'fields_to_link_catalogs' => implode(',', $this->postClean('fields_to_link_catalogs'))
                    );

                    if ($fields_to_link_catalogs != $data['fields_to_link_catalogs']) {
                        $this->db->trans_rollback();
                        $this->session->set_flashdata(['fields_to_link_catalogs' => '&nbsp;']);
                        $this->session->set_flashdata('error', "Os campos para vincular catálogos são diferentes dos configurados nos catálogos selecionados");
                        redirect('catalogs/create', 'refresh');
                    }
                    $this->model_catalogs->updateById($data, $catalog);
                }
            }

            $this->db->trans_commit();
			if($create) {
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_created'));
                redirect('catalogs/', 'refresh');
            }
            else {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                redirect('catalogs/create', 'refresh');
            }
		}
		else {
            $this->data['marketplaces'] = $this->model_integrations->getIntegrationsbyStoreId(0);
            $this->data['catalogs'] = $this->model_catalogs->getActiveCatalogs();
            $this->data['stores'] = $this->model_stores->getActiveStore();
            $this->render_template('catalogs/create', $this->data);
        }

	}

	public function update($id = null) 
	{
		if(!in_array('updateCatalog', $this->permission)) {
        	redirect('dashboard', 'refresh');
        }
		
		if(!$id) {
            redirect('dashboard', 'refresh');
        }
		$catalog = $this->model_catalogs->getCatalogData($id);
//        dump($catalog['price_min'],$catalog['price_max']);
		$catalog['price_min'] = ($catalog['price_min'] != '' ? number_format($catalog['price_min'],2,",",".") : '');
		$catalog['price_max'] = ($catalog['price_max'] != '' ? number_format($catalog['price_max'],2,",",".") : '');
		if(!$catalog) {
			redirect('dashboard', 'refresh');
		}
		$catalog_stores = $this->model_catalogs->getCatalogsStoresDataByCatalogId($id);
		$newCatalogsStores=$this->postClean('stores',TRUE);
		$this->form_validation->set_rules('name', $this->lang->line('application_name'), 'trim|required');
        $this->form_validation->set_rules('description', $this->lang->line('application_description'), 'trim|required');
		$this->form_validation->set_rules('price_min', $this->lang->line('application_price_min'),'trim');
		$this->form_validation->set_rules('price_max', $this->lang->line('application_price_max'),'trim');
		$this->form_validation->set_rules('marketplaces', $this->lang->line('application_marketplaces'),'trim|required|edit_unique[catalogs.int_to.'.$id.']');

		if (strlen($this->postClean('price_min',TRUE)) <= 6) { 
			$price_min = (float) str_replace(',', '.', $this->postClean('price_min',TRUE));
		}else{
			$price_min = (float) str_replace(',','.',str_replace('.','',$this->postClean('price_min',TRUE)));
		}

		if (strlen($this->postClean('price_max',TRUE)) <= 6) {
			$price_max = (float) str_replace(',', '.', $this->postClean('price_max',TRUE));
		}else{
			$price_max = (float) str_replace(',','.',str_replace('.','',$this->postClean('price_max',TRUE)));
		}

		if(!empty($price_min) && !empty($price_max)){
			if($price_min >= $price_max){
				$this->session->set_flashdata(['valid_price_min' => '&nbsp;', 'valid_price_max' => '&nbsp;']);
				$this->session->set_flashdata('error', $this->lang->line('application_price_min_msg'));
				redirect('catalogs/update/'.$id, 'refresh');
			}
		}

        if(empty($price_min)){
            $price_min = null;
        }
        if(empty($price_max)) {
            $price_max = null;
        }

        if (!empty($this->postClean('associate_skus_between_catalogs')) && empty($this->postClean('fields_to_link_catalogs'))) {
            $this->session->set_flashdata(['fields_to_link_catalogs' => '&nbsp;']);
            $this->session->set_flashdata('error', sprintf($this->lang->line('messages_field_required_when_informed_another_field'), $this->lang->line('application_fields_for_linking_catalogs'), $this->lang->line('application_associate_skus_between_catalogs')));
            redirect('catalogs/update/'.$id, 'refresh');
        }

        if (empty($this->postClean('associate_skus_between_catalogs')) && !empty($this->postClean('fields_to_link_catalogs'))) {
            $this->session->set_flashdata(['associate_skus_between_catalogs' => '&nbsp;']);
            $this->session->set_flashdata('error', sprintf($this->lang->line('messages_field_required_when_informed_another_field'), $this->lang->line('application_associate_skus_between_catalogs'), $this->lang->line('application_fields_for_linking_catalogs')));
            redirect('catalogs/update/'.$id, 'refresh');
        }

        $this->data['catalogs'] = $this->model_catalogs->getActiveCatalogs();

		if ($this->form_validation->run() == TRUE) {
            $associate_skus_between_catalogs_db = $this->model_catalogs_associated->getCatalogIdToByCatalogFrom($id);
            if (!empty($associate_skus_between_catalogs_db)) {
                $imp_associate_skus_between_catalogs = $this->postClean('associate_skus_between_catalogs') ?? [];
                foreach ($associate_skus_between_catalogs_db as $exp_associate_skus_between_catalog) {
                    if (!in_array($exp_associate_skus_between_catalog, $imp_associate_skus_between_catalogs)) {
                        if ($this->model_products_catalog_associated->checkIfCatalogExistProduct($exp_associate_skus_between_catalog)) {
                            $this->session->set_flashdata('error', "Existem catálogos que foram removidos da seleção, mas já existem produtos criados para vender nele.");
                            redirect('catalogs/update/' . $id, 'refresh');
                        }
                    }
                }
            }

			$stores_tmp = array();
			foreach($catalog_stores as $catStor){
				$stores_tmp[] = $catStor['store_id'];
			}
			$catalogs=$this->model_catalogs->getActiveCatalogsStoresDataByCatalogId($id);
			foreach($catalogs as $catal){
				if(!in_array($catal['store_id'],$newCatalogsStores)){
					$caralogsProductsCatalogs=$this->model_catalogs->getCaralogsProductsCatalogsByCatalogID($id);
					foreach($caralogsProductsCatalogs as $catalogProductCatalog){
						$product=$this->model_products->getProductByProductCatalogIdAndStore($catalogProductCatalog['product_catalog_id'],$catal['store_id']);
						if(!empty($product)){
							$data=array('status'=>2);
							$this->model_products->update($data,$product['id']);
							$this->log_data('batch', __FUNCTION__, "Inativação do produto :".$product['id']." Por inativação do logista ".$catal['store_id']." no catalogo ".$id.".", "E");
						}
					}
				}
			}

            $specification_id = null;
            $specification_value = null;
            $identifying_technical_specification = $this->postClean('identifying_technical_specification',TRUE);
            if ($identifying_technical_specification) {
                $identifying_technical_specification = explode(":", $identifying_technical_specification);

                $specification_id = $identifying_technical_specification[0];
                $specification_value = $identifying_technical_specification[1];
            }

			$catalog['stores'] = $stores_tmp; 
			$this->log_data('Catalogs','edit_before',json_encode($catalog),"I'");
            $data = array(
		        'name' => $this->postClean('name',TRUE),
		        'status' => $this->postClean('status',TRUE),
		        'price_min' => $price_min,
		        'price_max' => $price_max,
		        'description' => strip_tags_products($this->postClean('description',TRUE, false, false), $this->allowable_tags),
                'attribute_id' => $specification_id,
                'attribute_value' => $specification_value,
                'fields_to_link_catalogs' => $this->postClean('fields_to_link_catalogs') ? implode(',', $this->postClean('fields_to_link_catalogs')) : null,
                'int_to' => $this->postClean('marketplaces') ? $this->postClean('marketplaces') : null,
                'inactive_products_with_inactive_brands' => (bool)$this->postClean('inactive_products_with_inactive_brands'),
                'integrate_products_that_exist_in_other_catalogs' => (bool)$this->postClean('integrate_products_that_exist_in_other_catalogs'),
			);
			$update = $this->model_catalogs->update($data, $id,  $this->postClean('stores',TRUE));
            $this->model_catalogs_associated->removeByCatalogFrom($id);
            $associate_skus_between_catalogs = array();
            if (!empty($this->postClean('associate_skus_between_catalogs'))) {
                foreach ($this->postClean('associate_skus_between_catalogs') as $catalog) {

                    $associate_skus_between_catalogs[] = $catalog;

                    $data = array(
                        'fields_to_link_catalogs' => implode(',', $this->postClean('fields_to_link_catalogs'))
                    );
                    $this->model_catalogs->updateById($data, $catalog);
                }

                foreach ($associate_skus_between_catalogs as $associate_skus_between_catalog) {
                    $this->model_catalogs_associated->create(array(
                        'catalog_id_from' => $id,
                        'catalog_id_to' => $associate_skus_between_catalog
                    ));

                    if (!in_array($id, $this->model_catalogs_associated->getCatalogIdToByCatalogFrom($associate_skus_between_catalog))) {
                        $this->model_catalogs_associated->create(array(
                            'catalog_id_from' => $associate_skus_between_catalog,
                            'catalog_id_to' => $id
                        ));
                    }
                }
            }
			if($update) {
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
                redirect('catalogs/', 'refresh');
            }
            else {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                redirect('catalogs/update/'.$id, 'refresh');
            }
		}
		else {

            $attributesSelect = [];

            $identifyingTechnicalSpecification = $this->model_settings->getValueIfAtiveByName('identifying_technical_specification');
            if($identifyingTechnicalSpecification) {
                $catalog_name = $catalog['name'];
                $integrations = $this->model_integrations->getIntegrationsByCatalogName($catalog_name);
                if ($integrations) {
                    $int_to = $integrations->int_to;
                    $attributes = $this->model_atributos_categorias_marketplaces->getAttributesByIntToAndName($int_to, $identifyingTechnicalSpecification);
                    foreach($attributes as $attribute){
                        $values = json_decode($attribute->valor);
                        foreach ($values as $value){
                            if($value->IsActive){
                                $attributesSelect[] = [
                                    'fieldValueId' => $value->FieldValueId,
                                    'value' => $value->Value,
                                    'position' => $value->Position
                                ];
                            }
                        }
                    }
                }
            }

            $this->data['attributesSelect'] = $attributesSelect;
			$this->data['catalog'] = $catalog;
			$this->data['catalogs_stores'] = $catalog_stores;
            $this->data['stores'] = $this->model_stores->getActiveStore();
            $this->data['marketplaces'] = $this->model_integrations->getIntegrationsbyStoreId(0);
            $this->data['catalogs_associated'] = $this->model_catalogs_associated->getCatalogIdToByCatalogFrom($id);
            $this->render_template('catalogs/edit', $this->data);
        }

	}
	
	public function index()
	{

		if(!in_array('viewCatalog', $this->permission)) {
			redirect('dashboard', 'refresh');
		}
		$this->data['stores_filter'] = $this->model_catalogs->getStoresOnCatalogs(); 
		$this->render_template('catalogs/index', $this->data);	
	}	
	
	public function fetchCatalogsData()
	{
        ob_start();
		if(!in_array('viewCatalog', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
		$result = array('data' => array());
		
		$postdata = $this->postClean(NULL,TRUE);
		$ini = $postdata['start'];
		$draw = $postdata['draw'];
		$busca = $postdata['search']; 
		$length = $postdata['length'];

		$procura = '';
        if ($busca['value']) {
            if (strlen($busca['value'])>=2) {  // Garantir no minimo 3 letras
                $procura= " AND ( name like '%".$busca['value']."%' OR  description like '%".$busca['value']."%') ";
            }
        } else {
            if (is_array($postdata['lojas'])) {
                $lojas = $postdata['lojas'];
                $procura .= " AND id IN (SELECT catalog_id FROM catalogs_stores WHERE catalog_id = catalogs.id AND (";
                foreach($lojas as $loja) {
                    $procura .= "store_id = ".(int)$loja." OR ";
                }
                $procura = substr($procura, 0, (strlen($procura)-3));
                $procura .= ")) ";
            }
            
            if (trim($postdata['name'])) {
            	$procura .= " AND name like '%".$postdata['name']."%'";
            }
            if (trim($postdata['status'])) {
            	$procura .= " AND status = ".$postdata['status'];
            }
			if (trim($postdata['description'])) {
            	$procura .= " AND description like '%".$postdata['description']."%'";
            }
        }
				
		if (isset($postdata['order'])) {
			if ($postdata['order'][0]['dir'] == "asc") {
				$direcao = "ASC";
			} else { 
				$direcao = "DESC";
		    }
			$campos = array('id','name','description','status');
			$campo =  $campos[$postdata['order'][0]['column']];
			if ($campo != "") {
				if ($campo == 'id') {
					if ($direcao =="ASC") {$direcao ="DESC";}
					else {$direcao ="ASC";}
				}
				$sOrder = " ORDER BY ".$campo." ".$direcao;
		    }
		}

		$data = $this->model_catalogs->getCatalogsDataView($ini, $procura, $sOrder, $length );
		$filtered = $this->model_catalogs->getCatalogsDataCount($procura);
		if ($procura == '') {
			$total_rec = $filtered;
		}
		else {
			$total_rec = $this->model_catalogs->getCatalogsDataCount();
		}

		$result = array();
		foreach ($data as $key => $value) {
			// button
			$status  = '';
			switch ($value['status']) {
                case 1: 
                    $status =  '<span class="label label-success">'.$this->lang->line('application_active').'</span>';
                    break;
                default:
                    $status = '<span class="label label-danger">'.$this->lang->line('application_inactive').'</span>';
                    break;
			}
			
			$link_id = "<a href='".base_url('catalogs/update/'.$value['id'])."'>".$value['id']."</a>";
			$result[$key] = array(
				$link_id,
				$value['name'],
				$value['description'],
				($value['price_min'] != '' ? number_format($value['price_min'],2,",",".") : ''),
				($value['price_max'] != '' ? number_format($value['price_max'],2,",",".") : ''),
				$status,
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

}