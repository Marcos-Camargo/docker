<?php
/*

Verifica quais ordens precisam de frete e contrata no frete rápido

*/    
class IuguBatch extends BatchBackground_Controller {
		
	public function __construct()
	{
		parent::__construct();
		// log_message('debug', 'Class BATCH ini.');

   			$logged_in_sess = array(
   				'id' => 1,
		        'username'  => 'batch',
		        'email'     => 'batch@conectala.com.br',
		        'usercomp' => 1,
		        'logged_in' => TRUE
			);
		$this->session->set_userdata($logged_in_sess);
		
		// carrega os modulos necessários para o Job
		$this->load->model('model_iugu');
		$this->load->model('model_banks');
		$this->load->model('model_billet');
		$this->load->model('model_campaigns_v2');
		

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
		$this->atualizastatusboletosiuguautomatico();
		
		/* encerra o job */
		$this->log_data('batch',$log_name,'finish',"I");
		$this->gravaFimJob();
	}
	
	function atualizastatusboletosiuguautomatico(){
	    
	    //Busca todos os boletos da base
	    $boletos = $this->model_iugu->getBilletsData();
	    foreach($boletos as $boleto){
	        
	       if($boleto['status_iugu_id'] <> "9" and $boleto['status_iugu_id'] <> "5" ){
	            //Busca o status do Boleto na IUGU
	           $data = $this->model_iugu->getStatusIuguWs("Produção",$boleto['id_boleto_iugu'],$boleto['id']);
	            
	            //Traduz o status da IUGU
	            $data2 = $this->model_iugu->getStatusBilletIuguWsData($data['status']);
	            
	            //Salva o novo status do boleto
	            if($data['code'] == "0"){
	                //Atualiza Status Boleto na tabela
	                $att = $this->model_iugu->atualizaStatusBoletoTabela($boleto['id_boleto_iugu'], $data['status']);
	                if($att){
 	                    if($data2[0]['status_iugu_pt_br'] == "paga"){
	                       $ret = $this->enviaEmailPago($boleto);
	                    }else{
    	                   $ret['ok'] = "";
    	                   $ret['msg'] = "";
	                    }
	                    echo 'Boleto '.$boleto['id'].' '.$boleto['id_boleto_iugu'].'( '.$boleto['url_boleto_iugu'].' ) atualizado para o status: '.$data2[0]['status_iugu_pt_br'].' Retorno: '.$ret['ok']." - ".$ret['msg']."<br>";
	                }else{
	                    echo "Erro ao atualizar o boleto ".$boleto['id_boleto_iugu']."<br>";
	                }
	            }else{
	                echo "Erro ao atualizar o boleto ".$boleto['id_boleto_iugu']."<br>";
	            }
	            
	        }
	    }
	    
	}
	
	function enviaEmailPago($boleto){
	    try{

    	    //Busca dados da Loja
            $dados = $this->model_iugu->getStoresData($boleto['stores_id']);
            $dados = $dados[0];
    	    //Monta assunto
            $assunto = "Pagamento da fatura - ".$boleto['id_boleto_iugu']." Cliente - ".$dados['name']." - ".$dados['CNPJ'];

    	    //Monta e-mail de pago
    	    $mensagem = "<body>
                        	<div>
                        		<p>Prezados,</p> <br>
                                O pagamento do boleto <a href=\"".$boleto['url_boleto_iugu']."\">".$boleto['id_boleto_iugu']."</a> foi efetivado. <br>
                                Abaixo seguem as informações do cliente: <br>
                        		<table style='border: 1px solid black'> 
                                    <tr><th style='text-align: left'></th><td></td></tr>
                        			<tr><th style='text-align: left'>Nome Fantasia</th><td>".$dados['name']."</td></tr>
                        			<tr><th style='text-align: left'>Razão Social</th><td>".$dados['raz_social']."</td></tr>
                        			<tr><th style='text-align: left'>CNPJ</th><td>".$dados['CNPJ']."</td></tr>
                        			<tr><th style='text-align: left'>Responsável</th><td>".$dados['responsible_name']."</td></tr>
                                    <tr><th style='text-align: left'>E-mail </th><td>".$dados['responsible_email']."</td></tr>
                        		</table>
                        		<p>Obrigado</p> 
                        		<p>Equipe Conectalá</p> 
                        	</div>
                        </body>";
    	    
    	    return $this->sendEmailMarketing("clientenovo@conectala.com.br",$assunto,$mensagem);

	    }catch(Exception $e){
	        echo $e;die;
	    }
	    
	}
	
	function criasubcontaiugu($id=null,$params=null)
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
	    $this->criasubcontadelojas();
	    //$this->criasubcontadefornecedores();
	    
