<?php

/**
 * @property Model_csv_import_attributes_products $model_csv_import_attributes_products
 * @property Model_products $model_products
 * @property Model_categorias_marketplaces $model_categorias_marketplaces
 * @property Model_atributos_categorias_marketplaces $model_atributos_categorias_marketplaces
 * @property Model_settings $model_settings
 * @property Model_users $model_users
 * @property Model_groups $model_groups
 * @property Model_stores $model_stores
 */
class LerPlanilhaAtributosProdutos extends BatchBackground_Controller {
	
	var $permiteVariacaoNosAtributos = false;
	var $variacaoCorDefault = false;
	var $variacaoTamanhoDefault = false;

	public function __construct()
	{
		parent::__construct();

		$logged_in_sess = array(
			'id' 		=> 1,
			'username'  => 'batch',
			'email'     => 'batch@conectala.com.br',
			'usercomp'  => 1,
			'userstore' => 0,
			'logged_in' => TRUE
		);
		$this->session->set_userdata($logged_in_sess);
		
		// carrega os modulos necessários para o Job
		$this->load->model('model_csv_import_attributes_products');
		$this->load->model('model_products');
		$this->load->model('model_categorias_marketplaces');
		$this->load->model('model_atributos_categorias_marketplaces');
		$this->load->model('model_settings');
		$this->load->model('model_users');
		$this->load->model('model_groups');
		$this->load->model('model_stores');
		$this->load->library('excel');
    }

