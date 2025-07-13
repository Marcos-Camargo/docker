<?php

defined('BASEPATH') OR exit('No direct script access allowed');


/**
 * @property CI_Form_validation $form_validation
 * @property CI_Input $input
 * @property CI_Session $session
 * @property CI_Lang $lang
 * @property CI_Loader $load
 * @property Bucket $bucket
 * 
 * @property Model_products $model_products
 * @property Model_attributes $model_attributes
 * @property Model_category $model_category
 * @property Model_stores $model_stores
 * @property Model_integrations $model_integrations
 * @property Model_atributos_categorias_marketplaces $model_atributos_categorias_marketplaces
 * @property Model_errors_transformation $model_errors_transformation
 * @property Model_categorias_marketplaces $model_categorias_marketplaces
 * @property Model_brands $model_brands
 * @property Model_settings $model_settings
 * @property Model_products_catalog $model_products_catalog
 * @property Model_blacklist_words $model_blacklist_words
 * @property Model_products_category_mkt $model_products_category_mkt
 *
 * @property BlacklistOfWords $blacklistofwords
 */

class ProductsKit extends Admin_Controller
{
    public $allowable_tags = null;
	const UNDER_ANALYSISS = 4;
	
	var $vtexsellercenters;
	var $vtexsellercentersNames;

    private $product_length_name ;
    private $product_length_description ;


    public function __construct()
    {
        parent::__construct();
        
        $this->not_logged_in();
        
		$this->data['page_title'] = $this->lang->line('application_products_kit');
        
        $this->load->model('model_products');
		$this->load->model('model_attributes');
		$this->load->model('model_category');
        $this->load->model('model_stores');
		$this->load->model('model_integrations');
		$this->load->model('model_atributos_categorias_marketplaces');
        $this->load->model('model_errors_transformation');
		$this->load->model('model_categorias_marketplaces');
		$this->load->model('model_brands');
		$this->load->model('model_settings');
		$this->load->model('model_products_catalog');
        $this->load->model('model_blacklist_words');
		$this->load->model('model_products_category_mkt');
        $this->load->library('BlacklistOfWords');
		$this->load->library('Bucket');

        if ($allowableTags = $this->model_settings->getValueIfAtiveByName('products_allowable_tags')) {
            if (!empty($allowableTags)) {
                $this->allowable_tags = '<' . implode('><', explode(',', $allowableTags)) . '>';
            }
        }

        $usercomp = $this->session->userdata('usercomp');
        $this->data['usercomp'] = $usercomp;
        $more = " company_id = ".$usercomp;
        $this->data['mycontroller']=$this;
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
    }
	
