<?php 
/*
SW Serviços de Informática 2019
 
Model de Acesso ao BD para Recebimentos

*/  

class Model_troubleticket extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}
	
	public function cadastraChamado( $input ){
	    
	    
	    $data['integ_id']            = $input['slc_marketplace'];
	    $data['numero_chamado']      = $input['txt_numero_chamado'];
	    $data['billet_status_id']    = $input['slc_status'];
	    $data['descricao']           = $input['txt_descricao'];
	    $data['previsao_solucao']    = $input['txt_data_previsao'];
	    
	    $insert = $this->db->insert('chamado_marketplace', $data);
	    $order_id = $this->db->insert_id();
	    return ($order_id) ? $order_id : false;
	    
	}
	
	public function cadastraPedidos($input, $idChamado){
	    
	    $pedidos = "'".str_replace( ",", "','", $input['arraysplit'] )."'";
	    
	    $sql = "INSERT INTO chamado_marketplace_orders (order_id,numero_marketplace,chamado_marketplace_id)  
                SELECT id, numero_marketplace, '$idChamado' AS id FROM orders WHERE numero_marketplace IN ($pedidos)";
	    
	    return  $this->db->query($sql);
	    
	}
	
	public function getChamadossData($id = null, $inputs = null)
	{
	    
		$sql = "SELECT 	CM.id,
                    	SML.descloja,
                    	CM.numero_chamado,
                    	CM.data_criacao,
                    	CM.previsao_solucao,
                    	BS.nome AS `status`,
    	                CM.descricao,
                        CM.billet_status_id,
                        CM.integ_id,
                        DATE_FORMAT(CM.previsao_solucao,'%Y-%m-%d') as previsao_solucao_formatada
                FROM chamado_marketplace CM
                INNER JOIN stores_mkts_linked SML ON SML.id_mkt = CM.integ_id
                INNER JOIN billet_status BS ON BS.id = CM.billet_status_id
                WHERE 1=1";
		
		if($id <> ""){
		    $sql .= " and CM.id = $id ";
		}
		
		if($inputs <> null){
		    
		    if($inputs['txt_data_inicio'] <> ""){
		        $sql .= " and CM.data_criacao >= '".$inputs['txt_data_inicio']." 00:00:00' ";
		    }
		    
		    if($inputs['txt_data_fim'] <> ""){
		        $sql .= " and CM.data_criacao <= '".$inputs['txt_data_fim']." 23:59:59' ";
		    }
		    
		    if($inputs['txt_numero_chamado'] <> ""){
		        $sql .= " and CM.numero_chamado = '".$inputs['txt_numero_chamado']."'";
		    }
		    
		    if($inputs['slc_marketplace'] <> ""){
		        $sql .= " and CM.integ_id = ".$inputs['slc_marketplace'];
		    }
		    
		    if($inputs['slc_status_ciclo'] <> ""){
		        $sql .= " and CM.billet_status_id = ".$inputs['slc_status_ciclo'];
		    }
		    
		}
		
        $sql .= " order by CM.id ";
        //echo '<pre>'.$sql;die;
		$query = $this->db->query($sql);
		return $query->result_array();
	}
	
	public function getPedidosChamadosa($id = null)
	{
	    
	    $sql = 'SELECT 	   CMO.chamado_marketplace_id,
            	           GROUP_CONCAT(CMO.numero_marketplace) AS pedidos
                FROM chamado_marketplace_orders CMO
                WHERE ativo = 1';
	    
	    if($id <> ""){
	        $sql .= " and CMO.chamado_marketplace_id = $id ";
	    }
	    
	    $sql .= " group by CMO.chamado_marketplace_id ";
	    
	    $query = $this->db->query($sql);
	    return $query->result_array();
	}
	
	
	public function editaChamado($input){
	    
	    $data['integ_id']            = $input['slc_marketplace'];
	    $data['numero_chamado']      = $input['txt_numero_chamado'];
	    $data['billet_status_id']    = $input['slc_status'];
	    $data['descricao']           = $input['txt_descricao'];
	    $data['previsao_solucao']    = $input['txt_data_previsao'];
	    
	    $this->db->where('id', $input['hdnChamado']);
	    return $this->db->update('chamado_marketplace', $data);
	    
	}
	
	public function editaPedidos($input){
	    
	    $idChamado = $input['hdnChamado'];
	    $pedidos = "'".str_replace( ",", "','", $input['arraysplit'] )."'";
	    
	    //Desativa os pedidos que não existem mais na lista
	    $sql = "UPDATE chamado_marketplace_orders SET ativo = 0 WHERE chamado_marketplace_id = '$idChamado' AND numero_marketplace NOT IN ($pedidos)";
	    
	    $ret1 = $this->db->query($sql);
	    
	    if($ret1){
	        //Inclue os pedidos que entraram na lsita
	        $sql1 = "INSERT INTO chamado_marketplace_orders (order_id,numero_marketplace,chamado_marketplace_id)
	        SELECT id, numero_marketplace, '$idChamado' AS id FROM orders WHERE numero_marketplace IN
	        (SELECT numero_marketplace FROM orders WHERE numero_marketplace IN ($pedidos)
	         AND numero_marketplace NOT IN (SELECT numero_marketplace FROM chamado_marketplace_orders WHERE chamado_marketplace_id = '$idChamado' AND ativo = 1))";
	        
	        return $this->db->query($sql1);
	        
	    }else{
	        return false;
	    }
	    
	}
	
}