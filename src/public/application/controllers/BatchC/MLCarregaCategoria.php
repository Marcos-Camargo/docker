<?php
/*
Baixa as categorias do ML e guarda os campos obrigatórios 
*/  

 class MLCarregaCategoria extends BatchBackground_Controller {
		
	public function __construct()
	{
		parent::__construct();
		// log_message('debug', 'Class BATCH ini.');

   			$logged_in_sess = array(
   				'id' => 1,
		        'username'  => 'batch',
		        'email'     => 'batch@conectala.com.br',
		        'usercomp' => 1,
		        'userstore' => 0,
		        'logged_in' => TRUE
			);
		$this->session->set_userdata($logged_in_sess);
		// carrega os modulos necessários para o Job

    }

	// php index.php BatchC/MLCarregaCategoria run 
	function run($id=null,$params=null)
	{
		/* inicia o job */
		$this->setIdJob($id);
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__)) {
			$this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}
		$this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");
		
		/* faz o que o job precisa fazer */
		$url = 'https://api.mercadolibre.com/sites/MLB/categories';
		$raiz = $this->executeGetCategories($url);
		$raiz = json_decode($raiz,true);
		$campos= Array('');
		$total=0;
		foreach($raiz as $categoriaPai) {
		    $this->mapeiaCategorias($categoriaPai['id'], '',$campos, $total);				
		}
		//echo 'total ='. $total."\n";
		//echo 'campos ='.print_r($campos,true);
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
		
		/*
		$sql = "SELECT * FROM categories_mkts_linked WHERE id_integration = 11";
		$query = $this->db->query($sql);
		$data = $query->result_array();
		$campos= Array('BRAND');
		$total=0;
		foreach($data as $cat) {
			mapeiaCategorias($cat['id_loja'], '', $campos, $total);
		}
		echo 'total ='. $total."\n";
		echo 'campos ='.print_r($campos,true); */
	}
	
	function mapeiaCategorias($categoriaId,$nome,&$campos,&$total)
	{
		$int_to = 'ML';
		$url = 'https://api.mercadolibre.com/categories/'.$categoriaId;
		$categoria = $this->executeGetCategories($url);
		$categoria = json_decode($categoria,true);
		// echo 'Categoria = '.$categoria['id'].'-'.$categoria['name']."\n";
		$nome .= str_replace("'","",str_replace(">","-",$categoria['name']))." > ";
		if (count($categoria['children_categories'])>0) {
			$categoriasFilho = $categoria['children_categories'];
			foreach ($categoriasFilho as $categoriaFilho) {
				$this->mapeiaCategorias($categoriaFilho['id'], $nome,$campos,$total);
			}
			
		} 
		else {
			$nome = substr($nome,0,-3);
			$total++;
			echo "Categoria final ".$categoria['id'].'-'.$nome."\n";
			$sql = "INSERT IGNORE INTO categorias_todos_marketplaces (id_integration,id,nome,int_to) VALUES (11,'".$categoria['id']."','".$nome."','".$int_to."')";
			$query = $this->db->query($sql);
			
			$url = 'https://api.mercadolibre.com/categories/'.$categoria['id'].'?withAttributes=true#json';
			$categoriaAtributos = $this->executeGetCategories($url);
			$categoriaAtributos = json_decode($categoriaAtributos,true);
			$atributos = $categoriaAtributos['attributes'];
			foreach ( $atributos as $atributo) {
				
				$tags = $atributo['tags'];
				if (array_key_exists('read_only', $tags)) {	
					if ($tags['read_only'] == 'true') {
						//atributo que não pode ser alterado
						echo 'Apagando '.$categoria['id']. ' atributo '. $atributo['id']. "\n";
						$query = $this->db->delete("atributos_categorias_marketplaces", array('id_integration' => 11,'id_categoria' => $categoria['id'],'id_atributo' => $atributo['id']));
						continue;
					}
				}
				if (array_key_exists('fixed', $tags)) {	 
					if ($tags['fixed'] == 'true') {
						// se for fixed, não faz sentido perguntar no produto pois não varia
						echo 'Apagando '.$categoria['id']. ' atributo '. $atributo['id']. "\n";
						$query = $this->db->delete("atributos_categorias_marketplaces", array('id_integration' => 11,'id_categoria' => $categoria['id'],'id_atributo' => $atributo['id']));		
						continue;
					}
				}	
				if (array_key_exists('inferred', $tags)) {	 
					if ($tags['inferred'] == 'true') {
						// se for fixed, não faz sentido perguntar no produto pois não varia
						echo 'Apagando '.$categoria['id']. ' atributo '. $atributo['id']. "\n";
						$query = $this->db->delete("atributos_categorias_marketplaces", array('id_integration' => 11,'id_categoria' => $categoria['id'],'id_atributo' => $atributo['id']));
						continue;
					}
				}
				$obrigatorio = 2;
				if (array_key_exists('catalog_required', $tags)) {
					if ($tags['catalog_required'] == 'true') {
						$obrigatorio = 1;
					}
				}	
				if (array_key_exists('required', $tags)) {
					if ($tags['required'] == 'true') {
						$obrigatorio = 1;
					}
				}
				if (array_key_exists('new_required', $tags)) {
					if ($tags['new_required'] == 'true') {
						$obrigatorio = 1;
					}
				}
				$multi_value = 0;
				if (array_key_exists('multivalued', $tags)) {
					if ($tags['multivalued'] == 'true') {
						$multi_value = 1;
					}
				}
				$aceita_variacao = 0;
				if (array_key_exists('allow_variations', $tags)) {
					if ($tags['allow_variations'] == 'true') {
						$aceita_variacao = 1;
					}
				}

				// if ($obrigatorio != 1) {
				// 	continue; // so pego os obrigatórios por enquanto
				// }	
				$valor = '';
				if (array_key_exists('values', $atributo)) {
					$valor = str_replace("'"," ",json_encode($atributo['values'],JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
					//echo $valor;
				}					
				if (!in_array($atributo['id'], $campos)) {
					$campos[] = $atributo['name']; 
					
					try {
						$sql = "INSERT IGNORE INTO atributos_marketplaces
							(id,nome,tipo,valor,int_to,multi_value) 
							VALUES 
							('".$atributo['id']."','".addslashes($atributo['name'])."','".$atributo['value_type']."','".$valor."','".$int_to."',".$multi_value.")";
						$query = $this->db->query($sql);
					} catch (Exception $e) {
					    echo 'Exceção capturada: ',  $e->getMessage(), "\n";
						continue; 
					}
					
				}
				
				$tooltip = '';
				if ($atributo['id'] =='MPN') {
					$tooltip = 'Número da peça do fabricante (Manufacturer Part Number) - Pode ser o EAN, ISBN ou UPC, depende do que está vendendo';
				}
				if (array_key_exists('allowed_units', $atributo)) {
					$tooltip = 'Unidades aceitas: ';
					foreach ($atributo['allowed_units'] as $unidade) {
						$tooltip .= $unidade['name'].', ';
					}
					$tooltip =substr($tooltip,0,-2);
				}
				if (array_key_exists('tooltip', $atributo)) {
					$tooltip = $atributo['tooltip'];
				}
				if (array_key_exists('hint', $atributo)) {
					$tooltip = $atributo['hint'];
				}
				
				
				$data = array (
					'id_integration' => 11,
					'id_categoria' => $categoria['id'],
					'id_atributo' => $atributo['id'],
					'obrigatorio' => $obrigatorio,
					'int_to' => $int_to,
					'variacao' =>$aceita_variacao,
					'valor' =>$valor,
					'nome' => addslashes($atributo['name']),
					'tipo' => $atributo['value_type'],
					'multi_valor' => $multi_value,
					'tooltip' => $tooltip
				); 
				if (is_null($atributo['value_type'])) {  // se ficou nulo, não é mais preciso. 
					echo 'Apagando '.$categoria['id']. ' atributo '. $atributo['id']. "\n";
					$query = $this->db->delete("atributos_categorias_marketplaces", array('id_integration' => 11,'id_categoria' => $categoria['id'],
					'id_atributo' => $atributo['id']));
				} else {
					$query = $this->db->replace("atributos_categorias_marketplaces", $data);
				}
				
				/*
				$sql = "INSERT IGNORE INTO atributos_categorias_marketplaces 
							(id_integration, id_categoria, id_atributo, obrigatorio, int_to, variacao, valor, nome, tipo, multi_valor) 
							VALUES 
							(11,'".."','".."',,'".."', "..",'".$valor."','".."','".."',"..")";
				echo $sql;
				 * $query = $this->db->query($sql);
				 * */
							
				
			}
			
		}
	}

	function executeGetCategories($url){
	    $curl_handle = curl_init();
	    curl_setopt($curl_handle, CURLOPT_URL, $url);
	    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
	    $response = curl_exec($curl_handle);
	    curl_close($curl_handle);
	    return $response;
	}

}
