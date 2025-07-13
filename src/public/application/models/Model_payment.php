<?php 
/*
SW Serviços de Informática 2019
 
Model de Acesso ao BD para Recebimentos

*/  

class Model_payment extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}


    public function getMktPlaceBalance($gateway_id): ?array
    {
        return $this->db->get_where('gateway_balance_mktplace', array('gateway_id' => $gateway_id))->row_array();
    }


    public function getMktPlaceTotalReturn($gateway_id = null): int
    {
        // $sql = "select sum(amount_seller) as mktplace_missing from gateway_pendencies_schedule where payment_status = 0";

        // $sql = "
        //         SELECT 
        //             sum(gps.amount_seller) as mktplace_missing 
        //         FROM 
        //             gateway_pendencies_schedule gps
        //             INNER JOIN gateway_pendencies_statements gpst ON gpst.order_id=gps.order_number
        //         WHERE 
        //             gps.payment_status = 0";

        $sql = "select sum(amount) as mktplace_missing from gateway_pendencies where status = 't' and gateway_id = ".$gateway_id;
                    
        $query = $this->db->query($sql);

        return intVal($query->row_array()['mktplace_missing']);
    }


    public function getUnderscoredBalances(int $gateway_id = null, int $conciliation = null, string $stores = null, $pagarme_fee = null, $pagarme_fee_seller = null)
    {
		$pagarme_fee_total = 0;

		$where_1 = " where status_repasse in (21,25,43) ";

		$where_2 = "
					r.status_repasse in (21,25,43)    
                and 
                    c.status_repasse in (21,25)
                and 
                    cs.data_ciclo IS NOT null
                and 
                    g.gateway_id = ".$gateway_id."
                and
                    cs.data_ciclo <> ''
				group by 
                    g.store_id,cs.data_ciclo
                having
                    ( missing > 0 and valor_seller_total > 0 )
                    ";


		if ($stores && $conciliation)
		{
			$where_1 = "WHERE valor_seller > 0";

			$where_2 = "
					s.id in (".$stores.")
				and
					c.id = ".$conciliation."
				and 
                    g.gateway_id = ".$gateway_id."
				and
            		cs.data_ciclo <> ''
				group by 
                    g.store_id,cs.data_ciclo
			";
		}

		if ($gateway_id == 2) //exceção pagarme
		{
			if ($pagarme_fee)
			{
				$pagarme_fee_total = round((($pagarme_fee) / 100), 2);
			}
//			$this->load->model('model_gateway');
//
//			$pagarme_transfer_tax_active = $this->model_gateway->getGatewaySettingByNameAndGatewayCode('charge_seller_tax_pagarme', 2);
//
//			if ($pagarme_transfer_tax_active && $pagarme_transfer_tax_active->value == "1")
//			{
//				$pagarme_transfer_tax = $this->model_gateway->getGatewaySettingByNameAndGatewayCode('cost_transfer_tax_pagarme', 2);
//
//				if ($pagarme_transfer_tax->value == 0)
//				{
//					$pagarme_fee = 3.67;
//				}
//			}
		}

        $sql = "
                SELECT 
                    r.id as transfer_id
                    ,r.name
                    ,round(r.valor_seller_total, 2) AS valor_seller_total
                    ,r.conciliacao_id as conciliation_id
                    ,ROUND((g.available / 100),2) AS available
                    ,g.store_id
                    ,FORMAT(ROUND((r.valor_seller_total - (g.available / 100) + IF(s.bank <> 'Bradesco', $pagarme_fee_total, 0)), 2), 2) as missing
                    ,cs.data_ciclo
                    ,c.lote
                    #,case when p.status is null then 'p' ELSE p.status end as transfer_status
                    ,'p' as transfer_status,
                    '".$pagarme_fee_total."' as pagarme_fee 
				  	,s.bank           
                from 
                    gateway_balance g            
                    inner join (SELECT *,sum(valor_seller) as valor_seller_total from repasse ".$where_1." group by store_id, conciliacao_id having valor_seller_total > 0) r
                    ON r.store_id = g.store_id
                    inner JOIN conciliacao c on r.conciliacao_id = c.id
                    INNER JOIN stores s ON s.id = g.store_id   
                    inner join conciliacao_sellercenter cs on cs.lote = c.lote AND cs.store_id = s.id 
                    #left join gateway_pendencies p on r.conciliacao_id = p.conciliation_id and p.store_id = r.store_id
                where 
                    ".$where_2."

                order by 
                    #transfer_status,
                    cs.data_ciclo desc, r.name
                ";

        $query = $this->db->query($sql);
        return $query->result_array();
    }


    public function createBalanceTransfer($result_array = null)
    {
        if (empty($result_array))
            return false;

		$this->db->insert('gateway_pendencies', $result_array);

		return $this->db->insert_id();
    }
    

    public function checkBalanceTransfer($result_array = null)
    {
        if (empty($result_array))
            return false;

        $sql = "select * from gateway_pendencies where store_id = ? and conciliation_id = ?";
        $query = $this->db->query($sql, array($result_array['store_id'], $result_array['conciliation_id']));
        return $query->row_array();
    }


    public function getReceivablesDataCiclo()
	{
	    $sql = "SELECT PMC.id, PMC.integ_id, INTG.descloja AS mkt_place, PMC.data_inicio, PMC.data_fim , PMC.data_pagamento, PMC.data_pagamento_conecta, PMC.data_usada
                FROM param_mkt_ciclo PMC
                INNER JOIN stores_mkts_linked INTG ON INTG.id_mkt = PMC.integ_id 
                where PMC.ativo = 1 
                order by PMC.id";
	    $query = $this->db->query($sql);
	    return $query->result_array();
	}



    public function getModalBalanceOrdersData($store_id, $date_cycle)
    {
        if (empty($store_id) || empty($date_cycle))
            return false;

        $date_format = '%d/%m/%Y';
        
        if ($_SESSION['language_code'] == 'en_US')
            $date_format = '%m/%d/%Y';

        $sql = "
                SELECT 
                    cs.lote
                    ,cs.store_id
                    ,cs.seller_name
                    ,cs.order_id
                    ,cs.numero_marketplace
                    ,cs.data_ciclo
                    ,cs.valor_pedido
                    ,case when cs.valor_repasse_ajustado > 0 then cs.valor_repasse_ajustado ELSE cs.valor_repasse END AS valor_repasse
                    #,(SELECT status_repasse FROM repasse WHERE status_repasse IN (21,25) AND store_id=".$store_id." AND lote=cs.lote GROUP BY lote) AS status_repasse
                    ,o.data_pago
                    ,op.forma_id
                    ,op.forma_desc
                    ,gps.date_scheduled
                    ,date_format(gps.date_scheduled, '".$date_format."') as date_scheduled_formatted
                    ,date(gps.date_scheduled + interval 1 day) as date_scheduled_plus
                    ,date_format(date(gps.date_scheduled + interval 1 DAY), '".$date_format."') as date_scheduled_formatted_plus
                    ,case when gp.status is null then 'p' ELSE gp.status end as transfer_status
                    ,case when gpsch.payment_status = 1 then 'r' ELSE 'p' end as payment_status
                    ,gps.type
                    ,gps.id as statement_id
                FROM 
                    conciliacao_sellercenter cs
                    INNER JOIN conciliacao c USING(lote)
                    inner join orders o on cs.order_id=o.id
                    inner join orders_payment op ON op.order_id=o.id
                    LEFT  join gateway_pendencies gp on gp.conciliation_id=c.id
                    inner JOIN gateway_pendencies_statements gps ON gps.order_id = cs.numero_marketplace                    
                    #inner JOIN gateway_pendencies_statements gps ON substring(gps.order_id, 0, POSITION('-' IN gps.order_id)) = substring(cs.numero_marketplace, 0, POSITION('-' IN cs.numero_marketplace))
                    #LEFT JOIN gateway_pendencies_schedule gpsch ON gpsch.order_number = gps.order_id
                    LEFT JOIN gateway_pendencies_schedule gpsch ON gpsch.order_number = o.numero_marketplace
                WHERE 
                    cs.store_id = ".$store_id." 
                AND
                    cs.data_ciclo = '".$date_cycle."'
                AND
                    gps.date_scheduled >= CURDATE()
                AND
                    op.forma_id = 'creditCard'
                #AND
                #    (gpsch.payment_status <> 1 || gpsch.payment_status is NULL)
                AND
                    valor_repasse > 0 
                GROUP BY 
                    cs.order_id
                ";

        $query = $this->db->query($sql);
	    return $query->result_array();
    }


    public function saveStatement($orders, $url_order = null)
    {
        if (empty($orders))
            return false;

        $sql = "select * from gateway_pendencies_statements where store_id = ".$orders['store_id']." and order_id = '".$orders['order_id']."' and type = '".$orders['type']."'";
        $query = $this->db->query($sql);
        $exists = $query->row_array();

        if (!empty($exists))
        {            
            $update_array = [
                'date_order'=> $orders['date_order'],
                'date_payment'=> $orders['date_payment'],
                'payment_status'=> $orders['payment_status'],
            ];
            
            $match_array = [
                'store_id' => $orders['store_id'],
                'order_id' => $orders['order_id'],
                'type' => $orders['type']
            ];

            $this->db->update('gateway_pendencies_statements', $update_array, $match_array);

            return true;
        }
        else
        {
            $this->db->insert('gateway_pendencies_statements', $orders);
            return $this->db->insert_id();
        }
    }

    
    public function saveScheduledRefunds($refund_array = null): ?int
    {
        $sql = "select 
                    id 
                from 
                    gateway_pendencies_schedule 
                where 
                    pendency_id = ".$refund_array['pendency_id']." 
                and 
                    statement_id = '".$refund_array['statement_id']."'
                and 
                    order_number = '".$refund_array['order_number']."'
                and
                    amount_seller = ".$refund_array['amount_seller']." 
                ";
                
        $query = $this->db->query($sql);
        
        if ($query->num_rows() == 0)
        {
            $this->db->insert('gateway_pendencies_schedule', $refund_array);
            return $this->db->insert_id();        
        }

        return null;
    }


    public function updateCreditCardPendency($pendency_id = null, $creditcard_amount = null)
    {
        if (empty($pendency_id) || empty($creditcard_amount))
            return false;

        $this->db->where('id', $pendency_id);
        return $this->db->update('gateway_pendencies', array('amount_card' => $creditcard_amount));
    }


    public function getCurrentReturns()
    {
        $sql = "
                SELECT 
                    gps.*
                    ,moip.moip_id
                    ,moip.access_token
                FROM 
                    gateway_pendencies_schedule gps 
                    LEFT JOIN gateway_pendencies gp ON gp.id=gps.pendency_id
                    LEFT JOIN moip_subaccounts moip ON moip.store_id=gp.store_id
                WHERE 
                    gps.date_scheduled = CURDATE()
                AND
                    gps.payment_status < 1
                ";

        $query = $this->db->query($sql);
	    return   $query->result_array();
    }


    public function confirmSchedulePayment($return = null)
    {
        if (empty($return))
            return false;

        $this->db->where('id', $return['id']);
        return $this->db->update('gateway_pendencies_schedule', array('payment_status' => 1));
    }


    public function checkIfDebtIsSettled($pendency_id = null)
    {
        if (empty($pendency_id))
            return false;

        $sql = "
                SELECT 
                    case when COUNT(payment_status) = (SELECT COUNT(pendency_id) FROM gateway_pendencies_schedule WHERE pendency_id = ".$pendency_id.") then 'settled' ELSE 'not settled' END AS current_status
                FROM 
                    gateway_pendencies_schedule
                WHERE
                    pendency_id = ".$pendency_id."
                AND
                    payment_status = 1
            ";

            $query = $this->db->query($sql);
            return $query->row_array()['current_status'];
    }


    public function endPendency($pendency_id = null)
    {
        if (empty($pendency_id))
            return false;

        $sql = "update gateway_pendencies set status = 'r' where id = ".$pendency_id;
        return $this->db->query($sql);
    }


    public function getPendenciesList()
    {
        $sql = "
                SELECT 
                    gp.* 
                    #,gp.amount
                    ,s.name AS store_name
                    ,u.firstname
                    ,u.lastname
                    ,(SELECT SUM(amount_seller) AS amount_seller FROM gateway_pendencies_schedule WHERE pendency_id = gp.id AND payment_status = 1) AS total_paid
                FROM 
                    gateway_pendencies gp  
                    LEFT JOIN stores s ON gp.store_id = s.id
                    LEFT JOIN users u ON gp.user_id = u.id
                order by gp.id desc
                ";

        $query = $this->db->query($sql);
	    return   $query->result_array();
    }








	public function getBilletsPaymentSplitId($id = null)
	{
	    
	    // Verifica se já existe um split default cadastrado
	    $sql = "select count(*) as qtd from (
                                            select distinct
                                            	b.id,
                                            	i.name as marketplace,
                                            	b.id_boleto_iugu,
                                            	o.bill_no,
                                            	o.date_time,
                                            	o.gross_amount as total_order,
                                            	case when bo.ativo = 1 then 'Ativo' else 'Não contabilizado' end as ativo
                                            from billet b
                                            inner join billet_status sb on sb.id = b.status_id
                                            inner join billet_status si on si.id = b.status_iugu
                                            inner join integrations i on i.id = b.integrations_id
                                            inner join billet_order bo on bo.billet_id = b.id
                                            inner join billet_order_payment bop on bop.billet_order_id = bo.id
                                            inner join orders o on o.id = bo.order_id
                                            where b.id = $id) a";
	    
	    $query = $this->db->query($sql);
	    $query = $query->result_array();
	    $total = $query[0]['qtd'];
	    
	    if($total == "0"){
	        $this->insereSplitDefault($id);
	    }
	        echo 2;die;
	    
	    $sql = "select distinct
                		b.id,
                		i.name as marketplace,
                		b.id_boleto_iugu,
                        o.bill_no,
                        o.date_time,
                		o.gross_amount as total_order,
                        case when bo.ativo = 1 then 'Ativo' else 'Não contabilizado' end as ativo
                from billet b
                inner join billet_status sb on sb.id = b.status_id
                inner join billet_status si on si.id = b.status_iugu
                inner join integrations i on i.id = b.integrations_id
                inner join billet_order bo on bo.billet_id = b.id
                inner join orders o on o.id = bo.order_id
                where b.id = $id
                order by b.id, o.bill_no";
	    $query = $this->db->query($sql);
	    return $query->result_array();
	}
	
	public function insereSplitDefault($id){
	    echo 'aqui';
	    //Busca todos os pedidos do Boleto para realizar o split de acordo com as regras
	    $sql2 = 'select 	BILL_PAY.id as id_billet,
                		BILL_PAY.id_mkt_place,
                        BILL_PAY.total_order as valor_pedido,
                        BILL_PAY.total_ship as valor_frete,
                		MKTPLACE.valor_aplicado as percentual_mktplace,
                        \'5\' as percentual_conectala,
                        \'5\' as percentual_agency
                from (
                select 
                	b.id,
                    i.id as id_mkt_place,
                	i.name as marketplace,
                	b.id_boleto_iugu,
                	o.bill_no,
                    o.total_ship,
                	o.date_time,
                	o.gross_amount as total_order,
                	case when bo.ativo = 1 then \'Ativo\' else \'Não contabilizado\' end as ativo,
                	replace(SUBSTRING(cat.name, 1, position(" >" in cat.name)-1),"\"","") as categProduto
                from billet b
                inner join integrations i on i.id = b.integrations_id
                inner join billet_order bo on bo.billet_id = b.id
                inner join orders o on o.id = bo.order_id
                inner join orders_item oi on oi.order_id = b.id
                inner join products p on p.id = oi.product_id
                inner join categories cat on (cat.id = p.category_id) or (SUBSTRING(p.category_id,POSITION("[" IN p.category_id)+2,(POSITION("]" IN p.category_id)-1)-(POSITION("[" IN p.category_id)+2)) = cat.id)
                inner join company c on c.id = oi.company_id
                inner join stores s on s.id = oi.store_id
                where b.id = 1) BILL_PAY
                left join (select pmci.integ_id, pmc.nome as categoria, pmci.valor_aplicado from param_mkt_categ pmc inner join param_mkt_categ_integ pmci on pmci.mkt_categ_id = pmc.id) MKTPLACE on BILL_PAY.id_mkt_place = MKTPLACE.integ_id and BILL_PAY.categProduto = MKTPLACE.categoria';
	    
	    $query2 = $this->db->query($sql2);
	    $query2 = $query2->result_array();
	    echo '<pre>';print_r($query2);die;
	    
	}
	
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
                        o.gross_amount as valor
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


    /* FUNÇÕES DA TELA DE EXTRATO */
	public function getPrevisaoPagamentoGridSomatorioLoja($id = null, $data = null, $status = null){
	    
	    $mesTratado = $this->getMespeloAtual($data);
	    
	    if($data == null){
            $ano = date('Y');	        
	    }else{
    	    $ano = substr($data, 0, 4);
	    }
	    $more = ($this->data['usercomp'] == 1) ? "": (($this->data['userstore'] == 0) ? " AND O.company_id = ".$this->data['usercomp'] : " AND O.store_id = ".$this->data['userstore']);
	    
        $where = "";
        
        if($status <> null){
            $where = "where FN.ciclo = '$status'";
        }
        
        $mesTratadoPagamento1 = ($mesTratado * -1) + 1;
        $mesTratadoPagamento2 = ($mesTratado * -1) + 2;
        
        $sql = "SELECT 
                        FN.*
                FROM (
                        SELECT 	
                            ORDS.store_id,
                            ORDS.loja,
                        	CICLO.integ_id,
                        	CICLO.nome_mktplace,
                        	DATE_FORMAT(CICLO.data_inicio, '%d/%m/%Y') AS data_inicio,
                        	DATE_FORMAT(CICLO.data_fim, '%d/%m/%Y') AS data_fim,

                            CASE WHEN DATEDIFF(DATE(CONCAT(YEAR(CICLO.data_fim),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL $mesTratado+$mesTratadoPagamento1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento,CONCAT(' 00:00:00'))))))), CICLO.data_fim) < 0 THEN
            					CASE WHEN DATEDIFF(DATE(CONCAT(YEAR(CICLO.data_fim),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL $mesTratado+$mesTratadoPagamento2 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento,CONCAT(' 00:00:00'))))))), CICLO.data_fim) > 21 THEN
            						DATE_FORMAT(DATE(CONCAT(YEAR(CICLO.data_fim),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL $mesTratado+$mesTratadoPagamento1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
            					ELSE
            						DATE_FORMAT(DATE(CONCAT(YEAR(CICLO.data_fim),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL $mesTratado+$mesTratadoPagamento2 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
            					END
                            ELSE
            					CASE WHEN DATEDIFF(DATE(CONCAT(YEAR(CICLO.data_fim),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL $mesTratado+$mesTratadoPagamento1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento,CONCAT(' 00:00:00'))))))), CICLO.data_fim) > 21 THEN
            						DATE_FORMAT(DATE(CONCAT(YEAR(CICLO.data_fim),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
            					ELSE
            						DATE_FORMAT(DATE(CONCAT(YEAR(CICLO.data_fim),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL $mesTratado+$mesTratadoPagamento1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
            					END 
            				END AS data_pagamento,
            				
            				CASE WHEN DATEDIFF(DATE(CONCAT(YEAR(CICLO.data_fim),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL $mesTratado+$mesTratadoPagamento1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento,CONCAT(' 00:00:00'))))))), CICLO.data_fim) < 0 THEN
            					CASE WHEN DATEDIFF(DATE(CONCAT(YEAR(CICLO.data_fim),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL $mesTratado+$mesTratadoPagamento2 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento,CONCAT(' 00:00:00'))))))), CICLO.data_fim) > 21 THEN
            						DATE_FORMAT(DATE(CONCAT(YEAR(CICLO.data_fim),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL $mesTratado+$mesTratadoPagamento1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
            					ELSE
            						DATE_FORMAT(DATE(CONCAT(YEAR(CICLO.data_fim),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL $mesTratado+$mesTratadoPagamento2 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
            					END
                            ELSE
            					CASE WHEN DATEDIFF(DATE(CONCAT(YEAR(CICLO.data_fim),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL $mesTratado+$mesTratadoPagamento1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento,CONCAT(' 00:00:00'))))))), CICLO.data_fim) > 21 THEN
            						DATE_FORMAT(DATE(CONCAT(YEAR(CICLO.data_fim),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
            					ELSE
            						DATE_FORMAT(DATE(CONCAT(YEAR(CICLO.data_fim),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL $mesTratado+$mesTratadoPagamento1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
            					END 
            				END AS data_pagamento_conecta,

                            CASE WHEN DATE(CICLO.data_fim) > CURRENT_TIMESTAMP() THEN 'Em andamento' ELSE CASE WHEN IFNULL(ROUND(SUM( ORDS.expectativaReceb ),2),'-') = '-' THEN 'Encerrado - Sem repasse' ELSE CASE WHEN GROUP_CONCAT(DISTINCT CONCI.lote,'') is null then 'Encerrado - Não Conciliado' else 'Encerrado - Conciliado' END END END as ciclo,
                            GROUP_CONCAT(CONCI.observacao) AS observacao,
                            CASE WHEN ORDS.store_id IS NULL THEN '-' ELSE IFNULL(ROUND(SUM( ORDS.expectativaReceb ),2),'-') END AS valor_total_ciclo,
                            CASE WHEN DATE(CICLO.data_fim) > CURRENT_TIMESTAMP() THEN '-' else CASE WHEN GROUP_CONCAT(DISTINCT CONCI.lote,'') is null then '-' else IFNULL(ROUND(SUM( CONCI.valor_parceiro ),2),'-') END END AS recebido,
                        	CASE WHEN DATE(CICLO.data_fim) > CURRENT_TIMESTAMP() THEN '-' else CASE WHEN GROUP_CONCAT(DISTINCT CONCI.lote,'') is null then '-' else ROUND( IFNULL(SUM( CONCI.valor_parceiro ),'0.00') - IFNULL(SUM( ORDS.expectativaReceb ),'0.00') ,2) END END AS diferenca,
                            ifnull(GROUP_CONCAT(DISTINCT CONCI.lote,''),'-') AS lote
                        FROM (SELECT DISTINCT 
                        		PMC.integ_id, 
                        		CASE WHEN PMC.data_inicio < PMC.data_fim THEN
                        				DATE(CONCAT($ano,CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL $mesTratado MONTH)),CONCAT('/',CONCAT(PMC.data_inicio,CONCAT(' 00:00:00')))))))
                        			ELSE
                        				DATE(CONCAT($ano,CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ($mesTratado+-1) MONTH)),CONCAT('/',CONCAT(PMC.data_inicio,CONCAT(' 00:00:00'))))))) 
                        			END AS data_inicio,
                        		CASE WHEN DATE(CONCAT($ano,CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL $mesTratado MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00'))))))) IS NULL THEN
                        		DATE(CONCAT($ano,CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL $mesTratado MONTH)),CONCAT('/',CONCAT(PMC.data_fim-1,CONCAT(' 00:00:00'))))))) 
                        		ELSE
                        		DATE(CONCAT($ano,CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL $mesTratado MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00')))))))
                        		END AS data_fim,
                        		PMC.data_pagamento,
                        		PMC.data_pagamento_conecta,
                        		SML.descloja AS nome_mktplace,
                                SML.apelido
                        FROM `param_mkt_ciclo` PMC
                        INNER JOIN stores_mkts_linked SML ON SML.id_mkt = PMC.integ_id
                        WHERE PMC.ativo = 1) CICLO
                        LEFT JOIN (SELECT  SUM(O.total_order) AS valores_receber, 
                        		IFNULL(date(O.data_entrega),DATE('0001-01-01 00:00:00')) AS data_entrega,
                        		O.origin,
                        		O.date_time,
                        		O.total_order, 
                        		O.total_ship,
                        		O.gross_amount,
                        		ROUND(O.gross_amount - ( O.gross_amount * (O.service_charge_rate/100) ) - O.total_ship,2) AS expectativaReceb,
                        		S.name AS loja,
                        		O.id,
                        		O.numero_marketplace AS numero_pedido,
                        		O.service_charge_rate,
                        	    O.paid_status,
                                S.id as store_id
                        	FROM orders O
                        	INNER JOIN stores S ON S.id = O.store_id
                        	WHERE O.origin not in ('ML') and O.paid_status not in ('95','96','97','98','99') AND IFNULL(O.data_entrega,DATE('0001-01-01 00:00:00')) >= DATE(CONCAT($ano,CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ($mesTratado+-1) MONTH)),CONCAT('/','01 00:00:00'))))) AND
                        	IFNULL(O.data_entrega,DATE('0001-01-01 00:00:00')) <= (CASE WHEN DATE(CONCAT($ano,CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL $mesTratado MONTH)),CONCAT('/',CONCAT('31',CONCAT(' 00:00:00'))))))) IS NULL THEN
                                                                                    DATE(CONCAT($ano,CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL $mesTratado MONTH)),CONCAT('/',CONCAT('30',CONCAT(' 00:00:00'))))))) 
                                                                                    ELSE
                                                                                    DATE(CONCAT($ano,CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL $mesTratado MONTH)),CONCAT('/',CONCAT('31',CONCAT(' 00:00:00')))))))
                                                                                    END)
                        	   $more
                        	 GROUP BY  IFNULL(O.data_entrega,DATE('0001-01-01 00:00:00')), O.origin, O.date_time, O.total_order,  O.total_ship, O.gross_amount, S.name, O.id, O.numero_marketplace, O.paid_status, O.service_charge_rate
                            union
                            SELECT  SUM(O.total_order) AS valores_receber, 
                        		IFNULL(date(DATE_ADD(DATE(CONCAT(O.data_pago,' 00:00:00')), INTERVAL 28 DAY)),DATE('0001-01-01 00:00:00')) AS data_entrega,
                        		O.origin,
                        		O.date_time,
                        		O.total_order, 
                        		O.total_ship,
                        		O.gross_amount,
                        		ROUND(O.gross_amount - ( O.gross_amount * (O.service_charge_rate/100) ) - O.total_ship,2) AS expectativaReceb,
                        		S.name AS loja,
                        		O.id,
                        		O.numero_marketplace AS numero_pedido,
                        		O.service_charge_rate,
                        	    O.paid_status,
                                S.id as store_id
                        	FROM orders O
                        	INNER JOIN stores S ON S.id = O.store_id
                        	WHERE O.origin in ('ML') and O.paid_status not in ('95','96','97','98','99') AND IFNULL(DATE_ADD(DATE(CONCAT(O.data_pago,' 00:00:00')), INTERVAL 28 DAY),DATE('0001-01-01 00:00:00')) >= DATE(CONCAT($ano,CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ($mesTratado+-1) MONTH)),CONCAT('/','01 00:00:00'))))) AND
                        	IFNULL(DATE_ADD(DATE(CONCAT(O.data_pago,' 00:00:00')), INTERVAL 28 DAY),DATE('0001-01-01 00:00:00')) <= (CASE WHEN DATE(CONCAT($ano,CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL $mesTratado MONTH)),CONCAT('/',CONCAT('31',CONCAT(' 00:00:00'))))))) IS NULL THEN
                                                                                    DATE(CONCAT($ano,CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL $mesTratado MONTH)),CONCAT('/',CONCAT('30',CONCAT(' 00:00:00'))))))) 
                                                                                    ELSE
                                                                                    DATE(CONCAT($ano,CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL $mesTratado MONTH)),CONCAT('/',CONCAT('31',CONCAT(' 00:00:00')))))))
                                                                                    END)
                        	   $more
                        	 GROUP BY  IFNULL(DATE_ADD(DATE(CONCAT(O.data_pago,' 00:00:00')), INTERVAL 28 DAY),DATE('0001-01-01 00:00:00')), O.origin, O.date_time, O.total_order,  O.total_ship, O.gross_amount, S.name, O.id, O.numero_marketplace, O.paid_status, O.service_charge_rate
                        ) ORDS ON ORDS.data_entrega >= CICLO.data_inicio  AND ORDS.data_entrega <= CICLO.data_fim AND ORDS.origin = CICLO.apelido
                        LEFT JOIN (
                        SELECT CONCI.*, OBS.chamado_mktplace, OBS.chamado_agidesk, OBS.observacao FROM (
                        SELECT CC.lote, '' as entrega, CC.n_do_pedido AS numero_do_pedido, CASE WHEN valor_parceiro_novo = '0.00' THEN valor_parceiro ELSE valor_parceiro_novo END AS valor_parceiro, CASE WHEN tratado = 0 THEN \"Não tratado\" ELSE \"Tratado\" END AS tratado, SML.apelido FROM conciliacao_carrefour CC INNER JOIN `conciliacao` C ON C.lote = CC.lote INNER JOIN `stores_mkts_linked` SML ON SML.id_mkt = C. integ_id UNION
                        SELECT CC.lote, '' as entrega, CC.n_do_pedido AS numero_do_pedido, CASE WHEN valor_parceiro_novo = '0.00' THEN valor_parceiro ELSE valor_parceiro_novo END AS valor_parceiro, CASE WHEN tratado = 0 THEN \"Não tratado\" ELSE \"Tratado\" END AS tratado, SML.apelido FROM conciliacao_carrefour_xls CC INNER JOIN `conciliacao` C ON C.lote = CC.lote INNER JOIN `stores_mkts_linked` SML ON SML.id_mkt = C. integ_id UNION
                        SELECT CC.lote, '' as entrega, CC.numero_do_pedido AS numero_do_pedido, CASE WHEN valor_parceiro_novo = '0.00' THEN valor_parceiro ELSE valor_parceiro_novo END AS valor_parceiro, CASE WHEN tratado = 0 THEN \"Não tratado\" ELSE \"Tratado\" END AS tratado, SML.apelido FROM conciliacao_viavarejo CC INNER JOIN `conciliacao` C ON C.lote = CC.lote INNER JOIN `stores_mkts_linked` SML ON SML.id_mkt = C. integ_id UNION
                        SELECT CC.lote, '' as entrega, CC.external_reference AS numero_do_pedido, CASE WHEN valor_parceiro_novo = '0.00' THEN valor_parceiro ELSE valor_parceiro_novo END AS valor_parceiro, CASE WHEN tratado = 0 THEN \"Não tratado\" ELSE \"Tratado\" END AS tratado, SML.apelido FROM conciliacao_mercadolivre CC INNER JOIN `conciliacao` C ON C.lote = CC.lote INNER JOIN `stores_mkts_linked` SML ON SML.id_mkt = C. integ_id UNION
                        SELECT CC.lote, entrega AS entrega, CC.ref_pedido AS numero_do_pedido, CASE WHEN valor_parceiro_novo = '0.00' THEN valor_parceiro ELSE valor_parceiro_novo END AS valor_parceiro, CASE WHEN tratado = 0 THEN \"Não tratado\" ELSE \"Tratado\" END AS tratado, SML.apelido FROM conciliacao_b2w_tratado CC INNER JOIN `conciliacao` C ON C.lote = CC.lote INNER JOIN `stores_mkts_linked` SML ON SML.id_mkt = C. integ_id ) CONCI
                        LEFT JOIN (SELECT num_pedido, GROUP_CONCAT(DISTINCT IFNULL(chamado_mktplace,''),'-') AS chamado_mktplace, GROUP_CONCAT(DISTINCT IFNULL(chamado_agidesk,''),'-') AS chamado_agidesk, lote, GROUP_CONCAT(CONCAT(' [',CONCAT(DATE_FORMAT(data_criacao, '%d-%m-%Y'),CONCAT('] - ',observacao)))) AS observacao FROM `conciliacao_pedido` GROUP BY num_pedido) OBS ON OBS.num_pedido = CONCI.numero_do_pedido AND OBS.lote = CONCI.lote) CONCI ON CASE WHEN ORDS.origin = 'B2W' THEN SUBSTRING(ORDS.numero_pedido,POSITION('-' IN ORDS.numero_pedido)+1, LENGTH(ORDS.numero_pedido)- POSITION('-' IN ORDS.numero_pedido)) = CONCI.ENTREGA ELSE CONCI.numero_do_pedido = ORDS.numero_pedido END  AND ORDS.origin = CONCI.apelido
                        GROUP BY ORDS.store_id,
                            ORDS.loja,
        			         CICLO.integ_id,
                        	CICLO.nome_mktplace,
                        	CICLO.data_inicio,
                        	CICLO.data_fim,
                        	CICLO.data_pagamento,
                        	CICLO.data_pagamento_conecta
                        ) FN
                $where
                ORDER BY FN.nome_mktplace, FN.data_inicio";
        //echo '<pre>'.$sql;die;
	    $query = $this->db->query($sql);
	    return $query->result_array();
	    
	}
	
	public function getPrevisaoPagamentoGridSomatorioLojaControle($id = null){
	    
	    $more = ($this->data['usercomp'] == 1) ? "": (($this->data['userstore'] == 0) ? " and C.id = ".$this->data['usercomp'] : " and O.store_id = ".$this->data['userstore']);
	    
	    $sql = "SELECT 	ORDS.name AS loja, ORDS.origin AS mktplace, ifnull(CICLO.data_pagamento,'0') AS data_pagamento,
                	ROUND(SUM( ORDS.valores_receber ),2) AS valor_total_ciclo
                FROM (
                	SELECT C.id, C.name, O.origin,
                	ifnull(data_entrega,DATE('0001-01-01 00:00:00')) AS data_entrega , SUM(total_order) AS valores_receber
                	FROM orders O
                	LEFT JOIN `company` C ON C.id = O.company_id
                	WHERE date_time >= DATE(CONCAT(YEAR(CURRENT_TIMESTAMP),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL -1 MONTH)),CONCAT('/','01 00:00:00')))))
                $more
                 GROUP BY  C.id, C.name, O.origin, ifnull(data_entrega,DATE('0001-01-01 00:00:00'))) ORDS
                LEFT JOIN (SELECT DISTINCT 
                		PMC.integ_id, 
                		SMK.descloja,
                		DATE(CONCAT(YEAR(CURRENT_TIMESTAMP),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL -1 MONTH)),CONCAT('/',CONCAT(PMC.data_inicio,CONCAT(' 00:00:00'))))))) AS data_inicio,
                		CASE WHEN DATE(CONCAT(YEAR(CURRENT_TIMESTAMP),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL -1 MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00'))))))) IS NULL THEN
                		DATE(CONCAT(YEAR(CURRENT_TIMESTAMP),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL -1 MONTH)),CONCAT('/',CONCAT(PMC.data_fim-1,CONCAT(' 00:00:00'))))))) 
                		ELSE
                		DATE(CONCAT(YEAR(CURRENT_TIMESTAMP),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL -1 MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00')))))))
                		END AS data_fim,
                		PMC.data_pagamento_conecta AS data_pagamento
                FROM `param_mkt_ciclo` PMC
                INNER JOIN stores_mkts_linked SMK ON SMK.id_mkt = PMC.integ_id
                WHERE PMC.ativo = 1) CICLO ON ORDS.data_entrega >= CICLO.data_inicio  AND ORDS.data_entrega <= CICLO.data_fim AND ORDS.origin = CICLO.descloja
                GROUP BY ORDS.name, ORDS.origin, ifnull(CICLO.data_pagamento,'0')
                ORDER BY ifnull(CICLO.data_pagamento,'0'), ORDS.name, ORDS.origin";
	    
	    $query = $this->db->query($sql);
	    return $query->result_array();
	    
	}
	
	public function getMespeloAtual($data){
	    
	    if($data == null){
	        
	        $saida[0]['diferenca'] = 0;
	        
	        
	        return 0;
	    }else{
	        
	        $sql = "SELECT A.*, PERIOD_DIFF(filtro, atual) as diferenca FROM (
                    SELECT CONCAT(EXTRACT(YEAR FROM CURRENT_DATE()) ,CASE WHEN EXTRACT(MONTH FROM CURRENT_DATE()) < 10 THEN CONCAT('0',EXTRACT(MONTH FROM CURRENT_DATE())) ELSE EXTRACT(MONTH FROM CURRENT_DATE()) END ) AS atual, 
                    '$data' AS filtro) A";
	        
	        $query = $this->db->query($sql);
	        $saida = $query->result_array();
	        
	        return $saida[0]['diferenca'];
	        
	    }
	    
	    
	}
	
	public function buscalojafiltro($id = null){
	    
	    $more = ($this->data['usercomp'] == 1) ? "": (($this->data['userstore'] == 0) ? " AND S.company_id = ".$this->data['usercomp'] : " AND S.id = ".$this->data['userstore']);
	    
	    $where = "WHERE 1=1";
	    
	    if($id){
	        $where .= " S.id = $id ";
	    }
	    
	    $sql = "select distinct S.id, S.name 
                FROM stores S
                 $where $more
				order by S.name";
	    
         $query = $this->db->query($sql);
         return $query->result_array();
	    
	}
	
	public function getordersforadeciclo($id = null, $data = null, $status = null){
	    
	    $mesTratado = $this->getMespeloAtual($data);
	    
	    if($data == null){
	        $ano = date('Y');
	    }else{
	        $ano = substr($data, 0, 4);
	    }
	    $where = '';
	    if($status <> null){
	        $where = "where 'Em andamento - Fora de Ciclo' = '$status'";
	    }
	    
	    $more = ($this->data['usercomp'] == 1) ? "": (($this->data['userstore'] == 0) ? " AND O.company_id = ".$this->data['usercomp'] : " AND O.store_id = ".$this->data['userstore']);
	    
	    
	    $sql = "SELECT 	
                    ORDS.store_id,
                    ORDS.loja,
                	'' as integ_id,
                	'' as nome_mktplace,
                	'' AS data_inicio,
                	'' AS data_fim,
                	'' AS data_pagamento,
			        '' AS data_pagamento_conecta,
                	'Em andamento - Fora de Ciclo' AS ciclo,
                	'' AS observacao,
                    IFNULL(ROUND(SUM( ORDS.expectativaReceb ),2),'-') AS valor_total_ciclo,
                    '' AS recebido,
                	'' AS diferenca,
                    '' AS lote
                FROM (SELECT  SUM(O.total_order) AS valores_receber, 
                		IFNULL(O.data_entrega,DATE('0001-01-01 00:00:00')) AS data_entrega,
                		O.origin,
                		O.date_time,
                		O.total_order, 
                		O.total_ship,
                		O.gross_amount,
                		ROUND(O.gross_amount - ( O.gross_amount * (O.service_charge_rate/100) ) - O.total_ship,2) AS expectativaReceb,
                		S.name AS loja,
                		O.id,
                		O.numero_marketplace AS numero_pedido,
                		O.service_charge_rate,
                	    O.paid_status,
                        S.id as store_id
            	FROM orders O
            	INNER JOIN stores S ON S.id = O.store_id
            	WHERE IFNULL(O.date_time,DATE('0001-01-01 00:00:00')) >= DATE(CONCAT($ano,CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ($mesTratado+-1) MONTH)),CONCAT('/','01 00:00:00'))))) AND
            	IFNULL(O.date_time,DATE('0001-01-01 00:00:00')) <= (CASE WHEN DATE(CONCAT($ano,CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL $mesTratado MONTH)),CONCAT('/',CONCAT('31',CONCAT(' 00:00:00'))))))) IS NULL THEN
                                                                        DATE(CONCAT($ano,CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL $mesTratado MONTH)),CONCAT('/',CONCAT('30',CONCAT(' 00:00:00'))))))) 
                                                                        ELSE
                                                                        DATE(CONCAT($ano,CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL $mesTratado MONTH)),CONCAT('/',CONCAT('31',CONCAT(' 00:00:00')))))))
                                                                        END) -- AND O.data_entrega is null AND O.paid_status not in (1,6,95,96,97,98,99)
            	   $more
            	 GROUP BY  IFNULL(O.data_entrega,DATE('0001-01-01 00:00:00')), O.origin, O.date_time, O.total_order,  O.total_ship, O.gross_amount, S.name, O.id, O.numero_marketplace, O.paid_status, O.service_charge_rate) ORDS
                 $where
                GROUP BY ORDS.store_id,
                ORDS.loja";
        
	    $query = $this->db->query($sql);
        return $query->result_array();
	    
	}
	
	
	public function insertnfs($lote, $store_id, $data_ciclo, $arquivo){
	    
	    
	    $data['lote']       = $lote;
	    $data['store_id']         = $store_id;
	    $data['data_ciclo']       = $data_ciclo;
	    $data['nome_arquivo']   = $arquivo;
	    
	    $insert = $this->db->insert('nota_fiscal_servico_temp', $data);
	    $order_id = $this->db->insert_id();
	    return ($order_id) ? $order_id : false;
	    
	}
	
	public function salvavaloresnfsurlmassa($input, $arrayValores){
	    
	    
	    $data['lote']       = $input['lote'];
	    $data['store_id']         = $arrayValores[1];
	    $data['data_ciclo']       = $arrayValores[2];
	    $data['url']   = $arrayValores[3];
	    
	    $insert = $this->db->insert('nota_fiscal_servico_url_temp', $data);
	    $order_id = $this->db->insert_id();
	    return ($order_id) ? $order_id : false;
	    
	}

    public function insertNFSUrl(array $data): bool
    {

        $this->db->insert('nota_fiscal_servico_url', $data);
        $order_id = $this->db->insert_id();
        return ($order_id) ?: false;

    }

    public function insertNFSGroupData(array $data): bool
    {

        $this->db->insert('nota_fiscal_group', $data);
        $order_id = $this->db->insert_id();
        return ($order_id) ?: false;

    }

	public function getnfs($lote, $store){
	    
	    $more = ($this->data['usercomp'] == 1) ? "": (($this->data['userstore'] == 0) ? " AND S.company_id = ".$this->data['usercomp'] : " AND S.id = ".$this->data['userstore']);
	    
	    $where = "";
	    
	    if($lote <> ""){
	        $where .= " and lote = '$lote'";
	    }
	    
	    if($store <> ""){
	        $where .= " and store_id = '$store'";
	    }
	    
	    $sql = "select NFS.*, S.name, DATE_FORMAT(NFS.data_ciclo, '%d/%m/%Y') AS data_ciclo_tratado from nota_fiscal_servico_temp NFS inner join stores S on S.id = NFS.store_id where NFS.ativo = 1 $where $more";

	    $query = $this->db->query($sql);
        $result = $query->result_array();

        //quando ocorre troca de parametro entre manual e url a consulta fica viciada, porem mudam os campoas. essa flexibilizacao sera mto trabalhosa
//        if (empty($result))
//        {
//            $sql = "select NFSU.*, S.name, DATE_FORMAT(NFSU.data_ciclo, '%d/%m/%Y') AS data_ciclo_tratado, NFSU.url from nota_fiscal_servico_url_temp NFSU inner join stores S on S.id = NFSU.store_id where NFSU.ativo = 1 $where $more";
//            $query = $this->db->query($sql);
//            $result = $query->result_array();
//        }

	    return $result;
	    
	}
	
	public function desativaNFS($id){
	    
	    $data = array(
	        'ativo' => 0
	    );
	    
	    $this->db->where('id', $id);
	    return $this->db->update('nota_fiscal_servico_temp', $data);
	    
	}
	
	public function desativaNFSGroup($id){
	    
	    $data = array(
	        'ativo' => 0
	    );
	    
	    $this->db->where('id', $id);
	    return $this->db->update('nota_fiscal_group', $data);
	    
	}
	
	public function getmaxidtemp($lote){
	    
	    $sql = "SELECT COUNT(*) AS id FROM nota_fiscal_servico_temp WHERE lote = '$lote'";
	    
	    $query = $this->db->query($sql);
	    $reult = $query->result_array();
	    
	    return $reult[0];
	    
	}

	public function getnfsurl($lote, $store){
	    
	    $more = ($this->data['usercomp'] == 1) ? "": (($this->data['userstore'] == 0) ? " AND S.company_id = ".$this->data['usercomp'] : " AND S.id = ".$this->data['userstore']);
	    
	    $where = "";
	    
	    if($lote <> ""){
	        $where .= " and lote = '$lote'";
	    }
	    
	    if($store <> ""){
	        $where .= " and store_id = '$store'";
	    }
	    
	    $sql = "select NFS.*, S.name, case when DATE_FORMAT(NFS.data_ciclo, '%d/%m/%Y') is null then NFS.data_ciclo else DATE_FORMAT(NFS.data_ciclo, '%d/%m/%Y') end AS data_ciclo_tratado from nota_fiscal_servico_url_temp NFS inner join stores S on S.id = NFS.store_id where NFS.ativo = 1 $where $more";
	    
	    
	    $query = $this->db->query($sql);
	    return $query->result_array();
	    
	}
	
	public function desativaNFSurl($id){
	    
	    $data = array(
	        'ativo' => 0
	    );
	    
	    $this->db->where('id', $id);
	    return $this->db->update('nota_fiscal_servico_url_temp', $data);
	    
	}
	
	public function getmaxidtempurl($lote){
	    
	    $sql = "SELECT COUNT(*) AS id FROM nota_fiscal_servico_url_temp WHERE lote = '$lote'";
	    
	    $query = $this->db->query($sql);
	    $reult = $query->result_array();
	    
	    return $reult[0];
	    
	}
	
	public function insertnfsgroup($params){
	    
	    $data['lote']          = $params['hdnLote'];
	    $data['store_id']      = $params['slc_store'];
	    $data['data_ciclo']    = $params['slc_ciclo_fiscal'];
	    
	    $insert = $this->db->insert('nota_fiscal_group', $data);
	    $order_id = $this->db->insert_id();
	    return ($order_id) ? $order_id : false;
	    
	}
	
	public function updatenfsgroup($params){
	    
	    
	    $data = array(
	        'lote' => $params['hdnLote'],
	        'store_id' => $params['slc_store'],
	        'data_ciclo' => $params['slc_ciclo_fiscal'],
	    );
	    
	    $this->db->where('id', $params['hdnId']);
	    return $this->db->update('nota_fiscal_group', $data);
	    
	    
	}
	
	public function limpatabelanfsservico($lote, $temp = null){
	    
	    if($temp){
	        $tabela = "nota_fiscal_servico_temp";
	    }else{
	        $tabela = "nota_fiscal_servico";
	    }
	    
	    $sql = "delete from $tabela where lote = '$lote'";
	    return $this->db->query($sql);
	    
	}
	
	public function inseredadostabelanfs($lote, $temp = null){
	    
	    if($temp){
	        $tabelaInsert = "nota_fiscal_servico_temp";
	        $tabelaSelect = "nota_fiscal_servico";
	    }else{
	        $tabelaInsert = "nota_fiscal_servico";
	        $tabelaSelect = "nota_fiscal_servico_temp";
	    }
	    
	    $sql = "INSERT INTO $tabelaInsert (lote, store_id, data_ciclo, nome_arquivo, data_criacao, ativo)
                SELECT lote, store_id, data_ciclo, nome_arquivo, data_criacao, ativo FROM $tabelaSelect WHERE lote = '$lote'";
	    return $this->db->query($sql);
	    
	    
	}
	
	public function getnfsgroup($id = null){
	    
	    $where = ""; 
		$more = "";
	    if($id){
	        $where = " and NFG.id = '$id'";
	    }
	    
	    // $more = ($this->data['usercomp'] == 1) ? "": (($this->data['userstore'] == 0) ? "" : " AND NFG.store_id = ".$this->data['userstore']);

		if (isset($this->data['usercomp'])) {
            if(!($this->data['usercomp'] == 1)){
                if(0 == $this->data['userstore'] && !is_null($this->data['userstore'])){
                    if($this->data['usercomp'] <> ""){
                        $more = " AND S.company_id = {$this->data['usercomp']} ";
                    }
                }else{
                    if($this->data['userstore'] <> ""){
                        $more = " AND S.id = {$this->data['userstore']} ";
                    }
                }
            }  
        }
	    
	    $sql = "select NFG.*, S.name as loja, case when DATE_FORMAT(NFG.data_ciclo, '%d/%m/%Y') is null then NFG.data_ciclo else DATE_FORMAT(NFG.data_ciclo, '%d/%m/%Y') end AS data_ciclo_tratado from nota_fiscal_group NFG inner join stores S on S.id = NFG.store_id where NFG.ativo = 1 $where $more order by id desc";
	    
	    $query = $this->db->query($sql);
	    $reult = $query->result_array();
	    
	    if($id){
    	    return $reult[0];
	    }else{
	        return $reult;
	    }
	    
	}


	public function insertnfsgroupurl($params){

		//conta quantas lojas tem para poder tratar o group
//		$sql = "SELECT store_id, MAX(case when DATE_FORMAT(NFS.data_ciclo, '%d/%m/%Y') is null then NFS.data_ciclo else DATE_FORMAT(NFS.data_ciclo, '%d/%m/%Y') end) AS data_ciclo, lote FROM nota_fiscal_servico_url_temp NFS WHERE ativo = 1 and lote = '".$params['hdnLote']."' group by store_id, lote";
		$sql = "
                SELECT 
                    store_id, 
                    MAX(NFS.data_ciclo) AS data_ciclo, 
                    lote 
                FROM 
                    nota_fiscal_servico_url_temp NFS 
                WHERE 
                    ativo = 1 
                AND 
                    lote = '".$params['hdnLote']."'
                group BY 
                    store_id, lote
		";

		$query = $this->db->query($sql);
	    $result = $query->result_array();

		$qtd = count($result);
		if($qtd > 1){
			$sucesso = true;
			foreach($result as $tratamento){

				if($sucesso == true){

					//gera um novo lote
					$novoLote = $tratamento['lote'].'_'.$tratamento['store_id'];
					
					//atualiza a tabela temp com o lote novo gerado
					$data = array(
						'lote' => $novoLote
					);
					
					$this->db->where('lote', $tratamento['lote']);
					$this->db->where('store_id', $tratamento['store_id']);
					$update1 = $this->db->update('nota_fiscal_servico_url_temp', $data);
					
					if($update1){

						//gera o insert na group
						$data['lote']          = $novoLote;
						$data['store_id']      = $tratamento['store_id'];
						$data['data_ciclo']    = $tratamento['data_ciclo'];
						
						$insert = $this->db->insert('nota_fiscal_group', $data);
						$update2 = $this->db->insert_id();

						if($update2){

							//Limpa a tabela final de NFs e sobe os valores novos da temp
							$limpa = $this->limpatabelanfsservicourl($novoLote, null);
							if($limpa){

								//Insere os dados na tabela final
								$save = $this->inseredadostabelanfsurl($novoLote,null);
								if($save){
									$this->model_payment->limpatabelanfsservicourl($novoLote,"temp");
								}else{
									$sucesso = false;
								}
							}else{
								$sucesso = false;
							}
						}else{
							$sucesso = false;
						}
					}else{
						//atualiza a tabela temp com o lote antigo
						$data = array(
							'lote' => $tratamento['lote']
						);
						
						$this->db->where('lote', $tratamento['lote']);
						$this->db->where('store_id', $tratamento['store_id']);
						$update1 = $this->db->update('nota_fiscal_servico_url_temp', $data);
						$sucesso = false;
					}

				}
			}

		}else{

//			$sql = "SELECT MAX(store_id) AS store_id, MAX(case when DATE_FORMAT(NFS.data_ciclo, '%d/%m/%Y') is null then NFS.data_ciclo else DATE_FORMAT(NFS.data_ciclo, '%d/%m/%Y') end) AS data_ciclo, lote FROM nota_fiscal_servico_url_temp NFS WHERE ativo = 1 and lote = '".$params['hdnLote']."'";
	        $sql = "
                    SELECT 
                        MAX(store_id) AS store_id, 
                        MAX(NFS.data_ciclo) AS data_ciclo, 
                        lote 
                    FROM 
                        nota_fiscal_servico_url_temp NFS 
                    WHERE 
                        ativo = 1 
                    AND 
                        lote = '".$params['hdnLote']."'
	        ";
			$query = $this->db->query($sql);
			$reult = $query->result_array();

			$saida = $reult[0];

			$data['lote']          = $params['hdnLote'];
			$data['store_id']      = $saida['store_id'];
			$data['data_ciclo']    = $saida['data_ciclo'];
			
			$insert = $this->db->insert('nota_fiscal_group', $data);
			$update2 = $this->db->insert_id();
			$sucesso = true;
			if($update2){
				//Limpa a tabela final de NFs e sobe os valores novos da temp
				$limpa = $this->limpatabelanfsservicourl($params['hdnLote'], null);
				if($limpa){
					//Insere os dados na tabela final
					$save = $this->inseredadostabelanfsurl($params['hdnLote'],null);
					if($save){
						$this->model_payment->limpatabelanfsservicourl($params['hdnLote'],"temp");
					}else{
						$sucesso = false;
					}
				}else{
					$sucesso = false;
				}
			}else{
				$sucesso = false;
			}

		}

		return $sucesso;
		
	    
	}
	
	public function updatenfsgroupurl($params){

		//conta quantas lojas tem para poder tratar o group
		$sql = "SELECT store_id, MAX(case when DATE_FORMAT(NFS.data_ciclo, '%d/%m/%Y') is null then NFS.data_ciclo else DATE_FORMAT(NFS.data_ciclo, '%d/%m/%Y') end) AS data_ciclo, lote FROM nota_fiscal_servico_url_temp NFS WHERE ativo = 1 and lote = '".$params['hdnLote']."' group by store_id, lote";
	    
		$query = $this->db->query($sql);
	    $result = $query->result_array();

		$qtd = count($result);
		if($qtd > 1){

			//verifica qual a loja que está salva na group e mantém as informações
			$sql = "select * from nota_fiscal_group where lote = '".$params['hdnLote']."'";
			$query = $this->db->query($sql);
	    	$resultGroup = $query->result_array();

			$dadosNfGroup = $resultGroup[0];

			$sucesso = true;
			foreach($result as $tratamento){

				if($sucesso == true){

					//gera um novo lote
					if($tratamento['store_id'] == $dadosNfGroup['store_id']){
						$novoLote = $tratamento['lote'];
					}else{
						$novoLote = $tratamento['lote'].'_'.$tratamento['store_id'];
					}
					
					//atualiza a tabela temp com o lote novo gerado
					$data = array(
						'lote' => $novoLote
					);
					
					$this->db->where('lote', $tratamento['lote']);
					$this->db->where('store_id', $tratamento['store_id']);
					$update1 = $this->db->update('nota_fiscal_servico_url_temp', $data);
					
					if($update1){

						if($tratamento['store_id'] == $dadosNfGroup['store_id']){

							$data = array(
								'lote' => $novoLote,
								'store_id' => $tratamento['store_id'],
								'data_ciclo' => $tratamento['data_ciclo'],
							);

							$this->db->where('id', $dadosNfGroup['id']);
							$update2 = $this->db->update('nota_fiscal_group', $data);

						}else{
							//gera o insert na group
							$data['lote']          = $novoLote;
							$data['store_id']      = $tratamento['store_id'];
							$data['data_ciclo']    = $tratamento['data_ciclo'];
							
							$insert = $this->db->insert('nota_fiscal_group', $data);
							$update2 = $this->db->insert_id();

						}
						
						if($update2){

							//Limpa a tabela final de NFs e sobe os valores novos da temp
							$limpa = $this->limpatabelanfsservicourl($novoLote, null);
							if($limpa){

								//Insere os dados na tabela final
								$save = $this->inseredadostabelanfsurl($novoLote,null);
								if($save){
									$this->model_payment->limpatabelanfsservicourl($novoLote,"temp");
								}else{
									$sucesso = false;
								}
							}else{
								$sucesso = false;
							}
						}else{
							$sucesso = false;
						}
					}else{
						//atualiza a tabela temp com o lote antigo
						$data = array(
							'lote' => $tratamento['lote']
						);
						
						$this->db->where('lote', $tratamento['lote']);
						$this->db->where('store_id', $tratamento['store_id']);
						$update1 = $this->db->update('nota_fiscal_servico_url_temp', $data);
						$sucesso = false;
					}

				}
			}
		}else{

			$sql = "SELECT MAX(store_id) AS store_id, MAX(case when DATE_FORMAT(NFS.data_ciclo, '%d/%m/%Y') is null then NFS.data_ciclo else DATE_FORMAT(NFS.data_ciclo, '%d/%m/%Y') end) AS data_ciclo, lote FROM nota_fiscal_servico_url_temp NFS WHERE ativo = 1 and lote = '".$params['hdnLote']."'";
	    
			$query = $this->db->query($sql);
			$reult = $query->result_array();

			$saida = $reult[0];

			$data = array(
				'lote' => $params['hdnLote'],
				'store_id' => $saida['store_id'],
				'data_ciclo' => $saida['data_ciclo'],
			);
			
			$this->db->where('id', $params['hdnId']);
			$update2 = $this->db->update('nota_fiscal_group', $data);
			$sucesso = true;
			if($update2){
				//Limpa a tabela final de NFs e sobe os valores novos da temp
				$limpa = $this->limpatabelanfsservicourl($params['hdnLote'], null);
				if($limpa){
					//Insere os dados na tabela final
					$save = $this->inseredadostabelanfsurl($params['hdnLote'],null);
					if($save){
						$this->model_payment->limpatabelanfsservicourl($params['hdnLote'],"temp");
					}else{
						$sucesso = false;
					}
				}else{
					$sucesso = false;
				}
			}else{
				$sucesso = false;
			}

		}

		return $sucesso;

		
	    
	    
	}
	
	public function limpatabelanfsservicourl($lote, $temp = null){
	    
	    if($temp){
	        $tabela = "nota_fiscal_servico_url_temp";
	    }else{
	        $tabela = "nota_fiscal_servico_url";
	    }
	    
	    $sql = "delete from $tabela where lote = '$lote'";
	    return $this->db->query($sql);
	    
	}
	
	public function inseredadostabelanfsurl($lote, $temp = null){
	    
	    if($temp){
	        $tabelaInsert = "nota_fiscal_servico_url_temp";
	        $tabelaSelect = "nota_fiscal_servico_url";
	    }else{
	        $tabelaInsert = "nota_fiscal_servico_url";
	        $tabelaSelect = "nota_fiscal_servico_url_temp";
	    }
	    
	    $sql = "INSERT INTO $tabelaInsert (lote, store_id, data_ciclo, url, data_criacao, ativo, invoice_emission_date, invoice_number, invoice_amount_total, invoice_amount_irrf, param_mkt_ciclo_fiscal_id)
                SELECT lote, store_id, data_ciclo, url, data_criacao, ativo, invoice_emission_date, invoice_number, invoice_amount_total, invoice_amount_irrf, param_mkt_ciclo_fiscal_id FROM $tabelaSelect WHERE lote = '$lote'";
	    return $this->db->query($sql);
	    
	    
	}


    public function listStatements()
    {
        $sql = "select id, store_id, order_ownid from gateway_pendencies_statements order by order_id asc";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    
    public function fixStatement($statement_id, $numero_marketplace)
    {
        $sql = "update gateway_pendencies_statements set order_id = '".$numero_marketplace."' where id=".$statement_id;
        return $this->db->query($sql);
    }


     //braun
    public function paymentReportByCycle($ano_mes = null, $lote = null, $gateway_id = null): ?array
    {
        //caso ciclo nulo pegar o ultimo
        if (!$ano_mes)
        {
            $cycle      = $this->getPaymentCycles(1)[0];
            $ano_mes    = $cycle['ano_mes'];
            $lote       = $cycle['lote'];
        }

		if (!$gateway_id)
		{
			$gateway_id = 2;
		}

        $sql = "
                SELECT 
                    c.id
                    ,c.lote
                    ,mkt.data_pagamento
                    ,c.ano_mes
                    ,c.status_repasse AS status_conciliacao
                    ,paid_status_responsible
                    ,c.status AS status_conciliacao_text
                    ,r.store_id
                    ,r.name
                    ,ROUND(r.valor_seller, 2) AS valor_seller
                    ,r.status_repasse
                    ,r.refund
                    ,bs.nome as status_text
					,LPAD(mkt.data_inicio, 2, '0') as data_inicio
                    ,LPAD(mkt.data_fim, 2, '0') as data_fim
					,round((gb.available / 100), 2) AS balance
                    ,(SELECT round(SUM(valor_seller), 2) FROM repasse WHERE store_id = r.store_id AND conciliacao_id = r.conciliacao_id) AS total_repasse
                FROM 
                    repasse r
                    inner JOIN conciliacao c ON c.id=r.conciliacao_id
                    left JOIN billet_status bs ON c.status_repasse = bs.id
					LEFT JOIN param_mkt_ciclo mkt ON c.param_mkt_ciclo_id = mkt.id
					left JOIN gateway_balance gb ON gb.store_id = r.store_id AND gb.gateway_id = ".$gateway_id."
                WHERE
                    c.ano_mes = '".$ano_mes."'
                AND
                    c.lote = '".$lote."'
                AND
                    r.valor_seller <> 0
                AND
                    c.ativo = 1
                ORDER BY 
                    r.name asc
                ";

        $query = $this->db->query($sql);
        return $query->result_array();
    }
	

    //braun
    public function getPaymentCycles($limit = null)
    {
        $sql = "
                select 
	                c.ano_mes
                    ,c.lote 
                     ,LPAD(mkt.data_inicio, 2, '0') AS data_inicio
                    ,LPAD(mkt.data_fim, 2, '0') AS data_fim
                from 
	                conciliacao c 
	                inner join repasse r on c.lote=r.lote
	                LEFT JOIN param_mkt_ciclo mkt ON c.param_mkt_ciclo_id = mkt.id
                group by 
                    c.lote
                order by 
                    (STR_TO_DATE(CONCAT(SUBSTRING_INDEX(c.ano_mes, '-', -1),'-',SUBSTRING_INDEX(c.ano_mes, '-', 1),'-01'), '%Y-%m-%d')) DESC,
                    c.id desc
                ";

        if ($limit)
        {
            $sql .= " LIMIT ".$limit;
        }

        $query = $this->db->query($sql);
        return $query->result_array();
    }

	public function buscapedidossemmdr($id = null){

		$where = "";

		if($id <> null){
			$where = " and o.id = $id";
		}

		$sql = "select 	o.id,
						o.numero_marketplace,
						left(o.numero_marketplace,13) as numero_marketplace_api,
						op.id as op_id,
						op.transaction_id,
						op.payment_id,
						op.taxa_cartao_credito 
				from orders o 
				inner join orders_payment op on o.id = op.order_id 
				where op.taxa_cartao_credito  is null $where";

		$query = $this->db->query($sql);
		return $query->result_array();
				
	}

	public function updatemdrorderspayment($order_id = null, $mdr = null){

		//if (empty($order_id) || empty($mdr))
		if (empty($order_id))
            return false;

        $this->db->where('order_id', $order_id);
        return $this->db->update('orders_payment', array('taxa_cartao_credito' => $mdr));

	}

    //braun
    public function getBalanceTransferHistory($gateway_id = null)
    {
		$gateway_sql = ($gateway_id) ? " where gp.gateway_id = ".$gateway_id." " : "";

        $sql = "
                SELECT 
                    s.id AS store_id
                    ,s.name AS store_name
                    ,(select SUM(amount) FROM gateway_pendencies WHERE store_id = s.id) AS total_advanced
                    ,(select SUM(amount) FROM gateway_pendencies WHERE STATUS IN ('r') AND store_id = s.id) AS total_returned
                    ,(select SUM(amount) FROM gateway_pendencies WHERE STATUS = 't' AND store_id = s.id) AS total_pendency
                    ,gbal.available AS balance,
                     pg.name as gateway_name
                FROM
                    gateway_pendencies gp
                    INNER JOIN stores s ON s.id=gp.store_id
                    INNER JOIN gateway_balance gbal ON gp.store_id=gbal.store_id
                    inner join payment_gateways pg on gp.gateway_id = pg.id 
                ".$gateway_sql."
                GROUP BY
                    gp.store_id
                ORDER BY
                    s.name asc
            ";
        
        $query = $this->db->query($sql);
        return $query->result_array();
    }


    //braun
    public function getPendencyData($store_id)
    {
        $sql = "
                SELECT
                    DATE_FORMAT(date_insert, '%d/%m/%Y') AS advance
                    ,DATE_FORMAT(date_edit, '%d/%m/%Y') AS returned	
                    ,CONCAT('R$ ', FORMAT(amount / 100, 2, 'de_DE')) AS amount
                    ,status
                FROM
                    gateway_pendencies
                WHERE
                    store_id =?
                ";

        $query = $this->db->query($sql, [$store_id]);
        return $query->result_array();
    }

		public function getAnticipationTransferOrder($order_id = null){
			$sql = "SELECT * FROM anticipation_transfer WHERE order_id = ?";
			$query = $this->db->query($sql, array($order_id));
			if($query->num_rows() > 0){
				return "SIM";
			}
			return "NÃO";
		}


        public function getViableGatewayTransfers()
        {
            $sql = "
                    SELECT
                        DISTINCT CONCAT(YEAR(gt.date_insert),'-',LPAD(MONTH(gt.date_insert), 2, '0')) AS ano_mes
                    FROM
                        gateway_transfers gt
                    where
                        gt.STATUS = 'COMPLETED'
                    AND
                        gt.result_number >= 200 
                    AND
                        gt.result_number < 300
                    ORDER BY
                        gt.date_insert desc
                    ";

            $query = $this->db->query($sql);
            return $query->result_array();
        }


        public function getPeriodGatewayTransfers($ano_mes = null): ?array
        {
             $sql = "
                    SELECT 
                        gt.id                    
                        ,CONCAT(UPPER(SUBSTRING(pg.name,1,1)),LOWER(SUBSTRING(pg.name,2))) as gateway_name
						,CONCAT(
                            IFNULL((SELECT s.id FROM gateway_subaccounts gs INNER JOIN stores s ON gs.store_id = s.id WHERE gs.gateway_account_id = gt.sender_id), '0'), ' - ',
                            IFNULL((SELECT s.name FROM gateway_subaccounts gs INNER JOIN stores s ON gs.store_id = s.id WHERE gs.gateway_account_id = gt.sender_id), 'Marketplace')
                        ) AS sender
                        ,CONCAT(
                            IFNULL((SELECT s.id FROM gateway_subaccounts gs INNER JOIN stores s ON gs.store_id = s.id WHERE gs.gateway_account_id = gt.receiver_id), '0'), ' - ',
                            IFNULL((SELECT s.name FROM gateway_subaccounts gs INNER JOIN stores s ON gs.store_id = s.id WHERE gs.gateway_account_id = gt.receiver_id), 'Marketplace') 
                        ) AS receiver
                        ,CONCAT('R$ ', Replace(Replace(Replace(Format(round((gt.amount / 100),2), 2), '.', '|'), ',', '.'), '|', ',')) as amount    
                        ,DATE_FORMAT(gt.date_insert, '%d/%m/%y às %H:%m') as datetime
                    FROM 
                        gateway_transfers gt
                        INNER JOIN payment_gateways pg ON pg.id = gt.gateway_id
                    WHERE
                        CONCAT(year(gt.date_insert),'-',LPAD(MONTH(gt.date_insert),2,'0')) = ?
                    AND
                        gt.STATUS = 'COMPLETED'
                    AND
                        gt.result_number >= 200 
                    AND
                        gt.result_number < 300
                    ORDER BY
                        gt.date_insert desc, receiver asc
                    ";

            $query = $this->db->query($sql, [$ano_mes]);
            return $query->result_array();
        }

		public function buscapedidosparamdr($numeroMarketplace = null){

			$where = "";
	
			if($numeroMarketplace <> null){
				$where = " and o.numero_marketplace = '$numeroMarketplace'";
			}
	
			$sql = "select 	o.id,
							o.numero_marketplace,
							left(o.numero_marketplace,13) as numero_marketplace_api,
							op.id as op_id,
							op.transaction_id,
							op.payment_id,
							op.taxa_cartao_credito ,
							case when o.store_id < 10 then concat('DEC00',o.store_id) else case when o.store_id < 100 then concat('DEC0',o.store_id) else concat('DEC',o.store_id) end end as idVtex
					from orders o 
					inner join orders_payment op on o.id = op.order_id 
					where 1=1 $where limit 1";
	
			$query = $this->db->query($sql);
			$saida = $query->result_array();
			if($saida){
				return $saida[0];
			}else{
				return false;
			}
					
		}

		public function insertupdategetnetsaldos($array){


			/*$sql = "select id, count(*) as qtd from getnet_saldos
					where store_id = '".$array['store_id']."' and subseller_id = '".$array['subseller_id']."' and data_saldo = '".$array['data_saldo']."' and tipo_saldo = '".$array['tipo_saldo']."'";
			$query = $this->db->query($sql);
			$retorno =  $query->row();*/


			$sql = "insert into getnet_saldos (store_id, subseller_id, tipo_saldo, data_saldo, valor_disponivel) values 
				(
					".$array['store_id'].", 
					".$array['subseller_id'].",
					'".$array['tipo_saldo']."',
					'".$array['data_saldo']."',
					'".$array['valor_disponivel']."'
				)";

			$this->db->query($sql);
			return $this->db->insert_id();
		}

		public function deletegetnetsaldos($subseller_id){

			$sql = "delete from getnet_saldos where subseller_id ='$subseller_id'";
	    	return $this->db->query($sql);

		}

    public function getByOrderId(int $order_id): array
    {
        return $this->db->get_where('orders_payment', array('order_id' => $order_id))->result_array();
    }

    public function getNfsByInvoiceNumberAndStoreIdAndLote(string $invoice_number, int $store_id, string $lote): ?array
    {
        return $this->db->get_where('nota_fiscal_servico_url', array(
            'store_id'       => $store_id,
            'invoice_number' => $invoice_number,
            'lote'           => $lote
        ))->row_array();
    }

    /**
     * @param int|array $store_id
     * @param string $lote
     * @return bool
     */
    public function checkIfExistNfsUrlByStoreIdAndLote($store_id, string $lote): bool
    {
        if (is_array($store_id) && !empty($store_id)) {
            $this->db->where_in('store_id', $store_id);
        } else if (intVal($store_id) > 0) {
            $this->db->where('store_id', $store_id);
        }

        return $this->db->where('lote', $lote)
            ->where('url IS NOT NULL', NULL, FALSE)
            ->get('nota_fiscal_servico_url')->num_rows() > 0;
    }

    /**
     * @param int|array $store_id
     * @param string $lote
     * @return bool
     */
    public function checkIfExistNfsInvoiceNumberByStoreIdAndLote($store_id, string $lote): bool
    {
        if (is_array($store_id) && !empty($store_id)) {
            $this->db->where_in('store_id', $store_id);
        } else if (intVal($store_id) > 0) {
            $this->db->where('store_id', $store_id);
        }
        return $this->db->where('lote', $lote)
            ->where('invoice_number IS NOT NULL', NULL, FALSE)
            ->get('nota_fiscal_servico_url')->num_rows() > 0;
    }

    /**
     * @param string $lote
     * @param int $store_id
     * @param string $cycle
     * @return bool
     */
    public function checkIfExistNfsGroupByLoteAndStoreIdAndCycle(string $lote, int $store_id, string $cycle): bool
    {
        return $this->db->where('lote', $lote)
            ->where(array(
                'lote' => $lote,
                'store_id' => $store_id,
                'data_ciclo' => $cycle
            ))
            ->get('nota_fiscal_group')->num_rows() > 0;
    }

}
