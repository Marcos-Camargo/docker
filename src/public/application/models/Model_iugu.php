<?php 
/*
SW Serviços de Informática 2019
 
Model de Acesso ao BD para Recebimentos

*/

use phpDocumentor\Reflection\Types\Boolean;

class Model_iugu extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}
	
	public function criaBoleto($params , $tipoChaves = "Produção", $erro = null){
	    
	    /* 'cc_emails' => 'andrerisi@conectala.com.br;fernandoschumacher@conectala.com.br;filippemey@conectala.com.br;ricardoschaffer@conectala.com.br', */
	    
	    //Busca as chaves no banco
	    $keys = $this->buscaChaveNoBanco();
	    
	    if($params['slc_billet'] == "Outros"){
	        $desc = $params['slc_billet']." - ".$params['txt_outros'].": ".$params['txt_desc_item'];
	    }else{
	        $desc = $params['slc_billet'].": ".$params['txt_desc_item'];
	    }
	    
	    $params['txt_valor'] = $params['txt_valor']*100;
	    $params['txt_cpf_cnpj'] = str_replace(".","",$params['txt_cpf_cnpj']);
	    $params['txt_cpf_cnpj'] = str_replace("/","",$params['txt_cpf_cnpj']);
	    
	    
	    if( $params['txt_forma_pagamento'] == "boleto" ){
	        $tipoPagamento = "bank_slip";
	    }elseif( $params['txt_forma_pagamento'] == "cartao" ){
	        $tipoPagamento = "credit_card";
	    }else{
	        $tipoPagamento = "all";
	    }
	    
	    $headers = array(
	        'Content-Type: application/json',
	        'Authorization: Basic '.$keys['chave']
	    );
	    
	    // Pega a rua cadastrada na loja, ao invés de deixar a usada pelo CEP automático
	    if($erro <> null){
	        
    	    $data = array(
    	        'payer' => array(
    	            'name' => $params['txt_nome'],
    	            'cpf_cnpj' => $params['txt_cpf_cnpj'],
    	            'address' => array(
    	                'zip_code' => $params['txt_cep'],
    	                'street' => $params['txt_endereco'],
    	                'district' => $params['txt_bairro'],
    	                'city' => $params['txt_cidade'],
    	                'state' => $params['txt_uf'],
    	                'country' => $params['txt_pais'],
    	                'number' => $params['txt_numero']
    	            ),
    	        ),
    	        'items' => array(
    	            array('description' => $desc, 'quantity' => '1', 'price_cents' => $params['txt_valor'])
    	        ),
    	        'email' => $params['txt_email'],
    	        'cc_emails' => 'financeiro@conectala.com.br',
    	        'payable_with' => $tipoPagamento,
    	        'due_date' => $params['txt_dt_vencimento']
    	    );
    	    
	    }else{
	        
	        $data = array(
	            'payer' => array(
	                'name' => $params['txt_nome'],
	                'cpf_cnpj' => $params['txt_cpf_cnpj'],
	                'address' => array(
	                    'zip_code' => $params['txt_cep'],
	                    'number' => $params['txt_numero']
	                ),
	            ),
	            'items' => array(
	                array('description' => $desc, 'quantity' => '1', 'price_cents' => $params['txt_valor'])
	            ),
	            'email' => $params['txt_email'],
	            'cc_emails' => 'financeiro@conectala.com.br',
	            'payable_with' => $tipoPagamento,
	            'due_date' => $params['txt_dt_vencimento']
	        );
	        
	    }

	    $url = 'https://api.iugu.com/v1/invoices';
	    
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
       
        //Salvar Log da execução
        $this->logoIUGUExecucao(    $httpcodeLog,
                                    $erronoLog,
                                    $errmsgLog,
                                    $contentLog,
                                    $params['slc_store'],0);
        
        if($response_code == "200"){
            //Salva as informações do Boleto
            $this->salvaBoletoIUGU( $params, $array_response);
            return "0;Boleto Gerado com sucesso!<br>Id: ".$array_response['id']."<br>URL: ".$array_response['secure_url'];
        } else {
            $saida = "";
            foreach ($array_response as $resposta){
                if($saida == ""){
                    $saida = key($resposta).": ";
                }else{
                    $saida .= "<br>".key($resposta).": ";
                }
                foreach($resposta as $mensagem){
                    $i = 0;
                    foreach($mensagem as $final){
                        if($i == "0"){
                            $saida .= $final;
                        }else{
                            $saida .= ", ".$final;
                        }
                     $i++;
                    }
                }
            }
            
            return "1;Erro na geração do boleto:<br>".$saida;
        }
	    
	}
	
	function buscaChaveNoBanco($tipoChaves = "Produção", $id=0){
	    
	    $sql = "select *
                from iugu_keys i
                where stores_id = $id and ambiente = '".$tipoChaves."'
                limit 1";
	    $query = $this->db->query($sql);
	    $retorno = $query->result_array();

		if($retorno){
			return $retorno[0];
		}else{
			return false;
		}
	    
	}
	
	public function getStoresData($id = ""){
	    
	    $sql = "select *
                from stores 
                where active in (1,3)";
	    
	    if($id <> ""){
	        $sql .= " and id = $id";
	    }

		$sql .= " order by name";
	    
	    $query = $this->db->query($sql);
	    return $query->result_array();
	    
	}

	public function getStoresDataRelatorio($id = ""){
	    
	    $sql = "select S.*
                from stores S
				inner join iugu_subconta IUGU on IUGU.store_id = S.id
                where 1=1";
	    
	    if($id <> ""){
	        $sql .= " and S.id = $id";
	    }

		$sql .= " order by S.name";
	    
	    $query = $this->db->query($sql);
	    return $query->result_array();
	    
	}
	
	public function getSplitStatusData(){
	    
	    $sql = "select *
                from billet_status
                where tipo_status = 'Status Split' and ativo = 1
                order by id";
	    
	    $query = $this->db->query($sql);
	    return $query->result_array();
	    
	}
	
	public function getBilletStatusData($tipoBoleto = null){
	    
	    if($tipoBoleto == null){
	        $tipo = "Status Boleto";
	    }else{
	        $tipo = $tipoBoleto;
	    }
	    
	    $sql = "select *
                from billet_status
                where tipo_status = '$tipo' and ativo = 1
                order by id";
	    
	    $query = $this->db->query($sql);
	    return $query->result_array();
	    
	}
	
	public function logoIUGUExecucao( $httpcodeLog, $erronoLog, $errmsgLog, $contentLog, $store, $billet ){
	    
	    if($errmsgLog == ""){
	        $errmsgLog = " ";
	    }
	    
	    $data['httpcode']  = $httpcodeLog;
	    $data['erro']      = $erronoLog;
	    $data['errmsg']    = $errmsgLog;
	    $data['content']   = $contentLog;
	    $data['store_id']  = $store;
	    $data['billet_id']  = $billet;
	    
	    $insert = $this->db->insert('iugu_log', $data);
	    $order_id = $this->db->insert_id();
	    return ($order_id) ? $order_id : false;
	    
	}
	
	public function salvaBoletoIUGU( $params, $array_response){
	    
	    $data['valor_total']       = $params['txt_valor']/100;
	    $data['status_id']         = "16";
	    $data['status_iugu']       = "4";
	    $data['integrations_id']   = "0";
	    $data['stores_id']         = $params['slc_store'];
	    $data['id_boleto_iugu']    = $array_response['id'];
	    $data['url_boleto_iugu']   = $array_response['secure_url'];
 	    $data['status_split']      = "8";
	    $data['data_vencimento']   = $params['txt_dt_vencimento'];
	    $data['tipo_pagamento']   = $params['txt_forma_pagamento'];
	    $insert = $this->db->insert('billet', $data);
	    $order_id = $this->db->insert_id();
	    return ($order_id) ? $order_id : false;
	    
	}
	
	public function getBilletsData($id = null, $inputs = null)
	{
		
		$sql = 'select 
                		b.id,
                		case when i.name is null then concat(s.name,concat(" - ",s.raz_social)) else i.name end as marketplace,
                		b.id_boleto_iugu,
                        b.url_boleto_iugu,
                		date_format(b.data, "%d/%m/%Y") as data_geracao,
                        date_format(b.data_vencimento, "%d/%m/%Y") as data_vencimento,
                		b.valor_total,
                		sb.nome as status_billet,
                		si.nome as status_iugu,
                        sp.nome as status_split,
                		b.status_iugu as status_iugu_id,
                        b.status_split as status_split_id,
                		CONCAT(CONCAT(CONCAT(IFNULL(s.name,"Cliente externo"),CONCAT(" - ",IFNULL(s.raz_social,"Cliente externo"),CONCAT(" - ",IFNULL(CNPJ,"Cliente externo")))),CONCAT(" - ", b.id_boleto_iugu)),CONCAT(" - R$ ",b.valor_total)) AS store_nome,
                        s.responsible_email as email,
                        date_format(b.data_pagamento, "%d/%m/%Y") as data_pagamento,
                        b.stores_id,
                        s.raz_social, 
                        s.CNPJ,
                        s.name as Loja,
                        s.responsible_name, 
                        s.phone_1
                from billet b
                inner join billet_status sb on sb.id = b.status_id
                inner join billet_status si on si.id = b.status_iugu
                inner join billet_status sp on sp.id = b.status_split
                left join integrations i on i.id = b.integrations_id
                left join stores s on s.id = b.stores_id
                where 1=1';
		
		if($id <> ""){
		    $sql .= " and b.id = $id ";
		}
		
		if($inputs <> null){
		    
		    if($inputs['txt_data_inicio'] <> ""){
		        $sql .= " and b.data >= '".$inputs['txt_data_inicio']." 00:00:00' ";
		    }
		    
		    if($inputs['txt_data_fim'] <> ""){
		        $sql .= " and b.data <= '".$inputs['txt_data_fim']." 00:00:00' ";
		    }
		    
		    if($inputs['txt_data_pagamento_inicio'] <> ""){
		        $sql .= " and b.data_pagamento >= '".$inputs['txt_data_pagamento_inicio']." 00:00:00' ";
		    }
		    
		    if($inputs['txt_data_pagamento_fim'] <> ""){
		        $sql .= " and b.data_pagamento <= '".$inputs['txt_data_pagamento_fim']." 00:00:00' ";
		    }
		    
		    if($inputs['slc_status_ciclo'] <> ""){
		        $sql .= " and b.status_id = ".$inputs['slc_status_ciclo'];
		    }
		    
		}
		
        $sql .= " order by b.id ";
        
		$query = $this->db->query($sql);
		return $query->result_array();
	}
	
	function getStatusIuguWs($tipoChaves = "Produção", $id, $idBilletId = null){
	    
	    //Busca as chaves no banco
	    $keys = $this->buscaChaveNoBanco();
	    
	    $headers = array(
	        'Content-Type: application/json',
	        'Authorization: Basic '.$keys['chave']
	    );
	    
	    $data = array(
	        'id' => $id
	    );
	    
	    $url = 'https://api.iugu.com/v1/invoices/'.$id;
	    
	    $curl = curl_init($url);
	    //curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
	    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	    //curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
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
	    
	    //busca dados boleto IUGU
	    
	    //Salvar Log da execução
	    $this->logoIUGUExecucao(    $httpcodeLog,
	        $erronoLog,
	        $errmsgLog,
	        $contentLog,
	        0,$idBilletId);
	    
	    
	    if($response_code == "200"){
	        if($array_response['status']){
	            $saida['status']   = $array_response['status'];
	            $saida['code']     = "0";
	        }else{
	            $saida['status']   = "Erro";
	            $saida['code']     = "1";
	        }
	        
	    } else {
	        $saida['status']   = "Erro";
	        $saida['code']     = "1";
	    }
	    
        return $saida;
	    
	}
	
	public function atualizaStatusBoletoTabela($id, $status){
	    
	    
	    //Busca Status Novo
	    $sql = "select billet_status_id, status_id from iugu_api_status_billet where status_iugu = '$status'";
	    
	    $query = $this->db->query($sql);
	    $saida = $query->result_array();
	    $idStatus = $saida[0]['billet_status_id'];
	    $idStatusBol = $saida[0]['status_id'];
	    
	    $data['status_iugu'] = $idStatus;
	    $data['status_id'] = $idStatusBol;
	    
	    if($idStatus == "5"){
	        $data['data_pagamento'] = date('Y-m-d H:m:s');
	    }
	    
	    $this->db->where('id_boleto_iugu', $id);
	    return $update = $this->db->update('billet', $data);
	    
	}
	
	function getCancellBilletIuguWs($tipoChaves = "Produção", $id){
	    
	    //Busca as chaves no banco
	    $keys = $this->buscaChaveNoBanco();
	    
	    $headers = array(
	        'Content-Type: application/json',
	        'Authorization: Basic '.$keys['chave']
	    );
	    
	    $data = array(
	        'id' => $id
	    );
	    
		//$url = 'https://api.iugu.com/v1/invoices/'.$id.'';
	    $url = 'https://api.iugu.com/v1/invoices/'.$id.'/cancel';
	    
	    $curl = curl_init($url);
	    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
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
	    
	    //Salvar Log da execução
	    $this->logoIUGUExecucao(    $httpcodeLog,
	        $erronoLog,
	        $errmsgLog,
	        $contentLog,
	        0,0);
	    
	    
	    if($response_code == "200"){
	        if($array_response['status']){
	            $saida['status']   = $array_response['status'];
	            $saida['code']     = "0";
	            $saida['saida']    = $array_response;
	        }else{
	            $saida['status']   = "Erro";
	            $saida['code']     = "1";
	            $saida['saida']    = $array_response;
	        }
	        
	    } else {
	        $saida['status']   = "Erro";
	        $saida['code']     = "1";
	        $saida['saida']    = "";
	    }
	    //Salvar Log da execução
	    $this->logoIUGUExecucao(    $httpcodeLog,
	        $erronoLog,
	        $errmsgLog,
	        $contentLog,
	        $id,0);
	    
	    if($response_code == "200"){
	        //Salva as informações do Boleto
	        $this->alteraStatusBoletoIUGU( $id);
	        return "0;Boleto Cancelado com sucesso!<br>Id: ".$array_response['id'];
	    } else {
	        $saida = "";
	        foreach ($array_response as $resposta){
	            if( is_array($resposta)){
	                
    	            if($saida == ""){
    	                $saida = key($resposta).": ";
    	            }else{
    	                $saida .= "<br>".key($resposta).": ";
    	            }
    	            foreach($resposta as $mensagem){
    	                $i = 0;
    	                foreach($mensagem as $final){
    	                    if($i == "0"){
    	                        $saida .= $final;
    	                    }else{
    	                        $saida .= ", ".$final;
    	                    }
    	                    $i++;
    	                }
    	            }
	            }else{
	                
	                if($saida == ""){
	                    $saida = $resposta.": ";
	                }else{
	                    $saida .= "<br>".key($resposta).": ";
	                }
	                
	            }
	        }
	        return "1;Erro ao cancelar o boleto:<br>".$saida;
	    }
	    
	    
	    
	}
	
	public function getStatusBilletIuguWsData($status = "")
	{
	    
	    $sql = "select *
                from iugu_api_status_billet";
	    
	    if($status <> ""){
	        $sql .= " where status_iugu = '$status'";
	    }
	    
	    $sql .= " order by status_iugu";
	    $query = $this->db->query($sql);
	    return $query->result_array();
	}
	
	function alteraStatusBoletoIUGU($id){
	    
	    $data['status_iugu'] = 9;
	    
	    $this->db->where('id_boleto_iugu', $id);
	    $update = $this->db->update('billet', $data);
	    return $id;
	    
	}
	
	function createsubcontaiugu(){
	    
	    //Busca as chaves no banco
	    $keys = $this->buscaChaveNoBanco();
	    
	    if($params['slc_billet'] == "Outros"){
	        $desc = $params['slc_billet']." - ".$params['txt_outros'].": ".$params['txt_desc_item'];
	    }else{
	        $desc = $params['slc_billet'].": ".$params['txt_desc_item'];
	    }
	    
	    $params['txt_valor'] = $params['txt_valor']*100;
	    $params['txt_cpf_cnpj'] = str_replace(".","",$params['txt_cpf_cnpj']);
	    $params['txt_cpf_cnpj'] = str_replace("/","",$params['txt_cpf_cnpj']);
	    
	    $headers = array(
	        'Content-Type: application/json',
	        'Authorization: Basic '.$keys['chave']
	    );
	    
	    $data = array(
	        'payer' => array(
	            'name' => $params['txt_nome'],
	            'cpf_cnpj' => $params['txt_cpf_cnpj'],
	            'address' => array(
	                'zip_code' => $params['txt_cep'],
	                'number' => $params['txt_numero']
	            ),
	        ),
	        'items' => array(
	            array('description' => $desc, 'quantity' => '1', 'price_cents' => $params['txt_valor'])
	        ),
	        'email' => $params['txt_email'],
	        'cc_emails' => 'andrerisi@conectala.com.br;fernandoschumacher@conectala.com.br;filippemey@conectala.com.br;ricardoschaffer@conectala.com.br',
	        'due_date' => $params['txt_dt_vencimento']
	    );
	    
	    $url = 'https://api.iugu.com/v1/invoices';
	    
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
	    
	    //Salvar Log da execução
	    $this->logoIUGUExecucao(    $httpcodeLog,
	        $erronoLog,
	        $errmsgLog,
	        $contentLog,
	        $params['slc_store'],0);
	    
	    if($response_code == "200"){
	        //Salva as informações do Boleto
	        $this->salvaBoletoIUGU( $params, $array_response);
	        return "0;Boleto Gerado com sucesso!<br>Id: ".$array_response['id']."<br>URL: ".$array_response['secure_url'];
	    } else {
	        $saida = "";
	        foreach ($array_response as $resposta){
	            if($saida == ""){
	                $saida = key($resposta).": ";
	            }else{
	                $saida .= "<br>".key($resposta).": ";
	            }
	            foreach($resposta as $mensagem){
	                $i = 0;
	                foreach($mensagem as $final){
	                    if($i == "0"){
	                        $saida .= $final;
	                    }else{
	                        $saida .= ", ".$final;
	                    }
	                    $i++;
	                }
	            }
	        }
	        
	        return "1;Erro na geração do boleto:<br>".$saida;
	    }
	    
	}
	
	public function criaSubconta($params , $tipoChaves = "Produção"){
	    
	    //Busca as chaves no banco
	    $keys = $this->buscaChaveNoBanco();
	    
	    $retorno = $this->buscaDadosCadastroSubconta($params);
	    
	    if (!is_array($retorno)){
	        return "1;Erro na criação da Subconta: <br>"."Dados não encontrados";
	    }
	    
	    
	    $headers = array(
	        'Content-Type: application/json',
	        'Authorization: Basic '.$keys['chave']
	    );
	    
	    $data = array(
	        'name' => $retorno['raz_social'],
	        'commissions' => array(
	            'cents' => 0,
	            'credit_card_cents' => 0,
	            'bank_slip_cents' => 0
	        )
	    );
	    
	    $url = 'https://api.iugu.com/v1/marketplace/create_account';
	    
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
	    if($params['store_id'] <> ""){
	        $id = $params['store_id'];
	    }else{
	        $id = $params['providers_id'];
	    }
	    
	    //Salvar Log da execução
	    $this->logoIUGUExecucao(    $httpcodeLog,
	        $erronoLog,
	        $errmsgLog,
	        $contentLog,
	        $id,0);
	    
	    if($response_code == "200"){
	        //Salva as informações do Boleto
	        $idSubonta = $this->salvaSubcontaIUGU( $params, $array_response);
	        $this->salvaLogSubcontaIUGU( $idSubonta, "200", "Subconta criada com sucesso!");
	        return "0;Subconta criada com sucesso!<br>Id: ".$array_response['account_id']."<br>Nome: ".$array_response['name'];
	    } else {
	        $saida = "";
	        foreach ($array_response as $resposta){
	            
    	        if( is_array($resposta)){
    	            
    	            if($saida == ""){
    	                $saida = key($resposta).": ";
    	            }else{
    	                $saida .= "<br>".key($resposta).": ";
    	            }
    	            foreach($resposta as $mensagem){
    	                if( is_array($mensagem)){
    	                    $i = 0;
    	                    foreach($mensagem as $final){
    	                        if($i == "0"){
    	                            $saida .= $final;
    	                        }else{
    	                            $saida .= ", ".$final;
    	                        }
    	                        $i++;
    	                    }
    	                }else{
    	                    if($saida == ""){
    	                        $saida = $mensagem.": ";
    	                    }else{
    	                        $saida .= "<br>".$mensagem."<br> ";
    	                    }
    	                }
    	            }
    	        }else{
    	            
    	            if($saida == ""){
    	                $saida = $resposta.": ";
    	            }else{
    	                $saida .= "<br>".$resposta.": ";
    	            }
    	            
    	        }
	        
	        }
	        
	        return "1;Erro na criação da Subconta: <br>".$saida;
	    }
	    
	}
	
	public function validarSubconta($params , $tipoChaves = "Produção"){
	    
	    //Busca as chaves no banco
	    $keys = $this->buscaChaveNoBanco();
	    
	    $retorno = $this->buscaDadosCadastroSubconta($params);
	    $retorno2 = $this->buscaDadosValidacaoSubconta($params);
	    
	    if (!is_array($retorno) or !is_array($retorno2)){
	        return "1;Erro na validação da Subconta: <br>"."Dados não encontrados";
	    }
	    
	    $headers = array(
	        'Content-Type: application/json',
	        'cache-control: no-cache',
	        'Authorization: Basic '. base64_encode($retorno2['user_token'].':')
	    );
	   
	    if($retorno['id'] == "37"){
	        $cnpj_cpf = str_replace(".","","040.168.519-55");
	        $cnpj_cpf = str_replace("/","",$cnpj_cpf);
	        $cnpj_cpf = str_replace("-","",$cnpj_cpf);
	        
	    }elseif($retorno['id'] == "159"){
	        $cnpj_cpf = str_replace(".","","062.249.789-80");
	        $cnpj_cpf = str_replace("/","",$cnpj_cpf);
	        $cnpj_cpf = str_replace("-","",$cnpj_cpf);
	        
	    }else{
	        $cnpj_cpf = str_replace(".","",$retorno['CNPJ']);
	        $cnpj_cpf = str_replace("/","",$cnpj_cpf);
	        $cnpj_cpf = str_replace("-","",$cnpj_cpf);
	    }
	    
	    if (strlen($cnpj_cpf) == "11"){
	        $tipoPessoa    = "Pessoa Física";
	        $cnpj          = "";
	        $cpf           = $cnpj_cpf;
	        $nomeCpf       = $retorno['raz_social'];
	        $nomeCnpj      = "";
	        $resp_name     = "";
	        $resp_cpf      = $cnpj_cpf;
	    }else{
	        $tipoPessoa    = "Pessoa Jurídica";
	        $cnpj          = $cnpj_cpf;
	        $cpf           = "";
	        $nomeCpf       = "";
	        $nomeCnpj      = $retorno['raz_social'];
	        $resp_name     = $retorno['responsible_name'];
	        $resp_cpf      = "";
	    }
	    
	    if($retorno['account_type'] == "Conta Corrente"){
	        $tipo_conta = "Corrente";
	    }else{
	        $tipo_conta = "Poupança";
	    }
	    
	    $data = array(
	        'data' => array(
	            'price_range' => 'Até R$ 100,00',
	            'physical_products' => 'false',
	            'business_type' => 'Lojista e-commerce',
	            'person_type' => $tipoPessoa,
	            'automatic_transfer' => 'false',
	            'cnpj' => $cnpj,
	            'cpf' => $cpf,
	            'company_name' => $nomeCnpj,
	            'name' => $nomeCpf,
	            'address' => $retorno['address'],
	            'cep' => $retorno['zipcode'],
	            'city' => $retorno['addr_city'],
	            'state' => $retorno['addr_uf'],
	            'telephone' => $retorno['phone_1'],
	            'resp_name' => $resp_name,
	            'resp_cpf' => $resp_cpf,
	            'bank' => $retorno['bank'],
	            'bank_ag' => $retorno['agency'],
	            'account_type' => $tipo_conta,
	            'bank_cc' => $retorno['account'])
	    );
	    
	    $url = 'https://api.iugu.com/v1/accounts/'.$retorno2['account_id'].'/request_verification';
	    
	    $curl = curl_init( $url );
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
	    
	    if($params['store_id'] <> ""){
	        $id = $params['store_id'];
	    }else{
	        $id = $params['providers_id'];
	    }
	    
	    //Salvar Log da execução
	    $this->logoIUGUExecucao(    $httpcodeLog,
	        $erronoLog,
	        $errmsgLog,
	        $contentLog,
	        $retorno2['account_id'],0);
	    
	    if($response_code == "200"){
	        //Salva as informações do Boleto
	        $idSubconta = $this->salvaValidacaoSubcontaIUGU( $retorno2, $array_response);
	        $this->salvaLogSubcontaIUGU( $retorno2['id'], "200", "Subconta submetida a validação com sucesso!");
	        return "0;Subconta submetida a validação com sucesso!<br>Id: ".$array_response['account_id']."<br>Nome: ".$array_response['name'];
	    } else {
	        $saida = "";
	        foreach ($array_response as $resposta){
	            
	            if( is_array($resposta)){
	                
	                if($saida == ""){
	                    $saida = key($resposta).": ";
	                }else{
	                    $saida .= "<br>".key($resposta).": ";
	                }
	                foreach($resposta as $mensagem){
	                    if( is_array($mensagem)){
	                        $i = 0;
	                        foreach($mensagem as $final){
	                            if($i == "0"){
	                                $saida .= $final;
	                            }else{
	                                $saida .= ", ".$final;
	                            }
	                            $i++;
	                        }
	                    }else{
	                        if($saida == ""){
	                            $saida = $mensagem.": ";
	                        }else{
	                            $saida .= "<br>".$mensagem."<br> ";
	                        }
	                    }
	                }
	            }else{
	                
	                if($saida == ""){
	                    $saida = $resposta.": ";
	                }else{
	                    $saida .= "<br>".$resposta.": ";
	                }
	                
	            }
	            
	        }
	        $this->salvaLogSubcontaIUGU( $retorno2['id'], $response_code, $saida);
	        return "1;Erro em submeter a validação Subconta: <br>".$saida;
	    }
	    
	}
	
	function buscaDadosCadastroSubconta($params){
	    
	    if($params['store_id'] <> ""){
	        $sql = "select * from stores where id = ".$params['store_id'];
	    }else{
	        $sql = "select p.*, razao_social as raz_social, p.phone AS phone_1 from providers p where id = ".$params['providers_id'];
	    }
	    
	    $query = $this->db->query($sql);
	    $saida = $query->result_array();
	    
	    if(!empty ($saida)){
    	    return $saida[0];
	    }else{
	        return false;
	    }
	    
	    
	}
	
	public function buscaDadosValidacaoSubconta($params){
	    
	    if($params['store_id'] <> ""){
	        $sql = "select * from iugu_subconta where store_id = ".$params['store_id'];
	    }else{
	        $sql = "select * from iugu_subconta where providers_id = ".$params['providers_id'];
	    }
	    
	    $query = $this->db->query($sql);
	    $saida = $query->result_array();

	    if(!empty ($saida)){
	        return $saida[0];
	    }else{
	        return false;
	    }
	    
	    
	}
	
	function salvaSubcontaIUGU( $params, $array_response){
	    
	    $data['account_id']        = $array_response['account_id'];
	    $data['name']              = $array_response['name'];
	    $data['live_api_token']    = $array_response['live_api_token'];
	    $data['test_api_token']    = $array_response['test_api_token'];
	    $data['user_token']        = $array_response['user_token'];
	    $data['store_id']          = $params['store_id'];
	    $data['providers_id']      = $params['providers_id'];
	    $data['ativo']             = 10;
	    
	    $insert = $this->db->insert('iugu_subconta', $data);
	    $order_id = $this->db->insert_id();
	    return ($order_id) ? $order_id : false;
	    
	}
	
	function salvaValidacaoSubcontaIUGU( $params, $array_response){
	    
	    $data = array(
	        'ativo'                => 11,
	        'verifica_conta_id'    => $array_response['ID']
	    );
	    
	    $this->db->where('account_id', $id);
	    $update = $this->db->update('iugu_subconta', $data);
	    
	    return true;
	    
	}
	
	public function atualizaSubconta($params , $tipoChaves = "Produção"){
	    
	    //Busca as chaves no banco
	    $keys = $this->buscaChaveNoBanco();
	    
	    $retorno = $this->buscaDadosSubconta($params);
	    
	    if (!is_array($retorno)){
	        return "1;Erro na criação da Subconta: <br>"."Dados não encontrados";
	    }
	    
	    
	    $headers = array(
	        'Content-Type: application/json',
	        'Authorization: Basic '.$keys['chave']
	    );
	    
	    $data = array(
	        'name' => $retorno['raz_social'],
	        'commissions' => array(
	            'cents' => 0,
	            'credit_card_cents' => 0,
	            'bank_slip_cents' => 0
	        )
	    );
	    
	    $url = 'https://api.iugu.com/v1/marketplace/create_account';
	    
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
	    if($params['store_id'] <> ""){
	        $id = $params['store_id'];
	    }else{
	        $id = $params['providers_id'];
	    }
	    
	    //Salvar Log da execução
	    $this->logoIUGUExecucao(    $httpcodeLog,
	        $erronoLog,
	        $errmsgLog,
	        $contentLog,
	        $id,0);
	    print_r($array_response);
	    if($response_code == "200"){
	        //Salva as informações do Boleto
	        $this->salvaSubcontaIUGU( $params, $array_response);
	        return "0;Subconta criada com sucesso!<br>Id: ".$array_response['account_id']."<br>Nome: ".$array_response['name'];
	    } else {
	        $saida = "";
	        foreach ($array_response as $resposta){
	            
	            if( is_array($resposta)){
	                
	                if($saida == ""){
	                    $saida = key($resposta).": ";
	                }else{
	                    $saida .= "<br>".key($resposta).": ";
	                }
	                foreach($resposta as $mensagem){
	                    if( is_array($mensagem)){
	                        $i = 0;
	                        foreach($mensagem as $final){
	                            if($i == "0"){
	                                $saida .= $final;
	                            }else{
	                                $saida .= ", ".$final;
	                            }
	                            $i++;
	                        }
	                    }else{
	                        if($saida == ""){
	                            $saida = $mensagem.": ";
	                        }else{
	                            $saida .= "<br>".$mensagem."<br> ";
	                        }
	                    }
	                }
	            }else{
	                
	                if($saida == ""){
	                    $saida = $resposta.": ";
	                }else{
	                    $saida .= "<br>".$resposta.": ";
	                }
	                
	            }
	            
	        }
	        
	        return "1;Erro na criação da Subconta: <br>".$saida;
	    }
	    
	}
	
	public function buscadadostabelasubconta($id = null){
	    
	    $sql = "select * from iugu_subconta";
	    
	    if($id <> ""){
	        $sql .= " where id = $id";
	        
	    }
	    $sql .= " order by id";
	    
	    $query = $this->db->query($sql);
	    return $query->result_array();
	    
	}

	public function getSubAccountByStoreId(int $storeId): ?array
    {

	    $sql = "SELECT * FROM iugu_subconta WHERE store_id = ? LIMIT 1";

	    $query = $this->db->query($sql, [$storeId]);

	    return $query ? $query->row_array() : [];

	}

	public function buscadadostabelasubconta2($id = null){
	    
	    $sql = "select igs.*, S.name as nome_loja from iugu_subconta igs inner join stores S on S.id = igs.store_id";
	    
	    if($id <> ""){
	        $sql .= " where igs.id = $id";
	        
	    }
	    $sql .= " order by S.name";
	    
	    $query = $this->db->query($sql);
	    return $query->result_array();
	    
	}

	public function buscadadostabelasubconta3($dados){
	    
	    $sql = "select 	is2.account_id,
						is2.live_api_token,
						s.id,
						s.bank ,
						b.`number` ,
						s.agency ,
						s.account ,
						is2.ativo,
						account_type,
						case when upper(account_type) like upper('%Corrente%') then 'cc' else 'cp' end as tipo_conta
				from stores s
				inner join iugu_subconta is2 on s.id = is2.store_id 
				inner join banks b on b.name = s.bank 
				where is2.ativo <> 0 ";
	    
		if(array_key_exists('store_id',$dados)){
	        $sql .= " and s.id = ".$dados['store_id']; 
	    }

		if(array_key_exists('job',$dados)){
			$sql .= " and s.date_update >= DATE_ADD(now(), INTERVAL -".$dados['job']." hour)"; 
		}

	    $sql .= " order by s.name";
	    
	    $query = $this->db->query($sql);
	    return $query->result_array();
	    
	}
	
	public function buscadadostabelasubcontaextrato($filtros){
	    
	    $sql = "select * from iugu_subconta ";
	    
	    if( $filtros['store_id'] > 0 ){
	        $sql .= " where store_id = ".$filtros['store_id']."";
	    }elseif( $filtros['providers_id'] > 0 ){
	        $sql .= " where store_id = ".$filtros['providers_id']."";
	    }else{
	        $sql .= " where store_id = ".$filtros['company_id']."";
	    }
	    
	    $sql .= " order by id";
	    
	    $query = $this->db->query($sql);
	    $saida = $query->result_array();
	    
	    if($saida){
	        return $saida[0];
	    }else{
	        return "";
	    }
	    
	    
	    
	}
	
	public function atualizastatussubcontatabelaWS($subconta , $tipoChaves = "Produção"){
	    
	    //Busca as chaves no banco
	    $keys = $this->buscaChaveNoBanco();
	    
	    $headers = array(
	        'Content-Type: application/json',
	        'cache-control: no-cache',
	        'Authorization: Basic '. base64_encode($subconta['live_api_token'].':')
	    );
	    
	    $url = 'https://api.iugu.com/v1/accounts/'.$subconta['account_id'];

	    $curl = curl_init( $url );
	    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
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
	    
	    //Salvar Log da execução
	    $this->logoIUGUExecucao(    $httpcodeLog,
	        $erronoLog,
	        $errmsgLog,
	        $contentLog,
	        $subconta['account_id'],0);
		// 	    echo '<pre>';print_r($array_response);die;
	    if($response_code == "200"){
	        //Salva as informações do Boleto
	        $this->mudastatusSubcontaiuguTabela( $subconta, $array_response);
	        if ($array_response['is_verified?'] == "1"){
    	        return "0;Subconta ativada com sucesso!<br>Id: ".$subconta['account_id']."<br>Status verificado: ".$array_response['is_verified?'];
	        }else{
	            return "1;Subconta ainda não ativada!<br>Id: ".$subconta['account_id']."<br>Status verificado: ".$array_response['is_verified?'];
	        }
	    } else {
	        $saida = "";
	        foreach ($array_response as $resposta){
	            
	            if( is_array($resposta)){
	                
	                if($saida == ""){
	                    $saida = key($resposta).": ";
	                }else{
	                    $saida .= "<br>".key($resposta).": ";
	                }
	                foreach($resposta as $mensagem){
	                    if( is_array($mensagem)){
	                        $i = 0;
	                        foreach($mensagem as $final){
	                            if($i == "0"){
	                                $saida .= $final;
	                            }else{
	                                $saida .= ", ".$final;
	                            }
	                            $i++;
	                        }
	                    }else{
	                        if($saida == ""){
	                            $saida = $mensagem.": ";
	                        }else{
	                            $saida .= "<br>".$mensagem."<br> ";
	                        }
	                    }
	                }
	            }else{
	                
	                if($saida == ""){
	                    $saida = $resposta.": ";
	                }else{
	                    $saida .= "<br>".$resposta.": ";
	                }
	                
	            }
	            
	        }
	        
	        return "1;Erro verificar validação Subconta Id: ".$subconta['account_id']." <br>".$saida;
	    }
	    
	}
	
	function mudastatusSubcontaiuguTabela($account_id, $array_response){
	    
	    /*if ($array_response['is_verified?'] == "1"){
	        $data['ativo'] = '12';
	    }else{
	        $data['ativo'] = '11';
        }*/
        
        if ($array_response['balance_available_for_withdraw'] == "R$ 0,00"){
            $data['saldo_disponivel'] = '00.00';
        }else{
            $data['saldo_disponivel'] = str_replace(",",".",str_replace("R$ ","",$array_response['balance_available_for_withdraw']));
        }
	    
        $this->db->where('account_id', $account_id['account_id']);
        $update = $this->db->update('iugu_subconta', $data);
	    
	}
	
	public function solicitapedidodesaqueWS($subconta , $tipoChaves = "Produção"){
	    
	    //Busca as chaves no banco
	    $keys = $this->buscaChaveNoBanco();
	    
	    $headers = array(
	        'Content-Type: application/json',
	        'cache-control: no-cache',
	        'Authorization: Basic '. base64_encode($subconta['live_api_token'].':')
	    );
	    
	    $url = 'https://api.iugu.com/v1/accounts/'.$subconta['account_id'].'/request_withdraw';
	    
	    $data = array(
	        'amount' => $subconta['saldo_disponivel']
	    );
	    
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
	    
	    //Salvar Log da execução
	    $this->logoIUGUExecucao(    $httpcodeLog,
	        $erronoLog,
	        $errmsgLog,
	        $contentLog,
	        $subconta['account_id'],0);
	    
	    if($response_code == "200"){
	        //Salva as informações do Boleto
	        $this->mudastatusSubcontaiuguTabela( $subconta, $array_response);
	        return "0;Sucesso ao solicitar transferencia de Subconta!<br>Id: ".$subconta['account_id']."<br>Status verificado: ".$array_response['is_verified?'];
	    } else {
	        $saida = "";
	        foreach ($array_response as $resposta){
	            
	            if( is_array($resposta)){
	                
	                if($saida == ""){
	                    $saida = key($resposta).": ";
	                }else{
	                    $saida .= "<br>".key($resposta).": ";
	                }
	                foreach($resposta as $mensagem){
	                    if( is_array($mensagem)){
	                        $i = 0;
	                        foreach($mensagem as $final){
	                            if($i == "0"){
	                                $saida .= $final;
	                            }else{
	                                $saida .= ", ".$final;
	                            }
	                            $i++;
	                        }
	                    }else{
	                        if($saida == ""){
	                            $saida = $mensagem.": ";
	                        }else{
	                            $saida .= "<br>".$mensagem."<br> ";
	                        }
	                    }
	                }
	            }else{
	                
	                if($saida == ""){
	                    $saida = $resposta.": ";
	                }else{
	                    $saida .= "<br>".$resposta.": ";
	                }
	                
	            }
	            
	        }
	        
	        return "1;Erro ao solicitar transferencia de Subconta: <br>".$saida;
	    }
	    
	}
	
	public function gerasplitsubcontaiugu($subconta , $tipoChaves = "Produção"){

	    //Busca as chaves no banco
	    $keys = $this->buscaChaveNoBanco();
	    
	    $headers = array(
	        'Content-Type: application/json',
	        'Authorization: Basic '.$keys['chave']
	    );
	    
	    $data = array(
	        'receiver_id' => $subconta['account_id'],
	        'amount_cents'  => $subconta['valor_split']
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
	    
	    $id = $subconta['id'];
	    
	    //Salvar Log da execução
	    $this->logoIUGUExecucao(    $httpcodeLog,
	        $erronoLog,
	        $errmsgLog,
	        $contentLog,
	        $id,0);
	    
	    if($response_code == "200"){
	        //Salva as informações do Boleto
            $this->salvaSplitSubcontaIUGU( $subconta, $array_response);
	        return "0;Split para a subconta realizado com sucesso!<br>Id: ".$subconta['account_id']."<br>Valor: ".$subconta['valor_split'];
	    } else {
	        $saida = "";
	        foreach ($array_response as $resposta){
	            
	            if( is_array($resposta)){
	                
	                if($saida == ""){
	                    $saida = key($resposta).": ";
	                }else{
	                    $saida .= "<br>".key($resposta).": ";
	                }
	                foreach($resposta as $mensagem){
	                    if( is_array($mensagem)){
	                        $i = 0;
	                        foreach($mensagem as $final){
	                            if($i == "0"){
	                                $saida .= $final;
	                            }else{
	                                $saida .= ", ".$final;
	                            }
	                            $i++;
	                        }
	                    }else{
	                        if($saida == ""){
	                            $saida = $mensagem.": ";
	                        }else{
	                            $saida .= "<br>".$mensagem."<br> ";
	                        }
	                    }
	                }
	            }else{
	                
	                if($saida == ""){
	                    $saida = $resposta.": ";
	                }else{
	                    $saida .= "<br>".$resposta.": ";
	                }
	                
	            }
	            
	        }
	        
	        return "1;<br>Erro no split para a subconta ".$subconta['name'].": <br>".$saida;
	    }
	    
	}
	
	public function salvaSplitSubcontaIUGU($params, $array_response){
	    
	    $data['iugu_subconta_id']  = $params['id'];
	    $data['billet_id']         = $params['billet_id'];
	    $data['valor']             = $params['valor_split']/100;
	    
	    $insert = $this->db->insert('iugu_subconta_split', $data);
	    $order_id = $this->db->insert_id();
	    return ($order_id) ? $order_id : false;
	    
	}
	
	public function buscadadosfinanceirosiuguWS($tipoChaves = "Produção"){
	    
	    //Busca as chaves no banco
	    $keys = $this->buscaChaveNoBanco();

		if(!$keys){
			return false;
		}
	    
	    $dadosFiltro['store_id'] = 0;
	    $dadosFiltro['providers_id'] = 0;
	    $dadosFiltro['company_id'] = 0;
	    
	    if( $this->session->userdata('usercomp') == "1" ){
	        //Administrador de tudo, Conecta Lá
	        $headers = array(
	            'Content-Type: application/json',
	            'cache-control: no-cache',
	            'Authorization: Basic '. $keys['chave']
	        );
	        
	        $url = 'https://api.iugu.com/v1/accounts/'.$keys['account_id'];
	        
	        $account = $keys['account_id'];
	    }else{
	        
	        if( $this->session->userdata('userstore') > "0" ){
    	        // Dados apenas da Loja
	            $dadosFiltro['store_id'] = $this->session->userdata('userstore');
	            $subconta = $this->buscadadostabelasubcontaextrato($dadosFiltro);
	        }else{
	           // Dados de todas as lojas que a empresa comanda
	            $dadosFiltro['store_id'] = $this->session->userdata('usercomp');
	            $subconta = $this->buscadadostabelasubcontaextrato($dadosFiltro);
	        }

	        if(!$subconta){
	            $retorno[0] = 1;
	            return $retorno;
	        }
	        
	        $headers = array(
	            'Content-Type: application/json',
	            'cache-control: no-cache',
	            'Authorization: Basic '. base64_encode($subconta['live_api_token'].':')
	        );
	        $url = 'https://api.iugu.com/v1/accounts/'.$subconta['account_id'];
	        
	        $account = $subconta['account_id'];
	        
	    }	
	    
	    $curl = curl_init( $url );
	    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
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
	    
	    //Salvar Log da execução
	    $this->logoIUGUExecucao(    $httpcodeLog,
	        $erronoLog,
	        $errmsgLog,
	        $contentLog,
	        $account,0);
	    
		// 	    echo '<pre>';print_r($array_response);die;
	     	    
	    if($response_code == "200"){
	        $retorno[0] = 0;
	        $retorno[1] = $array_response;
	        return $retorno;
	    } else {
	        $saida = "";
	        if (isset($array_response)) foreach ($array_response as $resposta){
	            
	            if( is_array($resposta)){
	                
	                if($saida == ""){
	                    $saida = key($resposta).": ";
	                }else{
	                    $saida .= "<br>".key($resposta).": ";
	                }
	                foreach($resposta as $mensagem){
	                    if( is_array($mensagem)){
	                        $i = 0;
	                        foreach($mensagem as $final){
	                            if($i == "0"){
	                                $saida .= $final;
	                            }else{
	                                $saida .= ", ".$final;
	                            }
	                            $i++;
	                        }
	                    }else{
	                        if($saida == ""){
	                            $saida = $mensagem.": ";
	                        }else{
	                            $saida .= "<br>".$mensagem."<br> ";
	                        }
	                    }
	                }
	            }else{
	                
	                if($saida == ""){
	                    $saida = $resposta.": ";
	                }else{
	                    $saida .= "<br>".$resposta.": ";
	                }
	                
	            }
	            
	        }
	        
	        $retorno[0] = 1;
	        $retorno[1]['erroChamada'] = $saida;
	    }
	    
	}
	
	public function statuspedido($status){

		$nomestatus = "application_order_".$status;
	    return $this->lang->line($nomestatus);

//	    dd($status);
	    /*if($status == 1) {  // Não Pago
	        return "Aguardando Nota Fiscal";
	    }
	    elseif($status == 2) {  // NOVO e Pago  - NÂO DEVE OCORRER
	        return "Aguardando Nota Fiscal";
	    }
	    elseif($status == 3) {  // Em Andamento - Aguardando faturamento (ACABOU DE CHEGAR DO BING)
	        return "Aguardando Nota Fiscal";
	    }
	    elseif($status == 4) {  // Aguardando Coleta
	        return "Aguardando Postagem";
	    }
	    elseif($status == 5) {  // Enviado
	        return "Aguardando Entrega";
	    }
	    elseif($status == 6) {  // Entregue
	        return "Entregue";
	    }
	    
	    elseif($status == 40) {  // Aguardando Rastreio - Externo
	        return "Aguardando Postagem";
	    }
	    
	    elseif($status == 43) {  // Aguardando Coleta/Envio - Externo
	        return "Aguardando Postagem";
	    }
	    
	    elseif($status == 45) {  // Em Transporte
	        return "Aguardando Entrega";
	    }
	    
	    elseif($status == 50) {  // Nota Fiscal Registrada - Contratar o Frete.
	        return "Aguardando Postagem";
	    }
	    elseif($status == 51) {  // Frete Contratado - Mandar para o Bling
	        return "Aguardando Postagem";
	    }
	    elseif($status == 52) {  // Rastreio no Bling - Mandar NF para o Bling
	        return "Aguardando Nota Fiscal";
	    }
	    elseif($status == 53) {  // Tudo ok. Agora é com o Rastreio do frete
	        return "Aguardando Entrega";
	    }
	    elseif($status == 54) {  // Igual a 50 só que veio sem cotacao e fez cotacao manual
	        return "Aguardando Postagem";
	    }elseif($status == 55) {  // Pedido foi enviado mas precisa de intervenção manual no Marketplace para ser informado que foi enviado
	        return "Aguardando Entrega";
	    }
	    elseif($status == 56) { // Processando nfe aguardando envio para tiny
	        return "Aguardando Nota Fiscal";
	    }
	    elseif($status == 57) { // Problema para faturar o pedido
	        return "Aguardando Nota Fiscal";
	    }
	    elseif($status == 60) {  // Pedido foi Entregue mas precisa de intervenção manual no Marketplace para ser informado que foi entregue
	        return "Entregue";
	    }
	    
	    elseif($status == 95) {  // Cancelado Pelo Seller
	        return "Cancelado";
	    }

        elseif($status == 96) {  // Cancelado Pré
            return "Cancelado";
        }
	    
	    elseif($status == 97) {  // Devolvido // Cancelado por nós - NAO IMPLEMENTADO
	        return "Cancelado";
	    }
	    elseif($status == 98) {  // Cancelar no Marketplace
	        return "Cancelado";
	    }
	    elseif($status == 99) {  // Em Cancelamento - status para cancelar no Bling (BlingCancelar)
	        return "Cancelado";
	    }
	    elseif($status == 101) {  // Sem cotação de frete - deve ter falhado a consulta frete e precisa ser feita manualmente
	        return "Aguardando Postagem";
	    }
	    
	    elseif($status == "101b") {  // Sem cotação de frete - deve ter falhado a consulta frete e precisa ser feita manualmente
	        return "Aguardando Postagem";
	    }
	    
	    else{
	        return false;
	    }*/
	    
	}

	public function alterasaldodisponivel($id,$saldo_disponivel){
	    
	    $data = array(
	        'saldo_disponivel' => $saldo_disponivel
	    );
	    
	    $this->db->where('id', $id);
	    return $this->db->update('iugu_subconta', $data);
	    
	}
	
	/*****************************************************************/
	
	
	public function getMktPlacesData(){
	    
	    $sql = "select distinct min(INTG.id) as id, INTG.name as mkt_place
                from integrations INTG
                group by INTG.name
                ORDER BY INTG.name ";
	    $query = $this->db->query($sql);
	    return $query->result_array();
	    
	}
	
	public function getOrdersData($data = null){
	    
	    $sql = "select distinct
                    	o.id as id,
                		o.origin as mktplace,
                        o.bill_no as num_pedido,
                        o.date_time as data_pedido,
                        o.paid_status as status,
                        o.gross_amount as valor,
                        o.data_entrege
                from orders o
                left join integrations i on i.name = o.origin
                where 1=1 ";
	    
	    if($data['mktPlace'] <> ""){
	        $sql .= " and i.id = ".$data['mktPlace'];
	    }
	    
	    if($data['dtInicio'] <> "0"){
	        $sql .= " and o.date_time >= '".$data['dtInicio']."'";
	    }
	    
	    if($data['dtFim'] <> "0"){
	        $sql .= " and o.date_time <= '".$data['dtFim']."'";
	    }
	    
	    if($data['retirados'] <> "0"){
	        $sql .= " and o.id not in (".$data['retirados'].")";
	    }
	    
	    $sql .= " ORDER BY o.id ";
	    
	    $query = $this->db->query($sql);
	    return $query->result_array();
	    
	}
	
	public function getOrdersAddedData($data = null){

	    $sql = "select distinct
                    	o.id as id,
                		o.origin as mktplace,
                        o.bill_no as num_pedido,
                        o.gross_amount as valor
                from orders o
                where 1=1 ";
	    
	    if($data['idOrders'] <> ""){
	        $sql .= " and o.id in (".$data['idOrders'].")";
	    }
	    
	    $sql .= " ORDER BY o.id ";
	    
	    $query = $this->db->query($sql);
	    return $query->result_array();
	    
	}
	
	public function getSumOrdersAdded($idOrders){
	    
	    $sql = "select sum(o.gross_amount) as valor
                from orders o
                where  o.id in (".$idOrders.")";
	    
	    $query = $this->db->query($sql);
	    return $query->result_array();
	    
	}
	
	public function insertBillet($inputs){
	    
	    $valor = explode(" ", $inputs['txt_valor_total']);
	    $valor = $valor[1];
	    
	    $data['valor_total']       = $valor;
	    $data['status_id']         = 1;
	    $data['status_iugu']       = 2;
	    $data['integrations_id']   = $inputs['slc_mktplace'];
	    
	    $insert = $this->db->insert('billet', $data);
	    $order_id = $this->db->insert_id();
	    return ($order_id) ? $order_id : false;
	    
	}
	
	public function insertBilletOrder($ArrayBilletOrder){
	    
	    $insert = $this->db->insert('billet_order', $ArrayBilletOrder);
	    $order_id = $this->db->insert_id();
	    return ($order_id) ? $order_id : false;
	    
	}
	
	public function gerarBoletoIUGU($id){
	    
	    $retorno['ret']        = true;
	    $retorno['num_billet'] = "";
	    $retorno['url']        = "";
	    
	    return $retorno;
	    
	}
	
	public function atualizaStatus($id,$statusBoleto, $statusIUGU, $numBoleto,$url){
	    
	    $data = array(
	        'status_id' => $statusBoleto,
	        'status_iugu' => $statusIUGU,
	        'id_boleto_iugu' => $numBoleto,
	        'url_boleto_iugu' => $url
	    );
	    
	    $this->db->where('id', $id);
	    $update = $this->db->update('billet', $data);
	    // SW - Log Update
	    $data['id'] = $id;
	    get_instance()->log_data('billet','update',json_encode($data),"I");
	    
	    return true;
	    
	    
	}
	
	public function buscaLojasSemSubconta(){
	    
	    $sql = "SELECT * FROM stores S
                WHERE id NOT IN 
                (SELECT store_id FROM iugu_subconta IUGU 
                WHERE IUGU.store_id <> 0 AND IFNULL(IUGU.company_id,'0') = 0 AND IFNULL(providers_id,'0') = 0) AND active in (1,5)";
	    
	    $query = $this->db->query($sql);
	    return $query->result_array();
	    
	}
	
	public function buscaFornecedoresSemSubconta(){
	    
	    $sql = "SELECT * FROM providers P
                WHERE id NOT IN 
                (SELECT providers_id FROM iugu_subconta IUGU 
                WHERE IUGU.store_id = 0 AND IUGU.company_id = 0 AND providers_id <> 0) AND active = 1";
	    
	    $query = $this->db->query($sql);
	    return $query->result_array();
	    
	}
	
	public function buscaFornecedoresSemValidacao(){
	    
	    $sql = "SELECT P.* FROM iugu_subconta IUGU
                INNER JOIN providers P ON P.id = IUGU.providers_id
                WHERE ativo = 10";
	    
	    $query = $this->db->query($sql);
	    return $query->result_array();
	    
	}
	
	public function buscaLojasSemValidacao(){
	    
	    $sql = "SELECT S.* FROM iugu_subconta IUGU
                INNER JOIN stores S ON S.id = IUGU.store_id
                WHERE ativo = 10";
	    
	    $query = $this->db->query($sql);
	    return $query->result_array();
	    
	}
	
	public function subcontastatuslog($inputs){
	    
	    $sql = "SELECT 
                	IUGUS.id,
                	S.name AS loja,
                	P.name AS fornecedor,
                	BS.nome AS statuss,
                	IUGUL.log,
                	IUGUL.data_log,
                	IUGUL.retorno
                FROM iugu_subconta IUGUS
                LEFT JOIN iugu_subconta_log IUGUL ON IUGUL.iugu_subconta_id = IUGUS.id
                LEFT JOIN stores S ON S.id = IUGUS.store_id
                LEFT JOIN providers P ON P.id = IUGUS.providers_id
                LEFT JOIN billet_status BS ON BS.id = IUGUS.ativo
                WHERE 1=1 ";
	    
	    if(isset($inputs['slc_transportadora']) && $inputs['slc_transportadora']){
	        $sql .= " and P.id = ".$inputs['slc_transportadora'];
	    }
	    
	    if(isset($inputs['slc_store']) && $inputs['slc_store']){
	        $sql .= " and S.id = ".$inputs['slc_store'];
	    }

	    if(isset($inputs['iugu_subconta_id']) && $inputs['iugu_subconta_id']){
	        $sql .= " AND IUGUL.iugu_subconta_id = ".$inputs['iugu_subconta_id'];
	    }

	    $sql .= " ORDER BY IUGUS.id";

	    $query = $this->db->query($sql);
	    return $query->result_array();
	    
	    
	    
	}
	
	public function salvaLogSubcontaIUGU( $idSubonta, $retorno, $log){
	    
	    $data['iugu_subconta_id']  = $idSubonta;
	    $data['retorno']           = $retorno;
	    $data['log']               = $log;
	    
	    $insert = $this->db->insert('iugu_subconta_log', $data);
	    $order_id = $this->db->insert_id();
	    return ($order_id) ? $order_id : false;
	    
	}
	
	public function geraestornosplitsubcontaiugu($subconta , $tipoChaves = "Produção"){
	    
	    //Busca as chaves no banco
	    $keys = $this->buscaChaveNoBanco();
	    
	    $headers = array(
	        'Content-Type: application/json',
	        'Authorization: Basic '.$keys['chave']
	    );
	    
	    $data = array(
	        'receiver_id' => $subconta['account_id'],
	        'amount_cents'  => $subconta['valor_split']
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
	    
	    $id = $subconta['id'];
	    
	    //Salvar Log da execução
	    $this->logoIUGUExecucao(    $httpcodeLog,
	        $erronoLog,
	        $errmsgLog,
	        $contentLog,
	        $id,0);
	    
	    if($response_code == "200"){
	        //Salva as informações do Boleto
	        $this->salvaSplitSubcontaIUGU( $subconta, $array_response);
	        return "0;Split para a subconta realizado com sucesso!<br>Id: ".$subconta['account_id']."<br>Valor: ".$subconta['valor_split'];
	    } else {
	        $saida = "";
	        foreach ($array_response as $resposta){
	            
	            if( is_array($resposta)){
	                
	                if($saida == ""){
	                    $saida = key($resposta).": ";
	                }else{
	                    $saida .= "<br>".key($resposta).": ";
	                }
	                foreach($resposta as $mensagem){
	                    if( is_array($mensagem)){
	                        $i = 0;
	                        foreach($mensagem as $final){
	                            if($i == "0"){
	                                $saida .= $final;
	                            }else{
	                                $saida .= ", ".$final;
	                            }
	                            $i++;
	                        }
	                    }else{
	                        if($saida == ""){
	                            $saida = $mensagem.": ";
	                        }else{
	                            $saida .= "<br>".$mensagem."<br> ";
	                        }
	                    }
	                }
	            }else{
	                
	                if($saida == ""){
	                    $saida = $resposta.": ";
	                }else{
	                    $saida .= "<br>".$resposta.": ";
	                }
	                
	            }
	            
	        }
	        
	        return "1;<br>Erro no split para a subconta ".$subconta['name'].": <br>".$saida;
	    }
	    
	}

	public function alteravalordecontaagencia($dados){
	    
	    //Busca as chaves no banco
	    $keys = $this->model_iugu->buscaChaveNoBanco();
	    
	    // COLOCAR O LIVE API TOKEN
	    $headers = array(
	        'Content-Type: application/json',
	      //  'Authorization: Basic '.base64_encode("F50DF6F74A6FC5238CD4C79079BBBCB3A7E8CDB7DAD6BF3D6F05208C6EE89FB0".':')
		  	'Authorization: Basic '.base64_encode($dados['live_api_key'].':')
	    );
	    
	   /* $data = array(
            'agency' => '0101-5',
	        'account' => '00009605878-0',
            'account_type' => 'cc',
            'bank' => '085'
        ); */

		$data = array(
            'agency' => $dados['agencia'],
	        'account' => $dados['conta'],
            'account_type' => $dados['tipo_conta'],
            'bank' => $dados['banco'],
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
	    
	    return $array_response;
	    
	}
	 
	
	
	 /**************************************************/
	
	
	
	public function criaSubcontaullNine($params , $tipoChaves = "Produção", $nomeNovo = null){
	    
	    //Busca as chaves no banco
	    $keys = $this->buscaChaveNoBanco();
	    
	    $retorno = $this->buscaDadosCadastroSubconta($params);
	    
	    if (!is_array($retorno)){
	        return "1;Erro na criação da Subconta: <br>"."Dados não encontrados";
	    }
	    
	    if($nomeNovo <> null){
	        $nome = $nomeNovo;
	    }else{
	        $nome = $retorno['raz_social'];
	    }
	    
	    $headers = array(
	        'Content-Type: application/json',
	        'Authorization: Basic '.$keys['chave']
	    );
	    
	    $data = array(
	        'name' => $nome,
	        'commissions' => array(
	            'cents' => 0,
	            'credit_card_cents' => 0,
	            'bank_slip_cents' => 0
	        )
	    );
	    
	    $url = 'https://api.iugu.com/v1/marketplace/create_account';
	    
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
	    if($params['store_id'] <> ""){
	        $id = $params['store_id'];
	    }else{
	        $id = $params['providers_id'];
	    }
	    
	    //Salvar Log da execução
	    $this->logoIUGUExecucao(    $httpcodeLog,
	        $erronoLog,
	        $errmsgLog,
	        $contentLog,
	        $id,0);
	    
	    if($response_code == "200"){
	        //Salva as informações do Boleto
	        $idSubonta = $this->salvaSubcontaIUGU( $params, $array_response);
	        $this->salvaLogSubcontaIUGU( $idSubonta, "200", "Subconta criada com sucesso!");
	        return "0;Subconta criada com sucesso!<br>Id: ".$array_response['account_id']."<br>Nome: ".$array_response['name'];
	    } else {
	        $saida = "";
	        foreach ($array_response as $resposta){
	            
	            if( is_array($resposta)){
	                
	                if($saida == ""){
	                    $saida = key($resposta).": ";
	                }else{
	                    $saida .= "<br>".key($resposta).": ";
	                }
	                foreach($resposta as $mensagem){
	                    if( is_array($mensagem)){
	                        $i = 0;
	                        foreach($mensagem as $final){
	                            if($i == "0"){
	                                $saida .= $final;
	                            }else{
	                                $saida .= ", ".$final;
	                            }
	                            $i++;
	                        }
	                    }else{
	                        if($saida == ""){
	                            $saida = $mensagem.": ";
	                        }else{
	                            $saida .= "<br>".$mensagem."<br> ";
	                        }
	                    }
	                }
	            }else{
	                
	                if($saida == ""){
	                    $saida = $resposta.": ";
	                }else{
	                    $saida .= "<br>".$resposta.": ";
	                }
	                
	            }
	            
	        }
	        
	        return "1;Erro na criação da Subconta: <br>".$saida;
	    }
	    
	}
	
	public function validarSubcontaNine($params , $tipoChaves = "Produção", $nomeNovo = null){
	    
	    //Busca as chaves no banco
	    $keys = $this->buscaChaveNoBanco();
	    
	    $retorno = $this->buscaDadosCadastroSubconta($params);
	    $retorno2 = $this->buscaDadosValidacaoSubcontaFullNine($params);
	    echo '<Pre>';print_r($retorno2);
	    if (!is_array($retorno) or !is_array($retorno2)){
	        return "1;Erro na validação da Subconta: <br>"."Dados não encontrados";
	    }
	    
	    if($nomeNovo <> null){
	        $nome = $nomeNovo;
	    }else{
	        $nome = $retorno['raz_social'];
	    }
	    
	    $headers = array(
	        'Content-Type: application/json',
	        'cache-control: no-cache',
	        'Authorization: Basic '. base64_encode($retorno2['user_token'].':')
	    );
	    
	    $cnpj_cpf = str_replace(".","",$retorno['CNPJ']);
	    $cnpj_cpf = str_replace("/","",$cnpj_cpf);
	    $cnpj_cpf = str_replace("-","",$cnpj_cpf);
	    
	    if (strlen($cnpj_cpf) == "11"){
	        $tipoPessoa    = "Pessoa Física";
	        $cnpj          = "";
	        $cpf           = $cnpj_cpf;
	        $nomeCpf       = $nome;
	        $nomeCnpj      = "";
	        $resp_name     = "";
	        $resp_cpf      = $cnpj_cpf;
	    }else{
	        $tipoPessoa    = "Pessoa Jurídica";
	        $cnpj          = $cnpj_cpf;
	        $cpf           = "";
	        $nomeCpf       = "";
	        $nomeCnpj      = $nome;
	        $resp_name     = $retorno['responsible_name'];
	        $resp_cpf      = "";
	    }
	    
	    if($retorno['account_type'] == "Conta Corrente"){
	        $tipo_conta = "Corrente";
	    }else{
	        $tipo_conta = "Poupança";
	    }
	    
	    $data = array(
	        'data' => array(
	            'price_range' => 'Até R$ 100,00',
	            'physical_products' => 'false',
	            'business_type' => 'Lojista e-commerce',
	            'person_type' => $tipoPessoa,
	            'automatic_transfer' => 'false',
	            'cnpj' => $cnpj,
	            'cpf' => $cpf,
	            'company_name' => $nomeCnpj,
	            'name' => $nomeCpf,
	            'address' => $retorno['address'],
	            'cep' => $retorno['zipcode'],
	            'city' => $retorno['addr_city'],
	            'state' => $retorno['addr_uf'],
	            'telephone' => $retorno['phone_1'],
	            'resp_name' => $resp_name,
	            'resp_cpf' => $resp_cpf,
	            'bank' => $retorno['bank'],
	            'bank_ag' => $retorno['agency'],
	            'account_type' => $tipo_conta,
	            'bank_cc' => $retorno['account'])
	    );
	    
	    $url = 'https://api.iugu.com/v1/accounts/'.$retorno2['account_id'].'/request_verification';
	    
	    $curl = curl_init( $url );
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
	    
	    if($params['store_id'] <> ""){
	        $id = $params['store_id'];
	    }else{
	        $id = $params['providers_id'];
	    }
	    
	    //Salvar Log da execução
	    $this->logoIUGUExecucao(    $httpcodeLog,
	        $erronoLog,
	        $errmsgLog,
	        $contentLog,
	        $retorno2['account_id'],0);
	    
	    if($response_code == "200"){
	        //Salva as informações do Boleto
	        $idSubconta = $this->salvaValidacaoSubcontaIUGU( $retorno2, $array_response);
	        $this->salvaLogSubcontaIUGU( $retorno2['id'], "200", "Subconta submetida a validação com sucesso!");
	        return "0;Subconta submetida a validação com sucesso!<br>Id: ".$array_response['account_id']."<br>Nome: ".$array_response['name'];
	    } else {
	        $saida = "";
	        foreach ($array_response as $resposta){
	            
	            if( is_array($resposta)){
	                
	                if($saida == ""){
	                    $saida = key($resposta).": ";
	                }else{
	                    $saida .= "<br>".key($resposta).": ";
	                }
	                foreach($resposta as $mensagem){
	                    if( is_array($mensagem)){
	                        $i = 0;
	                        foreach($mensagem as $final){
	                            if($i == "0"){
	                                $saida .= $final;
	                            }else{
	                                $saida .= ", ".$final;
	                            }
	                            $i++;
	                        }
	                    }else{
	                        if($saida == ""){
	                            $saida = $mensagem.": ";
	                        }else{
	                            $saida .= "<br>".$mensagem."<br> ";
	                        }
	                    }
	                }
	            }else{
	                
	                if($saida == ""){
	                    $saida = $resposta.": ";
	                }else{
	                    $saida .= "<br>".$resposta.": ";
	                }
	                
	            }
	            
	        }
	        $this->salvaLogSubcontaIUGU( $retorno2['id'], $response_code, $saida);
	        return "1;Erro em submeter a validação Subconta: <br>".$saida;
	    }
	    
	}
	
	function buscaDadosValidacaoSubcontaFullNine($params){
	    
	    $sql = "select * from iugu_subconta where store_id = ".$params['store_id']." order by id desc";
	    
	    $query = $this->db->query($sql);
	    $saida = $query->result_array();
	    
	    if(!empty ($saida)){
	        return $saida[0];
	    }else{
	        return false;
	    }
	    
	    
	}
	
	public function validastatussubconta($store_id){
	    
	    // coloco os que estavam planejados como ativo os que já chegaram no dia
	    $sql = "UPDATE iugu_subconta SET ativo=12 WHERE store_id =$store_id AND ativo = 10";
	    $query = $this->db->query($sql);
	    
	}

	public function buscarelatoriosaqueiuguws($liveApi = null){

		if($liveApi == null){
			return false;
		}

		$array_response = array();

		// COLOCAR O LIVE API TOKEN (DA SUBCONTA AQUI)
	    //$liveApi = "14ff582f552a9164774dc13a7cb8752a";
        
	    $headers = array(
	        'Content-Type: application/json',
	        'Authorization: Basic '.base64_encode($liveApi.':')
	    );
	    
	    // COLOCAR O ID DA CONTA QUE VAI RECEBER O DINHEIRO
	    // COLOCAR O VALOR QUE VAI SER TRANSFERIDO
	    
	    $data = array(
	        'from' => '2020-01-01T00:00:00-03:00',
	        'to' => '',
	        'start' => '0',
	        'limit' => '1000'
	    );
	    
	    $url = 'https://api.iugu.com/v1/withdraw_conciliations';
	    
	    $curl = curl_init($url);
	    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
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

		if(array_key_exists('withdraw_requests',$array_response) ){
			return $array_response['withdraw_requests'];
		}else{
			return array();
		}

	}

	public function buscaLojasValidadasQueForamAtualizadas($store_id = null, $subconta_id = null){

		$where = "";

		if($store_id <> null){
			$where = "where S.id = $store_id";
		}

		if($subconta_id <> null){
			$where = "where IUGU.id = $subconta_id";
		}

		if($where == ""){
			$where = " WHERE S.date_update >= DATE_ADD(NOW(), INTERVAL -1 HOUR)";
		}

		$sql = "SELECT S.id, S.bank, S.agency, S.account, S.account_type, IUGU.live_api_token 
				FROM stores S 
				INNER JOIN iugu_subconta IUGU ON IUGU.store_id = S.id AND IUGU.ativo = 12 ";
		
		$sql .= $where;

		$query = $this->db->query($sql);
	    return $query->result_array();

	}
	
	public function getDataTransferencia($order_id){		        		
        $transferencia = $this->db->query('SELECT * FROM iugu_repasse WHERE order_id = '.$order_id);
		if($transferencia->num_rows() > 0){
			$data = $transferencia->row();			
			if($data->data_transferencia <> null){
				return date("d/m/Y H:i:s", strtotime($data->data_transferencia));
			}else{
				return "-";
			}
		}
		return "-";
	}

	public function getValorTransferencia($order_id){		        		
        $transferencia = $this->db->query('SELECT * FROM iugu_repasse WHERE order_id = '.$order_id);
		if($transferencia->num_rows() > 0){
			$data = $transferencia->row();			
			if($data->valor_parceiro <> null){
				$valor = number_format($data->valor_parceiro, 2, ",", ".");
				return "R$ ".$valor;
			}else{
				return "-";
			}
		}
		return "-";
	}


    //braun
    public function listPlans()
    {
        $sql = "select ip.*, concat(u.firstname, ' ', u.lastname) as user_name from iugu_plans ip left join users u on ip.user_id=u.id order by ip.plan_status asc, ip.plan_title asc";

        $query = $this->db->query($sql);

	    return $query->result_array();
    }


    //braun
    public function checkIfPlanExists($plan_title): bool
    {
        $sql = "select plan_title from iugu_plans where plan_title like '".$plan_title."'";

        $query =  $this->db->query($sql);
        return ($query->row_array()) ? true : false;
    }


    //braun
    public function insertNewPlan(array $new_plan)
    {
	    $this->db->insert('iugu_plans', $new_plan);

	    $plan_id = $this->db->insert_id();

	    return ($plan_id) ? $plan_id : false;
    }


    //braun
    public function editPlan(int $plan_id, array $edit_plan): bool
    {
        $sql = "update iugu_plans set iugu_plan_id = '".$edit_plan['id']."' where id = ".$plan_id;

        return ($this->db->query($sql)) ? true : false;
    }


    //braun
    public function togglePlanStatus(array $plan)
    {
        $sql = "select plan_status from iugu_plans where id=".$plan['plan_id'];

        $query = $this->db->query($sql);

        $current_status = $query->row_array()['plan_status'];

        if ($current_status == $plan['plan_status'])
        {
            $new_status = 1;

            if ($current_status == 1)
                $new_status = 2;

            $sql = "update iugu_plans set plan_status = ".$new_status." where id = ".$plan['plan_id'];
            $query = $this->db->query($sql);

            //braun hack
            //confirmar se ao ser desativado um plano tambem desativa as lojas associadas a ele, 
            //pois caso contrario, quando o plano for ativado novamente algumas lojas poderam estar ativas e mais de 1 plano
            //ja que elas ficam livres para serem associadas]
            if ($new_status == 2)
            {
                $sql = "update iugu_plan_stores set active = ".$new_status." where plan_id = ".$plan['plan_id'];
                $query = $this->db->query($sql);
            }
            
            if ($query)
                return ($new_status == 1) ? 'active' : 'inactive';
        }

        return 'ERROR';
    }


    //braun
    public function saveTransactionLog($iugu_log_array): bool
    {        
	    return ($this->db->insert('iugu_log', $iugu_log_array)) ? true : false;
    }

    
    //braun
    public function getPlanData(int $plan_id): ?array
    {
        $sql = "select iup.*, concat(u.firstname, ' ', u.lastname) AS username, DATE_FORMAT(iup.created_at, '%d/%m/%Y') AS date from iugu_plans iup left join users u on iup.user_id=u.id where iup.id=".$plan_id;
        
        $query = $this->db->query($sql);

        return $query->row_array();
    }


    //braun
    public function getAvailableStores(): ?array
    {
        $sql = "
                SELECT 
                    s.id AS store_id
                    ,s.name
                FROM 
                iugu_subconta isub 
                    INNER JOIN stores s ON isub.store_id = s.id
                    #LEFT JOIN  iugu_plan_stores ips ON ips.store_id = isub.store_id
                WHERE
                    isub.ativo > 0
                AND
                    isub.account_id IS NOT null  	
                AND	
                    isub.store_id NOT IN (
                    SELECT ips.store_id FROM iugu_plan_stores ips INNER JOIN iugu_plans ip ON ips.plan_id=ip.id
                    WHERE ips.active = 1 and ip.plan_status = 1
                )
                GROUP BY s.id
                ORDER BY s.name ASC
                ";

        $query = $this->db->query($sql);

        return $query->result_array();
    }


    //braun
    public function getCurrentStores(int $plan_id): ?array
    {
        $sql = "
                SELECT 
                    s.id
                    ,s.name
                    ,ips.date_plan_start
                    ,ips.active
                FROM
                    iugu_plan_stores ips
                    INNER JOIN iugu_plans ip ON ip.id=ips.plan_id
                    INNER JOIN stores s ON ips.store_id=s.id
                WHERE
                    ips.active = 1
                AND
                    ip.id = ".$plan_id."
            ";

        $query = $this->db->query($sql);

        return $query->result_array();
    }


    //braun
    public function addStoreToPlan(int $plan_id, int $store_id, $plan_date)
    {
        $sql            = "insert into iugu_plan_stores (plan_id, store_id, active, date_plan_start) values (".$plan_id.", ".$store_id.", 1, '".$plan_date."')";
        $this->db->query($sql);
        $new_plan       = $this->db->insert_id();

        if ($new_plan > 0)
        {
            $sql = "update iugu_plan_stores set active = 2 where plan_id = ".$plan_id." and store_id = ".$store_id." and id <> ".$new_plan;
            //esta remoção da relação entre planos e lojas deve ser acompanhada da remoção das assinaturas na lib
            
            if ($this->db->query($sql))
            {
                return $new_plan;
            }
        }

        return false;  
    }


    //braun
    public function toggleStoreInPlanStatus(int $plan_id, int $store_id)
    {
        $sql = "select id from iugu_plan_stores where plan_id=".$plan_id." and store_id=".$store_id." and active=1";
        $query = $this->db->query($sql);
        $toggle_id = $query->row_array()['id'];

        $this->db->query("update iugu_plan_stores set active=2 where plan_id = ".$plan_id." and store_id = ".$store_id);
        
        return $toggle_id;
    }


    //braun
    public function getPlansStoreFilters(): ?array
    {
        $sql = "
                SELECT s.name,ips.plan_id,ips.store_id FROM iugu_plan_stores ips
                INnER JOIN iugu_plans ip ON ips.plan_id=ip.id 
                InNER JOIN iugu_subconta isub ON ips.store_id=isub.store_id
                inner join stores s on isub.store_id=s.id
                WHERE ips.active = 1
                GROUP BY ips.id
        ";

        $query = $this->db->query($sql);
        return $query->result_array();
    }


    //braun
    public function getCurrentPlan($iugu_plan_stores_id)
    {
        $sql = "select plan_id from iugu_plan_stores where active = 1 and store_id = ".$iugu_plan_stores_id;       
        $query = $this->db->query($sql);

        return $query->row_array();
    }


    //braun
    public function getSubaccountData(int $store_id): ?array
    {
        $sql = "select * from iugu_subconta where store_id = ".$store_id." order by ativo desc limit 1";
        $query = $this->db->query($sql);

        return $query->row_array();
    }


    //braun
    public function editSubscription($subscription_id, $iugu_plan_stores_id)
    {
        $sql = "update iugu_plan_stores set subscription_id = '".$subscription_id."' where id = ".$iugu_plan_stores_id;

        return $this->db->query($sql);
    }


    //braun
    public function savePayload($invoice_created_array)
    {
        $insert = $this->db->insert('iugu_invoice_created', $invoice_created_array);
	    $order_id = $this->db->insert_id();
	    return ($order_id) ? $order_id : false;
    }


    //braun
    public function getSubscriptionData($toggle_id)
    {
        $sql = "select * from iugu_plan_stores where id = ".$toggle_id;
        $query = $this->db->query($sql);
        return $query->row_array();
    }


    //braun
    public function getSellerPlanData($subscription_id): ?array
    {
        $sql = "
                SELECT
                    ips.plan_id
                    ,ips.store_id
                    ,s.name
                FROM
                    iugu_plan_stores ips
                    INNER JOIN stores s ON ips.store_id = s.id
                WHERE
                    ips.subscription_id = ?
                ";
        $query = $this->db->query($sql, [$subscription_id]);

        return $query->row_array();
    }


    //braun
    public function saveBillingData($iugu_billing_array): bool
    {
	    return $this->db->insert('iugu_billing_history', $iugu_billing_array) ? true : false;
    }


    //braun
    public function getInitialBillingHistory()
    {
        $sql = "
                SELECT
                    s.name
                    ,ip.plan_title
                    ,ip.plan_type
                    ,ibh.amount
                    ,ibh.installments
                    ,ibh.status
                    ,ibh.created_at as billing_date
                    ,s.id as store_id
                FROM
                    iugu_billing_history ibh
                    INNER JOIN iugu_plans ip ON ip.id = ibh.plan_id
                    INNER JOIN stores s ON s.id = ibh.store_id
                    
                WHERE
                    ibh.id IN (
                        SELECT 
                            MAX(id) 
                        FROM
                            iugu_billing_history
                        WHERE
                            plan_id > 0
                        GROUP BY store_id
                    )
                    
                ORDER BY ip.plan_title asc
                ";
        //esta query traz somente 1 por loja

        $sql = "
                SELECT
                    s.name
                    ,ip.plan_title
                    ,ip.plan_type
                    ,ibh.amount
                    ,ibh.installments
                    ,ibh.status
                    ,ibh.created_at as billing_date
                    #,ibh.store_id
                    ,s.id as store_id
                FROM
                    iugu_billing_history ibh
                    INNER JOIN iugu_plans ip ON ip.id = ibh.plan_id
                    INNER JOIN stores s ON s.id = ibh.store_id	
                ORDER BY ibh.id desc, ip.plan_title asc
        ";

        $query = $this->db->query($sql);
        return $query->result_array();
    }

}