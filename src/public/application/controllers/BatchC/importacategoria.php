<?php

	function run($me,$cmd = NULL) 
	{
		$fn = fopen("/var/www/html/fase1/importacao/categorias_conecta_v3.csv","r");
		$errados = 0;
		$certos=0;
		while (! feof($fn)) {
			$linha = fgets($fn);
			$cat = explode(";", $linha);
			
			$sql = "SELECT * FROM tipos_volumes WHERE codigo = ". trim($cat[1]);
			$query = $me->db->query($sql);
			$tipo = $query->row_array();
			if (!isset($tipo)) {
				$errados++;
				echo "Não achei este tipo\n";
				continue;
			}
			
			$sql = "SELECT * FROM categories WHERE name Like '".trim($cat[0])."'"; 
		
			$query = $me->db->query($sql);
			$data = $query->row_array();
			
			
			if (!isset($data)) {
				$errados++;
				echo "categoria ".$cat[0]." tipo ".$tipo['id']." Não achei esta categoria\n";
				continue;
			}
			$sql = "UPDATE categories SET tipo_volume_id = ".$tipo['id']." WHERE id = ".$data['id'];
			$query = $me->db->query($sql);
			// echo ' ID = '. $tipo['id']."\n";	
			$certos++;		
		}
		fclose($fn);
		echo 'certos ='.$certos."\n";
		echo '$errados ='.$errados."\n";
	}
	
	function mapeiaCategorias($me, $categoriaId,$nome,&$campos,&$total)
	{

		
		$url = 'https://api.mercadolibre.com/categories/'.$categoriaId;
		$categoria = executeGetCategories($url);
		$categoria = json_decode($categoria,true);
		// echo 'Categoria = '.$categoria['id'].'-'.$categoria['name']."\n";
		$nome .= str_replace("'","",str_replace(">","-",$categoria['name']))." > ";
		if (count($categoria['children_categories'])>0) {
			$categoriasFilho = $categoria['children_categories'];
			foreach ($categoriasFilho as $categoriaFilho) {
				mapeiaCategorias($me, $categoriaFilho['id'], $nome,$campos,$total);
			}
			
		} 
		else {
			$nome = substr($nome,0,-3);
			$total++;
			// echo "Categoria final ".$categoria['id'].'-'.$nome."\n";
			$sql = "INSERT IGNORE INTO categorias_todos_marketplaces (id_integration,id,nome) VALUES (11,'".$categoria['id']."','".$nome."')";
			$query = $me->db->query($sql);
			
			$url = 'https://api.mercadolibre.com/categories/'.$categoria['id'].'?withAttributes=true#json';
			$categoriaAtributos = executeGetCategories($url);
			$categoriaAtributos = json_decode($categoriaAtributos,true);
			$atributos = $categoriaAtributos['attributes'];
			foreach ( $atributos as $atributo) {
				$tags = $atributo['tags'];
				if (array_key_exists('catalog_required', $tags)) {
					if ($tags['catalog_required'] == 'true') {
						if ($atributo['id']=='BRAND') {
							// ignora o brand que já é obrigatório
							continue;
						}
						// echo 'campo: '.$atributo['name']."\n";								
						if (!in_array($atributo['id'], $campos)) {
							$campos[] = $atributo['name']; 
							$valor = '';
							if (array_key_exists('values', $atributo)) {
								$valor = str_replace("'"," ",json_encode($atributo['values']));
								//echo $valor;
							}
							try {
								$sql = "INSERT IGNORE INTO atributos_marketplaces
									(id,nome,tipo,valor) 
									VALUES 
									('".$atributo['id']."','".$atributo['name']."','".$atributo['value_type']."','".$valor."')";
	
								$query = $me->db->query($sql);
							} catch (Exception $e) {
							    echo 'Exceção capturada: ',  $e->getMessage(), "\n";
								continue; 
							}
							
						}
						$sql = "INSERT IGNORE INTO atributos_categorias_marketplaces 
									(id_integration, id_categoria, id_atributo, obrigatorio) 
									VALUES 
									(11,'".$categoria['id']."','".$atributo['id']."',1)";
						$query = $me->db->query($sql);			
					}
				}
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