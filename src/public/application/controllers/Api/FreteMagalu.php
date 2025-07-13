<?php
/*
defined('BASEPATH') OR exit('No direct script access allowed');
 
class Apiitem extends Admin_Controller  
{
*/   
/* Método de chamada redefinido no config/routes.php
 * 
 * url_site/Apimag/freight
 * 
 */
require APPPATH . '/libraries/REST_Controller.php'; 
     
class FreteMagalu extends FreteConectala {
    
	  /**
     * Get All Data from this method.
     *
     * @return Response
    */
    public function __construct() {
       parent::__construct();
    }

    /**
     * Post All Data from this method.
     *
     * @return Response
    */
    public function index_post()
    {
       // $data = json_decode(file_get_contents('php://input'), true);
		$dataPost = file_get_contents('php://input');
		$data = json_decode($dataPost,true);
		if (is_null($data)) {
			$ret= array(
				'message' => 'Delivery not available',
				'code' => 'delivery_not_available'
			);
			echo json_encode($ret);
			$this->response($ret, REST_Controller::HTTP_BAD_REQUEST);
			// retirar depois
			$this->log_data('api', 'FreteMagalu Consulta Frete', 'Dados com formato errado'.print_r(file_get_contents('php://input'),true) ,'E');
			die;
		}
		header('Content-type: application/json');
		
		// Pego o Token pro frete Rápido 
		/*
		$sql = 'SELECT * FROM settings WHERE name = ?';
		$query = $this->db->query($sql, array('token_frete_rapido_master'));
		$row = $query->row_array();
		if ($row) {
			$token_fr = $row['value'];
		} else {
			$ret= array(
				'message' => 'Delivery not available',
				'code' => 'delivery_not_available'
			);
			echo json_encode($ret);
			$this->response($ret, REST_Controller::HTTP_BAD_REQUEST);
			$this->log_data('api','FreteMagalu Consulta Frete','Falta o cadastro do parametro token_frete_rapido_master','E');
			die; 
		}
		 * *
		 */
		$CNPJ = '30120829000199'; // CNPJ fixo do ConectaLa
		$cross_docking_default = 5;// tempo default de cross_docking  - depois colocar no prefixes
		$cross_docking = $cross_docking_default;  
		
		if ( is_Null($data)) {
			$this->log_data('api', 'FreteMagalu Consulta Frete', 'ERRO - Dados com formato errado recebidos ='.$dataPost,'E');
			$ret= array(
				'message' => 'Delivery not available',
				'code' => 'delivery_not_available'
			);
			echo json_encode($ret);
			$this->response($ret, REST_Controller::HTTP_BAD_REQUEST);
			die; 
		} 
        $zip = $data['zipcode'];
		$zip = substr('00000000'.$zip,-8); 
		
		$total_price = 0;
        $fr = Array();
        $fr['destinatario'] = Array ( 'tipo_pessoa' => 1,
        'endereco' => Array ( 'cep' => strval($data['zipcode'])));
		$erros = false;
		$prim_stor = 0; 
		foreach ($data['items'] as $vol) {
			// rick será que vem o sku do bling ou o sku do ML ?
			$sql = 'SELECT * FROM bling_ult_envio WHERE int_to ="MAGALU" AND skumkt = ?';
			$query = $this->db->query($sql, array($vol['sku']));
			$row_ult = $query->row_array();
			//var_dump($row_ult);
			if (is_null($row_ult)) {
				if (strrpos($sku, '-') !=0) {
					$sku = substr($vol['sku'], 0, strrpos($vol['sku'], '-'));
					$variant = substr($vol['sku'], strrpos($vol['sku'], '-')+1);
					$sql = 'SELECT * FROM bling_ult_envio WHERE int_to ="MAGALU" AND skubling = ?';
					$query = $this->db->query($sql, array($sku));
					$row_ult = $query->row_array();
				}
				if (is_null($row_ult)) {
					// produto inexistente. todo o pacote tem que ser invalidado
					$erros = true;
					echo 'aqui1';
				} else {
					$sql = 'SELECT * FROM prd_variants WHERE prd_id = ? and variant = ?';
					$query = $this->db->query($sql, array($row_ult['prd_id'],$variant));
					$prd_variants = $query->row_array();
					if (is_null($prd_variants)) {
						// produto inexistente. todo o pacote tem que ser invalidado
						$erros = true; 
					} else {
						$row_ult['qty_atual'] =$prd_variants['qty'];  // vale o estoque do produto variante	
					}
				}

			}
			if (!$erros) {
				if ($prim_stor == 0) {
					$prim_stor = $row_ult['store_id']; // leio a primeira compania do pacote 
				}
				if (($prim_stor != $row_ult['store_id']) || $erros || ($vol['quantity']> $row_ult['qty_atual'] )) {
					// echo 'erro aqui 3';
					$erros = true;
				}
				else {
				    // Produto existe e é da mesma company então monto array de consulta a frete rápido
					$sku = $row_ult['skubling'];
					$store_id = $row_ult['store_id'];
					
					$tipo_volume_codigo = intval($row_ult['tipo_volume_codigo']);
					if (is_null($row_ult['tipo_volume_codigo'])) {
						$tipo_volume_codigo = 999; 
					}
					//$tipo_volume_codigo = $row['tipo_volume_codigo'];
					$sql = 'SELECT * FROM prefixes WHERE id = ?';
					$query = $this->db->query($sql, array($store_id));
					$row_pr = $query->row_array();
					$origin = $row_pr['cep'];
					
					if (array_key_exists('price', $vol)) {
						$tot_price = (float) $vol['price'];
					}
					else {
						$tot_price = $row_ult['price'] * $vol['quantity'] ;
					}
					
					if (isset($row_ult['crossdocking'])) {  // pega o pior tempo de crossdocking dos produtos
						if (((int) $row_ult['crossdocking'] + $cross_docking_default) > $cross_docking) {
							$cross_docking = $cross_docking_default + (int) $row_ult['crossdocking']; 
						};
					}
					$total_price+=$tot_price;
		            $vl = Array ( 'tipo' => $tipo_volume_codigo,  
			            'sku' => $sku,
			            'quantidade' => $vol['quantity'],
			            'altura' => (float) $row_ult['altura'] / 100,
			            'largura' => (float) $row_ult['largura'] /100,
			            'comprimento' => (float) $row_ult['profundidade'] /100,
			            'peso' => (float) $row_ult['peso_bruto'],  
			            'valor' => $tot_price,
			            'volumes_produto' => 1,
			            'consolidar' => false,
			            'sobreposto' => false,
			            'tombar' => false
					);
		            $fr['volumes'][] = $vl;
					$stock = (int) $row_ult['qty'];
				}
            }

			// monto parte da string de retorno para cada item 
			$skus_key[] = $vol['sku']; 
			$itensRet[] = array(
				'sku' => $vol['sku'],
                'quantity' => (int) $vol['quantity']
			);
		}

		if ($erros) {
			// invalido os demais itens anteriores ao erro de produto ou empresa diferente 
			$ret= array(
				'message' => 'Delivery not available',
				'code' => 'delivery_not_available'
			);
			foreach ($data['items'] as $vol) {
				$ret['items'][] = Array(
					'sku' => $vol['sku']
				); 
			}
			echo json_encode($ret);
			$this->log_data('api', 'FreteMagalu Consulta Frete', 'ERRO - Produto Inexistente ou produtos de lojas diferentes='.print_r($data,true),'W');
			$this->response($ret, REST_Controller::HTTP_BAD_REQUEST);
			die; 
		}
		
		// tudo ok, vou consultar o Frete Rápido 
        $fr['remetente'] = Array (
        	'cnpj' => $CNPJ
			);
		$fr['expedidor'] = Array (
        	'cnpj' => $row_pr['CNPJ'],
			'endereco' => Array( 'cep' => $row_pr['cep'])
			);
		$fr['codigo_plataforma'] = 'nyHUB56ml';
		$fr['token'] = '5d1c7889ff8789959cb39eb151a3698e';  
		//$fr['token'] = $token_fr; 
		$fr['retornar_consolidacao'] = true; 
		//var_dump($fr);
		$json_data = json_encode($fr,JSON_UNESCAPED_UNICODE);
		$json_data = stripslashes($json_data);
		
		//rick - talvez mudar para o protocolo grpc 
		//https://github.com/freterapido/sdk-grpc
		//https://github.com/freterapido/sdk-grpc/blob/master/exemplos/php/index.php
		
		$url = 'https://freterapido.com/api/external/embarcador/v1/quote-simulator';
		$dataFR = $this->get_web_page( $url,$json_data);
		
		$erros = false; 
		
		//rick - testando se voltou tudo ok - Feito 
		if (!($dataFR['httpcode']=='200'))  {
			// Consulta ao Frete Rápido não funcionou. 
			// Nao consegui alocar frete então devolvo a resposta esperada 
				
			$ret= array(
				'message' => 'Delivery not available',
				'code' => 'delivery_not_available'
			);
			foreach ($data['items'] as $vol) {
				$ret['items'][] = Array(
					'sku' => $vol['sku']
				); 
			}
		  	$msgErro = 'ERRO - httpcode: '.$dataFR['httpcode'].' RESPOSTA FR: '.$dataFR['content'].' DADOS ENVIADOS:'.$json_data; 
		
			$dataErro = json_decode($dataFR['content'],true);
			if (array_key_exists('error', $dataErro)) {
				if ($dataErro['error'] == 'CEP de origem/destino inválido.') {
					$ret= array(
						'message' => 'Invalid zipcode',
						'code' => 'invalid_zipcode'
					);
					$msgErro = '';				
				}
			}
	
			echo json_encode($ret);
			$this->response($ret, REST_Controller::HTTP_BAD_REQUEST);
			if ($msgErro == '') {
				$this->log_data('api', 'FreteMagalu Consulta Frete', $msgErro, 'E' ); 
			}
			
			die; 
		} 
		$retorno_fr = $dataFR['content'];
		$dataF = json_decode($dataFR['content'],true);
			
		// var_dump($data);
		$ret = Array();
		$ret['items']= $itensRet;
		
		// funcionou ok, devolvo a cotação
		$transp = $dataF['transportadoras'];
		if (count($transp) == 0) {
			// nao veio nenhuma transportadora 
			$ret= array(
				'message' => 'Delivery not available',
				'code' => 'delivery_not_available'
			);
			foreach ($data['items'] as $vol) {
				$ret['items'][] = Array(
					'sku' => $vol['sku']
				); 
			}
			echo json_encode($ret);
			$this->response($ret, REST_Controller::HTTP_BAD_REQUEST);
			// retirar depois
			$this->log_data('api', 'FreteMagalu Consulta Frete', 'SEM TRANSPORTADORA: DADOS ENVIADOS:'.print_r($json_data,true).' RECEBIDOS '.print_r($retorno_fr,true),'E');
			
			die;
		} 
		// Adiciono a taxa de frete ao valor retornado 
		//$sql = 'SELECT av.value FROM attribute_value av, attributes a WHERE a.name ="frete_taxa" and a.id = av.attribute_parent_id';
		//$query = $this->db->query($sql);
		//$row_taxa = $query->row_array();
		//$transp[0]['preco_frete'] += (float) $row_taxa['value'];
		if ($total_price <= 40) {
			$transp[0]['preco_frete'] += 0.5;		
		}elseif ($total_price <= 70) {
			$transp[0]['preco_frete'] += 0.8;	
		}elseif ($total_price <= 100) {
			$transp[0]['preco_frete'] += 1;	
		}elseif ($total_price <= 150) {
			$transp[0]['preco_frete'] += 1.5;	
		}elseif ($total_price <= 200) {
			$transp[0]['preco_frete'] += 2;	
		}elseif ($total_price <= 250) {
			$transp[0]['preco_frete'] += 3;	
		}else {
			$transp[0]['preco_frete'] += 3.5;	
		}
		$ret['delivery_options'] = Array(Array( 
		        'id' => $dataF['token_oferta'],
		        'type' => 'conventional',
		        'name' => 'Entrega normal', 
				'price' => number_format($transp[0]['preco_frete'], 2, '.', ''), 
				'delivery_days' => $transp[0]['prazo_entrega'] + $cross_docking
			));
	
		$ret = Array('packages' => array ($ret));
		
		// var_dump($ret);
		//$json_data = json_encode($ret,JSON_UNESCAPED_UNICODE);
		//$json_data = stripslashes($json_data);
		// Retorna Resposta para o a MAGALU 
		//echo $json_data;
        //  Tirei a resposta por echo e coloquei por response... 
        $this->response($ret, REST_Controller::HTTP_OK);
		
		sort($skus_key);
		
        $quotes = Array();
		$quotes['marketplace'] = $row_ult['int_to'];
		$quotes['zip'] = $zip;
		$quotes['sku'] = json_encode($skus_key);
		$quotes['cost'] = $transp[0]['preco_frete'];
		$quotes['id'] = $dataF['token_oferta'];
		$quotes['oferta'] = $transp[0]['oferta']; 
		$quotes['validade'] = $transp[0]['validade'];
		$quotes['retorno'] = $retorno_fr; 
		$quotes['frete_taxa'] = $row_taxa['value']; 
		$this->db->replace('quotes_ship', $quotes);
    } 

     
	function get_web_page( $url,$post_data )
	{
	    $options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_HEADER         => false,    // don't return headers
	        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
	        CURLOPT_ENCODING       => '',       // handle all encodings
	        CURLOPT_USERAGENT      => 'conectala', // who am i
	        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	        CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
	        CURLOPT_TIMEOUT        => 120,      // timeout on response
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
			CURLOPT_POST		=> true,
			CURLOPT_POSTFIELDS	=> $post_data,
	        CURLOPT_SSL_VERIFYPEER => false     // Disabled SSL Cert checks
	    );
	    $ch      = curl_init( $url );
	    curl_setopt_array( $ch, $options );
	    $content = curl_exec( $ch );
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    $err     = curl_errno( $ch );
	    $errmsg  = curl_error( $ch );
	    $header  = curl_getinfo( $ch );
	    curl_close( $ch );
		$header['httpcode']   = $httpcode;
	    $header['errno']   = $err;
	    $header['errmsg']  = $errmsg;
	    $header['content'] = $content;
	    return $header;
	}   

}