    function run($id=null,$params=null)
	{
		/* inicia o job */
		$this->setIdJob($id);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$modulePath = (str_replace("BatchC/", '',$this->router->directory)) . $this->router->fetch_class();
        if (!$this->gravaInicioJob($modulePath, __FUNCTION__, $params)) {
			$this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}
        $this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");
        
		$this->validate();
		$this->import();

        /* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
	
	private function validate() {
		$list = $this->model_csv_import_attributes_products->getList();
		if(!$list) {
			echo "Nenhuma planilha nova para validar\n";
		}
		foreach ($list as $item) {
			$this->validateCsv($item);
		}
		echo "Acabou a validação\n";
	}

	private function validateCsv($item)
    {
        $user           = $this->model_users->getUserByEmail($item['email']);
        $group          = $this->model_groups->getUserGroupByUserId($user[0]['id']);
        $is_admin       = $group['only_admin'] == 1;
        $store_ids      = $is_admin ? null : $this->model_stores->getStoreIdByUser($user[0]['id']);

		echo "Procurando ".FCPATH.$item['path']."\n";
		$message = '';
		if (!file_exists(FCPATH.$item['path'])) {
			$message = "Arquivo inexistente ".FCPATH.$item['path'];
			echo $message."\n";
			$this->model_csv_import_attributes_products->insertErrorTransformation($item['id'], $message);
			$this->model_csv_import_attributes_products->markChecked($item['id'], $message == '');
			return;
		}
		echo "Lendo arquivo ".FCPATH.$item['path']."\n";
		$linhas = $this->lerExcel(FCPATH.$item['path']);
		echo "Terminou de ler\n";
		$attributes_rules = array();

		$pos = 1;
		$first_category = null;
		foreach ($linhas as $key => $linha) {

			if ($pos == 1) {
				for ($i = 6; $i <= count($linha); $i++) {
					if (trim($linha[$i]) == '') continue; 
					$attr_arr = explode('_', $linha[$i]);
					echo $i . " - ". $linha[$i] .PHP_EOL;
					echo json_encode($attr_arr) . PHP_EOL;					
					$required = $attr_arr[0] == '*';
					$index = 0;
					if (count($attr_arr) > 2) {
						$index++;
					}

					$int_to = $attr_arr[$index];
					$label = $attr_arr[$index+1];
					$position = $i;

					$attribute_rule = array(
						"required" 	=> $required,
						"int_to" 	=> $int_to,
						"label" 	=> $label,
						"position" 	=> $position,
						"type" 		=> 'string'
					);

					array_push($attributes_rules, $attribute_rule);
				}
				$pos++;
			}
			else {
				if (is_null($linha[2])) {
					$message = "Linha com formato inválido. Faltando ID do produto na coluna 2";
					echo $message . PHP_EOL;
					$this->model_csv_import_attributes_products->insertErrorTransformation($item['id'], $message);
					continue; 
				}
				$product = $this->model_products->getProductData(0, $linha[2]);
				
				if (is_null($product)) {
					if (!isset($linha[3])) { $linha[3] = '';}
					$message = 'Produto com com id (colunda 2): '.$linha[2].' sku (coluna 3): '. $linha[3] .' não encontrado';
					echo $message . PHP_EOL;
					$this->model_csv_import_attributes_products->insertErrorTransformation($item['id'], $message);
					continue;
				}
				echo PHP_EOL . $key .' - Validando Produto:' . $product['id'];

                if (!$is_admin && !in_array($product['store_id'], $store_ids)) {
                    $message = 'Produto com sku (coluna 3): '. $linha[3] .' não pertence ao usuário';
                    echo $message . PHP_EOL;
                    $this->model_csv_import_attributes_products->insertErrorTransformation($item['id'], $message);
                    continue;
                }

				if ($product['store_id'] != $linha[1]) {
					$message = 'Produto com sku (coluna 3): '. $linha[3] .' não pertence a Loja (coluna 1): '. $linha[1];
					echo $message . PHP_EOL;
					$this->model_csv_import_attributes_products->insertErrorTransformation($item['id'], $message);
					continue; 
				}

				if ($product['category_id'] != '["'.$linha[4].'"]') {
					$message = 'Produto com sku (coluna 3): '. $linha[3] .' está com a categoria (coluna 4) diferente de: '. $linha[4] .' - '. $linha[5];
					echo $message . PHP_EOL;
					$this->model_csv_import_attributes_products->insertErrorTransformation($item['id'], $message);
				}

				if (is_null($first_category)) {
					$first_category = $linha[4];
				}
				else {
					if ($first_category != $linha[4]) {
						$message = 'Produto com sku (coluna 3): '. $linha[3] .' está com a categoria (coluna 4) diferente da categoria da importação: '. $first_category;
						echo $message . PHP_EOL;
						$this->model_csv_import_attributes_products->insertErrorTransformation($item['id'], $message);
						continue;
					}
				}

				foreach($attributes_rules as $attribute_rule) {
					$category_marketplace = $this->model_categorias_marketplaces->getCategoryMktplace($attribute_rule['int_to'], $linha[4]);
					echo PHP_EOL. $attribute_rule['int_to'] .' - ' . $category_marketplace['category_marketplace_id'] .' - ' .  $attribute_rule['label'] . PHP_EOL;
					$attribute = $this->model_atributos_categorias_marketplaces->getAttributesByName($attribute_rule['int_to'], $category_marketplace['category_marketplace_id'], $attribute_rule['label']);
					$this->permiteVariacaoNosAtributos = $this->model_settings->getValueIfAtiveByName('permite_variacao_nos_atributos');
					if ($this->permiteVariacaoNosAtributos) {
						if (is_null($attribute)){
							$attribute = $this->model_atributos_categorias_marketplaces->getAttributesByNameWithVariants($attribute_rule['int_to'], $category_marketplace['category_marketplace_id'], $attribute_rule['label']);
						}
					}

					if (!is_null($attribute)) {
						$attribute_rule['required'] = $attribute['obrigatorio'] == 1;
						if ($attribute_rule['required']) {
							if (($linha[$attribute_rule['position']] == '') || (is_null($linha[$attribute_rule['position']]))) {
								$message = 'Produto com sku (coluna 3): '. $linha[3] . ' - Atributo (coluna '.$attribute_rule['position'].'): '. $attribute_rule['label'] . ' é de preenchimento obrigatório no marketplace: '. $attribute_rule['int_to'];
								echo $message . PHP_EOL;
								$this->model_csv_import_attributes_products->insertErrorTransformation($item['id'], $message);
							}
						}

						if (($linha[$attribute_rule['position']] == '') || (is_null($linha[$attribute_rule['position']]))) {
							continue;
						}

						if ($attribute['valor'] != '') {
							$values = json_decode($attribute['valor'], true);
							$hasCorrectValue = false;
							$valid_values = '';
							foreach ($values as $value) {
								$value_to_compare = '';
								if ($attribute_rule['int_to'] == 'ML') {
									$value_to_compare = $value['name'];
								}
								else if ($attribute_rule['int_to'] == 'VIA') {
									$value_to_compare = $value['udaValue'];
								} 
								else if (array_key_exists('FieldValueId', $value)) {
									$value_to_compare = $value['Value'];
								}

								$valid_values .= ($valid_values == '' ? '' : ', ') . $value_to_compare;
								if ($linha[$attribute_rule['position']] == $value_to_compare) {
									$hasCorrectValue = true;
								}
							}

							if (count($values) == 0) { $hasCorrectValue = true;}

							if (!$hasCorrectValue) {
								$message = 'Produto com sku (coluna 3): '. $linha[3] . ' - O atributo (coluna '.$attribute_rule['position'].')"'. ($attribute_rule['required'] ? '*_' : '') .  $attribute_rule['int_to'] .'_'. $attribute_rule['label'] . '" aceita apenas os seguintes valores: ['. $valid_values.']';
								echo $message . PHP_EOL;
								$this->model_csv_import_attributes_products->insertErrorTransformation($item['id'], $message);
							}
						}
					}
					else {
						echo 'Não encontrou o atributo' . PHP_EOL;
					}
				}
			}
		}
		echo PHP_EOL ; 
		$this->model_csv_import_attributes_products->markChecked($item['id'], $message == '' );
	}

	private function import() {
		$list = $this->model_csv_import_attributes_products->getList(true);

		$this->permiteVariacaoNosAtributos = $this->model_settings->getValueIfAtiveByName('permite_variacao_nos_atributos');
        if ($this->permiteVariacaoNosAtributos) {
			$this->variacaoCorDefault = $this->model_settings->getValueIfAtiveByName('variacao_cor_default');
			$this->variacaoTamanhoDefault = $this->model_settings->getValueIfAtiveByName('variacao_tamanho_default');
		}

		foreach ($list as $item) {
			if (($item['valid'] == 1) && ($item['sent_email'] == 0)) {
				$this->importCsv($item);
			}
		}
	}

	private function importCsv($item) {
		echo "Processando ".FCPATH.$item['path']."\n";
		$linhas = $this->lerExcel(FCPATH.$item['path']);
			
		$attributes_rules = array();

		$pos = 1;		
		foreach ($linhas as $linha) {
			if ($pos == 1) {
				for ($i = 6; $i <= count($linha); $i++) {
					if (trim($linha[$i]) == '') continue; 
					$attr_arr = explode('_', $linha[$i]);
					echo $i . " - ". $linha[$i] .PHP_EOL;
					echo json_encode($attr_arr) . PHP_EOL;					
					$required = $attr_arr[0] == '*';
					$index = 0;
					if (count($attr_arr) > 2) {
						$index++;
					}

					$int_to = $attr_arr[$index];
					$label = $attr_arr[$index+1];
					$position = $i;

					$attribute_rule = array(
						"required" 	=> $required,
						"int_to" 	=> $int_to,
						"label" 	=> $label,
						"position" 	=> $position,
						"type" 		=> 'string'
					);

					array_push($attributes_rules, $attribute_rule);
				}
				$pos++;
			}
			else {
				if (is_null($linha[2])) {
					echo "Pulando uma linha sem id\n";
					continue;
				}
				$product = $this->model_products->getProductData(0, $linha[2]);
				if (!$product) {
					echo "Pulando uma linha sem produto encontrdo de id ".$linha[2]."\n";
					continue;
				}
				echo "Alterando o produto ".$product['id']."\n";
				$product_changed = false;
				$int_to = '';
				foreach($attributes_rules as $attribute_rule) {
					$category_marketplace = $this->model_categorias_marketplaces->getCategoryMktplace($attribute_rule['int_to'], $linha[4]);
					$attribute = $this->model_atributos_categorias_marketplaces->getAttributesByName($attribute_rule['int_to'], $category_marketplace['category_marketplace_id'], $attribute_rule['label']);
					$this->permiteVariacaoNosAtributos = $this->model_settings->getValueIfAtiveByName('permite_variacao_nos_atributos');
					if ($this->permiteVariacaoNosAtributos) {
						$attribute_variant = $this->model_atributos_categorias_marketplaces->getAttributesVariantByName($attribute_rule['int_to'], $category_marketplace['category_marketplace_id'], $attribute_rule['label']);
                        if ($attribute_variant) {
                            $attribute = $attribute_variant;
                        }
					}
//					if ($attribute_rule["required"] && !empty($attribute) && $attribute["obrigatorio"] == 1) {
//						$attribute = $this->model_atributos_categorias_marketplaces->getAttributesVariantByName($attribute_rule['int_to'], $category_marketplace['category_marketplace_id'], $attribute_rule['label']);
//					}else {
//						$attribute = $this->model_atributos_categorias_marketplaces->getAttributesByName($attribute_rule['int_to'], $category_marketplace['category_marketplace_id'], $attribute_rule['label']);
//					}
					if (!is_null($attribute)) {
						if (array_key_exists($attribute_rule['position'], $linha)) {
							$valor = $linha[$attribute_rule['position']];
							if (($valor != '') && (!is_null($valor))) {
                                $attribute_value = $this->getRealValue($attribute, $valor);
                                if (!is_null($attribute_value)) {
                                    $data_attribute_product = array(
                                        'id_product' => $product['id'],
                                        'id_atributo' => $attribute['id_atributo'],
                                        'valor' => $attribute_value,
                                        'int_to' => $attribute_rule['int_to']
                                    );
                                    $int_to = $attribute_rule['int_to'];
                                    $this->model_atributos_categorias_marketplaces->saveProdutosAtributos($data_attribute_product);
                                }
								$product_changed = true;
							}
						}
					}
				}

				if ($product['has_variants'] != '') {
					$category_marketplace = $this->model_categorias_marketplaces->getCategoryMktplace($int_to, $linha[4]);
					if ($this->permiteVariacaoNosAtributos !== false) {
						if ($this->variacaoCorDefault !== false) {
							$attribute = $this->model_atributos_categorias_marketplaces->getAttributesVariantByName($int_to, $category_marketplace['category_marketplace_id'], 'Cor');
							if (!is_null($attribute)) {
								$productAttributeCor = $this->model_atributos_categorias_marketplaces->getProductAttributeById($product['id'], $attribute['id_atributo']);
								if (is_null($productAttributeCor)) {
									$data_attribute_product = array(
										'id_product' => $product['id'],
										'id_atributo' => $attribute['id_atributo'],
										'valor' => $this->variacaoCorDefault,
										'int_to' => $int_to
									);
									$this->model_atributos_categorias_marketplaces->saveProdutosAtributos($data_attribute_product);
									$product_changed = true; 
								}
							}
						}

						if ($this->variacaoTamanhoDefault !== false) {
							$attribute = $this->model_atributos_categorias_marketplaces->getAttributesVariantByName($int_to, $category_marketplace['category_marketplace_id'], 'Tamanho');
							if (!is_null($attribute)) {
								$productAttributeTamanho = $this->model_atributos_categorias_marketplaces->getProductAttributeById($product['id'], $attribute['id_atributo']);
								if (is_null($productAttributeTamanho)) {
									$data_attribute_product = array(
										'id_product' => $product['id'],
										'id_atributo' => $attribute['id_atributo'],
										'valor' => $this->variacaoTamanhoDefault,
										'int_to' => $int_to
									);
									$this->model_atributos_categorias_marketplaces->saveProdutosAtributos($data_attribute_product);
									$product_changed = true; 
								}
							}
						}
					}
				}
				if ($product_changed) { // altera o produto para entrar na fila para re-enviar. 
					$product = $this->model_products->update(array('date_update' => date('Y-m-d H:i:s')),$product['id'], 'Alterado na informação de Atributos');
				}
			}
		}
		$sellercenter = $this->model_settings->getValueIfAtiveByName('sellercenter');
        if (!$sellercenter) {
            $sellercenter = 'conectala';
		}
		
		$data = [];
		$data['filename']  = $item['name_original'];
        $data['created_date'] = $item['date_create'];
        $subject = 'Sucesso na importação do arquivo '. $item['name_original'];
        $data['info_by_line'] = [];
		$sellercenter_name = $this->model_settings->getValueIfAtiveByName('sellercenter_name');
        if (!$sellercenter_name) {
            $sellercenter_name = 'Conecta Lá';
        }
        $data['sellercentername'] = $sellercenter_name;
        if (is_file(APPPATH.'views/mailtemplate/'.$sellercenter . '/csv_import_attributes_report.php')) {
            $body= $this->load->view('mailtemplate/'.$sellercenter.'/csv_import_attributes_report',$data,TRUE);
        }
        else {
            $body= $this->load->view('mailtemplate/default/csv_import_attributes_report',$data,TRUE);
        }
        $from = $this->model_settings->getValueIfAtiveByName('email_marketing');
        if (!$from) {
            $from = 'marketing@conectala.com.br';
        }
		$this->sendEmailMarketing($item['email'], $subject, $body, $from, $item['path']);
		$this->model_csv_import_attributes_products->markSent($item['id']);

	}

	private function lerExcel($file, $sheet = 0, $inicio = 0)
	{
		$objPHPExcel = PHPExcel_IOFactory::load($file);
		$objWorksheet = $objPHPExcel->getSheet($sheet);
		
		$linhas = array();
		$cntlinhavazias = 0;
		foreach ($objWorksheet->getRowIterator() as $row) {
			$rowIndex = $row->getRowIndex() - 1;
			if ($rowIndex < $inicio) {
				continue;  
			}   
			$cellIterator = $row->getCellIterator();
			$linha = array();
			$hasItem = false;
			foreach ($cellIterator as $cell) {
			    $colIndex = PHPExcel_Cell::columnIndexFromString($cell->getColumn());
				$linha[$colIndex] = $cell->getValue();
				if (!is_null($linha[$colIndex])) {
					$hasItem = true;
				}
			}
			if ($hasItem) {
				$linhas[] = $linha;
				$cntlinhavazias = 0;
			}
			else {
				$cntlinhavazias++;
				if ($cntlinhavazias >=10) {
					echo "10 linhas em branco\n";
					break;
				}
			}
		}
		return $linhas;
	}
	
	private function getRealValue($attribute, $value_csv) {
		
		if ($attribute['tipo'] != 'list') {
			return $value_csv;
		}

		$values = json_decode($attribute['valor'], true);
		$valid_values = '';
		foreach ($values as $value) {
			$value_to_compare = '';
            $field = 'FieldValueId';
			if ($attribute['int_to'] == 'ML') {
				$value_to_compare = $value['name'];
				$field = 'name';
			}
			else if ($attribute['int_to'] == 'VIA') {
				$value_to_compare = $value['udaValue'];
				$field = 'udaValue';
			} 
			else if (array_key_exists('FieldValueId', $value)) {
				$value_to_compare = $value['Value'];
				$field = 'FieldValueId';
			}

			$valid_values .= ($valid_values == '' ? '' : ', ') . $value_to_compare;
			if ($value_csv == $value_to_compare) {
				return $value[$field];
			}
		}

		return null;
		
	}
}