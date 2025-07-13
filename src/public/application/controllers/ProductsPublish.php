<?php

require 'system/libraries/Vendor/autoload.php';
require_once APPPATH . "libraries/Publish/Publishing.php";

defined('BASEPATH') OR exit('No direct script access allowed');

use League\Csv\Reader;
use League\Csv\Statement;
use League\Csv\Writer;
use League\Csv\CharsetConverter;
use Publish\Publishing;


/**
 * @property CI_Loader $load
 * @property CI_Lang $lang
 * @property CI_Input $input
 * @property CI_Session $session
 * @property CI_Output $output
 * @property CI_DB_query_builder $db
 *
 * @property Model_products $model_products
 * @property Model_stores $model_stores
 * @property Model_attributes $model_attributes
 * @property Model_reports $model_reports
 * @property Model_integrations $model_integrations
 * @property Model_atributos_categorias_marketplaces $model_atributos_categorias_marketplaces
 * @property Model_promotions $model_promotions
 * @property Model_campaigns $model_campaigns
 * @property Model_categorias_marketplaces $model_categorias_marketplaces
 * @property Model_errors_transformation $model_errors_transformation
 * @property Model_orders $model_orders
 * @property Model_products_marketplace $model_products_marketplace
 * @property Model_blingultenvio $model_blingultenvio
 * @property Model_settings $model_settings
 * @property Model_log_products $model_log_products
 * @property Model_csv_to_verifications $model_csv_to_verifications
 *
 * @property Publishing $publishing
 *
 */
