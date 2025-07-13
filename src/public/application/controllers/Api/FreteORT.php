<?php 

 require APPPATH . "controllers/Api/FreteConectala.php";


class FreteORT extends FreteConectala 
{   
	 
    var $start = 0;
    var $CNPJ_Conecta_La = '30120829000199'; // CNPJ fixo do ConectaLa
    public $int_to = 'ORT';


    public function __construct() 
    {
       parent::__construct();
    }
        
    
    public function index_post() 
    {
    	$this->start = microtime(true);
    	$this->load->library('calculoFrete');

        $cross_docking = $cross_docking_default = 0;    // tempo default de cross_docking  - depois colocar no prefixes
		
        $data = json_decode(file_get_contents('php://input'), true);

		if (is_null($data)) 
        {
			$this->returnError('Dados fora do formato json!', 'Dados com formato errado'.print_r(file_get_contents('php://input'), true), 'E');
			die;
		}
		
        $zip = $data['destinationZip'];
		
		$destino = $this->calculofrete->lerCep($zip);

		if (!$destino)
        {
			$this->returnError('CEP Destino '.$zip.' inexistente.', 'CEP Destino '.$zip.' inexistente','W');
			die;
		}		

		$fr = Array();
		$zip = substr('00000000'.strval($data['destinationZip']), -8); 
        $fr['destinatario'] = Array ( 
        	'tipo_pessoa' => 1,
        	'endereco' => Array ('cep' => $zip));
		$prim_stor = 0; 
		$total_price = 0;
		$todos_proprio = true; // Precode 
		$todos_correios = true; 
		$todos_tipo_volume = true;
		$todos_por_peso = true;
		
		foreach ($data['volumes'] as $vol)
        {
			$skulocal = $vol['sku'];
			$sql = "SELECT * FROM integration_last_post WHERE int_to = '".$this->int_to."' and skulocal = ?";
			$query = $this->db->query($sql, array($skulocal));
			$row_ult = $query->row_array();
			$variant = '';

			if (is_null($row_ult))
            {
				//pode ser produto com variação
				if (strrpos($skulocal, '-') != 0)
                {
					$sku = substr($vol['sku'], 0, strrpos($vol['sku'], '-'));
					$variant = substr($vol['sku'], strrpos($vol['sku'], '-')+1);
					$sql = 'SELECT * FROM (integration_last_post use index (int_skulocal)) WHERE int_to = "'.$this->int_to.'" and skulocal = ?';
					$query = $this->db->query($sql, array($sku));
					$row_ult = $query->row_array();
				}

				if (is_null($row_ult))
                {
					//produto não existe
					$this->returnError('Produto inexistente', 'Produto inexistente para o Seller Center: '.print_r($data,true),'E');
					die; 
				}

				$sql = 'SELECT * FROM prd_variants WHERE prd_id = ? and variant = ?';
				$query = $this->db->query($sql, array($row_ult['prd_id'],$variant));
				$prd_variants = $query->row_array();

				if (is_null($prd_variants)) 
                {
					$this->returnError('Variação de Produto inexistente', 'Variação de Produto inexistente para o Seller Center: '.print_r($data, true), 'E');				
					die; 
				}

				$row_ult['qty_atual'] =$prd_variants['qty'];  // vale o estoque do produto variante
			} 
			
			// Precode 
			if ($variant == '')
				$skuseller = $row_ult['sku']; 
			else
				$skuseller = $prd_variants['sku'];
 
			$store_id = $row_ult['store_id'];

			if ($prim_stor == 0) 
				$prim_stor = $store_id; // leio a primeira compania do pacote 

			if ($prim_stor != $store_id)
            {
				$this->returnError('No momento não é possível atender com estes itens', 'Pedido com mais de uma empresa: '.print_r($data, true), 'W');
				die; 
			}

			if ($vol['quantity'] > $row_ult['qty_atual']) 
            {
				// sem estoque 
				$this->returnError('Estoque Insuficiente', 'Estoque Insuficiente: '.print_r($data, true), 'W');
				die;
			}
			
			$tipo_volume_codigo = intval($row_ult['tipo_volume_codigo']);

			if (is_null($row_ult['tipo_volume_codigo'])) 
				$tipo_volume_codigo = 999; 

			if ($row_ult['zipcode']) 
            {
                // se já cadastrou no integration_last_post, não preciso ler o prefix
				$CNPJ_seller = $row_ult['CNPJ'];
				$cep_seller = $row_ult['zipcode'];
			}
			else 
            {
				$sql = 'SELECT * FROM prefixes WHERE id = ?';
				$query = $this->db->query($sql, array($store_id));
				$row_pr = $query->row_array();

				if (empty($row_pr)) 
                {
					$this->returnError('No momento não é possível atender com estes itens', 'Tabela prefixes não existe ou a loja '.$store_id.' não foi cadastrada direito', 'E');
					die; 
				}

				$CNPJ_seller = $row_pr['CNPJ'];
				$cep_seller = $row_pr['cep'];
			}

			$origem = $this->calculofrete->lerCep($cep_seller);

			if (isset($row_ult['crossdocking']))
            {  
                // pega o pior tempo de crossdocking dos produtos
				if (((int)$row_ult['crossdocking'] + $cross_docking_default) > $cross_docking)
					$cross_docking = $cross_docking_default + (int)$row_ult['crossdocking']; 
			}

            if($cross_docking < 1)
                $cross_docking = 1;

			$total_price += $row_ult['price'] * $vol['quantity'];

            $vl = Array ( 
                'tipo' => $tipo_volume_codigo,     
                'skulocal' => $vol['sku'],
                'quantidade' => (int) $vol['quantity'],	           
                'altura' => (float) $row_ult['altura'] / 100,
                'largura' => (float) $row_ult['largura'] /100,
                'comprimento' => (float) $row_ult['profundidade'] /100,
                'peso' => (float) $row_ult['peso_bruto'],  
                'valor' => (float) $row_ult['price'] * $vol['quantity'],
                'volumes_produto' => 1,
                'consolidar' => false,
                'sobreposto' => false,
                'tombar' => false, 
                'skuseller' => $skuseller);

            $fr['volumes'][] = $vl;

            $skus_key[] = $skulocal; 

            $todos_proprio = $todos_proprio && ($row_ult['freight_seller'] == 1 && $row_ult['freight_seller_type'] == 1); // Precode

                if ($todos_proprio) 
                {
                    $todos_tipo_volume = false; 
                    $todos_correios = false;
                    $todos_por_peso = false;
                    $endpoint = $row_ult['freight_seller_end_point'];
                    $endpointtype=  $row_ult['freight_seller_type'];
			}
			else 
            {
				$todos_tipo_volume = $todos_tipo_volume && $this->calculofrete->verificaTipoVolume($row_ult,$origem['state'], $destino['state']); 

				if ($todos_tipo_volume) 
                { 
                    // se é tipo_volume não pode ser correios e não procisa consultar os correios
					$todos_correios = false; 
				}
				else 
                { 
                    // se não é tipo volumes, não precisa consultar o tipo_volumes pois já não achou antes 
					$todos_correios = $todos_correios && $this->calculofrete->verificaCorreios($row_ult); 
				}

				$todos_por_peso = $todos_por_peso && $this->calculofrete->verificaPorPeso($row_ult, $destino['state']); 
			}
		}
		
        $fr['remetente'] = Array (
        	'cnpj' => $this->CNPJ_Conecta_La
			);

		$fr['expedidor'] = Array (
        	'cnpj' => $CNPJ_seller,
			'endereco' => Array('cep' => $cep_seller)
			);

		if ($todos_proprio) 
        { 
            // Precode 
			if ($endpointtype == 1) 
            {
				if (!is_null($endpoint)) 
                {
					$this->respPreCode($endpoint, $fr, $cross_docking, $total_price, $skus_key, $row_ult['int_to'], $zip);
				}
				else 
                {
					$this->returnError('No momento não é possível atender com estes itens', 'freight_seller_end_point '.$endpoint.' inválido para os skus '.json_encode($skus_key),'E');
					die; 
				}
			}
			else
            {  // endpointtype errado 
				$this->returnError('No momento não é possível atender com estes itens', 'freight_seller_type '.$endpointtype.' inválido para os skus '.json_encode($skus_key),'E');
				die; 
			}
		}
		elseif ($todos_correios)
        {
			$resposta = $this->calculofrete->calculaCorreiosNovo($fr, $origem, $destino);
			$this->respTransportadora($resposta, $fr, $cross_docking, $total_price, $skus_key, $row_ult['int_to'], $zip);
		}
        elseif ($todos_tipo_volume) 
        {
			$resposta = $this->calculofrete->calculaTipoVolume($fr, $origem, $destino);
			$this->respTransportadora($resposta, $fr, $cross_docking, $total_price, $skus_key, $row_ult['int_to'], $zip);
		}
        elseif ($todos_por_peso) 
        {
			$resposta = $this->calculofrete->calculaPorPeso($fr, $origem,$destino);
			$this->respTransportadora($resposta, $fr, $cross_docking, $total_price, $skus_key, $row_ult['int_to'], $zip);
		}
        else
        {
			$this->returnError('Região de entrega não atendida ou CEP inválido', 'SEM TRANSPORTADORA. RECEBIDO:'.print_r($data, true), 'W');
			die; 
		}
    } 