	    /* encerra o job */
	    $this->log_data('batch',$log_name,'finish',"I");
	    $this->gravaFimJob();
	}
	
	function criasubcontadelojas(){
	    echo '<pre>';
	    //busca os dados de loja para abertura de subconta
	    $dadosLoja = $this->model_iugu->buscaLojasSemSubconta();
	    
	    foreach($dadosLoja as $loja){
	        
	        $inputs['store_id'] = $loja['id'];
	        $inputs['providers_id'] = "";
	        
	        //Cria subconta
	        $store_data = $this->model_iugu->criaSubconta($inputs, "Produção");
	        print_r($store_data);
	        //Valida subconta
	        $retornoCriacao = explode(";",$store_data);
	        
	        if($retornoCriacao[0] == "0"){
	            $store_data2 = $this->model_iugu->validarSubconta($inputs, "Produção");
	            print_r($store_data2);
	        }
	        
	    }
	    
	    
	}
	
	function criasubcontadefornecedores(){
	    echo '<pre>';
	    //busca os dados de fornecedores para abertura de subcontaIUGU
	    $dadosFornecedor = $this->model_iugu->buscaFornecedoresSemSubconta();
	    
	    foreach($dadosFornecedor as $fornecedor){
	        
	        $inputs['store_id'] = "";
	        $inputs['providers_id'] = $fornecedor['id'];
	        
	        //Cria subconta
	        $store_data = $this->model_iugu->criaSubconta($inputs, "Produção");
	        print_r($store_data);
	        //Valida subconta
	        $retornoCriacao = explode(";",$store_data);
	        
	        if($retornoCriacao[0] == "0"){
	            $store_data2 = $this->model_iugu->validarSubconta($inputs, "Produção");
	            print_r($store_data2);
	        }
	        
	    }
	    
	}
	
	function validasubcontaiugu($id=null,$params=null)
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
	    $this->validasubcontadelojas();
	    //$this->validasubcontadefornecedores();
	    
	    /* encerra o job */
	    $this->log_data('batch',$log_name,'finish',"I");
	    $this->gravaFimJob();
	}
	
	function validasubcontadelojas(){
	    echo '<pre>';
	    //busca os dados de loja para abertura de subconta
	    $dadosLoja = $this->model_iugu->buscaLojasSemValidacao();
	    
	    foreach($dadosLoja as $loja){
	        
	        $inputs['store_id'] = $loja['id'];
	        $inputs['providers_id'] = "";
	        
	        //Valida subconta
	        $store_data2 = $this->model_iugu->validarSubconta($inputs, "Produção");
	        
	        $pos = strpos($store_data2, "account already verified");
	        
	        if ($pos === false) {
	            
	            $pos = strpos($store_data2, "conta já verificada");
	            if ($pos === false) {
	                echo "<br>";
	                echo $store_data2;echo '<br>';
	                echo "Loja: ".$inputs['store_id']."<br>";
	            }else{
	                $this->model_iugu->validastatussubconta($loja['id']);
	            }
	            
	        }else{
	            $this->model_iugu->validastatussubconta($loja['id']);
	        }
	        
	        
	        
	    }
	    
	    
	}
	
	function validasubcontadefornecedores(){
	    echo '<pre>';
	    //busca os dados de fornecedores para abertura de subcontaIUGU
	    $dadosFornecedor = $this->model_iugu->buscaFornecedoresSemValidacao();
	    
	    foreach($dadosFornecedor as $fornecedor){
	        
	        $inputs['store_id'] = "";
	        $inputs['providers_id'] = $fornecedor['id'];
	        
	        //Valida subconta
	        $store_data2 = $this->model_iugu->validarSubconta($inputs, "Produção");
	        print_r($store_data2);
	        
	    }
	    
	}
	
	
	function verificasubcontaiugu($id=null,$params=null)
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
	    $this->verificavalidacaosubconta();
	    
	    /* encerra o job */
	    $this->log_data('batch',$log_name,'finish',"I");
	}
	
	function verificavalidacaosubconta(){
	    
	    //Busca Subcontas
	    $subcontas = $this->model_iugu->buscadadostabelasubconta();
	    
	    echo '<pre>';
	    foreach($subcontas as $subconta){
	        //Atualiza Status
	        $saida = $this->model_iugu->atualizastatussubcontatabelaWS($subconta);
	        print_r($saida);
	    }
	    die;
	    
	    
	}
	
	function solicitapedidodesaque(){
	    
	    //Busca as contas que tem a solicitação de saque hoje
	    $subcontas = $this->model_iugu->buscadadostabelasubconta();
	    // 	    print_r($subcontas);die;
	    foreach($subcontas as $subconta){
	        echo "\n";
	        //Atualiza Status
	        if($subconta['saldo_disponivel'] > 0){
	            $saida = $this->model_iugu->solicitapedidodesaqueWS($subconta);
	            print_r($saida);
	        }
	    }
	    die;
	    
	}
	
	function splitbilletiuguemlote(){
	    
	    $i = 0;
		
		//$saida[1] = "";
		
		foreach($saida as $contaSaida){
	        
	        $valores = explode("-",$contaSaida);
	        
	        //Busca dados Subconta
	        $dadosSubconta = $this->model_iugu->buscadadostabelasubconta($valores[0]);
	        
	        $dadosWS = $dadosSubconta[0];
	        $dadosWS['valor_split'] = $valores[1]*100;
	        $dadosWS['billet_id'] = 0;
	        
	        //Realiza o Split de conta
	        $retornoSplit = $this->model_iugu->gerasplitsubcontaiugu($dadosWS,"Produção");
	        $arraySaida[$i] = $retornoSplit;
	        $i++;
	        
	        $teste = explode(";",$retornoSplit);
	        
	        if($teste[0] <> "0"){
	            $codeSaida = $teste[0];
	        }else{
	            //altera o valor do saldo disponivel
	            $this->model_iugu->alterasaldodisponivel($dadosWS['id'],$valores[1]);
	        }
	        
	    }
	    
	    echo "\n";
	    foreach($arraySaida as $print){
	        echo "\n".$print."\n";
	    }
	    die;
	    
	}

	function gerapagamentoconciliacao($input){

		set_time_limit(0);

		echo "[".date("Y-m-d H:i:s")."] - Inicio do Job gerapagamentoconciliacao\n";

		$arrayInput = explode("-",$input);
		$conciliacoes = $this->model_billet->getConciliacaoGridData(null);

		foreach($arrayInput as $idConciliacao){

			$conciliacaoDados = array();

			echo "[".date("Y-m-d H:i:s")."] - Buscando informações da conciliação $idConciliacao\n";

			foreach($conciliacoes as $conciliacao){

				if($idConciliacao == $conciliacao['id_con']){
					$conciliacaoDados = $conciliacao;
				}

			}

			if($conciliacaoDados){
				echo "[".date("Y-m-d H:i:s")."] - Conciliação $idConciliacao encontrada\n";

				$input = array();
				$input['tipo'] = "Ok";
				$input['lote'] = $conciliacaoDados['lote'];

				$mktplace = $conciliacaoDados['integ_id'];

				if($mktplace == "10"){
					$data = $this->model_billet->getOrdersGridsB2W($input);
				}elseif($mktplace == "15"){
					$data = $this->model_billet->getOrdersGridsViaVarejo($input);
				}elseif($mktplace == "11"){
					$data = $this->model_billet->getOrdersGridsML($input);
				}elseif($mktplace == "16"){
					$data = $this->model_billet->getOrdersGridsCarrefour($input);
					if(empty($data)){
						$data = $this->model_billet->getOrdersGridsCarrefourXls($input);
					}
				}elseif($mktplace == "17"){
					$data = $this->model_billet->getOrdersGridsMadeira($input);
				}
				elseif($mktplace == "30")
				{
					$data = $this->model_billet->getOrdersGridsNM($input);
				}elseif($mktplace == "999")
				{
					$data = $this->model_billet->getOrdersGridsManual($input);
				}

				if($data){
					foreach ($data as $key => $value) {
						$arrayPagamentos[$value['store_id']]['id_store'] = $value['store_id'];
						$arrayPagamentos[$value['store_id']]['Loja'] = $value['seller_name'];
						$arrayPagamentos[$value['store_id']]['valor'] += $value['valor_parceiro_ajustado'];
					}

				}

			}else{
				echo "[".date("Y-m-d H:i:s")."] - Conciliação $idConciliacao não encontrada\n";
			}

		}

		echo "[".date("Y-m-d H:i:s")."] - Print array pagamentos\n";
		print_r($arrayPagamentos);
		echo "[".date("Y-m-d H:i:s")."] - Gerando pagamento das Conciliações encontradas\n";

		$i = 0;
		foreach($arrayPagamentos as $contaSaida){
			
	        if($contaSaida['valor'] > 0){
				
				if($contaSaida['id_store'] <> ""){

					//Busca dados Subconta
					$dadosSubconta = $this->model_iugu->getSubAccountByStoreId($contaSaida['id_store']);
					
					if($dadosSubconta){
						$dadosWS = $dadosSubconta;
						$dadosWS['valor_split'] = $contaSaida['valor']*100;
						$dadosWS['billet_id'] = 0;
						
						//Realiza o Split de conta
						$retornoSplit = $this->model_iugu->gerasplitsubcontaiugu($dadosWS,"Produção");
						$arraySaida[$i] = $retornoSplit."\n"." loja: ".$dadosWS['store_id']." - ".$contaSaida['Loja']." Valor: ".$dadosWS['valor_split'];
						$i++;
	
						$teste = explode(";",$retornoSplit);
						
						if($teste[0] <> "0"){
							$codeSaida = $teste[0];
						}else{
							//altera o valor do saldo disponivel
							$this->model_iugu->alterasaldodisponivel($dadosWS['id'],$contaSaida['valor']);
						}
					}else{
						$arraySaida[$i] = $retornoSplit."\n"." erro ao split para loja : ".$contaSaida['Loja']." - ".$contaSaida['Loja']." subconta não encontrada!";
						$i++;
					}


				}else{
					$codeSaida = "Erro, pois o parâmetro de loja não foi enviado".implode(", ",$contaSaida);
				}
			}
	    }
	    
		echo "[".date("Y-m-d H:i:s")."] - Split realizado com sucesso\n";

	    echo "\n";
	    foreach($arraySaida as $print){
	        echo "\n".$print."\n";
	    }

		echo "[".date("Y-m-d H:i:s")."] - Fim do Job gerapagamentoconciliacao\n";

	}

	function alteracontabancaria($id=null,$params=null,$horas)
	{
	    /* inicia o job */
	    $this->setIdJob($id);
	    $log_name =$this->router->fetch_class().'/'.__FUNCTION__;
		dd(__FUNCTION__);
	    if (!$this->gravaInicioJob($this->router->fetch_class(),__FUNCTION__)) {
	        $this->log_data('batch',$log_name,'Já tem um job rodando ou que foi cancelado',"E");
	        return ;
	    }
	    $this->log_data('batch',$log_name,'start '.trim($id." ".$params),"I");
	    
	    /* faz o que o job precisa fazer */
	    $this->alteracontabancariadelojas();
	    
	    /* encerra o job */
	    $this->log_data('batch',$log_name,'finish',"I");
	    $this->gravaFimJob();
	}
	
	function alteracontabancariadelojas($horas = 1){

		$param['job'] = $horas;

		$lojas = $this->model_iugu->buscadadostabelasubconta3($param);
		
		if($lojas){

			foreach($lojas as $loja){
				
				$dados['live_api_key'] = $loja['live_api_token'];
				$dados['agencia'] = $loja['agency'];
				$dados['conta'] = $loja['account'];
				$dados['tipo_conta'] = $loja['tipo_conta'];
				$dados['banco'] = $loja['number'];
		
				$retorno = $this->model_iugu->alteravalordecontaagencia($dados);
		
				if(array_key_exists('errors',$retorno)){
					echo $retorno['errors']['base'][0];
				}else{
					echo "Subconta ".$loja['account_id']." atualizada com sucesso!";
				}

			}

		}else{
			echo "Nenhuma loja a atualizar!";
		}

	}

	function relatoriocompleto(){
		
		$lojas = $this->model_iugu->buscadadostabelasubconta();

		$result = array();
		$aux = 0;
		foreach($lojas as $lojaFor){

			if($lojaFor['ativo'] == "12"){

				$params['store_id'] 	= $lojaFor['store_id'];
				$params['providers_id'] = "";

				$conta = $this->model_iugu->buscaDadosValidacaoSubconta($params);
				$loja = $this->model_iugu->getStoresDataRelatorio($lojaFor['store_id']);

				if($conta){
					$relatorios = $this->model_iugu->buscarelatoriosaqueiuguws($conta['live_api_token']);
					if($relatorios){
						foreach ($relatorios as $key => $value) {
						
							$result[$aux] = array(
								$lojaFor['store_id'],
								$loja[0]['name'],
								$value['status'],
								$value['created_at'],
								"R$".str_replace ( ".", ",", $value['amount'] )
							);
							$aux++;
						} 
					}
				}
			}
		}
		echo "\n";
		if($result){
			echo '|'."Id Loja"."|"."Nome Loja"."|"."Status Saque"."|"."Data Sasque"."|"."Valor Saque"."|\n"; 
			foreach($result as $saidaTela){
				if($saidaTela['2'] <> "rejected"){
					echo '|'.$saidaTela['0']."|".$saidaTela['1']."|".$saidaTela['2']."|".$saidaTela['3']."|".$saidaTela['4']."|";
					echo "\n";
				}
			}
			echo "\n";
		}
		
	}
}
?>
