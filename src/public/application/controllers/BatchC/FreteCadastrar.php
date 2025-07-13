<?php
/*
Verifica quais lojas precisam ser criadas no frete rapido como expedidor e as cria 
*/

/**
 * @property CI_Session $session
 * @property CI_Loader $load
 * @property CI_Router $router
 * 
 * @property Model_stores $model_stores
 * @property Model_integrations $model_integrations
 * @property Model_settings $model_settings
 * @property Model_products $model_products
 * @property Model_integration_logistic $model_integration_logistic
 *
 * @property CalculoFrete $calculofrete
 */

class FreteCadastrar extends BatchBackground_Controller {
		
	public function __construct()
	{
		parent::__construct();
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
	    $this->load->model('model_stores');
		$this->load->model('model_integrations');
		$this->load->model('model_settings');
		$this->load->model('model_products');
		$this->load->model('model_integration_logistic');
		$this->load->library('calculoFrete');
    }

	function run($id=null,$params=null)
	{
		/* inicia o job */
		$this->setIdJob($id);
		$log_name = $this->router->fetch_class().'/'.__FUNCTION__;
		if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__)) {
			$this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
			return ;
		}
		$this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");
		
		/* faz o que o job precisa fazer */
	    $this->setWarehouseLogistic($params);
		// $this->createIntegrations($params);
		
		/* removidos pois não usamos mais frete rápido
		$retorno = $this->verificaLojasComProdutosTransportadoras($params);
	 	$retorno = $this->cadastraLoja($params);
	 	$retorno = $this->alteraLoja($params);
	 	$retorno = $this->emailCategorias($params);
        $retorno = $this->testaCategorias($params,2); // testa os que já tem correio
	 	$retorno = $this->testaCategorias($params,3); // testa os que já mandou email
		*/
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}

	public function setWarehouseLogistic()
	{
		$integrationsSellerCenter = $this->model_integration_logistic->getAllIntegrationSellerCenter();
		foreach ($integrationsSellerCenter as $integrationSellerCenter) {
			$integrationsStore = $this->model_integration_logistic->getStoresIntegrationsSellerCenterByInt($integrationSellerCenter['id']);
			foreach ($integrationsStore as $integrationStore) {
				$store 		 = $integrationStore['store_id'];
				$integration = $integrationStore['integration'];

				try {
					$this->calculofrete->instanceLogistic($integration, $store, array(), false);
					$this->calculofrete->logistic->setWarehouse();
					$message = "Centro de distribuição criado/atualizado.[STORE=$store] [INTEGRATION=$integration]";
					echo $message . "\n";
					$this->log_data('batch',__CLASS__.'/'.__FUNCTION__, $message);
				} catch (InvalidArgumentException $exception) {
					$message =  "Não foi possível criar/atualizar o centro de distribuição.[STORE=$store] [INTEGRATION=$integration] {$exception->getMessage()}";
					echo $message . "\n";
					$this->log_data('batch',__CLASS__.'/'.__FUNCTION__, $message,"E");
				}
			}
		}
	}

	function createIntegrations($params = NULL)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;

		echo "Verificando integrações de lojas\n";
		$this->log_data('batch',$log_name,'start '.trim($params),"I");
		
		$int_type = 'BLING';
		$int_from = 'CONECTALA';
		$names = Array(
	//		['name ' => 'ConectaLá MercadoLivre Premium','int_to' => 'ML', 'auto_approve' => false],
			['name ' => 'ConectaLá B2W','int_to' => 'B2W', 'auto_approve' => true],
			//['name ' => 'ConectaLá ViaVarejo','int_to' => 'VIA', 'auto_approve' => true],
			['name ' => 'ConectaLá Carrefour','int_to' => 'CAR', 'auto_approve' => true],
	//		['name ' => 'ConectaLá MercadoLivre Clássico','int_to' => 'MLC', 'auto_approve' => false],
	//		['name ' => 'ConectaLá Novo Mundo','int_to' => 'NM', 'auto_approve' => true],
	//		['name ' => 'ConectaLá Ortobom','int_to' => 'ORT', 'auto_approve' => true],
	// 		['name ' => 'ConectaLá Madeira','int_to' => 'MAD', 'auto_approve' => true],
			['name ' => 'ConectaLá Mesbla','int_to' => 'MES', 'auto_approve' => true],
		);
		$lojas= $this->model_stores->getAllActiveStore(); 
		foreach ($lojas as $loja) {
			foreach ($names as $inte) { 
				$inte['int_type'] = $int_type;
				$inte['int_from'] = $int_from;
				$inte['store_id'] = $loja['id'];
				$inte['company_id'] = $loja['company_id'];
				$inte['active'] = '1'; 
				$id = $this->model_integrations->getIntegrationsbyCompIntType($inte['company_id'], $inte['int_to'],$inte['int_from'],$inte['int_type'],$inte['store_id']);
				if (!($id)) {
					echo " Cadastrando ".$loja['id']." - ".$loja['name']." para ".$inte['int_to']."\n";
					$this->model_integrations->create($inte);
				}
			}
		}
		$this->log_data('batch',$log_name,'finish',"I");
		 
	}

	function verificaLojasComProdutosTransportadoras($params = NULL)
	{
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$products = $this->model_products->getProductsNotCorreiosWithStoresNotAtFreteRapido();
		foreach($products as $product) {
			echo ' Marcando a loja '.$product['store_id'].' '.$product['loja'].' para cadastro no frete Rápido devido ao produto '.$product['id']."\n";
			$this->model_stores->setToSendToFreteRapido($product['store_id']);
		}
		
	}
  	
	function alteraLoja($params = NULL)
    {
    	$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		
		echo "Alterando lojas\n";
		$this->log_data('batch',$log_name,'start '.trim($params),"I");
		
		// fr_cadastro = null => empresa recem criou a loja e nao cadastrou os campos para o frete rápido
		// fr_cadastro = 1 => cadastrado mas sem confirmacao do cadastro de tabelas 
		// fr_cadastro = 2 => Aguardando cadastramento no frete rápido
		// fr_cadastro = 3 => Aguardando alteração no frete rápido
		// fr_cadastro = 4 => cadastrado 
		// fr_cadastro = 5 => Aguardando alteração no frete rápido mas já confirmou o cadastro de tabelas no frete rápido
		// fr_cadastro = 6 => loja que não tem produto de transportadora 
		
		//leio as lojas que tem fr_cadastro = 3 ou 5  
		$fr_cadastro = array(3,5);	
		$lojas  =$this->model_stores->getStoresByCadastro($fr_cadastro);
		if (count($lojas)==0) {
			$this->log_data('batch',$log_name,'Nenhuma loja sem Cadastro',"I");
			return ;
		}
		
		$setting = $this->model_settings->getSettingDatabyName('token_frete_rapido_master'); 
		if ($setting == false) {
			$this->log_data('batch',$log_name,'Falta o cadastro do parametro token_frete_rapido_master',"E");
			return ;
		}
		$token_fr = $setting['value'];
		
		foreach ($lojas as $loja) {

			if ((trim($loja['fr_email_contato']) == '') || 
				(trim($loja['fr_email_nfe']) == '')) {
				$this->log_data('batch',$log_name,'Loja '.$loja['name'].' não tem todos os dados para cadastramento no frete rápido',"W");
				continue;
			}

			echo "Alterando a loja ".$loja['id']." - ".$loja['name']."\n";
			$fr = Array(
					'cnpj' => preg_replace('/\D/', '', $loja['CNPJ']), 
					'razao_social' => $loja['raz_social'],
					'nome_fantasia' => $loja['name'],
					'inscricao_estadual' => preg_replace('/\D/', '', $loja['inscricao_estadual']),
					'email_contato' => $loja['fr_email_contato'] ,
					'email_nfe' => $loja['fr_email_nfe']
				);	
			
			//var_dump($fr);
			
			$json_data = json_encode($fr,JSON_UNESCAPED_UNICODE);
	    	$json_data = stripslashes($json_data);
			
			$json_data = json_encode($fr);
			
			$params = '?token='.$token_fr; 
			$url = 'https://freterapido.com/api/external/embarcador/v1/seller';
			//echo $url.$params."\n";
			//var_dump($json_data);
			$data = $this->executaAlteracaoExpedidor($url.$params,$json_data);
			$retorno_fr = $data['content'];
			//var_dump($retorno_fr);
			//if ((!($data['httpcode']=="200")) && (!($data['httpcode']=="204"))) {
			if (!($data['httpcode']=="200"))  {
				echo 'Erro na alteração do expedidor'."\n";
				echo " http: ".$url.$params. "\n";
				echo " httpcode : ".$data['httpcode']. "\n";
				echo " enviado: ".$json_data."\n";
				echo " recebido; ".print_r($data['content'],true);
				var_dump($retorno_fr);
				$this->log_data('batch',$log_name, 'ERRO - httpcode: '.$data['httpcode'].' RESPOSTA FR: '.$data['content'].' DADOS ENVIADOS:'.$json_data,"E");
				continue;
			} 
			if ($loja['fr_cadastro'] == 3) {
				$loja['fr_cadastro'] = 1;
			} else {
				$loja['fr_cadastro'] = 4;
			}	
			$this->log_data('batch',$log_name, 'Loja '.$loja['name'].' alterada no frete rápido',"I");
			$this->model_stores->update($loja, $loja['id']);
			
		} 
		$this->log_data('batch',$log_name,'finish',"I");
	}

	function populaPrefixes($params = NULL) {
		
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		$this->log_data('batch',$log_name,'start '.trim($params),"I");
		echo "Popula prefixes\n";
		$this->load->dbforge();
		
		// define table fields
		$fields = array(
		  'prefix' => array(
		    'type' => 'VARCHAR',
		    'constraint' => 20,
		    'null' => TRUE,
		  ),
		  'id' => array(
		    'type' => 'INT',
		    'constraint' => 11,
		    'unsigned' => TRUE
		  ),
		  'company_id' => array(
		    'type' => 'INT',
		    'constraint' => 11,
		    'unsigned' => TRUE,
		    'null' => TRUE,
		  ),
		  'cep' => array(
		    'type' => 'VARCHAR',
		    'constraint' => 20,
		    'null' => TRUE,
		  ),
		  'CNPJ' => array(
		    'type' => 'VARCHAR',
		    'constraint' => 25,
		    'null' => TRUE,
		  ),
		  'token_fr' => array(
		    'type' => 'VARCHAR',
		    'constraint' => 255,
		    'null' => TRUE,
		  )
		 );
		$attr = array('ENGINE' => 'MEMORY');
		$this->dbforge->add_field($fields);
		$this->dbforge->add_key('id', TRUE);
		
		// cria Prefixes se não existir 
		$this->dbforge->create_table('prefixes', TRUE, $attr);

		// popula com as lojas 
		$lojas  =$this->model_stores->getAllActiveStore(); 
		foreach ($lojas as $loja) {
			$pref = Array(
				'prefix' => $loja['prefix'],
				'id' => $loja['id'],
				'CNPJ' => preg_replace('/\D/', '', $loja['CNPJ']),
				'company_id' => $loja['company_id'],
				'cep' => preg_replace('/\D/', '', $loja['zipcode']),
				'token_fr' => $loja['token_fr']
			);
			$insert = $this->db->replace('prefixes', $pref);
		}
		$this->log_data('batch',$log_name,'finish',"I");
	}
  
    function cadastraLoja($params= null)
    {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Cadastrando lojas\n";
		$this->log_data('batch',$log_name,'start '.trim($params),"I");
		
		// fr_cadastro = null => empresa recem criou a loja e nao cadastrou os campos para o frete rápido
		// fr_cadastro = 1 => cadastrado mas sem confirmacao do cadastro de tabelas 
		// fr_cadastro = 2 => Aguardando cadastramento no frete rápido
		// fr_cadastro = 3 => Aguardando alteração no frete rápido
		// fr_cadastro = 4 => cadastrado 
		// fr_cadastro = 5 => Aguardando alteração no frete rápido mas já confirmou o cadastro de tabelas no frete rápido
		// fr_cadastro = 6 => loja que não tem produto de transportadora e não foi cadastrado no frete Rápido
		
		//leio as lojas que tem fr_cadastro = 2  
		$fr_cadastro = 2;	
		$lojas  =$this->model_stores->getStoresByCadastro($fr_cadastro);
		if (count($lojas)==0) {
			$this->log_data('batch',$log_name,'Nenhuma loja sem Cadastro',"I");
			return ;
		}
		
		$setting = $this->model_settings->getSettingDatabyName('token_frete_rapido_master'); 
		if ($setting == false) {
			$this->log_data('batch',$log_name,'Falta o cadastro do parametro token_frete_rapido_master',"E");
			return ;
		}
		$token_fr = $setting['value'];
		
		foreach ($lojas as $loja) {

			if ((trim($loja['fr_email_contato']) == '') || 
				(trim($loja['fr_email_nfe']) == '') || 
				(trim($loja['fr_email_login']) == '') || 
				(trim($loja['fr_senha']) == '')) {
				$this->log_data('batch',$log_name,'Loja '.$loja['name'].' não tem todos os dados para cadastramento no frete rápido',"W");
				continue;
			}

			$fr = Array(
					'cnpj' => preg_replace('/\D/', '', $loja['CNPJ']), 
					'razao_social' => $loja['raz_social'],
					'nome_fantasia' => $loja['name'],
					'inscricao_estadual' => preg_replace('/\D/', '', $loja['inscricao_estadual']),
					'email_contato' => $loja['fr_email_contato'] ,
					'email_nfe' => $loja['fr_email_nfe'],
					'email' => $loja['fr_email_login'],
					'senha' => $loja['fr_senha']
				);	
			
			// var_dump($fr);
			
			$json_data = json_encode($fr,JSON_UNESCAPED_UNICODE);
	    	$json_data = stripslashes($json_data);
			
			$json_data = json_encode($fr);
			
			$params = '?token='.$token_fr; 
			$url = 'https://freterapido.com/api/external/embarcador/v1/seller';
			
			$data = $this->executaCadastroExpedidor($url.$params,$json_data);
			$retorno_fr = $data['content'];
			//var_dump($retorno_fr);
			if ((!($data['httpcode']=="200")) && (!($data['httpcode']=="204"))) {
			// if (!($data['httpcode']=="200"))  {
				echo 'Erro no cadastro da loja '.$loja['id']."\n";
				echo " http: ".$url.$params. "\n";
				echo " httpcode : ".$data['httpcode']. "\n";
				echo " enviado: ".$json_data."\n";
				echo " recebido; ".print_r($data['content'],true);
				// var_dump($retorno_fr);
				$this->log_data('batch',$log_name, 'ERRO - httpcode: '.$data['httpcode'].' RESPOSTA FR: '.print_r($data['content'],true).' DADOS ENVIADOS:'.$json_data,"E");
				continue;
			} 
		
			$loja['fr_cadastro'] =1;
			$this->log_data('batch','FreteCadastrar/cadastraLoja', 'Loja '.$loja['name'].' cadastrada no frete rápido',"I");
			if ($this->model_stores->update($loja, $loja['id'])) {
				$this->log_data('batch',$log_name, 'ERRO ao fazer update da loja '.$loja['id'].'-'.$loja['name'],"E");
				return false;
			}
			
		} 
		$this->log_data('batch',$log_name,'finish',"I");
	}
	
	function emailCategorias($params= null)
    {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Procurando Novas Categorias\n";
		$this->log_data('batch',$log_name,'start '.trim($params),"I");
		
		// fr_cadastro = null => empresa recem criou a loja e nao cadastrou os campos para o frete rápido
		// fr_cadastro = 1 => cadastrado mas sem confirmacao do cadastro de tabelas 
		// fr_cadastro = 2 => Aguardando cadastramento no frete rápido
		// fr_cadastro = 3 => Aguardando alteração no frete rápido
		// fr_cadastro = 4 => cadastrado 
		// fr_cadastro = 5 => Aguardando alteração no frete rápido mas já confirmou o cadastro de tabelas no frete rápido
		// fr_cadastro = 6 => loja que não tem produto de transportadora e não foi cadastrado no frete Rápido
		
		//leio as lojas que tem fr_cadastro = 1 ou 4 , Lojas que já passaram pela ativação
		$lojas  =$this->model_stores->getAllActiveStoreFR();
		if (count($lojas)==0) {
			return ;
		}
		
		foreach ($lojas as $loja) {
			
			$tipos_volumes = $this->model_stores->getStoresTiposVolumesData($loja['id']);
			
			$existe = false;
			$categoria = "";
			$categorias_id= array();
			foreach($tipos_volumes as $tipo_volume) {
				$categoria .=$tipo_volume['tipo_volume']."<br>"; 
				if ($tipo_volume['status'] == 1) {
					$existe = true;
					$categorias_id[] = $tipo_volume['tipos_volumes_id'];
				}
			}
			if (!$existe) {
				continue; // se não existe status == 1 pego proxima loja
			}
			echo "loja ".$loja['id']. " precisa enviar email\n";

			$subject = 'ConectaLa: Cadastro de expedidor e categorias';
			$to[]='cs@freterapido.com';
			//$to[]='ricardoschaffer@conectala.com.br';
			$to[]='fernandoloureiro@conectala.com.br';
			
			$body = "
<body>
	<div>
		<p>Prezados da Frete Rápido, favor cadastrar o seguinte expedidor ou atualizar as suas categorias</p> 
		<table style='border: 1px solid black'> 
			<tr><th style='text-align: left'>CNPJ</th><td>".$loja['CNPJ']."</td></tr>
			<tr><th style='text-align: left'>Razão Social</th><td>".$loja['raz_social']."</td></tr>
			<tr><th style='text-align: left'>Nome Fantasia</th><td>".$loja['name']."</td></tr>
			<tr><th style='text-align: left'>Inscrição Estadual</th><td>".$loja['inscricao_estadual']."</td></tr>
			<tr><th style='text-align: left'>CEP</th><td>".$loja['zipcode']."</td></tr>
			<tr><th style='text-align: left'>Logradoro</th><td>".$loja['address']."</td></tr>
			<tr><th style='text-align: left'>Número</th><td>".$loja['addr_num']."</td></tr>
			<tr><th style='text-align: left'>Bairro</th><td>".$loja['addr_neigh']."</td></tr>
			<tr><th style='text-align: left'>Cidade</th><td>".$loja['addr_city']."</td></tr>
			<tr><th style='text-align: left'>Estado</th><td>".$loja['addr_uf']."</td></tr>
			<tr><th style='text-align: left'>Complemento</th><td>".$loja['addr_compl']."</td></tr>
			<tr><th style='text-align: left'>Categoria</th><td>".$categoria."</td></tr>
		</table>
		<p>Obrigado</p> 
		<p>Equipe Conectalá</p> 
	</div>
</body>
		";
		
			$resp = $this->sendEmailMarketing($to,$subject,$body);
			
			if ($resp['ok']) {
				echo "Email enviado \n";
				// mudar os status para 3; para começar a verificar se mudou lá no frete rápido. 
				foreach($categorias_id as $categoria_id) {
					$this->model_stores->updateStoresTiposVolumesStatus ($loja['id'], $categoria_id, '3');
				}
			}
			else {
				echo "erro no email \n";
				$this->log_data('batch',$log_name, 'ERRO ao enviar email '.$resp['msg'],"E");
			}
	 
			$this->log_data('batch',$log_name,'finish',"I");
		}
	}
	
	function testaCategorias($params= null,$ststatus = 3)
    {
		$log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		echo "Procurando Categorias para testar\n";
		$this->log_data('batch',$log_name,'start '.trim($params),"I");
			
		$lojas  =$this->model_stores->getStoresByNewCategories($ststatus);
		if (count($lojas)==0) {
			echo "Nenhuma categoria nova\n";
			$this->log_data('batch',$log_name,'Nenhuma categoria nova',"I");
			return ;
		}
		
		// Pego o Token pro frete Rápido 
		$sql = "SELECT * FROM settings WHERE name = ?";
		$query = $this->db->query($sql, array('token_frete_rapido_master'));
		$row = $query->row_array();
		if ($row) {
			$token_fr = $row['value'];
		} else {
			echo "Falta o cadastro do parametro token_frete_rapido_master\n";
			$this->log_data('batch',$log_name,'Falta o cadastro do parametro token_frete_rapido_master',"E");
			return ;
		}
		
		$CNPJ = '30120829000199'; // CNPJ fixo do ConectaLa
		foreach ($lojas as $loja) {
			echo "Verificando loja ".$loja['id']."-".$loja['name']." Tipo_volume= ".$loja['tipos_volumes_id']." Código FR= ".$loja['codigo']."\n";
			$fr = Array();
			$fr["remetente"] = Array (
	        	"cnpj" => $CNPJ
			);
			$fr["expedidor"] = Array (
	        	"cnpj" => preg_replace('/\D/', '', $loja['CNPJ']),
				"endereco" => 
					Array( 
						'cep' => strval($loja['zipcode'])
					)
				);
	    	$fr["destinatario"] = Array ( 
	    		"tipo_pessoa" => 1,
	        	"endereco" => Array (
	        		"cep" => strval($loja['zipcode'])
				)
			);
			$vl = Array ( 
	    		"tipo" => (int)$loja['codigo'],
	        	"sku" => 'SKUTESTE',
	            "quantidade" => 1,
	            "altura" => 0.5,
	            "largura" => 0.5,
	            "comprimento" => 0.5,
	            "peso" => 1 ,  
	            "valor" => 250.00,
	            "volumes_produto" => 1,
	            "consolidar" => false,
	            "sobreposto" => false,
	            "tombar" => false
			);
			$fr['volumes'][] = $vl;
			
			$fr["codigo_plataforma"] = "nyHUB56ml";
			$fr["token"] = $token_fr; 
			$fr["retornar_consolidacao"] = true; 
			$json_data = json_encode($fr,JSON_UNESCAPED_UNICODE);
			$json_data = stripslashes($json_data);
			//echo $json_data;
			//echo "\n";
			
			//rick - talvez mudar para o protocolo grpc 
			//https://github.com/freterapido/sdk-grpc
			//https://github.com/freterapido/sdk-grpc/blob/master/exemplos/php/index.php
			
			$url = "https://freterapido.com/api/external/embarcador/v1/quote-simulator";
			$data = $this->consultaFreteRapido( $url,$json_data);
			
			if (!($data['httpcode']=="200") )  { 
				if ($loja['fr_cadastro'] != 1) {
					echo " Erro ao testar categorias\n";
					echo " http: ".$url.$params. "\n";
					echo " httpcode : ".$data['httpcode']. "\n";
					echo " enviado: ".$json_data."\n";
					echo " recebido; ".print_r($data['content'],true);
					$this->log_data('batch',$log_name,'ERRO - httpcode: '.$data['httpcode'].' RESPOSTA FR: '.$data['content'].' DADOS ENVIADOS:'.$json_data,"E");
					//echo 'ERRO - httpcode: '.$data['httpcode'].' RESPOSTA FR: '.$data['content'].' DADOS ENVIADOS:'.$json_data."\n";
					continue ;	
				}
				else {// Se o cadastro não foi feito é normal dar erro
					echo 'Loja '.$loja['id'].'-'.$loja['name'].' ainda não cadastrada'."\n";
					continue;
				}

			}
				
			$retorno_fr = $data['content'];
			
			$data = json_decode($data['content'],true);
			$transp = $data['transportadoras'];
			if (count($transp) == 0) {
				// Não voltou transportadora. 
				//echo 'SEM TRANSPORTADORA: DADOS ENVIADOS:'.print_r($json_data,true).' RECEBIDOS '.print_r($retorno_fr,true);
				// var_dump($data);
				echo "Ainda sem Transportadora\n";
				echo "Enviamos ".print_r($json_data,true)."\n";
				echo "Recebemos ".print_r($retorno_fr,true)."\n"; 
				continue ;
			}
			echo "Achou transportadora:";
			$status=2; 
			foreach ($transp as $transportadora) {
				echo $transportadora['nome'].",";
				if ($transportadora['nome'] != 'CORREIOS') {
					$status=4;  // achou transportadora diferente de correios
					//break;
				}
			}
			echo "\n";
			$this->model_stores->updateStoresTiposVolumesStatus ($loja['id'], $loja['tipos_volumes_id'], $status);
			$this->model_stores->activeStoreIfNoNewCategory($loja['id']);
		}
 
		$this->log_data('batch',$log_name,'finish',"I");
	}

	function executaCadastroExpedidor($url, $post_data){
		
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_HEADER         => false,    // don't return headers
	        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
	        CURLOPT_ENCODING       => "",       // handle all encodings
	        CURLOPT_USERAGENT      => "conectala", // who am i
	        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
			CURLOPT_POST		=> true,
			CURLOPT_POSTFIELDS	=> $post_data,
			CURLOPT_HTTPHEADER =>  array('Content-Type:application/json')
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

    function executaAlteracaoExpedidor($url, $post_data){
		
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_HEADER         => false,    // don't return headers
	        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
	        CURLOPT_ENCODING       => "",       // handle all encodings
	        CURLOPT_USERAGENT      => "conectala", // who am i
	        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
			CURLOPT_POST		=> true,
			CURLOPT_POSTFIELDS	=> $post_data,
			CURLOPT_CUSTOMREQUEST => 'PATCH', 
			CURLOPT_HTTPHEADER =>  array('Content-Type:application/json')
	    );
		
		$options = array(
			CURLOPT_URL => $url, 
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_CUSTOMREQUEST => 'PATCH', 
	        CURLOPT_POSTFIELDS	=> $post_data,
			CURLOPT_HTTPHEADER =>  array('Content-Type:application/json')
	    );
	   // $ch      = curl_init( $url );
	    $ch      = curl_init();
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

	function consultaFreteRapido( $url,$post_data )
	{
	    $options = array(
	        CURLOPT_RETURNTRANSFER => true,     // return web page
	        CURLOPT_HEADER         => false,    // don't return headers
	        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
	        CURLOPT_ENCODING       => "",       // handle all encodings
	        CURLOPT_USERAGENT      => "conectala", // who am i
	        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
	        CURLOPT_CONNECTTIMEOUT => 120000,      // timeout on connect
	        CURLOPT_TIMEOUT        => 120000,      // timeout on response
	        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
			CURLOPT_POST		   => true,
			CURLOPT_POSTFIELDS     => $post_data,
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
?>