	function respTransportadora($resposta, $fr, $cross_docking, $total_price, $skus_key, $int_to, $zip)
	{
		if (array_key_exists('erro', $resposta ))
        {
			$this->returnError($resposta['erro'], $resposta['calculo'].' '.$resposta['erro'], 'E');
			die;
		}

		if (!array_key_exists('servicos', $resposta )) 
        {
			$this->returnError('No momento não é possível atender com estes itens', $resposta['calculo'].': Nenhum serviço de transporte para estes ceps '.json_encode($fr), 'W');
			die; 
		}

		if (empty($resposta['servicos'] ))
         {
			$this->returnError('No momento não é possível atender com estes itens', $resposta['calculo'].': Nenhum serviço de transporte para estes ceps '.json_encode($fr), 'W');
			die; 
		}	

		$key = key($resposta['servicos']); 
		$preco = $resposta['servicos'][$key]['preco']; 
		$prazo = $resposta['servicos'][$key]['prazo']; 
		$transportadora = $resposta['servicos'][$key]['empresa']; 
		$servico =  $resposta['servicos'][$key]['servico'];
		
		// $taxa = $this->calculofrete->calculaTaxa($total_price);
		$taxa = 0;
        $preco += $taxa;
		
		$ret = Array();        
        $ret["shippingCost"] = (float)$preco;
        $ret["deliveryTime"] = 9;
        $ret["shippingEstimateId"] = $servico;
        $ret["shippingMethodId"] = "";
        $ret["shippingMethodName"] = $transportadora;
        $ret["shippingMethodDisplayName"] = "";

		$retorno = Array();
		$retorno['shippingQuotes'] = [$ret];
		$json_data = json_encode($retorno, JSON_UNESCAPED_UNICODE);
		$json_data = stripslashes($json_data);

		ob_clean();
		header('Content-type: application/json');
		echo stripslashes($json_data);
		$this->response(REST_Controller::HTTP_OK);	
		
		sort($skus_key);
        $quotes = Array();
		$quotes['marketplace'] = $int_to;
		$quotes['zip'] = $zip;
		$quotes['sku'] = json_encode($skus_key);
		$quotes['price'] = $preco;
		$quotes['time'] = $prazo + $cross_docking;
		$quotes['service'] = $servico;
		$quotes['frete_taxa'] = $taxa; 
		$quotes['response'] = json_encode($resposta,true); 
		$fim= microtime(true);
		$quotes['response_time'] = ($fim-$this->start) * 1000; 
		$this->db->insert('quotes_correios', $quotes);
		
		die;
	}