	public function create()
    {
        if(!in_array('createProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
		$disable_message = $this->model_settings->getValueIfAtiveByName('disable_creation_of_new_products');
        if ($disable_message) {
            $this->session->set_flashdata('error', utf8_decode($disable_message));
            redirect('dashboard', 'refresh');
        }

		$this->data['product_length_name '] = $this->product_length_name ;
		$this->data['product_length_description'] = $this->product_length_description;
       	if ($this->input->method() == 'post') {
			$numprod = $this->postClean('numprod');
			if ($numprod==0) {
				$this->session->set_flashdata('error', 'Voce deve escolher ao menos um produto');
				$this->render_template('productskit/create', $this->data);
				return false;
			}
			$ids = $this->postClean('ID');
			$prices = $this->postClean('PRICE');
			$qtys = $this->postClean('QTY');
			$prods = array();
			for ($x=0;$x <= $numprod-1;$x++) {
				$prods[] = array(
					'id' => $ids[$x],
					'price' => $prices[$x],
					'qty' => $qtys[$x] 
					);	
			}
			if (($numprod==1) && ($qtys[0] == 1)) {
				$this->session->set_flashdata('error', 'Se escolher um só produto, a quantidade tem que ser maior 1');
				$this->render_template('productskit/create', $this->data);
				return false;
			}
			// criar diretório de imagem 
            $targetDir =  'assets/images/product_image/';
            $dirImage = Admin_Controller::getGUID(false); // gero um novo diretorio para as imagens
            $targetDir .= $dirImage;
			
			$name 					= 'KIT ';
			$sku 					= '%MUDE%'.random_int(100000, 999999);
			$price 					= 0;
			$image 					= $dirImage;
			$qty 					= -10000000;
			$description 			= '';
			$category_id			= '';
			$brand_id 				= '';
			$status 				= 1;
			$situacao 				= 1; 
			$ean 					= '';
			$codigo_do_fabricante 	= '';
			$peso_liquido 			= 0; 
			$peso_bruto 			= 0;
			$largura 				= '';
			$altura 				= ''; 
			$profundidade			= '';
			$garantia				= '';
			$ncm 					= '';
			$origin 				= 0;
			$has_variants			= '';
			$prazo_operacional_extra = 0;
			$gtprice 				= -1;
			foreach($prods as $prod) {
				$produto = $this->model_products->getProductData(0,$prod['id']);
				$pathImage = 'product_image';
				if (!is_null($produto['product_catalog_id'])) {
					$prd_catalog = $this->model_products_catalog->getProductProductData($produto['product_catalog_id']);
					$produto['name'] = $prd_catalog['name'];
					$produto['description'] = $prd_catalog['description'];
					$produto['EAN'] = $prd_catalog['EAN'];
					$produto['largura'] = $prd_catalog['width'];
					$produto['altura'] = $prd_catalog['height'];
					$produto['profundidade'] = $prd_catalog['length'];
					$produto['peso_bruto'] = $prd_catalog['gross_weight'];
					$produto['ref_id'] = $prd_catalog['ref_id']; 
					$produto['brand_code'] = $prd_catalog['brand_code'];
					$produto['brand_id'] = '["'.$prd_catalog['brand_id'].'"]'; 
					$produto['category_id'] = '["'.$prd_catalog['category_id'].'"]';
					$produto['image'] = $prd_catalog['image'];
					$pathImage = 'catalog_product_image';
				}				

				if ( $prod['qty'] == 1 ) {
					$name .= $produto['name'].' e ';
				}
				else {
					$name .= " de ".$prod['qty'].' '.$produto['name'].' e ';
				}
				
				$price += $prod['price']*$prod['qty'];
				if (($qty==-10000000) || ($qty > (intdiv($produto['qty'],$prod['qty'])))) {
					$qty = intdiv($produto['qty'],$prod['qty']);  // Pego a maior quantidade de kits que consigo criar com os produtos do kit
				}
				$description .= '<strong>'.$produto['name'].'</strong><br><br>'.$produto['description'].'<br>';
				if ((float)$prod['price'] > (float)$gtprice) {
					$category_id = $produto['category_id']; //pego a categoria do produto de maior preço
					$gtprice = $prod['price'];
				}
				$store_id 		= $produto['store_id'];
				$peso_liquido 	+= $produto['peso_liquido'] * $prod['qty'];
				$peso_bruto 	+= $produto['peso_bruto'] * $prod['qty'];
				//$largura 		+= $produto['largura'];
				//$altura 		+= $produto['altura'];
				//$profundidade 	+= $produto['profundidade'];
				$company_id		= $produto['company_id'];
				if (($garantia==0) || ($garantia > $produto['garantia'])) {
					$garantia =  $produto['garantia']; // ajusta para a menor garantia
				}
				if ((int)$prazo_operacional_extra < (int)$produto['prazo_operacional_extra']) {
					$prazo_operacional_extra = $produto['prazo_operacional_extra'];  // ajusto para o maior prazo
					
				}

				$principal_image = '';
				if (!$produto['is_on_bucket']) {
					$fotos = scandir(FCPATH . 'assets/images/' . $pathImage . '/' . $produto['image'], 1);
					foreach ($fotos as $foto) {
						if (($foto != '.') && ($foto != '..')) {
							// Apenas queremos enviar as imagens do produto, portanto não podemos utilizar o transfer para enviar as variações.
							// Lê para a stream e envia para o bucket.
							$foto_stream = fopen(FCPATH . 'assets/images/product_image/' . $produto['image'] . '/' . $foto,'r');
							$this->bucket->sendFileToObjectStorage($foto_stream, $targetDir . '/' . $foto);
							if ($principal_image == '') {
								$principal_image = $this->bucket->getAssetUrl('assets/images/product_image/' . $dirImage . '/' . $foto);
							}
						}
					}
				} else {
					// Busca cada imagem no bucket.
					$pathBucket = 'assets/images/' . $pathImage . '/' . $produto['image'];

					// Busca apenas as imagens diretamente do produto.
					$fotos = $this->bucket->getFinalObject($pathBucket);

					// Copia cada imagem de forma individual.
					foreach ($fotos['contents'] as $foto) {
						$this->bucket->copy($pathBucket . '/' . $foto['key'], $targetDir . '/' . $foto['key']);
						if ($principal_image == '') {
							$principal_image = $foto['url'];
						}
					}
				}
				
				if ($brand_id == '') {
					$brand_id = $produto['brand_id'];
				}
			}
			$name = substr($name,0, -3);
			
			$data_prod = array(
          		'name' => $name,
		        'sku' => $sku,
		        'price' => $price,
		        'qty' => $qty,
		        'image' => $image,
		        'description' => $description,
		        'attribute_value_id' => null,
		        'brand_id' => $brand_id,
		        'category_id' => $category_id,
                'store_id' => $store_id,
                'status' => $status,
                'EAN' => $ean,
                'codigo_do_fabricante' => $codigo_do_fabricante,
                'peso_liquido' => $peso_liquido,
                'peso_bruto' => $peso_bruto,
                'largura' => $largura,
                'altura' => $altura,
                'profundidade' => $profundidade,
                'garantia' => $garantia,
                'NCM' => $ncm,
                'origin' => $origin,
                'has_variants' => $has_variants,                
                'company_id' => $company_id,  
                'situacao' => $situacao, 
                'prazo_operacional_extra' => $prazo_operacional_extra,   
                'is_kit' => 1,        
              );

        	$create = $this->model_products->create($data_prod);

            // bloqueia produto se necessário
            $this->blacklistofwords->updateStatusProductAfterUpdateOrCreate($data_prod, $create);

			if ($create) {
				foreach($prods as $prod) {
					$data_kit = array(
						'product_id' => $create,
						'product_id_item' => $prod['id'],
						'qty' => $prod['qty'],
						'price' => $prod['price'],
					);
					$createkit = $this->model_products->createKit($data_kit);
				}
				$this->session->set_flashdata('success', $this->lang->line('messages_successfully_created'));
        		redirect('productsKit/update/'.$create, 'refresh');
			}
			else {
				$this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
        		redirect('productsKit/create', 'refresh');
			}
       	}
		else {

	      //  $this->data['filters'] = $this->model_reports->getFilters('products');
	        $this->render_template('productskit/create', $this->data);
		}
    }

	
	public function fetchProductData()
	{

		$postdata = $this->postClean(NULL,TRUE);
		$ini = $postdata['start'];
		$draw = $postdata['draw'];

		$busca = $postdata['search']; 
		$procura = '';

		if ($busca['value']) {
			if (strlen($busca['value'])>2) {  // Garantir no minimo 3 letras
				$procura = " AND ( p.sku like '%".$busca['value']."%' OR p.name like '%".$busca['value']."%' OR s.name like '%".$busca['value']."%' OR p.id like '%".$busca['value']."%')";
			}
		}

		$sOrder = "";
		if (isset($postdata['order'])) {
			if ($postdata['order'][0]['dir'] == "asc") {
				$direcao = "asc";
			} else {
				$direcao = "desc";
		    }
			$campos = array('','p.sku','p.name','CAST(p.price AS DECIMAL(12,2))','CAST(p.qty AS UNSIGNED)','s.name','p.id','','','');
			$campo =  $campos[$postdata['order'][0]['column']];
			if ($campo != "") {
				$sOrder = " ORDER BY ".$campo." ".$direcao;
		    }
		}


		$result = array();

		$data = $this->model_products->getProductsCompleteNoVarsData($ini, $procura, $sOrder);
		$filtered = $this->model_products->getProductsCompleteNoVarsCount($procura);

		$i = 0;
		foreach ($data as $key => $value) {
            $i++;

            $price = $value['price'];
            $stock = $value['qty'];

           // $buttons = '<button type="button" class="btn btn-default" onclick="qtyProduct(event,'.$value['id'].','.$stock.',\''.$value['name'].'\')" data-toggle="modal" data-target="#quantityModal"><i class="fa fa-check-circle"></i></button>';
            $buttons = '<button type="button" class="btn btn-default" onclick="qtyProduct(event,'.$value['id'].','.$value['store_id'].',\''.$value['sku'].'\','.$stock.',\''.$price.'\',\''.$value['name'].'\')" ><i class="fa fa-check-circle"></i></button>';

			$img = '';
			if (strpos(".." . $value['image'], "http") > 0) {
				$fotos = explode(",", $value['image']);
				foreach ($fotos as $foto) {
					$img = '<img src="' . $foto . '" alt="' . substr($value['name'], 0, 20) . '" class="img-circle" width="50" height="50" />';
					break;
				}
			} else {
				$url = base_url('assets/images/system/sem_foto.png');
				// Verifica se o produto está ou não no bucket na hora de montar as imagens.
				if (!$value['is_on_bucket']) {
					if (is_dir(FCPATH . 'assets/images/product_image/' . $value['image'])) {
						$fotos = scandir(FCPATH . 'assets/images/product_image/' . $value['image'], 1);
						$url = base_url('assets/images/product_image/' . $value['image']) . '/' . $fotos[0];
					}
				} else {
					$fotos = $this->bucket->getFinalObject('assets/images/product_image/' . $value['image']);
					if (count($fotos['contents']) > 0) {
						$url = $fotos['contents'][0]['url'];
					}
				}

				$img = '<img src="' . $url  . '" alt="' . utf8_encode(substr($value['name'], 0, 20)) . '" class="img-circle" width="50" height="50" />';
			}

            $qty_status = '';
            if ($stock <= 10 && $stock > 0) {
                $qty_status = '<span class="label label-warning">'.$this->lang->line('application_low').' !</span>';
            } else if ($stock <= 0) {
                $qty_status = '<span class="label label-danger">'.$this->lang->line('application_out_stock').' !</span>';
            }

			$result[$key] = array(
				$img,
				$value['sku'],
				$value['name'],
				$this->formatprice($price),
                $stock . ' ' . $qty_status,
                $value['loja'],
                $value['id'],
				$buttons
			);
		} // /foreach
		if ($filtered==0) {
            $filtered = $i;
        }
		$output = array(
		    "draw" => $draw,
            "recordsTotal" => $this->model_products->getProductsCompleteNoVarsCount(),
            "recordsFiltered" => $filtered,
            "data" => $result
        );
        echo json_encode($output);
        //echo json_last_error();

    }

    public function view($product_id = null)
    {
        $this->update($product_id);
    }

	public function update($product_id = null)
    {
        if(!in_array('updateProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
		if(!$product_id) {
            redirect('dashboard', 'refresh');
		}

		$product_data = $this->model_products->verifyProductsOfStore($product_id);
		if(!$product_data) {
			redirect('dashboard', 'refresh');
		}
		if (!$product_data['is_kit']) {
			redirect('products/update/'.$product_id, 'refresh');
			return;
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

        $productDeleted = $product_data['status'] == Model_products::DELETED_PRODUCT;
        if ($productDeleted) {
            if ($this->input->get('p') == 'r') {
                $this->session->set_flashdata('success', $this->lang->line('message_products_moved_to_trash'));
            } else {
                $this->session->set_flashdata('error', strlen($this->session->flashdata('error')) > 0
                    ? $this->session->flashdata('error')
                    : $this->lang->line('messages_edit_product_removed'));
            }
        }

		$product_data = $this->model_products->getProductData(0,$product_id);

		$product_data['description'] = strip_tags_products($product_data['description'], $this->allowable_tags);

		$this->form_validation->set_rules('sku', $this->lang->line('application_sku'), 'trim|required');
		if ($this->postClean('has_integration') || $this->postClean('status') != 1) {
            $this->form_validation->set_rules('product_name', $this->lang->line('application_product_name'), 'trim|required');
			$this->form_validation->set_rules('description', $this->lang->line('application_description'), 'trim|required');
        } else {
            $this->form_validation->set_rules('product_name', $this->lang->line('application_product_name'), 'trim|required|max_length['.$this->product_length_name.']');
			$this->form_validation->set_rules('description', $this->lang->line('application_description'), 'trim|required|max_length['.$this->product_length_description.']');
		}
		$this->form_validation->set_rules('status', $this->lang->line('application_status'), 'trim|required');
        $this->form_validation->set_rules('peso_liquido', $this->lang->line('application_net_weight'), 'trim|required');
        $this->form_validation->set_rules('peso_bruto', $this->lang->line('application_weight'), 'trim|required');
        // $this->form_validation->set_rules('largura', $this->lang->line('application_width'), 'trim|required|callback_checkMinValue[11]');
        $this->form_validation->set_rules('largura', $this->lang->line('application_width'), 'trim|required|callback_checkMinValue[1]');
        // $this->form_validation->set_rules('altura', $this->lang->line('application_height'), 'trim|required|callback_checkMinValue[2]');
        $this->form_validation->set_rules('altura', $this->lang->line('application_height'), 'trim|required|callback_checkMinValue[1]');
		// $this->form_validation->set_rules('profundidade', $this->lang->line('application_depth'), 'trim|required|callback_checkMinValue[16]');
		$this->form_validation->set_rules('profundidade', $this->lang->line('application_depth'), 'trim|required|callback_checkMinValue[1]');
		$this->form_validation->set_rules('products_package', $this->lang->line('application_products_by_packaging'), 'trim|required|callback_checkMinValue[1]');
		$this->form_validation->set_rules('garantia', $this->lang->line('application_garanty'), 'trim|required');
		$this->form_validation->set_rules('category[]', $this->lang->line('application_category'), 'trim|required');
        $this->form_validation->set_rules('brands[]', $this->lang->line('application_brands'), 'trim|required');

		$categoria_sel = $this->postClean('category');
		$this->data['sellercenter_name'] = $this->model_settings->getValueIfAtiveByName('sellercenter_name');
		$allgood = false;
		$principal_image = '';
        if ($this->form_validation->run() == TRUE) {
            $upload_image = $this->postClean('product_image');
            if (!$upload_image) {
                $semFoto = TRUE;

            } else {
                $this->data['upload_image'] = $upload_image;
                $allgood = true;
                $semFoto = false;
            }

			$numft = 0;
			if (strpos(".." . $upload_image, "http") > 0) {
				$fotos = explode(",", $upload_image);
				$numft = count($fotos);
			} else {
				// Busca o produto para ver se está ou não no bucket.
				$sql = 'SELECT * FROM products WHERE image = "' . $upload_image . '"';
				$query = $this->db->query($sql);
				$prd = $query->row_array();
				if (!$prd['is_on_bucket']) {
					$fotos = scandir(FCPATH . 'assets/images/product_image/' . $upload_image);
					foreach ($fotos as $foto) {
						if (($foto != ".") && ($foto != "..") && ($foto != "")) {
							if ($principal_image == '') {
								$principal_image = base_url('assets/images/product_image/' . $upload_image) . '/' . $foto;
							}
							$numft++;
						}
					}
				} else {
					$fotos = $this->bucket->getFinalObject('assets/images/product_image/' . $upload_image);
					foreach ($fotos['contents'] as $foto) {
						if ($principal_image == '') {
							$principal_image = $foto['url'];
						}
						$numft++;
					}
				}
			}
            if ($numft==0) {
                $semFoto = TRUE;
            }
            $allgood = true;
        }
		if ($allgood) {
		    $semCategoria = (trim($this->postClean('category')[0])=='');
		    if (($semFoto) || ($semCategoria) ) {
			      $situacao = '1';
		    }
	    	else {
			      $situacao = '2';
		    }
			$has_var = "";
			$prazo_operacional_extra = trim($this->postClean('prazo_operacional_extra'));
			if ($prazo_operacional_extra == '') { $prazo_operacional_extra  = 0;}

			$status = $this->postClean('status');
			// pego a quantidade dos items que me compoe
			$products_items = $this->model_products->getProductsKit($product_id);
			foreach ($products_items as $product_item) {
				if (($product_data['qty']> intdiv($product_item['qty_item'],$product_item['qty'])) ) {
					$product_data['qty'] = intdiv($product_item['qty_item'],$product_item['qty']);
				}
				if ($product_item['status'] !=1) {
					$status = $product_item['status'];
				}
			}

			$product_data['name'] = $this->postClean('product_name');
			$product_data['sku'] = str_replace("/", "-", $this->postClean('sku'));
			$product_data['description'] = strip_tags_products($this->postClean('description', true, false, false), $this->allowable_tags); // $this->postClean('description');
			$product_data['category_id'] = json_encode($this->postClean('category'));
			$product_data['status'] = $status;
			// verificar se pode ter o mesmo status
			$product_data['peso_liquido'] = $this->postClean('peso_liquido');
			$product_data['peso_bruto'] = $this->postClean('peso_bruto');
			$product_data['largura'] = $this->postClean('largura');
			$product_data['altura'] = $this->postClean('altura');
			$product_data['profundidade'] = $this->postClean('profundidade');
			$product_data['products_package'] = $this->postClean('products_package');
			$product_data['actual_width'] = $this->postClean('actual_width');
			$product_data['actual_height'] = $this->postClean('actual_height');
			$product_data['actual_depth'] = $this->postClean('actual_depth');
			$product_data['garantia'] = $this->postClean('garantia');
			$product_data['image'] = $upload_image;
			$product_data['situacao'] = $situacao;
			$product_data['prazo_operacional_extra'] = $prazo_operacional_extra;
			$product_data['date_update'] = date('Y-m-d H:i:s');
			$product_data['principal_image'] = $principal_image;
            $product_data['brand_id'] = json_encode($this->postClean('brands'));

			// var_dump($product_data);
			$category_id = $this->postClean('category')[0];
			// $this->model_atributos_categorias_marketplaces->saveBrandName($product_id, $this->postClean('brands')[0]);

            $update = $this->model_products->update($product_data, $product_id);


            // bloqueia produto se necessário
            $this->blacklistofwords->updateStatusProductAfterUpdateOrCreate($product_data, $product_id);

			if($update == true) {
				$log_var = array('id'=> $product_id);
				$this->log_data('Products','edit_after',json_encode(array_merge($log_var,$product_data)),"I");

				// gravo o stores_tipovolumes para avisar se mudou o tipo de volume e mudar no frete rápido
				if (!$semCategoria) {
					$categoria = $this->model_category->getCategoryData($this->postClean('category'));
					if (!is_null($categoria['tipo_volume_id'])) {
						$datastorestiposvolumes = array(
							'store_id' => $product_data['store_id'],
							'tipos_volumes_id' => $categoria['tipo_volume_id'],
							'status' => 1,
						);
						$this->model_stores->createStoresTiposVolumes($datastorestiposvolumes);
					}
				}

                $this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
                redirect("products/attributes/edit/$product_id/$category_id", 'refresh');
                // redirect("products", 'refresh');
            }
            else {
                $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
                redirect('productsKit/update/'.$product_id, 'refresh');
            }
        }
		else {
        	$attribute_data = $this->model_attributes->getActiveAttributeData('products');
            $attributes_final_data = array();
            foreach ($attribute_data as $k => $v) {
                $attributes_final_data[$k]['attribute_data'] = $v;
                $value = $this->model_attributes->getAttributeValueData($v['id']);
                $attributes_final_data[$k]['attribute_value'] = $value;
            }
            $this->data['attributes'] = $attributes_final_data;

            $this->data['category'] = $this->model_category->getActiveCategroy();
		   	$product_data = $this->model_products->getProductData(0,$product_id);

			$this->data['allow_delete'] = FALSE;
	        if ((substr($product_data['sku'],0,6) == '%MUDE%') && is_numeric(substr($product_data['sku'],-6))) {
	        	$product_data['sku'] = '';  // acabou de vir da criação e precisa mudar
	        	$this->data['allow_delete'] = TRUE;
	        }

			$this->data['store'] = $this->model_stores->getStoresData($product_data['store_id']);
            $this->data['product_data'] = $product_data;
            $this->log_data('Products','edit_before',json_encode($product_data),"I");  // LOg DATA

            $integrations = $this->model_integrations->getPrdIntegration($product_id);
            if ($integrations) {
                $integracoes = array();
                $i=0;
                foreach($integrations as $v) {
                	$error_transformation = $this->model_errors_transformation->getErrorsByProductId($product_id,$v['int_to']);
                    $integracoes[$i]['int_to'] = $v['int_to'];
                    $integracoes[$i]['skubling'] = $v['skubling'];
                    $integracoes[$i]['skumkt'] = $v['skumkt'];
                    if ($v['rule']) {
                        $ruleBlock = array();
                        foreach (json_decode($v['rule']) as $ruleBlockId) {
                            $rule = $this->model_blacklist_words->getWordById($ruleBlockId);
                            if ($rule)
                                array_push($ruleBlock, '<span class="label label-danger">'.strtoupper($rule['sentence']).'</span>');
                        }
                        $integracoes[$i]['status_int'] = implode('<br>', $ruleBlock);
                    } elseif ($error_transformation) {
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
                        $integracoes[$i]['status_int'] = '<span class="label label-danger">'.mb_strtoupper($this->lang->line('application_product_smaller_stock'),'UTF-8').'</span>';
                    } elseif ($v['status_int']==13) {
                        $integracoes[$i]['status_int'] = '<span class="label label-danger">'.mb_strtoupper($this->lang->line('application_product_minor_reputation'),'UTF-8').'</span>';
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
             		} elseif ($v['status_int']==90) {
                        $integracoes[$i]['status_int'] = '<span class="label label-default">'.mb_strtoupper($this->lang->line('application_product_inactive'),'UTF-8').'</span>';
                    } elseif ($v['status_int']==91) {
                        $integracoes[$i]['status_int'] = '<span class="label label-default">'.mb_strtoupper($this->lang->line('application_no_logistics'),'UTF-8').'</span>';
                    } elseif ($v['status_int']==99) {
                        $integracoes[$i]['status_int'] = '<span class="label label-warning">'.mb_strtoupper($this->lang->line('application_product_in_analysis'),'UTF-8').'</span>';
                    } else {
                        $integracoes[$i]['status_int'] = '<span class="label label-danger">'.mb_strtoupper($this->lang->line('application_product_out_of_stock'),'UTF-8').'</span>';
                    }
             		$integracoes[$i]['ad_link'] = $v['ad_link'];
					$integracoes[$i]['quality'] = $v['quality'];
					$integracoes[$i]['name'] = $v['name'];
					$integracoes[$i]['approved'] = $v['approved'];
					$integracoes[$i]['auto_approve'] = $v['auto_approve'];
					$integracoes[$i]['status'] = $v['status'];
					$integracoes[$i]['date_last_int'] = $v['date_last_int'];
					$integracoes[$i]['id'] = $v['id'];
                    $i++;
				}
				// $this->data['notAdmin'] = ($this->data['usercomp'] == '1' && $this->data['usergroup'] == '1' ? false : true);
				$this->data['notAdmin'] = ($this->data['usercomp'] == '1' ? false : true);
                $this->data['integracoes'] = $integracoes;

			}

           // Pega os atributos da Mercado Livre
            $arr = $this->pegaCamposMKTdaMinhaCategoria(preg_replace("/[^0-9]/", "", $product_data['category_id']),'ML');
            $campos_att = $arr[0];
            $ignoreML = array('BRAND','EAN','GTIN','SELLER_SKU','EXCLUSIVE_CHANNEL','ITEM_CONDITION');
            $fieldsML = [];
            foreach ($campos_att as $campo_att) {
                in_array($campo_att['id_atributo'], $ignoreML) ? '' : array_push($fieldsML, $campo_att);
            }

			// Agora pego os campos da Via Varejo
			$arr = $this->pegaCamposMKTdaMinhaCategoria(preg_replace("/[^0-9]/", "", $product_data['category_id']),'VIA');
			$campos_att = $arr[0];
			$ignoreVia = array('SELECIONE','GARANTIA');
			$fieldsVia = [];
	        foreach ($campos_att as $campo_att) {
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
	                in_array(strtoupper($campo_att['nome']), ${'ignore'.$sellercenter}) ? '' : array_push(${'fields'.$sellercenter}, $campo_att);
	            }
				$naoachou = $naoachou && empty(${'fields'.$sellercenter});
			}
            $this->data['show_attributes_button'] =$naoachou;

            $this->data['productskit'] = $this->model_products->getProductsKit($product_id);

			$errors_transformation = $this->model_errors_transformation->getErrorsByProductId($product_id);
			$this->data['errors_transformation'] = $errors_transformation;
            $this->data['brands'] = $this->model_brands->getActiveBrands();
            $this->data['product_length_name'] = $this->product_length_name ;
            $this->data['product_length_description'] = $this->product_length_description;
            $this->data['disableBrandCreationbySeller'] = $this->model_settings->getValueIfAtiveByName('disable_brand_creation_by_seller');
            $this->render_template('productskit/edit', $this->data);

        }
	}

	function pegaCamposMKTdaMinhaCategoriaOLD($idcat,$int_to)
    {

		$result= $this->model_categorias_marketplaces->getCategoryMktplace($int_to,$idcat);
		$idCatML= ($result)?$result['category_marketplace_id']:null;
        $result= $this->model_atributos_categorias_marketplaces->getAtributosCategoriaMKT($idCatML,$int_to);

        return $result;
    }

	function pegaCamposMKTdaMinhaCategoria($idcat,$int_to,$idprd = null)
    {
    	$result= $this->model_categorias_marketplaces->getCategoryMktplace($int_to,$idcat);
        $idCatML= ($result)?$result['category_marketplace_id']:null;
        $enriched = false;
        if ($idprd) {
            $productCategoryMkt = $this->model_products_category_mkt->getCategoryEnriched($idprd, $int_to);
            if ($productCategoryMkt) {
                $idCatML = $productCategoryMkt['category_mkt_id'];
                $enriched = true;
            }
        }
        $category_mkt = $this->model_categorias_marketplaces->getCategoryByMarketplace($int_to, $idCatML);
        $result= $this->model_atributos_categorias_marketplaces->getAtributosCategoriaMKT($idCatML, $int_to);

        return [$result, $category_mkt, $enriched];
    }

	public function checkMinValue($field, $min) {
		if ((int)$field < (int)$min) {
			$this->form_validation->set_message('checkMinValue', '%s não pode ser menor que "'.$min.'"');
			return FALSE;
		}
		return true;
	}

	public function check_price_product_kit($val, $param){

		$param = preg_split('/,/', $param);
		$original_price = $this->model_products->getProductById($param[0]);

		if($original_price->price < $val){
			$this->form_validation->set_message('check_price_product_kit', 'O novo preço não pode ser maior que o preço atual (R$ '.$original_price->price.') do produto.');
			return FALSE;
		}
		return TRUE;
	}

	public function updatePrice($id)
	{

		if(!in_array('updateProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

		$response = array();

		if($id) {
			$ids = $this->postClean('id_product');
			foreach($ids as $id_item) {
				$this->form_validation->set_rules('price_product_'.$id_item, $this->lang->line('application_price'), 'trim|required|callback_check_price_product_kit['.$id_item.', price_product_'.$id_item.']');
			}

			$this->form_validation->set_error_delimiters('<p class="text-danger">','</p>');

	        if ($this->form_validation->run() == TRUE) {

				$qtys = $this->postClean('qty_product');
				$prices = $this->postClean('price_product');

				$total_price = 0;
				foreach($ids as $id_item) {
					$qty = $this->postClean('qty_product_'.$id_item);
					$price = $this->postClean('price_product_'.$id_item);
					
					$update = $this->model_products->updatePriceProductKitItem($id,$id_item,$price);
					if (!$update) {
						break;
					}

					$total_price += $price * $qty;
				}
	        	if($update == true) {
	        		$update = $this->model_products->update_price($id,$total_price );
					if($update == true) {
		        		$response['success'] = true;
		        		$response['messages'] = $this->lang->line('messages_successfully_updated');
					}
					else {
						$response['success'] = false;
	        			$response['messages'] = $this->lang->line('messages_error_occurred');
					}
	        	}
	        	else {
	        		$response['success'] = false;
	        		$response['messages'] = $this->lang->line('messages_error_occurred');
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
		// ob_clean();
		echo json_encode($response);
	}

	public function removeProductKit($product_id)
	{
		if(!in_array('updateProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
		if(!$product_id) {
            redirect('dashboard', 'refresh');
        }
		// pega o diretorio de imagens do produto
		$product_data = $this->model_products->getProductData(0,$product_id);

		$delete = $this->model_products->remove($product_id);
		if ($delete) {
		 	$this->session->set_flashdata('success', $this->lang->line('messages_successfully_updated'));
            redirect('products/', 'refresh');
        }
        else {
            $this->session->set_flashdata('error', $this->lang->line('messages_error_occurred'));
            redirect('productsKit/update/'.$product_id, 'refresh');
        }
		
	}
	
	public function getVtexIntegrations()
    {
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

}