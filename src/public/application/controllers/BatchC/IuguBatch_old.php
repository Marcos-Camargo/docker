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
	
}
?>
