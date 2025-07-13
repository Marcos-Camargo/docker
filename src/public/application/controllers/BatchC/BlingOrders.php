<?php
/*
SW Serviços de Informática 2019
 
Atualiza pedidos que chegaram no BLING

*/   
class BlingOrders extends BatchBackground_Controller {
		
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
		$this->load->model('model_orders');
		$this->load->model('model_products');
		$this->load->model('model_stores');
		$this->load->model('model_clients');
		$this->load->model('model_integrations');
		$this->load->model('model_blingultenvio');
		$this->load->model('model_promotions');
		$this->load->model('model_category');

    }

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
		$apikeys = $this->getBlingKeys();
		$feitos = array();
		foreach($apikeys as $mkt => $apikey) {
			echo 'Pegando ordens do marketplace '.$mkt."\n";
			if (!(in_array($apikey,$feitos))) {
				$feitos[] = $apikey;
				$this->getorders($apikey);
			}
		}

		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
	
	function getBlingKeys() {
		$apikeys = Array();
		$sql = "select * from stores_mkts_linked where id_integration = 13";
		$query = $this->db->query($sql);
		$setts = $query->result_array();
		foreach ($setts as $ind => $val) {
			$apikeys[$val['apelido']] = $val['apikey'];
		}
		return $apikeys;
	}

    function getorders($apikey)
    {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		/* Lista de estados de pedidos de Vendas do Bling 
		 curl -X GET "https://bling.com.br/Api/v2/situacao/vendas/json/"
     		-G
     		-d "apikey={apikey}"
                    "id": "6",  "nome": "Em aberto",
                    "id": "9",  "nome": "Atendido",
                    "id": "12", "nome": "Cancelado",
                    "id": "15", "nome": "Em andamento",
                    "id": "18", "nome": "Venda Agenciada",
                    "id": "21", "nome": "Em digitação",
                    "id": "24", "nome": "Verificado",
		*/

		// $apikey = "3ca13ce24e18072f094ea9528f917a37c1ccb94ef4f4bb24dbf7c28e01f41066b7ff3157";
		$this->load->library('calculoFrete');
		
		$page  = 1;
		while (true) {
		
			$outputType = "json";
			$filters = date("d/m/Y",time() - 60 * 60 * 24* 7)." TO ".date("d/m/Y") ;   //"filters=dataEmissao[12/12/2013 TO 05/02/2014]; idSituacao[6]"
			echo "FILTRO DE BUSCA DE PEDIDOS COM ID= 6:".$filters."\n";
			$url = 'https://bling.com.br/Api/v2/pedidos/page='.$page.'/' . $outputType;
			$page++;
			$retorno = $this->executeGetOrder($url, $apikey,$filters,'6');
			
			if (isset($retorno['retorno']['erros'][0]['erro']['cod'])) {
				if ($retorno['retorno']['erros'][0]['erro']['cod'] == 14) {
					echo 'Acabaram os pedidos'."\n";
					break;
				}
			}
			if (!isset($retorno['retorno']['pedidos'])) {
				var_dump($retorno);
				return json_encode($retorno);
			}
			$pedidos = $retorno['retorno']['pedidos'];
			echo "temos ".count($pedidos)."\n";
			$i=1; 
			foreach($pedidos as $pedido) {
				
				$pedido = $pedido['pedido'];
				echo " PEDIDO ".$pedido['numero']. " lido = ".$i." \n";
				$i++;
			//	$this->log_data('batch',$log_name,json_encode($pedido),"I");
				$id_mkt = $pedido['loja'];
				$mkt = $this->model_integrations->getMktbyStore($id_mkt);
				$mktname = $mkt['apelido'];	
				echo $mktname."\n"; 
				
				if ($mktname == 'B2W') {
					echo "Ignorando pedido da B2W\n";
					continue; 
				}
				if ($mktname == 'CAR') {
					echo "Ignorando pedido do CAR\n";
					continue; 
				}
				if ($mktname == 'VIA') {
					echo "Ignorando pedido do VIA\n";
					continue; 
				}
				if ($mktname == 'ML') {
					echo "Ignorando pedido do ML\n";
					continue; 
				}
				$insert = true;
	//			if($MOrder = $this->model_orders->getMasterOrder('BLING',$pedido['numero'])) {
	//				$insert = false;
	//				$order_id = $MOrder['order_id']; 
	//			} else {
	//				$insert = true;
	//			}
				//var_dump($pedido);
				
				// Verifico se todos os skus estão certos e são das mesmas empresas 
				$cpy ='';
				$erro = false;
				
				$cross_docking_default = 2;// tempo default de cross_docking  - depois colocar no prefixes
				$cross_docking = $cross_docking_default;
				$new_cross_docking = null;
				foreach($pedido['itens'] as $item) {
					$item = $item['item'];
					$sku_item = $item['codigo'];
					$sql = "SELECT * FROM bling_ult_envio WHERE skubling = ? AND int_to = ?";
					$query = $this->db->query($sql, array($sku_item,$mktname));
					$prf = $query->row_array();
					if (empty($prf)) {
						// pode ser produto com variação
						if (strrpos($sku_item, "-") !=0) {
							$sku_item = substr($item['codigo'], 0, strrpos($item['codigo'], "-"));
							$sql = "SELECT * FROM bling_ult_envio WHERE skubling = ? AND int_to = ?";
							$query = $this->db->query($sql, array($sku_item,$mktname));
							$prf = $query->row_array();
						}
						if (empty($prf))  {
					    //if (empty($prf) || (strrpos($sku_item, "-")==0) ) {
							echo 'O pedido '.$pedido['numero'].' possui produto '.$item['codigo'].' que não é do Marketplace '.$mktname."! Ordem não importada"."\n";
							$this->log_data('batch',$log_name,'O pedido '.$pedido['numero'].' possui produto '.$item['codigo'].' que não é do Marketplace '.$mktname."! Ordem não importada","E");
							$erro = true; 
							break;
						}
					}
					if($cpy == '') {
						$cpy = $prf['company_id']; 
						$store_id = $prf['store_id'];
						echo "Peguei Empresa:".$cpy." e loja:".$store_id."\n";
			    	} 
			    	else 
			    	{
						if ($cpy != $prf['company_id']) {
							echo 'O pedido '.$pedido['numero'].' possui produtos de mais de uma empresa ('.$cpy.' e '. $prf['company_id'].')! Ordem não importada'."\n";
							$this->log_data('batch',$log_name,'O pedido '.$pedido['numero'].' possui produtos de mais de uma empresa ('.$cpy.' e '. $prf['company_id'].')! Ordem não importada',"E");
							$erro = true; 
							break;
						}
					}

					$sku = $prf['sku'];
					$prd = $this->model_products->getProductBySku($sku,$cpy);
					// Pego a categoria para ver se existe exceção nesse item para adicionar cross docking
					$new_cross_docking = $this->getCrossDocking($prd['category_id'], $new_cross_docking);
					
					// Tempo de crossdocking 
					if (isset($prf['crossdocking'])) {  // pega o pior tempo de crossdocking dos produtos
						if (((int) $prf['crossdocking'] + $cross_docking_default) > $cross_docking) {
							$cross_docking = $cross_docking_default + (int) $prf['crossdocking']; 
						};
					}
	
				}
				if ($erro) {
					continue;
				}
				echo 'cross_docking='.$cross_docking."\n";
				
				// Agora só pego ordens novas "Em Andamento"
				if ($order_exist = $this->model_orders->getOrdersDatabyBill($mktname,$pedido['numero'])) {
					echo "Ordem Já existe :".$order_exist['id']."\n";
					
					continue; 
				} 
				
				// Leio a Loja para pegar o service_charge_value
				$store = $this->model_stores->getStoresData($store_id);
				
				// PRIMEIRO INSERE O CLIENTE
				$clients = array();
				$clients['customer_name'] = $pedido['cliente']['nome'];
				$clients['customer_address'] = $pedido['cliente']['endereco'];
				$clients['addr_num'] = $pedido['cliente']['numero'];
				$clients['addr_compl'] = $pedido['cliente']['complemento'];
				$clients['addr_neigh'] = $pedido['cliente']['bairro'];
				$clients['addr_city'] = $pedido['cliente']['cidade'];
				$clients['addr_uf'] = $pedido['cliente']['uf'];
				$clients['country'] = 'BR';
				if (is_null($pedido['cliente']['fone'])) {
					$pedido['cliente']['fone']= '';
				}
				$clients['phone_1'] = $pedido['cliente']['fone'];
				if (is_null($pedido['cliente']['celular'])) {
					$pedido['cliente']['celular']= '';
				}
				$clients['phone_2'] = $pedido['cliente']['celular'];
				$clients['zipcode'] = $pedido['cliente']['cep'];
				if (is_null($pedido['cliente']['email'])) {
					$pedido['cliente']['email'] = '';
				}
				$clients['email'] = $pedido['cliente']['email'];
				$clients['cpf_cnpj'] = $pedido['cliente']['cnpj'];
				if (is_null($pedido['cliente']['ie'])) {
					$pedido['cliente']['ie']= '';
				}
				$clients['ie'] = $pedido['cliente']['ie'];
				$clients['rg'] = $pedido['cliente']['rg'];
				$clients['origin'] = $mktname;
				$clients['origin_id'] = $pedido['cliente']['id'];
				// var_dump($clients);
				/*
				if ($client_id = $this->model_clients->getByOrigin('BLING',$pedido['cliente']['id'])) {
					$clients['id'] = $client_id['id'];
					$client_id = $this->model_clients->replace($clients);
					echo "Cliente Atualizado:".$client_id."\n";
				} else {
					$client_id = $this->model_clients->insert($clients);
					echo "Cliente Inserido:".$client_id."\n";
				}
				 * */
				$client_id = $this->model_clients->insert($clients);
				if ($client_id==false) {
					echo 'Não consegui incluir o cliente'."\n";
					$this->log_data('batch',$log_name,'Erro ao incluir cliente',"E");
					return;
				}
				$zip = $pedido['cliente']['cep'];
				
				$order = Array();
				$orders['bill_no'] = $pedido['numero'];
				$bill_no = $pedido['numero'];
				$orders['numero_marketplace'] = $pedido['numeroPedidoLoja']; // numero do pedido no marketplace 
				$orders['date_time'] = $pedido['data'];
				$orders['customer_id'] = $client_id;
				$orders['total_order'] = $pedido['totalprodutos'];
				$orders['service_charge_rate'] = $store['service_charge_value'];  
				$orders['service_charge'] = $pedido['totalvenda'] * $store['service_charge_value'] / 100;  
				$orders['vat_charge_rate'] = 0; //pegar na tabela de empresa - Não está sendo usado.....
				$orders['vat_charge'] = $pedido['totalvenda'] * $orders['vat_charge_rate'] / 100; //pegar na tabela de empresa - Não está sendo usado.....
				$orders['gross_amount'] = $pedido['totalvenda'];
				$orders['total_ship'] = $pedido['valorfrete'];
				$frete = $pedido['valorfrete'];
				$orders['discount'] = $this->fmtNum($pedido['desconto']);
				$orders['net_amount'] = $orders['gross_amount'] - $orders['discount'] - $orders['service_charge'] - $orders['vat_charge'] - $pedido['valorfrete'];
				$bst = $pedido['situacao'];
				switch ($bst) {
				    case "Em aberto":
				        $bst = 3;
						$orders['data_pago'] = date("Y-m-d H:i:s");
				        break;
				    case "Atendido":
				        $bst = 4;
				        break;
				    case "Cancelado":
				        $bst = 99;
				        break;
				    case "Em andamento":
				        $bst = 3;
				        break;
				    default:
				        $bst = 1;
				        break;
				}
				
				$orders['paid_status'] = $bst; // $pedido['status'];  CONVERTIDO
				$orders['company_id'] = $cpy;   // BATER PELO SKU
				$orders['store_id'] = $store_id;
				$orders['origin'] = $mktname;
				$orders['customer_phone'] = $pedido['cliente']['fone'] ; 
				$orders['user_id'] = 1;   // ID DO SYSTEM USER

				// Altero o cross docking, caso exista exceção em alguma categoria dos itens
				if ($new_cross_docking && $mktname == "ML") $cross_docking = $new_cross_docking;

				$orders['data_limite_cross_docking'] = $bst == 3 || $bst == 4 ? $this->somar_dias_uteis(date("Y-m-d"),$cross_docking,'') : null;
				echo 'Data limite calculada ='.$orders['data_limite_cross_docking']."\n";
				
				if (isset($pedido['transporte']['enderecoEntrega'])) {
					$orders['customer_address'] = $pedido['transporte']['enderecoEntrega']['endereco'];
					$orders['customer_name'] = $pedido['transporte']['enderecoEntrega']['nome'];
					$orders['customer_address_num '] = $pedido['transporte']['enderecoEntrega']['numero'];
					if (is_null($pedido['transporte']['enderecoEntrega']['complemento']) || ($pedido['transporte']['enderecoEntrega']['complemento']=='null')) {
						$pedido['transporte']['enderecoEntrega']['complemento'] = '';
					}
					$orders['customer_address_compl'] = $pedido['transporte']['enderecoEntrega']['complemento'];
					$orders['customer_address_neigh'] = $pedido['transporte']['enderecoEntrega']['bairro'];
					$orders['customer_address_city'] = $pedido['transporte']['enderecoEntrega']['cidade'];
					$orders['customer_address_uf'] = $pedido['transporte']['enderecoEntrega']['uf'];
					$orders['customer_address_zip'] = preg_replace("/[^0-9]/", "",$pedido['transporte']['enderecoEntrega']['cep']);
				} else {
					$orders['customer_address'] = $pedido['cliente']['endereco'];
					$orders['customer_name'] = $pedido['cliente']['nome'];
					$orders['customer_address_num'] = $pedido['cliente']['numero'];
					if (is_null($pedido['cliente']['complemento']) || ($pedido['cliente']['complemento'] == "null")) {
						$pedido['cliente']['complemento'] = '';
					}
					$orders['customer_address_compl'] = $pedido['cliente']['complemento'];
					$orders['customer_address_neigh'] = $pedido['cliente']['bairro'];
					$orders['customer_address_city'] = $pedido['cliente']['cidade'];
					$orders['customer_address_zip'] = preg_replace("/[^0-9]/", "",$pedido['cliente']['cep']);
					$orders['customer_address_uf'] = $pedido['cliente']['up'];
				}
	
				if ($order_id = $this->model_orders->getOrdersDatabyBill($mktname,$pedido['numero'])) {

					if($order_id['data_limite_cross_docking']) unset($orders['data_limite_cross_docking']);

					$order_id = $this->model_orders->updateByOrigin($order_id['id'],$orders);
					echo "Atualizado:".$order_id."\n";
					
				} else {
					$order_id = $this->model_orders->insertOrder($orders);
					echo "Inserido:".$order_id."\n";
				}	
				if (!$order_id) {
					$this->log_data('batch',$log_name,'Erro ao incluir pedido',"E");
					return ;
				}
			
				// ITEMS
	
				// Preciso pegar o CPY no SKU dos produtos 
				// Fazer tratamento dos SKUs de empresas Diferentes. // nao deveria acontecer pois recusamos na consulta de frete.
				
				// Delete old items
				$quoteid = "";
				$this->model_orders->deleteItem($order_id);  // Nao deve deletar nada pois só pego ordem nova
				$itensIds = array();
				
				// para o verificação do frete
				$todos_correios = true; 
				$todos_tipo_volume= true;
				$todos_por_peso = true;
				$fr = array();
				$fr['destinatario']['endereco']['cep'] = $orders['customer_address_zip'];
		        $fr['expedidor']['endereco']['cep'] = $store['zipcode'];
				$origem=$this->calculofrete->lerCep($store['zipcode']);
				$destino=$this->calculofrete->lerCep($orders['customer_address_zip']);
				
				foreach($pedido['itens'] as $item) {
					$item = $item['item'];
					$items = array();
					
					//list ($cpy,$sku) = explode("|",$item['codigo']);
					
					$skubling = $item['codigo'];
					$variant = '';
					$sql = "SELECT * FROM bling_ult_envio WHERE skubling = ? AND int_to = ?";
					$query = $this->db->query($sql, array($skubling, $mktname));
					$prf = $query->row_array();
          			if (!$prf) {
						$skubling = substr($item['codigo'], 0, strrpos( $item['codigo'], "-"));
						$variant = substr($item['codigo'],strrpos($item['codigo'], "-")+1);
						$sql = "SELECT * FROM bling_ult_envio WHERE skubling = ? AND int_to = ?";
						$query = $this->db->query($sql, array($skubling, $mktname));
						$prf = $query->row_array();
          			}
          
				  	$cpy = $prf['company_id'];
					$sku = 	$prf['sku'];			
					echo  $item['codigo']."=".$cpy."=".$sku." variant=".$variant."\n";
					$prd = $this->model_products->getProductBySku($sku,$cpy);
					if ($prd['is_kit'] ==0) {	
						$items['order_id'] = $order_id; // ID da order incluida
						$items['product_id'] = $prd['id'];
						$items['sku'] = $sku;
						if ($variant != '') {
							$items['sku'] = $sku.'-'.$variant;
						}
						$items['variant'] = $variant;
						$items['name'] = $item['descricao'];
						$items['qty'] = $item['quantidade'];
						$items['rate'] = $item['valorunidade'];
						$items['amount'] = $item['valorunidade'] * $item['quantidade'];
						$items['discount'] = $this->fmtNum($item['descontoItem']);
						$items['company_id'] = $prd['company_id']; // PEGAR DO SKU
						$items['store_id'] = $prd['store_id']; // PEGAR DO SKU
						$items['un'] = $item['un'];
						$items['pesobruto'] = $item['pesoBruto'];
						$items['largura'] = $item['largura'];
						$items['altura'] = $item['altura'];
						$items['profundidade'] = $item['profundidade'];
						$items['unmedida'] = $item['unidadeMedida'];
						//var_dump($items);
						$item_id = $this->model_orders->insertItem($items);
						if (!$item_id) {
							$this->log_data('batch',$log_name,'Erro ao incluir item',"E");
							return; 
						}
						$itensIds[]= $item_id; 
						// Acerto o estoque do produto 
						$this->model_products->reduzEstoque($prd['id'],$item['quantidade'],$variant);
						$this->model_blingultenvio->reduzEstoque($mktname,$prd['id'],$item['quantidade']);
						
						// vejo se o produto estava com promoção de estoque e vejo se devo terminar 
						$this->model_promotions->updatePromotionByStock($prd['id'],$item['quantidade'],$item['valorunidade']); 
					}
					else { // é um kit,  
						echo "O item é um KIT id=". $prd['id']."\n";
						$productsKit = $this->model_products->getProductsKit($prd['id']);
						foreach ($productsKit as $productKit){
							$prd = $this->model_products->getProductData(0,$productKit['product_id_item']);
							echo "Produto item =".$prd['id']."\n";
							$items = array();
							$items['order_id'] = $order_id; // ID da order incluida
							$items['kit_id'] = $productKit['product_id'];
							$items['product_id'] = $prd['id'];
							$items['sku'] = $prd['sku'];
							$variant = '';
							$items['variant'] = $variant;  // Kit não pega produtos com variantes
							$items['name'] = $prd['name'];
							$items['qty'] = $item['quantidade'] * $productKit['qty'];
							$items['rate'] = $productKit['price'] ;  // pego o preço do KIT em vez do item
							$items['amount'] = (float)$items['rate'] * (float)$items['qty'];
							$items['discount'] = 0;  
							$items['company_id'] = $prd['company_id']; // PEGAR DO SKU
							$items['store_id'] = $prd['store_id']; // PEGAR DO SKU
							$items['un'] = $item['un'];
							$items['pesobruto'] = $prd['peso_bruto'];
							$items['largura'] = $prd['largura'];
							$items['altura'] = $prd['altura'];
							$items['profundidade'] = $prd['profundidade'];
							$items['unmedida'] = $item['unidadeMedida'];
							//var_dump($items);
							$item_id = $this->model_orders->insertItem($items);
							if (!$item_id) {
								$this->log_data('batch',$log_name,'Erro ao incluir item',"E");
								return; 
							}
							$itensIds[]= $item_id; 
							// Acerto o estoque do produto filho
							$this->model_products->reduzEstoque($prd['id'],$items['qty'],$variant);
						}
						$this->model_blingultenvio->reduzEstoque($mktname,$prd['id'],$item['quantidade']);  // reduzo o estoque do produto KIT no Bling_utl_envio
					}
					//verificacao do frete 
					$todos_correios = $todos_correios && $this->calculofrete->verificaCorreios($prf);
					$todos_tipo_volume = $todos_tipo_volume && $this->calculofrete->verificaTipoVolume($prf,$origem['state'],$destino['state']); 
					$todos_por_peso = $todos_por_peso && $this->calculofrete->verificaPorPeso($prf,$destino['state']);
					$vl = Array ( 
						'tipo' => $prf['tipo_volume_codigo'],     
			            'sku' => $skubling,
			            'quantidade' => $item['quantidade'],	           
			            'altura' => (float) $prf['altura'] / 100,
					    'largura' => (float) $prf['largura'] /100,
					    'comprimento' => (float) $prf['profundidade'] /100,
					    'peso' => (float) $prf['peso_bruto'],  
			            'valor' => (float) $item['valorunidade'],
			            'volumes_produto' => 1,
			            'consolidar' => false,
			            'sobreposto' => false,
			            'tombar' => false);
		            $fr['volumes'][] = $vl;
				}
        
        // verificação do frete 
				if ($todos_correios) {
					$resposta = $this->calculofrete->calculaCorreiosNovo($fr,$origem,$destino);
				}elseif ($todos_tipo_volume) {
					$resposta = $this->calculofrete->calculaTipoVolume($fr,$origem,$destino);
				}elseif ($todos_por_peso) {
					$resposta = $this->calculofrete->calculaPorPeso($fr,$origem,$destino);
				}	
				else {
					$resposta = array(
						'servicos' => array(
							'FR' => array ('empresa'=>'FreteRápido','servico'=>'A contratar', 'preco'=>0,'prazo'=>0,),
						),
					);
				}
				if (array_key_exists('erro',$resposta )) {
					echo $resposta['erro']."\n"; 
					$this->log_data('batch',$log_name, $resposta['erro'],"W");
					continue;	
				}
				if (!array_key_exists('servicos',$resposta )) {
					$erro = $reposta['calculo'].': Nenhum serviço de transporte para estes ceps '.json_encode($fr);
					echo $resposta['erro']."\n"; 
					$this->log_data('batch',$log_name, $resposta['erro'],"W");
					continue;	
				}
				if (empty($resposta['servicos'] )) {
					$erro = $reposta['calculo'].': Nenhum serviço de transporte para estes ceps '.json_encode($fr);
					echo $resposta['erro']."\n"; 
					$this->log_data('batch',$log_name, $resposta['erro'],"W");
					continue;	
				}	
				$key = key($resposta['servicos']); 
				$transportadora = $resposta['servicos'][$key]['empresa']; 
				$servico =  $resposta['servicos'][$key]['servico'];
				$prazo = $resposta['servicos'][$key]['prazo']; 
				$this->model_orders->setShipCompanyPreview($order_id,$transportadora,$servico,$prazo);
				
					/*
		TABELA TIPO DE FRETE
	Valor Descrição
	R	0 - Contratação do Frete por conta do Remetente (CIF)
	D	1 - Contratação do Frete por conta do Destinatário (FOB)
	T	2 - Contratação do Frete por conta de Terceiros
	3	3 - Transporte Próprio por conta do Remetente
	4	4 - Transporte Próprio por conta do Destinatário
	S	9 - Sem Ocorrência de Transporte	
	*/	
				// TRANSPORTE
				$i = 0;
				if (isset($pedido['transporte'])) {  // Nao deveria acontecer pois só pego novos pedidos e o frete eu que contrato.
					$transportes = $pedido['transporte'];
					if (isset($transportes['volumes'])) {
						foreach($transportes['volumes'] as $vol) {
							$vol = $vol['volume'];
							$freight['order_id'] = $order_id;
							$freight['item_id'] = $itensIds[$i++];
							$freight['company_id'] = $cpy;  // Pego pelo SKU
							$freight['ship_company'] = $vol['servico'];
							$freight['status_ship'] = 1; // Descobrir quais são
							/*
							 * Código Descrição
									0 Postado
									1 Em andamento
									2 Não entregue
									3 Entregue
							 */
							
							$freight['date_delivered'] = $vol['dataSaida'];
							$freight['ship_value'] = $vol['valorFretePrevisto'];
							$freight['prazoprevisto'] = $vol['prazoEntregaPrevisto'];
							$freight['idservico'] = $vol['idServico'];
							$freight['codigo_rastreio'] = $vol['codigoRastreamento'];
							//var_dump($freight);
							$freight_id = $this->model_orders->insertFreight($freight);
							if (!$freight_id) {				
								$this->log_data('batch',$log_name,'Erro ao incluir frete',"E");
								return ; 
							}
						}
					}
				}
				// PARCELAS
				$i = 0;
				if (isset($pedido['parcelas'])) { // Nao deveria acontecer pois só pego novos pedidos e o frete eu que contrato.
					$parcelas = $pedido['parcelas'];
					foreach($parcelas as $parc) {
						$parc = $parc['parcela'];
						$i++;
						$parcs['parcela'] = $i;
						$parcs['order_id'] = $order_id;
						$parcs['parcela'] = $i;
						$parcs['bill_no'] = $bill_no;
						$parcs['data_vencto'] = $parc['dataVencimento'];
						$parcs['valor'] = $parc['valor'];
						$parcs['forma_id'] = $parc['forma_pagamento']['id'];
						$parcs['forma_desc'] = $parc['forma_pagamento']['descricao'];
						$parcs['forma_cf'] = $parc['forma_pagamento']['codigoFiscal'];
						//var_dump($parcs);
						$parcs_id = $this->model_orders->insertParcels($parcs);
						if (!$parcs_id) {
							$this->log_data('batch',$log_name,'Erro ao incluir pagamento ',"E");
							return; 
						}
					}
				}
				// NFEs
				$i = 0;
				if (isset($pedido['nota'])) {
					$nota = $pedido['nota'];
					$nfe['sub_id'] = 1;
					$nfe['order_id'] = $order_id;
					$nfe['item_id'] = 0;
					$nfe['company_id'] = $cpy;
					$nfe['date_emission'] = $nota['dataEmissao'];
					$nfe['nfe_serie'] = $nota['seria'];
					$nfe['nfe_num'] = $nota['numero'];
					$nfe['nfe_value'] = $nota['valorNota'];
					$nfe['chave'] = $nota['chaveAcesso'];
					$nfe['info'] = "";
					$nfe_id = $this->model_hire_ship->insertNfes($nfe);
					if (!$nfe_id) {
						$this->log_data('batch',$log_name,'Erro ao incluir NFE ',"E");
						return; 
					}
				}
			}
		}
	}


	function executeGetOrder($url, $apikey,$filters,$idSituacao=null){
	    $curl_handle = curl_init();
		if (is_null($idSituacao)) {
			$params = "&filters=dataEmissao[".$filters."]";
		}
		else {
			$params = "&filters=dataEmissao[".$filters."]; idSituacao[".$idSituacao."]";
		}
	    echo "http = ".$url . '&apikey=' . $apikey.$params."\n";
	    curl_setopt($curl_handle, CURLOPT_URL, $url . '&apikey=' . $apikey.$params);
	    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
	    $response = curl_exec($curl_handle);
	    curl_close($curl_handle);
	    return json_decode($response,true);
	}

	function getCrossDocking($category_id, $new_cross_docking)
	{
		// Pego a categoria para ver se existe exceção nesse item para adicionar cross docking
		$category = filter_var($category_id, FILTER_SANITIZE_NUMBER_INT);
		$dataCategory = $this->model_category->getCategoryData($category);
		if ($dataCategory && $dataCategory['days_cross_docking']) {

			$limit_cross_docking_category = (int)$dataCategory['days_cross_docking'];

			if ($new_cross_docking && $limit_cross_docking_category < $new_cross_docking)
				$new_cross_docking = $limit_cross_docking_category;

			if (!$new_cross_docking)
				$new_cross_docking = $limit_cross_docking_category;
		}
		return $new_cross_docking;
	}

}

?>
