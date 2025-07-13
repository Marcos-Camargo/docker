<?php
/*
SW Serviços de Informática 2019

Controller de Recebimentos

*/  


defined('BASEPATH') OR exit('No direct script access allowed');

class Iugu extends Admin_Controller 
{
    private $iugu_library;

	public function __construct()
	{
		parent::__construct();

		$this->not_logged_in();

		$this->data['page_title'] = 'Parâmetros de Boletos';

		$this->load->model('model_iugu');
		$this->load->model('model_orders');
		$this->load->model('model_shipping_company');
		$this->load->model('model_billet');
		$this->load->model('model_banks');
		$usercomp = $this->session->userdata('usercomp');
		$this->data['usercomp'] = $usercomp;
		$more = " company_id = ".$usercomp;

        $this->load->library('IuguLibrary');
	}

	/* 
	* It only redirects to the manage order page
	*/
	public function index()
	{
		if(!in_array('viewBillet', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

		$this->data['page_title'] = 'Administrar os boletos';
		$this->render_template('iugu/list', $this->data);		
	}
	
	public function list()
	{
	    
	    if(!in_array('viewBillet', $this->permission)) {
	        redirect('dashboard', 'refresh');
	    }
	    
	    $split_data = $this->model_iugu->getBilletStatusData();
	    
	    $this->data['status_billets'] = $split_data;
	    
	    $this->render_template('iugu/list', $this->data);
	}

	/*
	* Fetches the orders data from the orders table 
	* this function is called from the datatable ajax function
	*/
	public function fetchBalanceData()
	{
	    
		$result = array('data' => array());
		
		$inputs = $this->input->get();
		
		$data = $this->model_iugu->getBilletsData(null, $inputs);
		
		setlocale(LC_MONETARY,"pt_BR", "ptb");
		
		foreach ($data as $key => $value) {

			// button
			$buttons = '';
			$status = '';

			if(in_array('viewBillet', $this->permission)) {
				$buttons .= ' <a href="'.base_url('iugu/view/'.$value['id']).'" class="btn btn-default"><i class="fa fa-eye"></i></a>';
			}
			
			if($value['status_billet'] == "Boleto Pago"){ // verde
			    $status = '<span class="label label-success">'.$value['status_billet'].'</span>';
			}elseif($value['status_billet'] == "Boleto Vencido" or $value['status_billet'] == "Boleto Cancelado"){ // vermelho
			    $status = '<span class="label label-danger">'.$value['status_billet'].'</span>';
			}else{ //amarelo
			    $status = '<span class="label label-warning">'.$value['status_billet'].'</span>';
			}
			

			$result['data'][$key] = array(
				$value['id'],
				$value['marketplace'],
				$value['email'],
			    $value['data_geracao'],
			    $value['data_vencimento'],
			    $value['data_pagamento'],
			    "R$ ".str_replace ( ".", ",", $value['valor_total'] ),
			    $status,
			    $value['status_iugu'],
				$buttons
			);
		} // /foreach

		echo json_encode($result);
	}
	
	public function fetchBalanceDataExcel()
	{
    
        header("Pragma: public");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: pre-check=0, post-check=0, max-age=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        header("Content-Transfer-Encoding: none");
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-type: application/x-msexcel; charset=utf-8");
        header("Content-Disposition: attachment; filename=Boletos.xls");
    	
    	$result = array('data' => array());
    	
    	$inputs = $this->input->get();
    	
    	$data = $this->model_iugu->getBilletsData(null, $inputs);
    	
    	setlocale(LC_MONETARY,"pt_BR", "ptb");
    	
    	foreach ($data as $key => $value) {
    	    
    	    $result['data'][$key] = array(
    	        $value['id'],
    	        $value['raz_social'],
    	        $value['CNPJ'],
    	        $value['responsible_name'],
    	        $value['phone_1'],
    	        $value['email'],
    	        $value['data_geracao'],
    	        $value['data_vencimento'],
    	        $value['data_pagamento'],
    	        "R$ ".str_replace ( ".", ",", $value['valor_total'] ),
    	        $value['status_billet'],
    	        $value['status_iugu']
    	    );
    	} // /foreach

    	echo "<table>
                    <tr>
                    <th>".$this->lang->line('application_id')."</th>
                    <th>".$this->lang->line('application_raz_soc')."</th>
                    <th>".$this->lang->line('application_cnpj')."</th>
                    <th>".$this->lang->line('application_responsible_name')."</th>
                    <th>".$this->lang->line('application_phone')."</th>
                    <th>".$this->lang->line('application_email')."</th>  
                    <th>".$this->lang->line('application_date')."</th>
                    <th>".$this->lang->line('application_due_date')."</th>
                    <th>".$this->lang->line('application_payment_date')."</th>
                    <th>".$this->lang->line('application_value')."</th>
                    <th>".$this->lang->line('application_status_billet')."</th>
                    <th>".$this->lang->line('application_status_billet_iugu')."</th>
                  </tr>";
    	
    	foreach($result['data'] as $value){
    	    
    	    echo "<tr>";
    	    echo "<td>".$value[0]."</td>";
    	    echo "<td>".$value[1]."</td>";
    	    echo "<td>".$value[2]."</td>";
    	    echo "<td>".$value[3]."</td>";
    	    echo "<td>".$value[4]."</td>";
    	    echo "<td>".$value[5]."</td>";
    	    echo "<td>".$value[6]."</td>";
    	    echo "<td>".$value[7]."</td>";
    	    echo "<td>".$value[8]."</td>";
    	    echo "<td>".$value[9]."</td>";
    	    echo "<td>".$value[10]."</td>";
    	    echo "<td>".$value[11]."</td>";
    	    echo "</tr>";
    	    
    	}
    	
    	echo "</table>";
	
	}
	
	public function createbillet(){
	    
	    $store_data = $this->model_iugu->getStoresData();
	    $split_data = $this->model_iugu->getSplitStatusData();
	    
	    $this->data['stores'] = $store_data;
	    $this->data['splits'] = $split_data;
	    
	    $this->render_template('iugu/create', $this->data);
	}
	
	public function buscaloja(){
	    
	    $inputs = $this->postClean(NULL,TRUE);
	    
	    $data = $this->model_iugu->getStoresData($inputs['slc_store']);
	    
	    echo json_encode($data);
	    
	}
	
	public function createbilletiugu(){
	    
	    $inputs = $this->postClean(NULL,TRUE);

	    $data = $this->model_iugu->criaBoleto($inputs);
	    
	    $verifica = explode(";",$data);
	    
	    if($verifica[0] == "1"){
	        $data = $this->model_iugu->criaBoleto($inputs, "produção", "erro de endereço");
	    }
	    
	    print_r($data);die;
	    
	}
	
	public function cancelbillet(){
	    
	    $store_data = $this->model_iugu->getBilletsData();
	    $store_data2 = $this->model_iugu->getStatusBilletIuguWsData();
	    
	    $this->data['billets'] = $store_data;
	    $this->data['status_iugu_wss'] = $store_data2;
	    
	    $this->render_template('iugu/cancel', $this->data);
	}
	
	public function buscarboletoid(){
	    
	    $inputs = $this->postClean(NULL,TRUE);
	    
	    $data = $this->model_iugu->getBilletsData($inputs['slc_billet']);
	    
	    echo json_encode($data[0]);
	    
	}
	
	public function cancelbilletstatusiugu(){
	    
	    $inputs = $this->postClean(NULL,TRUE);
	    
	    $data = $this->model_iugu->getStatusIuguWs("Produção",$inputs['txt_id'], null);
	    
	    $data2 = $this->model_iugu->getStatusBilletIuguWsData($data['status']);
	    
	    if($data['code'] == "0"){
	        //Atualiza Status Boleto na tabela
	        $this->model_iugu->atualizaStatusBoletoTabela($inputs['txt_id'], $data['status']);
	    }
	    
	    echo $data['code'].";".json_encode($data2[0]);
	    
	}
	
	public function cancelbilletiugu(){
	    
	    $inputs = $this->postClean(NULL,TRUE);
	    
	    $data = $this->model_iugu->getCancellBilletIuguWs("Produção",$inputs['txt_id']);
// 	    echo'<Pre>';print_r($data);die;
// 	    echo $data['code'].";".json_encode($data['saida']);
	    print_r($data);die;
	    
	}
	
	public function view($id = null){
	    
	    $store_data = $this->model_iugu->getBilletsData($id);
	    $store_data2 = $this->model_iugu->getStatusBilletIuguWsData();
	    
	    $this->data['billets'] = $store_data;
	    $this->data['status_iugu_wss'] = $store_data2;
	    
	    $this->render_template('iugu/view', $this->data);
	    
	}
	
	public function createsplit(){
	   
	    $store_data = $this->model_iugu->getBilletsData();
	    $store_data2 = $this->model_iugu->getSplitStatusData();
	    $store_data3 = $this->model_iugu->getStatusBilletIuguWsData();
	    $store_data4 = $this->model_iugu->buscadadostabelasubconta2();
	    
	    $this->data['billets'] = $store_data;
	    $this->data['status_splits'] = $store_data2;
	    $this->data['status_iugu_wss'] = $store_data3;
	    $this->data['subcontas_iugu'] = $store_data4;
	    
	    $this->render_template('iugu/split', $this->data);
	    
	}
	
	public function criasucontaiugu(){
	    
	    echo '<pre>';
	    
	    $inputs = $this->postClean(NULL,TRUE);
	    
	    //busca os dados de loja para abertura de subconta
	    $dadosLoja = $this->model_iugu->buscaLojasSemSubconta();
	    
	    foreach($dadosLoja as $loja){
	        
	        $inputs['store_id'] = $loja['id'];
	        $inputs['providers_id'] = "";
	        
	        //Cria subconta
	        $store_data = $this->model_iugu->criaSubconta($inputs, "Produção");
	        
	        //Valida subconta
	        $retornoCriacao = explode(";",$store_data);
	        
	        if($retornoCriacao[0] == "0"){
	            $store_data2 = $this->model_iugu->validarSubconta($inputs, "Produção");
	            print_r($store_data2);
	        }else{
	            print_r($store_data);
	        }
	        
	    }
	    
	    //busca os dados de fornecedores para abertura de subcontaIUGU
	    $dadosFornecedor = $this->model_iugu->buscaFornecedoresSemSubconta();
	    
	    foreach($dadosFornecedor as $fornecedor){
	        
	        $inputs['store_id'] = "";
	        $inputs['providers_id'] = $fornecedor['id'];
	        
	        //Cria subconta
	        $store_data = $this->model_iugu->criaSubconta($inputs, "Produção");
	        
	        //Valida subconta
	        $retornoCriacao = explode(";",$store_data);
	        
	        if($retornoCriacao[0] == "0"){
	            $store_data2 = $this->model_iugu->validarSubconta($inputs, "Produção");
	            print_r($store_data2);
	        }else{
	            print_r($store_data);
	        }
	        
	    }
	    
	    
	    
	}
	
	public function verificastaussubcontaiugu(){
	    
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
	
	public function solicitapedidodesaque(){
	    
	    //Busca as contas que tem a solicitação de saque hoje
	    $subcontas = $this->model_iugu->buscadadostabelasubconta();
// 	    print_r($subcontas);die;
	    echo '<pre>';
	    foreach($subcontas as $subconta){
	        echo '<br>';
	        //Atualiza Status
	        if($subconta['saldo_disponivel'] > 0){
	           $saida = $this->model_iugu->solicitapedidodesaqueWS($subconta);
    	        print_r($saida);
	        }
	        echo '<br>';
	    }
	    die;
	    
	    
	}
	
	public function atualizastatusboletosiuguautomatico(){
	    
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
    	                echo 'Boleto '.$boleto['id'].' '.$boleto['id_boleto_iugu'].'( '.$boleto['url_boleto_iugu'].' ) atualizado para o status: '.$data2[0]['status_iugu_pt_br'].'<br>';
    	            }else{
    	                echo "Erro ao atualizar o boleto ".$boleto['id_boleto_iugu']."<br>";
    	            }
    	        }else{
    	            echo "Erro ao atualizar o boleto ".$boleto['id_boleto_iugu']."<br>";
    	        }
    	        
	        }
	        
	    }
	    
	}
	
	public function splitbilletiugu(){
	    
	    $inputs = $this->postClean(NULL,TRUE);
	    $contaSplit = $inputs['arraysplit'];
	    $saida = explode(",",$contaSplit);
	    $arraySaida = array();
	    $i = 0;
	    $codeSaida = 0;
	    
	    foreach($saida as $contaSaida){
	        
	        $valores = explode("-",$contaSaida);
	        
	        //Busca dados Subconta
	        $dadosSubconta = $this->model_iugu->buscadadostabelasubconta($valores[0]);
            
	        $dadosWS = $dadosSubconta[0];
	        $dadosWS['valor_split'] = $valores[1]*100;
	        $dadosWS['billet_id'] = $inputs['slc_billet'];
	        
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
	    implode("|",$arraySaida);
	    echo $codeSaida.'$'.implode("|",$arraySaida);
	    die;
	    
	}
	
	public function subcontastatus(){
	    
	    //Busca informações financeiras da conta IUGU
	    $store_data = $this->model_iugu->getStoresData();
	    $tranps = $this->model_shipping_company->getProviderData();
	    
	    $this->data['stores'] = $store_data;
	    $this->data['transportadoras'] = $tranps;
	    $this->render_template('iugu/subcontastatus', $this->data);
	    
	}
	
	public function subcontastatuslog($loja = null, $fornecedor = null){
	    
	    $inputs = $this->postClean(NULL,TRUE);
	    
	    $inputs['slc_store'] = $loja;
	    $inputs['slc_transportadora'] = $fornecedor;

		if($loja == null && $fornecedor == null){
			$result['data'][0] = array("","","","","","");
		}else{
	    
			$data = $this->model_iugu->subcontastatuslog($inputs);

			if($data){
				foreach ($data as $key => $value) {
					
					$result['data'][$key] = array(
						$value['id'],
						$value['loja'],
						$value['fornecedor'],
						$value['statuss'],
						$value['log'],
						$value['data_log']
					);
				} // /foreach
			}else{
				$result['data'][0] = array("","","","","","");
			}
		}
	    echo json_encode($result);
	    
	    
	}

	public function geraestornosplitsubcontaiugu($idStore){
	    
	    $param['store_id'] = $idStore;
	    $param['providers_id'] = "0";
	    $param['company_id'] = "0";
	    
	    $retornoDados = $this->model_iugu->buscadadostabelasubcontaextrato($param);
	    
	    $retornoSplit = $this->model_iugu->geraestornosplitsubcontaiugu($retornoDados,"Produção");
	    
	    echo '<pre>';print_r($retornoSplit);die;
	    
	}
	
	
	public function splitbilletiuguemlote(){
	    
	    $saida[1] = "";

	    foreach($saida as $contaSaida){
	        
	        $valores = explode("-",$contaSaida);
	        
	        //Busca dados Subconta
	        $dadosSubconta = $this->model_iugu->buscadadostabelasubconta($valores[0]);
	        
	        $dadosWS = $dadosSubconta[0];
	        $dadosWS['valor_split'] = $valores[1]*100;
	        $dadosWS['billet_id'] = $inputs['slc_billet'];
	        
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
	    implode("|",$arraySaida);
	    echo '<br>'.$codeSaida.'$'.implode("|",$arraySaida).'<br>';
	    die;
	    
	}

	public function relatoriosaque(){

		//Busca informações financeiras da conta IUGU
	    $store_data = $this->model_iugu->getStoresDataRelatorio();
	    
	    $this->data['stores'] = $store_data;
	    $this->render_template('iugu/relatoriosaque', $this->data);

	}

	public function getrelatoriosaque($store = null){
		
		if($store == null || $store == ""){
			$result['data'][0] = array("","","","","");
			echo json_encode($result);
			die;
		}

		$params['store_id'] 	= $store;
		$params['providers_id'] = "";

		$conta = $this->model_iugu->buscaDadosValidacaoSubconta($params);
		$loja = $this->model_iugu->getStoresDataRelatorio($store);

		if($conta){
			$relatorios = $this->model_iugu->buscarelatoriosaqueiuguws($conta['live_api_token']);

			if($relatorios){

				foreach ($relatorios as $key => $value) {
    	        
					$result['data'][$key] = array(
						$store,
						$loja[0]['name'],
						$value['status'],
						$value['created_at'],
						"R$ ".str_replace ( ".", ",", $value['amount'] )
					);
				} // /foreach
				
			}else{
				$result['data'][0] = array("","","","","");
			}
		}else{
			$result['data'][0] = array("","","","","");
		}

		echo json_encode($result);

	}

	public function getrelatoriosaqueexcel($store = null){

		header("Pragma: public");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: pre-check=0, post-check=0, max-age=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        header("Content-Transfer-Encoding: none");
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-type: application/x-msexcel; charset=utf-8");
        header("Content-Disposition: attachment; filename=Relatorio Saque.xls");

		if($store == null || $store == ""){
			$result['data'][0] = array("","","","","");
		}else{

			$params['store_id'] 	= $store;
			$params['providers_id'] = "";

			$conta = $this->model_iugu->buscaDadosValidacaoSubconta($params);
			$loja = $this->model_iugu->getStoresData($store);

			if($conta){
				$relatorios = $this->model_iugu->buscarelatoriosaqueiuguws($conta['live_api_token']);

				if($relatorios){

					foreach ($relatorios as $key => $value) {
					
						$result['data'][$key] = array(
							$store,
							$loja[0]['name'],
							$value['status'],
							$value['created_at'],
							"R$ ".str_replace ( ".", ",", $value['amount'] )
						);
					} // /foreach
					
				}else{
					$result['data'][0] = array("","","","","");
				}
			}else{
				$result['data'][0] = array("","","","","");
			}

		}	

		echo '<table border = "1">';
		echo '<tr>
					<td>Id Loja</td>
					<td>Loja</td>
					<td>Status Saque</td>
					<td>Data Saque</td>
					<td>Valor Saque</td>
              </tr>';

		if($result){
			foreach($result['data'] as $value){
				echo "<tr>";
				echo "<td>".$value[0]."</td>";
				echo "<td>".$value[1]."</td>";
				echo "<td>".$value[2]."</td>";
				echo "<td>".$value[3]."</td>";
				echo "<td>".$value[4]."</td>";
				echo "</tr>";
			}
		}

		echo '</table>';

	}

	public function exportarelatoriocompletoiugu(){

		header("Pragma: public");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: pre-check=0, post-check=0, max-age=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        header("Content-Transfer-Encoding: none");
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-type: application/x-msexcel; charset=utf-8");
        header("Content-Disposition: attachment; filename=Relatorio Saques Completo IUGU.xls");

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
		
		if($result){
			echo'<table border="1"><tr><td>Id Loja</td><td>Nome Loja</td><td>Status Saque</td><td>Data Sasque</td><td>Valor Saque</td></tr>';
			foreach($result as $saidaTela){
				if($saidaTela['2'] <> "rejected"){
					$dataAux = explode ("T",$saidaTela['3']);
					$dataDia = explode("-",$dataAux[0]);
					$dataHora = explode("-",$dataAux[1]);
					$dataFinal = $dataDia[2]."/".$dataDia[1]."/".$dataDia[0]." ".$dataHora[0];
					echo '<tr><td>'.$saidaTela['0']."</td><td>".utf8_decode($saidaTela['1'])."</td><td>".$saidaTela['2']."</td><td>".$dataFinal."</td><td>".$saidaTela['4']."</td></tr>";
				}
			}
			echo '</table>';
		}
	}
	
	/************* TESTE *****************/
	
	public function testedados(){
	    
	    //Busca Subcontas
	    $subconta['live_api_token'] = "ODIwZDU3Y2M5YWQwY2E2MDNjMDE3NGNhNDFmYTdkNzE6";
	    $subconta['account_id'] = "560A6555ADB44262A72C2F42F48C8E1D";
	    
	    echo '<pre>';
        $saida = $this->model_iugu->atualizastatussubcontatabelaWS($subconta);
        print_r($saida);
	    die;
	    
	}

	public function testevalidasubconta(){
	    
	    echo '<pre>';
	    $inputs['store_id'] = 42;
	    $inputs['providers_id'] = "";
	    //Valida subconta
	    $store_data2 = $this->model_iugu->validarSubconta($inputs, "Produção");
	    print_r($store_data2);echo '<br>';
	    echo "Loja: ".$inputs['store_id']."<br>";
	    
	    
	    $inputs['store_id'] = 50;
	    $inputs['providers_id'] = "";
	    echo "<br>";
	    //Valida subconta
	    $store_data2 = $this->model_iugu->validarSubconta($inputs, "Produção");
	    print_r($store_data2);echo '<br>';
	    echo "Loja: ".$inputs['store_id']."<br>";
	    
	}

	public function alteravalordecontaagenciamanual($store){

		$param['store_id'] = $store;
		//$param['job'] = 1;

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
	
	public function alteravalordecontaagencia(){
	    
	    //Busca as chaves no banco
	    $keys = $this->model_iugu->buscaChaveNoBanco();
	    
	    // COLOCAR O LIVE API TOKEN
	    $headers = array(
	        'Content-Type: application/json',
	        'Authorization: Basic '.base64_encode("5a1df6b1a70937d8975ac45a458c729b".':')
	    );
	    
	    $data = array(
            'agency' => '1012',
	        'account' => '67900-5',
            'account_type' => 'cc',
            'bank' => '341'
        );
	    
	    /*
	        Banco do Brasil -> 001
            Santander -> 033
            Caixa Econômica -> 104
            Bradesco -> 237
            Next -> 237
            Itaú -> 341
            Banrisul -> 041
            Sicredi -> 748
            Sicoob -> 756
            Inter -> 077
            BRB -> 070
            Via Credi -> 085
            Neon/Votorantim -> 655
            Nubank -> 260
            Pagseguro -> 290
            Banco Original -> 212
            Safra -> 422
            Modal -> 746
            Banestes -> 021
            Unicred -> 136
            Money Plus -> 274
            Mercantil do Brasil-> 389
            JP Morgan -> 376
            Gerencianet Pagamentos do Brasil-> 364
            Banco C6 -> 336
            BS2 -> 218
            Banco Topazio -> 082
            Uniprime -> 099
            Banco Stone -> 197
            Banco Daycoval -> 707
            Banco Rendimento-> 633
            Banco do Nordeste-> 004
            Citibank -> 745
            PJBank -> 301
            Cooperativa Central de Credito Noroeste Brasileiro-> 97
            Uniprime Norte do Paraná-> 084
            Global SCM -> 384
            Cora -> 403
            Mercado Pago -> 323
            Banco da Amazonia-> 003
            BNP Paribas Brasil-> 752
            Juno -> 383
            Cresol -> 133
            BRL Trust DTVM -> 173
            Banco Banese-> 047
	     */    
	        
	    $url = 'https://api.iugu.com/v1/bank_verification';
	    
	    $curl = curl_init($url);
	    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
	    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
	    $response = curl_exec($curl);
	    $response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	    $err     = curl_errno( $curl );
	    $errmsg  = curl_error( $curl );
	    $header  = curl_getinfo( $curl );
	    $header['httpcode']   = $response_code;
	    $header['errno']   = $err;
	    $header['errmsg']  = $errmsg;
	    $header['content'] = $response;
	    
	    
	    if (!empty(curl_error($curl))) {
	        echo curl_error($curl);
	    } else {
	        $array_response = json_decode($response, true);
	    }
	    
	    curl_close($curl);
	    
	    if( is_array($header['httpcode']) ){
	        $httpcodeLog = implode($header['httpcode']);
	    } else {
	        $httpcodeLog = $header['httpcode'];
	    }
	    
	    if( is_array($header['errno']) ){
	        $erronoLog = implode($header['errno']);
	    } else {
	        $erronoLog = $header['errno'];
	    }
	    
	    if( is_array($header['errmsg']) ){
	        $errmsgLog = implode($header['errmsg']);
	    } else {
	        $errmsgLog = $header['errmsg'];
	    }
	    
	    if( is_array($header['content']) ){
	        $contentLog = implode($header['content']);
	    } else {
	        $contentLog = $header['content'];
	    }
	    
	    echo '<pre>';print_r($array_response);die;
	    
	}
	
	public function retornadinheirosubcontaemlote(){
	    
	    /*$saida[1]="9236a3f24c9187784637e868ed776392-253942";
	    $saida[2]="c43749cd254e537015161287a110bc7b-26170";
	    $saida[3]="1847b9ad27f14f937449167a2ecb65d3-98037";*/
		
		$saida[1]="dbec6dec872dc0f13a1a49e8a717e4a7-647256";
		$saida[2]="BB1EDC21C1907EDC454F20CE4662AC76AF367DA4BD2372ED117263F7B879923D-1288246";

	    foreach($saida as $contaSaida){
	        
	        $valores = explode("-",$contaSaida);
	        echo '<pre>';
	        echo "<br><br>live: ".$valores[0];
	        echo "<br>valor: ".$valores[1].'<br>';
	        $this->retornardinheirosubcontaparaprinciparl($valores[0],$valores[1]);
	        
	    }
	    
	}
	
	function retornardinheirosubcontaparaprinciparl($liveApi, $valor){
	    
	    // COLOCAR O LIVE API TOKEN (DA SUBCONTA AQUI)
	    $headers = array(
	        'Content-Type: application/json',
	        'Authorization: Basic '.base64_encode($liveApi.':')
	    );
	    
	    // COLOCAR O ID DA CONTA QUE VAI RECEBER O DINHEIRO
	    // COLOCAR O VALOR QUE VAI SER TRANSFERIDO
	    
	    $data = array(
	        'receiver_id' => '560A6555ADB44262A72C2F42F48C8E1D',
	        'amount_cents'  => $valor
	    );
	    
	    $url = 'https://api.iugu.com/v1/transfers';
	    
	    $curl = curl_init($url);
	    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
	    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
	    $response = curl_exec($curl);
	    $response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	    $err     = curl_errno( $curl );
	    $errmsg  = curl_error( $curl );
	    $header  = curl_getinfo( $curl );
	    $header['httpcode']   = $response_code;
	    $header['errno']   = $err;
	    $header['errmsg']  = $errmsg;
	    $header['content'] = $response;
	    
	    
	    if (!empty(curl_error($curl))) {
	        echo curl_error($curl);
	    } else {
	        $array_response = json_decode($response, true);
	    }
	    
	    curl_close($curl);
	    
	    if( is_array($header['httpcode']) ){
	        $httpcodeLog = implode($header['httpcode']);
	    } else {
	        $httpcodeLog = $header['httpcode'];
	    }
	    
	    if( is_array($header['errno']) ){
	        $erronoLog = implode($header['errno']);
	    } else {
	        $erronoLog = $header['errno'];
	    }
	    
	    if( is_array($header['errmsg']) ){
	        $errmsgLog = implode($header['errmsg']);
	    } else {
	        $errmsgLog = $header['errmsg'];
	    }
	    
	    if( is_array($header['content']) ){
	        $contentLog = implode($header['content']);
	    } else {
	        $contentLog = $header['content'];
	    }
	    
	    print_r($array_response);
	    
	}
	
	public function criasubcontasfullnine($id = null){
	    
	    /*
	        Full Nine - Setup
            Full Nine - Comissões
            Full Nine - Fretes
	     */
	    echo '<pre>';
	    
	    $inputs['store_id'] = "86";
	    $inputs['providers_id'] = "";
	    $nomeLoja = "Full Nine - COMISSÕES";
	    
	    if($id == null ){
    	    //Cria subconta
    	    $store_data = $this->model_iugu->criaSubcontaullNine($inputs, "Produção", $nomeLoja);
    	    print_r($store_data);
    	    //Valida subconta
    	    $retornoCriacao = explode(";",$store_data);
    	    
    	    if($retornoCriacao[0] == "0"){
    	        $store_data2 = $this->model_iugu->validarSubcontaNine($inputs, "Produção", $nomeLoja);
    	        print_r($store_data2);
    	    }
	    }else{
	        $store_data2 = $this->model_iugu->validarSubcontaNine($inputs, "Produção", $nomeLoja);
	        print_r($store_data2);
	    }
	    
	}

	public function testebanco(){

		echo $this->model_banks->getBankNumber("teste");
		die;

	}


    //braun
    public function listPlans()
    {
        if(!in_array('viewIuguPlans', $this->permission))
            redirect('dashboard', 'refresh');
        
        // $this->data['permissoes'] = $this->permission;

        $plans          = $user_names = $modal_stores = [];
        $plans_array    = $this->model_iugu->listPlans();

        if ($plans_array)
        {
            foreach ($plans_array as $key => $plan)
            {
                $plans[$key]    = $plan;
                $user_names[]   = $plan['user_name'];
            }
        }

        $user_names = array_unique($user_names);
        $stores = $this->model_iugu->getPlansStoreFilters();

        $this->data['plans']        = $plans;
        $this->data['user_names']   = $user_names;
        $this->data['stores']       = $stores;

        $modal_billing_history_initial_logs = $this->model_iugu->getInitialBillingHistory();
        $this->data['billing_history_initital_logs'] = $modal_billing_history_initial_logs;

        if ($modal_billing_history_initial_logs)
        {
            foreach ($modal_billing_history_initial_logs as $store)
            {
                $modal_stores[$store['store_id']] = $store['name'];
            }
        }
        
        $this->data['modal_stores'] = $modal_stores;

        $this->render_template('iugu/listplans', $this->data);
    }


    //braun
    public function createPlan()
    {
        if(!in_array('createIuguPlans', $this->permission))
            redirect('dashboard', 'refresh');
        
        $post = $this->postClean();

        if ($post)
        {
            $this->db->trans_begin();

            if ($this->model_iugu->checkIfPlanExists($post['plan_title']))
            {
                $this->session->set_flashdata('error', $this->lang->line('iugu_plans_creation_msg_error'));
                redirect('iugu/listPlans', 'refresh');
            }

            $new_plan['plan_title']         = strip_tags($post['plan_title']);
            $new_plan['plan_type']          = $post['plan_type'];
            $new_plan['plan_value']         = str_replace(',', '.', $post['plan_value']) * 100;
            $new_plan['plan_installments']  = intVal($post['plan_installments']);            
            $new_plan['installment_value']  = round(($post['plan_value'] / $post['plan_installments']), 2) * 100;
            $new_plan['plan_status']        = intVal($post['plan_status']);
            $new_plan['user_id']            = intVal($this->session->userdata('id'));

            $saved_plan = $this->saveNewPlan($new_plan);

            if ($saved_plan)
            {
                $iugu_transaction_data = $this->iugulibrary->saveIuguPlan($new_plan, $saved_plan);

                if ($iugu_transaction_data['response_code'] == 200)
                {
                    $this->db->trans_commit();  
                    $this->session->set_flashdata('success', $this->lang->line('iugu_plans_creation_msg_success'));
                }
                else
                {
                    $this->db->trans_rollback();
                    $this->session->set_flashdata('error', $this->lang->line('iugu_plans_creation_msg_error'));
                }

                $this->logTransaction($iugu_transaction_data);
            }
            else
            {
                $this->db->trans_rollback();
                $this->session->set_flashdata('error', $this->lang->line('iugu_plans_creation_msg_error'));
                $this->log_data('general', __FUNCTION__, serialize($new_plan), 'E');
            }

           redirect('iugu/listPlans', 'refresh'); 
        }

        $this->render_template('iugu/createplan', $this->data);
    }


    //braun
    public function saveNewPlan(array $new_plan)
    {
        if (!in_array('createIuguPlans', $this->permission))
        {
            redirect('dashboard', 'refresh');
        }

        return $this->model_iugu->insertNewPlan($new_plan);
    }


    //braun
    public function checkPlanTitle()
    {
        $inputs = $this->postClean();

        if (!empty(trim($inputs['plan_title'])))
        {
            $plan_title_exists = $this->model_iugu->checkIfPlanExists(trim($inputs['plan_title']));
            
            if (!$plan_title_exists)
                die('OK');
            else
                die('ERROR');        
        }

        die('ERROR');
    }


    //braun
    public function togglePlanStatus()
    {
        if (!in_array('updateIuguPlans', $this->permission))
        {
            redirect('dashboard', 'refresh');
        }

        $inputs = $this->postClean();

        if (!empty(trim($inputs['plan_id'])))
        {
            $new_status = $this->model_iugu->togglePlanStatus($inputs);

            //braun duvida
            //se o plano for desativado precisa remover todos os clientes dele?

            die($new_status);
        }

        die('ERROR');
    }


    //braun
    public function storesInPlan($plan_id = null)
    {
        if(!in_array('createIuguPlans', $this->permission))
            redirect('dashboard', 'refresh');

        $inputs = $this->postClean();        

        if ($inputs && (count($inputs['store_ids']) > 0) && (count($inputs['plan_dates']) > 0))
        {
            $relations_true = 0;
            $store_ids = $inputs['store_ids'];
            $plan_dates = $inputs['plan_dates'];

            foreach ($store_ids as $key => $val)
            {
                $this->db->trans_begin();

                $iugu_plan_stores_id = [0, 0];
                $plan_date = explode('-', $plan_dates[$key]);
                $plan_date = $plan_date[2].'-'.$plan_date[1].'-'.$plan_date[0];

                $iugu_plan_stores_id = $this->model_iugu->addStoreToPlan($inputs['plan_id'], $store_ids[$key], $plan_date);

                if ($iugu_plan_stores_id > 0)
                {                                        
                    //alem de criar o cliente e o plano, e associar cliente com o plano, é necessário tambem 
                    //criar a assinatura, que relaciona um com o outro para gerar a recorrencia

                    $subscription_data = $this->iugulibrary->createSubscription($inputs['plan_id'], $store_ids[$key], $iugu_plan_stores_id, $plan_dates[$key]);

                    if ($subscription_data['response_code'] == 200)
                    {
                        $relations_true++;
                        $this->db->trans_commit();  
                    }
                    else
                    {
                        $this->db->trans_rollback();
                    }

                    $this->logTransaction($subscription_data);
                }
                else
                {
                    $this->db->trans_rollback();
                    $this->session->set_flashdata('error', $this->lang->line('iugu_plans_creation_msg_error'));
                }                
            }
            
            ob_clean();

            if ($relations_true == count($inputs['store_ids']))
                echo 'success';
            else if ($relations_true > 0 && $relations_true < count($inputs['store_ids']))
                echo 'warning';
            else
                echo 'error';
                
            return;
        }

        if (!$plan_id)
        {
            $this->session->set_flashdata('error', $this->lang->line('iugu_plans_storesinplan_msg_error'));
            redirect('iugu/listPlans', 'refresh');
        }

        $this->data['available_stores']     = $this->model_iugu->getAvailableStores();
        $this->data['current_stores']       = $this->model_iugu->getCurrentStores($plan_id);
        $this->data['plan_data']            = $this->model_iugu->getPlanData($plan_id);

        $this->render_template('iugu/storesInPlan', $this->data);
    }


    //braun
    public function removeStoreInPlan()
    {
        if (!in_array('updateIuguPlans', $this->permission))
        {
            redirect('dashboard', 'refresh');
        }

        $inputs = $this->postClean();

        if ($inputs['plan_id'] && $inputs['store_id'])
        {
            $this->db->trans_begin();

            $toggle_id = $this->model_iugu->toggleStoreInPlanStatus($inputs['plan_id'], $inputs['store_id']);

            if ($toggle_id > 0)
            {
                //remove a assinatura via api
                $iugu_remove_subscription_data = $this->iugulibrary->removeSubscription($toggle_id, $inputs['store_id']);

                if (is_array($iugu_remove_subscription_data) && !empty($iugu_remove_subscription_data))
                {
                    $this->logTransaction($iugu_remove_subscription_data);
                    $this->db->trans_commit(); 
                    die('success');
                }
            }

            $this->db->trans_rollback();
        }

        die('error');
    }


    //braun
    public function getModalPlanDetails($plan_id = null)
    {
        if (!in_array('viewIuguPlans', $this->permission))
        {
            redirect('dashboard', 'refresh');
        }

        $plan_details = $this->model_iugu->getPlanData($plan_id);

        ob_clean();
        echo json_encode($plan_details, JSON_FORCE_OBJECT);
    }


    //braun
    public function logTransaction($log = null): bool
    {
        $iugu_log_array = [
            'httpcode'  => $log['response_code'],
            'erro'      => serialize([$log['url'], $log['method'], $log['headers']]),
            'errmsg'    => $log['result'],
            'content'   => $log['data'],
            'store_id'  => $log['store_id']
        ];

        return $this->model_iugu->saveTransactionLog($iugu_log_array);
    }
	
}