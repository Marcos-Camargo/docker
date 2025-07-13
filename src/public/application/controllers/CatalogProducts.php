<?php
/*
 
 Controller de Produtos de Catálogs  
 
 */
require 'system/libraries/Vendor/autoload.php';

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property Model_products $model_products
 * @property Model_brands $model_brands
 * @property Model_category $model_category
 * @property Model_attributes $model_attributes
 * @property Model_atributos_categorias_marketplaces $model_atributos_categorias_marketplaces
 * @property Model_categorias_marketplaces $model_categorias_marketplaces
 * @property Model_products_marketplace $model_products_marketplace
 * @property Model_settings $model_settings
 * @property Model_catalogs $model_catalogs
 * @property Model_products_catalog $model_products_catalog
 * @property Model_integrations $model_integrations
 * @property Model_promotions $model_promotions
 * @property Model_campaigns $model_campaigns
 * @property Model_errors_transformation $model_errors_transformation
 * @property Model_orders $model_orders
 * @property Model_company $model_company
 * @property Model_products_category_mkt $model_products_category_mkt
 * @property Model_stores_multi_channel_fulfillment $model_stores_multi_channel_fulfillment
 * @property Model_products_catalog_associated $model_products_catalog_associated
 * @property Model_stores $model_stores
 * @property Model_catalogs_associated $model_catalogs_associated
 * 
 * @property Bucket	$bucket
 */
class CatalogProducts extends Admin_Controller
{
    public $allowable_tags = null;

	var $companySika 	= array('id'=>'-100'); // retorna um id inválido para que o processo continue. 
	var $storeSika 		= array('id'=>'-100');
	var $catalogSika 	= array('id'=>'-100');
	
	var $vtexsellercenters;
	var $vtexsellercentersNames;
	
	private $product_length_name ;
    private $product_length_description ;

    private $identifying_technical_specification;

	const COLOR_DEFAULT = 'Cor';
	const SIZE_DEFAULT = 'TAMANHO';
	const VOLTAGE_DEFAULT = 'VOLTAGEM';
	const DEGREE_DEFAULT = 'GRAU';
	const SIDE_DEFAULT = 'LADO';

    public function __construct()
    {
        parent::__construct();
        
        $this->not_logged_in();
        
        $this->data['page_title'] = $this->lang->line('application_catalog_products');
        
        $this->load->model('model_products');
        $this->load->model('model_brands');
        $this->load->model('model_category');
        $this->load->model('model_attributes');
        $this->load->model('model_atributos_categorias_marketplaces');
		$this->load->model('model_categorias_marketplaces');
		$this->load->model('model_products_marketplace');
		$this->load->model('model_settings');
		$this->load->model('model_catalogs');
		$this->load->model('model_products_catalog');
		$this->load->model('model_integrations');
		$this->load->model('model_promotions');
		$this->load->model('model_campaigns');
		$this->load->model('model_errors_transformation');
		$this->load->model('model_orders');
		$this->load->model('model_company');
		$this->load->model('model_products_category_mkt');
		$this->load->model('model_stores_multi_channel_fulfillment');
		$this->load->model('model_stores');
		$this->load->model('model_products_catalog_associated');
        $this->load->model('model_catalogs_associated');
		$this->load->library('bucket');

        if ($allowableTags = $this->model_settings->getValueIfAtiveByName('catalogs_allowable_tags')) {
            if (!empty($allowableTags)) {
                $this->allowable_tags = '<' . implode('><', explode(',', $allowableTags)) . '>';
            }
        }

        $this->identifying_technical_specification = $this->model_settings->getSettingDatabyName('identifying_technical_specification');

        $valids = array();
        $attribs = $this->model_attributes->getActiveAttributeData('products');
        foreach ($attribs as $attrib) {
            $values = $this->model_attributes->getAttributeValueData($attrib['id']);
            $y = array();
            foreach ($values as $x) {
                $y[$x['value']] = $x['id'];
            }
            $valids[strtolower($attrib['name'])] = $y;
        }
        $this->data['valids'] = $valids;
        
		$int_tosvtex = $this->getVtexIntegrations();
        $this->vtexsellercenters = $int_tosvtex['int_to'];
	    $this->vtexsellercentersNames = $int_tosvtex['name'];

		$product_length_name = $this->model_settings->getValueIfAtiveByName('product_length_name');
        if ($product_length_name) {
            $this->product_length_name = $product_length_name;
        } else {
            $this->product_length_name = Model_products::CHARACTER_LIMIT_IN_FIELD_NAME;
        }
        $product_length_description = $this->model_settings->getValueIfAtiveByName('product_length_description');
        if ($product_length_description) {
            $this->product_length_description = $product_length_description;
        } else {
            $this->product_length_description = Model_products::CHARACTER_LIMIT_IN_FIELD_DESCRIPTION;
        }

		$this->variant_color  = $this->model_settings->getValueIfAtiveByName('variant_color_attribute');
		if (!$this->variant_color) {  $this->variant_color = self::COLOR_DEFAULT; }

		$this->variant_size  = $this->model_settings->getValueIfAtiveByName('variant_size_attribute');
		if (!$this->variant_size) {  $this->variant_size = self::SIZE_DEFAULT; }

		$this->variant_voltage  = $this->model_settings->getValueIfAtiveByName('variant_voltage_attribute');
		if (!$this->variant_voltage) {  $this->variant_voltage = self::VOLTAGE_DEFAULT; }

		$this->variant_degree  = $this->model_settings->getValueIfAtiveByName('variant_degree_attribute');
		if (!$this->variant_degree) {  $this->variant_degree = self::DEGREE_DEFAULT; }

        $this->variant_side  = $this->model_settings->getValueIfAtiveByName('variant_side_attribute');
		if (!$this->variant_side) {  $this->variant_side = self::SIDE_DEFAULT; }
    }
	
