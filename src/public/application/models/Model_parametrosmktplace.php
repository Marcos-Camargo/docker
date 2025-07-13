<?php 
/*
SW Serviços de Informática 2019
 
Model de Acesso ao BD para Recebimentos

*/  

class Model_parametrosmktplace extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}
	
	/* get the orders data */
  	public function getReceivablesData($id = null)
	{
		if($id) {
			$sql = "select distinct MCI.id, INTG.descloja as mkt_place, MC.nome as categoria, MCI.data_inicio_vigencia, MCI.data_fim_vigencia, MCI.valor_aplicado, MCI.valor_aplicado_ml_free, MCI.mkt_categ_id, MCI.integ_id, INTG.apelido AS int_to  
                    from param_mkt_categ_integ MCI
                    inner join param_mkt_categ MC on MC.id = MCI.mkt_categ_id
                    inner join stores_mkts_linked INTG on INTG.id_mkt = MCI.integ_id
                    where MC.ativo = 1 and MCI.ativo = 1 and MCI.id = ?";
			$query = $this->db->query($sql, array($id));
			return $query->row_array();
		}

		$sql = "select distinct MCI.id, INTG.descloja as mkt_place, MC.nome as categoria, MCI.data_inicio_vigencia, MCI.data_fim_vigencia, MCI.valor_aplicado, MCI.valor_aplicado_ml_free, MCI.mkt_categ_id, MCI.integ_id, INTG.apelido AS int_to 
                from param_mkt_categ_integ MCI
                inner join param_mkt_categ MC on MC.id = MCI.mkt_categ_id
                inner join stores_mkts_linked INTG on INTG.id_mkt = MCI.integ_id
                where MC.ativo = 1 and MCI.ativo = 1
                ORDER BY MCI.id ";
		$query = $this->db->query($sql);
		return $query->result_array();
	}
	
	/* get all categs from database */
	public function getAllCategs(){
	    
	    $sql = "select * from (
                select distinct MC.id, MC.nome as categoria
                from param_mkt_categ_integ MCI
                inner join param_mkt_categ MC on MC.id = MCI.mkt_categ_id
                inner join stores_mkts_linked INTG on INTG.id_mkt = MCI.integ_id
                where MC.ativo = 1 and MCI.ativo = 1
                ORDER BY MC.nome) CATEG
                union
                select 0 as id, 'OUTROS' as categoria ";
	    $query = $this->db->query($sql);
	    return $query->result_array();
	    
	}
	
	
	/* get all mktplaces from database */
	public function getAllMktPlace(){
	    
	    /*$sql = "select distinct INTG.id_mkt as id, INTG.descloja as mkt_place
                from param_mkt_categ_integ MCI
                inner join param_mkt_categ MC on MC.id = MCI.mkt_categ_id
                inner join stores_mkts_linked INTG on INTG.id_mkt = MCI.integ_id
                where MC.ativo = 1 and MCI.ativo = 1
                ORDER BY INTG.descloja ";*/
	    $sql = "SELECT DISTINCT INTG.id_mkt AS id, INTG.descloja AS mkt_place
                FROM stores_mkts_linked INTG 
                LEFT JOIN param_mkt_categ_integ MCI ON INTG.id_mkt = MCI.integ_id
                LEFT JOIN param_mkt_categ MC ON MC.id = MCI.mkt_categ_id
                ORDER BY INTG.descloja ";
	    $query = $this->db->query($sql);
	    return $query->result_array();
	    
	}
		
	public function checkExiste($id_mktPlace, $id_Categ){
	    
	    $select = "select count(*) as qtd from param_mkt_categ_integ where integ_id = $id_mktPlace and mkt_categ_id = $id_Categ";
	    $query = $this->db->query($select);
	    $result = $query->row_array();
	    
	    if($result['qtd'] > 0){
	        return true;
	    }else{
	        return false;
	    }
	    
	}
	
	public function checkExisteOutros($id_mktPlace, $nome_categ){
	    
	    $select = "select count(*) as qtd 
                    from param_mkt_categ_integ MCI
                    inner join param_mkt_categ MC on MC.id = MCI.mkt_categ_id 
                    where MCI.integ_id = $id_mktPlace and trim(upper(MC.nome)) = trim(upper('$nome_categ'))";
	    $query = $this->db->query($select);
	    $result = $query->row_array();

	    if($result['qtd'] > 0){
	        return true;
	    }else{
	        return false;
	    }
	    
	}
	
	public function insertCategoriaOnly($categoria){
	    
	    $data['nome'] = trim($categoria);
	    
	    $insert = $this->db->insert('param_mkt_categ', $data);
	    $order_id = $this->db->insert_id();
	    return ($order_id) ? $order_id : false;
	    
	}

	public function insertCategoria($params)
	{
	    
	    //verifica se é outros para cadastrar a categoria antes
	    if($params['cmb_categoria'] == 0){
	        $id_categ = $this->insertCategoriaOnly($params['txt_categoria']);
	        if($id_categ == false){
	            return false;
	        }
	    }else{
	        $id_categ = $params['cmb_categoria'];
	    }
	    
	    if($params['txt_dt_inicio'] == ""){
	        $dataInicio = date('Y-m-d H:m:s');
	    }else{
	        if($params['txt_hra_inicio'] <> ""){
	            $dataInicio = $params['txt_dt_inicio']." ".$params['txt_hra_inicio'].":00";
	        }else{
	            $dataInicio = $params['txt_dt_inicio']." "."00:00:00";
	        }
	        
	    }
	    
	    if($params['txt_dt_fim'] == ""){
	        $dataFim = null;
	    }else{
	        if($params['txt_hra_fim'] <> ""){
	            $dataFim = $params['txt_dt_fim']." ".$params['txt_hra_fim'].":00";
	        }else{
	            $dataFim = $params['txt_dt_fim']." "."00:00:00";
	        }
	        
	    }
	    
    	$data = array(
    	    'data_inicio_vigencia' => $dataInicio,
    	    'data_fim_vigencia' => $dataFim,
    	    'mkt_categ_id' => $id_categ,
    	    'integ_id' => $params['cmb_mktplace'],
    	    'valor_aplicado' => $params['txt_valor_aplicado'],
    	    'valor_aplicado_ml_free' => $params['txt_valor_aplicado_2']
    	);
		// SW - Log Create
		get_instance()->log_data('param_mkt_categ_integ','create',json_encode($data),"I");

		$insert = $this->db->insert('param_mkt_categ_integ', $data);
		$order_id = $this->db->insert_id();

		$this->load->model('model_parametrosmktplace');

		return ($order_id) ? $order_id : false;
	}
	
	public function editPercentual($params){
        $agora =  date('Y-m-d H:m:s');
        
        if($params['txt_dt_inicio'] == ""){
            $dataInicio = date('Y-m-d H:m:s');
        }else{
            if($params['txt_hra_inicio'] <> ""){
                $dataInicio = $params['txt_dt_inicio']." ".$params['txt_hra_inicio'].":00";
            }else{
                $dataInicio = $params['txt_dt_inicio']." "."00:00:00";
            }
            
        }
        
        if($params['txt_dt_fim'] == ""){
            $dataFim = null;
        }else{
            if($params['txt_hra_fim'] <> ""){
                $dataFim = $params['txt_dt_fim']." ".$params['txt_hra_fim'].":00";
            }else{
                $dataFim = $params['txt_dt_fim']." "."00:00:00";
            }
            
        }
        
	    $data = array(
	        'data_inicio_vigencia' => $dataInicio,
	        'data_fim_vigencia' => $dataFim,
	        'valor_aplicado' => $params['txt_valor_aplicado'],
	        'valor_aplicado_ml_free' => $params['txt_valor_aplicado_2'],
	        'data_inclusao' => $agora
	    );

	    $this->db->where('id', $params['id']);
	    $update = $this->db->update('param_mkt_categ_integ', $data);
	    // SW - Log Update
	    $data['id'] = $params['id'];
	    get_instance()->log_data('param_mkt_categ_integ','update',json_encode($data),"I");
	    
	    return true;
	}

	public function remove($id)
	{
		if($id) {
			$this->db->where('id', $id);
			$delete = $this->db->delete('param_mkt_categ_integ');
			return ($delete == true) ? true : false;
		}
	}
	
	public function editFullCategoriasMktplace($params){
	    $agora =  date('Y-m-d H:m:s');
	    
	    if($params['txt_dt_inicio_edt'] == ""){
	        $dataInicio = date('Y-m-d H:m:s');
	    }else{
	        if($params['txt_hra_inicio_edt'] <> ""){
	            $dataInicio = $params['txt_dt_inicio_edt']." ".$params['txt_hra_inicio_edt'].":00";
	        }else{
	            $dataInicio = $params['txt_dt_inicio_edt']." "."00:00:00";
	        }
	        
	    }
	    
	    if($params['txt_dt_fim_edt'] == ""){
	        $dataFim = null;
	    }else{
	        if($params['txt_hra_fim_edt'] <> ""){
	            $dataFim = $params['txt_dt_fim_edt']." ".$params['txt_hra_fim_edt'].":00";
	        }else{
	            $dataFim = $params['txt_dt_fim_edt']." "."00:00:00";
	        }
	        
	    }

	    $data = array(
	        'data_inicio_vigencia' => $dataInicio,
	        'data_fim_vigencia' => $dataFim,
	        'valor_aplicado' => $params['txt_valor_aplicado_edt'],
	        'valor_aplicado_ml_free' => $params['txt_valor_aplicado_2_edt'],
	        'data_inclusao' => $agora
	    );
	    
	    $this->db->where('integ_id', $params['cmb_mktplace_edt']);
	    $update = $this->db->update('param_mkt_categ_integ', $data);
	    
	    return true;
	}
	
	/***************** FUNÇÕES DE CICLOS ******************/
    public function getReceivablesDataCicloByMarketplaceNameAndPaymentDay(string $marketplaceName, int $paymentDate): ?array
    {

        $sql = "SELECT PMC.id, 
                            PMC.integ_id, INTG.descloja AS mkt_place, 
                            PMC.data_inicio, 
                            PMC.data_fim , 
                            PMC.data_pagamento, 
                            PMC.data_pagamento_conecta, PMC.data_usada
                    FROM param_mkt_ciclo PMC
                    INNER JOIN stores_mkts_linked INTG ON INTG.id_mkt = PMC.integ_id 
                    where PMC.ativo = ? AND INTG.descloja = ? AND PMC.data_pagamento = ?";

        $query = $this->db->query($sql, [1, $marketplaceName, $paymentDate]);

        return $query->row_array();

    }

	public function getReceivablesDataCiclo($id = null, $integ_id = null, $integ_ids = null)
	{
	    if($id) {
	        $sql = "SELECT PMC.id, 
                            PMC.integ_id, INTG.descloja AS mkt_place, 
                            PMC.data_inicio, 
                            PMC.data_fim , 
                            PMC.data_pagamento, 
                            PMC.data_pagamento_conecta, PMC.data_usada
                    FROM param_mkt_ciclo PMC
                    INNER JOIN stores_mkts_linked INTG ON INTG.id_mkt = PMC.integ_id 
                    where PMC.ativo = 1 and PMC.id = ?
                    GROUP BY PMC.data_pagamento";
	        $query = $this->db->query($sql, array($id));
	        return $query->row_array();
	    }
	    
	    if($integ_id) {
	        $sql = "SELECT PMC.id, PMC.integ_id, INTG.descloja AS mkt_place, PMC.data_inicio, PMC.data_fim , PMC.data_pagamento, PMC.data_pagamento_conecta, PMC.data_usada
                    FROM param_mkt_ciclo PMC
                    INNER JOIN stores_mkts_linked INTG ON INTG.id_mkt = PMC.integ_id
                    where PMC.ativo = 1 and PMC.integ_id = ?";
	        $query = $this->db->query($sql, array($integ_id));
	        return $query->row_array();
	    }

        if (is_array($integ_ids) && count($integ_ids) > 0) {
            $this->db->select('PMC.id, PMC.integ_id, INTG.descloja AS mkt_place, 
                           PMC.data_inicio, PMC.data_fim, 
                           PMC.data_pagamento, PMC.data_pagamento_conecta, PMC.data_usada');
            $this->db->from('param_mkt_ciclo PMC');
            $this->db->join('stores_mkts_linked INTG', 'INTG.id_mkt = PMC.integ_id');
            $this->db->where('PMC.ativo', 1);
            $this->db->where_in('PMC.integ_id', $integ_ids);
            $this->db->order_by('PMC.id');
            $query = $this->db->get();
            return $query->result_array();
        }
	    
	    $sql = "SELECT PMC.id, PMC.integ_id, INTG.descloja AS mkt_place, PMC.data_inicio, PMC.data_fim , PMC.data_pagamento, PMC.data_pagamento_conecta, PMC.data_usada
                FROM param_mkt_ciclo PMC
                INNER JOIN stores_mkts_linked INTG ON INTG.id_mkt = PMC.integ_id 
                where PMC.ativo = 1 
                order by PMC.id";
	    $query = $this->db->query($sql);
	    return $query->result_array();
	}


    //braun
    public function getReceivablesDataCicloByMktplace($integ_id = null)
    {	    
        if($integ_id) 
        {
            $sql = "SELECT PMC.id, PMC.integ_id, INTG.descloja AS mkt_place, PMC.data_inicio, PMC.data_fim , PMC.data_pagamento, PMC.data_pagamento_conecta, PMC.data_usada
                    FROM param_mkt_ciclo PMC
                    INNER JOIN stores_mkts_linked INTG ON INTG.id_mkt = PMC.integ_id
                    where PMC.ativo = 1 and PMC.integ_id = ?";
            $query = $this->db->query($sql, array($integ_id));
            return (false !== $query) ? $query->result_array() : false;
        }
        
        return false;
    }

	
	public function checkExisteCiclo($params){
	    
	    $select = "select count(*) as qtd from param_mkt_ciclo where integ_id = ".$params['cmb_mktplace']." and data_inicio = '".$params['txt_data_inicio']."' and data_fim = ".$params['txt_data_fim']."";
	    $query = $this->db->query($select);
	    $result = $query->row_array();
	    
	    if($result['qtd'] > 0){
	        return true;
	    }else{
	        return false;
	    }
	    
	}
	
	public function removeciclo($id)
	{
	    if($id) {
	        $this->db->where('id', $id);
	        $delete = $this->db->delete('param_mkt_ciclo');

            //Deleting not paid itens to regenerate new payment date for orders
            $this->deletePreviousGeneratedConciliationData();

	        return ($delete == true) ? true : false;
	    }
	}
	
	public function insertciclo($params)
	{
	    
	    $data = array(
	        'integ_id' => $params['cmb_mktplace'],
	        'data_inicio' => $params['txt_data_inicio'],
	        'data_fim' => $params['txt_data_fim'],
	        'data_pagamento' => $params['txt_data_pagamento'],
	        'data_pagamento_conecta' => $params['txt_data_pagamento_conecta'],
			'data_usada' => $params['cmb_data_corte']
	    );
	    // SW - Log Create
	    get_instance()->log_data('param_mkt_ciclo','create',json_encode($data),"I");
	    
	    $insert = $this->db->insert('param_mkt_ciclo', $data);
	    $order_id = $this->db->insert_id();

        //Deleting not paid itens to regenerate new payment date for orders
        $this->deletePreviousGeneratedConciliationData();

	    return ($order_id) ? $order_id : false;
	}
	
	public function editCiclo($params){
	    $agora =  date('Y-m-d H:m:s');
	    
	    $data = array(
	        'data_inicio' => $params['txt_data_inicio'],
	        'data_fim' => $params['txt_data_fim'],
	        'data_pagamento' => $params['txt_data_pagamento'],
	        'data_pagamento_conecta' => $params['txt_data_pagamento_conecta'],
			'data_usada' => $params['cmb_data_corte'],
	        'data_alteracao' => $agora
	    );
	    
	    $this->db->where('id', $params['id']);
	    $update = $this->db->update('param_mkt_ciclo', $data);
	    // SW - Log Update
	    $data['id'] = $params['id'];
	    get_instance()->log_data('param_mkt_ciclo','update',json_encode($data),"I");

        //Deleting not paid itens to regenerate new payment date for orders
        $this->deletePreviousGeneratedConciliationData();
	    
	    return true;
	}

	/***************** FUNÇÕES DE CICLOS TRANSPORTADORAS ******************/
	
	public function getAllMktPlacetransp(){
	    
	    $sql = "SELECT * FROM providers P
                ORDER BY P.id ";
	    $query = $this->db->query($sql);
	    return $query->result_array();
	    
	}
	
	public function getReceivablesDataCiclotransp($id = null)
	{
	    if($id) {
	        $sql = "SELECT PMC.id, PMC.providers_id, INTG.name AS nome_transportadora, INTG.razao_social AS mkt_place, PMC.data_inicio, PMC.data_fim , PMC.data_pagamento, PMC.data_pagamento_conecta, PMC.tipo_ciclo, PMC.dia_semana
                    FROM param_mkt_ciclo_transp PMC
                    INNER JOIN providers INTG ON INTG.id = PMC.providers_id
                    where PMC.ativo = 1 and PMC.id = ?";
	        $query = $this->db->query($sql, array($id));
	        return $query->row_array();
	    }
	    
        $sql = "SELECT PMC.id, PMC.providers_id, INTG.name AS nome_transportadora, INTG.razao_social AS mkt_place, PMC.data_inicio, PMC.data_fim , PMC.data_pagamento, PMC.data_pagamento_conecta, PMC.tipo_ciclo, PMC.dia_semana
                FROM param_mkt_ciclo_transp PMC
                INNER JOIN providers INTG ON INTG.id = PMC.providers_id
                where PMC.ativo = 1
                order by PMC.id";
	    $query = $this->db->query($sql);
	    return $query->result_array();
	}
	
	public function checkExisteCiclotransp($params){
	    
	    $select = "select count(*) as qtd from param_mkt_ciclo_transp where providers_id = ".$params['cmb_mktplace']." and data_inicio = '".$params['txt_data_inicio']."' and data_fim = '".$params['txt_data_fim']."'";
	    $query = $this->db->query($select);
	    $result = $query->row_array();
	    
	    if($result['qtd'] > 0){
	        return true;
	    }else{
	        return false;
	    }
	    
	}

    private function deletePreviousGeneratedConciliationData()
    {
        //Deleting not paid itens to regenerate new payment date for orders
        $this->db->query('DELETE FROM `conciliacao_sellercenter` WHERE lote NOT IN (SELECT lote FROM conciliacao)');
        $this->db->query('DELETE FROM `orders_conciliation_installments` WHERE lote = "" OR lote IS NULL OR lote NOT IN (SELECT lote FROM conciliacao_sellercenter)');
        $this->db->query('DELETE FROM `orders_payment_date` WHERE order_id NOT IN (SELECT order_id FROM orders_conciliation_installments)');
    }
	
	public function removeciclotransp($id)
	{
	    if($id) {
	        $this->db->where('id', $id);
	        $delete = $this->db->delete('param_mkt_ciclo_transp');
            $this->deletePreviousGeneratedConciliationData();
	        return ($delete == true) ? true : false;
	    }
	}
	
	public function insertciclotransp($params)
	{
	    
	    $data = array(
	        'providers_id' => $params['cmb_mktplace'],
	        'tipo_ciclo' => $params['cmb_tp_ciclo'],
	        'dia_semana' => $params['cmb_week_day'],
	        'data_inicio' => $params['txt_data_inicio'],
	        'data_fim' => $params['txt_data_fim'],
	        'data_pagamento' => $params['txt_data_pagamento'],
	        'data_pagamento_conecta' => $params['txt_data_pagamento_conecta']
	    );
	    // SW - Log Create
	    get_instance()->log_data('param_mkt_ciclo_transp','create',json_encode($data),"I");
	    
	    $insert = $this->db->insert('param_mkt_ciclo_transp', $data);
	    $order_id = $this->db->insert_id();

        $this->deletePreviousGeneratedConciliationData();

	    return ($order_id) ? $order_id : false;
	}
	
	public function editCiclotransp($params){
	    $agora =  date('Y-m-d H:m:s');
	    
	    $data = array(
	        'tipo_ciclo' => $params['cmb_tp_ciclo'],
	        'dia_semana' => $params['cmb_week_day'],
	        'data_inicio' => $params['txt_data_inicio'],
	        'data_fim' => $params['txt_data_fim'],
	        'data_pagamento' => $params['txt_data_pagamento'],
	        'data_pagamento_conecta' => $params['txt_data_pagamento_conecta'],
	        'data_alteracao' => $agora
	    );
	    
	    $this->db->where('id', $params['id']);
	    $update = $this->db->update('param_mkt_ciclo_transp', $data);
	    // SW - Log Update
	    $data['id'] = $params['id'];
	    get_instance()->log_data('param_mkt_ciclo_transp','update',json_encode($data),"I");

        $this->deletePreviousGeneratedConciliationData();
	    
	    return true;
	}

    public function getValorAplicadoByProductIdIntTo(int $productId, string $intTo)
    {

        $sql = "SELECT param_mkt_categ_integ.*, stores_mkts_linked.apelido FROM `param_mkt_categ_integ` 
                JOIN stores_mkts_linked ON (stores_mkts_linked.id_integration = param_mkt_categ_integ.integ_id AND stores_mkts_linked.apelido = ?)
                WHERE mkt_categ_id = (SELECT REPLACE(REPLACE(REPLACE(products.category_id,'\"',''),']',''),'[','') as category_id FROM products WHERE products.id=?)";
        $query = $this->db->query($sql, [$intTo, $productId]);

        $row = $query->row_array();

        return $row ? $row['valor_aplicado'] : null;

    }

	public function getDatasPagamentoMktplace($integ_id = null, $origin = null)
	{
		$where = "";
		if($integ_id){
			$where = " and PMC.integ_id = $integ_id";
		}

		if($origin){
			$where = " and INTG.apelido = '$origin'";
		}


		$sql = "select group_concat(distinct data_pagamento order by data_pagamento asc) as data_pagamento  
				from param_mkt_ciclo PMC
				INNER JOIN stores_mkts_linked INTG ON INTG.id_mkt = PMC.integ_id 
				where PMC.ativo = 1 $where
				order by data_pagamento";

		$query = $this->db->query($sql);
		if($query){
			return $query->row_array();
		}else{
			return "15";
		}
	    
	}

	/***************** FUNÇÕES DE CICLO FISCAL ******************/

	public function getReceivablesDataCicloFiscal($id = null, $integ_id = null)
	{
	    if($id) {
	        $sql = "SELECT PMC.id, 
                            PMC.integ_id, INTG.descloja AS mkt_place, 
                            PMC.data_inicio, 
                            PMC.data_fim , 
                            PMC.data_ciclo_fiscal, 
                            PMC.data_usada
                    FROM param_mkt_ciclo_fiscal PMC
                    INNER JOIN stores_mkts_linked INTG ON INTG.id_mkt = PMC.integ_id 
                    where PMC.ativo = 1 and PMC.id = ?
                    GROUP BY PMC.data_ciclo_fiscal";
	        $query = $this->db->query($sql, array($id));
	        return $query->row_array();
	    }
	    
	    if($integ_id) {
	        $sql = "SELECT PMC.id, PMC.integ_id, INTG.descloja AS mkt_place, PMC.data_inicio, PMC.data_fim , PMC.data_ciclo_fiscal, PMC.data_usada
                    FROM param_mkt_ciclo_fiscal PMC
                    INNER JOIN stores_mkts_linked INTG ON INTG.id_mkt = PMC.integ_id
                    where PMC.ativo = 1 and PMC.integ_id = ?";
	        $query = $this->db->query($sql, array($integ_id));
	        return $query->row_array();
	    }
	    
	    $sql = "SELECT PMC.id, PMC.integ_id, INTG.descloja AS mkt_place, PMC.data_inicio, PMC.data_fim , PMC.data_ciclo_fiscal, PMC.data_usada
                FROM param_mkt_ciclo_fiscal PMC
                INNER JOIN stores_mkts_linked INTG ON INTG.id_mkt = PMC.integ_id 
                where PMC.ativo = 1 
                order by PMC.id";
	    $query = $this->db->query($sql);
	    return $query->result_array();
	}

	public function checkExisteCicloFiscal($params){
	    
	    $select = "select count(*) as qtd from param_mkt_ciclo_fiscal where integ_id = ".$params['cmb_mktplace']." and data_inicio = '".$params['txt_data_inicio']."' and data_fim = ".$params['txt_data_fim']."";
	    $query = $this->db->query($select);
	    $result = $query->row_array();
	    
	    if($result['qtd'] > 0){
	        return true;
	    }else{
	        return false;
	    }
	    
	}

	public function insertciclofiscal($params)
	{
	    
	    $data = array(
	        'integ_id' => $params['cmb_mktplace'],
	        'data_inicio' => $params['txt_data_inicio'],
	        'data_fim' => $params['txt_data_fim'],
	        'data_ciclo_fiscal' => $params['txt_data_pagamento'],
			'data_usada' => $params['cmb_data_corte']
	    );
	    // SW - Log Create
	    get_instance()->log_data('param_mkt_ciclo_fiscal','create',json_encode($data),"I");
	    
	    $insert = $this->db->insert('param_mkt_ciclo_fiscal', $data);
	    $order_id = $this->db->insert_id();

        //Deleting not paid itens to regenerate new payment date for orders
        // $this->deletePreviousGeneratedConciliationData();

	    return ($order_id) ? $order_id : false;
	}

	public function editCicloFiscal($params){
	    $agora =  date('Y-m-d H:m:s');
	    
	    $data = array(
	        'data_inicio' => $params['txt_data_inicio'],
	        'data_fim' => $params['txt_data_fim'],
	        'data_ciclo_fiscal' => $params['txt_data_pagamento'],
			'data_usada' => $params['cmb_data_corte'],
	        'data_alteracao' => $agora
	    );
	    
	    $this->db->where('id', $params['id']);
	    $update = $this->db->update('param_mkt_ciclo_fiscal', $data);
	    // SW - Log Update
	    $data['id'] = $params['id'];
	    get_instance()->log_data('param_mkt_ciclo_fiscal','update',json_encode($data),"I");

        //Deleting not paid itens to regenerate new payment date for orders
        // $this->deletePreviousGeneratedConciliationData();
	    
	    return true;
	}

	public function removeciclofiscal($id)
	{
	    if($id) {
	        $this->db->where('id', $id);
	        $delete = $this->db->delete('param_mkt_ciclo_fiscal');

            //Deleting not paid itens to regenerate new payment date for orders
            // $this->deletePreviousGeneratedConciliationData();

	        return ($delete == true) ? true : false;
	    }
	}

    public function getAllFiscalCyclesActive(): array
    {
        return $this->db->get_where('param_mkt_ciclo_fiscal', array(
            'ativo' => 1
        ))->result_array();
    }

}