	function respPreCode($url, $fr, $cross_docking, $total_price, $skus_key, $int_to, $zip)
	{ 
        // Precode 		
		$vol = array(); 

		foreach ($fr['volumes'] as $item) 
        {
			$vol[] = array(
				'sku' => $item['skulocal'],
				'quantity' => $item['quantidade'],
			); 
		}
		
		$consulta = array(
    		'destinationZip' => $zip,
   			'volumes' => $vol
  		);
		
		$json_data = json_encode($consulta, JSON_UNESCAPED_UNICODE);
		$json_data = stripslashes($json_data);		
		
		$data = $this->get_web_page($url, $json_data);
		
		if ($data['httpcode'] == '404') 
        {
			$this->returnError('Região de entrega não atendida !', 'PRECODE NÂO ATENDE RESPOSTA: '.$data['content'].' DADOS ENVIADOS:'.$json_data,'W');
			die; 
		} 

		if (!($data['httpcode'] == '200'))  
        {
			$this->returnError('Região de entrega não atendida !', 'ERRO PRECODE - url:'.$url.' httpcode: '.$data['httpcode'].' 
                                RESPOSTA: '.$data['content'].' DADOS ENVIADOS:'.$json_data, 'E');
			die; 
		} 

		$retorno_fr = $data['content'];
		$data = json_decode($data['content'], true);
		$precode = $data['shippingQuotes'];

		if (count($precode) == 0) 
        {
			$this->returnError('Região de entrega não atendida !',  'SEM FRETE DO SELLER: URL:'.$url.' RECEBIDOS '.print_r($retorno_fr, true), 'W');
			die;
		}

		if ($precode[0]['shippingMethodName'] == "sku nao encontrado") 
        {
			$this->returnError('Produto inválido', 'PRECODE Produto Inválido FR='.print_r($fr, true).' Enviado='.$json_data.' Recebido='.print_r($retorno_fr, true), 'W');
		}

		if ($precode[0]['shippingMethodName'] == "Não TEM")
        {
			$this->returnError('Sem estoque', 'PRECODE Sem estoque FR='.print_r($fr, true).' Enviado='.$json_data.' Recebido='.print_r($retorno_fr, true), 'W');
		}

		$taxa = 0; // Ainda não cobramos taxa para Logistica própria do seller 
		
		$preco = $precode[0]['shippingCost'] + $taxa;
		$prazo = $precode[0]['deliveryTime'] + $cross_docking;

		$ret = Array();
		$ret['shippingCost'] = (float)$preco;

		$ret['deliveryTime'] = array(
		 	'total' => $prazo,
		 	'transit' => (int)$precode[0]['deliveryTime'],
		 	'expedition' => $cross_docking, 
		   ) ;

		$ret['shippingMethodId'] = $precode[0]['shippingMethodId'];
		$ret['shippingMethodName'] = $precode[0]['shippingMethodName'];
		$ret['shippingMethodDisplayName'] = $precode[0]['shippingMethodDisplayName'];

		$retorno = Array();
		$retorno['shippingQuotes'] = [$ret];
		$json_data = json_encode($retorno, JSON_UNESCAPED_UNICODE);
		$json_data = stripslashes($json_data);

		ob_clean();
		header('Content-type: application/json');
		echo stripslashes($json_data);
		$this->response(REST_Controller::HTTP_OK);	
		
		sort($skus_key);
        $quotes = Array();
		$quotes['marketplace'] = $int_to;
		$quotes['zip'] = $zip;
		$quotes['sku'] = json_encode($skus_key);
		$quotes['price'] = $preco;
		$quotes['time'] = $prazo;
		$quotes['service'] = 'PRECODE '.$precode[0]['shippingMethodName'];
		$quotes['frete_taxa'] = $taxa; 
		$quotes['response'] = json_encode($data,true); 
		$fim= microtime(true);
		$quotes['response_time'] = ($fim-$this->start) * 1000; 
		$this->db->insert('quotes_correios', $quotes);		
		die;
	}


	function respFreteRapido($fr, $cross_docking, $total_price, $skus_key, $int_to, $zip, $volumes)
	{
		$fr['codigo_plataforma'] = 'nyHUB56ml';
	    $fr['token'] = '5d1c7889ff8789959cb39eb151a3698e'; 
		$fr['retornar_consolidacao'] = true; 

		$json_data = json_encode($fr, JSON_UNESCAPED_UNICODE);
		$json_data = stripslashes($json_data);		

		$url = 'https://freterapido.com/api/external/embarcador/v1/quote-simulator';
		
		$data = $this->get_web_page($url, $json_data);
		
		if (!($data['httpcode'] == '200'))
          {
			// Consulta ao Frete Rápido não funcionou. 
			$this->returnError('', 'ERRO - httpcode: '.$data['httpcode'].' RESPOSTA FR: '.$data['content'].' DADOS ENVIADOS:'.$json_data, 'E');
			die; 
		}
			
		$retorno_fr = $data['content'];
		$data = json_decode($data['content'],true);
		$transp = $data['transportadoras'];

		if (count($transp) == 0)
        {
			$this->returnError('', 'SEM TRANSPORTADORA: DADOS ENVIADOS:'.print_r($json_data,true).' RECEBIDOS '.print_r($retorno_fr,true),'W');
			die; 
		}
		
		// $taxa = $this->calculofrete->calculaTaxa($total_price);		
		$taxa = 0;
		$transp[0]['preco_frete'] += $taxa;

		foreach ($volumes as $vol)
        {
			$ret[]=Array (
				'skuIdOrigin' => $vol['sku'],
				'quantity' => $vol['quantity'],
				'freightAmount' =>  $transp[0]['preco_frete'],
				'deliveryTime' => $transp[0]['prazo_entrega'] + $cross_docking, 
				'freightType' => 'NORMAL'
			);
		}
	
		$retorno = Array();
		$retorno['freights'] = $ret;
		$retorno['freightAdditionalInfo'] = $transp[0]['nome'];
		$retorno['sellerMpToken'] = '8hxJlXvVp3QO';
		
		$json_data = json_encode($retorno, JSON_UNESCAPED_UNICODE);
		$json_data = stripslashes($json_data);
		
		sort($skus_key);
        $quotes = Array();
		$quotes['marketplace'] = $int_to;
		$quotes['zip'] = $zip;
		$quotes['sku'] =  json_encode($skus_key);
		$quotes['cost'] = $transp[0]['preco_frete'];
		$quotes['id'] = $data['token_oferta'];
		$quotes['oferta'] = $transp[0]['oferta']; 
		$quotes['validade'] = $transp[0]['validade'];
		$quotes['retorno'] = $retorno_fr; 
		$quotes['frete_taxa'] = $taxa; 
		$this->db->replace('quotes_ship', $quotes);
		
		ob_clean();
		header('Content-type: application/json');
		echo $json_data;
        $this->response(REST_Controller::HTTP_OK);
	}

    
	function returnError($msg, $msg_log, $type = 'W')
    {
		$this->log_data('api', 'Frente SellerCenter Consulta Frete', $msg_log, $type);

		$json_msg = json_encode([
            'message' => $msg
            ], JSON_UNESCAPED_UNICODE);

		ob_clean();
		header('Content-type: application/json');
		echo stripslashes($json_msg);
		$this->response(REST_Controller::HTTP_NOT_FOUND);	
		die;
	}

}