	public function create()
    {
        if(!in_array('createProductsCatalog', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

		$this->data['sellercenter_name'] = 'conectala';
		$settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter_name');
        if ($settingSellerCenter)
            $this->data['sellercenter_name'] = $settingSellerCenter['value'];
		
		$label_ean = $this->model_settings->getValueIfAtiveByName('catalog_products_ean_name');
		if (!$label_ean) {
			$label_ean = $this->lang->line('application_ean');
		}
		$verify_ean = ($this->model_settings->getStatusbyName('catalog_products_verify_ean') == 1);

		$require_ean = ($this->model_settings->getStatusbyName('catalog_products_require_ean') == 1);
        $reqEanValidate = $require_ean ? 'required|' : '';
		
		$invalid_ean = $this->model_settings->getValueIfAtiveByName('catalog_products_error_ean');
		if (!$invalid_ean) {
			$invalid_ean= $this->lang->line('application_invalid_ean');
		}

        $this->data['dimenssion_min_product_image'] = null;
        $this->data['dimenssion_max_product_image'] = null;
        $product_image_rules = $this->model_settings->getValueIfAtiveByName('product_image_rules');
        if ($product_image_rules) {
            $exp_product_image_rules = explode(';', $product_image_rules);
            if (count($exp_product_image_rules) === 2) {
                $dimenssion_min_validate  = onlyNumbers($exp_product_image_rules[0]);
                $dimenssion_max_validate  = onlyNumbers($exp_product_image_rules[1]);

                $this->data['dimenssion_min_product_image'] = $dimenssion_min_validate;
                $this->data['dimenssion_max_product_image'] = $dimenssion_max_validate;
            }
        }

		$disable_variation_check = ($this->model_settings->getStatusbyName('disable_category_variation_check_on_catalog_products') == 1 ? true : false); 
		
		$this->form_validation->set_rules('product_image', $this->lang->line('application_uploadimages'), 'trim|required|callback_checkFotos');
		if ($verify_ean) {
			$this->form_validation->set_rules('EAN',$label_ean,'trim|'.$reqEanValidate.'is_unique[products_catalog.EAN]|callback_checkEan', array('checkEan' => $invalid_ean));
		}
		else {
			$this->form_validation->set_rules('EAN',$label_ean,'trim|'.$reqEanValidate.'is_unique[products_catalog.EAN]');
		}
		
	    $this->form_validation->set_rules('name', $this->lang->line('application_product_name'), 'trim|required');
		$this->form_validation->set_rules('status', $this->lang->line('application_availability'), 'trim|required');
		$this->form_validation->set_rules('catalogs[]', $this->lang->line('application_catalogs'), 'trim|required');
	    $this->form_validation->set_rules('description', $this->lang->line('application_description'), 'trim|required');
	    $this->form_validation->set_rules('price', $this->lang->line('application_price'), 'trim|required|greater_than[0]');
        //$this->form_validation->set_rules('net_weight', $this->lang->line('application_net_weight'), 'trim|required');
        $this->form_validation->set_rules('gross_weight', $this->lang->line('application_weight'), 'trim|required');
        $this->form_validation->set_rules('width', $this->lang->line('application_width'), 'trim|required|callback_checkMinValue[1]');
        $this->form_validation->set_rules('height', $this->lang->line('application_height'), 'trim|required|callback_checkMinValue[1]');
        $this->form_validation->set_rules('length', $this->lang->line('application_depth'), 'trim|required|callback_checkMinValue[1]');
        $this->form_validation->set_rules('products_package', $this->lang->line('application_depth'), 'trim|required|callback_checkMinValue[1]');
        $this->form_validation->set_rules('warranty', $this->lang->line('application_garanty'), 'trim|required');
        $this->form_validation->set_rules('origin', $this->lang->line('application_origin_product'), 'trim|required|numeric');
        $this->form_validation->set_rules('category', $this->lang->line('application_categories'), 'trim|required');
        $this->form_validation->set_rules('brands', $this->lang->line('application_brands'), 'trim|required');
		if ($this->postClean('semvar',true) !== "on") {
			if ($verify_ean) {
				$this->form_validation->set_rules('EAN_V[]', $label_ean, 'trim|'.$reqEanValidate.'is_unique[products_catalog.EAN]|callback_checkEan', array('checkEan' => $invalid_ean));
			}
			else {
				$this->form_validation->set_rules('EAN_V[]',$label_ean,'trim|'.$reqEanValidate.'is_unique[products_catalog.EAN]');
			}
		}
		$this->form_validation->set_rules('peso_liquido', $this->lang->line('application_net_weight'), 'trim|required');
        //$this->form_validation->set_rules('peso_bruto', $this->lang->line('application_weight'), 'trim|required');
        $this->form_validation->set_rules('actual_width', $this->lang->line('application_width'), 'trim');
        $this->form_validation->set_rules('actual_height', $this->lang->line('application_height'), 'trim');
        $this->form_validation->set_rules('actual_depth', $this->lang->line('application_depth'), 'trim');
		
        //Origem do produto
        $this->data['origins'] = array(
            0 => $this->lang->line("application_origin_product_0"),
            1 => $this->lang->line("application_origin_product_1"),
            2 => $this->lang->line("application_origin_product_2"),
            3 => $this->lang->line("application_origin_product_3"),
            4 => $this->lang->line("application_origin_product_4"),
            5 => $this->lang->line("application_origin_product_5"),
            6 => $this->lang->line("application_origin_product_6"),
            7 => $this->lang->line("application_origin_product_7"),
            8 => $this->lang->line("application_origin_product_8"),
        );
        
		$allgood = true;
		// Testo se tem atributos de marketplace obrigatórios como variação 
		if ((!is_null($this->postClean('category',true))) && (!$disable_variation_check)) {
			$msgError = '';
			
			// Agora pego os campos do ML. ML é mais simples e só obriga Cor ou tamanho e não precisa que seja 
			$arr = $this->pegaCamposMKTdaMinhaCategoria($this->postClean('category',true),'ML');
			$campos_att = $arr[0];
			foreach ($campos_att as $campo_att) {
				if (($campo_att['obrigatorio'] == 1) && ($campo_att['variacao'] == 1)) {
					if ($campo_att['id_atributo'] == 'COLOR') {  
						if ($this->postClean('colorvar',true) !== "on") {
							$msgError .= $this->lang->line('messages_error_color_variant_mercado_livre').'<br>';
						}
					}
					if ($campo_att['id_atributo'] == 'SIZE') {
						if ($this->postClean('sizevar',true) !== "on") {
							$msgError .= $this->lang->line('messages_error_size_variant_mercado_livre').'<br>';
						}
					}
				}
			}
			
			// Agora pego os campos da Via Varejo
			$arr = $this->pegaCamposMKTdaMinhaCategoria($this->postClean('category',true),'VIA');
			$campos_att = $arr[0];
			foreach($campos_att as $campo_att) {
				if (($campo_att['obrigatorio'] == 1) && ($campo_att['variacao'] == 1)) {
					if ($campo_att['nome'] == 'Cor') {  // campo cor da Via Varejo
						if ($this->postClean('colorvar',true) !== "on") {
							$msgError .= $this->lang->line('messages_error_color_variant_via_varejo').'<br>';
						}
						else {
							$coreslidas = json_decode($campo_att['valor'],true);
							$coresvalidas = array();
							foreach ($coreslidas as $corlida) {
								$coresvalidas[] = trim(ucfirst(strtolower($corlida['udaValue'])));
							}
							$cores = $this->postClean('C',true);
							foreach($cores as $key => $cor) {
								$i = $key+1;
								if (!in_array(ucfirst(strtolower($cor)),$coresvalidas)) {
									$msgError .= 'Cor "'.$cor.'" inválida na variação '.$i.'. Cores válidas para Via Varejo são: '.implode(",", $coresvalidas).'<br>';
								}		
							}
						}
					}
					if ($campo_att['nome'] == 'Tamanho') {
						if ($this->postClean('sizevar',true) !== "on") {
							$msgError .= $this->lang->line('messages_error_size_variant_via_varejo').'<br>';
						}
						else {
							$tamlidas = json_decode($campo_att['valor'],true);
							$tamvalidos = array();
							foreach ($tamlidas as $tamlida) {
								$tamvalidos[] = trim($tamlida['udaValue']);
							}
							$tams = $this->postClean('T',true);
							foreach($tams as $key => $tam) {
								$i = $key+1;
								if (!in_array($tam,$tamvalidos)) {
									$msgError .= 'Tamanho "'.$tam.'" inválido na variação '.$i.'. Tamanhos válidos para Via Varejo são: '.implode(",", $tamvalidos).'<br>';
								}		
							}
						}
					}
					if ($campo_att['nome'] == 'Voltagem') {
						if ($this->postClean('voltvar',true) !== "on") {
							$msgError .= $this->lang->line('messages_error_voltage_variant_via_varejo').'<br>';
						}
						else {
							$volts = $this->postClean('V',true);
							
						}
					}					
				}
			}

			foreach ($this->vtexsellercenters as $sellercenter) {
				$lcseller = strtolower($sellercenter);
				// Agora pego os campos da Novo Mundo
				$arr = $this->pegaCamposMKTdaMinhaCategoria($this->postClean('category',true), $sellercenter);
				$campos_att = $arr[0];
				foreach ($campos_att as $campo_att) {
					if (($campo_att['obrigatorio'] == 1) && ($campo_att['variacao'] == 1)) {
						if ((strtoupper($campo_att['nome']) == 'COR') || (strtoupper($campo_att['nome']) == 'CORES') || (strtoupper($campo_att['nome']) == strtoupper($this->variant_color)) )  {  // campo cor da 
							if ($this->postClean('colorvar',true) !== "on") {
								$msgError .= sprintf($this->lang->line('messages_error_color_variant_seller_name'),$this->data['sellercenter_name']) . '<br>';
							} else {
								$coreslidas = json_decode($campo_att['valor'], true);
								$coresvalidas = array();
								foreach ($coreslidas as $corlida) {
									$coresvalidas[] = trim(ucfirst(strtolower($corlida['Value'])));
								}
								$cores = $this->postClean('C',true);
								foreach ($cores as $key => $cor) {
									$i = $key + 1;
									if (!in_array(ucfirst(strtolower($cor)), $coresvalidas)) {
										$msgError .= 'Cor "' . $cor . '" inválida na variação ' . $i . '. Cores válidas para ' . $this->vtexsellercentersNames[$sellercenter] . ' são: ' . implode(",", $coresvalidas) . '<br>';
									}
								}
							}
						}elseif ((strtoupper($campo_att['nome']) == 'TAMANHO') || (strtoupper($campo_att['nome']) == strtoupper($this->variant_size))) {
							if ($this->postClean('sizevar',true) !== "on") {
								$msgError .= sprintf($this->lang->line('messages_error_size_variant_seller_name'),$this->data['sellercenter_name']) . '<br>';
							} else {
								$tamlidas = json_decode($campo_att['valor'], true);
								$tamvalidos = array();
								foreach ($tamlidas as $tamlida) {
									$tamvalidos[] = trim($tamlida['Value']);
								}
								$tams = $this->postClean('T',true);
								foreach ($tams as $key => $tam) {
									$i = $key + 1;
									if (!in_array($tam, $tamvalidos)) {
										$msgError .= 'Tamanho "' . $tam . '" inválido na variação ' . $i . '. Tamanhos válidos para ' . $this->vtexsellercentersNames[$sellercenter] . ' são: ' . implode(",", $tamvalidos) . '<br>';
									}
								}
							}
						}elseif ((strtoupper($campo_att['nome']) == 'VOLTAGEM') || (strtoupper($campo_att['nome']) == strtoupper($this->variant_voltage))){
							if ($this->postClean('voltvar',true) !== "on") {
								$msgError .= $this->lang->line('messages_error_voltage_variant_'.$lcseller) . '<br>';
							} else {
								$volts = $this->postClean('V',true);
							}
						}else {
							$msgError .= 'Esta categoria na '.$lcseller.' obriga a variação por '.$campo_att['nome'].' mas o sistema não suporta<br>';
						}
					}
				}
				if ($this->postClean('colorvar',TRUE) == "on") {
					$arr = $this->verifyVariantsAtCategoryMarketplace($this->postClean('category',TRUE), $sellercenter,array('Cor','Cores', $this->variant_color));
					if (!is_null($arr[0]) && (!$arr[1])) {
						$msgError .= 'Esta categoria na '.$this->vtexsellercentersNames[$sellercenter].' não permite variação por Cor'.'<br>';
					}
				}
				if ($this->postClean('voltvar',TRUE) == "on") {
					$arr = $this->verifyVariantsAtCategoryMarketplace($this->postClean('category',TRUE), $sellercenter,array('Voltagem', $this->variant_voltage));
					if (!is_null($arr[0]) && (!$arr[1])) {
						$msgError .= 'Esta categoria na '.$this->vtexsellercentersNames[$sellercenter].' não permite variação por Voltagem'.'<br>';
					}
				}
				if ($this->postClean('sizevar',TRUE) == "on") {
					$arr = $this->verifyVariantsAtCategoryMarketplace($this->postClean('category',TRUE), $sellercenter,array('Tamanho',$this->variant_size));
					if (!is_null($arr[0]) && (!$arr[1])) {
						$msgError .= 'Esta categoria na '.$this->vtexsellercentersNames[$sellercenter].' não permite variação por Tamanho'.'<br>';
					}
				}
			}

			if ($msgError !== '') {
				$this->session->set_flashdata('error', $msgError);
				$allgood = false;
			}
		}
		
        if (($this->form_validation->run() == TRUE)  && ($allgood)) {
            $comvar = $this->postClean('semvar',TRUE);
            $has_var = "";
            if ($comvar !== "on") {
                if ($this->postClean('sizevar',TRUE)=="on") {
                    $has_var .= ($has_var=="") ? "TAMANHO":";TAMANHO";
                }
                if ($this->postClean('colorvar',TRUE)=="on") {
                    $has_var .= ($has_var=="") ? "Cor":";Cor";
                }
                if ($this->postClean('voltvar',TRUE)=="on") {
                   $has_var .= ($has_var=="") ? "VOLTAGEM":";VOLTAGEM";
                }
				if ($this->postClean('grauvar',TRUE)=="on") {
					$has_var .= ($has_var=="") ? "GRAU":";GRAU";
				 }
				 if ($this->postClean('ladovar',TRUE)=="on") {
					$has_var .= ($has_var=="") ? "LADO":";LADO";
				 }
            }
			
			$principal_image = '';
			$upload_image = $this->postClean('product_image',TRUE);
            $this->data['upload_image'] = $upload_image;

			$asset_image = 'assets/images/product_image/' . $upload_image;
			$product_images = $this->bucket->getFinalObject($asset_image);
			if ($product_images['success'] && count($product_images['contents'])) {
				$principal_image = $product_images['contents'][0]['url'] ?? "";
			}
			
            $data_prod = array(
            	'EAN' 					=> $this->postClean('EAN',TRUE),
		        'name' 					=> $this->postClean('name',TRUE),
		        'status' 				=> $this->postClean('status',TRUE),
		        'has_variants' 			=> $has_var, 
		        'price' 				=> $this->postClean('price',TRUE),
		        'description' 			=> strip_tags_products($this->postClean('description',TRUE, false, false), $this->allowable_tags), // $this->postClean('description'),
		        'attribute_value_id' 	=> json_encode($this->postClean('attributes_value_id',TRUE)),
                'brand_code' 			=> $this->postClean('brand_code',TRUE),
                'net_weight' 			=> $this->postClean('peso_liquido',TRUE),
                'gross_weight' 			=> $this->postClean('gross_weight',TRUE),
                'width' 				=> $this->postClean('width',TRUE),
                'height' 				=> $this->postClean('height',TRUE),
                'length' 				=> $this->postClean('length',TRUE),
                'products_package' 		=> $this->postClean('products_package',TRUE),
                'actual_width' 			=> $this->postClean('actual_width',TRUE),
                'actual_height' 		=> $this->postClean('actual_height',TRUE),
                'actual_depth' 			=> $this->postClean('actual_depth',TRUE),
                'warranty' 				=> $this->postClean('warranty',TRUE),
                'brand_id' 				=> $this->postClean('brands',TRUE),
		    	'category_id' 			=> $this->postClean('category',TRUE),
                'NCM' 					=> preg_replace('/[^\d\+]/', '',$this->postClean('NCM',TRUE)),
                'origin' 				=> $this->postClean('origin',TRUE),
		    	'image' 				=> $upload_image,
		        'principal_image' 		=> $principal_image,    
		        'original_price' 		=> $this->postClean('original_price',TRUE),        
              );
			$category_id = $this->postClean('category',TRUE);
            $prd_cat_id = $this->model_products_catalog->create($data_prod, $this->postClean('catalogs',TRUE));
        	if($prd_cat_id != false) {
				if ($has_var != "") {
					$tam 			= $this->postClean('T',TRUE);
					$cor 			= $this->postClean('C',TRUE);
					$volt 			= $this->postClean('V',TRUE);
					$grau 			= $this->postClean('gd',TRUE);
					$lado 			= $this->postClean('ld',TRUE);
                    $eanVar 		= $this->postClean('EAN_V',TRUE);
					$image_folder 	= $this->postClean('IMAGEM',TRUE);
                    $countVarEmpty = 1;

					for ($x=0;$x <= $this->postClean('numvar',TRUE)-1;$x++) {
						$variante = "";					
						if ($this->postClean('sizevar',TRUE)=="on") {
							$variante .= ";".$tam[$x];
						}
						if ($this->postClean('colorvar',TRUE)=="on") {
							$variante .= ";".$cor[$x];
						}
						if ($this->postClean('voltvar',TRUE)=="on") {
							$variante .= ";".$volt[$x];
						}
						if ($this->postClean('grauvar',TRUE)=="on") {
							$variante .= ";".$grau[$x];
						}
						if ($this->postClean('ladovar',TRUE)=="on") {
							$variante .= ";".$lado[$x];
						}
						$variante = substr($variante,1);
						
						$data_prod['EAN'] = $eanVar[$x];
						$data_prod['prd_principal_id'] = $prd_cat_id;
						$data_prod['name'] = $variante;
						$data_prod['variant_id'] = $x;
						$data_prod['image'] = $image_folder[$x];
						
						$createvar = $this->model_products_catalog->create($data_prod, $this->postClean('catalogs',TRUE));
						
					}
				}	

                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_created'));
                if ($disable_variation_check) {
					redirect("catalogProducts/index", 'refresh');
				}
				redirect("catalogProducts/attributes/create/$prd_cat_id/$category_id", 'refresh');
        	}
        	else {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                redirect('catalogproducts/create', 'refresh');
        	}
        }
        else {
            // false case
            $this->data['prdtoken'] = get_instance()->getGUID(false);
			
			$key = '';
		    $keys = array_merge(range('A', 'Z'), range('a', 'z'));
		
		    for ($i = 0; $i < 15; $i++) {
		        $key .= $keys[array_rand($keys)];
		    }
            $this->data['imagemvariant0'] = $key;
            // attributes
            $attribute_data = $this->model_attributes->getActiveAttributeData('products');
            
            $attributes_final_data = array();
            foreach ($attribute_data as $k => $v) {
                $attributes_final_data[$k]['attribute_data'] = $v;
                
                $value = $this->model_attributes->getAttributeValueData($v['id']);
                
                $attributes_final_data[$k]['attribute_value'] = $value;
            }
            
            $this->data['attributes'] = $attributes_final_data;
            $this->data['brands'] = $this->model_brands->getActiveBrands();
            $this->data['category'] = $this->model_category->getActiveCategroy();   
			$this->data['catalogs'] = $this->model_catalogs->getMyCatalogs(); 
			
			$comvar = $this->postClean('semvar',TRUE);
			$variacaotamanho = array();
			$variacaocor = array();
			$variacaovoltagem = array();
            $variacaosabor = array();
			$variacaograu = array();
			$variacaolado = array();
			$variacaoean = array();
			$variacaoimagem=array();
			if ($comvar != "on") {
                if ($this->postClean('sizevar',TRUE)=="on") {
					$variacaotamanho =  $this->postClean('T',TRUE);
                }
                if ($this->postClean('colorvar',TRUE)=="on") {
					$variacaocor =  $this->postClean('C',TRUE);
                }
                if ($this->postClean('voltvar',TRUE)=="on") {
					$variacaovoltagem =  $this->postClean('V',TRUE);
				}
                if ($this->postClean('saborvar',TRUE)=="on") {
                    $variacaosabor =  $this->postClean('sb',TRUE);
                }
				if ($this->postClean('grauvar',TRUE)=="on") {
					$variacaograu =  $this->postClean('gr',TRUE);
				}
				if ($this->postClean('ladovar',TRUE)=="on") {
					$variacaolado =  $this->postClean('ld',TRUE);
				}
				$variacaoean = $this->postClean('EAN_V',TRUE);
				$variacaoimagem = $this->postClean('IMAGEM',TRUE);
			}
			$this->data['variacaotamanho'] =$variacaotamanho; 
			$this->data['variacaocor'] =$variacaocor;
			$this->data['variacaovoltagem'] =$variacaovoltagem;
            $this->data['variacaosabor'] =$variacaosabor;
			$this->data['variacaograu'] =$variacaograu;
			$this->data['variacaolado'] =$variacaolado;
			$this->data['variacaoean'] = $variacaoean;
			$this->data['variacaoimagem'] = $variacaoimagem;
			
			$this->data['upload_image']= $this->postClean('product_image',TRUE);
			
			$this->data['label_ean']= $label_ean;
			$this->data['verify_ean']= $verify_ean;
			$this->data['invalid_ean']= $invalid_ean;
			$this->data['require_ean']= $require_ean;
			$this->data['product_length_name'] = $this->product_length_name ;
        	$this->data['product_length_description'] = $this->product_length_description;

            $this->render_template('catalogproducts/create', $this->data);
        }
    }

	public function update($product_id = null)
    {
    	
        if(!in_array('updateProductsCatalog', $this->permission)) {
        	if(in_array('viewProductsCatalog', $this->permission)) {
            	redirect('catalogProducts/view/'.$product_id, 'refresh');  // redireciona para View se ele puder ver apenas
       		}
        	redirect('dashboard', 'refresh');
        }
		$this->data['sellercenter_name'] = 'conectala';
		$settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter_name');
        if ($settingSellerCenter)
            $this->data['sellercenter_name'] = $settingSellerCenter['value'];

		$product_data=$this->model_products_catalog->getProductProductData($product_id);
		
		if (!is_null($product_data['prd_principal_id'])) { // se for editar uma variação, muda para o produto principal
			redirect("catalogProducts/update/".$product_data['prd_principal_id'], 'refresh');
		} 
		
		$label_ean = $this->model_settings->getValueIfAtiveByName('catalog_products_ean_name');
		if (!$label_ean) {
			$label_ean = $this->lang->line('application_ean');
		}
		$verify_ean = ($this->model_settings->getStatusbyName('catalog_products_verify_ean') == 1);

		$require_ean = ($this->model_settings->getStatusbyName('catalog_products_require_ean') == 1);
        $reqEanValidate = $require_ean ? 'required|' : '';

		$invalid_ean = $this->model_settings->getValueIfAtiveByName('catalog_products_error_ean');
		if (!$invalid_ean) {
			$invalid_ean= $this->lang->line('application_invalid_ean');
		}

        $this->data['dimenssion_min_product_image'] = null;
        $this->data['dimenssion_max_product_image'] = null;
        $product_image_rules = $this->model_settings->getValueIfAtiveByName('product_image_rules');
        if ($product_image_rules) {
            $exp_product_image_rules = explode(';', $product_image_rules);
            if (count($exp_product_image_rules) === 2) {
                $dimenssion_min_validate  = onlyNumbers($exp_product_image_rules[0]);
                $dimenssion_max_validate  = onlyNumbers($exp_product_image_rules[1]);

                $this->data['dimenssion_min_product_image'] = $dimenssion_min_validate;
                $this->data['dimenssion_max_product_image'] = $dimenssion_max_validate;
            }
        }

		$disable_variation_check = ($this->model_settings->getStatusbyName('disable_category_variation_check_on_catalog_products') == 1 ? true : false); 
		
		$this->form_validation->set_rules('product_image', $this->lang->line('application_uploadimages'), 'trim|required|callback_checkFotos');
		if ($verify_ean) {
			$this->form_validation->set_rules('EAN',$label_ean,'trim|'.$reqEanValidate.'callback_checkUniqueEan['.$product_id.']|callback_checkEan', array('checkEan' => $invalid_ean));
		}
		else {
			$this->form_validation->set_rules('EAN',$label_ean,'trim|'.$reqEanValidate.'callback_checkUniqueEan['.$product_id.']]');
		}
		
		$this->form_validation->set_rules('product_image', $this->lang->line('application_uploadimages'), 'trim|required|callback_checkFotos');
		$this->form_validation->set_rules('name', $this->lang->line('application_product_name'), 'trim|required');
		$this->form_validation->set_rules('status', $this->lang->line('application_availability'), 'trim|required');
		$this->form_validation->set_rules('catalogs[]', $this->lang->line('application_catalogs'), 'trim|required');
	    $this->form_validation->set_rules('description', $this->lang->line('application_description'), 'trim|required');
	    $this->form_validation->set_rules('price', $this->lang->line('application_price'), 'trim|required|greater_than[0]');
        $this->form_validation->set_rules('net_weight', $this->lang->line('application_net_weight'), 'trim|required');
        $this->form_validation->set_rules('gross_weight', $this->lang->line('application_weight'), 'trim|required');
        $this->form_validation->set_rules('width', $this->lang->line('application_width'), 'trim|required|callback_checkMinValue[1]');
        $this->form_validation->set_rules('height', $this->lang->line('application_height'), 'trim|required|callback_checkMinValue[1]');
        $this->form_validation->set_rules('length', $this->lang->line('application_depth'), 'trim|required|callback_checkMinValue[1]');
        $this->form_validation->set_rules('products_package', $this->lang->line('application_products_by_packaging'), 'trim|required|callback_checkMinValue[1]');
        $this->form_validation->set_rules('warranty', $this->lang->line('application_garanty'), 'trim|required');
        $this->form_validation->set_rules('origin', $this->lang->line('application_origin_product'), 'trim|required|numeric');
        $this->form_validation->set_rules('category', $this->lang->line('application_categories'), 'trim|required');
        $this->form_validation->set_rules('brands', $this->lang->line('application_brands'), 'trim|required');
		if ($this->postClean('semvar',TRUE) !== "on") {
			$eanVar = $this->postClean('EAN_V',TRUE);
			$variant_id = $this->postClean('VARIANT_ID',TRUE);
			$image_folder = $this->postClean('IMAGEM',TRUE);
			if (is_array($image_folder)) {
				for ($x=0;$x <= count($image_folder)-1 ;$x++) {
					$var_id = null;
					if (key_exists($x, $variant_id)) {
						$var_id = $variant_id[$x]; 
					}
					
					if ($verify_ean) {
						$this->form_validation->set_rules('EAN_V['.$x.']',$label_ean,'trim|'.$reqEanValidate.'callback_checkUniqueEan['.$var_id.']|callback_checkEan', array('checkEan' => $invalid_ean));
					}
					else {
						$this->form_validation->set_rules('EAN_V['.$x.']',$label_ean,'trim|'.$reqEanValidate.'callback_checkUniqueEan['.$var_id.']');
					}
					
				}
			}
		}
		$this->form_validation->set_rules('actual_width', $this->lang->line('application_width'), 'trim');
		$this->form_validation->set_rules('actual_height', $this->lang->line('application_enter_actual_height'), 'trim');
		$this->form_validation->set_rules('actual_depth', $this->lang->line('application_depth'), 'trim');
        //Origem do produto
        $this->data['origins'] = array(
            0 => $this->lang->line("application_origin_product_0"),
            1 => $this->lang->line("application_origin_product_1"),
            2 => $this->lang->line("application_origin_product_2"),
            3 => $this->lang->line("application_origin_product_3"),
            4 => $this->lang->line("application_origin_product_4"),
            5 => $this->lang->line("application_origin_product_5"),
            6 => $this->lang->line("application_origin_product_6"),
            7 => $this->lang->line("application_origin_product_7"),
            8 => $this->lang->line("application_origin_product_8"),
        );
		
		$allgood = true;
		// Testo se tem atributos de marketplace obrigatórios como variação 
		if (!is_null($this->postClean('category',TRUE)) && (!$disable_variation_check)) {
			$msgError = '';
			
			// Agora pego os campos do ML. ML é mais simples e só obriga Cor ou tamanho e não precisa que seja 
			$arr = $this->pegaCamposMKTdaMinhaCategoria($this->postClean('category',TRUE),'ML');
			$campos_att = $arr[0];
			foreach($campos_att as $campo_att) {
				if (($campo_att['obrigatorio'] == 1) && ($campo_att['variacao'] == 1)) {
					if ($campo_att['id_atributo'] == 'COLOR') {  
						if ($this->postClean('colorvar',TRUE) !== "on") {
							$msgError .= $this->lang->line('messages_error_color_variant_mercado_livre').'<br>';
						}
					}
					if ($campo_att['id_atributo'] == 'SIZE') {
						if ($this->postClean('sizevar',TRUE) !== "on") {
							$msgError .= $this->lang->line('messages_error_size_variant_mercado_livre').'<br>';
						}
					}
				}
			}

			
			// Agora pego os campos da Via Varejo
			$arr = $this->pegaCamposMKTdaMinhaCategoria($this->postClean('category',TRUE),'VIA');
			$campos_att = $arr[0];
			foreach($campos_att as $campo_att) {
				if (($campo_att['obrigatorio'] == 1) && ($campo_att['variacao'] == 1)) {
					if ($campo_att['nome'] == 'Cor') {  // campo cor da Via Varejo
						if ($this->postClean('colorvar',TRUE) !== "on") {
							$msgError .= $this->lang->line('messages_error_color_variant_via_varejo').'<br>';
						}
						else {
							$coreslidas = json_decode($campo_att['valor'],true);
							$coresvalidas = array();
							foreach ($coreslidas as $corlida) {
								$coresvalidas[] = trim(ucfirst(strtolower($corlida['udaValue'])));
							}
							$cores = $this->postClean('C',TRUE);
							foreach($cores as $key => $cor) {
								if (!in_array(ucfirst(strtolower($cor)),$coresvalidas)) {
									$msgError .= 'Cor "'.$cor.'" inválida na variação '.$key.'. Cores válidas para Via Varejo são: '.implode(",", $coresvalidas).'<br>';
								}		
							}
						}
					}
					if ($campo_att['nome'] == 'Tamanho') {
						if ($this->postClean('sizevar',TRUE) !== "on") {
							$msgError .= $this->lang->line('messages_error_size_variant_via_varejo').'<br>';
						}
						else {
							$tamlidas = json_decode($campo_att['valor'],true);
							$tamvalidos = array();
							foreach ($tamlidas as $tamlida) {
								$tamvalidos[] = trim($tamlida['udaValue']);
							}
							$tams = $this->postClean('T',TRUE);
							foreach($tams as $key => $tam) {
								if (!in_array($tam,$tamvalidos)) {
									$msgError .= 'Tamanho "'.$tam.'" inválido na variação '.$key.'. Tamanhos válidos para Via Varejo são: '.implode(",", $tamvalidos).'<br>';
								}		
							}
						}
					}
					if ($campo_att['nome'] == 'Voltagem') {
						if ($this->postClean('voltvar',TRUE) !== "on") {
							$msgError .= $this->lang->line('messages_error_voltage_variant_via_varejo').'<br>';
						}
						else {
							$volts = $this->postClean('V',TRUE);
							
						}
					}		
				}
			}

			foreach ($this->vtexsellercenters as $sellercenter) {
				$lcseller = strtolower($sellercenter);
				// Agora pego os campos da Novo Mundo
				$arr = $this->pegaCamposMKTdaMinhaCategoria($this->postClean('category',TRUE), $sellercenter);
				$campos_att = $arr[0];
				foreach ($campos_att as $campo_att) {
					if (($campo_att['obrigatorio'] == 1) && ($campo_att['variacao'] == 1)) {
						if ((strtoupper($campo_att['nome']) == 'COR') || (strtoupper($campo_att['nome']) == strtoupper($this->variant_color))){  // campo cor da 
							if ($this->postClean('colorvar',TRUE) !== "on") {
								//  $msgError .= $this->lang->line('messages_error_color_variant_'.$lcseller) . '<br>';
								$msgError .= sprintf($this->lang->line('messages_error_color_variant_seller_name'),$this->data['sellercenter_name']) . '<br>';
							} else {
								$coreslidas = json_decode($campo_att['valor'], true);
								$coresvalidas = array();
								foreach ($coreslidas as $corlida) {
									$coresvalidas[] = trim(ucfirst(strtolower($corlida['Value'])));
								}
								$cores = $this->postClean('C',TRUE);
								foreach ($cores as $key => $cor) {
									$i = $key + 1;
									if (!in_array(ucfirst(strtolower($cor)), $coresvalidas)) {
										$msgError .= 'Cor "' . $cor . '" inválida na variação ' . $i . '. Cores válidas para ' . $this->vtexsellercentersNames[$sellercenter] . ' são: ' . implode(",", $coresvalidas) . '<br>';
									}
								}
							}
						}
						elseif ((strtoupper($campo_att['nome']) == 'TAMANHO') || (strtoupper($campo_att['nome']) == strtoupper($this->variant_size))) {
							if ($this->postClean('sizevar',TRUE) !== "on") {
								// $msgError .= $this->lang->line('messages_error_size_variant_'.$lcseller) . '<br>';
								$msgError .= sprintf($this->lang->line('messages_error_size_variant_seller_name'),$this->data['sellercenter_name']) . '<br>';
							} else {
								$tamlidas = json_decode($campo_att['valor'], true);
								$tamvalidos = array();
								foreach ($tamlidas as $tamlida) {
									$tamvalidos[] = trim($tamlida['Value']);
								}
								$tams = $this->postClean('T',TRUE);
								foreach ($tams as $key => $tam) {
									$i = $key + 1;
									if (!in_array($tam, $tamvalidos)) {
										$msgError .= 'Tamanho "' . $tam . '" inválido na variação ' . $i . '. Tamanhos válidos para ' . $this->vtexsellercentersNames[$sellercenter] . ' são: ' . implode(",", $tamvalidos) . '<br>';
									}
								}
							}
						}
						elseif ((strtoupper($campo_att['nome']) == 'VOLTAGEM') || (strtoupper($campo_att['nome']) == strtoupper($this->variant_voltage))) {
							if ($this->postClean('voltvar',TRUE) !== "on") {
								// $msgError .= $this->lang->line('messages_error_voltage_variant_'.$lcseller) . '<br>';
								$msgError .= sprintf($this->lang->line('messages_error_voltage_variant_seller_name'),$this->data['sellercenter_name']) . '<br>';
							} else {
								$volts = $this->postClean('V',TRUE);
							}
						}
						else {
							$msgError .= 'Esta categoria na '.$lcseller.' obriga a variação por '.$campo_att['nome'].' mas o sistema não suporta<br>';
						}
					}
				}
				if ($this->postClean('colorvar',TRUE) == "on") {
					$arr = $this->verifyVariantsAtCategoryMarketplace($this->postClean('category',TRUE), $sellercenter,array('Cor','Cores', $this->variant_color));
					if (!is_null($arr[0]) && (!$arr[1])) {
						$msgError .= 'Esta categoria na '.$this->vtexsellercentersNames[$sellercenter].' não permite variação por Cor'.'<br>';
					}
				}
				if ($this->postClean('voltvar',TRUE) == "on") {
					$arr = $this->verifyVariantsAtCategoryMarketplace($this->postClean('category',TRUE), $sellercenter,array('Voltagem', $this->variant_voltage));
					if (!is_null($arr[0]) && (!$arr[1])) {
						$msgError .= 'Esta categoria na '.$this->vtexsellercentersNames[$sellercenter].' não permite variação por Voltagem'.'<br>';
					}
				}
				if ($this->postClean('sizevar',TRUE) == "on") {
					$arr = $this->verifyVariantsAtCategoryMarketplace($this->postClean('category',TRUE), $sellercenter,array('Tamanho',$this->variant_size));
					if (!is_null($arr[0]) && (!$arr[1])) {
						$msgError .= 'Esta categoria na '.$this->vtexsellercentersNames[$sellercenter].' não permite variação por Tamanho'.'<br>';
					}
				}
				if ($this->postClean('grauvar',TRUE) == "on") {
					$arr = $this->verifyVariantsAtCategoryMarketplace($this->postClean('category',TRUE), $sellercenter,array('Grau',$this->variant_degree));
					if (!is_null($arr[0]) && (!$arr[1])) {
						$msgError .= 'Esta categoria na '.$this->vtexsellercentersNames[$sellercenter].' não permite variação por Grau'.'<br>';
					}
				}
				if ($this->postClean('ladovar',TRUE) == "on") {
					$arr = $this->verifyVariantsAtCategoryMarketplace($this->postClean('category',TRUE), $sellercenter,array('Lado',$this->variant_side));
					if (!is_null($arr[0]) && (!$arr[1])) {
						$msgError .= 'Esta categoria na '.$this->vtexsellercentersNames[$sellercenter].' não permite variação por Lado'.'<br>';
					}
				}
			}
			if ($msgError !== '') {
				$this->session->set_flashdata('error', $msgError);
				$allgood = false;
			}
		}	
        
        if (($this->form_validation->run() == TRUE) && ($allgood)) {
            $comvar = $this->postClean('semvar',TRUE);
            $has_var = "";
            if ($comvar !== "on") {
                if ($this->postClean('sizevar',TRUE)=="on") {
                    $has_var .= ($has_var=="") ? "TAMANHO":";TAMANHO";
                }
                if ($this->postClean('colorvar',TRUE)=="on") {
                    $has_var .= ($has_var=="") ? "Cor":";Cor";
                }
                if ($this->postClean('voltvar',TRUE)=="on") {
                   $has_var .= ($has_var=="") ? "VOLTAGEM":";VOLTAGEM";
                }
				if ($this->postClean('grauvar',TRUE)=="on") {
					$has_var .= ($has_var=="") ? "GRAU":";GRAU";
				}
				if ($this->postClean('ladovar',TRUE)=="on") {
					$has_var .= ($has_var=="") ? "LADO":";LADO";
				}
            }
			
			$principal_image = '';
			$upload_image = $this->postClean('product_image',TRUE);
            $this->data['upload_image'] = $upload_image;
			if (!$product_data['is_on_bucket']) {
				$fotos = scandir(FCPATH . 'assets/images/catalog_product_image/' . $upload_image);
				foreach ($fotos as $foto) {
					if (($foto != ".") && ($foto != "..") && ($foto != "")) {
						if ($principal_image == '') {
							$principal_image = base_url('assets/images/catalog_product_image/' . $upload_image) . '/' . $foto;
							break;
						}
					}
				}
			} else {
				// Imagem é enviada primeiro para product_images, depois para catalog _product_images.
				$asset_image = 'assets/images/catalog_product_image/' . $upload_image;
				$this->bucket->renameDirectory('assets/images/product_image/' . $upload_image, $asset_image);
				$product_images = $this->bucket->getFinalObject($asset_image);
				if ($product_images['success'] && count($product_images['contents'])) {
					$principal_image = $product_images['contents'][0]['url'] ?? "";
				}
			}

            $data_prod = array(
            	'EAN' 					=> $this->postClean('EAN',TRUE),
		        'name' 					=> $this->postClean('name',TRUE),
		        'status' 				=> $this->postClean('status',TRUE),
		        'has_variants' 			=> $has_var, 
		        'price' 				=> $this->postClean('price',TRUE),
		        'description' 			=> strip_tags_products($this->postClean('description',TRUE, false, false), $this->allowable_tags), // $this->postClean('description'),
		        'attribute_value_id' 	=> json_encode($this->postClean('attributes_value_id',TRUE)),
                'brand_code' 			=> $this->postClean('brand_code',TRUE),
                'net_weight' 			=> $this->postClean('net_weight',TRUE),
                'gross_weight' 			=> $this->postClean('gross_weight',TRUE),
                'width' 				=> $this->postClean('width',TRUE),
                'height' 				=> $this->postClean('height',TRUE),
                'length' 				=> $this->postClean('length',TRUE),
                'products_package' 		=> $this->postClean('products_package',TRUE),
                'actual_width' 			=> $this->postClean('actual_width',TRUE),
                'actual_height' 		=> $this->postClean('actual_height',TRUE),
                'actual_depth' 			=> $this->postClean('actual_depth',TRUE),
                'warranty' 				=> $this->postClean('warranty',TRUE),
                'brand_id' 				=> $this->postClean('brands',TRUE),
				'category_id' 			=> $this->postClean('category',TRUE),
                'NCM' 					=> preg_replace('/[^\d\+]/', '',$this->postClean('NCM',TRUE)),
                'origin' 				=> $this->postClean('origin',TRUE),
		    	'image' 				=> $upload_image,
		        'principal_image' 		=> $principal_image, 
		        'original_price' 		=> $this->postClean('original_price',TRUE),           
            );
			$category_id = $this->postClean('category',TRUE);
            $prd_cat_id = $this->model_products_catalog->update($data_prod, $product_id, $this->postClean('catalogs',TRUE));
        	if($prd_cat_id != false) {
				if ($has_var != "") {
					$tam = $this->postClean('T',TRUE);
					$cor = $this->postClean('C',TRUE);
					$volt = $this->postClean('V',TRUE);
					$grau = $this->postClean('gr',TRUE);
					$lado = $this->postClean('ld',TRUE);
                    $eanVar = $this->postClean('EAN_V',TRUE);
					$image_folder = $this->postClean('IMAGEM',TRUE);
					$variant_id = $this->postClean('VARIANT_ID',TRUE);
                    $countVarEmpty = 1;
					for ($x=0; $x <= count($image_folder) -1 ; $x++) {
						//echo '$x = '.$x;
						$variante = "";					
						if ($this->postClean('sizevar',TRUE)=="on") {
							$variante .= ";".$tam[$x];
						}
						if ($this->postClean('colorvar',TRUE)=="on") {
							$variante .= ";".$cor[$x];
						}
						if ($this->postClean('voltvar',TRUE)=="on") {
							$variante .= ";".$volt[$x];
						}
						if ($this->postClean('grauvar',TRUE)=="on") {
							$variante .= ";".$grau[$x];
						}
						if ($this->postClean('ladovar',TRUE)=="on") {
							$variante .= ";".$lado[$x];
						}
						$variante = substr($variante,1);
						
						$data_prod['EAN'] = $eanVar[$x];
						$data_prod['prd_principal_id'] = $prd_cat_id;
						$data_prod['name'] = $variante;
						$data_prod['variant_id'] = $x;
						$data_prod['image'] = $image_folder[$x];
						$upload_image = $image_folder[$x];

						if (!$product_data['is_on_bucket']) {
							$targetDir = FCPATH . 'assets/images/catalog_product_image/';
							$targetDir .= $upload_image;
							if (!file_exists($targetDir)) {
								// cria o diretorio para o produto receber as imagens 
								@mkdir($targetDir);
							}
							$fotos = scandir(FCPATH . 'assets/images/catalog_product_image/' . $upload_image);
							$data_prod['principal_image'] = '';
							foreach ($fotos as $foto) {
								if (($foto != ".") && ($foto != "..") && ($foto != "")) {
									$data_prod['principal_image'] = base_url('assets/images/catalog_product_image/' . $upload_image) . '/' . $foto;
									break;
								}
							}
						} else {
							$fotos = $this->bucket->getFinalObject('assets/images/catalog_product_image/' . $upload_image);
							foreach ($fotos['contents'] as $foto) {
								if ($foto['url'] != "") {
									$data_prod['principal_image'] = $foto['url'];
									break;
								}
							}
						}

						if (key_exists($x,$variant_id)) {
							if ($variant_id[$x] !== '') {
								//echo "Acertando ". print_r($data_prod,true);
								$createvar = $this->model_products_catalog->update($data_prod,$variant_id[$x], $this->postClean('catalogs',TRUE));
							}
							else {
								//echo "Criando ". print_r($data_prod,true);
								$createvar = $this->model_products_catalog->create($data_prod, $this->postClean('catalogs',TRUE));
							}
						}
						else {
							//echo "Criando ". print_r($data_prod,true);
							$createvar = $this->model_products_catalog->create($data_prod, $this->postClean('catalogs',TRUE));
						}
						
					}

				}	

                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
				if ($disable_variation_check) {
					redirect("catalogProducts/index", 'refresh');
				}
                redirect("catalogProducts/attributes/edit/$product_id/$category_id", 'refresh');
        	}
        	else {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                redirect('catalogproducts/create', 'refresh');
        	}
        }
        else {
			
            // attributes
            $attribute_data = $this->model_attributes->getActiveAttributeData('products');
            
            $attributes_final_data = array();
            foreach ($attribute_data as $k => $v) {
                $attributes_final_data[$k]['attribute_data'] = $v;
                
                $value = $this->model_attributes->getAttributeValueData($v['id']);
                
                $attributes_final_data[$k]['attribute_value'] = $value;
            }
            
            $this->data['attributes'] = $attributes_final_data;
            $this->data['brands'] = $this->model_brands->getActiveBrands();
            $this->data['category'] = $this->model_category->getActiveCategroy();   
			$this->data['catalogs'] = $this->model_catalogs->getMyCatalogs(); 

			$product_variants = array();
			if ($product_data['has_variants']!="") {
				$tipos_variacao = explode(";",strtoupper($product_data['has_variants'])); 
                $product_variants = $this->model_products_catalog->getProductCatalogVariants($product_id);
            }
		
			$linkcatalogs = array();  // substituir por consulta que mostar os produtos que usam este produto de catálogo
			$this->data['linkcatalogs'] = $this->model_products_catalog->getCatalogsStoresDataByProductCatalogId($product_id);
			
            $this->data['product_variants'] = $product_variants;

			// Pega os atributos da Mercado Livre
            $arr = $this->pegaCamposMKTdaMinhaCategoria(preg_replace("/[^0-9]/", "", $product_data['category_id']),'ML');
            $campos_att = $arr[0];
			$ignore = array('BRAND','EAN','GTIN','SELLER_SKU','EXCLUSIVE_CHANNEL','ITEM_CONDITION');  
			if ($product_data['has_variants']!="") {
				 $tipos_variacao = explode(";",strtoupper($product_data['has_variants'])); 
			}
            $fieldsML = [];
            foreach ($campos_att as $campo_att) {
            	if ($product_data['has_variants']!="") {
            		if 	(in_array(strtoupper($campo_att['nome']), $tipos_variacao)) { // ignora os atributos que estão na variação. 
						$ignore[]= $campo_att['id_atributo']; 
					}
				}
                in_array($campo_att['id_atributo'], $ignore) ? '' : array_push($fieldsML, $campo_att);
            }

			// Agora pego os campos da Via Varejo
			$arr = $this->pegaCamposMKTdaMinhaCategoria(preg_replace("/[^0-9]/", "", $product_data['category_id']),'VIA');
			$campos_att = $arr[0];
			$ignoreVia = array('SELECIONE','GARANTIA'); 
			$fieldsVia = [];
	        foreach ($campos_att as $campo_att) {
	        	if ($product_data['has_variants']!="") {
					if (in_array(strtoupper($campo_att['nome']), $tipos_variacao)) { // ignora os atributos que estão na variação. 
						$ignoreVia[]= strtoupper($campo_att['nome']); 
					}
				}
				
	            in_array(strtoupper($campo_att['nome']), $ignoreVia) ? '' : array_push($fieldsVia, $campo_att);
	        }

            // Agora pego os campos da NovoMundo
			$naoachou = empty($fieldsML) && empty($fieldsVia);
			foreach ($this->vtexsellercenters as $sellercenter) {
				// Agora pego os campos da NovoMundo
	            $arr = $this->pegaCamposMKTdaMinhaCategoria(preg_replace("/[^0-9]/", "", $product_data['category_id']), $sellercenter);
				$campos_att = $arr[0];
				$sellercenter = str_replace('&','',$sellercenter);
				${'ignore'.$sellercenter} = array();
				${'fields'.$sellercenter} = array();
	            foreach ($campos_att as $campo_att) {
	                if ($product_data['has_variants'] != "") {
	                    if (in_array(strtoupper($campo_att['nome']), $tipos_variacao)) { // ignora os atributos que estão na variação. 
	                        ${'ignore'.$sellercenter}[] = strtoupper($campo_att['nome']);
	                    }
	                }
	
	                in_array(strtoupper($campo_att['nome']), ${'ignore'.$sellercenter}) ? '' : array_push(${'fields'.$sellercenter}, $campo_att);
	            }
				$naoachou = $naoachou && empty(${'fields'.$sellercenter});
			}

			if ($disable_variation_check) {
				$naoachou= true;
			}
			
            $this->data['show_attributes_button'] = $naoachou;
			
			$comvar = $this->postClean('semvar',TRUE);
			$variacaotamanho = array();
			$variacaocor = array();
			$variacaovoltagem = array();
            $variacaosabor = array();
			$variacaograu = array();
			$variacaolado = array();
			$variacaoean = array();
			$variacaoimagem = array();
			$variacaoid = array();
			if ($comvar != "on") {
                if ($this->postClean('sizevar',TRUE)=="on") {
					$variacaotamanho =  $this->postClean('T',TRUE);
                }
                if ($this->postClean('colorvar',TRUE)=="on") {
					$variacaocor =  $this->postClean('C',TRUE);
                }
                if ($this->postClean('voltvar',TRUE)=="on") {
					$variacaovoltagem =  $this->postClean('V',TRUE);
				}
                if ($this->postClean('saborvar',TRUE)=="on") {
                    $variacaosabor =  $this->postClean('sb',TRUE);
                }
				if ($this->postClean('grauvar',TRUE)=="on") {
                    $variacaograu =  $this->postClean('gr',TRUE);
				}
				if ($this->postClean('ladovar',TRUE)=="on") {
                    $variacaolado =  $this->postClean('ld',TRUE);
				}
				$variacaoean = $this->postClean('EAN_V',TRUE);
				$variacaoimagem = $this->postClean('IMAGEM',TRUE);
				$variacaoid = $this->postClean('IMAGEM',TRUE);
			}
			
			$this->data['variacaotamanho'] =$variacaotamanho; 
			$this->data['variacaocor'] =$variacaocor;
			$this->data['variacaovoltagem'] =$variacaovoltagem;
            $this->data['variacaosabor'] =$variacaosabor;
			$this->data['variacaograu'] =$variacaograu;
			$this->data['variacaolado'] =$variacaolado;
			$this->data['variacaoean'] = $variacaoean;
			$this->data['variacaoimagem'] = $variacaoimagem;
			
			$this->data['product_data'] = $product_data;
			
			$this->data['label_ean']= $label_ean;
			$this->data['verify_ean']= $verify_ean;
			$this->data['invalid_ean']= $invalid_ean;
			$this->data['require_ean']= $require_ean;
            $this->data['identifying_technical_specification'] = $this->identifying_technical_specification;
			$this->data['productssellers']= $this->model_products_catalog->getProductsByProductCatalogId($product_id);
			$this->data['readonlytag'] = false;
			$this->data['stores_filter'] = $this->model_stores->getStoresData();
			$this->data['product_length_name'] = $this->product_length_name ;
        	$this->data['product_length_description'] = $this->product_length_description;
			
            $this->render_template('catalogproducts/edit', $this->data);
        }
    }
    
	public function view($product_id = null)
    {
        if(!in_array('viewProductsCatalog', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

		$disable_variation_check = ($this->model_settings->getStatusbyName('disable_category_variation_check_on_catalog_products') == 1 ? true : false); 
		
		$product_data=$this->model_products_catalog->getProductProductData($product_id);
		
		if (!is_null($product_data['prd_principal_id'])) { // se for editar uma variação, muda para o produto principal
			redirect("catalogProducts/view/".$product_data['prd_principal_id'], 'refresh');
		} 

		$label_ean = $this->model_settings->getValueIfAtiveByName('catalog_products_ean_name');
		if (!$label_ean) {
			$label_ean = $this->lang->line('application_ean');
		}
		$verify_ean = ($this->model_settings->getStatusbyName('catalog_products_verify_ean') == 1);

		$require_ean = ($this->model_settings->getStatusbyName('catalog_products_require_ean') == 1);

		$invalid_ean = $this->model_settings->getValueIfAtiveByName('catalog_products_error_ean');
		if (!$invalid_ean) {
			$invalid_ean= $this->lang->line('application_invalid_ean');
		}

        //Origem do produto
        $this->data['origins'] = array(
            0 => $this->lang->line("application_origin_product_0"),
            1 => $this->lang->line("application_origin_product_1"),
            2 => $this->lang->line("application_origin_product_2"),
            3 => $this->lang->line("application_origin_product_3"),
            4 => $this->lang->line("application_origin_product_4"),
            5 => $this->lang->line("application_origin_product_5"),
            6 => $this->lang->line("application_origin_product_6"),
            7 => $this->lang->line("application_origin_product_7"),
            8 => $this->lang->line("application_origin_product_8"),
        );
			
        // attributes
        $attribute_data = $this->model_attributes->getActiveAttributeData('products');
        
        $attributes_final_data = array();
        foreach ($attribute_data as $k => $v) {
            $attributes_final_data[$k]['attribute_data'] = $v;
            
            $value = $this->model_attributes->getAttributeValueData($v['id']);
            
            $attributes_final_data[$k]['attribute_value'] = $value;
        }
        
        $this->data['attributes'] = $attributes_final_data;
        $this->data['brands'] = $this->model_brands->getActiveBrands();
        $this->data['category'] = $this->model_category->getActiveCategroy();   
		$this->data['catalogs'] = $this->model_catalogs->getActiveCatalogs(); 

		$product_variants = array();
		if ($product_data['has_variants']!="") {
			$tipos_variacao = explode(";",strtoupper($product_data['has_variants'])); 
            $product_variants = $this->model_products_catalog->getProductCatalogVariants($product_id);
        }
	
		$linkcatalogs = array();  // substituir por consulta que mostar os produtos que usam este produto de catálogo
		$this->data['linkcatalogs'] = $this->model_products_catalog->getCatalogsStoresDataByProductCatalogId($product_id);
		
        $this->data['product_variants'] = $product_variants;

		// Pega os atributos da Mercado Livre
        $arr = $this->pegaCamposMKTdaMinhaCategoria(preg_replace("/[^0-9]/", "", $product_data['category_id']),'ML');
        $campos_att = $arr[0];
		$ignore = array('BRAND','EAN','GTIN','SELLER_SKU','EXCLUSIVE_CHANNEL','ITEM_CONDITION');  
		if ($product_data['has_variants']!="") {
			 $tipos_variacao = explode(";",strtoupper($product_data['has_variants'])); 
		}
        $fieldsML = [];
        foreach ($campos_att as $campo_att) {
        	if ($product_data['has_variants']!="") {
        		if 	(in_array(strtoupper($campo_att['nome']), $tipos_variacao)) { // ignora os atributos que estão na variação. 
					$ignore[]= $campo_att['id_atributo']; 
				}
			}
            in_array($campo_att['id_atributo'], $ignore) ? '' : array_push($fieldsML, $campo_att);
        }

		// Agora pego os campos da Via Varejo
		$arr = $this->pegaCamposMKTdaMinhaCategoria(preg_replace("/[^0-9]/", "", $product_data['category_id']),'VIA');
		$campos_att = $arr[0];
		$ignoreVia = array('SELECIONE','GARANTIA'); 
		$fieldsVia = [];
        foreach ($campos_att as $campo_att) {
        	if ($product_data['has_variants']!="") {
				if (in_array(strtoupper($campo_att['nome']), $tipos_variacao)) { // ignora os atributos que estão na variação. 
					$ignoreVia[]= strtoupper($campo_att['nome']); 
				}
			}
			
            in_array(strtoupper($campo_att['nome']), $ignoreVia) ? '' : array_push($fieldsVia, $campo_att);
        }
		
		// Agora pego os campos da NovoMundo
		$naoachou = empty($fieldsML) && empty($fieldsVia);
		foreach ($this->vtexsellercenters as $sellercenter) {
			// Agora pego os campos da NovoMundo
            $arr  = $this->pegaCamposMKTdaMinhaCategoria(preg_replace("/[^0-9]/", "", $product_data['category_id']), $sellercenter);
			$campos_att = $arr[0];
			$sellercenter = str_replace('&','',$sellercenter);
			${'ignore'.$sellercenter} = array();
			${'fields'.$sellercenter} = array();
            foreach ($campos_att as $campo_att) {
                if ($product_data['has_variants'] != "") {
                    if (in_array(strtoupper($campo_att['nome']), $tipos_variacao)) { // ignora os atributos que estão na variação. 
                        ${'ignore'.$sellercenter}[] = strtoupper($campo_att['nome']);
                    }
                }

                in_array(strtoupper($campo_att['nome']), ${'ignore'.$sellercenter}) ? '' : array_push(${'fields'.$sellercenter}, $campo_att);
            }
			$naoachou = $naoachou && empty(${'fields'.$sellercenter});
		}
		
		if ($disable_variation_check) {
			$naoachou= true;
		}
		

        $this->data['show_attributes_button'] = $naoachou;
		
		$comvar = $this->postClean('semvar',TRUE);
		$variacaotamanho = array();
		$variacaocor = array();
		$variacaovoltagem = array();
        $variacaosabor = array();
		$variacaograu = array();
		$variacaolado = array();
		$variacaoean = array();
		$variacaoimagem = array();
		$variacaoid = array();
		if ($comvar != "on") {
            if ($this->postClean('sizevar',TRUE)=="on") {
				$variacaotamanho =  $this->postClean('T',TRUE);
            }
            if ($this->postClean('colorvar',TRUE)=="on") {
				$variacaocor =  $this->postClean('C',TRUE);
            }
            if ($this->postClean('voltvar',TRUE)=="on") {
				$variacaovoltagem =  $this->postClean('V',TRUE);
			}
            if ($this->postClean('saborvar',TRUE)=="on") {
                $variacaosabor =  $this->postClean('sb',TRUE);
            }
			if ($this->postClean('grauvar',TRUE)=="on") {
                $variacaograu =  $this->postClean('gr',TRUE);
			}
			if ($this->postClean('ladovar',TRUE)=="on") {
                $variacaolado =  $this->postClean('ld',TRUE);
			}
			$variacaoean = $this->postClean('EAN_V',TRUE);
			$variacaoimagem = $this->postClean('IMAGEM',TRUE);
			$variacaoid = $this->postClean('IMAGEM',TRUE);
		}
		
		$this->data['variacaotamanho'] =$variacaotamanho; 
		$this->data['variacaocor'] =$variacaocor;
		$this->data['variacaovoltagem'] =$variacaovoltagem;
        $this->data['variacaosabor'] =$variacaosabor;
		$this->data['variacaograu'] =$variacaograu;
		$this->data['variacaolado'] =$variacaolado;
		$this->data['variacaoean'] = $variacaoean;
		$this->data['variacaoimagem'] = $variacaoimagem;
		
		$this->data['product_data'] = $product_data;
		
		$this->data['label_ean']= $label_ean;
		$this->data['verify_ean']= $verify_ean;
		$this->data['invalid_ean']= $invalid_ean;
		$this->data['require_ean']= $require_ean;
		
		$this->data['productssellers']= $this->model_products_catalog->getProductsByProductCatalogId($product_id);

        $this->data['identifying_technical_specification'] = $this->identifying_technical_specification;
		$this->data['readonlytag'] = true;
		$this->data['stores_filter'] = $this->model_stores->getStoresData();
		$this->data['product_length_name'] = $this->product_length_name ;
        $this->data['product_length_description'] = $this->product_length_description;
		
        $this->render_template('catalogproducts/edit', $this->data);
    }
	
	public function index() {
		if(!in_array('viewProductsCatalog', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
		$label_ean = $this->model_settings->getValueIfAtiveByName('catalog_products_ean_name');
		if (!$label_ean) {
			$label_ean = $this->lang->line('application_ean');
		}
		$this->data['show_ref_id'] = $this->model_settings->getStatusbyName('show_ref_id');
		
		$this->data['catalogs'] = $this->model_catalogs->getMyCatalogs(); 
		$this->data['label_ean'] = $label_ean;
		$this->render_template('catalogproducts/index', $this->data);	
	}
	
	public function fetchProductsCatalogData(): CI_Output
    {
        $draw   = $this->postClean('draw');
        $result = array();

        try {
            $filters        = array();
            $filter_default = array();

            $show_ref_id = $this->model_settings->getStatusbyName('show_ref_id');
            $postdata = $this->postClean();

            $filter_default[]['where']['p.prd_principal_id'] = null;

            if ($show_ref_id == 1) {
                $fields_order = array('','p.EAN','p.ref_id','b.name','p.name','CAST(p.price AS DECIMAL(12,2))','p.id','p.status');
            } else {
                $fields_order = array('','p.EAN','b.name','p.name','CAST(p.price AS DECIMAL(12,2))','p.id','p.status');
            }

            $mycatalogs = $this->model_catalogs->getMyCatalogs();
            if (is_array($postdata['catalog'])) {
                $catalogs = $postdata['catalog'];
            } else {
                $catalogs = array();
                foreach($mycatalogs as $catalog) {
                    $catalogs[] = $catalog['id'];
                }
            }
            if (count($catalogs) > 0) {
                $catalogs_filter = array_map(function($catalog){
                    return "catalog_id = $catalog";
                }, $catalogs);
                $filters[]['where']['escape'] = "p.id IN (SELECT product_catalog_id FROM catalogs_products_catalog WHERE product_catalog_id = p.id AND (" . implode(' OR ', $catalogs_filter) . '))';
            } else {
                $filters[]['where']['escape'] = "FALSE";
            }

            if (trim($postdata['ean'])) {
                $filters[]['like']['p.EAN'] = $postdata['ean'];
            }
            if (trim($postdata['nome'])) {
                $filters[]['like']['p.name'] = $postdata['nome'];
            }
            if (trim($postdata['marca'])) {
                $filters[]['like']['b.name'] = $postdata['marca'];
            }

            if ($postdata['status']) {
                $filters[]['where']['p.status'] = $postdata['status'];
            } else {
                $deletedStatus = Model_products::DELETED_PRODUCT;
                $filters[]['where']['p.status !='] = [$deletedStatus];
            }

            if ($show_ref_id == 1 && trim($postdata['refid'])) {
                $filters[]['like']['p.ref_id'] = $postdata['refid'];
            }

            $query = array();
            $query['select'][] = "p.status, p.id, p.name, p.principal_image, p.principal_image, p.EAN, p.ref_id, p.price, b.name as brandname";

            $query['from'][] = 'products_catalog p';
            $query['join'][] = ["brands b", "p.brand_id = b.id"];

            $data = fetchDataTable(
                $query,
                array('p.id', 'DESC'),
                null,
                null,
                ['viewProductsCatalog'],
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

        foreach ($data['data'] as $value) {
			// button
			switch ($value['status']) {
                case 1: 
                    $status =  '<span class="label label-success">'.$this->lang->line('application_active').'</span>';
                    break;
                case 4:
                    $status = '<span class="label label-danger">'.$this->lang->line('application_duplicated').'</span>';
                    break;
				default:
                    $status = '<span class="label label-danger">'.$this->lang->line('application_inactive').'</span>';
                    break;
			}
			
			$link_id = "<a href='".base_url('catalogProducts/update/'.$value['id'])."'>".$value['name']."</a>";
            $image_src = (!is_null($value['principal_image'])) && ($value['principal_image'] != '') ? $value['principal_image'] : base_url('assets/images/system/sem_foto.png');
            $img = '<img src="'.$image_src.'" alt="'.utf8_encode(substr($value['name'],0,20)).'" class="img-rounded" width="50" height="50" />';

			if ($show_ref_id == 1) {
                $result[] = array(
					$img,
                    $value['EAN'],
					$value['ref_id'],
					$value['brandname'],
                    $link_id,
					$this->formatprice($value['price']),
					$value['id'],
					$status,
				);
			} else {
                $result[] = array(
					$img,
                    $value['EAN'],
					$value['brandname'],
                    $link_id,
					$this->formatprice($value['price']),
					$value['id'],
					$status,
				);
			}
	
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

	public function showcase() {
		if(!in_array('showcaseCatalog', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
		$label_ean = $this->model_settings->getValueIfAtiveByName('catalog_products_ean_name');
		if (!$label_ean) {
			$label_ean = $this->lang->line('application_ean');
		}

        $this->data['collections'] = $this->model_catalogs->getCollections();
        $this->data['identifying_technical_specification'] = $this->identifying_technical_specification;
		$this->data['catalogs'] = $this->model_catalogs->getMyCatalogs(); 
		$this->data['label_ean'] = $label_ean;
		$this->render_template('catalogproducts/showcase', $this->data);	
	}
	
	public function fetchShowCaseData()
	{

        $draw   = $this->postClean('draw');
        $result = array();

        try {
            $filters        = array();
            $filter_default = array();

            $postdata = $this->postClean();
            $identifying_technical_specification = $this->identifying_technical_specification;
            $attribute = null;
            if ($identifying_technical_specification && $identifying_technical_specification['status'] == 1) {
                if ($postdata['colecoes']) {
                    $attribute = $postdata['colecoes'];
                }
            }

            $filter_default[]['where']['p.prd_principal_id'] = null;
            $filter_default[]['where']['p.status'] = 1;
            $filter_default[]['where']['b.active'] = 1;

            if ($this->data['usercomp'] == 1) { // administrador le todos os catalogos ativos
                $catalogs = "SELECT id FROM catalogs WHERE status=1 ";
            } elseif (($this->data['userstore'] == 0)) { // pego todos os catálogos das minhas lojas
                $catalogs = "SELECT id FROM catalogs WHERE status=1 AND id in (SELECT catalog_id FROM catalogs_stores WHERE store_id IN (SELECT id FROM stores WHERE company_id = ".$this->data['usercomp'].")) ";
            } else {
                $catalogs = "SELECT id FROM catalogs WHERE status=1 AND id in (SELECT catalog_id FROM catalogs_stores WHERE store_id = ".$this->data['userstore'].") ";
            }
            $filters[]['where']['escape'] = "p.id IN (SELECT product_catalog_id FROM catalogs_products_catalog WHERE product_catalog_id = p.id AND catalog_id in ($catalogs))";

            if ($attribute) {
                $attributes = implode('","', $attribute);
                $filters[]['where_in']['c.attribute_value'] = $attributes;
            }

            if (is_array($postdata['catalog'])) {
                if (is_array($postdata['catalog'])) {
                    $catalogs = $postdata['catalog'];
                }

                $catalogs_filter = array_map(function($catalog){
                    return "catalog_id = $catalog";
                }, $catalogs);
                $filters[]['where']['escape'] = "p.id IN (SELECT product_catalog_id FROM catalogs_products_catalog WHERE product_catalog_id = p.id AND (" . implode(' OR ', $catalogs_filter) . '))';
                $filters[]['where_in']['cpc.catalog_id'] = $catalogs;
            }
            if (trim($postdata['ean'])) {
                $filters[]['like']['p.EAN'] = $postdata['ean'];
            }
            if (trim($postdata['nome'])) {
                $filters[]['like']['p.name'] = $postdata['nome'];
            }
            if (trim($postdata['brand'])) {
                $filters[]['where']['escape'] = "p.brand_id IN ( SELECT id FROM brands WHERE name like '%$postdata[brand]%' )";
            }

            $fields_order = array('','p.EAN','p.name','b.name','CAST(p.price AS DECIMAL(12,2))','p.id','p.status');

            $query = array();
            $query['select'][] = "c.attribute_value, p.EAN, p.status, p.id, p.principal_image, p.name, b.name as brand, c.name as catalog_name, p.price";

            $query['from'][] = 'products_catalog p';
            $query['join'][] = ["brands b", "p.brand_id = b.id"];
            $query['join'][] = ["catalogs_products_catalog cpc", "cpc.product_catalog_id = p.id"];
            $query['join'][] = ["catalogs c", "c.id = cpc.catalog_id"];

            $data = fetchDataTable(
                $query,
                array('p.id', 'DESC'),
                null,
                null,
                ['showcaseCatalog'],
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
		// leio minhas lojas 
		$myStores = $this->model_stores->getActiveStore();

        foreach ($data['data'] as $key => $value) {
            $collection = $value['attribute_value'] ?? '-';

			switch ($value['status']) {
                case 1: 
                    $status =  '<span class="label label-success">'.$this->lang->line('application_active').'</span>';
                    break;
                default:
                    $status = '<span class="label label-danger">'.$this->lang->line('application_inactive').'</span>';
                    break;
			}
			
			$link_id = "<a href='".base_url('catalogProducts/view/'.$value['id'])."'>".$value['EAN']."</a>";
            $image_src = (!is_null($value['principal_image'])) && ($value['principal_image'] != '') ? $value['principal_image'] : base_url('assets/images/system/sem_foto.png');
            $img = '<img src="'.$image_src.'" alt="'.utf8_encode(substr($value['name'],0,20)).'" class="img-rounded" width="50" height="50" />';
			$action = '';
			
			if (count($myStores) == 1) {
				$prodCatExist= $this->model_products_catalog->getProductByProductCatalogIdAndStoreId($value['id'],$myStores[0]['id']);
                if  (!$prodCatExist) {
                    $action =  '<a href="'.base_url("catalogProducts/createFromCatalog/$value[id]").'" class="btn btn-primary">'.$this->lang->line('application_add_product').'</a>';
                } else {
                    $action =  '<a href="'.base_url("catalogProducts/updateFromCatalog/$prodCatExist[id]").'" class="btn btn-primary">'.$this->lang->line('application_view_product').'</a>';
                }
			}
			else {
				$cnt = 0 ;
				foreach($myStores as $myStore) {
					$prodCatExist= $this->model_products_catalog->getProductByProductCatalogIdAndStoreId($value['id'],$myStore['id']);
					if  ($prodCatExist) {
						$cnt++;
					}
				}
				if (in_array('showcaseCatalog',  $this->permission))  {
					if  ($cnt != count($myStores)) {
		        		$action =  '<a href="'.base_url('catalogProducts/createFromCatalog/'.$value['id']).'" class="btn btn-primary">'.$this->lang->line('application_add_product').'</a>';
					} else {
						$action =  '<span class="label label-warning">'.$this->lang->line('application_all_your_stores_has_this_product').'</span>';
					}
				}
			}
            $result[$key] = array(
				$img, 
				$link_id,
				$value['name'],
				$value['brand'],
				$value['catalog_name'],
				$this->formatprice($value['price']),
				$value['id'],
				$status,
				$action
			);

            if ($identifying_technical_specification && $identifying_technical_specification['status'] == 1) {
                array_splice($result[$key], 7, 0, $collection);
            }

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
	
	public function orderimages()
    {
    	if(!in_array('createProductsCatalog', $this->permission)  &&  !in_array('updateProductsCatalog', $this->permission))  {
            redirect('dashboard', 'refresh');
        }

		$path = 'assets/images/product_image/';
		$parameters = $this->postClean('params');
		$stacks = $parameters['stack'];
		$folders = explode('/', $stacks['0']['key']);
		$mainFolder = $folders['0'];
		if (!$this->postClean('onBucket')) {
			$path = substr($_SERVER['SCRIPT_FILENAME'], 0, strpos($_SERVER['SCRIPT_FILENAME'], 'index.php')) . 'assets/images/product_image/';
			$folderFiles = scandir($path . $mainFolder);
			foreach ($folderFiles as $file) {
				if ($file != '.' && $file != '..') {
					foreach ($stacks as $key => $stack) {
						$folderAndFile = explode('/', $stack['key']);
						if (substr($file, 1) == substr($folderAndFile['1'], 1)) {
							$newFile = $key . substr($folderAndFile['1'], 1);
							$result = rename($path . $folderAndFile['0'] . '/' . $file, $path . $folderAndFile['0'] . '/' . $newFile);
						}
					}
				}
			}
		} else {
			// Percorre cada stack.
            foreach ($stacks as $key => $stack) {
                // Verifica se o objeto existe.
                if ($this->bucket->objectExists($path . $stack['key'])) {
                    // Monta o novo nome da imagem como o nome anterior, alterando a primeira letra para ordenar.
                    $image_name = $key . substr(basename($stack['key']), 1);
                    $dir = dirname($stack['key']);
                    $this->bucket->renameObject($path . $stack['key'], $path . $dir . '/' . $image_name);
                }
            }
		}
    }

	public function checkMinValue($field, $min) {
		if ((int)$field < (int)$min) {
			$this->form_validation->set_message('checkMinValue', '%s não pode ser menor que "'.$min.'"');
			return FALSE;
		}
		return true;
	}
    
    public function checkEan($ean) {
        return $this->model_products->ean_check($ean);
    }
	
	public function checkUniqueEan($ean, $id = null) {

        $require_ean = $this->model_settings->getStatusbyName('catalog_products_require_ean') == 1;

        if (!$require_ean && empty($ean))
            return true;

		if ($this->model_settings->getStatusbyName('catalog_products_allow_ean_duplicate') == 1)
			return true;

		if (!$this->model_products_catalog->VerifyEanUnique($ean, $id)) {
			$label_ean = $this->model_settings->getValueIfAtiveByName('catalog_products_ean_name');
			if (!$label_ean) {
				$label_ean = $this->lang->line('application_ean');
			}
			
			$this->form_validation->set_message('checkUniqueEan', $label_ean.' '.$ean.' já existe.');
			return FALSE;
		}
		return true;
	}
	
	public function checkFotos($upload_image) {
		$temImagem =false;
		$fotos = $this->bucket->getFinalObject('assets/images/product_image/' . $upload_image);
		if(!$fotos['contents']){
			$fotos = $this->bucket->getFinalObject('assets/images/catalog_product_image/' . $upload_image);
		}
	    foreach($fotos['contents'] as $foto) {
	        if ($foto['url']!="") {
	        	$temImagem = true;
	        	break;
	        }
	    }
		if (!$temImagem) {
			$this->form_validation->set_message('checkFotos', 'Faça upload de, pelo menos, uma imagem jpg com tamanho em pixels entre 800x800 e 1200x1200 px e com tamanho de arquivo < 700 MB.');
		}
		return $temImagem;
	} 
	
	public function checkEANpost()
    {
        ob_start();
    	if(!in_array('createProductsCatalog', $this->permission) && !in_array('updateProductsCatalog', $this->permission)  ) {
            redirect('dashboard', 'refresh');
        }
		
     	$ean = $this->postClean('ean');
		$product_id = $this->postClean('product_id');
		$verify_ean = ($this->model_settings->getStatusbyName('catalog_products_verify_ean') == 1);
		$msg = '';
		$label_ean = $this->model_settings->getValueIfAtiveByName('catalog_products_ean_name');
		$require_ean = ($this->model_settings->getStatusbyName('catalog_products_require_ean') == 1);
		
		if (($require_ean) && (trim($ean) =='')) {
			$msg = $this->lang->line('application_invalid_ean');
			ob_clean();
			echo json_encode(array('success' => false, 'message' => 'O campo '.$label_ean.' é obrigatório.'));
			return ;
		}
		
		if ($verify_ean) {
			$ok = $this->checkEan($ean); 
			if (!$ok) {  // verifica se a formataçao do ean é invalida e já retorna erro. 
				$msg = $this->lang->line('application_invalid_ean');
				ob_clean();
				echo json_encode(array('success' => $ok, 'message' => $msg));
				return ;
			}	
		}
		if ($product_id == '0') { $product_id = null; }
		
		$ok  = $this->model_products_catalog->VerifyEanUnique($ean, $product_id);
		if (!$ok) {// verifico se está repetido 
			
			if (!$label_ean) {
				$label_ean = $this->lang->line('application_ean');
			}
			$msg =  $label_ean.' '.$ean.' já existe ';
		}
		ob_clean();
		echo json_encode(array('success' => $ok, 'message' => $msg));
		return; 
		
    }

	public function getImages()
	{
		ob_start();
		if (!in_array('createProductsCatalog', $this->permission) && !in_array('updateProductsCatalog', $this->permission)) {
			redirect('dashboard', 'refresh');
		}
		$upload_image = $this->postClean('tokenimagem');

		$isOnBucket = $this->postClean('onBucket');
		$numft = 0;
		$ln1 = array();
		$ln2 = array();
		if (!$isOnBucket) {
			$fotos = scandir(FCPATH . 'assets/images/catalog_product_image/' . $upload_image);
			foreach ($fotos as $foto) {
				if (($foto != ".") && ($foto != "..") && ($foto != "")) {
					$numft++;
					array_push($ln1, base_url('assets/images/catalog_product_image/' . $upload_image . '/' . $foto));
					array_push($ln2, ['width' => "120px", 'key' => $upload_image . '/' . $foto]);
				}
			}
		} else {
			$fotos = $this->bucket->getFinalObject('assets/images/catalog_product_image/' . $upload_image);
			foreach ($fotos['contents'] as $foto) {
				if ($foto['key'] != "") {
					$numft++;
					array_push($ln1, base_url('assets/images/catalog_product_image/' . $upload_image . '/' . $foto));
					array_push($ln2, ['width' => "120px", 'key' => $upload_image . '/' . $foto]);
				}
			}
		}
		ob_end_clean();
		echo json_encode(array('success' => true, 'ln1' => $ln1, 'ln2' => $ln2));
	}
	
	    /**
     * Remove uma imagem de um produto no bucket.
     * Utilizado apenas para bucket, se for no local é utilizado um plugin.
     */
    public function removeImageProduct(){
		ob_start();
        if (!in_array('createProduct', $this->permission)  &&  !in_array('updateProducts', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $path = 'assets/images/catalog_product_image/';
        $key = $this->postClean('key');
        if($key){
            $path.= $key;
            $this->bucket->deleteObject($path);
        }
		ob_end_clean();

         echo json_encode(array());
    }
	
	public function attributes($action = 'create', $product_id = null, $category_id = null)
    {
        if ($product_id == null || $category_id == null) {
            redirect('catalogProducts', 'refresh');
        }
		
		if ($action == 'view'){
			if(!in_array('viewProductsCatalog', $this->permission)) {
				redirect('dashboard', 'refresh');
			}
		}
		else if(!in_array('createProductsCatalog', $this->permission) && !in_array('updateProductsCatalog', $this->permission)  ) {
            redirect('dashboard', 'refresh');
        }
		
		$product_data = $this->model_products_catalog->getProductProductData($product_id);
		if(!$product_data) {
			redirect('dashboard', 'refresh');
		}
		
        $arr = $this->pegaCamposMKTdaMinhaCategoria($category_id,'ML');
        $campos_att = $arr[0];
		// PARA TESTAR, UTILIZAR A CATEGORIA 2093 (UTILIDADES DOMÉSTICAS > CONJUNTO DE PANELAS > CONJUNTO DE PANELAS DE CERÂMICA)

		$tipos_variacao = explode(";", strtoupper($product_data['has_variants']));

        // se alterar esta lista aqui, lembre-se de alterar em BatchC/MLLeilao e MLSyncProducts
        $ignoreML = array('BRAND','EAN','GTIN','SELLER_SKU','EXCLUSIVE_CHANNEL','ITEM_CONDITION');  	
		if ($product_data['has_variants']!="") {
			$tipos_variacao = explode(";",strtoupper($product_data['has_variants'])); 
		}
        $fieldsML = [];
        foreach ($campos_att as $campo_att) {
        	if ($product_data['has_variants']!="") {
				if (in_array(strtoupper($campo_att['nome']), $tipos_variacao)) { // ignora os atributos que estão na variação. 
					$ignoreML[]= $campo_att['id_atributo']; 
				}
			}
            in_array($campo_att['id_atributo'], $ignoreML) ? '' : array_push($fieldsML, $campo_att);
        }
        
        // Agora pego os campos da Via Varejo
		$arr = $this->pegaCamposMKTdaMinhaCategoria($category_id,'VIA');
		$campos_att = $arr[0];
		$ignoreVia = array('SELECIONE','GARANTIA'); 
		$fieldsVia = [];
        foreach ($campos_att as $campo_att) {
        	if ($product_data['has_variants']!="") {
				if (in_array(strtoupper($campo_att['nome']), $tipos_variacao)) { // ignora os atributos que estão na variação. 
					$ignoreVia[] = strtoupper($campo_att['nome']); 
				}
			}
			
            in_array(strtoupper($campo_att['nome']), $ignoreVia) ? '' : array_push($fieldsVia, $campo_att);
        }
		
		// Agora pego os campos da Novo Mundo
        $naoachou = (empty($fieldsML)) && (empty($fieldsVia));
		foreach ($this->vtexsellercenters as $sellercenter) {
			$arr = $this->pegaCamposMKTdaMinhaCategoria($category_id, $sellercenter);
			$campos_att = $arr[0];
			$sellercenter = str_replace('&','',$sellercenter);
	        ${'ignore'.$sellercenter} = array();
			${'fields'.$sellercenter} = array();
	        // foreach ($campos_att as $campo_att) {
	        //     if (($campo_att['obrigatorio'] == 1) && ($campo_att['variacao'] == 1)) { // se deveria ser uma variação, tb não mostro na segunda tela 
	        //         ${'ignore'.$sellercenter}[] = strtoupper($campo_att['nome']);
	        //     } elseif ($product_data['has_variants'] != "") {
	        //         if (in_array(strtoupper($campo_att['nome']), $tipos_variacao)) { // ignora os atributos que estão na variação. 
	        //             ${'ignore'.$sellercenter}[] = strtoupper($campo_att['nome']);
	        //         }
	        //     }
	
	        //     in_array(strtoupper($campo_att['nome']), ${'ignore'.$sellercenter}) ? '' : array_push(${'fields'.$sellercenter}, $campo_att);
			// }
			
			foreach ($campos_att as $campo_att) 
            {
                if (($campo_att['obrigatorio'] == 1) && ($campo_att['variacao'] == 1)) 
                { 
					$verify_field = $campo_att['nome'];
					if (strtoupper($verify_field) == strtoupper($this->variant_color)) {
						$verify_field = self::COLOR_DEFAULT;
                    }
                    if (strtoupper($verify_field) == strtoupper($this->variant_size)) {
						$verify_field= self::SIZE_DEFAULT;
                    }
                    if (strtoupper($verify_field) == strtoupper($this->variant_voltage)) {
						$verify_field = self::VOLTAGE_DEFAULT;
                    }

                    if (!in_array(strtoupper($verify_field), $tipos_variacao)) {
                        array_push(${'fields' . $sellercenter}, $campo_att);
                    }
                    if ($product_data['has_variants'] != "") {
                        // se deveria ser uma variação, tb não mostro na segunda tela
                        if (in_array(strtoupper($verify_field), $tipos_variacao)) {
                            ${'ignore' . $sellercenter}[] = strtoupper($campo_att['nome']);
                        }
                    }
                } 
                elseif ($product_data['has_variants'] != "") 
                {
                    if (in_array(strtoupper($campo_att['nome']), $tipos_variacao)) {
                        ${'ignore' . $sellercenter}[] = strtoupper($campo_att['nome']);
                    }
                }

                if (!in_array(strtoupper($campo_att['nome']), ${'ignore' . $sellercenter})) {
                    $can_add = true;
                    foreach (${'fields' . $sellercenter} as $field) {
                        if (strtoupper($campo_att['nome']) == strtoupper($field['nome']))
                            $can_add = false;
                    }
                    if ($can_add) { 
                        array_push(${'fields' . $sellercenter}, $campo_att);
                    }
                }
                // in_array(strtoupper($campo_att['nome']), ${'ignore' . $sellercenter}) ? '' : array_push(${'fields' . $sellercenter}, $campo_att);
            }

			$naoachou = $naoachou && empty(${'fields'.$sellercenter});
		}
		
		if ($action != 'view'){
			if (($naoachou)) {
	            $this->model_products_catalog->deleteAttributes($product_id);
	            redirect('catalogProducts', 'refresh');
	        }
		}
        // Obrigatórios do Mercado Livre
        if ($this->postClean('obrigML[]',TRUE) != null) {
            foreach ($this->postClean('obrigML[]') as $key => $obrig) {
                if ($obrig == '1') {
                    //$this->form_validation->set_rules("valorML[$key]", $this->lang->line('application_'.strtolower($this->postClean("id_atributoML[$key]"))), 'trim|required');
                	$this->form_validation->set_rules("valorML[$key]", $this->postClean("nomeML[$key]",TRUE), 'trim|required');
                } else {
                    //$this->form_validation->set_rules("valorML[$key]", $this->lang->line('application_'.strtolower($this->postClean("id_atributoML[$key]"))), 'trim');
               		$this->form_validation->set_rules("valorML[$key]",  $this->postClean("nomeML[$key]",TRUE), 'trim');
                }
            }
        }
		
		// Obrigatórios da Via Varejo
		if ($this->postClean('obrigVia[]',TRUE) != null) {
            foreach ($this->postClean('obrigVia[]') as $key => $obrig) {
                if ($obrig == '1') {
                    $this->form_validation->set_rules("valorVia[$key]", $this->postClean("nomeVia[$key]",TRUE), 'trim|required');
                } else {
                    $this->form_validation->set_rules("valorVia[$key]", $this->postClean("nomeVia[$key]",TRUE), 'trim');
                }
            }
        }
		
		foreach ($this->vtexsellercenters as $sellercenter) {
			$sellercenter = str_replace('&','',$sellercenter);
	        if ($this->postClean('obrig'.$sellercenter.'[]',TRUE) != null) {
	            foreach ($this->postClean('obrig'.$sellercenter.'[]') as $key => $obrig) {
	                if ($obrig == '1') {
	                    $this->form_validation->set_rules("valor".$sellercenter."[$key]", $this->postClean("nome".$sellercenter."[$key]",TRUE), 'trim|required');
	                } else {
	                    $this->form_validation->set_rules("valor".$sellercenter."[$key]", $this->postClean("nome".$sellercenter."[$key]",TRUE), 'trim');
	                }
	            }
	        }
		}

        if ($this->form_validation->run()) {
        	$this->model_products_catalog->deleteAttributes($product_id);
            
			// Gravo os atributos Mercado livre
			if (!empty($fieldsML)) {
				$atributos = $this->postClean('id_atributoML',TRUE);
	            foreach ($atributos as $key => $atributo) {
	                if ($this->postClean("valorML[$key]",TRUE) != "" && $this->postClean("valorML[$key]",TRUE) != null) {
	                    $data = [
	                        'product_catalog_id'   => $product_id,
	                        'id_atributo' 		   => $this->postClean("id_atributoML[$key]",TRUE),
	                        'valor'                => $this->postClean("valorML[$key]",TRUE),
	                        'int_to'      			=> 'ML',
	                    ];
	                    $this->model_products_catalog->saveProductsCatalogAttributes($data);
	                }
	            }
			}
			// Gravo os atributos Via Varejo
			if  (!empty($fieldsVia)) {
				$atributos = $this->postClean('id_atributoVia',TRUE);
	            foreach ($atributos as $key => $atributo) {
	                if ($this->postClean("valorVia[$key]",TRUE) != "" && $this->postClean("valorVia[$key]",TRUE) != null) {
	                    $data = [
	                        'product_catalog_id'  => $this->postClean("id_product",TRUE),
	                        'id_atributo' 		  => $this->postClean("id_atributoVia[$key]",TRUE),
	                        'valor'               => $this->postClean("valorVia[$key]",TRUE),
	                        'int_to'              => 'VIA',
	                    ];
	                    $this->model_products_catalog->saveProductsCatalogAttributes($data);
	                }
	            }
			}
			// Gravo os atributos NovoMundo
			foreach ($this->vtexsellercenters as $sellercenter) {
            	$sellercenter = str_replace('&','',$sellercenter);
	            if (!empty(${'fields'.$sellercenter})) {
	                $atributos = $this->postClean('id_atributo'.$sellercenter,TRUE);
	                foreach ($atributos as $key => $atributo) {
	                    if ($this->postClean("valor".$sellercenter."[$key]",TRUE) != "" && $this->postClean("valor".$sellercenter."[$key]",TRUE) != null) {
	                        $data = [
	                            'product_catalog_id'  => $this->postClean("id_product",TRUE),
	                            'id_atributo' => $this->postClean("id_atributo".$sellercenter."[$key]",TRUE),
	                            'valor'       => $this->postClean("valor".$sellercenter."[$key]",TRUE),
	                            'int_to'      => $sellercenter,
	                        ];
	                        $this->model_products_catalog->saveProductsCatalogAttributes($data);
	                    }
	                }
	            }
			}
			
            $this->session->set_flashdata('success', $this->lang->line('messages_successfully_created'));
            redirect('catalogProducts', 'refresh');
        }
		
        if (($action == 'edit') || ($action == 'view')) {
            $this->data['attributes'] = $this->model_products_catalog->getAllProductsCatalogAttributes($product_id);
        } else {
            $this->data['attributes'] = '';
        }
		$label_ean = $this->model_settings->getValueIfAtiveByName('catalog_products_ean_name');
		if (!$label_ean) {
			$label_ean = $this->lang->line('application_ean');
		}
		$this->data['label_ean'] = $label_ean;
        $this->data['camposML'] = $fieldsML;
		$this->data['camposVIA'] = $fieldsVia;
		foreach ($this->vtexsellercenters as $sellercenter) {
            $sellercenter = str_replace('&','',$sellercenter);
			$this->data['campos'.$sellercenter] = ${'fields'.$sellercenter};
		}
		$this->data['sellercenters'] = $this->vtexsellercenters; 
		
        $this->data['product']  = $product_id;
        $this->data['category'] = $category_id;
 		$this->data['product_data'] = $product_data;
		$this->data['readonly'] = ($action == 'view');
        $this->render_template('catalogproducts/attributes', $this->data);
    }
	
	function pegaCamposMKTdaMinhaCategoriaOLD($idcat,$int_to)
    {
    	
		$result= $this->model_categorias_marketplaces->getCategoryMktplace($int_to,$idcat);
		$idCatML= $result['category_marketplace_id'];
        $result= $this->model_atributos_categorias_marketplaces->getAtributosCategoriaMKT($idCatML,$int_to);
        
        return $result;
    }

	function pegaCamposMKTdaMinhaCategoria($idcat, $int_to, $idprd = null)
    {
        $result = $this->model_categorias_marketplaces->getCategoryMktplace($int_to, $idcat);
        $idCatML = ($result) ? $result['category_marketplace_id'] : null;
        $enriched = false;
        if ($idprd) {
            $productCategoryMkt = $this->model_products_category_mkt->getCategoryEnriched($idprd, $int_to);
            if ($productCategoryMkt) {
                $idCatML = $productCategoryMkt['category_mkt_id'];
                $enriched = true;
            }
        }
        $category_mkt = $this->model_categorias_marketplaces->getCategoryByMarketplace($int_to, $idCatML);
        $result = $this->model_atributos_categorias_marketplaces->getAtributosCategoriaMKT($idCatML, $int_to);

        return [$result, $category_mkt, $enriched];
    }
	
	public function createFromCatalog($product_catalog_id) {
		
		if ( (!in_array('createProduct', $this->permission)) && (!in_array('createFromCatalog', $this->permission)) ) {
        	redirect('dashboard', 'refresh');
        }

		$disable_message = $this->model_settings->getValueIfAtiveByName('disable_creation_of_new_products');
        if ($disable_message) {
            $this->session->set_flashdata('error', utf8_decode($disable_message));
            redirect('dashboard', 'refresh');
        }

		$product_catalog =$this->model_products_catalog->getProductProductData($product_catalog_id);

		$label_ean = $this->model_settings->getValueIfAtiveByName('catalog_products_ean_name');
		if (!$label_ean) {
			$label_ean = $this->lang->line('application_ean');
		}
		$verify_ean = ($this->model_settings->getStatusbyName('catalog_products_verify_ean') == 1);

		$require_ean = ($this->model_settings->getStatusbyName('catalog_products_require_ean') == 1);

		$invalid_ean = $this->model_settings->getValueIfAtiveByName('catalog_products_error_ean');
		if (!$invalid_ean) {
			$invalid_ean= $this->lang->line('application_invalid_ean');
		}
		$Preco_Quantidade_Por_Marketplace = $this->model_settings->getStatusbyName('price_qty_by_marketplace');
		if (!in_array('updateProductsMarketplace', $this->permission)) {
			$Preco_Quantidade_Por_Marketplace = 0; 
		}
		
		$priceRO = $this->model_settings->getValueIfAtiveByName('catalog_products_dont_modify_price');
		if (in_array('disablePrice', $this->permission)) {
			$priceRO = true;
		}
		$crossdocking_catalog_default = $this->model_settings->getValueIfAtiveByName('crossdocking_catalog_default');
		if (!$crossdocking_catalog_default) {
			$crossdocking_catalog_default = 0; 
		}
		$sellercentername=$this->model_settings->getValueIfAtiveByName('sellercenter');
		if($sellercentername){
			$this->data['sellercenter_name']=$sellercentername;
		}else{
			$this->data['sellercenter_name']='';
		}

		$auto_publish = ($this->model_settings->getStatusbyName('catalog_products_auto_publish') == 1);

		$this->data['stores'] = $this->model_stores->getActiveStore();
		$this->data['changeCrossdocking'] = !in_array('changeCrossdocking', $this->permission);
		
		$allgood=true; 
		if ($this->postClean('store',TRUE)) {
			$product_exist = $this->model_products_catalog->getProductByProductCatalogStoreId($product_catalog_id,$this->postClean('store',TRUE), true);
			if ($product_exist) {
				$allgood=false; 
				if (count($this->data['stores']) > 1) {
					$this->session->set_flashdata('error', 'Já existe produto baseado neste produto de catálogo criado para esta Loja. Troque de loja ou clique '.
					'<a href='.base_url('catalogProducts/updateFromCatalog/'.$product_exist['id']).'>aqui </a> para acessar o produto.' );
				} else {
					$this->session->set_flashdata('error', 'Já existe produto baseado neste produto de catálogo criado para esta Loja. Você foi enviado para a alteração do produto ');
					redirect("catalogProducts/updateFromCatalog/".$product_exist['id'], 'refresh');
				}
			}	

		}
		
		$this->data['integrations'] = array();  // Não mostra o preço por variação e deixar criar o default para mudar depois
		if ($Preco_Quantidade_Por_Marketplace == 1) {
			if (($this->data['userstore'] != 0)) {  // se o usuário é de uma loja só posso pegar o preço por variação
				$this->data['integrations'] = $this->model_integrations->getIntegrationsbyStoreId($this->data['userstore']); 
			}
			elseif  (count($this->data['stores'])  == 1) { // se o usuário só tem cadastrada uma loja só na sua empresa, posso pegar o preço por variação
				$this->data['integrations'] = $this->model_integrations->getIntegrationsbyStoreId($this->data['stores'][0]['id']); 
			}
			foreach($this->data['integrations'] as $integration) { 
				if ($this->postClean('samePrice_'.$integration['id'],TRUE) !== 'on') { 
					$this->form_validation->set_rules('price_'.$integration['id'], $this->lang->line('application_price').' '.$integration['int_to'], 'trim|required');
				}
			} 	
		}
		
		$store_id = $this->postClean('store',TRUE); 
		$this->form_validation->set_rules('sku', $this->lang->line('application_sku'), 'trim|required|callback_checkSKUExist['.$store_id.',null]');
		if (!$priceRO) {
			$this->form_validation->set_rules('price', $this->lang->line('application_price'), 'trim|required|decimal|greater_than[10]');
		}else {
			if ($this->postClean('checkdiscount',TRUE)=='on') {
				$this->form_validation->set_rules('maximum_discount', $this->lang->line('application_maximum_discount'), 'trim|required|greater_than[0]|less_than_equal_to[100]');
			}
		}
		
		$product_variants = array();
		if ($product_catalog['has_variants']!=="") {
			$tipos_variacao = explode(";",strtoupper($product_catalog['has_variants'])); 
           	$product_variants = $this->model_products_catalog->getProductCatalogVariants($product_catalog_id);
			for ($i=0; $i<count($product_variants); $i++) {
				$this->form_validation->set_rules('Q['.$i.']', $this->lang->line('application_item_qty'), 'trim|required');
				//$this->form_validation->set_rules('SKU_V['.$i.']',$this->lang->line('application_sku'),'trim|required|callback_checkUniqueSku['.$product_variants[$i]['id'].']');
			     $this->form_validation->set_rules('SKU_V['.$i.']',$this->lang->line('application_sku'),'trim|required');
			} 
		}
		else {
			$this->form_validation->set_rules('qty', $this->lang->line('application_item_qty'), 'trim|required');
		}
		$this->form_validation->set_rules('status', $this->lang->line('application_availability'), 'trim|required');
		
		 //Origem do produto
        $this->data['origins'] = array(
            0 => $this->lang->line("application_origin_product_0"),
            1 => $this->lang->line("application_origin_product_1"),
            2 => $this->lang->line("application_origin_product_2"),
            3 => $this->lang->line("application_origin_product_3"),
            4 => $this->lang->line("application_origin_product_4"),
            5 => $this->lang->line("application_origin_product_5"),
            6 => $this->lang->line("application_origin_product_6"),
            7 => $this->lang->line("application_origin_product_7"),
            8 => $this->lang->line("application_origin_product_8"),
        );

        if (($this->form_validation->run() == TRUE) && ($allgood)) {
            $enable_catalog_associated = $this->postClean('enable_catalog_associated') ?? [];

            foreach ($enable_catalog_associated as $key => $enable_catalog) {
                if (empty($this->postClean('catalog_id_associated')[$key])) {
                    $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred') . ' catalog_id');
                    redirect('catalogProducts/createFromCatalog/'.$product_catalog_id, 'refresh');
                }
                if ($this->postClean('status_associated')[$key] != 2 && $this->postClean('status_associated')[$key] != 1) {
                    $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred') . ' status');
                    redirect('catalogProducts/createFromCatalog/'.$product_catalog_id, 'refresh');
                }
            }

			$maximum_discount = null;
			if ($priceRO) {
			  	if ($this->postClean('checkdiscount',TRUE)=='on') {
					$maximum_discount =$this->postClean('maximum_discount',TRUE); 
				}
			}
            $prazo_operacional_extra = trim($this->postClean('prazo_operacional_extra',TRUE));
			if ($prazo_operacional_extra == '') { $prazo_operacional_extra  = 0;}
			$loja =$this->model_stores->getStoresData($this->postClean('store',TRUE));
            $qty = $this->postClean('qty',TRUE);
         	if (count($product_variants)> 0){
         		$qty= 0;
         		$qtd = $this->postClean('Q',TRUE);
				foreach ($qtd as $q) {
					$qty +=  $q;
				}
            } 
            if ($priceRO) {
				$price= $product_catalog['price'];
			}else{
				$price= $this->postClean('price',TRUE);
			}
			if(is_null($product_catalog['brand_code'])) {
				$product_catalog['brand_code'] = '';
			}
			
			// vejo se é da sika. Remover depois que tiver franquia
			$dont_publish = false;
			$this->getDataSika();
			if (($loja['company_id'] == $this->companySika['id']) && 
				($loja['id'] !== $this->storeSika['id'])) {  // marco para não publicar o produto se for catalogo da sika, empresa sika mas não for a empresa principal da sika
					$dont_publish = true;
			}
			
			
            $data_prod = array(
		        'name' 						=> $product_catalog['name'],
		        'sku' 						=> str_replace("/", "-", $this->postClean('sku',TRUE)),
		        'price' 					=> $price,
		        'qty' 						=> $qty,
		        'image' 					=> 'catalog_'.$product_catalog_id,
		        'principal_image' 			=> $product_catalog['principal_image'],
		        'description' 				=> $product_catalog['description'],
		        'attribute_value_id' 		=> $product_catalog['attribute_value_id'],
		        'brand_id' 					=> json_encode(array($product_catalog['brand_id'])),
		    	'category_id' 				=> json_encode(array($product_catalog['category_id'])),
                'store_id' 					=> $this->postClean('store',TRUE),
                'status' 					=> $this->postClean('status',TRUE),
                'EAN' 						=> $product_catalog['EAN'],
                'codigo_do_fabricante' 		=> $product_catalog['brand_code'],
                'peso_liquido' 				=> $product_catalog['net_weight'],
                'peso_bruto' 				=> $product_catalog['gross_weight'],
                'largura' 					=> $product_catalog['width'],
                'altura' 					=> $product_catalog['height'],
                'profundidade' 				=> $product_catalog['length'],
                'garantia' 					=> $product_catalog['warranty'],
				'actual_width'				=> $product_catalog['actual_width'] ?: null, 
				'actual_height'				=> $product_catalog['actual_height'] ?: null, 
				'actual_depth'				=> $product_catalog['actual_depth'] ?: null, 
                'NCM' 						=> $product_catalog['NCM'],
                'origin' 					=> $product_catalog['origin'],
                'has_variants' 				=> $product_catalog['has_variants'],                
                'company_id' 				=> $loja['company_id'],
                'situacao' 					=> 2, 
                'prazo_operacional_extra' 	=> $prazo_operacional_extra,
                'product_catalog_id' 		=> $product_catalog_id,
                'maximum_discount_catalog' 	=> $maximum_discount,
                'dont_publish' 				=> $dont_publish,
            );
			if (!is_null($maximum_discount) && ($product_catalog['original_price']>0)) {
				if ((float)$maximum_discount < round((1-$product_catalog['price'] / $product_catalog['original_price'])*100,2)) {
					$data_prod['status'] = 2; // já entra inativo
				}
			}

            $this->db->trans_begin();
			$create = $this->model_products->create($data_prod);

            $catalog_id = $this->model_products_catalog->getCatalogsStoresDataByProductCatalogId($product_catalog_id)[0]['catalog_id'] ?? null;
            $catalog = $this->model_catalogs->getCatalogData($catalog_id);

            foreach ($enable_catalog_associated as $key => $enable_catalog) {
                $match_sku_associate = $this->getMatchCatalogProductToAssociate($catalog['fields_to_link_catalogs'], $this->postClean('catalog_id_associated')[$key], $product_catalog['brand_id'], $product_catalog['EAN']);
                if (!$match_sku_associate) {
                    $this->db->trans_rollback();
                    $this->session->set_flashdata('error', "Não foi encontrado a associação com o produto de catálogo do catálogo {$this->postClean('catalog_id_associated')[$key]}");
                    redirect('catalogProducts/updateFromCatalog/'.$product_catalog_id, 'refresh');
                }

                $this->model_products_catalog_associated->create(array(
                    'catalog_id'                    => $this->postClean('catalog_id_associated')[$key],
                    'original_catalog_product_id'   => $product_catalog_id,
                    'catalog_product_id'            => $match_sku_associate['id'],
                    'product_id'                    => $create,
                    'maximum_discount_catalog'      => $this->postClean('maximum_discount_associated')[$key] ?? null,
                    'store_id'                      => $this->postClean('store'),
                    'company_id'                    => $loja['company_id'],
                    'status'                        => $this->postClean('status_associated')[$key],
                ));
            }
            $this->db->trans_commit();

        	if($create != false) {
        		$log_var = array('id'=> $create);
				$this->log_data('Products','create',json_encode(array_merge($log_var,$data_prod)),"I");
				
				// gravo o stores_tipovolumes para avisar se mudou o tipo de volume e mudar no frete rápido
				$categoria = $this->model_category->getCategoryData($product_catalog['category_id']);
				if (!is_null($categoria['tipo_volume_id'])) {
					$datastorestiposvolumes = array(
						'store_id' 			=> $this->postClean('store',TRUE),
						'tipos_volumes_id' 	=> $categoria['tipo_volume_id'],
						'status' 			=> 1,
					);
					$this->model_stores->createStoresTiposVolumes($datastorestiposvolumes);
				}
				
				if (count($product_variants)> 0){
					$qty=0;
					$qtd = $this->postClean('Q',TRUE);
                    $skuVar = $this->postClean('SKU_V',TRUE);
                    foreach ($product_variants as $key => $product_variant) {
                    	$data_var = Array (
							'prd_id' 				=> $create,
							'variant' 				=> $product_variant['variant_id'],
							'name' 					=> $product_variant['name'],
							'sku' 					=> $skuVar[$key],
							'price' 				=> $this->postClean('price',TRUE),
							'qty' 					=> $qtd[$key],
							'image' 				=> $product_variant['principal_image'],
							'status' 				=> 1,
							'EAN' 					=> $product_variant['EAN'],
							'codigo_do_fabricante' 	=> '',	
						);

						$createvar = $this->model_products->createvar($data_var);
						$this->log_data('Products','create_variation',json_encode($data_var),"I");
					}
				}
				$minhasLojas =$this->model_stores->getActiveStore();
				if (($this->data['userstore'] != 0)) {  // se o usuário é de uma loja só, posso pegar o preço por variação
					$integrations= $this->model_integrations->getIntegrationsbyStoreId($this->data['userstore']); 
				}
				elseif (count($minhasLojas)  == 1) { // se o usuário só tem cadastrada uma loja só na sua empresa, posso pegar o preço por variação
					$integrations = $this->model_integrations->getIntegrationsbyStoreId($minhasLojas[0]['id']); 
				}
				else {
					$integrations = array();
				}
				// altero agora os preços e qty por marketplace que foram criados automaticamente pelo model_products->create
				foreach($integrations as $integration) {
					if ($auto_publish) {
						$prd_to_int = Array(
							'int_id' 		=> $integration['id'],
							'prd_id' 		=> $create,
							'company_id' 	=> $loja['company_id'],
							'store_id' 		=> $this->postClean('store',TRUE),
							'date_last_int' => '',
							'status' 		=> 1,
							'user_id' 		=> (isset($this->session->userdata['id'])) ? $this->session->userdata['id'] : "1", 
							'status_int' 	=> 1,
							'int_type' 		=> 13,       
							'int_to' 		=> $integration['int_to'] , 
							'skubling' 		=> null,
							'skumkt' 		=> null,
							'variant' 		=> null,
							'approved' 		=> ($integration['auto_approve']) ? 1 : 3,
						);
						if (count($product_variants)> 0){
							foreach ($product_variants as $key => $product_variant) {
								$prd_to_int ['variant'] =  $product_variant['variant_id']; 
								$this->model_integrations->setProductToMkt($prd_to_int);
							}
						}
						else {
							$this->model_integrations->setProductToMkt($prd_to_int);
						}
					}

					$this->model_products_marketplace->createIfNotExist($integration['int_to'],$create, $integration['int_type']=='DIRECT');
					
					$products_marketplace=$this->model_products_marketplace->getAllDataByIntToProduct($integration['int_to'], $create);
					foreach ($products_marketplace as $product_marketplace) {
						if ($product_marketplace['hub'] || ($product_marketplace['variant'] == '0') || ($product_marketplace['variant'] == '')) {
							if ($this->postClean('samePrice_'.$integration['id'],TRUE) == 'on') {
								$data = array(
									'same_price' => true,
									'price' => $this->postClean('price',TRUE),
								);
							}else{
								$data = array(
									'same_price' => false,
									'price' => $this->postClean('price_'.$integration['id'],TRUE),
								);
							}
							// gravo log e update 
							$log = [
					            'id' 	 	 => $product_marketplace['id'],
					            'int_to' 	 => $product_marketplace['int_to'],
					            'product_id' => $create,
					            'old_price'    => 'NOVO',
					            'new_price'    => $data['price']
					        ];
					        $this->log_data('ProductsMarketPlace', 'Update_Price', json_encode($log), 'I');
							$this->model_products_marketplace->updateAllVariants($data,$product_marketplace['int_to'], $product_marketplace['prd_id']);
						}
						if ($product_marketplace['hub']) { 
							if ($product_marketplace['variant'] == '') {
								if ($this->postClean('sameQty_'.$integration['id'],TRUE) == 'on') {
									$data = array(
										'same_qty' => true,
										'qty' => $this->postClean('qty',TRUE),
									);
								}else{
									$data = array(
										'same_qty' => false,
										'qty' => $this->postClean('qty_'.$integration['id'],TRUE),
									);
								}
								// gravo log e update somente se alterou...
								$log = [
						            'id' 	 	 => $product_marketplace['id'],
						            'int_to' 	 => $product_marketplace['int_to'],
						            'product_id' => $create,
						            'old_qty'    => 'NOVO',
						            'new_qty'    => $data['qty']
						        ];
						        $this->log_data('ProductsMarketPlace', 'Update_Qty', json_encode($log), 'I');
								$this->model_products_marketplace->update($data,$product_marketplace['id']);								
							}
						}
						
					}
				}
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_created'));
                redirect("products", 'refresh');
        	}
        	else {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                redirect('catalogProducts/create/'.$product_catalog_id, 'refresh');
        	}
        }
        else {
			
            // attributes
            $attribute_data = $this->model_attributes->getActiveAttributeData('products');
            
            $attributes_final_data = array();
            foreach ($attribute_data as $k => $v) {
                $attributes_final_data[$k]['attribute_data'] = $v;
                $value = $this->model_attributes->getAttributeValueData($v['id']);
                $attributes_final_data[$k]['attribute_value'] = $value;
            }

            $this->data['catalog'] = $this->model_products_catalog->getCatalogByProduct($product_catalog_id);
            $this->data['identifying_technical_specification'] = $this->identifying_technical_specification;
            $this->data['attributes'] = $attributes_final_data;
            $this->data['brands'] = $this->model_brands->getActiveBrands();
            $this->data['category'] = $this->model_category->getActiveCategroy();   
			$this->data['catalogs'] = $this->model_catalogs->getActiveCatalogs(); 

			$linkcatalogs = array();  // substituir por consulta que mostar os produtos que usam este produto de catálogo
			$this->data['linkcatalogs'] = $this->model_products_catalog->getCatalogsStoresDataByProductCatalogId($product_catalog_id);
			
            $this->data['product_variants'] = $product_variants;
			
			$this->data['product_catalog'] = $product_catalog;
			
			$this->data['label_ean']= $label_ean;
			$this->data['verify_ean']= $verify_ean;
			$this->data['invalid_ean']= $invalid_ean;
			$this->data['require_ean']= $require_ean;
			$this->data['changeCrossdocking'] = !in_array('changeCrossdocking', $this->permission);
			$this->data['crossdocking_catalog_default'] = $crossdocking_catalog_default;
			$this->data['priceRO']= $priceRO;
            $this->data['catalogs_associated'] = array();
            $this->data['products_catalogs_associated'] = array();

            $this->render_template('catalogproducts/createfromcatalog', $this->data);
    	}
		
	}

    public function getCatalogsAssociateByStore(int $product_catalog_id, int $store_id, int $product_id = null): CI_Output
    {
        $catalogs_store = array_map(function($catalog) {
            return $catalog['catalog_id'];
        }, $this->model_catalogs->getCatalogsStoresDataByStoreId($store_id));

        $product_catalog = $this->model_products_catalog->getProductProductData($product_catalog_id);
        $catalog = $this->model_catalogs->getCatalogData($this->model_products_catalog->getCatalogsStoresDataByProductCatalogId($product_catalog_id)[0]['catalog_id']);
        $response['products_catalogs_associated'] = is_null($product_id) ? [] : $this->model_products_catalog_associated->getByProductId($product_id);
        $associate_skus_between_catalogs_db = $this->model_catalogs_associated->getCatalogIdToByCatalogFrom($catalog['id']);
        if (!empty($associate_skus_between_catalogs_db)) {
            $catalogs_associated = array_merge(array($catalog), $this->model_catalogs->getCatalogData($associate_skus_between_catalogs_db));
            foreach ($catalogs_associated as $catalog_associated) {
                if (!in_array($catalog_associated['id'], $catalogs_store)) {
                    continue;
                }

                $product_catalog_associated = $this->getMatchCatalogProductToAssociate($catalog['fields_to_link_catalogs'], $catalog_associated['id'], $product_catalog['brand_id'], $product_catalog['EAN']);

                if (!empty($product_catalog_associated)) {
                    $catalog_associated['price'] = $product_catalog_associated['price'];
                    $response['catalogs_associated'][] = $catalog_associated;
                }
            }
        } else {
            $catalog['price'] = $product_catalog['price'];
            $response['catalogs_associated'][] = $catalog;
        }


        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    private function getMatchCatalogProductToAssociate(string $fields_to_link_catalogs, int $catalog_id, int $brand_id, string $ean): ?array
    {
        $product_catalog_associated = null;
        switch ($fields_to_link_catalogs) {
            case 'brand,ean':
            case 'ean,brand':
                $product_catalog_associated = $this->model_products_catalog->getProductProductDataByBrandAndEan($catalog_id, $brand_id, $ean);
                break;
            case 'brand':
                $product_catalog_associated = $this->model_products_catalog->getProductProductDataByBrand($catalog_id, $brand_id);
                break;
            case 'ean':
                if (empty($ean)) {
                    break;
                }
                $product_catalog_associated = $this->model_products_catalog->getProductProductDataByEan($catalog_id, $ean);
                break;
            default:
                break;
        }

        if (is_null($product_catalog_associated)) {
            return null;
        }

        if (count($product_catalog_associated) == 1) {
            return $product_catalog_associated[0];
        }

        return null;
    }

    public function viewFromCatalog($product_id = null)
    {
        $this->updateFromCatalog($product_id);
    }

	public function updateFromCatalog($product_id, $backsite = null) {
		
		if((!in_array('updateProduct', $this->permission)) && (!in_array('updateFromCatalog', $this->permission))) {
        	redirect('dashboard', 'refresh');
        }
		$this->session->set_flashdata('error', '');
		
		$product_data = $this->model_products->verifyProductsOfStore($product_id);
		
		if(!$product_data) {
			redirect('dashboard', 'refresh');
		}

        $productDeleted = $product_data['status'] == Model_products::DELETED_PRODUCT;
        if ($productDeleted) {
            $this->session->set_flashdata('error', strlen($this->session->flashdata('error')) > 0
                ? $this->session->flashdata('error')
                : $this->lang->line('messages_edit_product_removed'));
        }

		if (is_null($product_data['product_catalog_id'])) {
			redirect('products/update/'.$product_id, 'refresh');
			return; 
		}

		if ($this->model_settings->getStatusbyName('stores_multi_cd') == 1) {
			$store = $this->model_stores->getStoresData($product_data['store_id']);
			if ($store['type_store'] ==2)  {
				$multi_channel = $this->model_stores_multi_channel_fulfillment->getRangeZipcode($store['id'], $store['company_id'], 1);
				if ($multi_channel){
					$original_store_id = $multi_channel[0]['store_id_principal'];
					if ($product_data['has_variants'] == '') {
						$prd_original = $this->model_products->getProductComplete($product_data['sku'], $store['company_id'], $original_store_id);
						if ($prd_original) {
							$this->data['prd_original'] =  $prd_original;
						}
					}
					else{
						$variants  = $this->model_products->getVariants($product_data['id']);
						foreach ($variants as $variant) {
							$variant_original = $this->model_products->getVariantsBySkuAndStore($variant['sku'], $original_store_id);
							if ($variant_original) {
								$this->data['prd_original'] = $this->model_products->getProductData(0,$variant_original['prd_id']);
								break;
							}
						}
					}
				}
			}
		}

		$product_catalog_id = $product_data['product_catalog_id']; 
		
		$product_catalog =$this->model_products_catalog->getProductProductData($product_catalog_id);
		
		$label_ean = $this->model_settings->getValueIfAtiveByName('catalog_products_ean_name');
		if (!$label_ean) {
			$label_ean = $this->lang->line('application_ean');
		}
		$verify_ean = ($this->model_settings->getStatusbyName('catalog_products_verify_ean') == 1);

		$require_ean = ($this->model_settings->getStatusbyName('catalog_products_require_ean') == 1);

		$invalid_ean = $this->model_settings->getValueIfAtiveByName('catalog_products_error_ean');
		if (!$invalid_ean) {
			$invalid_ean= $this->lang->line('application_invalid_ean');
		}
		$sellercentername=$this->model_settings->getValueIfAtiveByName('sellercenter');
		if($sellercentername){
			$this->data['sellercenter_name']=$sellercentername;
		}else{
			$this->data['sellercenter_name']='';
		}
		$priceRO = $this->model_settings->getValueIfAtiveByName('catalog_products_dont_modify_price');
		if (in_array('disablePrice', $this->permission)) {
			$priceRO = true;
		}
		
		$Preco_Quantidade_Por_Marketplace = $this->model_settings->getStatusbyName('price_qty_by_marketplace');
		if (!in_array('updateProductsMarketplace', $this->permission)) {
			$Preco_Quantidade_Por_Marketplace = 0; 
		}
		
		$this->data['usergroup'] = $this->session->userdata('group_id');
		
		$this->data['stores'] = $this->model_stores->getActiveStore();
		
		$this->data['integrations'] = array();  // Não mostra o preço por variação e deixar criar o default para mudar depois
		if ($Preco_Quantidade_Por_Marketplace == 1) {
			$products_marketplace = $this->model_products_marketplace->getAllDataByProduct($product_id);
			foreach ($products_marketplace as $product_marketplace) {
				if ($product_marketplace['hub'] || ($product_marketplace['variant'] == '0') || ($product_marketplace['variant'] == '')) {	
					if ($this->postClean('samePrice_'.$product_marketplace['int_to'],TRUE) !== 'on') { 
						$this->form_validation->set_rules('price_'.$product_marketplace['int_to'], $this->lang->line('application_price').' '.$product_marketplace['int_to'], 'trim|required');
					}
				}
			}
		}
		
		$store_id = $product_data['store_id']; 
		$this->form_validation->set_rules('sku', $this->lang->line('application_sku'), 'trim|required|callback_checkSKUExist['.$store_id.','.$product_id.']');
		if (!$priceRO) {
			$this->form_validation->set_rules('price', $this->lang->line('application_price'), 'trim|required|greater_than[10]');
		}else {
			if ($this->postClean('checkdiscount',TRUE)=='on') {
				$this->form_validation->set_rules('maximum_discount', $this->lang->line('application_maximum_discount'), 'trim|required|greater_than[0]|less_than_equal_to[100]');
			}
		}
		
		$product_cat_variants = array();
		if ($product_catalog['has_variants']!=="") {
			$tipos_variacao = explode(";",strtoupper($product_catalog['has_variants'])); 
           	$product_cat_variants = $this->model_products_catalog->getProductCatalogVariants($product_catalog_id);
			for ($i=0; $i<count($product_cat_variants); $i++) {
				$this->form_validation->set_rules('Q['.$i.']', $this->lang->line('application_item_qty'), 'trim|required');
				//$this->form_validation->set_rules('SKU_V['.$i.']',$this->lang->line('application_sku'),'trim|required|callback_checkUniqueSku['.$product_variants[$i]['id'].']');
			     $this->form_validation->set_rules('SKU_V['.$i.']',$this->lang->line('application_sku'),'trim|required');
			} 
		}
		else {
			$this->form_validation->set_rules('qty', $this->lang->line('application_item_qty'), 'trim|required');
		}
		$this->form_validation->set_rules('status', $this->lang->line('application_availability'), 'trim|required');
		
		 //Origem do produto
        $this->data['origins'] = array(
            0 => $this->lang->line("application_origin_product_0"),
            1 => $this->lang->line("application_origin_product_1"),
            2 => $this->lang->line("application_origin_product_2"),
            3 => $this->lang->line("application_origin_product_3"),
            4 => $this->lang->line("application_origin_product_4"),
            5 => $this->lang->line("application_origin_product_5"),
            6 => $this->lang->line("application_origin_product_6"),
            7 => $this->lang->line("application_origin_product_7"),
            8 => $this->lang->line("application_origin_product_8"),
		);
		
		$this->data['changeCrossdocking'] = !in_array('changeCrossdocking', $this->permission);

        $catalog_id = $this->model_products_catalog->getCatalogsStoresDataByProductCatalogId($product_catalog_id)[0]['catalog_id'] ?? null;
        $catalog = $this->model_catalogs->getCatalogData($catalog_id);

        if ($this->form_validation->run() == TRUE) {
            $enable_catalog_associated = $this->postClean('enable_catalog_associated');

            if ($enable_catalog_associated) {
                foreach ($enable_catalog_associated as $key => $enable_catalog) {
                    if (empty($this->postClean('catalog_id_associated')[$key])) {
                        $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred') . ' catalog_id');
                        redirect('catalogProducts/updateFromCatalog/' . $product_catalog_id, 'refresh');
                    }
                    if ($this->postClean('status_associated')[$key] != 2 && $this->postClean('status_associated')[$key] != 1) {
                        $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred') . ' status');
                        redirect('catalogProducts/updateFromCatalog/' . $product_catalog_id, 'refresh');
                    }
                }
            }

			$maximum_discount = null;
			if ($priceRO) {
			  	if ($this->postClean('checkdiscount',TRUE)=='on') {
					$maximum_discount =$this->postClean('maximum_discount',TRUE); 
				}
			}
            $prazo_operacional_extra = trim($this->postClean('prazo_operacional_extra',TRUE));
			if ($prazo_operacional_extra == '') { $prazo_operacional_extra  = 0;}
			$loja =$this->model_stores->getStoresData($this->postClean('store',TRUE));
            $qty = $this->postClean('qty',TRUE);
         	if (count($product_cat_variants)> 0){
         		$qty= 0;
         		$qtd = $this->postClean('Q',TRUE);
				foreach ($qtd as $q) {
					$qty +=  $q;
				}
            } 
			if ($priceRO) {
				$price= $product_catalog['price'];
			}else{
				$price= $this->postClean('price',TRUE);
			}
			if(is_null($product_catalog['brand_code'])) {
				$product_catalog['brand_code'] = '';
			}

			// vejo se é da sika. Remover depois que tiver franquia
			$dont_publish = false;
			$this->getDataSika();
			if (($loja['company_id'] == $this->companySika['id']) && 
				($loja['id'] !== $this->storeSika['id'])) {  // marco para não publicar o produto se for catalogo da sika, empresa sika mas não for a empresa principal da sika
					$dont_publish = true;
			}
				
            $data_prod = array(
		        'name' 						=> $product_catalog['name'],
		        'sku' 						=> str_replace("/", "-", $this->postClean('sku',TRUE)),
		        'price' 					=> $price,
		        'qty' 						=> $qty,
		        'image' 					=> 'catalog_'.$product_catalog_id,
		        'principal_image' 			=> $product_catalog['principal_image'],
		        'description'				=> $product_catalog['description'],
				'attribute_value_id' 		=> $product_catalog['attribute_value_id'],
		        'brand_id' 					=> json_encode(array($product_catalog['brand_id'])),
		    	'category_id' 				=> json_encode(array($product_catalog['category_id'])),
                'store_id' 					=> $this->postClean('store',TRUE),
                'status' 					=> $this->postClean('status',TRUE),
                'EAN' 						=> $product_catalog['EAN'],
                'codigo_do_fabricante' 		=> $product_catalog['brand_code'],
                'peso_liquido' 				=> $product_catalog['net_weight'],
                'peso_bruto' 				=> $product_catalog['gross_weight'],
                'largura' 					=> $product_catalog['width'],
                'altura' 					=> $product_catalog['height'],
                'profundidade' 				=> $product_catalog['length'],
				'actual_width'				=> $product_catalog['actual_width'] ?: null, 
				'actual_height'				=> $product_catalog['actual_height'] ?: null, 
				'actual_depth'				=> $product_catalog['actual_depth'] ?: null, 
                'garantia' 					=> $product_catalog['warranty'],
                'NCM' 						=> $product_catalog['NCM'],
                'origin' 					=> $product_catalog['origin'],
                'has_variants' 				=> $product_catalog['has_variants'],                
                'company_id' 				=> $loja['company_id'],
                'situacao' 					=> 2, 
                'prazo_operacional_extra' 	=> $prazo_operacional_extra,
                'product_catalog_id' 		=> $product_catalog_id, 
                'date_update' 				=> date('Y-m-d H:i:s'),  // Forço a data para que funcione o sincronismos de produtos novamente
                'maximum_discount_catalog' 	=> $maximum_discount,
                'dont_publish' 				=> $dont_publish, 
            );

			if (!is_null($maximum_discount) && ($product_catalog['original_price']>0)) {
				if ((float)$maximum_discount < round((1-$product_catalog['price'] / $product_catalog['original_price'])*100,2)) {
					$data_prod['status'] = 2; // já entra inativo
				}
			}
			
			if (($product_catalog['status'] != 1) && ($data_prod['status'] == 1)) { // se o product_catolog ficou inativo, o produto baseado nele não pode ficar ativo 
				$data_prod['status'] = 2; 
			}

			if (!empty($data_prod['qty']) && $data_prod['qty'] != $product_data['qty']) {
				$data_prod['stock_updated_at'] = date('Y-m-d H:i:s');
			}

            $this->db->trans_begin();
			$update = $this->model_products->update($data_prod, $product_id);

            $old_products_catalog_associated = $this->model_products_catalog_associated->getByProductId($product_id);
            if ($enable_catalog_associated) {
                foreach ($enable_catalog_associated as $key => $enable_catalog) {
                    $match_sku_associate = $this->getMatchCatalogProductToAssociate($catalog['fields_to_link_catalogs'], $this->postClean('catalog_id_associated')[$key], $product_catalog['brand_id'], $product_catalog['EAN']);
                    if (!$match_sku_associate) {
                        $this->db->trans_rollback();
                        $this->session->set_flashdata('error', "Não foi encontrado a associação com o produto de catálogo do catálogo {$this->postClean('catalog_id_associated')[$key]}");
                        redirect('catalogProducts/updateFromCatalog/'.$product_catalog_id, 'refresh');
                    }

                    $this->model_products_catalog_associated->create(array(
                        'catalog_id'                    => $this->postClean('catalog_id_associated')[$key],
                        'original_catalog_product_id'   => $product_catalog_id,
                        'catalog_product_id'            => $match_sku_associate['id'],
                        'product_id'                    => $product_id,
                        'maximum_discount_catalog'      => $this->postClean('maximum_discount_associated')[$key] ?? null,
                        'store_id'                      => $this->postClean('store'),
                        'company_id'                    => $loja['company_id'],
                        'status'                        => $this->postClean('status_associated')[$key],
                    ));
                }
            }

            foreach ($old_products_catalog_associated as $old_product_catalog_associated) {
                $this->model_products_catalog_associated->remove($old_product_catalog_associated['id']);
            }

            $this->db->trans_commit();

        	if($update == true) {
        		$log_var = array('id'=> $product_id);
				$this->log_data('Products','edit_after',json_encode(array_merge($log_var,$data_prod)),"I");
				
				// gravo o stores_tipovolumes para avisar se mudou o tipo de volume e mudar no frete rápido
				$categoria = $this->model_category->getCategoryData($product_catalog['category_id']);
				if (!is_null($categoria['tipo_volume_id'])) {
					$datastorestiposvolumes = array(
						'store_id' => $this->postClean('store',TRUE),
						'tipos_volumes_id' => $categoria['tipo_volume_id'],
						'status' => 1,
					);
					$this->model_stores->createStoresTiposVolumes($datastorestiposvolumes);
				}
				
				if (count($product_cat_variants)> 0){
					$qty=0;
					$qtd = $this->postClean('Q',TRUE);
                    $skuVar = $this->postClean('SKU_V',TRUE);
                    foreach ($product_cat_variants as $key => $product_variant) {
                    	$data_var = Array (
							'prd_id' 				=> $product_id,
							'variant' 				=> $product_variant['variant_id'],
							'name' 					=> $product_variant['name'],
							'sku' 					=> $skuVar[$key],
							'price' 				=> $this->postClean('price',TRUE),
							'qty' 					=> $qtd[$key],
							'image' 				=> $product_variant['principal_image'],
							'status' 				=> 1,
							'EAN' 					=> $product_variant['EAN'],
							'codigo_do_fabricante' 	=> '',	
						);

						$updatevar = $this->model_products->updateVar($data_var, $product_id, $product_variant['variant_id']);
						$this->log_data('Products','edit_after_variation',json_encode($data_var),"I");
					}
				}
				if ($Preco_Quantidade_Por_Marketplace == 1) {  // se o parametro estiver ligado, pego o resultado do sistema.
					$products_marketplace = $this->model_products_marketplace->getAllDataByProduct($product_id);
					foreach ($products_marketplace as $product_marketplace) {
						if ($product_marketplace['hub'] || ($product_marketplace['variant'] == '0') || ($product_marketplace['variant'] == '')) {
							if (($this->postClean('samePrice_'.$product_marketplace['int_to'],TRUE) != 'on') && (!is_null($this->postClean('price_'.$product_marketplace['int_to'],TRUE)))) {
								$data = array(
									'same_price' => false,
									'price' => $this->postClean('price_'.$product_marketplace['int_to'],TRUE),
								);
							}else{
								$data = array(
									'same_price' => true,
									'price' => $this->postClean('price',TRUE),
								);
							}
							// gravo log somente se alterou...
							if (($data['same_price'] != $product_marketplace['same_price']) || ($data['price'] != $product_marketplace['price'])) {
								$log = [
						            'id' 	 	 => $product_marketplace['id'],
						            'int_to' 	 => $product_marketplace['int_to'],
						            'product_id' => $product_id,
						            'old_same_price'    => $product_marketplace['same_price'],
						            'same_price'    => $data['same_price'],
						            'old_price'    => $product_marketplace['price'],
						            'new_price'    => $data['price']
						        ];
						        $this->log_data('ProductsMarketPlace', 'Update_PriceX', json_encode($log), 'I');
							}
							$this->model_products_marketplace->updateAllVariants($data,$product_marketplace['int_to'], $product_marketplace['prd_id']);
						}
						if ($product_marketplace['hub']) { 
							if ($product_marketplace['variant'] == '') {
								if ($this->postClean('sameQty_'.$product_marketplace['int_to'],TRUE) != 'on') {
									$data = array(
										'same_qty' => false,
										'qty' => $this->postClean('qty_'.$product_marketplace['int_to'],TRUE),
									);
								}else{
									$data = array(
										'same_qty' => true,
										'qty' => $this->postClean('qty',TRUE),
									);
									
								}
								// gravo log e update somente se alterou...
								if (($data['same_qty'] != $product_marketplace['same_qty']) || ($data['qty'] != $product_marketplace['qty'])) {
									$log = [
							            'id' 	 	 => $product_marketplace['id'],
							            'int_to' 	 => $product_marketplace['int_to'],
							            'product_id' => $product_id,
							            'old_same_qty'    => $product_marketplace['same_qty'],
						            	'same_qty'    => $data['same_qty'],
							            'old_qty'    => $product_marketplace['qty'],
							            'new_qty'    => $data['qty']
							        ];
							        $this->log_data('ProductsMarketPlace', 'Update_Qty', json_encode($log), 'I');
									$this->model_products_marketplace->update($data,$product_marketplace['id']);	
								}							
							}
							else {  /*  falta colocar na tela as qtd por variação 
								$qtd = $this->postClean('Q');
								if ($this->postClean('sameQty_'.$product_marketplace['id']) == 'on') {
									$data = array(
										'same_qty' => true,
										'qty' => $qtd[$product_marketplace['variant']],
									);
								}else{
									$data = array(
										'same_qty' => false,
										'qty' => $this->postClean('qty_'.$product_marketplace['id']),
									);
								}
								// gravo log e update somente se alterou...
								if (($data['same_qty'] != $product_marketplace['same_qty']) || ($data['qty'] != $product_marketplace['qty'])) {
									$log = [
							            'id' 	 	 => $product_marketplace['id'],
							            'int_to' 	 => $product_marketplace['int_to'],
							            'product_id' => $product_id,
							            'variant'    => $product_marketplace['variant'],
							            'old_qty'    => $product_marketplace['same_qty'],
							            'new_qty'    => $data['qty']
							        ];
							        $this->log_data('ProductsMarketPlace', 'Update_Qty', json_encode($log), 'I');
									$this->model_products_marketplace->update($data,$product_marketplace['id']);	
								} */
							} 
						}
						
					}
				}
                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_created'));
                redirect("products", 'refresh');
        	}
        	else {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                redirect('catalogProducts/create/'.$product_catalog_id, 'refresh');
        	}

			
        }
        else {

            $percPriceCatalogSetting = $this->model_settings->getSettingDatabyName('alert_percentage_update_price_catalog');
            $daysPriceCatalogSetting = $this->model_settings->getSettingDatabyName('alert_days_update_price_catalog');
            if ($percPriceCatalogSetting && $daysPriceCatalogSetting && $daysPriceCatalogSetting['status'] == 1 && $percPriceCatalogSetting['status'] == 1) {
                if ($logCatlogProd = $this->model_products_catalog->getProductWithChangedPrice($product_catalog_id)) {
                    $this->session->set_flashdata('error', "{$this->lang->line('messages_alert_price_product_catalog_updated')} {$percPriceCatalogSetting['value']}%. (&nbsp; R$".number_format($logCatlogProd['old_price'],2 ,',', '.')." &nbsp;&nbsp;<i class='fas fa-arrow-right'></i>&nbsp;&nbsp; R$".number_format($logCatlogProd['new_price'],2 ,',', '.').' &nbsp;)');
                }
            }
			
            // attributes
            $attribute_data = $this->model_attributes->getActiveAttributeData('products');
            
            $attributes_final_data = array();
            foreach ($attribute_data as $k => $v) {
                $attributes_final_data[$k]['attribute_data'] = $v;
                $value = $this->model_attributes->getAttributeValueData($v['id']);
                $attributes_final_data[$k]['attribute_value'] = $value;
            }
            
            $this->data['attributes'] = $attributes_final_data;
            $this->data['brands'] = $this->model_brands->getActiveBrands();
            $this->data['category'] = $this->model_category->getActiveCategroy();   
			$this->data['catalogs'] = $this->model_catalogs->getActiveCatalogs();
            $this->data['catalogs'] = $this->model_catalogs->getMyCatalogs();
            $linkcatalogs = array();  // substituir por consulta que mostar os produtos que usam este produto de catálogo
			$this->data['linkcatalogs'] = $this->model_products_catalog->getCatalogsStoresDataByProductCatalogId($product_catalog_id);
			
			if ($product_cat_variants>0) {
				$product_variants = $this->model_products->getProductVariants($product_id,$product_data['has_variants']);
			}
			else {
				$product_variants = array(); 
			}
            $this->data['product_variants'] = $product_variants;
			$this->data['product_cat_variants'] = $product_cat_variants;
			$this->data['product_catalog'] = $product_catalog;
			
			$product_data['bestprice'] = $this->model_integrations->getPrdBestPrice($product_catalog['EAN']);
			$better_price_by_ean = $this->model_products->getBetterPriceByEan($product_catalog['EAN'], $product_data['price']);
            if ($better_price_by_ean) {
                // fazer cálculo
                $original_price = (float)$product_data['price'];
                $product_data['competitiveness'] = $better_price_by_ean < $original_price ? ((($original_price - $better_price_by_ean)/$better_price_by_ean)*100) : false;
            } else {
                $product_data['competitiveness'] = false;
            }
			
			$this->data['product_data'] = $product_data;
			
			
			$this->data['label_ean']= $label_ean;
			$this->data['verify_ean']= $verify_ean;
			$this->data['invalid_ean']= $invalid_ean;
			$this->data['require_ean']= $require_ean;
			
			//  $integrations = $this->model_integrations->getPrdIntegration($product_id,$product_data['company_id'],1);
            $integrations = $this->model_integrations->getPrdIntegration($product_id);
            if ($integrations) {
                $integracoes = array();
                $i=0;
                foreach($integrations as $v) {
                	$error_transformation = $this->model_errors_transformation->getErrorsByProductId($product_id,$v['int_to']);
                    $integracoes[$i]['int_to'] = $v['int_to'];
                    $integracoes[$i]['skubling'] = $v['skubling'];
                    $integracoes[$i]['skumkt'] = $v['skumkt'];
                    if ($error_transformation) {
                    	$integracoes[$i]['status_int'] = '<span class="label label-danger">'.mb_strtoupper($this->lang->line('application_errors_tranformation'),'UTF-8').'</span>';
                    } elseif ($v['status_int']==0) {
                        $integracoes[$i]['status_int'] = '<span class="label label-warning">'.mb_strtoupper($this->lang->line('application_product_in_analysis'),'UTF-8').'</span>';
                    } elseif ($v['status_int']==1) {
                        $integracoes[$i]['status_int'] = '<span class="label label-success">'.mb_strtoupper($this->lang->line('application_product_waiting_to_be_sent'),'UTF-8').'</span>';
                    } elseif ($v['status_int']==2) {
                        $integracoes[$i]['status_int'] = '<span class="label label-primary">'.mb_strtoupper($this->lang->line('application_product_sent'),'UTF-8').'</span>';
                    } elseif ($v['status_int']==11) {
                        $over = $this->model_integrations->getPrdBestPrice($product_data['EAN']);
                        $integracoes[$i]['status_int'] = '<span class="label label-danger">'.mb_strtoupper($this->lang->line('application_product_higher_price'),'UTF-8').' ('.$over.')</span>';
                    } elseif ($v['status_int']==12) {
                        $integracoes[$i]['status_int'] = '<span class="label label-danger">'.mb_strtoupper($this->lang->line('application_product_higher_price'),'UTF-8').'</span>';
                    } elseif ($v['status_int']==13) {
                        $integracoes[$i]['status_int'] = '<span class="label label-danger">'.mb_strtoupper($this->lang->line('application_product_higher_price'),'UTF-8').'</span>';
                    } elseif ($v['status_int']==14) {
                        $integracoes[$i]['status_int'] = '<span class="label label-danger">'.mb_strtoupper($this->lang->line('application_product_release'),'UTF-8').'</span>';
                    } elseif ($v['status_int']==20) {
                        $integracoes[$i]['status_int'] = '<span class="label label-success">'.mb_strtoupper($this->lang->line('application_in_registration'),'UTF-8').'</span>';
                    } elseif ($v['status_int']==21) {
                        $integracoes[$i]['status_int'] = '<span class="label label-success">'.mb_strtoupper($this->lang->line('application_in_registration'),'UTF-8').'</span>';
                    } elseif ($v['status_int']==22) {
                        $integracoes[$i]['status_int'] = '<span class="label label-success">'.mb_strtoupper($this->lang->line('application_in_registration'),'UTF-8').'</span>';
                    } elseif ($v['status_int']==23) {
                        $integracoes[$i]['status_int'] = '<span class="label label-success">'.mb_strtoupper($this->lang->line('application_in_registration'),'UTF-8').'</span>';	
             		} elseif ($v['status_int']==24) {
                        $integracoes[$i]['status_int'] = '<span class="label label-success">'.mb_strtoupper($this->lang->line('application_in_registration'),'UTF-8').'</span>';	
             		} elseif ($v['status_int']==99) {
                        $integracoes[$i]['status_int'] = '<span class="label label-warning">'.mb_strtoupper($this->lang->line('application_product_in_analysis'),'UTF-8').'</span>';
                    } elseif ($v['status_int']==90) {
                        $integracoes[$i]['status_int'] = '<span class="label label-default">'.mb_strtoupper($this->lang->line('application_product_inactive'),'UTF-8').'</span>';
                    } elseif ($v['status_int']==91) {
                        $integracoes[$i]['status_int'] = '<span class="label label-default">'.mb_strtoupper($this->lang->line('application_no_logistics'),'UTF-8').'</span>';
                    } else {
                        $integracoes[$i]['status_int'] = '<span class="label label-danger">'.mb_strtoupper($this->lang->line('application_product_out_of_stock'),'UTF-8').'</span>';
                    }
					$integracoes[$i]['ad_link'] = $v['ad_link'];
					$integracoes[$i]['name'] = $v['name'];
					$integracoes[$i]['quality'] = $v['quality'];
					$integracoes[$i]['approved'] = $v['approved'];
					$integracoes[$i]['auto_approve'] = $v['auto_approve'];
					$integracoes[$i]['status'] = $v['status'];
					$integracoes[$i]['date_last_int'] = $v['date_last_int'];
					$integracoes[$i]['id'] = $v['id'];
                    $i++;
                }
                $this->data['integracoes'] = $integracoes;
                
            }
			
			 //pego a promoção do produto se tiver
            $promotion = $this->model_promotions->getPromotionByProductId($product_id);
            if ($promotion) {
                $this->data['promotion'] = $promotion;
            }
            $campaigns = $this->model_campaigns->getCampaignByProductId($product_id);
            $this->data['campaigns'] = $campaigns;
            
			$errors_transformation = $this->model_errors_transformation->getErrorsByProductId($product_id);
			$this->data['errors_transformation'] = $errors_transformation;
			
			$this->data['mykits'] = $this->model_products->getProductsKitFromProductItem($product_id);
			$this->data['myorders'] = $this->model_orders->getOrdersByProductItem($product_id);
			
			if ($Preco_Quantidade_Por_Marketplace == 1) {
				$this->data['products_marketplace'] = $this->model_products_marketplace->getAllDataByProduct($product_id);
			}
			else {
				$this->data['products_marketplace'] = array();
			}
			$this->data['notAdmin'] = ($this->data['usercomp'] == '1' ? false : true);

            $this->data['catalog'] = $this->model_products_catalog->getCatalogByProduct($product_id);
            $this->data['identifying_technical_specification'] = $this->identifying_technical_specification;
			$this->data['priceRO']= $priceRO;
			$this->data['backsite'] = $backsite;
			$this->data['changeCrossdocking'] = !in_array('changeCrossdocking', $this->permission);
			$this->data['in_catalog']=$this->model_stores->inCatalogByStoreIDAndProductID($store_id,$product_id,$product_data['product_catalog_id']);
            $catalog = $this->model_catalogs->getCatalogData($this->model_products_catalog->getCatalogsStoresDataByProductCatalogId($product_catalog['id'])[0]['catalog_id']);
            $this->data['catalogs_associated'] = array();
            $associate_skus_between_catalogs_db = $this->model_catalogs_associated->getCatalogIdToByCatalogFrom($catalog['id']);
            $products_catalogs_associated_catalog = [];
            $this->data['products_catalogs_associated'] = array();
            if (!empty($associate_skus_between_catalogs_db)) {
                $catalogs_store = array_map(function($catalog) {
                    return $catalog['catalog_id'];
                }, $this->model_catalogs->getCatalogsStoresDataByStoreId($store_id));

                $catalogs_associated = array_merge(array($catalog), $this->model_catalogs->getCatalogData($associate_skus_between_catalogs_db));
                foreach ($catalogs_associated as $catalog_associated) {
                    if (!in_array($catalog_associated['id'], $catalogs_store)) {
                        continue;
                    }

                    $product_catalog_associated = $this->getMatchCatalogProductToAssociate($catalog['fields_to_link_catalogs'], $catalog_associated['id'], $product_catalog['brand_id'], $product_catalog['EAN']);

                    if (!empty($product_catalog_associated)) {
                        $this->data['catalogs_associated'][] = $catalog_associated;

                        $better_price_by_ean = $this->model_products->getBetterPriceByEan($product_catalog['EAN'], $product_catalog_associated['price']);
                        if ($better_price_by_ean) {
                            // fazer cálculo
                            $original_price = (float)$product_catalog_associated['price'];
                            $product_catalog_associated['competitiveness'] = $better_price_by_ean < $original_price ? ((($original_price - $better_price_by_ean)/$better_price_by_ean)*100) : false;
                        } else {
                            $product_catalog_associated['competitiveness'] = false;
                        }

                        if ($catalog['id'] == $product_catalog_associated['catalog_id']) {
                            $product_catalog_associated['price'] = $product_data['price'];
                        }

                        $products_catalogs_associated_catalog[$catalog_associated['id']] = $product_catalog_associated;
                    }
                }
                $this->data['products_catalogs_associated'] = $this->model_products_catalog_associated->getByProductId($product_id);
                $this->data['products_catalogs_associated_catalog'] = $products_catalogs_associated_catalog;
            } else {
                $product_catalog_associated = $product_catalog;
                $product_catalog_associated['price'] = $product_data['price'];
                $better_price_by_ean = $this->model_products->getBetterPriceByEan($product_catalog['EAN'], $product_catalog_associated['price']);
                if ($better_price_by_ean) {
                    // fazer cálculo
                    $original_price = (float)$product_catalog_associated['price'];
                    $product_catalog_associated['competitiveness'] = $better_price_by_ean < $original_price ? ((($original_price - $better_price_by_ean)/$better_price_by_ean)*100) : false;
                } else {
                    $product_catalog_associated['competitiveness'] = false;
                }

                $products_catalogs_associated_catalog[$catalog['id']] = $product_catalog_associated;

                $catalog['catalog_id'] = $catalog['id'];
                $this->data['catalogs_associated'][] = $catalog;
                $this->data['products_catalogs_associated_catalog'] = $products_catalogs_associated_catalog;
            }

			$this->log_data('Products','edit_before',json_encode($product_data),"I");  // LOg DATA
			
            $this->render_template('catalogproducts/updatefromcatalog', $this->data);
    	}
		
	}

	public function fetchMyProductCatalogData()
	{
		if((!in_array('createProduct', $this->permission)) && (!in_array('viewProductsCatalog', $this->permission))){
            redirect('dashboard', 'refresh');
        }
		//$result = array('data' => array());
		
		$postdata = $this->postClean(NULL,TRUE);
		$ini = $postdata['start'];
		$draw = $postdata['draw'];
		$busca = $postdata['search']; 
		$length = $postdata['length'];

		$procura = '';
		
		$product_catalog_id = $postdata['product_catalog_id'];
        if ($busca['value']) {
            if (strlen($busca['value'])>=2) {  // Garantir no minimo 3 letras
                $procura= " AND ( s.name like '%".$busca['value']."%' OR p.id like '%".$busca['value']."%' OR p.sku like '%".$busca['value']."%' )";
            }
        } else {
            if (is_array($postdata['lojas'])) {
                $lojas = $postdata['lojas'];
                $procura .= " AND (";
                foreach($lojas as $loja) {
                    $procura .= "s.id = ".(int)$loja." OR ";
                }
                $procura = substr($procura, 0, (strlen($procura)-3));
                $procura .= ") ";
            }
			if (trim($postdata['sku'])) {
                $procura .= " AND p.sku like '%".$postdata['sku']."%' ";
            }

            $deletedStatus = Model_products::DELETED_PRODUCT;
            if ($postdata['status']) {
                $procura .= " AND (p.status = {$postdata['status']}
                    AND p.status NOT IN ({$deletedStatus}))";
            } else {
                $procura .= " AND p.status NOT IN ({$deletedStatus})";
            }

			if (trim($postdata['estoque'])) {
            	switch ((int)$postdata['estoque']) {
                    case 1: 
                        $procura .= " AND p.qty > 0 ";
                        break;
                    case 2:
                        $procura .= " AND p.qty <= 0 ";
                        break;
                }
            }
        }
		$back = 'backupdate';
        if ($postdata['view']) { $back = 'backview'; }
		
		$sOrder = "";
		if (isset($postdata['order'])) {
			if ($postdata['order'][0]['dir'] == "asc") {
				$direcao = "ASC";
			} else { 
				$direcao = "DESC";
		    }
			$campos = array('s.name','p.sku','CAST(p.qty AS UNSIGNED)','CAST(p.price AS DECIMAL(12,2))','p.id','p.status');
			$campo =  $campos[$postdata['order'][0]['column']];
			if ($campo != "") {
				if ($campo == 'id') {
					if ($direcao =="ASC") {$direcao ="DESC";}
					else {$direcao ="ASC";}
				}
				$sOrder = " ORDER BY ".$campo." ".$direcao;
		    }
		}
		
		//$procura='';
		//$sOrder='';	
		//$length = 20;
	    //$ini = 0;
		$data = $this->model_products_catalog->getProductsFetchProductCatalogId($product_catalog_id, $ini, $procura, $sOrder, $length );
		$filtered = $this->model_products_catalog->getProductsFetchProductCatalogIdCount($product_catalog_id, $procura);
		if ($procura == '') {
			$total_rec = $filtered;
		}
		else {
			$total_rec = $this->model_products_catalog->getProductsFetchProductCatalogIdCount($product_catalog_id);
		}

		$result = array();
		foreach ($data as $key => $value) {
			// button
			$status  = '';
			switch ($value['status']) {
                case 1: 
                    $status =  '<span class="label label-success">'.$this->lang->line('application_active').'</span>';
                    break;
				case 2: 
                    $status =  '<span class="label label-danger">'.$this->lang->line('application_inactive').'</span>';
                    break;
                default:
                    $status = '<span class="label label-danger">'.$this->lang->line('application_inactive').'</span>';
                    break;
			}
			
			$link_id = '<a target="_blank" href="'.base_url('catalogProducts/updateFromCatalog/'.$value['id'].'/'.$back).'">'.$value['id'].'</a>';
			$result[$key] = array(
				$value['store'],
				$value['sku'],
				$value['qty'],
				$this->formatprice($value['price']),
				$link_id,
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

	function checkSKUExist($sku,$param)
	{
		 $param = preg_split('/,/', $param);
	     $store_id = $param[0];
	     $prod_id = $param[1];
		 if ($prod_id=='null') { $prod_id=null; }
		 if (!$this->model_products->checkSkuAvailable($sku, $store_id, $prod_id)) {
		 	$this->form_validation->set_message('checkSKUExist', $this->lang->line('messages_product_sku_available').$sku);
            return FALSE;
        }
		return true;
	}
	
	function getDataSika()
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		$this->companySika 	= array('id'=>'-100'); // retorna um id inválido para que o processo continue. 
		$this->storeSika 	= array('id'=>'-100');
		$this->catalogSika 	= array('id'=>'-100');
		// pego a empresa
		
		$comp = $this->model_company->getCompaniesByName('SIKA');
		if (count($comp) != 1) {	
			return;
		}
		$this->companySika =  $comp[0];
		
		//pego a loja 
		$stores = $this->model_stores->getMyCompanyStores($this->companySika['id']);
		foreach($stores as $store) {
			if ($store['CNPJ'] == $this->companySika['CNPJ']) {
				$this->storeSika = $store;
				break;
			}
		}
		if (is_null($this->storeSika)) {
			return;
		}
		
		// pego o catálogo
		$catalog = $this->model_catalogs->getCatalogByName('Catálogo Sika');
		if (!$catalog) {
			return;
		}
		$this->catalogSika = $catalog; 
	}
	
	public function getVtexIntegrations() {
		$integrations = $this->model_integrations->getIntegrationsbyStoreId(0);
        $intto = array();
		$nameintto = array();
        foreach ($integrations as  $integration) {
            if ($integration['active'] == 1) {
            	if ((strpos($integration['auth_data'], 'X_VTEX_API_AppKey') > 0) 
					|| $integration['int_to'] == 'SH'
            		|| $integration['int_to'] == 'GPA'){
            		$intto[] = $integration['int_to'];
					$nameintto[$integration['int_to']] = $integration['name'];
				}
			}
		}
        return array('int_to' => $intto, 'name' => $nameintto);
	}

    /*public function copy($prodId)
    {
        if (!in_array('createProductsCatalog', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->render_template('catalogproducts/updatefromcatalog', $this->data);
    }*/

	function verifyVariantsAtCategoryMarketplace($idcat, $int_to, $variant_name, $idprd = null)
    {
        $result = $this->model_categorias_marketplaces->getCategoryMktplace($int_to, $idcat);
        $idCatML = ($result) ? $result['category_marketplace_id'] : null;
        if ($idprd) {
            $productCategoryMkt = $this->model_products_category_mkt->getCategoryEnriched($idprd, $int_to);
            if ($productCategoryMkt) {
                $idCatML = $productCategoryMkt['category_mkt_id'];
            } 
        }
		return [$result, $this->model_atributos_categorias_marketplaces->getAtributosCategoriaMKTVariant($idCatML, $int_to, $variant_name)];
    }
 }