class ProductsPublish extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
        
        $this->not_logged_in();
        
        $this->load->model('model_products');
        $this->load->model('model_stores');
        $this->load->model('model_attributes');
        $this->load->model('model_reports');
        $this->load->model('model_integrations');
        $this->load->model('model_atributos_categorias_marketplaces');
        $this->load->model('model_promotions');
        $this->load->model('model_campaigns');
		$this->load->model('model_categorias_marketplaces');
	    $this->load->model('model_errors_transformation');
		$this->load->model('model_orders');
		$this->load->model('model_products_marketplace');
		$this->load->model('model_blingultenvio');
		$this->load->model('model_settings');
		$this->load->model('model_log_products');
        $this->load->model('model_csv_to_verifications');
	    
    	$this->load->helper('datatables');

        $this->load->library("Publish\\Publishing", array(), 'publishing');
		
        $usercomp = $this->session->userdata('usercomp');
        $this->data['usercomp'] = $usercomp;
        $more = " company_id = ".$usercomp;
        $this->data['mycontroller']=$this;
                
    }
	
	public function index() {
        if(!in_array('doProductsPublish', $this->permission)) {
        	redirect('dashboard', 'refresh');
        }

        $filterCount = '';
        // recupera loja main
        $onlyStorePublishedSetting = $this->model_settings->getSettingDatabyName('publish_only_one_store_company');
        if ($onlyStorePublishedSetting && $onlyStorePublishedSetting['status'] == 1) {

            $storePublished = $onlyStorePublishedSetting['value'];
            $dataStoreMain = $this->model_stores->getStoresData($storePublished);

            $filterCount = " AND (company_id <> {$dataStoreMain['company_id']} OR id = {$storePublished})";
        }

		$nameOfIntegrations = [
			'CAR'  => 'Carrefour',
			'ML'   => 'Mercado Livre Premium',
			'MLC'  => 'Mercado Livre Clássico',
			'VIA'  => 'Via Varejo',
		];

		$activeIntegrations = $this->model_integrations->getIntegrations();

		foreach ($activeIntegrations as $key => $activeIntegration) {
			if (!array_key_exists($activeIntegration['int_to'], $nameOfIntegrations)) {
				$nameOfIntegrations[$activeIntegration['int_to']] = $activeIntegration['int_to'];
			}
		}
		$this->data['nameOfIntegrations'] = $nameOfIntegrations;
		$this->data['activeIntegrations'] = is_array($activeIntegrations) ? $activeIntegrations : array();

        $this->data['stores_filter'] = $this->model_stores->getActiveStoreProductsPublish($filterCount);
        $this->data['page_title'] = $this->lang->line('application_products_publish');
		$this->data['names_marketplaces'] = $this->model_integrations->getMyNamesIntegrations(true);
		if (count($this->data['names_marketplaces']) ==0) {
			$this->session->set_flashdata('error', $this->lang->line('messages_no_integration_with_marketplaces_defined_for_this_store'));
			redirect('dashboard', 'refresh');
		}

        $disable_message = $this->model_settings->getValueIfAtiveByName('disable_publication_of_new_products');
        if ($disable_message) {
            $this->session->set_flashdata('error', utf8_decode($disable_message));
            redirect('dashboard', 'refresh');
        }
		
        $this->render_template('productspublish/index', $this->data);
    }
	
	public function fetchProductsPublish()
    {
        $draw   = $this->postClean('draw');
        $result = array();

        try {
            $status         = $this->postClean('status');
            $stores         = $this->postClean('lojas');
            $status_int     = $this->postClean('status_int');
            $int_to         = $this->postClean('int_to');
            $sku            = trim($this->postClean('sku'));
            $name           = trim($this->postClean('nome'));
            $stock          = (int)trim($this->postClean('estoque'));
            $situation      = (int)trim($this->postClean('situacao'));
            $filters        = array();
            $filter_default = array();

            $deletedStatus = Model_products::DELETED_PRODUCT;
            $filter_default[]['where']['p.status !='] = $deletedStatus;
            $filter_default[]['where']['p.dont_publish !='] = true;

            if (!empty($sku)) {
                $filters[]['like']['p.sku'] = $sku;
            }
            if (!empty($name)) {
                $filters[]['like']['p.name'] = $name;
            }
            if ($stock) {
                $filters[]['where'][$stock == 1 ? 'p.qty >' : 'p.qty <='] = 0;
            }
            if ($situation) {
                $filters[]['where']['p.situacao'] = $situation == 1 ? 1 : 2;
            }
            if ($status) {
                $filters[]['where']['p.status'] = $status;
            }
            if (is_array($stores)) {
                $filters[]['where_in']['s.id'] = $stores;
            }

            $integrations = [];
            $activeIntegrations = $this->model_integrations->getIntegrations();
            foreach ($activeIntegrations as $key => $activeIntegration) {                
                $integrations[] = $activeIntegration['int_to'];                
            }

            if ($int_to && (count($integrations) != count($int_to))) {
                if (!array_filter($int_to) == []){
                    if (is_array($int_to)) {
                        $int_tos = $int_to;
                        $filters[]['group_start'] = '';
                        foreach($int_tos as $int_to) {
                            $filters[]['or_group_start'] = '';

                            if ($status_int == 998) {
                                $filters[]['where']['i.int_to !='] = $int_to;
                            } else {
                                $filters[]['where']['i.int_to'] = $int_to;
                            }
                            if ($status_int && ($status_int != 999 && $status_int != 998 && $status_int != 40)) {
                                $filters[]['where']['i.status_int'] = $status_int;
                            }

                            $filters[]['group_end'] = '';
                        }
                        $filters[]['group_end'] = '';

                        if ($status_int == 998) {
                            $filters[]['where']['i.id !='] = null;  
                        }

                        if ($status_int != 999 && $status_int != 998) {
                            switch ($status_int) {
                                case 30:
                                    $filters[]['where']['et.status'] = 0;
                                    break;
                                case 40:
                                    $filters[]['where']['i.ad_link !='] = null;
                                    break;
                                default:
                                    $filters[]['where']['i.status_int'] = $status_int;
                                    break;
                            }
                        }
                    }
                }
            } else {
                // SE NÃO TEM NENHUM MARKETPLACE SELECIONADO, CONSULTA TODOS OS PRODUTOS QUE NAO ESTAO PUBLICADOS
                if ($status_int != 999) {
                    switch ($status_int) {             
                        case 998:
                            $filters[]['where']['i.id'] = null;
                            break;
                        default:
                            $filters[]['where']['i.status_int'] = $status_int;
                            break;
                    }
                } 
            }

            //$filters[]['where']['s.type_store !='] = 2;  // remove produtos de lojas CD de lojas Multi CD

            $fields_order = array('p.id', 'p.id', 'p.sku', 'p.name', 's.name', 'p.price', 'p.qty', 'p.status', 'p.situacao', 'i.int_to', '');

            $query = array();
            $query['select'][] = "p.*, s.name AS store";
            $query['from'][] = 'products p';
            $query['join'][] = ["stores s", "s.id=p.store_id and s.type_store != 2", 'LEFT'];
            $query['join'][] = ["prd_to_integration i", "i.prd_id = p.id", 'LEFT'];
            $query['join'][] = ["errors_transformation et", "et.prd_id = p.id", 'LEFT'];

            $data = fetchDataTable(
                $query,
                array('p.id', 'DESC'),
                array(
                    'company'   => 'p.company_id',
                    'store'     => 'p.store_id'
                ),
                'p.id',
                ['doProductsPublish'],
                $filters,
                $fields_order,
                $filter_default
            );
        } catch (Exception $exception) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(
                    json_encode(array(
                        "draw"              => $draw,
                        "recordsTotal"      => 0,
                        "recordsFiltered"   => 0,
                        "data"              => $result,
                        "message"           => $exception->getMessage()
                    ))
                );
        }

        foreach ($data['data'] as $key => $value) {
            if ((!is_null($value['principal_image'])) && ($value['principal_image'] != '')) {
                $img = '<img src="' . $value['principal_image'] . '" alt="' . utf8_encode(substr($value['name'], 0, 20)) . '" class="img-rounded" width="50" height="50" />';
            } else {
                $img = '<img src="' . base_url('assets/images/system/sem_foto.png') . '" alt="' . utf8_encode(substr($value['name'], 0, 20)) . '" class="img-rounded" width="50" height="50" />';
            }

            $situacao = '';
			switch ($value['status']) {
                case 1: 
                    $status =  '<span class="label label-success">'.$this->lang->line('application_active').'</span>';
                    break;
                default:
                    $status = '<span class="label label-danger">'.$this->lang->line('application_inactive').'</span>';
                    break;
			}
			switch ($value['situacao']) {
				case 1:
                    $situacao .=  '<span class="label label-danger">'.$this->lang->line('application_incomplete').'</span>';
					 break;
                case 2:
                    $situacao .=  '<span class="label label-success">'.$this->lang->line('application_complete').'</span>';
                     break;
			}
			// Plataforma integrada
			$integrations = $this->model_integrations->getIntegrationsProductAll($value['id'], true);
            if ($integrations) {
                $plataforma = "";
                foreach($integrations as $v) {
					if ($v['status']==0) {
						$btn = "danger"; $tip = mb_strtoupper($this->lang->line('application_inactive'),'UTF-8');
					} elseif ($v['errors'] == 1) {
                       	$btn = "danger"; $tip = mb_strtoupper($this->lang->line('application_errors_tranformation'),'UTF-8');
					} elseif ($v['status_int']==0) {
                       	$btn = "warning"; $tip = mb_strtoupper($this->lang->line('application_product_in_analysis'),'UTF-8');
					} elseif ($v['status_int']==1) {
                       	$btn = "success"; $tip = mb_strtoupper($this->lang->line('application_product_waiting_to_be_sent'),'UTF-8');
					} elseif ($v['status_int']==2) {
                       	$btn = "primary"; $tip = mb_strtoupper($this->lang->line('application_product_sent'),'UTF-8');
					} elseif ($v['status_int']==11) {
                        $over = $this->model_integrations->getPrdBestPrice($value['EAN']);
                        $btn = "danger"; $tip = mb_strtoupper($this->lang->line('application_product_higher_price'),'UTF-8').' ('.$over.')';
				    } elseif ($v['status_int']==12) {
                    	$btn = "danger"; $tip = mb_strtoupper($this->lang->line('application_product_higher_price'),'UTF-8');
					} elseif ($v['status_int']==13) {
                       	$btn = "danger"; $tip = mb_strtoupper($this->lang->line('application_product_higher_price'),'UTF-8');
					} elseif ($v['status_int']==14) {
                        $btn = "danger"; $tip = mb_strtoupper($this->lang->line('application_product_release'),'UTF-8');
					} elseif ($v['status_int']==20) {
                        $btn = "success"; $tip = mb_strtoupper($this->lang->line('application_in_registration'),'UTF-8');
					} elseif ($v['status_int']==21) {
                        $btn = "success"; $tip = mb_strtoupper($this->lang->line('application_in_registration'),'UTF-8');
					} elseif ($v['status_int']==22) {
                        $btn = "success"; $tip = mb_strtoupper($this->lang->line('application_in_registration'),'UTF-8');
					} elseif ($v['status_int']==23) {
                        $btn = "success"; $tip = mb_strtoupper($this->lang->line('application_in_registration'),'UTF-8');
					} elseif ($v['status_int']==24) {
                        $btn = "success"; $tip = mb_strtoupper($this->lang->line('application_in_registration'),'UTF-8');
					} elseif ($v['status_int']==90) {
                       	$btn = "default"; $tip = mb_strtoupper($this->lang->line('application_product_inactive'),'UTF-8');
					} elseif ($v['status_int']==91) {
                        $btn = "default"; $tip = mb_strtoupper($this->lang->line('application_no_logistics'),'UTF-8');
				    } elseif ($v['status_int']==99) {
                       	$btn = "warning"; $tip = mb_strtoupper($this->lang->line('application_product_in_analysis'),'UTF-8');
					} else {
                       	$btn = "danger"; $tip = mb_strtoupper($this->lang->line('application_product_out_of_stock'),'UTF-8');
					}
					$plataforma .= '<span class="label label-'.$btn.'" data-toggle="tooltip" title="'.$tip.'">'.$v['int_to'].'</span>&nbsp;';
                }
				$buttons = '<button onclick="publishProduct(event,\''.$value['id'].'\',\''.$value['sku'].'\',\''.str_replace("'"," ",$value['name']).'\')" class="btn btn-primary" >'.'<i class="fas fa-search-dollar"></i> &nbsp;'.$this->lang->line('application_review_publish').'</button>';
            } else {
                $tip = mb_strtoupper($this->lang->line('application_not_published'),'UTF-8');
				$btn = "default";
            	$buttons = '<button onclick="publishProduct(event,\''.$value['id'].'\',\''.$value['sku'].'\',\''.str_replace("'"," ",$value['name']).'\')" class="btn btn-primary" >'.'<i class="fa-solid fa-boxes-packing"></i> &nbsp;'.$this->lang->line('application_publish').'</button>';
				$plataforma = '<span class="label label-'.$btn.'" >'.$tip.'</span>&nbsp;';
			}
			$linkid = 	'<a target="__blank" href="'.base_url('products/update/'.$value['id']).'" >'.$value['sku'].'</a>';
            $result[$key] = array(
                $value['id'],
                $img,
                $linkid,
                $value['name'],
                $value['store'],
                $this->formatprice($value['price']),
                $value['qty'],
				$status,
                $situacao,
				$plataforma,
				$buttons, 
            );

        }

        $output = array(
            "draw"              => $draw,
            "recordsTotal"      => $data['recordsTotal'],
            "recordsFiltered"   => $data['recordsFiltered'],
            "data"              => $result,
        );

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($output));
    }

	public function getProductIntegrations($product_id=null)
	{
        ob_start();
		if (is_null($product_id)) {
			redirect('dashboard', 'refresh');
		}
		if(!in_array('doProductsPublish', $this->permission)) {
        	redirect('dashboard', 'refresh');
        }
        //$names_marketplaces =  $this->model_integrations->getMyNamesIntegrations(true);
		$names_marketplaces =  $this->model_integrations->getAllIntegrations(true);

		$response = array();
		foreach($names_marketplaces as $name_marketplace) {

            $isActive = $this->db->get_where('integrations', array('store_id' => 0, 'name' => $name_marketplace['name']))->row_array();
            if(!$isActive || $isActive['active'] == '0'){
                continue;
            }

			$prd_integration =  $this->model_integrations->getPrdIntegrationByIntToProdId($name_marketplace['int_to'] , $product_id);
			$status = null;
			$status_int = null;
			$btn = "label-default";
			$tip = mb_strtoupper($this->lang->line('application_not_published'),'UTF-8');
			if ($prd_integration) {
				$status = $prd_integration['status'];
				$status_int = $prd_integration['status_int'];
				if ($status==0) {
                    $btn = "label-danger"; $tip = mb_strtoupper($this->lang->line('application_inactive'),'UTF-8');
				} elseif ($prd_integration['status_int']==1) {
                    $btn = "label-success"; $tip = mb_strtoupper($this->lang->line('application_product_waiting_to_be_sent'),'UTF-8');
				} elseif ($prd_integration['status_int']==2) {
                	$btn = "label-primary"; $tip = mb_strtoupper($this->lang->line('application_product_sent'),'UTF-8');
				} elseif ($prd_integration['status_int']==11) {
                   	$btn = "label-danger"; $tip = mb_strtoupper($this->lang->line('application_product_higher_price'),'UTF-8');
				} elseif ($prd_integration['status_int']==12) {
                   	$btn = "label-danger"; $tip = mb_strtoupper($this->lang->line('application_product_higher_price'),'UTF-8');
				} elseif ($prd_integration['status_int']==13) {
                	$btn = "label-danger"; $tip = mb_strtoupper($this->lang->line('application_product_higher_price'),'UTF-8');
				} elseif ($prd_integration['status_int']==14) {
                	$btn = "label-danger"; $tip = mb_strtoupper($this->lang->line('application_product_release'),'UTF-8');
				} elseif ($prd_integration['status_int']==20) {
                	$btn = "label-success"; $tip = mb_strtoupper($this->lang->line('application_in_registration'),'UTF-8');
				} elseif ($prd_integration['status_int']==21) {
                 	$btn = "label-success"; $tip = mb_strtoupper($this->lang->line('application_in_registration'),'UTF-8');
				} elseif ($prd_integration['status_int']==22) {
                	$btn = "label-success"; $tip = mb_strtoupper($this->lang->line('application_in_registration'),'UTF-8');
				} elseif ($prd_integration['status_int']==23) {
					$btn = "label-success"; $tip = mb_strtoupper($this->lang->line('application_in_registration'),'UTF-8');
				} elseif ($prd_integration['status_int']==24) {
                	$btn = "label-success"; $tip = mb_strtoupper($this->lang->line('application_in_registration'),'UTF-8');
				} elseif ($prd_integration['status_int']==90) {
                	$btn = "label-default"; $tip = mb_strtoupper($this->lang->line('application_product_inactive'),'UTF-8');
				} elseif ($prd_integration['status_int']==91) {
                	$btn = "label-default"; $tip = mb_strtoupper($this->lang->line('application_no_logistics'),'UTF-8');	
			    } elseif ($prd_integration['status_int']==99) {
			    	$btn = "label-warning"; $tip = mb_strtoupper($this->lang->line('application_product_in_analysis'),'UTF-8');
				} else {
                	$btn = "label-danger"; $tip = mb_strtoupper($this->lang->line('application_product_out_of_stock'),'UTF-8');
				}
				
			}
			$response[] = array(
				'int_to' => $name_marketplace['int_to'],
				'name' => $name_marketplace['name'],
				'status' => $status,
				'status_int' => $status_int,
				'label' => $btn,
				'description' => $tip,		
			);
		}
		ob_clean();
        echo json_encode($response);
	}

	public function toPublishSeveral()
	{
		if(!in_array('doProductsPublish', $this->permission)) {
        	redirect('dashboard', 'refresh');
        }
        $disable_message = $this->model_settings->getValueIfAtiveByName('disable_publication_of_new_products');
        if ($disable_message) {
            $this->session->set_flashdata('error', utf8_decode($disable_message));
            redirect('dashboard', 'refresh');
        }
        if ($this->postClean('select_all_products') == 1) {
            $form_data = $this->postClean();

            // remove campos que não serão utilizados.
            unset($form_data['id_publish_several']);
            unset($form_data['publishProductsubmitSeveral']);
            unset($form_data['select_all_products']);

            $userstore = $this->session->userdata('userstore');
            if($userstore !== null && ((int) $userstore) == 0){
                // SE VISAO DE MARKETPLACE E NENHUMA LOJA SELECIONAD, NÃO É PERMITIDO PUBLICAR
                if($form_data['busca_lojas'] == ""){
                    $this->session->set_flashdata('error', $this->lang->line('messages_select_one_store'));
                    redirect('productsPublish/index');
                }
                // SE VISAO DE MARKETPLACE E MAIS DE UMA LOJA SELECIONADA, NÃO É PERMITIDO PUBLICAR
                if($form_data['busca_lojas'] != ""){
                    $lojas = explode(",", $form_data['busca_lojas']);
                    if(count($lojas) > 1){
                        $this->session->set_flashdata('error', $this->lang->line('messages_select_only_one_store'));
                        redirect('productsPublish/index');
                    }
                }
            }

            $this->model_csv_to_verifications->create(
                array(
                    'upload_file'   => '',
                    'user_id'       => $this->session->userdata('id'),
                    'username'      => $this->session->userdata('username'),
                    'user_email'    => $this->session->userdata('email'),
                    'usercomp'      => $this->session->userdata('usercomp') ?? 1,
                    'allow_delete'  => true,
                    'module'        => 'AddProductsToQueueByPublishFilter',
                    'form_data'     => json_encode($form_data, JSON_UNESCAPED_UNICODE),
                    'store_id'      => $this->session->userdata('userstore') ?: null
                )
            );
            $this->session->set_flashdata('success', $this->lang->line('messages_products_sent_will_be_processed_in_a_moment'));
            redirect('productsPublish/index');
        }

		$prod_id = $this->postClean('id_publish_several');
		$products_id = explode(";",$prod_id);
		$intos_Active = $this->postClean('int_to_several');
		$intos_Inactive = $this->postClean('int_to_inactive');
		foreach($products_id as $product_id) {
			if ($product_id !='') {
				$this->publishing->setPublish($product_id, $intos_Active, 1, False);
                $this->publishing->setPublish($product_id, $intos_Inactive, 0, False);
			}
		}
        $this->session->set_flashdata('success', $this->lang->line('messages_successfully_sent'));
		redirect('productsPublish/index');
	}
	
	public function toPublishAllFiltered(){
		if(!in_array('doProductsPublish', $this->permission)) {
        	redirect('dashboard', 'refresh');
        }
        $disable_message = $this->model_settings->getValueIfAtiveByName('disable_publication_of_new_products');
        if ($disable_message) {
            $this->session->set_flashdata('error', utf8_decode($disable_message));
            redirect('dashboard', 'refresh');
        }
		$postdata = $this->postClean(NULL,TRUE);

		$procura = '';
		if (trim($postdata['sku_filtered'])!='') {
			$procura .= " AND p.sku like '%".$postdata['sku_filtered']."%' ";
		}
		if (trim($postdata['product_name'])!='') {
			$procura .= " AND p.name like '%".$postdata['product_name']."%' ";
		}
		$find_status_int = '';
		if (trim($postdata['status_int_filter']) && ((int)$postdata['status_int_filter'] != 999)) {
			$find_status_int = ' AND i.status_int = '.(int)$postdata['status_int_filter']. ' ';				
		}
		if(trim($postdata['marketplace'])!='[]') {
			$postdata['marketplace'] = json_decode($postdata['marketplace']);
			if (is_array($postdata['marketplace'])) {
                $int_tos = $postdata['marketplace'];
                $procura .= " AND (";
                foreach($int_tos as $int_to) {
					$procura .= "(i.int_to = ".$this->db->escape($int_to).$find_status_int." ) OR ";
                }
                $procura = substr($procura, 0, (strlen($procura)-3));
                $procura .= ") ";
            }
		}
		
		if(trim($postdata['stores'])!='[]') {
			$postdata['stores'] = json_decode($postdata['stores']);
			if (is_array($postdata['stores'])) {
                $lojas = $postdata['stores'];
                $procura .= " AND s.id in (";
                foreach($lojas as $loja) {
                    $procura .= (int)$loja.",";
                }
                $procura = substr($procura, 0, (strlen($procura)-1));
                $procura .= ") ";
            }
		}

    	if (trim($postdata['qtd_stock'])!='') {
			switch ((int)$postdata['qtd_stock']) {
				case 1: 
					$procura .= " AND p.qty > 0 ";
					break;
				case 2:
					$procura .= " AND p.qty <= 0 ";
					break;
			}
		}

		if (trim($postdata['situation_filter'])) {
			switch ((int)$postdata['situation_filter']) {
				case 1: 
					$procura .= " AND p.situacao = 1 ";
					break;
				case 2:
					$procura .= " AND p.situacao = 2 ";
					break;
			}
		}
		$deletedStatus = Model_products::DELETED_PRODUCT;
		if ($postdata['status_filter']) {
			$procura .= " AND p.status = {$postdata['status_filter']}";
		} else {
			$procura .= " AND p.status NOT IN ({$deletedStatus})";
		}
			
        $filtered = $this->model_integrations->getProductsToPublishCount($procura);
		$datas = $this->model_integrations->getProductsToPublish(0, '', $procura, $filtered);  

		$intos_Active = $this->postClean('int_to_several');
		$intos_Inactive = $this->postClean('int_to_inactive');  
		
		foreach($datas as $product) {
            $this->publishing->setPublish($product['id'], $intos_Active, 1, False);
            $this->publishing->setPublish($product['id'], $intos_Inactive, 0, False);
		}
		redirect('productsPublish/index');
		
	}
	
	public function toPublish()
	{
		if(!in_array('doProductsPublish', $this->permission)) {
        	redirect('dashboard', 'refresh');
        }
        $disable_message = $this->model_settings->getValueIfAtiveByName('disable_publication_of_new_products');
        if ($disable_message) {
            $this->session->set_flashdata('error', utf8_decode($disable_message));
            redirect('dashboard', 'refresh');
        }
		$product_id = $this->postClean('id_publish');
		$intos = $this->postClean('int_to');

        $this->publishing->setPublish($product_id, $intos, 1, True);

        $this->log_data(__CLASS__, __CLASS__.'/'.__FUNCTION__, "produto {$product_id} enviado para publicação\nintos=".json_encode($intos)."\nERRO=".json_encode($this->session->error ?? null), "I");

        $this->session->set_flashdata('success', $this->lang->line('messages_successfully_sent'));
		redirect('productsPublish/index');
	}
	
	
}
