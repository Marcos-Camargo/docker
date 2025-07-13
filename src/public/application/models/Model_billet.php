<?php
/*
SW Serviços de Informática 2019
 
Model de Acesso ao BD para Recebimentos
*/

/**
 * @property Model_orders_conciliation_installments $model_orders_conciliation_installments
 */
class Model_billet extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_orders_conciliation_installments');

        $this->orders_precancelled_to_zero  = ($this->model_settings->getStatusbyName('orders_precancelled_to_zero') == 1) ? true : false;        
    }


    /* get the orders data */
    public function getBilletsData($id = null)
    {

        $sql = "select 
                		b.id,
                        i.name as marketplace,
                        b.id_boleto_iugu,
                        b.data as data_geracao,
                        b.valor_total,
                        sb.nome as status_billet,
                        si.nome as status_iugu,
                        b.status_iugu as status_iugu_id,
                        b.status_split as status_split_payment
                from billet b
                inner join billet_status sb on sb.id = b.status_id
                inner join billet_status si on si.id = b.status_iugu
                inner join integrations i on i.id = b.integrations_id";

        if ($id <> "") {
            $sql .= " where b.id = $id ";
        }

        $sql .= " order by b.id ";
        $query = $this->db->query($sql);
        return (false !== $query) ? $query->result_array() : false;
    }

    public function getBilletsDataId($id = null)
    {

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
        return (false !== $query) ? $query->result_array() : false;
    }


    public function getMktPlacesData()
    {

        $sql = "SELECT id_mkt as id, descloja AS mkt_place , apelido
                FROM stores_mkts_linked
                ORDER BY descloja, id_mkt ";
        $query = $this->db->query($sql);
        return (false !== $query) ? $query->result_array() : false;

    }

    public function getMktPlacesDataID($id)
    {

        $sql = "SELECT id_mkt as id, descloja AS mkt_place, apelido
                FROM stores_mkts_linked 
                where id_mkt = $id
                ORDER BY descloja, id_mkt ";
        $query = $this->db->query($sql);
        return (false !== $query) ? $query->result_array() : false;

    }

    public function getOrdersData($data = null)
    {

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

        if ($data['mktPlace'] <> "") {
            $sql .= " and i.id = " . $data['mktPlace'];
        }

        if ($data['dtInicio'] <> "0") {
            $sql .= " and o.date_time >= '" . $data['dtInicio'] . "'";
        }

        if ($data['dtFim'] <> "0") {
            $sql .= " and o.date_time <= '" . $data['dtFim'] . "'";
        }

        if ($data['retirados'] <> "0") {
            $sql .= " and o.id not in (" . $data['retirados'] . ")";
        }

        $sql .= " ORDER BY o.id ";

        $query = $this->db->query($sql);
        return (false !== $query) ? $query->result_array() : false;

    }

    public function getOrdersAddedData($data = null)
    {

        $sql = "select distinct
                    	o.id as id,
                		o.origin as mktplace,
                        o.bill_no as num_pedido,
                        o.gross_amount as valor
                from orders o
                where 1=1 ";

        if ($data['idOrders'] <> "") {
            $sql .= " and o.id in (" . $data['idOrders'] . ")";
        }

        $sql .= " ORDER BY o.id ";

        $query = $this->db->query($sql);
        return (false !== $query) ? $query->result_array() : false;

    }

    public function getSumOrdersAdded($idOrders)
    {

        $sql = "select sum(o.gross_amount) as valor
                from orders o
                where  o.id in (" . $idOrders . ")";

        $query = $this->db->query($sql);
        return (false !== $query) ? $query->result_array() : false;

    }

    public function insertBillet($inputs)
    {

        $valor = explode(" ", $inputs['txt_valor_total']);
        $valor = $valor[1];

        $data['valor_total'] = $valor;
        $data['status_id'] = 1;
        $data['status_iugu'] = 2;
        $data['integrations_id'] = $inputs['slc_mktplace'];

        $insert = $this->db->insert('billet', $data);
        $order_id = $this->db->insert_id();
        return ($order_id) ? $order_id : false;

    }

    public function insertBilletOrder($ArrayBilletOrder)
    {

        $insert = $this->db->insert('billet_order', $ArrayBilletOrder);
        $order_id = $this->db->insert_id();
        return ($order_id) ? $order_id : false;

    }

    public function gerarBoletoIUGU($id)
    {

        $retorno['ret'] = true;
        $retorno['num_billet'] = "";
        $retorno['url'] = "";

        return $retorno;

    }

    public function atualizaStatus($id, $statusBoleto, $statusIUGU, $numBoleto, $url)
    {

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
        get_instance()->log_data('billet', 'update', json_encode($data), "I");

        return true;

    }

    public function getConciliacaoGridData($lote = null, $storeKey = null)
    {
        // Adiciona o filtro pelo store_id, se fornecido
        $joinCS = "";
        $whereLoja = "";

        if ($storeKey !== null) {
            $joinCS = "LEFT JOIN conciliacao_sellercenter CSC ON CSC.lote = CON.lote ";
            $whereLoja .= "AND CSC.store_id = " . $this->db->escape($storeKey);
        }

        $sql = "SELECT distinct
                    CON.lote,
                    CON.status,
                    CON.status_repasse,
                    CON.id AS id_con,
                    CON.data_criacao,
                    CON.ano_mes,
                    Case when CON.integ_id = '999' then 'Manual' else SML.descloja end as descloja,
                    PMC.data_inicio,
                    PMC.data_fim,
                    CON.integ_id,
                    CON.integ_ids_adicionais,
                    CON.param_mkt_ciclo_ids_adicionais,
                    Case when CON.integ_id = '999' then 'Manual' else SML.apelido end as apelido,
                    PMC.id AS id_ciclo,
                    Case when CON.integ_id = '999' then 999 else SML.id_mkt end as id_mkt,
                    CASE WHEN IR.conciliacao_id IS NOT NULL THEN 'Conciliação Paga' ELSE 'Conciliação não paga' END AS pagamento_conciliacao,
                    CASE WHEN IR.conciliacao_id IS NOT NULL THEN IR.conciliacao_id ELSE 0 END AS conciliacao_id
                FROM conciliacao CON
                LEFT JOIN stores_mkts_linked SML ON SML.id_mkt = CON.integ_id
                LEFT JOIN param_mkt_ciclo PMC ON PMC.id = CON.param_mkt_ciclo_id
                LEFT JOIN (SELECT DISTINCT conciliacao_id FROM iugu_repasse) IR ON IR.conciliacao_id = CON.id
                $joinCS
                WHERE CON.ativo = 1 $whereLoja";

        if ($lote <> null) {
            $sql .= "and CON.lote = '$lote'";
        }

        $sql .= " order by CON.id desc";

        $query = $this->db->query($sql);

        return (false !== $query) ? $query->result_array() : false;
    }


    public function getCicloData()
    {


        $sql = "SELECT PMC.id, INTG.descloja AS mkt_place, CASE WHEN PMC.tipo_ciclo = 'dia_util' THEN 'Dias Úteis' WHEN PMC.tipo_ciclo = 'dia_corrido' THEN 'Dias Corridos' ELSE 'Dia Fixo do Mês' END AS tipo_ciclo, PMC.valor_ciclo 
                FROM param_mkt_ciclo PMC
                INNER JOIN stores_mkts_linked INTG ON INTG.id_mkt = PMC.integ_id 
                where PMC.ativo = 1 order by PMC.id";
	    
	    $query = $this->db->query($sql);
	    return (false !== $query) ? $query->result_array() : false;
	    
	    
	}
	
	public function salvarArquivoB2WTable($input, $arquivo){

	    $data['lote'] = $input['hdnLote'];
	    $data['nome_arquivo'] = $input['arquivo'];
	    $data['marca'] = str_replace( "\"", "", utf8_encode($arquivo[0]));
	    $data['nome_fantasia'] = str_replace( "\"", "", utf8_encode($arquivo[1]));
	    $data['data_pedido'] = str_replace( "\"", "", utf8_encode($arquivo[2]));
	    $data['data_pagamento'] = str_replace( "\"", "", utf8_encode($arquivo[3]));
	    $data['data_estorno'] = str_replace( "\"", "", utf8_encode($arquivo[4]));
	    $data['data_liberacao'] = str_replace( "\"", "", utf8_encode($arquivo[5]));
	    $data['data_prevista_pgto'] = str_replace( "\"", "", utf8_encode($arquivo[6]));
	    $data['lancamento'] = str_replace( "\"", "", utf8_encode($arquivo[7]));
	    $data['ref_pedido'] = str_replace( "\"", "", utf8_encode($arquivo[8]));
	    $data['entrega'] = str_replace( "\"", "", utf8_encode($arquivo[9]));
	    $data['tipo'] = str_replace( "\"", "", utf8_encode($arquivo[10]));
	    $data['status'] = str_replace( "\"", "", utf8_encode($arquivo[11]));
	    $data['valor'] = str_replace( "\"", "", utf8_encode($arquivo[12]));
	    $data['parcela'] = str_replace( "\"", "", utf8_encode($arquivo[13]));
	    $data['meio_pgto'] = str_replace( "\"", "", utf8_encode($arquivo[14]));
	    $data['modelo_financeiro'] = str_replace( "\"", "", utf8_encode($arquivo[15]));
	    
	    
	    $insert = $this->db->insert('conciliacao_b2w', $data);
	    $order_id = $this->db->insert_id();
	    return ($order_id) ? $order_id : false;
	    
	}
	
	public function salvarArquivoViaVarejoTable($input, $arquivo){
	    
	    $data['lote'] = $input['hdnLote'];
	    $data['nome_arquivo'] = $input['arquivo'];
	    
		$data['numero_do_pedido'] = $arquivo[1]; // OK
        $data['id_entrega'] = $arquivo[2]; 
        $data['tipo_da_transacao'] = $arquivo[3]; // OK
        $data['marca'] = $arquivo[4]; 
        $data['data_pedido_incluido'] = $arquivo[5]; 
        $data['data_pedido_entregue'] = $arquivo[6]; 
        $data['data_liberacao'] = $arquivo[7]; 
        $data['data_prevista_repasse'] = $arquivo[8]; 
        $data['data_repasse'] = $arquivo[9]; 
        $data['data_antecipacao'] = $arquivo[10]; 
        $data['numero_liquidacao'] = $arquivo[11]; 
        $data['sku_marketplace'] = $arquivo[12]; 
        $data['sku_lojista'] = $arquivo[13]; 
        $data['valor_produto_sem_desconto'] = $arquivo[14]; 
        $data['desconto_onus_via'] = $arquivo[15]; 
        $data['desconto_onus_lojista'] = $arquivo[16]; 
        $data['valor_frete'] = $arquivo[17]; // OK
        $data['tipo_do_frete'] = $arquivo[18]; 
        $data['frete_promocional_onus_via'] = $arquivo[19]; 
        $data['frete_promocional_onus_lojista'] = $arquivo[20]; 
        $data['valor_da_transacao'] = $arquivo[21]; 
        $data['comissao_contratual'] = $arquivo[22]; 
        $data['comissao_aplicada_porcentagem'] = $arquivo[23]; 
        $data['comissao_aplicada_reais'] = $arquivo[24]; 
        $data['total_de_parcelas'] = $arquivo[25]; // OK
        $data['parcela_atual'] = $arquivo[26]; 
        $data['valor_da_parcela'] = $arquivo[27]; // OK
        $data['valor_bruto_repasse'] = $arquivo[28]; 
        $data['valor_antecipacao'] = $arquivo[29]; 
        $data['taxa_antecipacao'] = $arquivo[30]; 
        $data['valor_liquido_repasse'] = $arquivo[31]; 
        $data['motivo_ajuste'] = $arquivo[32]; 
        $data['observacao'] = $arquivo[33]; 
        $data['origem_repasse'] = $arquivo[34]; 
        $data['forma_de_pagamento'] = $arquivo[35]; // OK
        $data['tipo_campanha'] = $arquivo[36]; 
        $data['ajuste_realizado_outro_ciclo'] = $arquivo[37]; 
        $data['valor_ajuste_ciclos_anteriores'] = $arquivo[38]; 
        $data['data_ajuste'] = $arquivo[39]; 
        $data['nf_repasse'] = $arquivo[40]; 
        $data['nf_cliente'] = $arquivo[41]; 
        $data['descricao_produto'] = $arquivo[42]; 
        $data['departamento'] = $arquivo[43]; 
        $data['categoria'] = $arquivo[44];                
												  
	    $data['usuario'] = $_SESSION['username'];
	    
	    $insert = $this->db->insert('conciliacao_viavarejo', $data);
	    $order_id = $this->db->insert_id();
	    return ($order_id) ? $order_id : false;
	    
	}

    public function salvarArquivoViaVarejoTableBoleto($input, $arquivo){
	    
	    $data['lote'] = $input['hdnLote'];
	    $data['nome_arquivo'] = $input['arquivo'];
	    
	    $data['ciclo_da_transacao'] = $arquivo[0];
	    $data['ciclo_do_vencimento'] = $arquivo[1];
	    $data['data_de_vencimento'] = $arquivo[2];
	    $data['data_do_pedido'] = $arquivo[3];
        $data['data_envio_pedido'] = $arquivo[4];

	    $data['tipo_da_transacao'] = $arquivo[5];
	    $data['numero_do_pedido'] = $arquivo[6];
	    $data['sequencial_do_item'] = $arquivo[7];
	    $data['descricao_do_produto'] = $arquivo[8];
	    $data['nsu'] = $arquivo[9];
	    $data['forma_de_pagamento'] = $arquivo[10];
	    $data['total_de_parcelas'] = $arquivo[11];
	    $data['numero_da_parcela'] = $arquivo[12];
	    $data['total_do_pedido'] = $arquivo[13];
	    $data['valor_da_transacao'] = $arquivo[14];
	    $data['valor_da_comissao'] = $arquivo[15];
	    $data['valor_do_repasse'] = $arquivo[16];

        $data['valor_do_produto'] = $arquivo[17];

	    $data['valor_do_frete'] = $arquivo[18];
	    $data['tipo_do_frete'] = $arquivo[19];
	    $data['usuario_Responsavel'] = $arquivo[20];
	    $data['motivo'] = $arquivo[21];
	    $data['data_de_notificacao_de_envio'] = $arquivo[22];
	    $data['origem'] = $arquivo[23];
	    $data['usuario'] = $_SESSION['username'];
	    
	    $insert = $this->db->insert('conciliacao_viavarejo', $data);
	    $order_id = $this->db->insert_id();
	    return ($order_id) ? $order_id : false;
	    
	}
	
	public function salvarArquivoMLTable($input, $arquivo){
	    
	    $data['lote'] = $input['hdnLote'];
	    $data['nome_arquivo'] = $input['arquivo'];
	    
	    $data['date'] = $arquivo[1];
	    $data['source_id'] = $arquivo[2];
	    $data['external_reference'] = $arquivo[3];
	    $data['record_type'] = $arquivo[4];
	    $data['description'] = $arquivo[5];
	    $data['net_credit_amount'] = $arquivo[6];
	    $data['net_debit_amount'] = $arquivo[7];
	    $data['gross_amount'] = $arquivo[8];
	    $data['seller_amount'] = $arquivo[9];
	    $data['mp_fee_amount'] = $arquivo[10];
	    $data['financing_fee_amount'] = $arquivo[11];
	    $data['shipping_fee_amount'] = $arquivo[12];
	    $data['taxes_amount'] = $arquivo[13];
	    $data['coupon_amount'] = $arquivo[14];
	    $data['installments'] = $arquivo[15];
	    $data['payment_method'] = $arquivo[16];
	    $data['tax_detail'] = $arquivo[17];
	    $data['tax_amount_telco'] = $arquivo[18];
	    $data['transaction_approval_date'] = $arquivo[19];
	    $data['pos_id'] = $arquivo[20];
	    $data['pos_name'] = $arquivo[21];
	    $data['external_pos_id'] = $arquivo[22];
	    $data['store_id'] = $arquivo[23];
	    $data['store_name'] = $arquivo[24];
	    $data['external_store_id'] = $arquivo[25];
	    $data['currency'] = $arquivo[26];
	    $data['taxes_disaggregated'] = $arquivo[27];
	    $data['shipping_id'] = $arquivo[28];
	    $data['shipment_mode'] = $arquivo[29];
	    $data['order_id'] = $arquivo[30];
	    $data['pack_id'] = $arquivo[31];
	    $data['metadata'] = $arquivo[32];
	    $data['refund_id'] = $arquivo[33];
	    
	    $data['usuario'] = $_SESSION['username'];
	    
	    $insert = $this->db->insert('conciliacao_mercadolivre', $data);
	    $order_id = $this->db->insert_id();
	    return ($order_id) ? $order_id : false;
	    
	}
	
	public function salvarArquivoMLTableCSV($input, $arquivo){
	    
	    $data['lote'] = $input['hdnLote'];
	    $data['nome_arquivo'] = $input['arquivo'];
	    
	    $data['date'] = $arquivo[1];
	    $data['source_id'] = $arquivo[2];
	    $data['external_reference'] = $arquivo[3];
	    $data['record_type'] = $arquivo[4];
	    $data['description'] = $arquivo[5];
	    $data['net_credit_amount'] = $arquivo[6];
	    $data['net_debit_amount'] = $arquivo[7];
	    $data['gross_amount'] = $arquivo[8];
	    $data['seller_amount'] = $arquivo[9];
	    $data['mp_fee_amount'] = $arquivo[10];
	    $data['financing_fee_amount'] = $arquivo[11];
	    $data['shipping_fee_amount'] = $arquivo[12];
	    $data['taxes_amount'] = $arquivo[13];
	    $data['coupon_amount'] = $arquivo[14];
	    $data['installments'] = $arquivo[15];
	    $data['payment_method'] = $arquivo[16];
	    $data['tax_detail'] = $arquivo[17];
	    $data['tax_amount_telco'] = $arquivo[18];
	    $data['transaction_approval_date'] = $arquivo[19];
	    $data['pos_id'] = $arquivo[20];
	    $data['pos_name'] = $arquivo[21];
	    $data['external_pos_id'] = $arquivo[22];
	    $data['store_id'] = $arquivo[23];
	    $data['store_name'] = $arquivo[24];
	    $data['external_store_id'] = $arquivo[25];
	    $data['currency'] = $arquivo[26];
	    $data['taxes_disaggregated'] = $arquivo[27];
	    $data['shipping_id'] = $arquivo[28];
	    $data['shipment_mode'] = $arquivo[29];
	    $data['order_id'] = $arquivo[30];
	    $data['pack_id'] = $arquivo[31];
	    $data['metadata'] = $arquivo[32];
	    $data['refund_id'] = $arquivo[33];
	    
	    $data['usuario'] = $_SESSION['username'];
	    
	    $insert = $this->db->insert('conciliacao_mercadolivre', $data);
	    $order_id = $this->db->insert_id();
	    return ($order_id) ? $order_id : false;
	    
	}
	
	public function salvarArquivoCarrefourTable($input, $arquivo){
	    
	    $data['lote'] = $input['hdnLote'];
	    $data['nome_arquivo'] = $input['arquivo'];
	    
	    $data['n_do_pedido'] = $arquivo[1];
	    $data['data_de_criacao'] = $arquivo[2];
	    $data['status_do_pedido'] = $arquivo[3];
	    $data['subtotal_dos_itens'] = $arquivo[4];
	    $data['despesas_de_envio_com_imposto'] = $arquivo[5];
	    $data['preco_total_com_imposto'] = $arquivo[6];
	    $data['comissao'] = $arquivo[7];
	    $data['impostos_sobre_a_comissao'] = $arquivo[8];
	    $data['montante_reembolsado_a_loja'] = $arquivo[9];
	    $data['quantidade'] = $arquivo[10];
	    $data['produto'] = $arquivo[11];
	    $data['sku_do_produto'] = $arquivo[12];
	    $data['sku_da_oferta'] = $arquivo[13];
	    $data['data_de_confirmacao_do_pagamento'] = $arquivo[14];
	    $data['Metodo_de_envio'] = $arquivo[15];
	    $data['sobrenome_no_endereco_de_entrega'] = $arquivo[16];
	    $data['nome_no_endereco_de_entrega'] = $arquivo[17];
	    $data['Empresa_no_endereco_de_entrega'] = $arquivo[18];
	    $data['rua_1_do_endereco_de_entrega'] = $arquivo[19];
	    $data['rua_2_do_endereco_de_entrega_2'] = $arquivo[20];
	    $data['complemento_do_endereco_de_entrega'] = $arquivo[21];
	    $data['codigo_postal_do_endereco_de_entrega'] = $arquivo[22];
	    $data['cidade_do_endereco_de_entrega'] = $arquivo[23];
	    $data['estado_do_endereco_de_entrega'] = $arquivo[24];
	    $data['pais_do_endereco_de_entrega'] = $arquivo[25];
	    $data['sobrenome_do_endereco_de_faturamento'] = $arquivo[26];
	    $data['nome_do_endereco_de_faturamento'] = $arquivo[27];
	    $data['empresa_do_endereco_de_faturamento'] = $arquivo[28];
	    $data['rua_1_do_endereco_de_faturamento'] = $arquivo[29];
	    $data['rua_2_do_endereco_de_faturamento'] = $arquivo[30];
	    $data['complemento_do_endereco_de_faturamento'] = $arquivo[31];
	    $data['codigo_postal_do_endereco_de_faturamento'] = $arquivo[32];
	    $data['cidade_do_endereco_de_faturamento'] = $arquivo[33];
	    $data['estado_do_endereco_de_faturamento'] = $arquivo[34];
	    $data['pais_do_endereco_de_faturamento'] = $arquivo[35];
	    $data['subtotal_dos_artigos'] = $arquivo[36];
	    $data['impostos_dos_artigos'] = $arquivo[37];
	    $data['despesas_de_envio_sem_imposto'] = $arquivo[38];
	    $data['impostos_sobre_despesas_de_envio'] = $arquivo[39];
	    $data['preco_total_sem_imposto'] = $arquivo[40];
	    $data['impostos_sobre_o_preco_total'] = $arquivo[41];
	    $data['moeda'] = $arquivo[42];
	    
	    $data['usuario'] = $_SESSION['username'];
	    
	    $insert = $this->db->insert('conciliacao_carrefour', $data);
	    $order_id = $this->db->insert_id();
	    return ($order_id) ? $order_id : false;
	    
	}
	
	public function salvarArquivoCarrefourTableXls($input, $arquivo){
	
	$data['lote'] = $input['hdnLote'];
	$data['nome_arquivo'] = $input['arquivo'];
	
	/*$data['tipo_de_transacao'] = $arquivo[1];
	$data['ajuste_manual'] = $arquivo[2];
	$data['id_loja'] = $arquivo[3];
	$data['loja'] = $arquivo[4];
	$data['periodo'] = $arquivo[5];
	$data['ciclo_seller'] = $arquivo[6];
	$data['status_do_ciclo'] = $arquivo[7];
	$data['n_do_pedido'] = $arquivo[8];
	$data['total_do_pedido'] = $arquivo[9];
	$data['status_do_pedido'] = $arquivo[10];
	$data['realizado_em'] = $arquivo[11];
	$data['pago_em'] = $arquivo[12];
	$data['entregue_em'] = $arquivo[13];
	$data['devolvido_cancelado_em'] = $arquivo[14];
	$data['motivo_da_devolucao_cancelamento'] = $arquivo[15];
	$data['data_de_processamento_de_incidente'] = $arquivo[16];
	$data['notivo_do_incidente'] = $arquivo[17];
	$data['SKU'] = $arquivo[18];
	$data['categoria'] = $arquivo[19];
	$data['produto'] = $arquivo[20];
	$data['valor_unitario'] = $arquivo[21];
	$data['valor_unitario_promocional'] = $arquivo[22];
	$data['frete_unitario'] = $arquivo[23];
	$data['quantidade'] = $arquivo[24];
	$data['total'] = $arquivo[25];
	$data['total_promocional'] = $arquivo[26];
	$data['grade_associada'] = $arquivo[27];
	$data['comissao_percentual'] = $arquivo[28];
	$data['comissao'] = $arquivo[29];
	$data['saldo'] = $arquivo[30];*/
	
	$data['data_criacao']   = \DateTime::createFromFormat('d/m/Y H:i:s', str_replace("-", "", $arquivo[1]))->format('Y-m-d H:i:s');
	$data['data_recebida']  = $arquivo[2];
	$data['data_transacao'] = $arquivo[3];
	$data['loja']           = $arquivo[4];
	$data['n_do_pedido']    = $arquivo[5];
	$data['numero_fatura']  = $arquivo[6];
	$data['numero_transacao']   = $arquivo[7];
	$data['quantidade']   = $arquivo[8];
	$data['rotulo_categoria']   = $arquivo[9];
	$data['SKU']   = $arquivo[10];
	$data['descricao']   = $arquivo[11];
	$data['tipo']   = $arquivo[12];
	$data['status_pagamento']   = $arquivo[13];
	$data['valor_extrato']   = $arquivo[14];
	$data['debito']   = $arquivo[15];
	$data['credito']   = $arquivo[16];
	$data['saldo']   = $arquivo[17];
	$data['moeda']   = $arquivo[18];
	$data['referencia_pedido_cliente']   = $arquivo[19];
	$data['referencia_pedido_loja']   = $arquivo[20];
	$data['data_ciclo_faturamento']   = $arquivo[21];     
		
	$data['usuario'] = $_SESSION['username'];
	$insert = $this->db->insert('conciliacao_carrefour_xls', $data);
	$order_id = $this->db->insert_id();
	return ($order_id) ? $order_id : false;
	
	}

    public function salvarArquivoConciliacaoManual($input, $arquivo){

        $data['lote'] = $input['hdnLote'];
	    $data['nome_arquivo'] = $input['arquivo'];

        $data['numero_pedido'] = $arquivo[1];
        $data['ref_pedido'] = $arquivo[2];
        $data['marketplace'] = $arquivo[3];

        $data['usuario'] = $_SESSION['username'];

        $insert = $this->db->insert('conciliacao_manual', $data);
        $order_id = $this->db->insert_id();
        return ($order_id) ? $order_id : false;

    }

	public function removeAcentos($string) {
		$comAcentos = array('à', 'á', 'â', 'ã', 'ä', 'å', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ù', 'ü', 'ú', 'ÿ', 'À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'O', 'Ù', 'Ü', 'Ú');
		$semAcentos = array('a', 'a', 'a', 'a', 'a', 'a', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'y', 'A', 'A', 'A', 'A', 'A', 'A', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U');
		return str_replace($comAcentos, $semAcentos, $string);
	}

	public function salvarArquivoMadeiraTable($input, $arquivo){

	    $data['lote'] = $input['hdnLote'];
	    $data['nome_arquivo'] = $input['arquivo'];
		
		$data['data'] = $arquivo[1];
        $data['valor'] = $arquivo[2];
        $data['descricao'] = $arquivo[3];
        $data['pedido'] = $arquivo[4];
        $data['pedido_mm'] = $arquivo[5];
        $data['data_liberacao'] = $arquivo[6];
        $data['detalhamento'] = $arquivo[7];
	    
	    $insert = $this->db->insert('conciliacao_madeira', $data);
		
	    $id = $this->db->insert_id();
	    return ($id) ? $id : false;
	    
	}


    public function salvarArquivoNMTable($input, $arquivo)
    {
	    $data['lote'] = $input['hdnLote'];
	    $data['nome_arquivo'] = $input['arquivo'];

	    $data['id_transf'] = str_replace( "\"", "", utf8_encode($arquivo[0]));
	    $data['operacao'] = str_replace( "\"", "", utf8_encode($arquivo[1]));
	    $data['cod_transf'] = str_replace( "\"", "", utf8_encode($arquivo[2]));
	    $data['id_pedido_nm'] = str_replace( "\"", "", utf8_encode($arquivo[3]));
	    $data['id_parc'] = str_replace( "\"", "", utf8_encode($arquivo[4]));
	    $data['id_fornecedor'] = str_replace( "\"", "", utf8_encode($arquivo[5]));
	    $data['seller'] = str_replace( "\"", "", utf8_encode($arquivo[6]));
	    $data['sequence'] = str_replace( "\"", "", utf8_encode($arquivo[7]));
	    $data['data_emissao'] = str_replace( "\"", "", utf8_encode($arquivo[8]));
	    $data['data_entrega'] = str_replace( "\"", "", utf8_encode($arquivo[9]));
	    $data['orderid'] = str_replace( "\"", "", utf8_encode($arquivo[10]));
	    $data['cpf_cnpj'] = str_replace( "\"", "", utf8_encode($arquivo[11]));
	    $data['nome'] = str_replace( "\"", "", utf8_encode($arquivo[12]));
	    $data['situacao'] = str_replace( "\"", "", utf8_encode($arquivo[13]));
	    $data['total_pedido'] = str_replace( "\"", "", utf8_encode($arquivo[14]));
	    $data['valor_comissao'] = str_replace( "\"", "", utf8_encode($arquivo[15]));
	    $data['valor_repasse'] = str_replace( "\"", "", utf8_encode($arquivo[16]));
	    $data['valor_ir'] = str_replace( "\"", "", utf8_encode($arquivo[17]));
	    $data['total'] = str_replace( "\"", "", utf8_encode($arquivo[18]));
	    
	    $insert = $this->db->insert('conciliacao_nm', $data);
	    $order_id = $this->db->insert_id();
	    return ($order_id) ? $order_id : false;
	    
	}

	
	public function trataoarquivoparatabelanovab2w($input){
	    
	    // INSERT VENDAS
	   /* $sql1 = "INSERT INTO conciliacao_b2w_tratado (id,lote,data_criacao,nome_arquivo,marca,nome_fantasia,data_pedido,data_pagamento,
	        data_estorno,data_liberacao,data_prevista_pgto,lancamento,ref_pedido,entrega,tipo,`status`,valor,parcela,meio_pgto,
	        modelo_financeiro,status_conciliacao,status_conciliacao_novo,valor_pedido,valor_frete,valor_produto_calculado,valor_produto_parceiro,
	        valor_frete_parceiro,valor_frete_calculado,valor_produto_recebido,valor_frete_recebido,dif_valor_recebido,dif_valor_recebido_produto,
	        dif_valor_recebido_frete,valor_receita_calculado,valor_produto_conecta,valor_frete_conecta,valor_produto,valor_conectala,valor_conectala_real,
	        valor_marketplace,valor_agencia,valor_percentual_mktplace,valor_percentual_parceiro,valor_parceiro,valor_parceiro_novo,valor_autonomo,
	        valor_afiliado,valor_frete_real,usuario,seller_name,tratado,valor_frete_real_contratado)
	        
	        SELECT id,lote,data_criacao,nome_arquivo,marca,nome_fantasia,data_pedido,data_pagamento,
	        data_estorno,data_liberacao,data_prevista_pgto,lancamento,ref_pedido,entrega,tipo,`status`,REPLACE(valor, '.',''),parcela,meio_pgto,
	        modelo_financeiro,status_conciliacao,status_conciliacao_novo,valor_pedido,valor_frete,valor_produto_calculado,valor_produto_parceiro,
	        valor_frete_parceiro,valor_frete_calculado,valor_produto_recebido,valor_frete_recebido,dif_valor_recebido,dif_valor_recebido_produto,
	        dif_valor_recebido_frete,valor_receita_calculado,valor_produto_conecta,valor_frete_conecta,valor_produto,valor_conectala,valor_conectala_real,
	        valor_marketplace,valor_agencia,valor_percentual_mktplace,valor_percentual_parceiro,valor_parceiro,valor_parceiro_novo,valor_autonomo,
	        valor_afiliado,valor_frete_real,usuario,seller_name,tratado,valor_frete_real_contratado FROM `conciliacao_b2w` WHERE lote = '" . $input['hdnLote'] . "' AND UPPER(tipo) = UPPER('Venda')";
        $update1 = $this->db->query($sql1);
        if ($update1) {
            //INSERT - ESTORNO
            $sql2 = "INSERT INTO conciliacao_b2w_tratado (id,lote,data_criacao,nome_arquivo,marca,nome_fantasia,data_pedido,data_pagamento,
	            data_estorno,data_liberacao,data_prevista_pgto,lancamento,ref_pedido,entrega,tipo,`status`,valor,parcela,meio_pgto,
	            modelo_financeiro,status_conciliacao,status_conciliacao_novo,valor_pedido,valor_frete,valor_produto_calculado,valor_produto_parceiro,
	            valor_frete_parceiro,valor_frete_calculado,valor_produto_recebido,valor_frete_recebido,dif_valor_recebido,dif_valor_recebido_produto,
	            dif_valor_recebido_frete,valor_receita_calculado,valor_produto_conecta,valor_frete_conecta,valor_produto,valor_conectala,valor_conectala_real,
	            valor_marketplace,valor_agencia,valor_percentual_mktplace,valor_percentual_parceiro,valor_parceiro,valor_parceiro_novo,valor_autonomo,
	            valor_afiliado,valor_frete_real,usuario,seller_name,tratado,valor_frete_real_contratado)
	            
	            SELECT id,lote,data_criacao,nome_arquivo,marca,nome_fantasia,data_pedido,data_pagamento,
	            data_estorno,data_liberacao,data_prevista_pgto,lancamento,ref_pedido,entrega,tipo,`status`,REPLACE(valor, '.',''),parcela,meio_pgto,
	            modelo_financeiro,status_conciliacao,status_conciliacao_novo,valor_pedido,valor_frete,valor_produto_calculado,valor_produto_parceiro,
	            valor_frete_parceiro,valor_frete_calculado,valor_produto_recebido,valor_frete_recebido,dif_valor_recebido,dif_valor_recebido_produto,
	            dif_valor_recebido_frete,valor_receita_calculado,valor_produto_conecta,valor_frete_conecta,valor_produto,valor_conectala,valor_conectala_real,
	            valor_marketplace,valor_agencia,valor_percentual_mktplace,valor_percentual_parceiro,valor_parceiro,valor_parceiro_novo,valor_autonomo,
	            valor_afiliado,valor_frete_real,usuario,seller_name,tratado,valor_frete_real_contratado FROM `conciliacao_b2w` WHERE lote = '" . $input['hdnLote'] . "' AND UPPER(tipo) = UPPER('Estorno_Venda')";
            $update2 = $this->db->query($sql2);
            if ($update2) {
                // UPDATE COMISSÃO ESTORNO
                $sql3 = "UPDATE conciliacao_b2w_tratado CBT
        	            INNER JOIN conciliacao_b2w CB ON CB.lote = CBT.lote AND CB.ref_pedido = CBT.ref_pedido AND CB.entrega = CBT.entrega
        	            SET CBT.valor_comissao = REPLACE(CB.valor, '.','')
        	            WHERE UPPER(CB.tipo) = UPPER('Comissao') AND CBT.lote = '" . $input['hdnLote'] . "'";
                $update3 = $this->db->query($sql3);
                if ($update3) {
                    // UPDATE FRETE ESTORNO
                    $sql4 = "UPDATE conciliacao_b2w_tratado CBT
    	            INNER JOIN conciliacao_b2w CB ON CB.lote = CBT.lote AND CB.ref_pedido = CBT.ref_pedido AND CB.entrega = CBT.entrega
    	            SET CBT.valor_frete_b2w = REPLACE(CB.valor, '.','')
    	            WHERE UPPER(CB.tipo) = UPPER('%Frete%') AND CBT.lote = '" . $input['hdnLote'] . "'";
                    $update4 = $this->db->query($sql4);
                    if ($update4) {
                        // UPDATE FRETE ESTORNO
                        $sql5 = "UPDATE conciliacao_b2w_tratado CBT
                                 SET valor_pago_marketplace = round(REPLACE(CAST(valor AS CHAR),',','.') - case when REPLACE(CAST(valor_comissao AS CHAR),',','.') < 0 then REPLACE(CAST(valor_comissao AS CHAR),',','.')*-1 else REPLACE(CAST(valor_comissao AS CHAR),',','.') end ,2)
                	             WHERE UPPER(CBT.tipo) = UPPER('Venda') AND CBT.lote = '" . $input['hdnLote'] . "'";
                        $update5 = $this->db->query($sql5);
                        if($update5){
                            // UPDATE PROMOÇÃO
                            $sql6 = "UPDATE conciliacao_b2w_tratado CBT
            	                    INNER JOIN conciliacao_b2w CB ON CB.ref_pedido = CBT.ref_pedido and CB.entrega = CBT.entrega and CB.lote = CBT.lote
                                    SET CBT.valor_pago_marketplace = CBT.valor_pago_marketplace + CB.valor
                                    WHERE UPPER(CB.tipo) = UPPER('Ressarcimento_promocao') AND CBT.lote = '".$input['hdnLote']."'";
                            $update6 = $this->db->query($sql6);
                            if($update6){
                                // UPDATE COMISSÃO PROMOÇÃO
                                $sql7 = "UPDATE conciliacao_b2w_tratado CBT
                                        INNER JOIN conciliacao_b2w CB ON CB.ref_pedido = CBT.ref_pedido and CB.entrega = CBT.entrega and CB.lote = CBT.lote
                                        SET CBT.valor_pago_marketplace = CBT.valor_pago_marketplace + CB.valor
                                        WHERE UPPER(CB.tipo) = UPPER('Comissao_Ressarcimento_Promocao') AND CBT.lote = '".$input['hdnLote']."'";
                                return $this->db->query($sql7);
                            }
                        }
                    }
                }
            }
        }*/

        $sql1 = "INSERT INTO conciliacao_b2w_tratado (id,lote,data_criacao,nome_arquivo,marca,nome_fantasia,data_pedido,data_pagamento,
	        data_estorno,data_liberacao,data_prevista_pgto,lancamento,ref_pedido,entrega,tipo,`status`,valor,parcela,meio_pgto,
	        modelo_financeiro,status_conciliacao,status_conciliacao_novo,valor_pedido,valor_frete,valor_produto_calculado,valor_produto_parceiro,
	        valor_frete_parceiro,valor_frete_calculado,valor_produto_recebido,valor_frete_recebido,dif_valor_recebido,dif_valor_recebido_produto,
	        dif_valor_recebido_frete,valor_receita_calculado,valor_produto_conecta,valor_frete_conecta,valor_produto,valor_conectala,valor_conectala_real,
	        valor_marketplace,valor_agencia,valor_percentual_mktplace,valor_percentual_parceiro,valor_parceiro,valor_parceiro_novo,valor_autonomo,
	        valor_afiliado,valor_frete_real,usuario,seller_name,tratado,valor_frete_real_contratado)
	        
	        SELECT id,lote,data_criacao,nome_arquivo,marca,nome_fantasia,data_pedido,data_pagamento,
	        data_estorno,data_liberacao,data_prevista_pgto,lancamento,ref_pedido,entrega,tipo,`status`,SUM(REPLACE(valor, ',','.')),parcela,meio_pgto,
	        modelo_financeiro,status_conciliacao,status_conciliacao_novo,valor_pedido,valor_frete,valor_produto_calculado,valor_produto_parceiro,
	        valor_frete_parceiro,valor_frete_calculado,valor_produto_recebido,valor_frete_recebido,dif_valor_recebido,dif_valor_recebido_produto,
	        dif_valor_recebido_frete,valor_receita_calculado,valor_produto_conecta,valor_frete_conecta,valor_produto,valor_conectala,valor_conectala_real,
	        valor_marketplace,valor_agencia,valor_percentual_mktplace,valor_percentual_parceiro,valor_parceiro,valor_parceiro_novo,valor_autonomo,
	        valor_afiliado,valor_frete_real,usuario,seller_name,tratado,valor_frete_real_contratado FROM `conciliacao_b2w` WHERE lote = '" . $input['hdnLote'] . "' group by ref_pedido";
        return $this->db->query($sql1);

    }

    public function createConciliacaoMadeiraTratado($data){
    	$insert = $this->db->insert('conciliacao_madeira_tratado', $data);
        $create_id = $this->db->insert_id();
        return ($create_id) ? $create_id : false;    
	}
	
	public function updateConciliacaoMadeiraTratado($data, $id){
		$this->db->where('id', $id);
        $update =  $this->db->update('conciliacao_madeira_tratado', $data);
        return $update;    
	}
	
	public function getConciliacaoMadeiraTratado($lote, $ref_pedido){
     	$sql = " SELECT * FROM conciliacao_madeira_tratado WHERE lote = ? AND ref_pedido = ? LIMIT 1";
        $query = $this->db->query($sql, array($lote, $ref_pedido));
        return $query->row_array();
	}
	
	public function getConciliacaoByLote($table, $lote) {
		$sql = " SELECT * FROM {$table} WHERE lote = ? ";
        $query = $this->db->query($sql, array($lote));
        return (false !== $query) ? $query->result_array() : false;
	}
	// rick
    public function trataoarquivoparatabelanovaMadeira($input){
	    		
		$lote = $input['hdnLote']; 
	    $concs = $this->getConciliacaoByLote('conciliacao_madeira',$lote);

	    foreach ($concs as $conc) {
	    	
			if ($conc['Status'] != 'Repassado') { // só processo os repassados. Outros status "Bloqueado", "Liberado"
				continue;
			}
			
	    	$conc_trat =false;
			if ( $conc['ref_pedido'] != '') {
				$conc_trat = $this->getConciliacaoMadeiraTratado($lote, $conc['ref_pedido']) ; // leio o registro atual do pedido
			} 
	    	$create = false;
	    	if (!$conc_trat) {
	    		$create = true;
	    		$conc_trat = array(
	    			'lote' 					=> $lote,
	    			'data_criacao' 			=> $conc['data_criacao'], 
	    			'nome_arquivo' 			=> $conc['nome_arquivo'], 
	    			'seller' 				=> $conc['seller'], 
	    			'ref_pedido' 			=> $conc['ref_pedido'], 
	    			'data_pedido'			=> $conc['data_pedido'], 
	    			'data_pagamento' 		=> $conc['data_pagamento'], 
	    			'data_liberacao' 		=> $conc['data_liberacao'], 
	    			'data_prevista_pgto' 	=> $conc['data_prevista_pgto'], 
	    			'status' 				=> $conc['status'], 
	    			'tipo'					=> $conc['tipo'], 
	    			'valor' 				=> $conc['valor'], 
	    			'valor_pedido'    		=> 0, 
	    			'valor_pago_marketplace' => 0,
				);
	    	}
	    	if ($conc['tipo'] == 'Venda') {
	    		$conc_trat['valor_pedido'] = $conc['valor'];
				$conc_trat['valor_pago_marketplace'] = $conc['valor'] + $conc_trat['valor_pago_marketplace'];
	    	}
			if ($conc['tipo'] == 'Estorno') {
	    		$conc_trat['valor_pedido'] = $conc['valor'];
				$conc_trat['data_estorno'] = $conc['data_liberacao'];
	    	}
			if ($conc['tipo'] == 'Comissao') {
	    		$conc_trat['valor_comissao'] = $conc['valor'];
				$conc_trat['valor_pago_marketplace'] = $conc_trat['valor_pago_marketplace'] + $conc['valor'];
	    	}
			if ( $conc['ref_pedido'] == '') { // outros ;
				$conc_trat['valor_pago_marketplace'] = $conc['valor'];
			}
			if ($create) {
				$this->createConciliacaoMadeiraTratado($conc_trat);
			}
			else {
				$this->updateConciliacaoMadeiraTratado($conc_trat, $conc['id']);
			}
	    }
    }

    public function consiliaarquivoB2W($input)
    {

        $this->trataoarquivoparatabelanovab2w($input);

        //Marca os pedidos não encontrados no arquivo
        $sql = "UPDATE conciliacao_b2w_tratado SET status_conciliacao = 'Não encontrado' WHERE lote = '" . $input['hdnLote'] . "' AND CASE WHEN LEFT(ref_pedido,2) = '01' THEN CONCAT('Shoptime-',entrega)
                WHEN LEFT(ref_pedido,2) = '02' THEN CONCAT('Lojas Americanas-',entrega)
                WHEN LEFT(ref_pedido,2) = '03' THEN CONCAT('Submarino-',entrega)
                WHEN LEFT(ref_pedido,2) = '09' THEN CONCAT('Americanas Empresas-',entrega)
                END NOT IN (SELECT DISTINCT O.numero_marketplace FROM orders O WHERE numero_marketplace IS NOT NULL)";
        $update1 = $this->db->query($sql);

        if ($update1) {
            //Marca os pedidos de estorno
            $sql2 = "UPDATE conciliacao_b2w_tratado SET status_conciliacao = 'Estorno' WHERE lote = '" . $input['hdnLote'] . "' and UPPER(tipo) LIKE UPPER('%estorno%') AND status_conciliacao IS NULL AND entrega IN ( select * from (SELECT DISTINCT entrega FROM conciliacao_b2w_tratado WHERE UPPER(tipo) LIKE UPPER('%estorno%') AND lote = '" . $input['hdnLote'] . "') TS )";
            $update2 = $this->db->query($sql2);

            if ($update2) {
                // Marca os pedidos Ok
                $sql3 = "UPDATE conciliacao_b2w_tratado SET status_conciliacao = 'Ok' WHERE lote = '" . $input['hdnLote'] . "' and UPPER(tipo) NOT LIKE UPPER('%estorno%') AND entrega IN (
                        SELECT DISTINCT ARQ.entrega FROM (
                        SELECT DISTINCT CASE WHEN LEFT(ref_pedido,2) = '01' THEN CONCAT('Shoptime-',entrega)
                                        WHEN LEFT(ref_pedido,2) = '02' THEN CONCAT('Lojas Americanas-',entrega)
                                        WHEN LEFT(ref_pedido,2) = '03' THEN CONCAT('Submarino-',entrega)
                                        WHEN LEFT(ref_pedido,2) = '09' THEN CONCAT('Americanas Empresas-',entrega)
                                        END AS entrega_tratado, entrega, valor AS valor_total FROM conciliacao_b2w_tratado WHERE lote = '" . $input['hdnLote'] . "' AND UPPER(tipo) NOT LIKE UPPER('%estorno%') ) ARQ
                        INNER JOIN (
                        SELECT O.numero_marketplace, total_order + total_ship AS valor_total FROM orders O WHERE numero_marketplace IS NOT NULL) ORDS ON ORDS.numero_marketplace = ARQ.entrega_tratado AND (ROUND(REPLACE(CAST(ARQ.valor_total AS CHAR),',','.'),2) = ROUND(REPLACE(CAST(ORDS.valor_total AS CHAR),',','.'),2) OR(  ROUND(REPLACE(CAST(ARQ.valor_total AS CHAR),',','.'),2) - ROUND(REPLACE(CAST(ORDS.valor_total AS CHAR),',','.'),2) IN ('-0.01','0.01','0.00') )))";
                $update3 = $this->db->query($sql3);

                if ($update3) {
                    // Marca os pedidos Divergentes
                    $sql4 = "UPDATE conciliacao_b2w_tratado SET status_conciliacao = 'Divergente' WHERE lote = '" . $input['hdnLote'] . "' and UPPER(tipo) NOT LIKE UPPER('%estorno%') AND entrega IN (
    	                SELECT DISTINCT ARQ.entrega FROM (
                        SELECT DISTINCT CASE WHEN LEFT(ref_pedido,2) = '01' THEN CONCAT('Shoptime-',entrega)
                                        WHEN LEFT(ref_pedido,2) = '02' THEN CONCAT('Lojas Americanas-',entrega)
                                        WHEN LEFT(ref_pedido,2) = '03' THEN CONCAT('Submarino-',entrega)
                                        WHEN LEFT(ref_pedido,2) = '09' THEN CONCAT('Americanas Empresas-',entrega)
                                        END AS entrega_tratado, entrega, valor AS valor_total FROM conciliacao_b2w_tratado WHERE lote = '" . $input['hdnLote'] . "' AND UPPER(tipo) NOT LIKE UPPER('%estorno%') ) ARQ
                        INNER JOIN (
                        SELECT O.numero_marketplace, total_order + total_ship AS valor_total FROM orders O WHERE numero_marketplace IS NOT NULL) ORDS ON ORDS.numero_marketplace = ARQ.entrega_tratado AND round(REPLACE(CAST(ARQ.valor_total AS CHAR),',','.'),2) <> round(REPLACE(CAST(ORDS.valor_total AS CHAR),',','.'),2) )";
                    $update4 = $this->db->query($sql4);

                    if ($update4) {
                        // Marca como Outros as linhas sem número de pedido
                        $sql5 = "UPDATE conciliacao_b2w_tratado SET status_conciliacao = 'Outros' WHERE lote = '" . $input['hdnLote'] . "' AND (entrega is null or entrega = '')";
                        $update5 = $this->db->query($sql5);

                        if ($update5) {
                            // Marca como Nulo os pedidos que tem estorno neles
                            $sql6 = "UPDATE conciliacao_b2w_tratado SET status_conciliacao = NULL
                                     WHERE lote = '" . $input['hdnLote'] . "'
                                            AND entrega IN ( select * from (SELECT DISTINCT entrega FROM conciliacao_b2w_tratado WHERE UPPER(tipo) LIKE UPPER('%estorno%') AND lote = '" . $input['hdnLote'] . "') TS )
                                            AND UPPER(tipo) NOT LIKE UPPER('%estorno%')";
                            return $this->db->query($sql6);
                        }
                    }
                }
            }
        }
    }

    public function ajustaconciliacaob2wcomplanilhadesconto($input){

        $sql = "UPDATE conciliacao_b2w_tratado CB 
        INNER JOIN carga_desconto CD ON CD.cod_pedido = CB.ref_pedido
        SET CB.valor_desconto_camp_promo = IF(CD.cash_discount_b2w = \"NULL\",0,CD.cash_discount_b2w) + IF(CD.cashback_b2w = \"NULL\",0,CD.cashback_b2w) + IF(CD.finance_b2w = \"NULL\",0,CD.finance_b2w) + IF(CD.desconto_incondicional_b2w = \"NULL\",0,CD.desconto_incondicional_b2w) + IF(CD.frete_b2w = \"NULL\",0,CD.frete_b2w) + IF(CD.cupom_b2w = \"NULL\",0,CD.cupom_b2w)
        WHERE CB.lote = '".$input['hdnLote']."'";

        return $this->db->query($sql);

    }

    public function atualizavaloresconciliadivisaoB2W($input)
    {

        $sql = "SELECT MAX(valor_aplicado) AS valor_aplicado FROM `param_mkt_categ_integ` WHERE integ_id = '10'";
        $query = $this->db->query($sql);
        $valor = $query->result_array();
        $percentual = $valor[0]['valor_aplicado'];

        //Busca os valores que deverão ser atualizados na conciliação
        $sql = "select CASE WHEN LEFT(ref_pedido,2) = '01' THEN CONCAT('Shoptime-',entrega)
                        WHEN LEFT(ref_pedido,2) = '02' THEN CONCAT('Lojas Americanas-',entrega)
                        WHEN LEFT(ref_pedido,2) = '03' THEN CONCAT('Submarino-',entrega)
                        WHEN LEFT(ref_pedido,2) = '09' THEN CONCAT('Americanas Empresas-',entrega)
                        END AS entrega_tratado, CB.* from conciliacao_b2w_tratado CB WHERE CB.lote = '" . $input['hdnLote'] . "' AND CB.status_conciliacao <> 'Não encontrado'";
        $query = $this->db->query($sql);
        $dadosConciliacao = $query->result_array();

        if($dadosConciliacao){
            foreach($dadosConciliacao as $conciliacao){
                $sql2 = "SELECT 
                                S.name AS seller_name,
                                S.id AS store_id,
                                CASE WHEN O.data_envio IS NULL THEN 'Não Enviado' ELSE 'Enviado' END AS pedido_enviado,
                                CASE WHEN UPPER(FR.ship_company) LIKE CONCAT(\"%\",CONCAT(UPPER(S.name),\"%\")) OR S.freight_seller_type > 0 OR O.freight_seller > 0 THEN 'Seller' ELSE 'Conecta Lá' END AS tipo_frete,
                                '" . $_SESSION['username'] . "' AS usuario,
                                O.gross_amount AS valor_pedido,
                                ROUND(O.gross_amount - O.total_ship,2) AS valor_produto,
                                O.total_ship AS valor_frete,
                                O.frete_real AS valor_frete_real,
                                ROUND(O.total_ship - O.frete_real,2) AS valor_frete_real_contratado,
                                O.service_charge_rate AS valor_percentual_parceiro,
                                $percentual AS valor_percentual_mktplace,
                                ROUND( O.gross_amount - (  O.gross_amount * ($percentual/100)  ) ,2) AS `valor_marketplace`,
                                ROUND( (ROUND(O.gross_amount - O.total_ship,2))  * ($percentual/100),2) AS valor_produto_calculado,
                                ROUND(O.total_ship * ($percentual/100),2) AS valor_frete_calculado,
                                ROUND( ROUND( (ROUND(O.gross_amount - O.total_ship,2))  * ($percentual/100),2) + ROUND(O.total_ship * ($percentual/100),2) ,2) AS valor_receita_calculado,
                                ROUND( ROUND(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100)  ,2) AS valor_produto_parceiro,
                                ROUND( O.total_ship * (O.service_charge_rate/100)  ,2) AS valor_frete_parceiro,
                                ROUND(
                                    (ROUND(O.gross_amount - O.total_ship,2)) -
                                    (ROUND( ROUND(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100)  ,2)) -
                                    (ROUND( O.total_ship * (O.service_charge_rate/100)  ,2) )
                                    ,2) AS valor_parceiro,
                                ROUND(
                                    (ROUND( ROUND(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100)  ,2)) -
                                    (ROUND( (ROUND(O.gross_amount - O.total_ship,2))  * ($percentual/100),2))
                                    ,2) AS valor_produto_conecta,
                                ROUND(
                                    (ROUND(O.total_ship - ( O.total_ship * ( $percentual / 100 ) ),2)) - (O.frete_real)
                                    ,2) AS valor_frete_conecta,
                                ROUND(
                                    (ROUND(
                                    (ROUND( ROUND(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100),2)) -
                                    (ROUND( (ROUND(O.gross_amount - O.total_ship,2))  * ($percentual/100),2))
                                    ,2)) +
                                    ( (ROUND(O.total_ship - ( O.total_ship * ( $percentual / 100 ) ),2)) - (O.frete_real) )
                                    ,2) AS valor_conectala,
                                    ROUND(O.total_ship - ( O.total_ship * ( 10 / 100 ) ),2) AS valor_frete_recebido,
                                    ROUND(O.total_ship - ( O.total_ship * ( 10 / 100 ) ),2) AS valor_produto_recebido,
                                    CASE WHEN DTU.data_usada = 'Data Envio' THEN O.data_mkt_sent ELSE O.data_mkt_delivered END AS data_gatilho
                        FROM orders O
                        INNER JOIN stores S ON S.id = O.store_id
                        LEFT JOIN (SELECT DISTINCT order_id, ship_company FROM freights) FR ON FR.order_id = O.id
                        INNER JOIN (SELECT DISTINCT MAX(PMC.data_usada) AS data_usada, apelido FROM `stores_mkts_linked` SML INNER JOIN `param_mkt_ciclo` PMC ON PMC.integ_id = SML.id_mkt GROUP BY apelido) DTU ON DTU.apelido = O.origin
                        WHERE O.numero_marketplace = '" . $conciliacao['entrega_tratado'] . "' limit 1";

                $query = $this->db->query($sql2);
                $dadosPedidoConciliacao = $query->result_array();

                if($dadosPedidoConciliacao){
                    $resulPedidoTratamento = $dadosPedidoConciliacao[0];
                        
                    $updateConciliacao = "UPDATE conciliacao_b2w_tratado CB
                                                SET CB.seller_name = \"" . $resulPedidoTratamento['seller_name'] . "\",
                                                    CB.store_id = '" . $resulPedidoTratamento['store_id'] . "',
                                                    CB.pedido_enviado = '" . $resulPedidoTratamento['pedido_enviado'] . "',
                                                    CB.tipo_frete = '" . $resulPedidoTratamento['tipo_frete'] . "',
                                                    CB.usuario = '" . $resulPedidoTratamento['usuario'] . "',
                                                    CB.valor_pedido = '" . $resulPedidoTratamento['valor_pedido'] . "',
                                                    CB.valor_produto = '" . $resulPedidoTratamento['valor_produto'] . "',
                                                    CB.valor_frete = '" . $resulPedidoTratamento['valor_frete'] . "',
                                                    CB.valor_frete_real = '" . $resulPedidoTratamento['valor_frete_real'] . "',
                                                    CB.valor_frete_real_contratado = '" . $resulPedidoTratamento['valor_frete_real_contratado'] . "',
                                                    CB.valor_percentual_parceiro = '" . $resulPedidoTratamento['valor_percentual_parceiro'] . "',
                                                    CB.valor_percentual_mktplace = '" . $resulPedidoTratamento['valor_percentual_mktplace'] . "',
                                                    CB.`valor_marketplace` = '" . $resulPedidoTratamento['valor_marketplace'] . "',
                                                    CB.valor_produto_calculado = '" . $resulPedidoTratamento['valor_produto_calculado'] . "',
                                                    CB.valor_frete_calculado = '" . $resulPedidoTratamento['valor_frete_calculado'] . "',
                                                    CB.valor_receita_calculado = '" . $resulPedidoTratamento['valor_receita_calculado'] . "',
                                                    CB.valor_produto_parceiro = '" . $resulPedidoTratamento['valor_produto_parceiro'] . "',
                                                    CB.valor_frete_parceiro = '" . $resulPedidoTratamento['valor_frete_parceiro'] . "',
                                                    CB.valor_parceiro = '" . $resulPedidoTratamento['valor_parceiro'] . "',
                                                    CB.valor_produto_conecta = '" . $resulPedidoTratamento['valor_produto_conecta'] . "',
                                                    CB.valor_frete_conecta = '" . $resulPedidoTratamento['valor_frete_conecta'] . "',
                                                    CB.valor_conectala = '" . $resulPedidoTratamento['valor_conectala'] . "',
                                                CB.valor_frete_recebido = IF(CB.valor_pago_marketplace IS NULL,NULL,'" . $resulPedidoTratamento['valor_frete_recebido'] . "'),
                                                CB.valor_produto_recebido =  round( REPLACE(CB.valor_pago_marketplace,',','.') - '" . $resulPedidoTratamento['valor_produto_recebido'] . "' , 2),
                                                CB.data_gatilho = '" . $resulPedidoTratamento['data_gatilho'] . "'
                                                WHERE CB.lote = '" . $input['hdnLote'] . "' AND CB.status_conciliacao <> 'Não encontrado' AND CB.entrega = '" . $conciliacao['entrega'] . "'";
                    
                    $attConciliacao = $this->db->query($updateConciliacao);

                    if(!$attConciliacao){
                        return false;
                    }
                    
                }
                      
            }

            $sql1 = "UPDATE conciliacao_b2w_tratado CB SET
                                                    CB.dif_valor_recebido = round(REPLACE(CB.valor_pago_marketplace,',','.') - CB.valor_marketplace,2),
                                                    CB.dif_valor_recebido_produto = round(CB.valor_produto_recebido - (CB.valor_produto - CB.valor_produto_calculado) ,2),
                                                    CB.dif_valor_recebido_frete = round(CB.valor_frete_recebido - ( CB.valor_frete - CB.valor_frete_calculado) ,2)
            WHERE CB.lote = '" . $input['hdnLote'] . "' AND CB.status_conciliacao NOT IN ('Não encontrado','Estorno')";
            $update2 = $this->db->query($sql1);

            if ($update2) {

                $sqlEst = "UPDATE conciliacao_b2w_tratado CB SET
                                                    CB.dif_valor_recebido = round(REPLACE(CB.valor_pago_marketplace,',','.') + CB.valor_marketplace,2),
                                                    CB.dif_valor_recebido_produto = (round((round( REPLACE(CB.valor_pago_marketplace,',','.') + ( round(CB.valor_frete - ( CB.valor_frete * ( 12 / 100 ) ),2) ) , 2)) + (CB.valor_produto - CB.valor_produto_calculado) ,2)) * -1,
                                                    CB.dif_valor_recebido_frete = round(CB.valor_frete_recebido - ( CB.valor_frete - CB.valor_frete_calculado) ,2),
                                                    CB.valor_produto_recebido = round( REPLACE(CB.valor_pago_marketplace,',','.') + ( round(CB.valor_frete - ( CB.valor_frete * ( 12 / 100 ) ),2) ) , 2)
                         WHERE CB.lote = '" . $input['hdnLote'] . "' AND CB.status_conciliacao IN ('Estorno')";
                $updateEst = $this->db->query($sqlEst);

                if ($updateEst) {

                    //calcula o valor a ser pago no estorno

                    $sql3 = "UPDATE conciliacao_b2w_tratado CBT
                             SET  valor_parceiro_novo = round(case when CBT.valor_comissao > 0 then 
                                                            case when valor < 0 then
                                                                (CBT.valor + CBT.valor_pedido) - CBT.valor_comissao
                                                            else
                                                                ((CBT.valor*-1) + CBT.valor_pedido) - CBT.valor_comissao
                                                            end
                                                        else
                                                            case when valor < 0 then
                                                                (CBT.valor + CBT.valor_pedido) + CBT.valor_comissao
                                                            else
                                                                ((CBT.valor*-1) + CBT.valor_pedido) + CBT.valor_comissao
                                                            end
                                                        end,2),
                                CBT.valor_pago_marketplace = valor
            	             WHERE UPPER(CBT.tipo) = UPPER('Estorno_Venda') AND CBT.lote = '" . $input['hdnLote'] . "'";

                    $udpdateParceiroNovo = $this->db->query($sql3);

                    if ($udpdateParceiroNovo) {

                        $sql4 = "UPDATE conciliacao_b2w_tratado SET status_conciliacao = 'Divergente' WHERE  status_conciliacao IN ('Ok') AND 
                                ( ( REPLACE(dif_valor_recebido,',','.') > '0.01' OR REPLACE(dif_valor_recebido,',','.') < '-0.01') OR 
                                  ( REPLACE(dif_valor_recebido_produto,',','.') > '0.01' OR REPLACE(dif_valor_recebido_produto,',','.') < '-0.01') OR
                                  ( REPLACE(dif_valor_recebido_frete,',','.') > '0.01' OR REPLACE(dif_valor_recebido_frete,',','.') < '-0.01') )
                                AND CBT.lote = '" . $input['hdnLote'] . "'";

                        return $this->db->query($sql3);

                    }
                }

            }
        }

        //Atualiza o valor do pedido na tabela
        /*$sql = "UPDATE conciliacao_b2w_tratado CB
            	INNER JOIN orders O ON CB.entrega = SUBSTRING(O.numero_marketplace,POSITION('-' IN O.numero_marketplace)+1, LENGTH(O.numero_marketplace)- POSITION('-' IN O.numero_marketplace))
            	INNER JOIN stores S ON S.id = O.store_id
                LEFT JOIN (SELECT DISTINCT order_id, ship_company FROM freights) FR ON FR.order_id = O.id
            	SET CB.seller_name = S.name,
                    CB.store_id = S.id,
                    CB.pedido_enviado = case when O.data_envio is null then 'Não Enviado' else 'Enviado' end,
                    CB.tipo_frete = CASE WHEN UPPER(FR.ship_company) LIKE CONCAT(\"%\",CONCAT(UPPER(S.name),\"%\")) OR S.freight_seller_type > 0 OR O.freight_seller > 0 THEN 'Seller' else 'Conecta Lá' end,
                    CB.usuario = '" . $_SESSION['username'] . "',
                    CB.valor_pedido = O.gross_amount,
                    CB.valor_produto = round(O.gross_amount - O.total_ship,2),
                    CB.valor_frete = O.total_ship,
                    CB.valor_frete_real = O.frete_real,
                    CB.valor_frete_real_contratado = round(O.total_ship - O.frete_real,2),
                    CB.valor_percentual_parceiro = O.service_charge_rate,
                    CB.valor_percentual_mktplace = $percentual,
                    CB.`valor_marketplace` = round( O.gross_amount - (  O.gross_amount * ($percentual/100)  ) ,2),
                    CB.valor_produto_calculado = round( (round(O.gross_amount - O.total_ship,2))  * ($percentual/100),2),
                    CB.valor_frete_calculado = round(O.total_ship * ($percentual/100),2),
                    CB.valor_receita_calculado = round( round( (round(O.gross_amount - O.total_ship,2))  * ($percentual/100),2) + round(O.total_ship * ($percentual/100),2) ,2),
                    CB.valor_produto_parceiro = round( round(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100)  ,2),
                    CB.valor_frete_parceiro = round( O.total_ship * (O.service_charge_rate/100)  ,2),
                    CB.valor_parceiro = round(
                                                (round(O.gross_amount - O.total_ship,2)) -
                                                (round( round(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100)  ,2)) -
                                                (round( O.total_ship * (O.service_charge_rate/100)  ,2) )
                                            ,2),
                    CB.valor_produto_conecta = round(
                                                        (round( round(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100)  ,2)) -
                                                        (round( (round(O.gross_amount - O.total_ship,2))  * ($percentual/100),2))
                                                    ,2),
                                                    
                    CB.valor_frete_conecta = round(
                                                    (round(O.total_ship - ( O.total_ship * ( $percentual / 100 ) ),2)) - (O.frete_real)
                                                    ,2),
                    CB.valor_conectala = ROUND(
                                                    (ROUND(
                                                            (ROUND( ROUND(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100),2)) -
                                                            (ROUND( (ROUND(O.gross_amount - O.total_ship,2))  * ($percentual/100),2))
                                                        ,2)) +
                                                    ( (round(O.total_ship - ( O.total_ship * ( $percentual / 100 ) ),2)) - (O.frete_real) )
                                                    
                                                ,2),
                CB.valor_frete_recebido = IF(CB.valor_pago_marketplace IS NULL,NULL,round(O.total_ship - ( O.total_ship * ( $percentual / 100 ) ),2)),
                CB.valor_produto_recebido =  round( REPLACE(CB.valor_pago_marketplace,',','.') - ( round(O.total_ship - ( O.total_ship * ( $percentual / 100 ) ),2) ) , 2)
                
            	WHERE CB.lote = '" . $input['hdnLote'] . "' AND CB.status_conciliacao <> 'Não encontrado'";
        $update1 = $this->db->query($sql);*/

        /*if ($update1) {
            $sql1 = "UPDATE conciliacao_b2w_tratado CB SET
                                                    CB.dif_valor_recebido = round(REPLACE(CB.valor_pago_marketplace,',','.') - CB.valor_marketplace,2),
                                                    CB.dif_valor_recebido_produto = round(CB.valor_produto_recebido - (CB.valor_produto - CB.valor_produto_calculado) ,2),
                                                    CB.dif_valor_recebido_frete = round(CB.valor_frete_recebido - ( CB.valor_frete - CB.valor_frete_calculado) ,2)
            WHERE CB.lote = '" . $input['hdnLote'] . "' AND CB.status_conciliacao NOT IN ('Não encontrado','Estorno')";
            $update2 = $this->db->query($sql1);
            if ($update2) {
                $sqlEst = "UPDATE conciliacao_b2w_tratado CB SET
                                                    CB.dif_valor_recebido = round(REPLACE(CB.valor_pago_marketplace,',','.') + CB.valor_marketplace,2),
                                                    CB.dif_valor_recebido_produto = (round((round( REPLACE(CB.valor_pago_marketplace,',','.') + ( round(CB.valor_frete - ( CB.valor_frete * ( 12 / 100 ) ),2) ) , 2)) + (CB.valor_produto - CB.valor_produto_calculado) ,2)) * -1,
                                                    CB.dif_valor_recebido_frete = round(CB.valor_frete_recebido - ( CB.valor_frete - CB.valor_frete_calculado) ,2),
                                                    CB.valor_produto_recebido = round( REPLACE(CB.valor_pago_marketplace,',','.') + ( round(CB.valor_frete - ( CB.valor_frete * ( 12 / 100 ) ),2) ) , 2)
                         WHERE CB.lote = '" . $input['hdnLote'] . "' AND CB.status_conciliacao IN ('Estorno')";
                $updateEst = $this->db->query($sqlEst);
                if ($updateEst) {
                    //calcula o valor a ser pago no estorno
                    $sql3 = "UPDATE conciliacao_b2w_tratado CBT
                             SET  valor_parceiro_novo = round(case when CBT.valor_comissao > 0 then 
                                                            case when valor < 0 then
                                                                (CBT.valor + CBT.valor_pedido) - CBT.valor_comissao
                                                            else
                                                                ((CBT.valor*-1) + CBT.valor_pedido) - CBT.valor_comissao
                                                            end
                                                        else
                                                            case when valor < 0 then
                                                                (CBT.valor + CBT.valor_pedido) + CBT.valor_comissao
                                                            else
                                                                ((CBT.valor*-1) + CBT.valor_pedido) + CBT.valor_comissao
                                                            end
                                                        end,2),
                                CBT.valor_pago_marketplace = valor
            	             WHERE UPPER(CBT.tipo) = UPPER('Estorno_Venda') AND CBT.lote = '" . $input['hdnLote'] . "'";
                    $udpdateParceiroNovo = $this->db->query($sql3);
                    if ($udpdateParceiroNovo) {
                        $sql4 = "UPDATE conciliacao_b2w_tratado SET status_conciliacao = 'Divergente' WHERE  status_conciliacao IN ('Ok') AND 
                                ( ( REPLACE(dif_valor_recebido,',','.') > '0.01' OR REPLACE(dif_valor_recebido,',','.') < '-0.01') OR 
                                  ( REPLACE(dif_valor_recebido_produto,',','.') > '0.01' OR REPLACE(dif_valor_recebido_produto,',','.') < '-0.01') OR
                                  ( REPLACE(dif_valor_recebido_frete,',','.') > '0.01' OR REPLACE(dif_valor_recebido_frete,',','.') < '-0.01') )
                                AND CBT.lote = '" . $input['hdnLote'] . "'";
                        return $this->db->query($sql3);
                    }
                }
            }
        }*/

    }

    public function consiliaarquivoViaVarejo($input)
    {

        $sql0 = "UPDATE conciliacao_viavarejo 
                SET valor_da_transacao =    round(CASE WHEN REPLACE(valor_da_comissao,',','.') <= 0 THEN 
                                            REPLACE(valor_da_transacao,',','.') + REPLACE(valor_da_comissao,',','.') 
                                                ELSE REPLACE(valor_da_transacao,',','.') - REPLACE(valor_da_comissao,',','.') 
                                            END,2)
                WHERE lote = '" . $input['hdnLote'] . "' AND valor_da_transacao = total_do_pedido";
        $update0 = $this->db->query($sql0);
        if ($update0) {
            //Marca os pedidos não encontrados no arquivo
            $sql = "UPDATE conciliacao_viavarejo SET status_conciliacao = 'Não encontrado' WHERE lote = '" . $input['hdnLote'] . "' AND numero_do_pedido NOT IN (SELECT DISTINCT numero_marketplace FROM orders WHERE numero_marketplace IS NOT NULL)";
            $update1 = $this->db->query($sql);

            if ($update1) {
                //Marca os pedidos de estorno
                $sql2 = "UPDATE conciliacao_viavarejo SET status_conciliacao = 'Estorno' WHERE lote = '" . $input['hdnLote'] . "' and UPPER(tipo_da_transacao) LIKE UPPER('%CANCELAMENTO%') AND status_conciliacao IS NULL AND numero_do_pedido IN ( select * from (SELECT DISTINCT numero_do_pedido FROM conciliacao_viavarejo WHERE UPPER(tipo_da_transacao) LIKE UPPER('%CANCELAMENTO%') AND lote = '" . $input['hdnLote'] . "') TS )";
                // 	    $sql2 = "UPDATE conciliacao_viavarejo CV
                //                 inner join (SELECT DISTINCT numero_do_pedido FROM conciliacao_viavarejo WHERE UPPER(tipo_da_transacao) LIKE UPPER('%CANCELAMENTO%') AND lote = '".$input['hdnLote']."' ) P on CV.numero_do_pedido = P.numero_do_pedido
                //                 SET status_conciliacao = 'Estorno'
                //                 WHERE lote = '".$input['hdnLote']."' AND status_conciliacao IS NULL";
                $update2 = $this->db->query($sql2);

                if ($update2) {
                    // Marca os pedidos Ok
                    $sql3 = "UPDATE conciliacao_viavarejo SET status_conciliacao = 'Ok' WHERE lote = '" . $input['hdnLote'] . "' and UPPER(tipo_da_transacao) LIKE UPPER('%VENDA%') AND numero_do_pedido IN (
                            SELECT DISTINCT ARQ.numero_do_pedido FROM (
                            SELECT DISTINCT numero_do_pedido, REPLACE(CAST(total_do_pedido AS CHAR),',','.') AS valor_total FROM conciliacao_viavarejo WHERE lote = '" . $input['hdnLote'] . "' AND UPPER(tipo_da_transacao) LIKE UPPER('%VENDA%') ) ARQ
                            INNER JOIN (
                            SELECT numero_marketplace, total_order + total_ship AS valor_total FROM orders WHERE numero_marketplace IS NOT NULL) ORDS ON ORDS.numero_marketplace = ARQ.numero_do_pedido AND ARQ.valor_total = ORDS.valor_total)";
                    $update3 = $this->db->query($sql3);

                    if ($update3) {
                        // Marca os pedidos Divergentes
                        $sql4 = "UPDATE conciliacao_viavarejo SET status_conciliacao = 'Divergente' WHERE lote = '" . $input['hdnLote'] . "' and UPPER(tipo_da_transacao) LIKE UPPER('%VENDA%') AND numero_do_pedido IN (
        	                SELECT DISTINCT ARQ.numero_do_pedido FROM (
                            SELECT DISTINCT numero_do_pedido, REPLACE(CAST(total_do_pedido AS CHAR),',','.') AS valor_total FROM conciliacao_viavarejo WHERE lote = '" . $input['hdnLote'] . "' AND UPPER(tipo_da_transacao) LIKE UPPER('%VENDA%') ) ARQ
                            INNER JOIN (
                            SELECT numero_marketplace, total_order + total_ship AS valor_total FROM orders WHERE numero_marketplace IS NOT NULL) ORDS ON ORDS.numero_marketplace = ARQ.numero_do_pedido AND ARQ.valor_total <> ORDS.valor_total)";
                        $update4 = $this->db->query($sql4);

                        if ($update4) {
                            // Marca como Outros as linhas sem número de pedido
                            $sql5 = "UPDATE conciliacao_viavarejo SET status_conciliacao = 'Outros' WHERE lote = '" . $input['hdnLote'] . "' AND (numero_do_pedido is null or numero_do_pedido = '')";
                            $update5 = $this->db->query($sql5);

                            if ($update5) {
                                // Marca como Nulo os pedidos que tem estorno neles
                                $sql6 = "UPDATE conciliacao_viavarejo SET status_conciliacao = NULL
                                         WHERE lote = '" . $input['hdnLote'] . "'
                                                AND numero_do_pedido IN ( select * from (SELECT DISTINCT numero_do_pedido FROM conciliacao_viavarejo WHERE UPPER(tipo_da_transacao) LIKE UPPER('%CANCELAMENTO%') AND lote = '" . $input['hdnLote'] . "') TS )
                                                AND UPPER(tipo_da_transacao) NOT LIKE UPPER('%CANCELAMENTO%')";
                                return $this->db->query($sql6);
                            }
                        }
                    }
                }
            }
        }
    }

    public function atualizavaloresconciliadivisaoViaVArejo($input)
    {

        $sql = "SELECT MAX(valor_aplicado) AS valor_aplicado FROM `param_mkt_categ_integ` WHERE integ_id = '15'";
        $query = $this->db->query($sql);
        $valor = $query->result_array();
        $percentual = $valor[0]['valor_aplicado'];

        //Busca os valores que deverão ser atualizados na conciliação
        $sql = "select * from conciliacao_viavarejo CB WHERE CB.lote = '" . $input['hdnLote'] . "' AND CB.status_conciliacao <> 'Não encontrado'";
        $query = $this->db->query($sql);
        $dadosConciliacao = $query->result_array();

        if($dadosConciliacao){
            foreach($dadosConciliacao as $conciliacao){
                $sql2 = "SELECT 
                                S.name AS seller_name,
                                S.id AS store_id,
                                CASE WHEN O.data_envio IS NULL THEN 'Não Enviado' ELSE 'Enviado' END AS pedido_enviado,
                                CASE WHEN UPPER(FR.ship_company) LIKE CONCAT(\"%\",CONCAT(UPPER(S.name),\"%\")) OR S.freight_seller_type > 0 OR O.freight_seller > 0 THEN 'Seller' ELSE 'Conecta Lá' END AS tipo_frete,
                                '" . $_SESSION['username'] . "' AS usuario,
                                O.gross_amount AS valor_pedido,
                                ROUND(O.gross_amount - O.total_ship,2) AS valor_produto,
                                O.total_ship AS valor_frete,
                                O.frete_real AS valor_frete_real,
                                ROUND(O.total_ship - O.frete_real,2) AS valor_frete_real_contratado,
                                O.service_charge_rate AS valor_percentual_parceiro,
                                $percentual AS valor_percentual_mktplace,
                                ROUND( O.gross_amount - (  O.gross_amount * ($percentual/100)  ) ,2) AS `valor_marketplace`,
                                ROUND( (ROUND(O.gross_amount - O.total_ship,2))  * ($percentual/100),2) AS valor_produto_calculado,
                                ROUND(O.total_ship * ($percentual/100),2) AS valor_frete_calculado,
                                ROUND( ROUND( (ROUND(O.gross_amount - O.total_ship,2))  * ($percentual/100),2) + ROUND(O.total_ship * ($percentual/100),2) ,2) AS valor_receita_calculado,
                                ROUND( ROUND(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100)  ,2) AS valor_produto_parceiro,
                                ROUND( O.total_ship * (O.service_charge_rate/100)  ,2) AS valor_frete_parceiro,
                                ROUND(
                                    (ROUND(O.gross_amount - O.total_ship,2)) -
                                    (ROUND( ROUND(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100)  ,2)) -
                                    (ROUND( O.total_ship * (O.service_charge_rate/100)  ,2) )
                                    ,2) AS valor_parceiro,
                                ROUND(
                                    (ROUND( ROUND(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100)  ,2)) -
                                    (ROUND( (ROUND(O.gross_amount - O.total_ship,2))  * ($percentual/100),2))
                                    ,2) AS valor_produto_conecta,
                                ROUND(
                                    (ROUND(O.total_ship - ( O.total_ship * ( $percentual / 100 ) ),2)) - (O.frete_real)
                                    ,2) AS valor_frete_conecta,
                                ROUND(
                                    (ROUND(
                                    (ROUND( ROUND(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100),2)) -
                                    (ROUND( (ROUND(O.gross_amount - O.total_ship,2))  * ($percentual/100),2))
                                    ,2)) +
                                    ( (ROUND(O.total_ship - ( O.total_ship * ( $percentual / 100 ) ),2)) - (O.frete_real) )
                                    ,2) AS valor_conectala,
                                    ROUND(O.total_ship - ( O.total_ship * ( 10 / 100 ) ),2) AS valor_frete_recebido,
                                    ROUND(O.total_ship - ( O.total_ship * ( 10 / 100 ) ),2) AS valor_produto_recebido,
                                    CASE WHEN DTU.data_usada = 'Data Envio' THEN O.data_mkt_sent ELSE O.data_mkt_delivered END AS data_gatilho
                        FROM orders O
                        INNER JOIN stores S ON S.id = O.store_id
                        LEFT JOIN (SELECT DISTINCT order_id, ship_company FROM freights) FR ON FR.order_id = O.id
                        INNER JOIN (SELECT DISTINCT MAX(PMC.data_usada) AS data_usada, apelido FROM `stores_mkts_linked` SML INNER JOIN `param_mkt_ciclo` PMC ON PMC.integ_id = SML.id_mkt GROUP BY apelido) DTU ON DTU.apelido = O.origin
                        WHERE O.numero_marketplace = '" . $conciliacao['numero_do_pedido'] . "' limit 1";


                $query = $this->db->query($sql2);
                $dadosPedidoConciliacao = $query->result_array();

                if($dadosPedidoConciliacao){
                    $resulPedidoTratamento = $dadosPedidoConciliacao[0];
                        
                    $updateConciliacao = "UPDATE conciliacao_viavarejo CB
                                                SET CB.seller_name = \"" . $resulPedidoTratamento['seller_name'] . "\",
                                                    CB.store_id = '" . $resulPedidoTratamento['store_id'] . "',
                                                    CB.pedido_enviado = '" . $resulPedidoTratamento['pedido_enviado'] . "',
                                                    CB.tipo_frete = '" . $resulPedidoTratamento['tipo_frete'] . "',
                                                    CB.usuario = '" . $resulPedidoTratamento['usuario'] . "',
                                                    CB.valor_pedido = '" . $resulPedidoTratamento['valor_pedido'] . "',
                                                    CB.valor_produto = '" . $resulPedidoTratamento['valor_produto'] . "',
                                                    CB.valor_frete = '" . $resulPedidoTratamento['valor_frete'] . "',
                                                    CB.valor_frete_real = '" . $resulPedidoTratamento['valor_frete_real'] . "',
                                                    CB.valor_frete_real_contratado = '" . $resulPedidoTratamento['valor_frete_real_contratado'] . "',
                                                    CB.valor_percentual_parceiro = '" . $resulPedidoTratamento['valor_percentual_parceiro'] . "',
                                                    CB.valor_percentual_mktplace = '" . $resulPedidoTratamento['valor_percentual_mktplace'] . "',
                                                    CB.`valor_marketplace` = '" . $resulPedidoTratamento['valor_marketplace'] . "',
                                                    CB.valor_produto_calculado = '" . $resulPedidoTratamento['valor_produto_calculado'] . "',
                                                    CB.valor_frete_calculado = '" . $resulPedidoTratamento['valor_frete_calculado'] . "',
                                                    CB.valor_receita_calculado = '" . $resulPedidoTratamento['valor_receita_calculado'] . "',
                                                    CB.valor_produto_parceiro = '" . $resulPedidoTratamento['valor_produto_parceiro'] . "',
                                                    CB.valor_frete_parceiro = '" . $resulPedidoTratamento['valor_frete_parceiro'] . "',
                                                    CB.valor_parceiro = '" . $resulPedidoTratamento['valor_parceiro'] . "',
                                                    CB.valor_produto_conecta = '" . $resulPedidoTratamento['valor_produto_conecta'] . "',
                                                    CB.valor_frete_conecta = '" . $resulPedidoTratamento['valor_frete_conecta'] . "',
                                                    CB.valor_conectala = '" . $resulPedidoTratamento['valor_conectala'] . "',
                                                    CB.valor_frete_recebido = IF(CB.valor_da_transacao IS NULL,NULL,'" . $resulPedidoTratamento['valor_frete_recebido'] . "'),
                                                    CB.valor_produto_recebido =  round( REPLACE(CB.valor_da_transacao,',','.') - '" . $resulPedidoTratamento['valor_produto_recebido'] . "' , 2),
                                                    CB.data_gatilho = '" . $resulPedidoTratamento['data_gatilho'] . "'
                                                WHERE CB.lote = '" . $input['hdnLote'] . "' AND CB.status_conciliacao <> 'Não encontrado' AND CB.numero_do_pedido = '" . $conciliacao['numero_do_pedido'] . "'";
                    
                    $attConciliacao = $this->db->query($updateConciliacao);

                    if(!$attConciliacao){
                        echo '<pre>'.$sql2.'<br>'.$updateConciliacao;die;
                    }
                    
                }

            }

            $sql1 = "UPDATE conciliacao_viavarejo CB SET
                                                    CB.dif_valor_recebido = round(REPLACE(CB.valor_da_transacao,',','.') - CB.valor_marketplace,2),
                                                    CB.dif_valor_recebido_produto = round(CB.valor_produto_recebido - (CB.valor_produto - CB.valor_produto_calculado) ,2),
                                                    CB.dif_valor_recebido_frete = round(CB.valor_frete_recebido - ( CB.valor_frete - CB.valor_frete_calculado) ,2)
            WHERE CB.lote = '" . $input['hdnLote'] . "' AND CB.status_conciliacao NOT IN ('Não encontrado','Estorno')";
            $update2 = $this->db->query($sql1);

            if ($update2) {

                $sqlEst = "UPDATE conciliacao_viavarejo CB SET
                                                    CB.dif_valor_recebido = round(REPLACE(CB.valor_da_transacao,',','.') + CB.valor_marketplace,2),
                                                    CB.dif_valor_recebido_produto = (round((round( REPLACE(CB.valor_da_transacao,',','.') + ( round(CB.valor_frete - ( CB.valor_frete * ( 12 / 100 ) ),2) ) , 2)) + (CB.valor_produto - CB.valor_produto_calculado) ,2)) * -1,
                                                    CB.dif_valor_recebido_frete = round(CB.valor_frete_recebido - ( CB.valor_frete - CB.valor_frete_calculado) ,2),
                                                    CB.valor_produto_recebido = round( REPLACE(CB.valor_da_transacao,',','.') + ( round(CB.valor_frete - ( CB.valor_frete * ( 12 / 100 ) ),2) ) , 2)
                         WHERE CB.lote = '" . $input['hdnLote'] . "' AND CB.status_conciliacao IN ('Estorno')";
                $updateEst = $this->db->query($sqlEst);

                if ($updateEst) {

                    $sql3 = "UPDATE conciliacao_viavarejo SET status_conciliacao = 'Divergente' where
                        lote = '" . $input['hdnLote'] . "' and
                        status_conciliacao = 'Ok' and
                        valor_da_comissao <> REPLACE(CAST(valor_marketplace AS CHAR),',','.') and
                        (dif_valor_recebido > 0 or dif_valor_recebido < 0)";

                    return $this->db->query($sql3);

                }

            }

        }else{
            return true;
        }

        //Atualiza o valor do pedido na tabela
        /*$sql = "UPDATE conciliacao_viavarejo CB
            	INNER JOIN orders O ON CB.numero_do_pedido = O.numero_marketplace
            	INNER JOIN stores S ON S.id = O.store_id
                LEFT JOIN (SELECT DISTINCT order_id, ship_company FROM freights) FR ON FR.order_id = O.id
            	SET CB.seller_name = S.name,
                    CB.store_id = S.id,
                    CB.pedido_enviado = case when O.data_envio is null then 'Não Enviado' else 'Enviado' end,
                    CB.tipo_frete = CASE WHEN UPPER(FR.ship_company) LIKE CONCAT(\"%\",CONCAT(UPPER(S.name),\"%\")) OR S.freight_seller_type > 0 OR O.freight_seller > 0 THEN 'Seller' else 'Conecta Lá' end,
                    CB.usuario = '" . $_SESSION['username'] . "',
                    CB.valor_pedido = O.gross_amount, 
                    CB.valor_produto = round(O.gross_amount - O.total_ship,2),  
                    CB.valor_frete = O.total_ship,
                    CB.valor_frete_real = O.frete_real, 
                    CB.valor_frete_real_contratado = round(O.total_ship - O.frete_real,2),
                    CB.valor_percentual_parceiro = O.service_charge_rate,
                    CB.valor_percentual_mktplace = $percentual,
                    CB.`valor_marketplace` = round( O.gross_amount - (  O.gross_amount * ($percentual/100)  ) ,2),
                    CB.valor_produto_calculado = round( (round(O.gross_amount - O.total_ship,2))  * ($percentual/100),2),
                    CB.valor_frete_calculado = round(O.total_ship * ($percentual/100),2),
                    CB.valor_receita_calculado = round( round( (round(O.gross_amount - O.total_ship,2))  * ($percentual/100),2) + round(O.total_ship * ($percentual/100),2) ,2),
                    CB.valor_produto_parceiro = round( round(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100)  ,2),
                    CB.valor_frete_parceiro = round( O.total_ship * (O.service_charge_rate/100)  ,2),
                    CB.valor_parceiro = round(
                                                (round(O.gross_amount - O.total_ship,2)) - 
                                                (round( round(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100)  ,2)) -
                                                (round( O.total_ship * (O.service_charge_rate/100)  ,2) )
                                            ,2),
                    CB.valor_produto_conecta = round(  
                                                        (round( round(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100)  ,2)) -
                                                        (round( (round(O.gross_amount - O.total_ship,2))  * ($percentual/100),2)) 
                                                    ,2),
                    CB.valor_frete_conecta = round(
                                                    (round(O.total_ship - ( O.total_ship * ( $percentual / 100 ) ),2)) - (O.frete_real)
                                                    ,2),
                    CB.valor_conectala = ROUND( 
                                                    (ROUND(  
                                                            (ROUND( ROUND(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100),2)) -
                                                            (ROUND( (ROUND(O.gross_amount - O.total_ship,2))  * ($percentual/100),2)) 
                                                        ,2)) + 
                                                    ( (round(O.total_ship - ( O.total_ship * ( $percentual / 100 ) ),2)) - (O.frete_real) )
                                                    
                                                ,2),
                CB.valor_frete_recebido = round(O.total_ship - ( O.total_ship * ( $percentual / 100 ) ),2),
                CB.valor_produto_recebido =  round( REPLACE(CB.valor_da_transacao,',','.') - ( round(O.total_ship - ( O.total_ship * ( $percentual / 100 ) ),2) ) , 2)
            	WHERE CB.lote = '" . $input['hdnLote'] . "' AND CB.status_conciliacao <> 'Não encontrado'";*/

    }

    public function consiliaarquivoManual($input)
    {

        //Marca os pedidos não encontrados no arquivo
        $sql = "UPDATE conciliacao_manual SET status_conciliacao = 'Não encontrado' WHERE lote = '" . $input['hdnLote'] . "' AND 
                case when marketplace = 'B2W' then 
                    CASE WHEN LEFT(ref_pedido,2) = '01' THEN CONCAT('Shoptime-',numero_pedido)
                    WHEN LEFT(ref_pedido,2) = '02' THEN CONCAT('Lojas Americanas-',numero_pedido)
                    WHEN LEFT(ref_pedido,2) = '03' THEN CONCAT('Submarino-',numero_pedido)
                    WHEN LEFT(ref_pedido,2) = '09' THEN CONCAT('Americanas Empresas-',numero_pedido) END
                else
                    numero_pedido
                end  NOT IN (SELECT DISTINCT numero_marketplace FROM orders WHERE numero_marketplace IS NOT NULL)";
        $update1 = $this->db->query($sql);
        if ($update1) {

            //Marca os pedidos como OK
            $sql2 = "UPDATE conciliacao_manual SET status_conciliacao = 'Ok' WHERE lote = '" . $input['hdnLote'] . "' AND 
                    case when marketplace = 'B2W' then 
                        CASE WHEN LEFT(ref_pedido,2) = '01' THEN CONCAT('Shoptime-',numero_pedido)
                        WHEN LEFT(ref_pedido,2) = '02' THEN CONCAT('Lojas Americanas-',numero_pedido)
                        WHEN LEFT(ref_pedido,2) = '03' THEN CONCAT('Submarino-',numero_pedido)
                        WHEN LEFT(ref_pedido,2) = '09' THEN CONCAT('Americanas Empresas-',numero_pedido) END
                    else
                        numero_pedido
                    end IN (SELECT DISTINCT numero_marketplace FROM orders WHERE numero_marketplace IS NOT NULL)";
            return $this->db->query($sql2);

        }
    }

    public function atualizavaloresconciliadivisaoManual($input)
    {

        //Busca os valores que deverão ser atualizados na conciliação
        $sql = "select CB.*,case when marketplace = 'B2W' then 
                    CASE WHEN LEFT(ref_pedido,2) = '01' THEN CONCAT('Shoptime-',numero_pedido)
                    WHEN LEFT(ref_pedido,2) = '02' THEN CONCAT('Lojas Americanas-',numero_pedido)
                    WHEN LEFT(ref_pedido,2) = '03' THEN CONCAT('Submarino-',numero_pedido)
                    WHEN LEFT(ref_pedido,2) = '09' THEN CONCAT('Americanas Empresas-',numero_pedido) END
                else
                    numero_pedido end as numero_pedido_join from conciliacao_manual CB WHERE CB.lote = '" . $input['hdnLote'] . "' AND CB.status_conciliacao <> 'Não encontrado'";
        $query = $this->db->query($sql);
        $dadosConciliacao = $query->result_array();

        if($dadosConciliacao){
            foreach($dadosConciliacao as $conciliacao){
                $sql2 = "SELECT 
                                S.name AS seller_name,
                                S.id AS store_id,
                                CASE WHEN O.data_envio IS NULL THEN 'Não Enviado' ELSE 'Enviado' END AS pedido_enviado,
                                CASE WHEN UPPER(FR.ship_company) LIKE CONCAT(\"%\",CONCAT(UPPER(S.name),\"%\")) OR S.freight_seller_type > 0 OR O.freight_seller > 0 THEN 'Seller' ELSE 'Conecta Lá' END AS tipo_frete,
                                '" . $_SESSION['username'] . "' AS usuario,
                                O.gross_amount AS valor_pedido,
                                ROUND(O.gross_amount - O.total_ship,2) AS valor_produto,
                                O.total_ship AS valor_frete,
                                O.frete_real AS valor_frete_real,
                                ROUND(O.total_ship - O.frete_real,2) AS valor_frete_real_contratado,
                                O.service_charge_rate AS valor_percentual_parceiro,
                                PMCI.valor_aplicado AS valor_percentual_mktplace,
                                ROUND( O.gross_amount - (  O.gross_amount * (PMCI.valor_aplicado/100)  ) ,2) AS `valor_marketplace`,
                                ROUND( (ROUND(O.gross_amount - O.total_ship,2))  * (PMCI.valor_aplicado/100),2) AS valor_produto_calculado,
                                ROUND(O.total_ship * (PMCI.valor_aplicado/100),2) AS valor_frete_calculado,
                                ROUND( ROUND( (ROUND(O.gross_amount - O.total_ship,2))  * (PMCI.valor_aplicado/100),2) + ROUND(O.total_ship * (PMCI.valor_aplicado/100),2) ,2) AS valor_receita_calculado,
                                ROUND( ROUND(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100)  ,2) AS valor_produto_parceiro,
                                ROUND( O.total_ship * (O.service_charge_rate/100)  ,2) AS valor_frete_parceiro,
                                CASE WHEN UPPER(FR.ship_company) LIKE CONCAT(\"%\",CONCAT(UPPER(S.name),\"%\")) OR S.freight_seller_type > 0 OR O.freight_seller > 0 THEN
                                    ROUND(
                                        (ROUND(O.gross_amount,2)) -
                                        (ROUND( ROUND(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100)  ,2)) -
                                        (ROUND( O.total_ship * (O.service_charge_rate/100)  ,2) )
                                        ,2) 
                                else
                                    ROUND(
                                        (ROUND(O.gross_amount - O.total_ship,2)) -
                                        (ROUND( ROUND(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100)  ,2)) -
                                        (ROUND( O.total_ship * (O.service_charge_rate/100)  ,2) )
                                        ,2) 
                                    end AS valor_parceiro,
                                ROUND(
                                    (ROUND( ROUND(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100)  ,2)) -
                                    (ROUND( (ROUND(O.gross_amount - O.total_ship,2))  * (PMCI.valor_aplicado/100),2))
                                    ,2) AS valor_produto_conecta,
                                ROUND(
                                    (ROUND(O.total_ship - ( O.total_ship * ( PMCI.valor_aplicado / 100 ) ),2)) - (O.frete_real)
                                    ,2) AS valor_frete_conecta,
                                ROUND(
                                    (ROUND(
                                    (ROUND( ROUND(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100),2)) -
                                    (ROUND( (ROUND(O.gross_amount - O.total_ship,2))  * (PMCI.valor_aplicado/100),2))
                                    ,2)) +
                                    ( (ROUND(O.total_ship - ( O.total_ship * ( PMCI.valor_aplicado / 100 ) ),2)) - (O.frete_real) )
                                    ,2) AS valor_conectala,
                                    ROUND(O.total_ship - ( O.total_ship * ( 10 / 100 ) ),2) AS valor_frete_recebido,
                                    ROUND(O.total_ship - ( O.total_ship * ( 10 / 100 ) ),2) AS valor_produto_recebido,
                                    CASE WHEN DTU.data_usada = 'Data Envio' THEN O.data_mkt_sent ELSE O.data_mkt_delivered END AS data_gatilho
                        FROM orders O
                        INNER JOIN stores S ON S.id = O.store_id
                        LEFT JOIN (SELECT DISTINCT order_id, ship_company FROM freights) FR ON FR.order_id = O.id
                        INNER JOIN (SELECT SML.apelido, MAX(PMCI.valor_aplicado) AS valor_aplicado FROM `param_mkt_categ_integ` PMCI INNER JOIN `stores_mkts_linked` SML ON SML.id_mkt = PMCI.integ_id GROUP BY SML.apelido) PMCI ON PMCI.apelido = O.origin
                        INNER JOIN (SELECT DISTINCT MAX(PMC.data_usada) AS data_usada, apelido FROM `stores_mkts_linked` SML INNER JOIN `param_mkt_ciclo` PMC ON PMC.integ_id = SML.id_mkt GROUP BY apelido) DTU ON DTU.apelido = O.origin
                        WHERE O.numero_marketplace = '" . $conciliacao['numero_pedido_join'] . "' limit 1";

                $query = $this->db->query($sql2);
                $dadosPedidoConciliacao = $query->result_array();

                if($dadosPedidoConciliacao){
                    $resulPedidoTratamento = $dadosPedidoConciliacao[0];

                    $updateConciliacao = "UPDATE conciliacao_manual CB
                                                SET CB.seller_name = \"" . $resulPedidoTratamento['seller_name'] . "\",
                                                    CB.store_id = '" . $resulPedidoTratamento['store_id'] . "',
                                                    CB.pedido_enviado = '" . $resulPedidoTratamento['pedido_enviado'] . "',
                                                    CB.tipo_frete = '" . $resulPedidoTratamento['tipo_frete'] . "',
                                                    CB.usuario = '" . $resulPedidoTratamento['usuario'] . "',
                                                    CB.valor_pedido = '" . $resulPedidoTratamento['valor_pedido'] . "',
                                                    CB.valor_produto = '" . $resulPedidoTratamento['valor_produto'] . "',
                                                    CB.valor_frete = '" . $resulPedidoTratamento['valor_frete'] . "',
                                                    CB.valor_frete_real = '" . $resulPedidoTratamento['valor_frete_real'] . "',
                                                    CB.valor_frete_real_contratado = '" . $resulPedidoTratamento['valor_frete_real_contratado'] . "',
                                                    CB.valor_percentual_parceiro = '" . $resulPedidoTratamento['valor_percentual_parceiro'] . "',
                                                    CB.valor_percentual_mktplace = '" . $resulPedidoTratamento['valor_percentual_mktplace'] . "',
                                                    CB.`valor_marketplace` = '" . $resulPedidoTratamento['valor_marketplace'] . "',
                                                    CB.valor_produto_calculado = '" . $resulPedidoTratamento['valor_produto_calculado'] . "',
                                                    CB.valor_frete_calculado = '" . $resulPedidoTratamento['valor_frete_calculado'] . "',
                                                    CB.valor_receita_calculado = '" . $resulPedidoTratamento['valor_receita_calculado'] . "',
                                                    CB.valor_produto_parceiro = '" . $resulPedidoTratamento['valor_produto_parceiro'] . "',
                                                    CB.valor_frete_parceiro = '" . $resulPedidoTratamento['valor_frete_parceiro'] . "',
                                                    CB.valor_parceiro = '" . $resulPedidoTratamento['valor_parceiro'] . "',
                                                    CB.valor_produto_conecta = '" . $resulPedidoTratamento['valor_produto_conecta'] . "',
                                                    CB.valor_frete_conecta = '" . $resulPedidoTratamento['valor_frete_conecta'] . "',
                                                    CB.valor_conectala = '" . $resulPedidoTratamento['valor_conectala'] . "',
                                                CB.valor_frete_recebido = 0,
                                                CB.valor_produto_recebido =  0,
                                                CB.data_gatilho = '" . $resulPedidoTratamento['data_gatilho'] . "'
                                                WHERE CB.lote = '" . $input['hdnLote'] . "' AND CB.status_conciliacao <> 'Não encontrado' AND CB.numero_pedido = '" . $conciliacao['numero_pedido'] . "'";

                    $attConciliacao = $this->db->query($updateConciliacao);

                    if(!$attConciliacao){
                        return false;
                    }

                }

            }

            return true;
           /* $sql1 = "UPDATE conciliacao_mercadolivre CB SET
                                                    CB.dif_valor_recebido = round(REPLACE(CB.net_credit_amount,',','.') - CB.valor_marketplace,2),
                                                    CB.dif_valor_recebido_produto = round(CB.valor_produto_recebido - (CB.valor_produto - CB.valor_produto_calculado) ,2),
                                                    CB.dif_valor_recebido_frete = round(CB.valor_frete_recebido - ( CB.valor_frete - CB.valor_frete_calculado) ,2)
            WHERE CB.lote = '" . $input['hdnLote'] . "' AND CB.status_conciliacao NOT IN ('Não encontrado','Estorno')";
            $update2 = $this->db->query($sql1);
            if ($update2) {
                $sqlEst = "UPDATE conciliacao_mercadolivre CB SET
                                                    CB.dif_valor_recebido = round(REPLACE(CB.net_credit_amount,',','.') - CB.valor_marketplace,2),
                                                    CB.dif_valor_recebido_produto = (round((round( REPLACE(CB.net_credit_amount,',','.') + ( round(CB.valor_frete - ( CB.valor_frete * ( 12 / 100 ) ),2) ) , 2)) + (CB.valor_produto - CB.valor_produto_calculado) ,2)) * -1,
                                                    CB.dif_valor_recebido_frete = round(CB.valor_frete_recebido - ( CB.valor_frete - CB.valor_frete_calculado) ,2),
                                                    CB.valor_produto_recebido = round( REPLACE(CB.net_credit_amount,',','.') + ( round(CB.valor_frete - ( CB.valor_frete * ( 12 / 100 ) ),2) ) , 2)
                         WHERE CB.lote = '" . $input['hdnLote'] . "' AND CB.status_conciliacao IN ('Estorno')";
                $updateEst = $this->db->query($sqlEst);
                if ($updateEst) {
                    $sql3 = "UPDATE conciliacao_mercadolivre SET status_conciliacao = 'Divergente' where
                        lote = '" . $input['hdnLote'] . "' and
                        status_conciliacao = 'Ok' and
                        MP_FEE_AMOUNT <> REPLACE(CAST(valor_marketplace AS CHAR),',','.') and
                        (dif_valor_recebido > 0 or dif_valor_recebido < 0)";
                    return $this->db->query($sql3);
                }
            } */

        }else{
            return true;
        }

    }


    public function consiliaarquivoML($input)
    {

        //Marca os pedidos não encontrados no arquivo
        $sql = "UPDATE conciliacao_mercadolivre SET status_conciliacao = 'Não encontrado' WHERE lote = '" . $input['hdnLote'] . "' AND external_reference NOT IN (SELECT DISTINCT numero_marketplace FROM orders WHERE numero_marketplace IS NOT NULL)";
        $update1 = $this->db->query($sql);

        if ($update1) {
            //Marca os pedidos de estorno
            $sql2 = "UPDATE conciliacao_mercadolivre SET status_conciliacao = 'Estorno' WHERE lote = '" . $input['hdnLote'] . "' and UPPER(description) LIKE UPPER('%refund%') AND status_conciliacao IS NULL AND external_reference IN ( select * from (SELECT DISTINCT external_reference FROM conciliacao_mercadolivre WHERE UPPER(description) LIKE UPPER('%refund%') AND lote = '" . $input['hdnLote'] . "') TS )";
            // 	    $sql2 = "UPDATE conciliacao_mercadolivre CV
            //                 inner join (SELECT DISTINCT external_reference FROM conciliacao_mercadolivre WHERE UPPER(description) LIKE UPPER('%refund%') AND lote = '".$input['hdnLote']."' ) P on CV.external_reference = P.external_reference
            //                 SET status_conciliacao = 'Estorno'
            //                 WHERE lote = '".$input['hdnLote']."' AND status_conciliacao IS NULL";
            $update2 = $this->db->query($sql2);

            if ($update2) {
                // Marca os pedidos Ok
                $sql3 = "UPDATE conciliacao_mercadolivre SET status_conciliacao = 'Ok' WHERE lote = '" . $input['hdnLote'] . "' and UPPER(description) LIKE UPPER('%payment%') AND external_reference IN (
                        SELECT DISTINCT ARQ.external_reference FROM (
                        SELECT DISTINCT external_reference, REPLACE(CAST(gross_amount AS CHAR),',','.') AS valor_total FROM conciliacao_mercadolivre WHERE lote = '" . $input['hdnLote'] . "' AND UPPER(description) LIKE UPPER('%payment%') ) ARQ
                        INNER JOIN (
                        SELECT numero_marketplace, total_order + total_ship AS valor_total FROM orders WHERE numero_marketplace IS NOT NULL) ORDS ON ORDS.numero_marketplace = ARQ.external_reference AND (ROUND(REPLACE(CAST(ARQ.valor_total AS CHAR),',','.'),2) = ROUND(REPLACE(CAST(ORDS.valor_total AS CHAR),',','.'),2) OR(  ROUND(REPLACE(CAST(ARQ.valor_total AS CHAR),',','.'),2) - ROUND(REPLACE(CAST(ORDS.valor_total AS CHAR),',','.'),2) IN ('-0.01','0.01','0.00','-0.00') ))  )";
                $update3 = $this->db->query($sql3);

                if ($update3) {
                    // Marca os pedidos Divergentes
                    $sql4 = "UPDATE conciliacao_mercadolivre SET status_conciliacao = 'Divergente' WHERE lote = '" . $input['hdnLote'] . "' and UPPER(description) LIKE UPPER('%payment%') AND external_reference IN (
    	                SELECT DISTINCT ARQ.external_reference FROM (
                        SELECT DISTINCT external_reference, REPLACE(CAST(gross_amount AS CHAR),',','.') AS valor_total FROM conciliacao_mercadolivre WHERE lote = '" . $input['hdnLote'] . "' AND UPPER(description) LIKE UPPER('%payment%') ) ARQ
                        INNER JOIN (
                        SELECT numero_marketplace, total_order + total_ship AS valor_total FROM orders WHERE numero_marketplace IS NOT NULL) ORDS ON ORDS.numero_marketplace = ARQ.external_reference AND ARQ.valor_total <> ORDS.valor_total)";
                    $update4 = $this->db->query($sql4);

                    if ($update4) {
                        // Marca como Outros as linhas sem número de pedido
                        $sql5 = "UPDATE conciliacao_mercadolivre SET status_conciliacao = 'Outros' WHERE lote = '" . $input['hdnLote'] . "' AND (external_reference is null or external_reference = '')";
                        $update5 = $this->db->query($sql5);

                        if ($update5) {
                            // Marca como Nulo os pedidos que tem estorno neles
                            $sql6 = "UPDATE conciliacao_mercadolivre SET status_conciliacao = NULL 
                                     WHERE lote = '" . $input['hdnLote'] . "' 
                                            AND external_reference IN ( select * from (SELECT DISTINCT external_reference FROM conciliacao_mercadolivre WHERE UPPER(description) LIKE UPPER('%refund%') AND lote = '" . $input['hdnLote'] . "') TS )
                                            AND UPPER(description) NOT LIKE UPPER('%refund%')";
                            return $this->db->query($sql6);
                        }
                    }
                }
            }
        }
    }

    public function atualizavaloresconciliadivisaoML($input)
    {

        $sql = "SELECT MAX(valor_aplicado) AS valor_aplicado FROM `param_mkt_categ_integ` WHERE integ_id = '11'";
        $query = $this->db->query($sql);
        $valor = $query->result_array();
        $percentual = $valor[0]['valor_aplicado'];

        //Busca os valores que deverão ser atualizados na conciliação
        $sql = "select * from conciliacao_mercadolivre CB WHERE CB.lote = '" . $input['hdnLote'] . "' AND CB.status_conciliacao <> 'Não encontrado'";
        $query = $this->db->query($sql);
        $dadosConciliacao = $query->result_array();

        if($dadosConciliacao){
            foreach($dadosConciliacao as $conciliacao){
                $sql2 = "SELECT 
                                S.name AS seller_name,
                                S.id AS store_id,
                                CASE WHEN O.data_envio IS NULL THEN 'Não Enviado' ELSE 'Enviado' END AS pedido_enviado,
                                CASE WHEN UPPER(FR.ship_company) LIKE CONCAT(\"%\",CONCAT(UPPER(S.name),\"%\")) OR S.freight_seller_type > 0 OR O.freight_seller > 0 THEN 'Seller' ELSE 'Conecta Lá' END AS tipo_frete,
                                '" . $_SESSION['username'] . "' AS usuario,
                                O.gross_amount AS valor_pedido,
                                ROUND(O.gross_amount - O.total_ship,2) AS valor_produto,
                                O.total_ship AS valor_frete,
                                O.frete_real AS valor_frete_real,
                                ROUND(O.total_ship - O.frete_real,2) AS valor_frete_real_contratado,
                                O.service_charge_rate AS valor_percentual_parceiro,
                                $percentual AS valor_percentual_mktplace,
                                ROUND( O.gross_amount - (  O.gross_amount * ($percentual/100)  ) ,2) AS `valor_marketplace`,
                                ROUND( (ROUND(O.gross_amount - O.total_ship,2))  * ($percentual/100),2) AS valor_produto_calculado,
                                ROUND(O.total_ship * ($percentual/100),2) AS valor_frete_calculado,
                                ROUND( ROUND( (ROUND(O.gross_amount - O.total_ship,2))  * ($percentual/100),2) + ROUND(O.total_ship * ($percentual/100),2) ,2) AS valor_receita_calculado,
                                ROUND( ROUND(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100)  ,2) AS valor_produto_parceiro,
                                ROUND( O.total_ship * (O.service_charge_rate/100)  ,2) AS valor_frete_parceiro,
                                ROUND(
                                    (ROUND(O.gross_amount - O.total_ship,2)) -
                                    (ROUND( ROUND(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100)  ,2)) -
                                    (ROUND( O.total_ship * (O.service_charge_rate/100)  ,2) )
                                    ,2) AS valor_parceiro,
                                ROUND(
                                    (ROUND( ROUND(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100)  ,2)) -
                                    (ROUND( (ROUND(O.gross_amount - O.total_ship,2))  * ($percentual/100),2))
                                    ,2) AS valor_produto_conecta,
                                ROUND(
                                    (ROUND(O.total_ship - ( O.total_ship * ( $percentual / 100 ) ),2)) - (O.frete_real)
                                    ,2) AS valor_frete_conecta,
                                ROUND(
                                    (ROUND(
                                    (ROUND( ROUND(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100),2)) -
                                    (ROUND( (ROUND(O.gross_amount - O.total_ship,2))  * ($percentual/100),2))
                                    ,2)) +
                                    ( (ROUND(O.total_ship - ( O.total_ship * ( $percentual / 100 ) ),2)) - (O.frete_real) )
                                    ,2) AS valor_conectala,
                                    ROUND(O.total_ship - ( O.total_ship * ( 10 / 100 ) ),2) AS valor_frete_recebido,
                                    ROUND(O.total_ship - ( O.total_ship * ( 10 / 100 ) ),2) AS valor_produto_recebido,
                                    CASE WHEN DTU.data_usada = 'Data Envio' THEN O.data_mkt_sent ELSE O.data_mkt_delivered END AS data_gatilho
                                    INNER JOIN (SELECT DISTINCT MAX(PMC.data_usada) AS data_usada, apelido FROM `stores_mkts_linked` SML INNER JOIN `param_mkt_ciclo` PMC ON PMC.integ_id = SML.id_mkt GROUP BY apelido) DTU ON DTU.apelido = O.origin
                        FROM orders O
                        INNER JOIN stores S ON S.id = O.store_id
                        LEFT JOIN (SELECT DISTINCT order_id, ship_company FROM freights) FR ON FR.order_id = O.id
                        WHERE O.numero_marketplace = '" . $conciliacao['external_reference'] . "' limit 1";

                $query = $this->db->query($sql2);
                $dadosPedidoConciliacao = $query->result_array();

                if($dadosPedidoConciliacao){
                    $resulPedidoTratamento = $dadosPedidoConciliacao[0];
                        
                    $updateConciliacao = "UPDATE conciliacao_mercadolivre CB
                                                SET CB.seller_name = \"" . $resulPedidoTratamento['seller_name'] . "\",
                                                    CB.store_id = '" . $resulPedidoTratamento['store_id'] . "',
                                                    CB.pedido_enviado = '" . $resulPedidoTratamento['pedido_enviado'] . "',
                                                    CB.tipo_frete = '" . $resulPedidoTratamento['tipo_frete'] . "',
                                                    CB.usuario = '" . $resulPedidoTratamento['usuario'] . "',
                                                    CB.valor_pedido = '" . $resulPedidoTratamento['valor_pedido'] . "',
                                                    CB.valor_produto = '" . $resulPedidoTratamento['valor_produto'] . "',
                                                    CB.valor_frete = '" . $resulPedidoTratamento['valor_frete'] . "',
                                                    CB.valor_frete_real = '" . $resulPedidoTratamento['valor_frete_real'] . "',
                                                    CB.valor_frete_real_contratado = '" . $resulPedidoTratamento['valor_frete_real_contratado'] . "',
                                                    CB.valor_percentual_parceiro = '" . $resulPedidoTratamento['valor_percentual_parceiro'] . "',
                                                    CB.valor_percentual_mktplace = '" . $resulPedidoTratamento['valor_percentual_mktplace'] . "',
                                                    CB.`valor_marketplace` = '" . $resulPedidoTratamento['valor_marketplace'] . "',
                                                    CB.valor_produto_calculado = '" . $resulPedidoTratamento['valor_produto_calculado'] . "',
                                                    CB.valor_frete_calculado = '" . $resulPedidoTratamento['valor_frete_calculado'] . "',
                                                    CB.valor_receita_calculado = '" . $resulPedidoTratamento['valor_receita_calculado'] . "',
                                                    CB.valor_produto_parceiro = '" . $resulPedidoTratamento['valor_produto_parceiro'] . "',
                                                    CB.valor_frete_parceiro = '" . $resulPedidoTratamento['valor_frete_parceiro'] . "',
                                                    CB.valor_parceiro = '" . $resulPedidoTratamento['valor_parceiro'] . "',
                                                    CB.valor_produto_conecta = '" . $resulPedidoTratamento['valor_produto_conecta'] . "',
                                                    CB.valor_frete_conecta = '" . $resulPedidoTratamento['valor_frete_conecta'] . "',
                                                    CB.valor_conectala = '" . $resulPedidoTratamento['valor_conectala'] . "',
                                                CB.valor_frete_recebido = IF(CB.net_credit_amount IS NULL,NULL,'" . $resulPedidoTratamento['valor_frete_recebido'] . "'),
                                                CB.valor_produto_recebido =  round( REPLACE(CB.net_credit_amount,',','.') - '" . $resulPedidoTratamento['valor_produto_recebido'] . "' , 2),
                                                CB.data_gatilho = '" . $resulPedidoTratamento['data_gatilho'] . "'
                                                WHERE CB.lote = '" . $input['hdnLote'] . "' AND CB.status_conciliacao <> 'Não encontrado' AND CB.external_reference = '" . $conciliacao['external_reference'] . "'";
                    
                    $attConciliacao = $this->db->query($updateConciliacao);

                    if(!$attConciliacao){
                        return false;
                    }
                    
                }
                      
            }

            $sql1 = "UPDATE conciliacao_mercadolivre CB SET
                                                    CB.dif_valor_recebido = round(REPLACE(CB.net_credit_amount,',','.') - CB.valor_marketplace,2),
                                                    CB.dif_valor_recebido_produto = round(CB.valor_produto_recebido - (CB.valor_produto - CB.valor_produto_calculado) ,2),
                                                    CB.dif_valor_recebido_frete = round(CB.valor_frete_recebido - ( CB.valor_frete - CB.valor_frete_calculado) ,2)
            WHERE CB.lote = '" . $input['hdnLote'] . "' AND CB.status_conciliacao NOT IN ('Não encontrado','Estorno')";
            $update2 = $this->db->query($sql1);

            if ($update2) {

                $sqlEst = "UPDATE conciliacao_mercadolivre CB SET
                                                    CB.dif_valor_recebido = round(REPLACE(CB.net_credit_amount,',','.') - CB.valor_marketplace,2),
                                                    CB.dif_valor_recebido_produto = (round((round( REPLACE(CB.net_credit_amount,',','.') + ( round(CB.valor_frete - ( CB.valor_frete * ( 12 / 100 ) ),2) ) , 2)) + (CB.valor_produto - CB.valor_produto_calculado) ,2)) * -1,
                                                    CB.dif_valor_recebido_frete = round(CB.valor_frete_recebido - ( CB.valor_frete - CB.valor_frete_calculado) ,2),
                                                    CB.valor_produto_recebido = round( REPLACE(CB.net_credit_amount,',','.') + ( round(CB.valor_frete - ( CB.valor_frete * ( 12 / 100 ) ),2) ) , 2)
                         WHERE CB.lote = '" . $input['hdnLote'] . "' AND CB.status_conciliacao IN ('Estorno')";
                $updateEst = $this->db->query($sqlEst);

                if ($updateEst) {

                    $sql3 = "UPDATE conciliacao_mercadolivre SET status_conciliacao = 'Divergente' where
                        lote = '" . $input['hdnLote'] . "' and
                        status_conciliacao = 'Ok' and
                        MP_FEE_AMOUNT <> REPLACE(CAST(valor_marketplace AS CHAR),',','.') and
                        (dif_valor_recebido > 0 or dif_valor_recebido < 0)";

                    return $this->db->query($sql3);

                }

            }

        }else{
            return true;
        }

        //Atualiza o valor do pedido na tabela
        /*$sql = "UPDATE conciliacao_mercadolivre CB
            	INNER JOIN orders O ON CB.external_reference = O.numero_marketplace
            	INNER JOIN stores S ON S.id = O.store_id
                LEFT JOIN (SELECT DISTINCT order_id, ship_company FROM freights) FR ON FR.order_id = O.id
            	SET CB.seller_name = S.name,
                    CB.store_id = S.id,
                    CB.pedido_enviado = case when O.data_envio is null then 'Não Enviado' else 'Enviado' end,
                    CB.tipo_frete = CASE WHEN UPPER(FR.ship_company) LIKE CONCAT(\"%\",CONCAT(UPPER(S.name),\"%\")) OR S.freight_seller_type > 0 OR O.freight_seller > 0 THEN 'Seller' else 'Conecta Lá' end,
                    CB.usuario = '" . $_SESSION['username'] . "',
                    CB.valor_pedido = O.gross_amount,
                    CB.valor_produto = round(O.gross_amount - O.total_ship,2),
                    CB.valor_frete = O.total_ship,
                    CB.valor_frete_real = O.frete_real,
                    CB.valor_frete_real_contratado = round(O.total_ship - O.frete_real,2),
                    CB.valor_percentual_parceiro = O.service_charge_rate,
                    CB.valor_percentual_mktplace = $percentual,
                    CB.valor_marketplace = round( O.gross_amount - (  O.gross_amount * ($percentual/100)  ) ,2),
                    CB.valor_produto_calculado = round( (round(O.gross_amount - O.total_ship,2))  * ($percentual/100),2),
                    CB.valor_frete_calculado = round(O.total_ship * ($percentual/100),2),
                    CB.valor_receita_calculado = round( round( (round(O.gross_amount - O.total_ship,2))  * ($percentual/100),2) + round(O.total_ship * ($percentual/100),2) ,2),
                    CB.valor_produto_parceiro = round( round(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100)  ,2),
                    CB.valor_frete_parceiro = round( O.total_ship * (O.service_charge_rate/100)  ,2),
                    CB.valor_parceiro = round(
                                                (round(O.gross_amount - O.total_ship,2)) -
                                                (round( round(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100)  ,2)) -
                                                (round( O.total_ship * (O.service_charge_rate/100)  ,2) )
                                            ,2),
                    CB.valor_produto_conecta = round(
                                                        (round( O.gross_amount - (  O.gross_amount * ($percentual/100)  ) ,2)) -
                                                        
                                                        ((round(O.gross_amount - O.total_ship,2)) -
                                                        (round( round(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100)  ,2)) -
                                                        (round( O.total_ship * (O.service_charge_rate/100)  ,2) )) -
                                                        O.frete_real
                                                    ,2),
                                                    
                    CB.valor_frete_conecta = round( O.total_ship - O.frete_real ,2),
                    CB.valor_conectala = ROUND(
                                                    (ROUND(
                                                            (round( O.gross_amount - (  O.gross_amount * ($percentual/100)  ) ,2)) -
                                                        
                                                            ((round(O.gross_amount - O.total_ship,2)) -
                                                            (round( round(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100)  ,2)) -
                                                            (round( O.total_ship * (O.service_charge_rate/100)  ,2) )) -
    
                                                            O.frete_real
                                                        ,2)) +
                                                    ( round( O.total_ship - O.frete_real ,2) )
                                                    
                                                ,2),
                CB.valor_frete_recebido = round(O.total_ship - ( O.total_ship * ( $percentual / 100 ) ),2),
                CB.valor_produto_recebido =  round( REPLACE(CB.net_credit_amount,',','.') - ( round(O.total_ship - ( O.total_ship * ( $percentual / 100 ) ),2) ) , 2)
                
            	WHERE CB.lote = '" . $input['hdnLote'] . "' AND CB.status_conciliacao <> 'Não encontrado'";*/
        

    }

    public function consiliaarquivoCarrefour($input)
    {

        //Marca os pedidos não encontrados no arquivo
        $sql = "UPDATE conciliacao_carrefour SET status_conciliacao = 'Não encontrado' WHERE lote = '" . $input['hdnLote'] . "' AND n_do_pedido NOT IN (SELECT DISTINCT numero_marketplace FROM orders WHERE numero_marketplace IS NOT NULL)";
        $update1 = $this->db->query($sql);

        if ($update1) {
            //Marca os pedidos de estorno
            $sql2 = "UPDATE conciliacao_carrefour SET status_conciliacao = 'Estorno' WHERE lote = '" . $input['hdnLote'] . "' and UPPER(status_do_pedido) LIKE UPPER('%débito%') AND status_conciliacao IS NULL AND n_do_pedido IN ( select * from (SELECT DISTINCT n_do_pedido FROM conciliacao_carrefour WHERE UPPER(status_do_pedido) LIKE UPPER('%débito%') AND lote = '" . $input['hdnLote'] . "') TS )";
            $update2 = $this->db->query($sql2);

            if ($update2) {
                // Marca os pedidos Ok
                $sql3 = "UPDATE conciliacao_carrefour SET status_conciliacao = 'Ok' WHERE lote = '" . $input['hdnLote'] . "' and UPPER(status_do_pedido) LIKE UPPER('%Recebido%') AND n_do_pedido IN (
                            SELECT DISTINCT ARQ.n_do_pedido FROM (
                            SELECT DISTINCT n_do_pedido, REPLACE(CAST(preco_total_com_imposto AS CHAR),',','.') AS valor_total FROM conciliacao_carrefour WHERE lote = '" . $input['hdnLote'] . "' AND UPPER(status_do_pedido) LIKE UPPER('%Recebido%') ) ARQ
                            INNER JOIN (
                            SELECT numero_marketplace, total_order + total_ship AS valor_total FROM orders WHERE numero_marketplace IS NOT NULL) ORDS ON ORDS.numero_marketplace = ARQ.n_do_pedido AND ARQ.valor_total = ORDS.valor_total)";
                $update3 = $this->db->query($sql3);

                if ($update3) {
                    // Marca os pedidos Divergentes
                    $sql4 = "UPDATE conciliacao_carrefour SET status_conciliacao = 'Divergente' WHERE lote = '" . $input['hdnLote'] . "' and UPPER(status_do_pedido) LIKE UPPER('%Recebido%') AND n_do_pedido IN (
        	                SELECT DISTINCT ARQ.n_do_pedido FROM (
                            SELECT DISTINCT n_do_pedido, REPLACE(CAST(preco_total_com_imposto AS CHAR),',','.') AS valor_total FROM conciliacao_carrefour WHERE lote = '" . $input['hdnLote'] . "' AND UPPER(status_do_pedido) LIKE UPPER('%Recebido%') ) ARQ
                            INNER JOIN (
                            SELECT numero_marketplace, total_order + total_ship AS valor_total FROM orders WHERE numero_marketplace IS NOT NULL) ORDS ON ORDS.numero_marketplace = ARQ.n_do_pedido AND ARQ.valor_total <> ORDS.valor_total)";
                    $update4 = $this->db->query($sql4);

                    if ($update4) {
                        // Marca como Outros as linhas sem número de pedido
                        $sql5 = "UPDATE conciliacao_carrefour SET status_conciliacao = 'Outros' WHERE lote = '" . $input['hdnLote'] . "' AND (n_do_pedido is null or n_do_pedido = '')";
                        $update5 = $this->db->query($sql5);

                        if ($update5) {
                            // Marca como Nulo os pedidos que tem estorno neles
                            $sql6 = "UPDATE conciliacao_carrefour SET status_conciliacao = NULL
                                         WHERE lote = '" . $input['hdnLote'] . "'
                                                AND n_do_pedido IN ( select * from (SELECT DISTINCT n_do_pedido FROM conciliacao_carrefour WHERE UPPER(status_do_pedido) LIKE UPPER('%débito%') AND lote = '" . $input['hdnLote'] . "') TS )
                                                AND UPPER(status_do_pedido) NOT LIKE UPPER('%débito%')";
                            return $this->db->query($sql6);
                        }
                    }
                }
            }
        }
    }

    public function atualizavaloresconciliadivisaoCarrefour($input)
    {

        $sql = "SELECT MAX(valor_aplicado) AS valor_aplicado FROM `param_mkt_categ_integ` WHERE integ_id = '16'";
        $query = $this->db->query($sql);
        $valor = $query->result_array();
        $percentual = $valor[0]['valor_aplicado'];

        //Atualiza o valor do pedido na tabela
        $sql = "UPDATE conciliacao_carrefour CB
            	INNER JOIN orders O ON CB.n_do_pedido = O.numero_marketplace
            	INNER JOIN stores S ON S.id = O.store_id
                LEFT JOIN (SELECT DISTINCT order_id, ship_company FROM freights) FR ON FR.order_id = O.id
                INNER JOIN (SELECT DISTINCT MAX(PMC.data_usada) AS data_usada, apelido FROM `stores_mkts_linked` SML INNER JOIN `param_mkt_ciclo` PMC ON PMC.integ_id = SML.id_mkt GROUP BY apelido) DTU ON DTU.apelido = O.origin
            	SET CB.seller_name = S.name,
                    CB.store_id = S.id,
                    CB.pedido_enviado = case when O.data_envio is null then 'Não Enviado' else 'Enviado' end,
                    CB.tipo_frete = CASE WHEN UPPER(FR.ship_company) LIKE CONCAT(\"%\",CONCAT(UPPER(S.name),\"%\")) OR S.freight_seller_type > 0 OR O.freight_seller > 0 THEN 'Seller' else 'Conecta Lá' end,
                    CB.usuario = '" . $_SESSION['username'] . "',
                    CB.valor_pedido = O.gross_amount,
                    CB.valor_produto = round(O.gross_amount - O.total_ship,2),
                    CB.valor_frete = O.total_ship,
                    CB.valor_frete_real = O.frete_real,
                    CB.valor_frete_real_contratado = round(O.total_ship - O.frete_real,2),
                    CB.valor_percentual_parceiro = O.service_charge_rate,
                    CB.valor_percentual_mktplace = $percentual,
                    CB.`valor_marketplace` = round( O.gross_amount - (  O.gross_amount * ($percentual/100)  ) ,2),
                    CB.valor_produto_calculado = round( (round(O.gross_amount - O.total_ship,2))  * ($percentual/100),2),
                    CB.valor_frete_calculado = round(O.total_ship * ($percentual/100),2),
                    CB.valor_receita_calculado = round( round( (round(O.gross_amount - O.total_ship,2))  * ($percentual/100),2) + round(O.total_ship * ($percentual/100),2) ,2),
                    CB.valor_produto_parceiro = round( round(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100)  ,2),
                    CB.valor_frete_parceiro = round( O.total_ship * (O.service_charge_rate/100)  ,2),
                    CB.valor_parceiro = round(
                                                (round(O.gross_amount - O.total_ship,2)) -
                                                (round( round(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100)  ,2)) -
                                                (round( O.total_ship * (O.service_charge_rate/100)  ,2) )
                                            ,2),
                    CB.valor_produto_conecta = round(
                                                        (round( round(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100)  ,2)) -
                                                        (round( (round(O.gross_amount - O.total_ship,2))  * ($percentual/100),2))
                                                    ,2),                              
                    CB.valor_frete_conecta = round(
                                                    (round(O.total_ship - ( O.total_ship * ( $percentual / 100 ) ),2)) - (O.frete_real)
                                                    ,2),
                    CB.valor_conectala = ROUND(
                                                    (ROUND(
                                                            (ROUND( ROUND(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100),2)) -
                                                            (ROUND( (ROUND(O.gross_amount - O.total_ship,2))  * ($percentual/100),2))
                                                        ,2)) +
                                                    ( (round(O.total_ship - ( O.total_ship * ( $percentual / 100 ) ),2)) - (O.frete_real) )
                                                    
                                                ,2),
                CB.valor_frete_recebido = round(O.total_ship - ( O.total_ship * ( $percentual / 100 ) ),2),
                CB.valor_produto_recebido =  round( REPLACE(CB.montante_reembolsado_a_loja,',','.') - ( round(O.total_ship - ( O.total_ship * ( $percentual / 100 ) ),2) ) , 2),
                CB.data_gatilho = DTU.data_gatilho
                
            	WHERE CB.lote = '" . $input['hdnLote'] . "' AND CB.status_conciliacao <> 'Não encontrado'";


        $update1 = $this->db->query($sql);

        if ($update1) {

            $sql1 = "UPDATE conciliacao_carrefour CB SET
                                                    CB.dif_valor_recebido = round(REPLACE(CB.montante_reembolsado_a_loja,',','.') - CB.valor_marketplace,2),
                                                    CB.dif_valor_recebido_produto = round(CB.valor_produto_recebido - (CB.valor_produto - CB.valor_produto_calculado) ,2),
                                                    CB.dif_valor_recebido_frete = round(CB.valor_frete_recebido - ( CB.valor_frete - CB.valor_frete_calculado) ,2)
            WHERE CB.lote = '" . $input['hdnLote'] . "' AND CB.status_conciliacao NOT IN ('Não encontrado','Estorno')";
            $update2 = $this->db->query($sql1);

            if ($update2) {

                $sqlEst = "UPDATE conciliacao_carrefour CB SET
                                                    CB.dif_valor_recebido = round(REPLACE(CB.montante_reembolsado_a_loja,',','.') - CB.valor_marketplace,2),
                                                    CB.dif_valor_recebido_produto = (round((round( REPLACE(CB.montante_reembolsado_a_loja,',','.') + ( round(CB.valor_frete - ( CB.valor_frete * ( 12 / 100 ) ),2) ) , 2)) + (CB.valor_produto - CB.valor_produto_calculado) ,2)) * -1,
                                                    CB.dif_valor_recebido_frete = round(CB.valor_frete_recebido - ( CB.valor_frete - CB.valor_frete_calculado) ,2),
                                                    CB.valor_produto_recebido = round( REPLACE(CB.montante_reembolsado_a_loja,',','.') + ( round(CB.valor_frete - ( CB.valor_frete * ( 12 / 100 ) ),2) ) , 2)
                         WHERE CB.lote = '" . $input['hdnLote'] . "' AND CB.status_conciliacao IN ('Estorno')";
                $updateEst = $this->db->query($sqlEst);

                if ($updateEst) {

                    $sql3 = "UPDATE conciliacao_carrefour SET status_conciliacao = 'Divergente' where
                        lote = '" . $input['hdnLote'] . "' and
                        status_conciliacao = 'Ok' and
                        comissao <> REPLACE(CAST(valor_marketplace AS CHAR),',','.') and
                        (dif_valor_recebido > 0 or dif_valor_recebido < 0)";

                    return $this->db->query($sql3);

                }

            }

        }

    }


    public function consiliaarquivoCarrefourXls($input)
    {

        //Marca os pedidos não encontrados no arquivo
        $sql = "UPDATE conciliacao_carrefour_xls SET status_conciliacao = 'Não encontrado' WHERE lote = '" . $input['hdnLote'] . "' AND n_do_pedido NOT IN (SELECT DISTINCT SUBSTRING(numero_marketplace,1,POSITION('-' IN numero_marketplace)-1) as numero_marketplace FROM orders WHERE numero_marketplace IS NOT NULL)";
        $update1 = $this->db->query($sql);

        if ($update1) {
            //Marca os pedidos de estorno
            $sql2 = "UPDATE conciliacao_carrefour_xls SET status_conciliacao = 'Estorno' WHERE lote = '" . $input['hdnLote'] . "' and UPPER(tipo_de_transacao) LIKE UPPER('%débito%') AND status_conciliacao IS NULL AND n_do_pedido IN ( select * from (SELECT DISTINCT n_do_pedido FROM conciliacao_carrefour_xls WHERE UPPER(tipo_de_transacao) LIKE UPPER('%débito%') AND lote = '" . $input['hdnLote'] . "') TS )";
            $update2 = $this->db->query($sql2);

            if ($update2) {
                // Marca os pedidos Ok
                $sql3 = "UPDATE conciliacao_carrefour_xls SET status_conciliacao = 'Ok' WHERE lote = '" . $input['hdnLote'] . "' and UPPER(tipo_de_transacao) LIKE UPPER('%Crédito%') AND n_do_pedido IN (
                            SELECT DISTINCT ARQ.n_do_pedido FROM (
                            SELECT DISTINCT n_do_pedido, REPLACE(CAST(total_do_pedido AS CHAR),',','.') AS valor_total FROM conciliacao_carrefour_xls WHERE lote = '" . $input['hdnLote'] . "' AND UPPER(tipo_de_transacao) LIKE UPPER('%Crédito%') ) ARQ
                            INNER JOIN (
                            SELECT SUBSTRING(numero_marketplace,1,POSITION('-' IN numero_marketplace)-1) as numero_marketplace, total_order + total_ship AS valor_total FROM orders WHERE numero_marketplace IS NOT NULL) ORDS ON ORDS.numero_marketplace = ARQ.n_do_pedido AND ARQ.valor_total = ORDS.valor_total)";
                $update3 = $this->db->query($sql3);

                if ($update3) {
                    // Marca os pedidos Divergentes
                    $sql4 = "UPDATE conciliacao_carrefour_xls SET status_conciliacao = 'Divergente' WHERE lote = '" . $input['hdnLote'] . "' and UPPER(tipo_de_transacao) LIKE UPPER('%Crédito%') AND n_do_pedido IN (
        	                SELECT DISTINCT ARQ.n_do_pedido FROM (
                            SELECT DISTINCT n_do_pedido, REPLACE(CAST(total_do_pedido AS CHAR),',','.') AS valor_total FROM conciliacao_carrefour_xls WHERE lote = '" . $input['hdnLote'] . "' AND UPPER(tipo_de_transacao) LIKE UPPER('%Crédito%') ) ARQ
                            INNER JOIN (
                            SELECT SUBSTRING(numero_marketplace,1,POSITION('-' IN numero_marketplace)-1) as numero_marketplace, total_order + total_ship AS valor_total FROM orders WHERE numero_marketplace IS NOT NULL) ORDS ON ORDS.numero_marketplace = ARQ.n_do_pedido AND ARQ.valor_total <> ORDS.valor_total)";
                    $update4 = $this->db->query($sql4);

                    if ($update4) {
                        // Marca como Outros as linhas sem número de pedido
                        $sql5 = "UPDATE conciliacao_carrefour_xls SET status_conciliacao = 'Outros' WHERE lote = '" . $input['hdnLote'] . "' AND (n_do_pedido is null or n_do_pedido = '')";
                        $update5 = $this->db->query($sql5);

                        if ($update5) {
                            // Marca como Nulo os pedidos que tem estorno neles
                            $sql6 = "UPDATE conciliacao_carrefour_xls SET status_conciliacao = NULL
                                         WHERE lote = '" . $input['hdnLote'] . "'
                                                AND n_do_pedido IN ( select * from (SELECT DISTINCT n_do_pedido FROM conciliacao_carrefour_xls WHERE UPPER(tipo_de_transacao) LIKE UPPER('%débito%') AND lote = '" . $input['hdnLote'] . "') TS )
                                                AND UPPER(tipo_de_transacao) NOT LIKE UPPER('%débito%')";
                            return $this->db->query($sql6);
                        }
                    }
                }
            }
        }
    }

    public function atualizavaloresconciliadivisaoCarrefourXls($input)
    {

        $sql = "SELECT MAX(valor_aplicado) AS valor_aplicado FROM `param_mkt_categ_integ` WHERE integ_id = '16'";
        $query = $this->db->query($sql);
        $valor = $query->result_array();
        $percentual = $valor[0]['valor_aplicado'];

        //Busca os valores que deverão ser atualizados na conciliação
        $sql = "select * from conciliacao_carrefour_xls CB WHERE CB.lote = '" . $input['hdnLote'] . "' AND CB.status_conciliacao <> 'Não encontrado'";
        $query = $this->db->query($sql);
        $dadosConciliacao = $query->result_array();

        if($dadosConciliacao){
            foreach($dadosConciliacao as $conciliacao){
                $sql2 = "SELECT 
                                S.name AS seller_name,
                                S.id AS store_id,
                                CASE WHEN O.data_envio IS NULL THEN 'Não Enviado' ELSE 'Enviado' END AS pedido_enviado,
                                CASE WHEN UPPER(FR.ship_company) LIKE CONCAT(\"%\",CONCAT(UPPER(S.name),\"%\")) OR S.freight_seller_type > 0 OR O.freight_seller > 0 THEN 'Seller' ELSE 'Conecta Lá' END AS tipo_frete,
                                '" . $_SESSION['username'] . "' AS usuario,
                                O.gross_amount AS valor_pedido,
                                ROUND(O.gross_amount - O.total_ship,2) AS valor_produto,
                                O.total_ship AS valor_frete,
                                O.frete_real AS valor_frete_real,
                                ROUND(O.total_ship - O.frete_real,2) AS valor_frete_real_contratado,
                                O.service_charge_rate AS valor_percentual_parceiro,
                                $percentual AS valor_percentual_mktplace,
                                ROUND( O.gross_amount - (  O.gross_amount * ($percentual/100)  ) ,2) AS `valor_marketplace`,
                                ROUND( (ROUND(O.gross_amount - O.total_ship,2))  * ($percentual/100),2) AS valor_produto_calculado,
                                ROUND(O.total_ship * ($percentual/100),2) AS valor_frete_calculado,
                                ROUND( ROUND( (ROUND(O.gross_amount - O.total_ship,2))  * ($percentual/100),2) + ROUND(O.total_ship * ($percentual/100),2) ,2) AS valor_receita_calculado,
                                ROUND( ROUND(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100)  ,2) AS valor_produto_parceiro,
                                ROUND( O.total_ship * (O.service_charge_rate/100)  ,2) AS valor_frete_parceiro,
                                ROUND(
                                    (ROUND(O.gross_amount - O.total_ship,2)) -
                                    (ROUND( ROUND(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100)  ,2)) -
                                    (ROUND( O.total_ship * (O.service_charge_rate/100)  ,2) )
                                    ,2) AS valor_parceiro,
                                ROUND(
                                    (ROUND( ROUND(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100)  ,2)) -
                                    (ROUND( (ROUND(O.gross_amount - O.total_ship,2))  * ($percentual/100),2))
                                    ,2) AS valor_produto_conecta,
                                ROUND(
                                    (ROUND(O.total_ship - ( O.total_ship * ( $percentual / 100 ) ),2)) - (O.frete_real)
                                    ,2) AS valor_frete_conecta,
                                ROUND(
                                    (ROUND(
                                    (ROUND( ROUND(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100),2)) -
                                    (ROUND( (ROUND(O.gross_amount - O.total_ship,2))  * ($percentual/100),2))
                                    ,2)) +
                                    ( (ROUND(O.total_ship - ( O.total_ship * ( $percentual / 100 ) ),2)) - (O.frete_real) )
                                    ,2) AS valor_conectala,
                                    ROUND(O.total_ship - ( O.total_ship * ( 10 / 100 ) ),2) AS valor_frete_recebido,
                                    ROUND(O.total_ship - ( O.total_ship * ( 10 / 100 ) ),2) AS valor_produto_recebido,
                                    CASE WHEN DTU.data_usada = 'Data Envio' THEN O.data_mkt_sent ELSE O.data_mkt_delivered END AS data_gatilho
                        FROM orders O
                        INNER JOIN stores S ON S.id = O.store_id
                        LEFT JOIN (SELECT DISTINCT order_id, ship_company FROM freights) FR ON FR.order_id = O.id
                        INNER JOIN (SELECT DISTINCT MAX(PMC.data_usada) AS data_usada, apelido FROM `stores_mkts_linked` SML INNER JOIN `param_mkt_ciclo` PMC ON PMC.integ_id = SML.id_mkt GROUP BY apelido) DTU ON DTU.apelido = O.origin
                        WHERE SUBSTRING(O.numero_marketplace,1,POSITION('-' IN O.numero_marketplace)-1) = '" . $conciliacao['n_do_pedido'] . "' limit 1";

                $query = $this->db->query($sql2);
                $dadosPedidoConciliacao = $query->result_array();

                if($dadosPedidoConciliacao){
                    $resulPedidoTratamento = $dadosPedidoConciliacao[0];
                        
                    $updateConciliacao = "UPDATE conciliacao_carrefour_xls CB
                                                SET CB.seller_name = \"" . $resulPedidoTratamento['seller_name'] . "\",
                                                    CB.store_id = '" . $resulPedidoTratamento['store_id'] . "',
                                                    CB.pedido_enviado = '" . $resulPedidoTratamento['pedido_enviado'] . "',
                                                    CB.tipo_frete = '" . $resulPedidoTratamento['tipo_frete'] . "',
                                                    CB.usuario = '" . $resulPedidoTratamento['usuario'] . "',
                                                    CB.valor_pedido = '" . $resulPedidoTratamento['valor_pedido'] . "',
                                                    CB.valor_produto = '" . $resulPedidoTratamento['valor_produto'] . "',
                                                    CB.valor_frete = '" . $resulPedidoTratamento['valor_frete'] . "',
                                                    CB.valor_frete_real = '" . $resulPedidoTratamento['valor_frete_real'] . "',
                                                    CB.valor_frete_real_contratado = '" . $resulPedidoTratamento['valor_frete_real_contratado'] . "',
                                                    CB.valor_percentual_parceiro = '" . $resulPedidoTratamento['valor_percentual_parceiro'] . "',
                                                    CB.valor_percentual_mktplace = '" . $resulPedidoTratamento['valor_percentual_mktplace'] . "',
                                                    CB.`valor_marketplace` = '" . $resulPedidoTratamento['valor_marketplace'] . "',
                                                    CB.valor_produto_calculado = '" . $resulPedidoTratamento['valor_produto_calculado'] . "',
                                                    CB.valor_frete_calculado = '" . $resulPedidoTratamento['valor_frete_calculado'] . "',
                                                    CB.valor_receita_calculado = '" . $resulPedidoTratamento['valor_receita_calculado'] . "',
                                                    CB.valor_produto_parceiro = '" . $resulPedidoTratamento['valor_produto_parceiro'] . "',
                                                    CB.valor_frete_parceiro = '" . $resulPedidoTratamento['valor_frete_parceiro'] . "',
                                                    CB.valor_parceiro = '" . $resulPedidoTratamento['valor_parceiro'] . "',
                                                    CB.valor_produto_conecta = '" . $resulPedidoTratamento['valor_produto_conecta'] . "',
                                                    CB.valor_frete_conecta = '" . $resulPedidoTratamento['valor_frete_conecta'] . "',
                                                    CB.valor_conectala = '" . $resulPedidoTratamento['valor_conectala'] . "',
                                                CB.valor_frete_recebido = round(" . $resulPedidoTratamento['valor_frete'] . " - ( " . $resulPedidoTratamento['valor_frete'] . " * ( $percentual / 100 ) ),2),
                                                CB.valor_produto_recebido =  round( REPLACE(CB.saldo,',','.') - ( round(" . $resulPedidoTratamento['valor_frete'] . " - ( " . $resulPedidoTratamento['valor_frete'] . " * ( $percentual / 100 ) ),2) ) , 2),
                                                CB.data_gatilho = '" . $resulPedidoTratamento['data_gatilho'] . "'
                                                WHERE CB.lote = '" . $input['hdnLote'] . "' AND CB.status_conciliacao <> 'Não encontrado' AND CB.n_do_pedido = '" . $conciliacao['n_do_pedido'] . "'";
                    
                    $attConciliacao = $this->db->query($updateConciliacao);

                    if(!$attConciliacao){
                        echo '<Pre>'.$sql2.'<br>'.$updateConciliacao;;die;
                    }
                    
                }
                      
            }

            $sql1 = "UPDATE conciliacao_carrefour_xls CB SET
                                                    CB.dif_valor_recebido = round(REPLACE(CB.saldo,',','.') - CB.valor_marketplace,2),
                                                    CB.dif_valor_recebido_produto = round(CB.valor_produto_recebido - (CB.valor_produto - CB.valor_produto_calculado) ,2),
                                                    CB.dif_valor_recebido_frete = round(CB.valor_frete_recebido - ( CB.valor_frete - CB.valor_frete_calculado) ,2)
            WHERE CB.lote = '" . $input['hdnLote'] . "' AND CB.status_conciliacao NOT IN ('Não encontrado','Estorno')";
            $update2 = $this->db->query($sql1);

            if ($update2) {

                $sqlEst = "UPDATE conciliacao_carrefour_xls CB SET
                                                    CB.dif_valor_recebido = round(REPLACE(CB.saldo,',','.') - CB.valor_marketplace,2),
                                                    CB.dif_valor_recebido_produto = (round((round( REPLACE(CB.saldo,',','.') + ( round(CB.valor_frete - ( CB.valor_frete * ( 12 / 100 ) ),2) ) , 2)) + (CB.valor_produto - CB.valor_produto_calculado) ,2)) * -1,
                                                    CB.dif_valor_recebido_frete = round(CB.valor_frete_recebido - ( CB.valor_frete - CB.valor_frete_calculado) ,2),
                                                    CB.valor_produto_recebido = round( REPLACE(CB.saldo,',','.') + ( round(CB.valor_frete - ( CB.valor_frete * ( 12 / 100 ) ),2) ) , 2)
                         WHERE CB.lote = '" . $input['hdnLote'] . "' AND CB.status_conciliacao IN ('Estorno')";
                $updateEst = $this->db->query($sqlEst);

                if ($updateEst) {

                    $sql3 = "UPDATE conciliacao_carrefour_xls SET status_conciliacao = 'Divergente' where
                        lote = '" . $input['hdnLote'] . "' and
                        status_conciliacao = 'Ok' and
                        comissao <> REPLACE(CAST(valor_marketplace AS CHAR),',','.') and
                        (dif_valor_recebido > 0 or dif_valor_recebido < 0)";

                    return $this->db->query($sql3);

                }

            }

        }else{
            return true;
        }

        //Atualiza o valor do pedido na tabela
        /*$sql = "UPDATE conciliacao_carrefour_xls CB
            	INNER JOIN orders O ON CB.n_do_pedido = SUBSTRING(O.numero_marketplace,1,POSITION('-' IN O.numero_marketplace)-1)
            	INNER JOIN stores S ON S.id = O.store_id
                LEFT JOIN (SELECT DISTINCT order_id, ship_company FROM freights) FR ON FR.order_id = O.id
            	SET CB.seller_name = S.name,
                    CB.store_id = S.id,
                    CB.pedido_enviado = case when O.data_envio is null then 'Não Enviado' else 'Enviado' end,
                    CB.tipo_frete = CASE WHEN UPPER(FR.ship_company) LIKE CONCAT(\"%\",CONCAT(UPPER(S.name),\"%\")) OR S.freight_seller_type > 0 OR O.freight_seller > 0 THEN 'Seller' else 'Conecta Lá' end,
                    CB.usuario = '" . $_SESSION['username'] . "',
                    CB.valor_pedido = O.gross_amount,
                    CB.valor_produto = round(O.gross_amount - O.total_ship,2),
                    CB.valor_frete = O.total_ship,
                    CB.valor_frete_real = O.frete_real,
                    CB.valor_frete_real_contratado = round(O.total_ship - O.frete_real,2),
                    CB.valor_percentual_parceiro = O.service_charge_rate,
                    CB.valor_percentual_mktplace = $percentual,
                    CB.`valor_marketplace` = round( O.gross_amount - (  O.gross_amount * ($percentual/100)  ) ,2),
                    CB.valor_produto_calculado = round( (round(O.gross_amount - O.total_ship,2))  * ($percentual/100),2),
                    CB.valor_frete_calculado = round(O.total_ship * ($percentual/100),2),
                    CB.valor_receita_calculado = round( round( (round(O.gross_amount - O.total_ship,2))  * ($percentual/100),2) + round(O.total_ship * ($percentual/100),2) ,2),
                    CB.valor_produto_parceiro = round( round(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100)  ,2),
                    CB.valor_frete_parceiro = round( O.total_ship * (O.service_charge_rate/100)  ,2),
                    CB.valor_parceiro = round(
                                                (round(O.gross_amount - O.total_ship,2)) -
                                                (round( round(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100)  ,2)) -
                                                (round( O.total_ship * (O.service_charge_rate/100)  ,2) )
                                            ,2),
                    CB.valor_produto_conecta = round(
                                                        (round( round(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100)  ,2)) -
                                                        (round( (round(O.gross_amount - O.total_ship,2))  * ($percentual/100),2))
                                                    ,2),
                                                    
                    CB.valor_frete_conecta = round(
                                                    (round(O.total_ship - ( O.total_ship * ( $percentual / 100 ) ),2)) - (O.frete_real)
                                                    ,2),
                    CB.valor_conectala = ROUND(
                                                    (ROUND(
                                                            (ROUND( ROUND(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100),2)) -
                                                            (ROUND( (ROUND(O.gross_amount - O.total_ship,2))  * ($percentual/100),2))
                                                        ,2)) +
                                                    ( (round(O.total_ship - ( O.total_ship * ( $percentual / 100 ) ),2)) - (O.frete_real) )
                                                    
                                                ,2),
                CB.valor_frete_recebido = round(O.total_ship - ( O.total_ship * ( $percentual / 100 ) ),2),
                CB.valor_produto_recebido =  round( REPLACE(CB.saldo,',','.') - ( round(O.total_ship - ( O.total_ship * ( $percentual / 100 ) ),2) ) , 2)
                
            	WHERE CB.lote = '" . $input['hdnLote'] . "' AND CB.status_conciliacao <> 'Não encontrado'";
*/


    }

    public function consiliaarquivoMadeira($input)
    {
    	 $this->trataoarquivoparatabelanovaMadeira($input);

        //Marca os pedidos não encontrados no arquivo
        $sql = "UPDATE conciliacao_madeira_tratado SET status_conciliacao = 'Não encontrado' WHERE lote = '" . $input['hdnLote'] . "' AND ref_pedido NOT IN (SELECT O.numero_marketplace FROM orders O WHERE numero_marketplace IS NOT NULL)";
        $update1 = $this->db->query($sql);

        if ($update1) {
            //Marca os pedidos de estorno
            $sql2 = "UPDATE conciliacao_madeira_tratado SET status_conciliacao = 'Estorno' WHERE lote = '" . $input['hdnLote'] . "' and UPPER(tipo) LIKE UPPER('%estorno%') AND status_conciliacao IS NULL AND ref_pedido IN ( select * from (SELECT DISTINCT ref_pedido FROM conciliacao_madeira_tratado WHERE UPPER(tipo) LIKE UPPER('%estorno%') AND lote = '" . $input['hdnLote'] . "') TS )";
            $update2 = $this->db->query($sql2);

            if ($update2) {
                // Marca os pedidos Ok
                $sql3 = "UPDATE conciliacao_madeira_tratado SET status_conciliacao = 'Ok' WHERE lote = '" . $input['hdnLote'] . "' and UPPER(tipo) NOT LIKE UPPER('%estorno%') AND ref_pedido IN (
                        SELECT DISTINCT ARQ.ref_pedido FROM (
                        SELECT DISTINCT ref_pedido, valor AS valor_total FROM conciliacao_madeira_tratado WHERE lote = '" . $input['hdnLote'] . "' AND UPPER(tipo) NOT LIKE UPPER('%estorno%') ) ARQ
                        INNER JOIN (
                        SELECT numero_marketplace, total_order + total_ship AS valor_total FROM orders O WHERE numero_marketplace IS NOT NULL) ORDS ON ORDS.numero_marketplace = ARQ.ref_pedido AND (ROUND(REPLACE(CAST(ARQ.valor_total AS CHAR),',','.'),2) = ROUND(REPLACE(CAST(ORDS.valor_total AS CHAR),',','.'),2) OR(  ROUND(REPLACE(CAST(ARQ.valor_total AS CHAR),',','.'),2) - ROUND(REPLACE(CAST(ORDS.valor_total AS CHAR),',','.'),2) IN ('-0.01','0.01','0.00') )))";
                $update3 = $this->db->query($sql3);

                if ($update3) {
                    // Marca os pedidos Divergentes
                    $sql4 = "UPDATE conciliacao_madeira_tratado SET status_conciliacao = 'Divergente' WHERE lote = '" . $input['hdnLote'] . "' and UPPER(tipo) NOT LIKE UPPER('%estorno%') AND ref_pedido IN (
    	                SELECT DISTINCT ARQ.ref_pedido FROM (
                        SELECT DISTINCT ref_pedido, valor AS valor_total FROM conciliacao_madeira_tratado WHERE lote = '" . $input['hdnLote'] . "' AND UPPER(tipo) NOT LIKE UPPER('%estorno%') ) ARQ
                        INNER JOIN (
                        SELECT numero_marketplace, total_order + total_ship AS valor_total FROM orders O WHERE numero_marketplace IS NOT NULL) ORDS ON ORDS.numero_marketplace = ARQ.ref_pedido AND round(REPLACE(CAST(ARQ.valor_total AS CHAR),',','.'),2) <> round(REPLACE(CAST(ORDS.valor_total AS CHAR),',','.'),2) )";
                    $update4 = $this->db->query($sql4);

                    if ($update4) {
                        // Marca como Outros as linhas sem número de pedido
                        $sql5 = "UPDATE conciliacao_madeira_tratado SET status_conciliacao = 'Outros' WHERE lote = '" . $input['hdnLote'] . "' AND (ref_pedido is null or ref_pedido = '')";
                        $update5 = $this->db->query($sql5);

                        if ($update5) {
                            // Marca como Nulo os pedidos que tem estorno neles
                            $sql6 = "UPDATE conciliacao_madeira_tratado SET status_conciliacao = NULL
                                     WHERE lote = '" . $input['hdnLote'] . "'
                                            AND ref_pedido IN ( select * from (SELECT DISTINCT ref_pedido FROM conciliacao_madeira_tratado WHERE UPPER(tipo) LIKE UPPER('%estorno%') AND lote = '" . $input['hdnLote'] . "') TS )
                                            AND UPPER(tipo) NOT LIKE UPPER('%estorno%')";
                            return $this->db->query($sql6);
                        }
                    }
                }
            }
        }
    }

	public function atualizavaloresconciliadivisaoMadeira($input)
    {

        $sql = "SELECT MAX(valor_aplicado) AS valor_aplicado FROM `param_mkt_categ_integ` WHERE integ_id = '17'";
        $query = $this->db->query($sql);
        $valor = $query->result_array();
        $percentual = $valor[0]['valor_aplicado'];

        //Busca os valores que deverão ser atualizados na conciliação
        $sql = "select * from conciliacao_madeira_tratado CB WHERE CB.lote = '" . $input['hdnLote'] . "' AND CB.status_conciliacao <> 'Não encontrado'";
        $query = $this->db->query($sql);
        $dadosConciliacao = $query->result_array();

        if($dadosConciliacao){
            foreach($dadosConciliacao as $conciliacao){
                $sql2 = "SELECT 
                                S.name AS seller_name,
                                S.id AS store_id,
                                CASE WHEN O.data_envio IS NULL THEN 'Não Enviado' ELSE 'Enviado' END AS pedido_enviado,
                                CASE WHEN UPPER(FR.ship_company) LIKE CONCAT(\"%\",CONCAT(UPPER(S.name),\"%\")) OR S.freight_seller_type > 0 OR O.freight_seller > 0 THEN 'Seller' ELSE 'Conecta Lá' END AS tipo_frete,
                                '" . $_SESSION['username'] . "' AS usuario,
                                O.gross_amount AS valor_pedido,
                                ROUND(O.gross_amount - O.total_ship,2) AS valor_produto,
                                O.total_ship AS valor_frete,
                                O.frete_real AS valor_frete_real,
                                ROUND(O.total_ship - O.frete_real,2) AS valor_frete_real_contratado,
                                O.service_charge_rate AS valor_percentual_parceiro,
                                $percentual AS valor_percentual_mktplace,
                                ROUND( O.gross_amount - (  O.gross_amount * ($percentual/100)  ) ,2) AS `valor_marketplace`,
                                ROUND( (ROUND(O.gross_amount - O.total_ship,2))  * ($percentual/100),2) AS valor_produto_calculado,
                                ROUND(O.total_ship * ($percentual/100),2) AS valor_frete_calculado,
                                ROUND( ROUND( (ROUND(O.gross_amount - O.total_ship,2))  * ($percentual/100),2) + ROUND(O.total_ship * ($percentual/100),2) ,2) AS valor_receita_calculado,
                                ROUND( ROUND(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100)  ,2) AS valor_produto_parceiro,
                                ROUND( O.total_ship * (O.service_charge_rate/100)  ,2) AS valor_frete_parceiro,
                                ROUND(
                                    (ROUND(O.gross_amount - O.total_ship,2)) -
                                    (ROUND( ROUND(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100)  ,2)) -
                                    (ROUND( O.total_ship * (O.service_charge_rate/100)  ,2) )
                                    ,2) AS valor_parceiro,
                                ROUND(
                                    (ROUND( ROUND(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100)  ,2)) -
                                    (ROUND( (ROUND(O.gross_amount - O.total_ship,2))  * ($percentual/100),2))
                                    ,2) AS valor_produto_conecta,
                                ROUND(
                                    (ROUND(O.total_ship - ( O.total_ship * ( $percentual / 100 ) ),2)) - (O.frete_real)
                                    ,2) AS valor_frete_conecta,
                                ROUND(
                                    (ROUND(
                                    (ROUND( ROUND(O.gross_amount - O.total_ship,2) * (O.service_charge_rate/100),2)) -
                                    (ROUND( (ROUND(O.gross_amount - O.total_ship,2))  * ($percentual/100),2))
                                    ,2)) +
                                    ( (ROUND(O.total_ship - ( O.total_ship * ( $percentual / 100 ) ),2)) - (O.frete_real) )
                                    ,2) AS valor_conectala,
                                    ROUND(O.total_ship - ( O.total_ship * ( 10 / 100 ) ),2) AS valor_frete_recebido,
                                    ROUND(O.total_ship - ( O.total_ship * ( 10 / 100 ) ),2) AS valor_produto_recebido,
                                    CASE WHEN DTU.data_usada = 'Data Envio' THEN O.data_mkt_sent ELSE O.data_mkt_delivered END AS data_gatilho
                        FROM orders O
                        INNER JOIN stores S ON S.id = O.store_id
                        LEFT JOIN (SELECT DISTINCT order_id, ship_company FROM freights) FR ON FR.order_id = O.id
                        INNER JOIN (SELECT DISTINCT MAX(PMC.data_usada) AS data_usada, apelido FROM `stores_mkts_linked` SML INNER JOIN `param_mkt_ciclo` PMC ON PMC.integ_id = SML.id_mkt GROUP BY apelido) DTU ON DTU.apelido = O.origin
                        WHERE O.numero_marketplace = '" . $conciliacao['ref_pedido'] . "' limit 1";

                $query = $this->db->query($sql2);
                $dadosPedidoConciliacao = $query->result_array();

                if($dadosPedidoConciliacao){
                    $resulPedidoTratamento = $dadosPedidoConciliacao[0];
                        
                    $updateConciliacao = "UPDATE conciliacao_madeira_tratado CB
                                                SET CB.seller_name = \"" . $resulPedidoTratamento['seller_name'] . "\",
                                                    CB.store_id = '" . $resulPedidoTratamento['store_id'] . "',
                                                    CB.pedido_enviado = '" . $resulPedidoTratamento['pedido_enviado'] . "',
                                                    CB.tipo_frete = '" . $resulPedidoTratamento['tipo_frete'] . "',
                                                    CB.usuario = '" . $resulPedidoTratamento['usuario'] . "',
                                                    CB.valor_pedido = '" . $resulPedidoTratamento['valor_pedido'] . "',
                                                    CB.valor_produto = '" . $resulPedidoTratamento['valor_produto'] . "',
                                                    CB.valor_frete = '" . $resulPedidoTratamento['valor_frete'] . "',
                                                    CB.valor_frete_real = '" . $resulPedidoTratamento['valor_frete_real'] . "',
                                                    CB.valor_frete_real_contratado = '" . $resulPedidoTratamento['valor_frete_real_contratado'] . "',
                                                    CB.valor_percentual_parceiro = '" . $resulPedidoTratamento['valor_percentual_parceiro'] . "',
                                                    CB.valor_percentual_mktplace = '" . $resulPedidoTratamento['valor_percentual_mktplace'] . "',
                                                    CB.`valor_marketplace` = '" . $resulPedidoTratamento['valor_marketplace'] . "',
                                                    CB.valor_produto_calculado = '" . $resulPedidoTratamento['valor_produto_calculado'] . "',
                                                    CB.valor_frete_calculado = '" . $resulPedidoTratamento['valor_frete_calculado'] . "',
                                                    CB.valor_receita_calculado = '" . $resulPedidoTratamento['valor_receita_calculado'] . "',
                                                    CB.valor_produto_parceiro = '" . $resulPedidoTratamento['valor_produto_parceiro'] . "',
                                                    CB.valor_frete_parceiro = '" . $resulPedidoTratamento['valor_frete_parceiro'] . "',
                                                    CB.valor_parceiro = '" . $resulPedidoTratamento['valor_parceiro'] . "',
                                                    CB.valor_produto_conecta = '" . $resulPedidoTratamento['valor_produto_conecta'] . "',
                                                    CB.valor_frete_conecta = '" . $resulPedidoTratamento['valor_frete_conecta'] . "',
                                                    CB.valor_conectala = '" . $resulPedidoTratamento['valor_conectala'] . "',
                                                CB.valor_frete_recebido = IF(CB.valor_pago_marketplace IS NULL,NULL,'" . $resulPedidoTratamento['valor_frete_recebido'] . "'),
                                                CB.valor_produto_recebido =  round( REPLACE(CB.valor_pago_marketplace,',','.') - '" . $resulPedidoTratamento['valor_produto_recebido'] . "' , 2),
                                                CB.data_gatilho = '" . $resulPedidoTratamento['data_gatilho'] . "'
                                                WHERE CB.lote = '" . $input['hdnLote'] . "' AND CB.status_conciliacao <> 'Não encontrado' AND CB.ref_pedido = '" . $conciliacao['ref_pedido'] . "'";
                    
                    $attConciliacao = $this->db->query($updateConciliacao);

                    if(!$attConciliacao){
                        return false;
                    }
                    
                }
                      
            }

            $sql1 = "UPDATE conciliacao_madeira_tratado CB SET
                                                    CB.dif_valor_recebido = round(REPLACE(CB.valor_pago_marketplace,',','.') - CB.valor_marketplace,2),
                                                    CB.dif_valor_recebido_produto = round(CB.valor_produto_recebido - (CB.valor_produto - CB.valor_produto_calculado) ,2),
                                                    CB.dif_valor_recebido_frete = round(CB.valor_frete_recebido - ( CB.valor_frete - CB.valor_frete_calculado) ,2)
            WHERE CB.lote = '" . $input['hdnLote'] . "' AND CB.status_conciliacao NOT IN ('Não encontrado','Estorno')";
            $update2 = $this->db->query($sql1);

            if ($update2) {

                $sqlEst = "UPDATE conciliacao_madeira_tratado CB SET
                                                    CB.dif_valor_recebido = round(REPLACE(CB.valor_pago_marketplace,',','.') + CB.valor_marketplace,2),
                                                    CB.dif_valor_recebido_produto = (round((round( REPLACE(CB.valor_pago_marketplace,',','.') + ( round(CB.valor_frete - ( CB.valor_frete * ( 12 / 100 ) ),2) ) , 2)) + (CB.valor_produto - CB.valor_produto_calculado) ,2)) * -1,
                                                    CB.dif_valor_recebido_frete = round(CB.valor_frete_recebido - ( CB.valor_frete - CB.valor_frete_calculado) ,2),
                                                    CB.valor_produto_recebido = round( REPLACE(CB.valor_pago_marketplace,',','.') + ( round(CB.valor_frete - ( CB.valor_frete * ( 12 / 100 ) ),2) ) , 2)
                         WHERE CB.lote = '" . $input['hdnLote'] . "' AND CB.status_conciliacao IN ('Estorno')";
                $updateEst = $this->db->query($sqlEst);

                if ($updateEst) {

                    //calcula o valor a ser pago no estorno

                    $sql3 = "UPDATE conciliacao_madeira_tratado CBT
                             SET  valor_parceiro_novo = round(case when CBT.valor_comissao > 0 then 
                                                            case when valor < 0 then
                                                                (CBT.valor + CBT.valor_pedido) - CBT.valor_comissao
                                                            else
                                                                ((CBT.valor*-1) + CBT.valor_pedido) - CBT.valor_comissao
                                                            end
                                                        else
                                                            case when valor < 0 then
                                                                (CBT.valor + CBT.valor_pedido) + CBT.valor_comissao
                                                            else
                                                                ((CBT.valor*-1) + CBT.valor_pedido) + CBT.valor_comissao
                                                            end
                                                        end,2),
                                CBT.valor_pago_marketplace = valor
            	             WHERE UPPER(CBT.tipo) = UPPER('Estorno_Venda') AND CBT.lote = '" . $input['hdnLote'] . "'";

                    $udpdateParceiroNovo = $this->db->query($sql3);

                    if ($udpdateParceiroNovo) {

                        $sql4 = "UPDATE conciliacao_madeira_tratado SET status_conciliacao = 'Divergente' WHERE  status_conciliacao IN ('Ok') AND 
                                ( ( REPLACE(dif_valor_recebido,',','.') > '0.01' OR REPLACE(dif_valor_recebido,',','.') < '-0.01') OR 
                                  ( REPLACE(dif_valor_recebido_produto,',','.') > '0.01' OR REPLACE(dif_valor_recebido_produto,',','.') < '-0.01') OR
                                  ( REPLACE(dif_valor_recebido_frete,',','.') > '0.01' OR REPLACE(dif_valor_recebido_frete,',','.') < '-0.01') )
                                AND CBT.lote = '" . $input['hdnLote'] . "'";

                        return $this->db->query($sql3);

                    }
                }

            }
        }

    }


    public function consiliaarquivoNM($input)
    {
        //Marca os pedidos não encontrados no arquivo
        $sql = "UPDATE conciliacao_nm SET status_conciliacao = 'Não encontrado' WHERE lote = '" . $input['hdnLote'] . "' AND orderid NOT IN 
        (SELECT numero_marketplace FROM orders WHERE numero_marketplace IS NOT NULL)";
        $update1 = $this->db->query($sql);

        if ($update1) 
        {
            $sql2 = "UPDATE conciliacao_nm SET status_conciliacao = 'Estorno' WHERE lote = '" . $input['hdnLote'] . "' and UPPER(operacao) LIKE UPPER('%estorno%') 
            AND status_conciliacao IS NULL AND orderid IN ( select * from (SELECT DISTINCT orderid FROM conciliacao_nm WHERE UPPER(operacao) LIKE UPPER('%estorno%') 
            AND lote = '" . $input['hdnLote'] . "') TS )";
            $update2 = $this->db->query($sql2);

            if ($update2)
            {
                $sql3 = "UPDATE conciliacao_nm SET status_conciliacao = 'Ok' WHERE lote = '" . $input['hdnLote'] . "' and UPPER(operacao) NOT LIKE UPPER('%estorno%') 
                        AND orderid IN (
                        SELECT DISTINCT ARQ.orderid FROM (
                        SELECT DISTINCT orderid, total_pedido AS valor_total FROM conciliacao_nm WHERE lote = '" . $input['hdnLote'] . "' 
                        AND UPPER(operacao) NOT LIKE UPPER('%estorno%') ) ARQ
                        INNER JOIN (
                            SELECT numero_marketplace, 
                            REPLACE(CAST(total_order AS CHAR),',','.') + REPLACE(CAST(total_ship AS CHAR),',','.') AS valor_total 
                            FROM orders WHERE numero_marketplace IS NOT NULL
                            ) ORDS
                        ON ORDS.numero_marketplace = ARQ.orderid 
                        AND 
                        (ROUND(REPLACE(CAST(ARQ.valor_total AS CHAR),',','.'),2) = ROUND(REPLACE(CAST(ORDS.valor_total AS CHAR),',','.'),2)
                        OR
                        (ROUND(REPLACE(CAST(ARQ.valor_total AS CHAR),',','.'),2) - ROUND(REPLACE(CAST(ORDS.valor_total AS CHAR),',','.'),2) IN ('-0.01','0.01','0.00') )))";

                $update3 = $this->db->query($sql3);

                if ($update3)
                {
                    // Marca os pedidos Divergentes
                    $sql4 = "UPDATE conciliacao_nm SET status_conciliacao = 'Divergente' WHERE lote = '" . $input['hdnLote'] . "' and UPPER(operacao) NOT LIKE UPPER('%estorno%')
                         AND orderid IN (
    	                SELECT DISTINCT ARQ.orderid FROM (
                            SELECT DISTINCT orderid, total_pedido AS valor_total FROM conciliacao_nm WHERE lote = '" . $input['hdnLote'] . "' 
                            AND UPPER(operacao) NOT LIKE UPPER('%estorno%') 
                            ) ARQ
                        INNER JOIN (
                            SELECT numero_marketplace, REPLACE(CAST(total_order AS CHAR),',','.') + REPLACE(CAST(total_ship AS CHAR),',','.') 
                            AS valor_total FROM orders WHERE numero_marketplace IS NOT NULL
                            ) 
                        ORDS ON ORDS.numero_marketplace = ARQ.orderid AND round(REPLACE(CAST(ARQ.valor_total AS CHAR),',','.'),2) <> 
                        round(REPLACE(CAST(ORDS.valor_total AS CHAR),',','.'),2) )";

                    $update4 = $this->db->query($sql4);

                    if ($update4) 
                    {
                        // Marca como Outros as linhas sem número de pedido
                        $sql5 = "UPDATE conciliacao_nm SET status_conciliacao = 'Outros' WHERE lote = '" . $input['hdnLote'] . "' AND (orderid is null or orderid = '')";
                        $update5 = $this->db->query($sql5);

                        if ($update5) 
                        {
                            // Marca como Nulo os pedidos que tem estorno neles
                            $sql6 = "UPDATE conciliacao_nm SET status_conciliacao = NULL
                                     WHERE lote = '" . $input['hdnLote'] . "'
                                     AND orderid IN ( select * from (SELECT DISTINCT orderid FROM conciliacao_nm WHERE UPPER(operacao) LIKE UPPER('%estorno%') 
                                     AND lote = '" . $input['hdnLote'] . "') TS )
                                     AND UPPER(operacao) NOT LIKE UPPER('%estorno%')";

                            return $this->db->query($sql6);
                        }
                    }
                }
            }
        }
    }


    public function getPercentual($mktplace)
    {
        $sql = "SELECT MAX(valor_aplicado) AS valor_aplicado FROM `param_mkt_categ_integ` WHERE integ_id = ".$mktplace;
        $query = $this->db->query($sql);
        $valor = $query->result_array();
        $percentual = $valor[0]['valor_aplicado'];

        return $percentual;
    }


    public function atualizavaloresconciliadivisaoNM($input)
    {
        $percentual = $this->getPercentual($input['slc_mktplace']);

        //Atualiza o valor do pedido na tabela
        $sql1 = "UPDATE conciliacao_nm NM
            	left JOIN orders O ON NM.orderid = O.numero_marketplace
            	left JOIN stores S ON S.id = O.store_id
                LEFT JOIN (SELECT DISTINCT order_id, ship_company FROM freights) FR ON FR.order_id = O.id
            	SET NM.seller_name = S.name
                    ,NM.store_id = S.id
                    ,NM.pedido_enviado = case when O.data_envio is null then 'Não Enviado' else 'Enviado' end
                    ,NM.tipo_frete = CASE WHEN UPPER(FR.ship_company) LIKE CONCAT(\"%\",CONCAT(UPPER(S.name),\"%\")) OR S.freight_seller_type > 0 OR O.freight_seller > 0 THEN 'Seller' else 'Marketplace' end
                    ,NM.usuario = '" . $_SESSION['username'] . "'
                    ,NM.valor_pedido = O.gross_amount
                ,NM.valor_produto = round(REPLACE(O.gross_amount,',','.') - REPLACE(O.total_ship,',','.'), 2)
                    ,NM.valor_frete = O.total_ship
                    ,NM.valor_frete_real =  ifnull(REPLACE(O.frete_real,',','.'),0)
                    ,NM.valor_frete_real_contratado = round(REPLACE(O.total_ship,',','.') - ifnull(REPLACE(O.frete_real,',','.'),0) ,2)
                    ,NM.valor_percentual_parceiro = O.service_charge_rate
                    ,NM.valor_percentual_mktplace = $percentual
                    ,NM.valor_marketplace = round( REPLACE(O.gross_amount,',','.') - (REPLACE(O.gross_amount,',','.') * ($percentual / 100)) ,2)
                    
                ,NM.valor_produto_calculado = round( (REPLACE(O.gross_amount,',','.') - REPLACE(O.total_ship,',','.')) * ($percentual / 100),2)
                    ,NM.valor_frete_calculado = round(REPLACE(O.total_ship,',','.') * ($percentual / 100),2)
                    ,NM.valor_receita_calculado = round( round( (round(REPLACE(O.gross_amount,',','.') - REPLACE(O.total_ship,',','.'),2))  * ($percentual / 100),2) 
                        + round(REPLACE(O.total_ship,',','.') * ($percentual / 100),2) ,2)                   
                    ,NM.valor_produto_parceiro = round( round(REPLACE(O.gross_amount,',','.') - REPLACE(O.total_ship,',','.'),2) * (REPLACE(O.service_charge_rate,',','.')/100)  ,2)
                    ,NM.valor_frete_parceiro = round( REPLACE(O.total_ship,',','.') * (REPLACE(O.service_charge_rate,',','.')/100)  ,2)
                    ,NM.valor_parceiro = round((round(REPLACE(O.gross_amount,',','.') - REPLACE(O.total_ship,',','.'),2)) - (round( round(REPLACE(O.gross_amount,',','.') - REPLACE(O.total_ship,',','.'),2) * (REPLACE(O.service_charge_rate,',','.')/100)  ,2))
                         - (round( REPLACE(O.total_ship,',','.') * (REPLACE(O.service_charge_freight_value,',','.')/100)  ,2) ),2)
                    ,NM.valor_produto_conecta =                     
                        ROUND(
                            (REPLACE(O.gross_amount,',','.') - REPLACE(O.total_ship,',','.')) * (REPLACE(O.service_charge_rate,',','.')/100)
                            -
                            (REPLACE(O.gross_amount,',','.') - REPLACE(O.total_ship,',','.')) * ($percentual/100)
                        ,2)
                    ,NM.valor_frete_conecta = 
                        ROUND(
                            ( REPLACE(O.total_ship,',','.') - (REPLACE(O.total_ship,',','.') * ($percentual / 100)) ) 
                            - 
                            ifnull(REPLACE(O.frete_real,',','.'),0)
                        ,2)
                    ,NM.valor_conectala = 
                    ROUND(
                        
                        ( (REPLACE(O.gross_amount,',','.') - REPLACE(O.total_ship,',','.')) * (REPLACE(O.service_charge_rate,',','.')/100) ) 
                        -
                        ( (REPLACE(O.gross_amount,',','.') - REPLACE(O.total_ship,',','.')) * ($percentual / 100) )
                        +
                        ( REPLACE(O.total_ship,',','.') - (REPLACE(O.total_ship,',','.') * ($percentual / 100)) )
                        - 
                        ifnull(REPLACE(O.frete_real,',','.'),0)
                        ,2)
                        
                    ,NM.valor_frete_recebido = round(REPLACE(O.total_ship,',','.') - ( REPLACE(O.total_ship,',','.') * ( $percentual / 100 ) ),2)
                    ,NM.valor_produto_recebido =  round( 
                            round(REPLACE(O.gross_amount,',','.') - (REPLACE(O.gross_amount,',','.') * ($percentual / 100)) ,2)
                            - 
                            round(REPLACE(O.total_ship,',','.') - (REPLACE(O.total_ship,',','.') * ( $percentual / 100 )) ,2)
                        ,2)
            	WHERE 
                NM.lote = '" . $input['hdnLote'] . "' 
                AND 
                NM.status_conciliacao <> 'Não encontrado'
                ";

        $update1 = $this->db->query($sql1);

        if ($update1)
        {
            $sql2 = "UPDATE conciliacao_nm NM SET
                        NM.dif_valor_recebido = round(REPLACE(NM.valor_repasse,',','.') - NM.valor_marketplace,2),
                        NM.dif_valor_recebido_produto = round(REPLACE(NM.valor_produto_recebido,',','.') - (REPLACE(NM.valor_produto,',','.') - REPLACE(NM.valor_produto_calculado,',','.')) ,2),
                        NM.dif_valor_recebido_frete = round(REPLACE(NM.valor_frete_recebido,',','.') - ( REPLACE(NM.valor_frete,',','.') - REPLACE(NM.valor_frete_calculado,',','.')) ,2)
                        WHERE NM.lote = '" . $input['hdnLote'] . "' AND NM.status_conciliacao NOT IN ('Não encontrado','Estorno')";
            $update2 = $this->db->query($sql2);

            if ($update2) 
            {
                $sqlEst = "UPDATE conciliacao_nm NM SET
                                                    NM.dif_valor_recebido = round(REPLACE(NM.valor_repasse,',','.') - REPLACE(NM.valor_marketplace,',','.'),2),
                                                    NM.dif_valor_recebido_produto = (round((round( REPLACE(NM.valor_repasse,',','.') + ( round(REPLACE(NM.valor_frete,',','.') - ( REPLACE(NM.valor_frete,',','.') * ( 12 / 100 ) ),2) ) , 2)) + (REPLACE(NM.valor_produto,',','.') - REPLACE(NM.valor_produto_calculado,',','.')) ,2)) * -1,
                                                    NM.dif_valor_recebido_frete = round(REPLACE(NM.valor_frete_recebido,',','.') - ( REPLACE(NM.valor_frete,',','.') - REPLACE(NM.valor_frete_calculado,',','.')) ,2),
                                                    NM.valor_produto_recebido = round( REPLACE(NM.valor_repasse,',','.') + ( round(REPLACE(NM.valor_frete,',','.') - ( REPLACE(NM.valor_frete,',','.') * ( 12 / 100 ) ),2) ) , 2)
                            WHERE NM.lote = '" . $input['hdnLote'] . "' AND NM.status_conciliacao IN ('Estorno')";

                $updateEst = $this->db->query($sqlEst);

                if ($updateEst) 
                {
                    $sql3 = "UPDATE conciliacao_nm SET status_conciliacao = 'Divergente' where
                            lote = '" . $input['hdnLote'] . "' 
                            and status_conciliacao = 'Ok' 
                            and REPLACE(CAST(valor_comissao AS CHAR),',','.') <> REPLACE(CAST(valor_marketplace AS CHAR),',','.') 
                            AND (dif_valor_recebido > 0 or dif_valor_recebido < 0)";

                    return $this->db->query($sql3);
                }

            }
        }
    }


    public function getOrdersGridsB2W($data = null, $paramConta = null)
    {

        if ($paramConta <> null) {
            $where = "ifnull(CB.status_conciliacao,'Estorno')";
        } else {
            $where = "CB.status_conciliacao";
        }

        $sql = "SELECT DISTINCT
            	CB.marca,
                CB.seller_name,
                CB.store_id,
                CB.usuario,
            	CB.data_pedido,
            	CB.ref_pedido AS ref_pedido,
                CB.entrega,
            	CB.valor AS valor_pedido_mktplace,
            	CASE WHEN ctp.num_pedido IS NOT NULL and CB.status_conciliacao IS NOT NULL THEN 'Ok' ELSE IFNULL(CB.status_conciliacao_novo,CB.status_conciliacao) END AS status_conciliacao,
                CB.valor_pedido,
            	CB.valor_pedido+CB.valor_frete AS valor_pedido_interno,
            	CB.valor_frete,
                CB.valor_frete_real,
                CB.valor_frete_real_contratado,
            	CB.valor_conectala,
                CB.valor_conectala_real,
                ROUND(CB.valor_conectala - CB.valor_conectala_real,2) AS valor_conectala_dif,
            	CB.valor_marketplace,
                CB.valor_comissao AS valor_da_comissao,
            	CB.valor_agencia,
            	CB.valor_parceiro,
                CB.valor_parceiro_novo,
            	CB.valor_autonomo,
            	CB.valor_afiliado,
                CB.valor_produto,
                CB.valor_percentual_mktplace,
                CB.valor_produto_calculado,
                CB.valor_frete_calculado,
                CB.valor_produto_parceiro,
                CB.valor_frete_parceiro,
                CB.valor_produto_conecta,
                CB.valor_frete_conecta,
                CB.valor_receita_calculado,
                CB.valor_produto_recebido,
                CB.valor_frete_recebido,
                CB.dif_valor_recebido,
                CB.dif_valor_recebido_produto,
                CB.dif_valor_recebido_frete,
                round(CB.valor_pedido - ( CB.valor_pedido * (CB.valor_percentual_mktplace/100)),0) AS valor_da_transacao,
                CB.valor_percentual_parceiro,
                CB.tratado,
                CB.valor,
                OBS.observacao,
                OBS.chamado_mktplace, 
                OBS.chamado_agidesk,
                CASE WHEN CB.valor_frete > (CB.valor_pedido*0.35) THEN 'Maior' ELSE 'Menor' END AS alertaFrete,
                CASE WHEN CB.valor_frete_real > (CB.valor_pedido*0.35) THEN 'Maior' ELSE 'Menor' END AS alertaFreteReal,
                CASE WHEN CB.valor_parceiro_novo <> '0.00' THEN CB.valor_parceiro_novo ELSE CB.valor_parceiro END AS valor_parceiro_ajustado,
                CASE WHEN CB.valor_produto_conecta_ajustado <> '0.00' THEN CB.valor_produto_conecta_ajustado ELSE CB.valor_produto_conecta END AS valor_produto_conecta_ajustado,
                CASE WHEN CB.valor_frete_conecta_ajustado <> '0.00' THEN CB.valor_frete_conecta_ajustado ELSE CB.valor_frete_conecta END AS valor_frete_conecta_ajustado,
                CASE WHEN CB.valor_conectala_ajustado <> '0.00' THEN CB.valor_conectala_ajustado ELSE CB.valor_conectala END AS valor_conectala_ajustado,
                CB.pedido_enviado,
                CB.tipo_frete,
                CB.valor_desconto_camp_promo,
                DATE_FORMAT(CB.data_gatilho, '%d-%m-%Y') as data_gatilho
            FROM conciliacao_b2w_tratado CB
           LEFT JOIN (   SELECT OBS.*, CHAM.chamado_mktplace, CHAM.chamado_agidesk FROM (
                        SELECT num_pedido, 
                        	lote, 
                        	GROUP_CONCAT(CONCAT(' [',CONCAT(DATE_FORMAT(data_criacao, '%d-%m-%Y'),CONCAT('] - ',observacao)))) AS observacao 
                        	FROM `conciliacao_pedido`
                        	GROUP BY num_pedido, lote) OBS
                        LEFT JOIN (
                        	SELECT num_pedido, lote, GROUP_CONCAT(DISTINCT IFNULL(chamado_mktplace,''),'-') AS chamado_mktplace, 
                        		GROUP_CONCAT(DISTINCT IFNULL(chamado_agidesk,''),'-') AS chamado_agidesk
                        	FROM conciliacao_pedido
                        	GROUP BY num_pedido, lote) CHAM ON CHAM.num_pedido = OBS.num_pedido AND CHAM.lote = OBS.lote   ) OBS ON OBS.num_pedido = CB.ref_pedido AND OBS.lote = CB.lote
            LEFT JOIN conciliacao_temp_pedido ctp ON ctp.num_pedido = CB.ref_pedido AND ctp.lote = CB.lote
            WHERE CB.lote = '" . $data['lote'] . "' AND CASE WHEN ctp.num_pedido IS NOT NULL and CB.status_conciliacao IS NOT NULL THEN 'Ok' ELSE ifnull(CB.status_conciliacao_novo,$where) END = '" . $data['tipo'] . "'
            ORDER BY CB.id";

        $query = $this->db->query($sql);
        return (false !== $query) ? $query->result_array() : false;

    }

    public function getOrdersGridsViaVarejo($data = null, $paramConta = null)
    {

        if ($paramConta <> null) {
            $where = "ifnull(CB.status_conciliacao,'Estorno')";
        } else {
            $where = "CB.status_conciliacao";
        }

        $sql = "SELECT DISTINCT
            	CB.origem AS marca,
                CB.seller_name,
                CB.store_id,
                CB.usuario,
            	CB.data_do_pedido AS data_pedido,
            	CB.numero_do_pedido AS ref_pedido,
                '' as entrega,
            	CB.total_do_pedido AS valor_pedido_mktplace,
            	CASE WHEN ctp.num_pedido IS NOT NULL and CB.status_conciliacao IS NOT NULL THEN 'Ok' ELSE ifnull(CB.status_conciliacao_novo,CB.status_conciliacao) END AS status_conciliacao,
                CB.valor_pedido,
            	CB.valor_pedido+CB.valor_frete AS valor_pedido_interno,
            	CB.valor_frete,
                CB.valor_frete_real,
                CB.valor_frete_real_contratado,
            	CB.valor_conectala,
                CB.valor_conectala_real,
                round(CB.valor_conectala - CB.valor_conectala_real,2) as valor_conectala_dif,
            	CB.valor_marketplace,
                CB.valor_da_comissao,
            	CB.valor_agencia,
            	CB.valor_parceiro,
                CB.valor_parceiro_novo,
            	CB.valor_autonomo,
            	CB.valor_afiliado,
                CB.valor_produto,
                CB.valor_percentual_mktplace,
                CB.valor_produto_calculado,
                CB.valor_frete_calculado,
                CB.valor_produto_parceiro,
                CB.valor_frete_parceiro,
                CB.valor_produto_conecta,
                CB.valor_frete_conecta,
                CB.valor_receita_calculado,
                CB.valor_produto_recebido,
                CB.valor_frete_recebido,
                CB.dif_valor_recebido,
                CB.dif_valor_recebido_produto,
                CB.dif_valor_recebido_frete,
                CB.valor_da_transacao,
                CB.valor_percentual_parceiro,
                CB.tratado,
                OBS.observacao,
                OBS.chamado_mktplace, 
                OBS.chamado_agidesk,
                case when CB.valor_frete > (CB.valor_pedido*0.35) then 'Maior' else 'Menor' end as alertaFrete,
                case when CB.valor_frete_real > (CB.valor_pedido*0.35) then 'Maior' else 'Menor' end as alertaFreteReal,
                CASE WHEN CB.valor_parceiro_novo <> '0.00' THEN CB.valor_parceiro_novo ELSE CB.valor_parceiro END AS valor_parceiro_ajustado,
                CASE WHEN CB.valor_produto_conecta_ajustado <> '0.00' THEN CB.valor_produto_conecta_ajustado ELSE CB.valor_produto_conecta END AS valor_produto_conecta_ajustado,
                CASE WHEN CB.valor_frete_conecta_ajustado <> '0.00' THEN CB.valor_frete_conecta_ajustado ELSE CB.valor_frete_conecta END AS valor_frete_conecta_ajustado,
                CASE WHEN CB.valor_conectala_ajustado <> '0.00' THEN CB.valor_conectala_ajustado ELSE CB.valor_conectala END AS valor_conectala_ajustado,
                CB.pedido_enviado,
                CB.tipo_frete,
                CB.valor_desconto_camp_promo,
                DATE_FORMAT(CB.data_gatilho, '%d-%m-%Y') as data_gatilho
            FROM conciliacao_viavarejo CB
           LEFT JOIN (   SELECT OBS.*, CHAM.chamado_mktplace, CHAM.chamado_agidesk FROM (
                        SELECT num_pedido, 
                        	lote, 
                        	GROUP_CONCAT(CONCAT(' [',CONCAT(DATE_FORMAT(data_criacao, '%d-%m-%Y'),CONCAT('] - ',observacao)))) AS observacao 
                        	FROM `conciliacao_pedido`
                        	GROUP BY num_pedido, lote) OBS
                        LEFT JOIN (
                        	SELECT num_pedido, lote, GROUP_CONCAT(DISTINCT IFNULL(chamado_mktplace,''),'-') AS chamado_mktplace, 
                        		GROUP_CONCAT(DISTINCT IFNULL(chamado_agidesk,''),'-') AS chamado_agidesk
                        	FROM conciliacao_pedido
                        	GROUP BY num_pedido, lote) CHAM ON CHAM.num_pedido = OBS.num_pedido AND CHAM.lote = OBS.lote   ) OBS ON OBS.num_pedido = CB.numero_do_pedido AND OBS.lote = CB.lote
            LEFT JOIN conciliacao_temp_pedido ctp ON ctp.num_pedido = CB.numero_do_pedido AND ctp.lote = CB.lote
            WHERE CB.lote = '" . $data['lote'] . "' AND CASE WHEN ctp.num_pedido IS NOT NULL and CB.status_conciliacao IS NOT NULL THEN 'Ok' ELSE ifnull(CB.status_conciliacao_novo,$where) END = '" . $data['tipo'] . "'
            ORDER BY CB.id";
// 	echo '<pre>'.$sql;die;
        $query = $this->db->query($sql);
        return (false !== $query) ? $query->result_array() : false;

    }

    public function getOrdersGridsCarrefour($data = null, $paramConta = null)
    {

        if ($paramConta <> null) {
            $where = "ifnull(CB.status_conciliacao,'Estorno')";
        } else {
            $where = "CB.status_conciliacao";
        }

        $sql = "SELECT DISTINCT
            			'' AS marca,
                		CB.seller_name,
                        CB.quantidade,
                        CB.store_id,
                		CB.usuario,
                		CB.data_de_criacao AS data_pedido,
                		CB.n_do_pedido AS ref_pedido,
                        '' as entrega,
                		CB.preco_total_com_imposto AS valor_pedido_mktplace,
                		CASE WHEN ctp.num_pedido IS NOT NULL and CB.status_conciliacao IS NOT NULL THEN 'Ok' ELSE IFNULL(CB.status_conciliacao_novo,CB.status_conciliacao) END AS status_conciliacao,
                		CB.valor_pedido,
                		CB.valor_pedido+CB.valor_frete AS valor_pedido_interno,
                		CB.valor_frete,
                		CB.valor_frete_real,
                        CB.valor_frete_real_contratado,
                		CB.valor_conectala,
                		CB.valor_conectala_real,
                		ROUND(CB.valor_conectala - CB.valor_conectala_real,2) AS valor_conectala_dif,
                		CB.valor_marketplace,
                		CB.comissao AS valor_da_comissao,
                		CB.valor_agencia,
                		CB.valor_parceiro,
                		CB.valor_parceiro_novo,
                		CB.valor_autonomo,
                		CB.valor_afiliado,
                		CB.valor_produto,
                		CB.valor_percentual_mktplace,
                		CB.valor_produto_calculado,
                		CB.valor_frete_calculado,
                		CB.valor_produto_parceiro,
                		CB.valor_frete_parceiro,
                		CB.valor_produto_conecta,
                		CB.valor_frete_conecta,
                		CB.valor_receita_calculado,
                		CB.valor_produto_recebido,
                		CB.valor_frete_recebido,
                		CB.dif_valor_recebido,
                		CB.dif_valor_recebido_produto,
                		CB.dif_valor_recebido_frete,
                		CB.montante_reembolsado_a_loja AS valor_da_transacao,
                		CB.valor_percentual_parceiro,
                		CB.tratado,
                		OBS.observacao,
                		OBS.chamado_mktplace,
                		OBS.chamado_agidesk,
                		CASE WHEN CB.valor_frete > (CB.valor_pedido*0.35) THEN 'Maior' ELSE 'Menor' END AS alertaFrete,
                		CASE WHEN CB.valor_frete_real > (CB.valor_pedido*0.35) THEN 'Maior' ELSE 'Menor' END AS alertaFreteReal,
                        CASE WHEN CB.valor_parceiro_novo <> '0.00' THEN CB.valor_parceiro_novo ELSE CB.valor_parceiro END AS valor_parceiro_ajustado,
                        CASE WHEN CB.valor_produto_conecta_ajustado <> '0.00' THEN CB.valor_produto_conecta_ajustado ELSE CB.valor_produto_conecta END AS valor_produto_conecta_ajustado,
                        CASE WHEN CB.valor_frete_conecta_ajustado <> '0.00' THEN CB.valor_frete_conecta_ajustado ELSE CB.valor_frete_conecta END AS valor_frete_conecta_ajustado,
                        CASE WHEN CB.valor_conectala_ajustado <> '0.00' THEN CB.valor_conectala_ajustado ELSE CB.valor_conectala END AS valor_conectala_ajustado,
                        CB.pedido_enviado,
                        CB.tipo_frete,
                CB.valor_desconto_camp_promo,
                DATE_FORMAT(CB.data_gatilho, '%d-%m-%Y') as data_gatilho
            FROM conciliacao_carrefour CB
           LEFT JOIN (   SELECT OBS.*, CHAM.chamado_mktplace, CHAM.chamado_agidesk FROM (
                        SELECT num_pedido,
                        	lote,
                        	GROUP_CONCAT(CONCAT(' [',CONCAT(DATE_FORMAT(data_criacao, '%d-%m-%Y'),CONCAT('] - ',observacao)))) AS observacao
                        	FROM `conciliacao_pedido`
                        	GROUP BY num_pedido, lote) OBS
                        LEFT JOIN (
                        	SELECT num_pedido, lote, GROUP_CONCAT(DISTINCT IFNULL(chamado_mktplace,''),'-') AS chamado_mktplace,
                        		GROUP_CONCAT(DISTINCT IFNULL(chamado_agidesk,''),'-') AS chamado_agidesk
                        	FROM conciliacao_pedido
                        	GROUP BY num_pedido, lote) CHAM ON CHAM.num_pedido = OBS.num_pedido AND CHAM.lote = OBS.lote   ) OBS ON OBS.num_pedido = CB.n_do_pedido AND OBS.lote = CB.lote
            LEFT JOIN conciliacao_temp_pedido ctp ON ctp.num_pedido = CB.n_do_pedido AND ctp.lote = CB.lote
            WHERE CB.lote = '" . $data['lote'] . "' AND CASE WHEN ctp.num_pedido IS NOT NULL and CB.status_conciliacao IS NOT NULL THEN 'Ok' ELSE ifnull(CB.status_conciliacao_novo,$where) END = '" . $data['tipo'] . "'
            ORDER BY CB.id";
        // 	echo '<pre>'.$sql;die;
        $query = $this->db->query($sql);
        return (false !== $query) ? $query->result_array() : false;

    }

    public function getOrdersGridsCarrefourXls($data = null, $paramConta = null)
    {

        if ($paramConta <> null) {
            $where = "ifnull(CB.status_conciliacao,'Estorno')";
        } else {
            $where = "CB.status_conciliacao";
        }

        $sql = "SELECT DISTINCT
            			'' AS marca,
                		CB.seller_name,
                        CB.store_id,
                        CB.quantidade,
                        CB.tipo,
                        CB.descricao,
                		CB.usuario,
                		CB.realizado_em AS data_pedido,
                		CB.n_do_pedido AS ref_pedido,
                        '' as entrega,
                		CB.total_do_pedido AS valor_pedido_mktplace,
                		CASE WHEN ctp.num_pedido IS NOT NULL and CB.status_conciliacao IS NOT NULL THEN 'Ok' ELSE IFNULL(CB.status_conciliacao_novo,CB.status_conciliacao) END AS status_conciliacao,
                		CB.valor_pedido,
                		CB.valor_pedido+CB.valor_frete AS valor_pedido_interno,
                		CB.valor_frete,
                		CB.valor_frete_real,
                        CB.valor_frete_real_contratado,
                		CB.valor_conectala,
                		CB.valor_conectala_real,
                		ROUND(CB.valor_conectala - CB.valor_conectala_real,2) AS valor_conectala_dif,
                		CB.valor_marketplace,
                		CB.comissao AS valor_da_comissao,
                		CB.valor_agencia,
                		CB.valor_parceiro,
                		CB.valor_parceiro_novo,
                		CB.valor_autonomo,
                		CB.valor_afiliado,
                		CB.valor_produto,
                		CB.valor_percentual_mktplace,
                		CB.valor_produto_calculado,
                		CB.valor_frete_calculado,
                		CB.valor_produto_parceiro,
                		CB.valor_frete_parceiro,
                		CB.valor_produto_conecta,
                		CB.valor_frete_conecta,
                		CB.valor_receita_calculado,
                		CB.valor_produto_recebido,
                		CB.valor_frete_recebido,
                		CB.dif_valor_recebido,
                		CB.dif_valor_recebido_produto,
                		CB.dif_valor_recebido_frete,
                		CB.saldo AS valor_da_transacao,
                		CB.valor_percentual_parceiro,
                		CB.tratado,
                		OBS.observacao,
                        CB.valor_extrato,
                		OBS.chamado_mktplace,
                		OBS.chamado_agidesk,
                		CASE WHEN CB.valor_frete > (CB.valor_pedido*0.35) THEN 'Maior' ELSE 'Menor' END AS alertaFrete,
                		CASE WHEN CB.valor_frete_real > (CB.valor_pedido*0.35) THEN 'Maior' ELSE 'Menor' END AS alertaFreteReal,
                        CASE WHEN CB.valor_parceiro_novo <> '0.00' THEN CB.valor_parceiro_novo ELSE CB.valor_parceiro END AS valor_parceiro_ajustado,
                        CASE WHEN CB.valor_produto_conecta_ajustado <> '0.00' THEN CB.valor_produto_conecta_ajustado ELSE CB.valor_produto_conecta END AS valor_produto_conecta_ajustado,
                        CASE WHEN CB.valor_frete_conecta_ajustado <> '0.00' THEN CB.valor_frete_conecta_ajustado ELSE CB.valor_frete_conecta END AS valor_frete_conecta_ajustado,
                        CASE WHEN CB.valor_conectala_ajustado <> '0.00' THEN CB.valor_conectala_ajustado ELSE CB.valor_conectala END AS valor_conectala_ajustado,
                        CB.pedido_enviado,
                        CB.tipo_frete,
                CB.valor_desconto_camp_promo,
                DATE_FORMAT(CB.data_gatilho, '%d-%m-%Y') as data_gatilho
            FROM conciliacao_carrefour_xls CB
           LEFT JOIN (   SELECT OBS.*, CHAM.chamado_mktplace, CHAM.chamado_agidesk FROM (
                        SELECT num_pedido,
                        	lote,
                        	GROUP_CONCAT(CONCAT(' [',CONCAT(DATE_FORMAT(data_criacao, '%d-%m-%Y'),CONCAT('] - ',observacao)))) AS observacao
                        	FROM `conciliacao_pedido`
                        	GROUP BY num_pedido, lote) OBS
                        LEFT JOIN (
                        	SELECT num_pedido, lote, GROUP_CONCAT(DISTINCT IFNULL(chamado_mktplace,''),'-') AS chamado_mktplace,
                        		GROUP_CONCAT(DISTINCT IFNULL(chamado_agidesk,''),'-') AS chamado_agidesk
                        	FROM conciliacao_pedido
                        	GROUP BY num_pedido, lote) CHAM ON CHAM.num_pedido = OBS.num_pedido AND CHAM.lote = OBS.lote   ) OBS ON OBS.num_pedido = CB.n_do_pedido AND OBS.lote = CB.lote
            LEFT JOIN conciliacao_temp_pedido ctp ON ctp.num_pedido = CB.n_do_pedido AND ctp.lote = CB.lote
            WHERE CB.lote = '" . $data['lote'] . "' AND CASE WHEN ctp.num_pedido IS NOT NULL and CB.status_conciliacao IS NOT NULL THEN 'Ok' ELSE ifnull(CB.status_conciliacao_novo,$where) END = '" . $data['tipo'] . "'
            ORDER BY CB.id";
        // 	echo '<pre>'.$sql;die;
        $query = $this->db->query($sql);
        return (false !== $query) ? $query->result_array() : false;

    }

    public function getOrdersGridsManual($data = null)
    {

        $sql = "SELECT DISTINCT
            	        '' AS marca,
                        CB.seller_name,
                        CB.store_id,
                        CB.usuario,
                        '' AS data_pedido,
                        CB.numero_pedido AS ref_pedido,
                        '' AS entrega,
                        CB.valor_pedido AS valor_pedido_mktplace,
                        CASE WHEN ctp.num_pedido IS NOT NULL AND CB.status_conciliacao IS NOT NULL THEN 'Ok' ELSE IFNULL(CB.status_conciliacao_novo,CB.status_conciliacao) END AS status_conciliacao,
                        CB.valor_pedido,
                        CB.valor_pedido+CB.valor_frete AS valor_pedido_interno,
                        CB.valor_frete,
                        CB.valor_frete_real,
                        CB.valor_frete_real_contratado,
                        CB.valor_conectala,
                        CB.valor_conectala_real,
                        0 AS valor_conectala_dif,
                        CB.valor_marketplace,
                        round( ( CB.valor_pedido * (CB.valor_percentual_mktplace/100)),0) AS valor_da_comissao,
                        CB.valor_agencia,
                        CB.valor_parceiro,
                        CB.valor_parceiro_novo,
                        CB.valor_autonomo,
                        CB.valor_afiliado,
                        CB.valor_produto,
                        CB.valor_percentual_mktplace,
                        CB.valor_produto_calculado,
                        CB.valor_frete_calculado,
                        CB.valor_produto_parceiro,
                        CB.valor_frete_parceiro,
                        CB.valor_produto_conecta,
                        CB.valor_frete_conecta,
                        CB.valor_receita_calculado,
                        CB.valor_produto_recebido,
                        CB.valor_frete_recebido,
                        CB.dif_valor_recebido,
                        CB.dif_valor_recebido_produto,
                        CB.dif_valor_recebido_frete,
                        CASE WHEN CB.valor_parceiro_novo <> '0.00' THEN CB.valor_parceiro_novo ELSE CB.valor_parceiro END AS valor_da_transacao,
                        CB.valor_percentual_parceiro,
                        CB.tratado,
                        OBS.observacao,
                        OBS.chamado_mktplace,
                        OBS.chamado_agidesk,
                        CASE WHEN CB.valor_frete > (CB.valor_pedido*0.35) THEN 'Maior' ELSE 'Menor' END AS alertaFrete,
                        CASE WHEN CB.valor_frete_real > (CB.valor_pedido*0.35) THEN 'Maior' ELSE 'Menor' END AS alertaFreteReal,
                        CASE WHEN CB.valor_parceiro_novo <> '0.00' THEN CB.valor_parceiro_novo ELSE CB.valor_parceiro END AS valor_parceiro_ajustado,
                        CASE WHEN CB.valor_produto_conecta_ajustado <> '0.00' THEN CB.valor_produto_conecta_ajustado ELSE CB.valor_produto_conecta END AS valor_produto_conecta_ajustado,
                        CASE WHEN CB.valor_frete_conecta_ajustado <> '0.00' THEN CB.valor_frete_conecta_ajustado ELSE CB.valor_frete_conecta END AS valor_frete_conecta_ajustado,
                        CASE WHEN CB.valor_conectala_ajustado <> '0.00' THEN CB.valor_conectala_ajustado ELSE CB.valor_conectala END AS valor_conectala_ajustado,
                        CB.pedido_enviado,
                        CB.tipo_frete,
                        CB.valor_desconto_camp_promo,
                        DATE_FORMAT(CB.data_gatilho, '%d-%m-%Y') as data_gatilho
            FROM conciliacao_manual CB
           LEFT JOIN (   SELECT OBS.*, CHAM.chamado_mktplace, CHAM.chamado_agidesk FROM (
                        SELECT num_pedido,
                        	lote,
                        	GROUP_CONCAT(CONCAT(' [',CONCAT(DATE_FORMAT(data_criacao, '%d-%m-%Y'),CONCAT('] - ',observacao)))) AS observacao
                        	FROM `conciliacao_pedido`
                        	GROUP BY num_pedido, lote) OBS
                        LEFT JOIN (
                        	SELECT num_pedido, lote, GROUP_CONCAT(DISTINCT IFNULL(chamado_mktplace,''),'-') AS chamado_mktplace,
                        		GROUP_CONCAT(DISTINCT IFNULL(chamado_agidesk,''),'-') AS chamado_agidesk
                        	FROM conciliacao_pedido
                        	GROUP BY num_pedido, lote) CHAM ON CHAM.num_pedido = OBS.num_pedido AND CHAM.lote = OBS.lote   ) OBS ON OBS.num_pedido = CB.numero_pedido AND OBS.lote = CB.lote
            LEFT JOIN conciliacao_temp_pedido ctp ON ctp.num_pedido = CB.numero_pedido AND ctp.lote = CB.lote
            WHERE CB.lote = '" . $data['lote'] . "' AND CASE WHEN ctp.num_pedido IS NOT NULL and CB.status_conciliacao IS NOT NULL THEN 'Ok' ELSE ifnull(CB.status_conciliacao_novo,CB.status_conciliacao) END = '" . $data['tipo'] . "'
            ORDER BY CB.id";
            
        $query = $this->db->query($sql);
        return (false !== $query) ? $query->result_array() : false;

    }

    public function getOrdersGridsML($data = null)
    {

        $sql = "SELECT DISTINCT
            	        '' AS marca,
                		CB.seller_name,
                        CB.store_id,
                		CB.usuario,
                		CB.date AS data_pedido,
                		CB.external_reference AS ref_pedido,
                        '' as entrega,
                		CB.GROSS_AMOUNT AS valor_pedido_mktplace,
                		CASE WHEN ctp.num_pedido IS NOT NULL and CB.status_conciliacao IS NOT NULL THEN 'Ok' ELSE IFNULL(CB.status_conciliacao_novo,CB.status_conciliacao) END AS status_conciliacao,
                		CB.valor_pedido,
                		CB.valor_pedido+CB.valor_frete AS valor_pedido_interno,
                		CB.valor_frete,
                		CB.valor_frete_real,
                        CB.valor_frete_real_contratado,
                		CB.valor_conectala,
                		CB.valor_conectala_real,
                		ROUND(CB.valor_conectala - CB.valor_conectala_real,2) AS valor_conectala_dif,
                		CB.valor_marketplace,
                		CB.MP_FEE_AMOUNT AS valor_da_comissao,
                		CB.valor_agencia,
                		CB.valor_parceiro,
                		CB.valor_parceiro_novo,
                		CB.valor_autonomo,
                		CB.valor_afiliado,
                		CB.valor_produto,
                		CB.valor_percentual_mktplace,
                		CB.valor_produto_calculado,
                		CB.valor_frete_calculado,
                		CB.valor_produto_parceiro,
                		CB.valor_frete_parceiro,
                		CB.valor_produto_conecta,
                		CB.valor_frete_conecta,
                		CB.valor_receita_calculado,
                		CB.valor_produto_recebido,
                		CB.valor_frete_recebido,
                		CB.dif_valor_recebido,
                		CB.dif_valor_recebido_produto,
                		CB.dif_valor_recebido_frete,
                		CB.net_credit_amount AS valor_da_transacao,
                		CB.valor_percentual_parceiro,
                		CB.tratado,
                		OBS.observacao,
                		OBS.chamado_mktplace,
                		OBS.chamado_agidesk,
                		CASE WHEN CB.valor_frete > (CB.valor_pedido*0.35) THEN 'Maior' ELSE 'Menor' END AS alertaFrete,
                		CASE WHEN CB.valor_frete_real > (CB.valor_pedido*0.35) THEN 'Maior' ELSE 'Menor' END AS alertaFreteReal,
                        CASE WHEN CB.valor_parceiro_novo <> '0.00' THEN CB.valor_parceiro_novo ELSE CB.valor_parceiro END AS valor_parceiro_ajustado,
                        CASE WHEN CB.valor_produto_conecta_ajustado <> '0.00' THEN CB.valor_produto_conecta_ajustado ELSE CB.valor_produto_conecta END AS valor_produto_conecta_ajustado,
                        CASE WHEN CB.valor_frete_conecta_ajustado <> '0.00' THEN CB.valor_frete_conecta_ajustado ELSE CB.valor_frete_conecta END AS valor_frete_conecta_ajustado,
                        CASE WHEN CB.valor_conectala_ajustado <> '0.00' THEN CB.valor_conectala_ajustado ELSE CB.valor_conectala END AS valor_conectala_ajustado,
                        CB.pedido_enviado,
                        CB.tipo_frete,
                CB.valor_desconto_camp_promo,
                DATE_FORMAT(CB.data_gatilho, '%d-%m-%Y') as data_gatilho
            FROM conciliacao_mercadolivre CB
           LEFT JOIN (   SELECT OBS.*, CHAM.chamado_mktplace, CHAM.chamado_agidesk FROM (
                        SELECT num_pedido,
                        	lote,
                        	GROUP_CONCAT(CONCAT(' [',CONCAT(DATE_FORMAT(data_criacao, '%d-%m-%Y'),CONCAT('] - ',observacao)))) AS observacao
                        	FROM `conciliacao_pedido`
                        	GROUP BY num_pedido, lote) OBS
                        LEFT JOIN (
                        	SELECT num_pedido, lote, GROUP_CONCAT(DISTINCT IFNULL(chamado_mktplace,''),'-') AS chamado_mktplace,
                        		GROUP_CONCAT(DISTINCT IFNULL(chamado_agidesk,''),'-') AS chamado_agidesk
                        	FROM conciliacao_pedido
                        	GROUP BY num_pedido, lote) CHAM ON CHAM.num_pedido = OBS.num_pedido AND CHAM.lote = OBS.lote   ) OBS ON OBS.num_pedido = CB.external_reference AND OBS.lote = CB.lote
            LEFT JOIN conciliacao_temp_pedido ctp ON ctp.num_pedido = CB.external_reference AND ctp.lote = CB.lote
            WHERE CB.lote = '" . $data['lote'] . "' AND CASE WHEN ctp.num_pedido IS NOT NULL and CB.status_conciliacao IS NOT NULL THEN 'Ok' ELSE ifnull(CB.status_conciliacao_novo,CB.status_conciliacao) END = '" . $data['tipo'] . "'
            ORDER BY CB.id";

        $query = $this->db->query($sql);
        return (false !== $query) ? $query->result_array() : false;

    }
    
    public function getOrdersGridsMadeira($data = null, $paramConta = null)
    {

        if ($paramConta <> null) {
            $where = "ifnull(CB.status_conciliacao,'Estorno')";
        } else {
            $where = "CB.status_conciliacao";
        }

        $sql = "SELECT DISTINCT
            	'MAD' as marca,
                CB.seller_name,
                CB.store_id,
                CB.usuario,
            	CB.data_pedido,
            	CB.ref_pedido AS ref_pedido,
                '' AS entrega,
            	CB.valor AS valor_pedido_mktplace,
            	CASE WHEN ctp.num_pedido IS NOT NULL and CB.status_conciliacao IS NOT NULL THEN 'Ok' ELSE IFNULL(CB.status_conciliacao_novo,CB.status_conciliacao) END AS status_conciliacao,
                CB.valor_pedido,
            	CB.valor_pedido+CB.valor_frete AS valor_pedido_interno,
            	CB.valor_frete,
                CB.valor_frete_real,
                CB.valor_frete_real_contratado,
            	CB.valor_conectala,
                CB.valor_conectala_real,
                ROUND(CB.valor_conectala - CB.valor_conectala_real,2) AS valor_conectala_dif,
            	CB.valor_marketplace,
                CB.valor_comissao AS valor_da_comissao,
            	CB.valor_agencia,
            	CB.valor_parceiro,
                CB.valor_parceiro_novo,
            	CB.valor_autonomo,
            	CB.valor_afiliado,
                CB.valor_produto,
                CB.valor_percentual_mktplace,
                CB.valor_produto_calculado,
                CB.valor_frete_calculado,
                CB.valor_produto_parceiro,
                CB.valor_frete_parceiro,
                CB.valor_produto_conecta,
                CB.valor_frete_conecta,
                CB.valor_receita_calculado,
                CB.valor_produto_recebido,
                CB.valor_frete_recebido,
                CB.dif_valor_recebido,
                CB.dif_valor_recebido_produto,
                CB.dif_valor_recebido_frete,
                CB.valor_pago_marketplace AS valor_da_transacao,
                CB.valor_percentual_parceiro,
                CB.tratado,
                OBS.observacao,
                OBS.chamado_mktplace, 
                OBS.chamado_agidesk,
                CASE WHEN CB.valor_frete > (CB.valor_pedido*0.35) THEN 'Maior' ELSE 'Menor' END AS alertaFrete,
                CASE WHEN CB.valor_frete_real > (CB.valor_pedido*0.35) THEN 'Maior' ELSE 'Menor' END AS alertaFreteReal,
                CASE WHEN CB.valor_parceiro_novo <> '0.00' THEN CB.valor_parceiro_novo ELSE CB.valor_parceiro END AS valor_parceiro_ajustado,
                CASE WHEN CB.valor_produto_conecta_ajustado <> '0.00' THEN CB.valor_produto_conecta_ajustado ELSE CB.valor_produto_conecta END AS valor_produto_conecta_ajustado,
                CASE WHEN CB.valor_frete_conecta_ajustado <> '0.00' THEN CB.valor_frete_conecta_ajustado ELSE CB.valor_frete_conecta END AS valor_frete_conecta_ajustado,
                CASE WHEN CB.valor_conectala_ajustado <> '0.00' THEN CB.valor_conectala_ajustado ELSE CB.valor_conectala END AS valor_conectala_ajustado,
                CB.pedido_enviado,
                CB.tipo_frete,
                CB.valor_desconto_camp_promo,
                DATE_FORMAT(CB.data_gatilho, '%d-%m-%Y') as data_gatilho
            FROM conciliacao_madeira_tratado CB
           LEFT JOIN (   SELECT OBS.*, CHAM.chamado_mktplace, CHAM.chamado_agidesk FROM (
                        SELECT num_pedido, 
                        	lote, 
                        	GROUP_CONCAT(CONCAT(' [',CONCAT(DATE_FORMAT(data_criacao, '%d-%m-%Y'),CONCAT('] - ',observacao)))) AS observacao 
                        	FROM `conciliacao_pedido`
                        	GROUP BY num_pedido, lote) OBS
                        LEFT JOIN (
                        	SELECT num_pedido, lote, GROUP_CONCAT(DISTINCT IFNULL(chamado_mktplace,''),'-') AS chamado_mktplace, 
                        		GROUP_CONCAT(DISTINCT IFNULL(chamado_agidesk,''),'-') AS chamado_agidesk
                        	FROM conciliacao_pedido
                        	GROUP BY num_pedido, lote) CHAM ON CHAM.num_pedido = OBS.num_pedido AND CHAM.lote = OBS.lote   ) OBS ON OBS.num_pedido = CB.ref_pedido AND OBS.lote = CB.lote
            LEFT JOIN conciliacao_temp_pedido ctp ON ctp.num_pedido = CB.ref_pedido AND ctp.lote = CB.lote
            WHERE CB.lote = '" . $data['lote'] . "' AND CASE WHEN ctp.num_pedido IS NOT NULL and CB.status_conciliacao IS NOT NULL THEN 'Ok' ELSE ifnull(CB.status_conciliacao_novo,$where) END = '" . $data['tipo'] . "'
            ORDER BY CB.id";

        $query = $this->db->query($sql);
        return (false !== $query) ? $query->result_array() : false;

    }


    public function getOrdersGridsNM($data = null, $paramConta = null)
    {

        if ($paramConta <> null)        
            $where = "ifnull(NM.status_conciliacao,'Estorno')";
        else
            $where = "NM.status_conciliacao";        

        $sql = "SELECT DISTINCT
                '' AS marca,
                NM.seller as seller_name,
                NM.store_id,
                NM.usuario,
            	NM.data_emissao as data_pedido,
            	NM.orderid AS ref_pedido,
                NM.data_entrega as entrega,
            	NM.total AS valor_pedido_mktplace,
            	CASE WHEN ctp.num_pedido IS NOT NULL and NM.status_conciliacao IS NOT NULL THEN 'Ok' ELSE IFNULL(NM.status_conciliacao_novo,NM.status_conciliacao) END AS status_conciliacao,
                NM.valor_pedido,
            	NM.valor_pedido + NM.valor_frete AS valor_pedido_interno,
            	NM.valor_frete,
                NM.valor_frete_real,
                NM.valor_frete_real_contratado,
            	NM.valor_conectala,
                NM.valor_conectala_real,
                ROUND(NM.valor_conectala - NM.valor_conectala_real,2) AS valor_conectala_dif,
            	NM.valor_marketplace,
                NM.valor_comissao AS valor_da_comissao,
            	NM.valor_agencia,
            	NM.valor_parceiro,
                NM.valor_parceiro_novo,
            	NM.valor_autonomo,
            	NM.valor_afiliado,
                NM.valor_produto,
                NM.valor_percentual_mktplace,
                NM.valor_produto_calculado,
                NM.valor_frete_calculado,
                NM.valor_produto_parceiro,
                NM.valor_frete_parceiro,
                NM.valor_produto_conecta,
                NM.valor_frete_conecta,
                NM.valor_receita_calculado,
                NM.valor_produto_recebido,
                NM.valor_frete_recebido,
                NM.dif_valor_recebido,
                NM.dif_valor_recebido_produto,
                NM.dif_valor_recebido_frete,
                NM.valor_marketplace AS valor_da_transacao,
                NM.valor_percentual_parceiro,
                NM.valor_produto_conecta,
                NM.tratado,
                NM.valor_desconto_camp_promo,
                NM.valor_produto_conecta_ajustado,
                NM.valor_frete_conecta_ajustado,
                NM.valor_conectala_ajustado,
                NM.valor_parceiro_ajustado,
                OBS.observacao,
                OBS.chamado_mktplace, 
                OBS.chamado_agidesk,
                CASE WHEN NM.valor_frete > (NM.valor_pedido * 0.35) THEN 'Maior' ELSE 'Menor' END AS alertaFrete,
                CASE WHEN NM.valor_frete_real > (NM.valor_pedido * 0.35) THEN 'Maior' ELSE 'Menor' END AS alertaFreteReal,
                CASE WHEN NM.valor_parceiro_novo <> '0.00' THEN NM.valor_parceiro_novo ELSE NM.valor_parceiro END AS valor_parceiro_ajustado,
                CASE WHEN NM.valor_produto_calculado <> '0.00' THEN NM.valor_produto_calculado ELSE NM.valor_produto_conecta END AS valor_produto_calculado,
                CASE WHEN NM.valor_frete_conecta_ajustado <> '0.00' THEN NM.valor_frete_conecta_ajustado ELSE NM.valor_frete_conecta END AS valor_frete_conecta_ajustado,
                CASE WHEN NM.valor_conectala_ajustado <> '0.00' THEN NM.valor_conectala_ajustado ELSE NM.valor_conectala END AS valor_conectala_ajustado,
                CASE WHEN NM.valor_conectala_real <> '0.00' THEN NM.valor_conectala_real ELSE NM.valor_conectala END AS valor_conectala_real,
                NM.pedido_enviado,
                NM.tipo_frete,
                NM.data_gatilho     
            FROM conciliacao_nm NM
            LEFT JOIN (   SELECT OBS.*, CHAM.chamado_mktplace, CHAM.chamado_agidesk FROM (
                        SELECT num_pedido, 
                        	lote, 
                        	GROUP_CONCAT(CONCAT(' [',CONCAT(DATE_FORMAT(data_criacao, '%d-%m-%Y'),CONCAT('] - ',observacao)))) AS observacao 
                        	FROM `conciliacao_pedido`
                        	GROUP BY num_pedido, lote) OBS
                        LEFT JOIN (
                        	SELECT num_pedido, lote, GROUP_CONCAT(DISTINCT IFNULL(chamado_mktplace,''),'-') AS chamado_mktplace, 
                        		GROUP_CONCAT(DISTINCT IFNULL(chamado_agidesk,''),'-') AS chamado_agidesk
                        	FROM conciliacao_pedido
                        	GROUP BY num_pedido, lote) CHAM ON CHAM.num_pedido = OBS.num_pedido AND CHAM.lote = OBS.lote   ) OBS ON OBS.num_pedido = NM.orderid AND OBS.lote = NM.lote
            LEFT JOIN conciliacao_temp_pedido ctp ON ctp.num_pedido = NM.orderid AND ctp.lote = NM.lote
            WHERE NM.lote = '" . $data['lote'] . "' AND CASE WHEN ctp.num_pedido IS NOT NULL and NM.status_conciliacao IS NOT NULL THEN 'Ok' ELSE ifnull(NM.status_conciliacao_novo,$where) END = '" . $data['tipo'] . "'
            ORDER BY NM.id";

        $query = $this->db->query($sql);
        return (false !== $query) ? $query->result_array() : false;

    }


    public function salvaObservacao($input)
    {

        $data['lote'] = $input['hdnLote'];
        $data['num_pedido'] = $input['txt_hdn_pedido'];
        $data['observacao'] = $input['txt_observacao'];
        $data['chamado_mktplace'] = $input['txt_chamado_mktplace'];
        $data['chamado_agidesk'] = $input['txt_chamado_agidesk'];
        $data['conciliacao_obs_fixo_id'] = $input['slc_obs_fixo'];

        $insert = $this->db->insert('conciliacao_pedido', $data);
        $order_id = $this->db->insert_id();
        return ($order_id) ? $order_id : false;

    }

    public function salvaComissao($input)
    {

        if ($input['slc_mktplace'] == "10") {
            $sql = "UPDATE conciliacao_b2w_tratado SET valor_parceiro_novo = '" . $input['txt_comissao'] . "', valor_produto_conecta_ajustado = '" . $input['txt_comissao_produto_conectala'] . "', valor_frete_conecta_ajustado = '" . $input['txt_comissao_frete_conectala'] . "', valor_conectala_ajustado = round('" . $input['txt_comissao_produto_conectala'] . "' + '" . $input['txt_comissao_frete_conectala'] . "',2) WHERE lote = '" . $input['hdnLote'] . "' and ref_pedido = '" . $input['txt_hdn_pedido_comissao'] . "'";
        } elseif ($input['slc_mktplace'] == "15") {
            $sql = "UPDATE conciliacao_viavarejo SET valor_parceiro_novo = '" . $input['txt_comissao'] . "', valor_produto_conecta_ajustado = '" . $input['txt_comissao_produto_conectala'] . "', valor_frete_conecta_ajustado = '" . $input['txt_comissao_frete_conectala'] . "', valor_conectala_ajustado = round('" . $input['txt_comissao_produto_conectala'] . "' + '" . $input['txt_comissao_frete_conectala'] . "',2)  WHERE lote = '" . $input['hdnLote'] . "' and numero_do_pedido = '" . $input['txt_hdn_pedido_comissao'] . "'";
        } elseif ($input['slc_mktplace'] == "11") {
            $sql = "UPDATE conciliacao_mercadolivre SET valor_parceiro_novo = '" . $input['txt_comissao'] . "', valor_produto_conecta_ajustado = '" . $input['txt_comissao_produto_conectala'] . "', valor_frete_conecta_ajustado = '" . $input['txt_comissao_frete_conectala'] . "', valor_conectala_ajustado = round('" . $input['txt_comissao_produto_conectala'] . "' + '" . $input['txt_comissao_frete_conectala'] . "',2)  WHERE lote = '" . $input['hdnLote'] . "' and external_reference = '" . $input['txt_hdn_pedido_comissao'] . "'";
        } elseif ($input['slc_mktplace'] == "16") {
            $sql = "UPDATE conciliacao_carrefour SET valor_parceiro_novo = '" . $input['txt_comissao'] . "', valor_produto_conecta_ajustado = '" . $input['txt_comissao_produto_conectala'] . "', valor_frete_conecta_ajustado = '" . $input['txt_comissao_frete_conectala'] . "', valor_conectala_ajustado = round('" . $input['txt_comissao_produto_conectala'] . "' + '" . $input['txt_comissao_frete_conectala'] . "',2)  WHERE lote = '" . $input['hdnLote'] . "' and n_do_pedido = '" . $input['txt_hdn_pedido_comissao'] . "'";
            $sql1 = "UPDATE conciliacao_carrefour_xls SET valor_parceiro_novo = '" . $input['txt_comissao'] . "', valor_produto_conecta_ajustado = '" . $input['txt_comissao_produto_conectala'] . "', valor_frete_conecta_ajustado = '" . $input['txt_comissao_frete_conectala'] . "', valor_conectala_ajustado = round('" . $input['txt_comissao_produto_conectala'] . "' + '" . $input['txt_comissao_frete_conectala'] . "',2)  WHERE lote = '" . $input['hdnLote'] . "' and n_do_pedido = '" . $input['txt_hdn_pedido_comissao'] . "'";
            $this->db->query($sql1);
        }elseif ($input['slc_mktplace'] == "17") {
            $sql = "UPDATE conciliacao_madeira_tratado SET valor_parceiro_novo = '" . $input['txt_comissao'] . "', valor_produto_conecta_ajustado = '" . $input['txt_comissao_produto_conectala'] . "', valor_frete_conecta_ajustado = '" . $input['txt_comissao_frete_conectala'] . "', valor_conectala_ajustado = round('" . $input['txt_comissao_produto_conectala'] . "' + '" . $input['txt_comissao_frete_conectala'] . "',2)  WHERE lote = '" . $input['hdnLote'] . "' and ref_pedido = '" . $input['txt_hdn_pedido_comissao'] . "'";
        }
        elseif ($input['slc_mktplace'] == "30") 
        {
            $sql = "UPDATE conciliacao_nm SET valor_parceiro_novo = '" . $input['txt_comissao'] . "', valor_produto_conecta_ajustado = '" . $input['txt_comissao_produto_conectala'] . "', valor_frete_conecta_ajustado = '" . $input['txt_comissao_frete_conectala'] . "', valor_conectala_ajustado = round(REPLACE('".$input['txt_comissao_produto_conectala']."',',','.') + REPLACE('".$input['txt_comissao_frete_conectala']."',',','.'),2)  WHERE lote = '" . $input['hdnLote'] . "' and orderid = '" . $input['txt_hdn_pedido_comissao'] . "'";
        }

        return $this->db->query($sql);

    }

    public function criaconsiliacao($input)
    {
        $marketplaces = is_array($input['slc_mktplace']) ? $input['slc_mktplace'] : [$input['slc_mktplace']];
        $ciclos = is_array($input['slc_ciclo']) ? $input['slc_ciclo'] : [$input['slc_ciclo']];

        $data['lote'] = $input['hdnLote'];
        $data['status'] = "Conciliação com sucesso";
        $data['integ_id'] = $marketplaces[0];
        $data['param_mkt_ciclo_id'] = $ciclos[0];
        $data['ano_mes'] = $input['slc_ano_mes'];
        $data['users_id'] = $this->session->userdata('id');

        // Se houver adicionais, salva como JSON nos campos novos
        if (count($marketplaces) > 1) {
            $data['integ_ids_adicionais'] = json_encode(array_slice($marketplaces, 1));
        }

        if (count($ciclos) > 1) {
            $data['param_mkt_ciclo_ids_adicionais'] = json_encode(array_slice($ciclos, 1));
        }

        $this->db->insert('conciliacao', $data);
        $order_id = $this->db->insert_id();

        return $order_id ?: false;
    }

    public function editaconsiliacao($input)
    {

        $status = $this->statusConciliacaotbl($input['hdnLote'], $input['slc_mktplace']);

        $data = array(
            'status' => $status,
            'integ_id' => $input['slc_mktplace'],
            'param_mkt_ciclo_id' => $input['slc_ciclo'],
            'ano_mes' => $input['slc_ano_mes'],
            'users_id' => $this->session->userdata('id')
        );

        $this->db->where('lote', $input['hdnLote']);
        return $this->db->update('conciliacao', $data);

    }

    public function statusConciliacaotbl($lote, $mktPlace)
    {
        $sql = '';
        if ($mktPlace == "10") {
            $sql = "select ifnull(status_conciliacao_novo,status_conciliacao) as status_conciliacao, count(*) as qtd from conciliacao_b2w_tratado where lote = '$lote' group by ifnull(status_conciliacao_novo,status_conciliacao)";
        } elseif ($mktPlace == "11") {
            $sql = "select ifnull(status_conciliacao_novo,status_conciliacao) as status_conciliacao, count(*) as qtd from conciliacao_mercadolivre where lote = '$lote' group by ifnull(status_conciliacao_novo,status_conciliacao)";
        } elseif ($mktPlace == "15") {
            $sql = "select ifnull(status_conciliacao_novo,status_conciliacao) as status_conciliacao, count(*) as qtd from conciliacao_viavarejo where lote = '$lote' group by ifnull(status_conciliacao_novo,status_conciliacao)";
        } elseif ($mktPlace == "16") {
            $sql = "select ifnull(status_conciliacao_novo,status_conciliacao) as status_conciliacao, count(*) as qtd from conciliacao_carrefour where lote = '$lote' group by ifnull(status_conciliacao_novo,status_conciliacao)";
        } elseif ($mktPlace == "17") {
            $sql = "select ifnull(status_conciliacao_novo,status_conciliacao) as status_conciliacao, count(*) as qtd from conciliacao_madeira_tratado where lote = '$lote' group by ifnull(status_conciliacao_novo,status_conciliacao)";
        }
        elseif ($mktPlace == "30") 
        {
            $sql = "select ifnull(status_conciliacao_novo,status_conciliacao) as status_conciliacao, count(*) as qtd from conciliacao_nm where lote = '$lote' group by ifnull(status_conciliacao_novo,status_conciliacao)";
        }

        if ($sql == ''){
            return "Conciliação com sucesso";
        }

        $query = $this->db->query($sql);
        $saida = $query->result_array();

        $status = 0;
        foreach ($saida as $statusteste) {
            if ($statusteste['status_conciliacao'] <> "Ok" and $statusteste['qtd'] > 0) {
                $status = 1;
            }
        }

        if ($status > 0) {
            return "Conciliação com pendências";
        } else {
            return "Conciliação com sucesso";
        }

    }

    public function mudastatuspedidolote($input)
    {

        if ($input['slc_mktplace'] == "10") {
            $sql = "UPDATE conciliacao_b2w_tratado SET status_conciliacao_novo = 'Ok' WHERE lote = '" . $input['hdnLote'] . "' AND ref_pedido IN (SELECT DISTINCT num_pedido FROM conciliacao_temp_pedido WHERE lote = '" . $input['hdnLote'] . "') ";
        } elseif ($input['slc_mktplace'] == "11") {
            $sql = "UPDATE conciliacao_mercadolivre SET status_conciliacao_novo = 'Ok' WHERE lote = '" . $input['hdnLote'] . "' AND external_reference IN (SELECT DISTINCT num_pedido FROM conciliacao_temp_pedido WHERE lote = '" . $input['hdnLote'] . "') ";
        } elseif ($input['slc_mktplace'] == "15") {
            $sql = "UPDATE conciliacao_viavarejo SET status_conciliacao_novo = 'Ok' WHERE lote = '" . $input['hdnLote'] . "' AND numero_do_pedido IN (SELECT DISTINCT num_pedido FROM conciliacao_temp_pedido WHERE lote = '" . $input['hdnLote'] . "') ";
        } elseif ($input['slc_mktplace'] == "16") {
            $sql = "UPDATE conciliacao_carrefour SET status_conciliacao_novo = 'Ok' WHERE lote = '" . $input['hdnLote'] . "' AND n_do_pedido IN (SELECT DISTINCT num_pedido FROM conciliacao_temp_pedido WHERE lote = '" . $input['hdnLote'] . "') ";
            $sql1 = "UPDATE conciliacao_carrefour SET status_conciliacao_novo = 'Ok' WHERE lote = '" . $input['hdnLote'] . "' AND n_do_pedido IN (SELECT DISTINCT num_pedido FROM conciliacao_temp_pedido WHERE lote = '" . $input['hdnLote'] . "') ";
            $this->db->query($sql1);
        } elseif ($input['slc_mktplace'] == "17") {
            $sql = "UPDATE conciliacao_madeira_tratado SET status_conciliacao_novo = 'Ok' WHERE lote = '" . $input['hdnLote'] . "' AND ref_pedido IN (SELECT DISTINCT num_pedido FROM conciliacao_temp_pedido WHERE lote = '" . $input['hdnLote'] . "') ";
        }
        elseif ($input['slc_mktplace'] == "30") 
        {
            $sql = "UPDATE conciliacao_nm SET status_conciliacao_novo = 'Ok' WHERE lote = '" . $input['hdnLote'] . "' AND orderid IN (SELECT DISTINCT num_pedido FROM conciliacao_temp_pedido WHERE lote = '" . $input['hdnLote'] . "') ";
        }
        return $this->db->query($sql);

    }

    public function insereconciliacaotemppedido($input)
    {

        $data['integ_id'] = $input['mktplace'];
        $data['lote'] = $input['lote'];
        $data['num_pedido'] = $input['pedido'];

        $insert = $this->db->insert('conciliacao_temp_pedido', $data);
        $order_id = $this->db->insert_id();
        return ($order_id) ? $order_id : false;

    }

    public function insereconciliacaotemppedidolote($input){

        $sql1 = "INSERT INTO conciliacao_temp_pedido (integ_id,lote,num_pedido) ";
        $sql2 = "INSERT INTO conciliacao_temp_pedido (integ_id,lote,num_pedido) ";
        
        if($input['mktplace'] == "10"){
            $sql1 .= " select '".$input['mktplace']."','".$input['lote']."', ref_pedido from conciliacao_b2w_tratado where lote = '".$input['lote']."' and `status_conciliacao` = '".$input['status']."'";
        }elseif($input['mktplace'] == "11"){
            $sql1 .= " select '".$input['mktplace']."','".$input['lote']."', external_reference from conciliacao_mercadolivre where lote = '".$input['lote']."' and status_conciliacao = '".$input['status']."'";
        }elseif($input['mktplace'] == "15"){
            $sql1 .= " select '".$input['mktplace']."','".$input['lote']."', numero_do_pedido from conciliacao_viavarejo where lote = '".$input['lote']."' and status_conciliacao = '".$input['status']."'";
        }elseif($input['mktplace'] == "16"){
            $sql1 .= " select '".$input['mktplace']."','".$input['lote']."', n_do_pedido from conciliacao_carrefour_xls where lote = '".$input['lote']."' and status_conciliacao = '".$input['status']."'";
            $sql2 .= " select '".$input['mktplace']."','".$input['lote']."', n_do_pedido from conciliacao_carrefour where lote = '".$input['lote']."' and status_conciliacao = '".$input['status']."'";
            $this->db->query($sql2);
        }elseif($input['mktplace'] == "17"){
            $sql1 .= " select '".$input['mktplace']."','".$input['lote']."', ref_pedido from conciliacao_madeira_tratado where lote = '".$input['lote']."' and status_conciliacao = '".$input['status']."'";
        }
        elseif($input['mktplace'] == "30")
        {
            $sql1 .= " select '".$input['mktplace']."','".$input['lote']."', orderid from conciliacao_nm where lote = '".$input['lote']."' and status_conciliacao = '".$input['status']."'";
        }

        return $this->db->query($sql1);

    }

    public function limpatabelaconciliacaotempedido($lote)
    {

        $sql = "delete from conciliacao_temp_pedido where lote = '$lote'";
        return $this->db->query($sql);

    }

    public function verificaconsiliacao($input)
    {

        $sql = "select count(*) as qtd from conciliacao where lote = '" . $input['hdnLote'] . "'";

        $query = $this->db->query($sql);
        $saida = $query->result_array();
        return $saida[0];

    }

    public function mudastatusconciliacao($input)
    {

        $status = $this->statusConciliacaotbl($input['hdnLote'], $input['slc_mktplace']);

        if ($input['slc_mktplace'] == "10") {
            $sql = "UPDATE conciliacao SET status = '$status' WHERE lote = '" . $input['hdnLote'] . "'";
        } elseif ($input['slc_mktplace'] == "11") {
            $sql = "UPDATE conciliacao SET status = '$status' WHERE lote = '" . $input['hdnLote'] . "'";
        } elseif ($input['slc_mktplace'] == "15") {
            $sql = "UPDATE conciliacao SET status = '$status' WHERE lote = '" . $input['hdnLote'] . "'";
        } elseif ($input['slc_mktplace'] == "16") {
            $sql = "UPDATE conciliacao SET status = '$status' WHERE lote = '" . $input['hdnLote'] . "'";
        } elseif ($input['slc_mktplace'] == "17") {
            $sql = "UPDATE conciliacao SET status = '$status' WHERE lote = '" . $input['hdnLote'] . "'";
        }
        elseif ($input['slc_mktplace'] == "30") 
        {
            $sql = "UPDATE conciliacao SET status = '$status' WHERE lote = '" . $input['hdnLote'] . "'";
        }

        return $this->db->query($sql);

    }

    public function getDataObservacaoFixaPedido($id = null)
    {

        $sql = "select * from conciliacao_obs_fixo";

        if ($id) {
            $sql .= " where id = $id";
        }

        $sql .= " order by id";

        $query = $this->db->query($sql);
        return (false !== $query) ? $query->result_array() : false;

    }

    public function getConciliacaoGridDataTransp($transportadora = null, $ciclo = null)
    {

        $sql = "SELECT 	O.id,
                	C.name AS empresa,
                	S.name AS loja,
                    FR.ship_company,
                    O.data_entrega,
                	O.numero_marketplace,
                	O.paid_status,
                	O.frete_real,
                    O.frete_real_recalculado,
                    CT.observacao
                FROM orders O
                left join (SELECT num_pedido, GROUP_CONCAT(CONCAT(' [',CONCAT(DATE_FORMAT(data_criacao, '%d-%m-%Y'),CONCAT('] - ',observacao)))) AS observacao FROM `conciliacao_transportadora` group by num_pedido) CT ON O.numero_marketplace = CT.num_pedido
                LEFT JOIN freights FR ON FR.order_id = O.id
                LEFT JOIN company C ON C.id = O.company_id
                LEFT JOIN stores S ON S.id = O.store_id 
                where 1=1 ";


        if ($transportadora) {
            $sql .= " and FR.ship_company = REPLACE('$transportadora', '%20', ' ' )";
        }

        if ($ciclo) {
            $sql .= " and O.data_entrega >= (SELECT DATE(CONCAT(YEAR(CURRENT_TIMESTAMP),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL -1 MONTH)),CONCAT('/',CONCAT(PMC.data_inicio,CONCAT(' 00:00:00'))))))) AS data_inicio FROM param_mkt_ciclo_transp PMC WHERE PMC.id = $ciclo) and O.data_entrega <= (SELECT CASE WHEN DATE(CONCAT(YEAR(CURRENT_TIMESTAMP),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL -1 MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00'))))))) IS NULL THEN DATE(CONCAT(YEAR(CURRENT_TIMESTAMP),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL -1 MONTH)),CONCAT('/',CONCAT(PMC.data_fim-1,CONCAT(' 00:00:00')))))))  ELSE DATE(CONCAT(YEAR(CURRENT_TIMESTAMP),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL -1 MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00'))))))) END AS data_fim FROM param_mkt_ciclo_transp PMC WHERE PMC.id = $ciclo)";
        }

        $sql .= " order by O.id";

        $query = $this->db->query($sql);
        return (false !== $query) ? $query->result_array() : false;

    }

    public function getTransportadorasFreights()
    {

        $sql = "SELECT DISTINCT ship_company AS id, ship_company FROM freights";

        $query = $this->db->query($sql);
        return (false !== $query) ? $query->result_array() : false;

    }

    public function salvaObservacaoTransp($input)
    {

        $data['num_pedido'] = $input['txt_hdn_pedido'];
        $data['observacao'] = $input['txt_observacao'];
        $data['conciliacao_obs_fixo_id'] = $input['slc_obs_fixo'];

        $insert = $this->db->insert('conciliacao_transportadora', $data);
        $order_id = $this->db->insert_id();
        if ($order_id) {
            return $this->alteraValorFreteNovoPedido($input);
        } else {
            return false;
        }

    }

    public function alteraValorFreteNovoPedido($inputs)
    {

        $sql = "UPDATE ORDERS SET frete_real_recalculado = " . $inputs['txt_novo_frete'] . " WHERE numero_marketplace = '" . $inputs['txt_hdn_pedido'] . "'";
        $update1 = $this->db->query($sql);

        return $update1;


    }

    public function salvaPedidoTratadoConeciliacao($input)
    {

        if ($input['mktplace'] == "10") {
            $sql = "UPDATE conciliacao_b2w_tratado SET tratado = '1', usuario = '" . $_SESSION['username'] . "' WHERE lote = '" . $input['lote'] . "' AND ref_pedido = '" . $input['pedido'] . "' ";
        } elseif ($input['mktplace'] == "15") {
            $sql = "UPDATE conciliacao_viavarejo SET tratado = '1', usuario = '" . $_SESSION['username'] . "' WHERE lote = '" . $input['lote'] . "' AND numero_do_pedido = '" . $input['pedido'] . "' ";
        } elseif ($input['mktplace'] == "11") {
            $sql = "UPDATE conciliacao_mercadolivre SET tratado = '1', usuario = '" . $_SESSION['username'] . "' WHERE lote = '" . $input['lote'] . "' and external_reference = '" . $input['pedido'] . "'";
        } elseif ($input['mktplace'] == "16") {
            $sql = "UPDATE conciliacao_carrefour SET tratado = '1', usuario = '" . $_SESSION['username'] . "' WHERE lote = '" . $input['lote'] . "' and n_do_pedido = '" . $input['pedido'] . "'";
            $sql1 = "UPDATE conciliacao_carrefour_xls SET tratado = '1', usuario = '" . $_SESSION['username'] . "' WHERE lote = '" . $input['lote'] . "' and n_do_pedido = '" . $input['pedido'] . "'";
            $this->db->query($sql1);
        }elseif ($input['mktplace'] == "17") {
            $sql = "UPDATE conciliacao_madeira_tratado SET tratado = '1', usuario = '" . $_SESSION['username'] . "' WHERE lote = '" . $input['lote'] . "' AND ref_pedido = '" . $input['pedido'] . "' ";
        }
        elseif ($input['mktplace'] == "30") 
        {
            $sql = "UPDATE conciliacao_nm SET tratado = '1', usuario = '" . $_SESSION['username'] . "' WHERE lote = '" . $input['lote'] . "' AND orderid = '" . $input['pedido'] . "' ";
        }
        elseif ($input['mktplace'] == "999")
        {
            $sql = "UPDATE conciliacao_manual SET tratado = '1', usuario = '" . $_SESSION['username'] . "' WHERE lote = '" . $input['lote'] . "' AND numero_pedido = '" . $input['pedido'] . "' ";
        }

        return $this->db->query($sql);

    }

    public function salvaPedidoTratadoConeciliacaoLote($input)
    {

        if ($input['mktplace'] == "10") {
            $sql = "UPDATE conciliacao_b2w_tratado SET tratado = '1', usuario = '" . $_SESSION['username'] . "' WHERE lote = '" . $input['lote'] . "'";
        } elseif ($input['mktplace'] == "15") {
            $sql = "UPDATE conciliacao_viavarejo SET tratado = '1', usuario = '" . $_SESSION['username'] . "' WHERE lote = '" . $input['lote'] . "'";
        } elseif ($input['mktplace'] == "11") {
            $sql = "UPDATE conciliacao_mercadolivre SET tratado = '1', usuario = '" . $_SESSION['username'] . "' WHERE lote = '" . $input['lote'] . "'";
        } elseif ($input['mktplace'] == "16") {
            $sql = "UPDATE conciliacao_carrefour SET tratado = '1', usuario = '" . $_SESSION['username'] . "' WHERE lote = '" . $input['lote'] . "'";
            $sql1 = "UPDATE conciliacao_carrefour_xls SET tratado = '1', usuario = '" . $_SESSION['username'] . "' WHERE lote = '" . $input['lote'] . "'";
            $this->db->query($sql1);
        }elseif ($input['mktplace'] == "17") {
            $sql = "UPDATE conciliacao_madeira_tratado SET tratado = '1', usuario = '" . $_SESSION['username'] . "' WHERE lote = '" . $input['lote'] . "'";
		}
        elseif ($input['mktplace'] == "30") 
        {
            $sql = "UPDATE conciliacao_nm SET tratado = '1', usuario = '" . $_SESSION['username'] . "' WHERE lote = '" . $input['lote'] . "'";
		}
        elseif ($input['mktplace'] == "999")
        {
            $sql = "UPDATE conciliacao_manual SET tratado = '1', usuario = '" . $_SESSION['username'] . "' WHERE lote = '" . $input['lote'] . "'";
		}

        return $this->db->query($sql);

    }

    public function buscaobservacaopedido($lote, $num_pedido, $tipo = null)
    {

        if ($tipo <> null) {
            $campos = 'num_pedido, observacao, data_criacao';
        } else {
            $campos = '*';
        }

        $sql = "SELECT $campos FROM `conciliacao_pedido` where num_pedido = '$num_pedido' and lote = '$lote'";

        $query = $this->db->query($sql);
        return (false !== $query) ? $query->result_array() : false;

    }

    public function buscaobservacaopedidofixo($lote, $num_pedido, $tipo = null)
    {

        if ($tipo <> null) {
            $campos = 'CP.num_pedido, COF.observacao_fixa as observacao, CP.data_criacao';
        } else {
            $campos = '*';
        }

        $sql = "SELECT $campos FROM `conciliacao_pedido` CP INNER JOIN conciliacao_obs_fixo COF ON COF.id = CP.conciliacao_obs_fixo_id where num_pedido = '$num_pedido' and lote = '$lote'";

        $query = $this->db->query($sql);
        return (false !== $query) ? $query->result_array() : false;

    }

    public function buscaobservacaopedidofixolote($lote, $store_id, $tipo = null)
    {

        if ($tipo <> null) {
            $campos = 'CP.num_pedido, COF.observacao_fixa as observacao, CP.data_criacao';
        } else {
            $campos = 'CP.*, COF.*';
        }

        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND O.company_id = " . $this->data['usercomp'] : " AND O.store_id = " . $this->data['userstore']);

        $sql = "SELECT distinct $campos FROM `conciliacao_pedido` CP 
                INNER JOIN conciliacao_obs_fixo COF ON COF.id = CP.conciliacao_obs_fixo_id
                INNER JOIN 
                (SELECT CONCI.* FROM (
                SELECT CC.lote, CC.n_do_pedido AS numero_do_pedido, CASE WHEN valor_parceiro_novo = '0.00' THEN valor_parceiro ELSE valor_parceiro_novo END AS valor_parceiro, CASE WHEN tratado = 0 THEN \"Não tratado\" ELSE \"Tratado\" END AS tratado, SML.apelido FROM conciliacao_carrefour CC INNER JOIN `conciliacao` C ON C.lote = CC.lote INNER JOIN `stores_mkts_linked` SML ON SML.id_mkt = C. integ_id UNION
                SELECT CC.lote, CC.numero_do_pedido AS numero_do_pedido, CASE WHEN valor_parceiro_novo = '0.00' THEN valor_parceiro ELSE valor_parceiro_novo END AS valor_parceiro, CASE WHEN tratado = 0 THEN \"Não tratado\" ELSE \"Tratado\" END AS tratado, SML.apelido FROM conciliacao_viavarejo CC INNER JOIN `conciliacao` C ON C.lote = CC.lote INNER JOIN `stores_mkts_linked` SML ON SML.id_mkt = C. integ_id UNION
                SELECT CC.lote, CC.external_reference AS numero_do_pedido, CASE WHEN valor_parceiro_novo = '0.00' THEN valor_parceiro ELSE valor_parceiro_novo END AS valor_parceiro, CASE WHEN tratado = 0 THEN \"Não tratado\" ELSE \"Tratado\" END AS tratado, SML.apelido FROM conciliacao_mercadolivre CC INNER JOIN `conciliacao` C ON C.lote = CC.lote INNER JOIN `stores_mkts_linked` SML ON SML.id_mkt = C. integ_id ) CONCI
                INNER JOIN orders O ON O.numero_marketplace = CONCI.numero_do_pedido
                where O.store_id = $store_id $more 
                ) PED ON PED.lote = CP.lote and CP.num_pedido = PED.numero_do_pedido
                WHERE CP.lote = '$lote'";

        $query = $this->db->query($sql);
        return (false !== $query) ? $query->result_array() : false;

    }


    public function getConciliacaoGridDataTranspresumo($transportadora = null, $ciclo = null)
    {

        $transportadoraFiltro = "";
        $cicloFiltro = "";

        if ($transportadora) {
            $transportadoraFiltro = " and FR.ship_company = REPLACE('$transportadora', '%20', ' ' )";
        }

        if ($ciclo) {
            $cicloFiltro = " AND PMC.id = $ciclo";
        }


        $sql = "SELECT 
                	FGRID.CNPJ,
                	FGRID.ship_company,
                	round(SUM(FGRID.ship_value),2) AS ship_value,
			        FGRID.id AS idCiclo,
                	FGRID.tipo_ciclo,
                	FGRID.dia_semana,
                	FGRID.data_inicio,
                	FGRID.data_fim,
                	FGRID.data_pagamento,
                	FGRID.data_pagamento_conecta,
                	FGRID.statusPedido,
                	OBS.observacao,
                	FRETE.valor_pago_real,
                    round(SUM(FGRID.ship_value) - FRETE.valor_pago_real,2) as diferenca_frete
                FROM (
                SELECT 
                	FR.CNPJ,
                	FR.ship_company,
                	FR.ship_value,
                	FC.id,
                	FC.tipo_ciclo,
                	FC.dia_semana,
                	FC.data_inicio,
                	FC.data_fim,
                	FC.data_pagamento,
                	FC.data_pagamento_conecta,
                	'Entregue' AS statusPedido
                FROM `freights` FR
                INNER JOIN orders O ON O.id = FR.order_id
                LEFT JOIN (SELECT 	PMC.id,
					           REPLACE(REPLACE(REPLACE(P.cnpj,'.',''),'-',''),'/','') AS cnpj,
                			PMC.tipo_ciclo,
                			PMC.dia_semana,
                			PMC.data_pagamento,
                			PMC.data_pagamento_conecta,
                			DATE(CONCAT(YEAR(CURRENT_TIMESTAMP),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(PMC.data_inicio,CONCAT(' 00:00:00'))))))) AS data_inicio,
                			CASE WHEN 
                			DATE(CONCAT(YEAR(CURRENT_TIMESTAMP),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00'))))))) IS NULL THEN 
                			DATE(CONCAT(YEAR(CURRENT_TIMESTAMP),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(PMC.data_fim-1,CONCAT(' 00:00:00')))))))  
                			ELSE DATE(CONCAT(YEAR(CURRENT_TIMESTAMP),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00'))))))) END AS data_fim  
                		FROM `param_mkt_ciclo_transp` PMC
                		INNER JOIN providers P ON P.id = PMC.providers_id
                        WHERE 1=1 $cicloFiltro) FC ON FC.cnpj = FR.CNPJ AND O.data_entrega >= FC.data_inicio AND O.data_entrega <= FC.data_fim
                WHERE O.data_entrega IS NOT NULL $transportadoraFiltro
                UNION
                SELECT 
                	FR.CNPJ,
                	FR.ship_company,
                	FR.ship_value,
                	FC.id,
                	FC.tipo_ciclo,
                	FC.dia_semana,
                	FC.data_inicio,
                	FC.data_fim,
                	FC.data_pagamento,
                	FC.data_pagamento_conecta,
                	'Previsão de entrega' AS statusPedido
                FROM `freights` FR
                INNER JOIN orders O ON O.id = FR.order_id
                INNER JOIN (SELECT 	PMC.id,
				            REPLACE(REPLACE(REPLACE(P.cnpj,'.',''),'-',''),'/','') AS cnpj,
                			PMC.tipo_ciclo,
                			PMC.dia_semana,
                			PMC.data_pagamento,
                			PMC.data_pagamento_conecta,
                			DATE(CONCAT(YEAR(CURRENT_TIMESTAMP),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(PMC.data_inicio,CONCAT(' 00:00:00'))))))) AS data_inicio,
                			CASE WHEN 
                			DATE(CONCAT(YEAR(CURRENT_TIMESTAMP),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00'))))))) IS NULL THEN 
                			DATE(CONCAT(YEAR(CURRENT_TIMESTAMP),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(PMC.data_fim-1,CONCAT(' 00:00:00')))))))  
                			ELSE DATE(CONCAT(YEAR(CURRENT_TIMESTAMP),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00'))))))) END AS data_fim  
                		FROM `param_mkt_ciclo_transp` PMC
                		INNER JOIN providers P ON P.id = PMC.providers_id
                        WHERE 1=1 $cicloFiltro) FC ON FC.cnpj = FR.CNPJ AND O.data_coleta >= FC.data_inicio AND O.data_coleta <= FC.data_fim
                WHERE O.data_entrega IS NULL $transportadoraFiltro
                UNION
                SELECT 
                	FR.CNPJ,
                	FR.ship_company,
                	SUM(FR.ship_value) AS ship_value,
                	'' as id,
                	'' AS tipo_ciclo,
                	'' AS dia_semana,
                	'' AS data_inicio,
                	'' AS data_fim,
                	'' AS data_pagamento,
                	'' AS data_pagamento_conecta,
                	'Não contabilizado' AS statusPedido
                FROM `freights` FR
                INNER JOIN orders O ON O.id = FR.order_id
                LEFT JOIN (SELECT 	PM.id,
                            REPLACE(REPLACE(REPLACE(P.cnpj,'.',''),'-',''),'/','') AS cnpj,
                			PM.tipo_ciclo,
                			PM.dia_semana,
                			PM.data_inicio,
                			PM.data_fim,
                			PM.data_pagamento,
                			PM.data_pagamento_conecta
                		FROM `param_mkt_ciclo_transp` PM
                		INNER JOIN providers P ON P.id = PM.providers_id
                        WHERE 1=1 $cicloFiltro) FC ON FC.cnpj = FR.CNPJ
                WHERE O.data_entrega IS NULL $transportadoraFiltro
                GROUP BY FR.CNPJ,
                	FR.ship_company) FGRID
                LEFT JOIN (SELECT param_mkt_ciclo_transp_id,cnpj, GROUP_CONCAT(CONCAT(' [',CONCAT(DATE_FORMAT(data_criacao, '%d-%m-%Y'),CONCAT('] - ',observacao)))) AS observacao FROM conciliacao_transportadora_resumo WHERE valor_pago_real IS NULL GROUP BY param_mkt_ciclo_transp_id,cnpj) OBS ON OBS.param_mkt_ciclo_transp_id = FGRID.id AND OBS.cnpj = FGRID.CNPJ
		          LEFT JOIN (SELECT param_mkt_ciclo_transp_id,cnpj, valor_pago_real FROM conciliacao_transportadora_resumo WHERE valor_pago_real IS NOT NULL GROUP BY param_mkt_ciclo_transp_id,cnpj) FRETE ON FRETE.param_mkt_ciclo_transp_id = FGRID.id AND FRETE.cnpj = FGRID.CNPJ
                GROUP BY FGRID.CNPJ,
                	FGRID.ship_company,
                	FGRID.tipo_ciclo,
			        FGRID.id,
                	FGRID.dia_semana,
                	FGRID.data_inicio,
                	FGRID.data_fim,
                	FGRID.data_pagamento,
                	FGRID.data_pagamento_conecta,
                	FGRID.statusPedido,
                	OBS.observacao,
                	FRETE.valor_pago_real ";

        $query = $this->db->query($sql);
        return (false !== $query) ? $query->result_array() : false;

    }

    public function salvaObservacaoTranspresumo($input)
    {

        $valores = explode("|", $input['txt_hdn_pedido']);

        $data['param_mkt_ciclo_transp_id'] = $valores[0];
        $data['cnpj'] = $valores[1];
        $data['observacao'] = $input['txt_observacao'];
        $data['conciliacao_obs_fixo_id'] = $input['slc_obs_fixo'];

        $insert = $this->db->insert('conciliacao_transportadora_resumo', $data);
        return $this->db->insert_id();

    }

    public function salvaFreteTranspresumo($input)
    {

        $valores = explode("|", $input['txt_hdn_pedido_valor']);

        //verifica se já existe valor cadastrado
        $sql = "select count(*) as qtd from conciliacao_transportadora_resumo where param_mkt_ciclo_transp_id = '" . $valores[0] . "' and cnpj = '" . $valores[1] . "' and conciliacao_obs_fixo_id = 0 and observacao is null";
        $query = $this->db->query($sql);

        $retorno = $query->result_array();

        if ($retorno[0]['qtd'] == "0") {
            $data['param_mkt_ciclo_transp_id'] = $valores[0];
            $data['cnpj'] = $valores[1];
            $data['valor_pago_real'] = $input['txt_novo_frete'];

            $insert = $this->db->insert('conciliacao_transportadora_resumo', $data);
            return $this->db->insert_id();
        } else {

            $sql2 = "UPDATE conciliacao_transportadora_resumo SET valor_pago_real = '" . $input['txt_novo_frete'] . "' where param_mkt_ciclo_transp_id = '" . $valores[0] . "' and cnpj = '" . $valores[1] . "' and conciliacao_obs_fixo_id = 0 and observacao is null";
            return $this->db->query($sql2);
        }


    }

    public function buscaobservacaotranspresumo($chave, $tipo = null)
    {

        if ($tipo <> null) {
            $campos = 'cnpj, observacao, data_criacao';
        } else {
            $campos = '*';
        }

        $valores = explode("|", $chave);

        $sql = "SELECT $campos FROM `conciliacao_transportadora_resumo` 
                where param_mkt_ciclo_transp_id = '" . $valores[0] . "' and cnpj = '" . $valores[1] . "' and valor_pago_real IS NULL";

        $query = $this->db->query($sql);
        return (false !== $query) ? $query->result_array() : false;

    }

    public function getGridContaGridDataTranspPedido($transportadora = null, $ciclo = null)
    {


        $sql = "SELECT 	ORDS.origin,
                    	CICLO.descloja,
                    	round(SUM(ORDS.gross_amount),2) AS valor,
                    	CICLO.data_pagamento
                    FROM orders ORDS
                    INNER JOIN (
                    SELECT DISTINCT 
                    		PMC.integ_id, 
                    		SMK.apelido,
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
                    WHERE PMC.ativo = 1) CICLO ON ORDS.data_entrega >= CICLO.data_inicio  AND ORDS.data_entrega <= CICLO.data_fim AND ORDS.origin = CICLO.apelido
                    GROUP BY 	ORDS.origin,
                    		CICLO.data_pagamento
                    ORDER BY ORDS.origin,
                    	CICLO.data_pagamento,
                    	CICLO.descloja";

        $query = $this->db->query($sql);
        return (false !== $query) ? $query->result_array() : false;

    }

    public function getGridContaGridDataTransp($transportadora = null, $ciclo = null)
    {

        $sql = "SELECT
                	frt.CNPJ AS origin,
                	frt.ship_company AS descloja,
                	ROUND(SUM(IFNULL(frete_real_recalculado,frete_real)),2)*-1 AS valor,
                	CICLO.data_pagamento
                	FROM orders ORDS
                	INNER JOIN freights frt ON frt.order_id = ORDS.id
                	INNER JOIN (
                	    SELECT DISTINCT
                	    SMK.name AS apelido,
                	    SMK.cnpj,
                	    DATE(CONCAT(YEAR(CURRENT_TIMESTAMP),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL -1 MONTH)),CONCAT('/',CONCAT(PMC.data_inicio,CONCAT(' 00:00:00'))))))) AS data_inicio,
                    CASE WHEN DATE(CONCAT(YEAR(CURRENT_TIMESTAMP),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL -1 MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00'))))))) IS NULL THEN
                	    DATE(CONCAT(YEAR(CURRENT_TIMESTAMP),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL -1 MONTH)),CONCAT('/',CONCAT(PMC.data_fim-1,CONCAT(' 00:00:00')))))))
                	    ELSE
                	        DATE(CONCAT(YEAR(CURRENT_TIMESTAMP),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL -1 MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00')))))))
                	    END AS data_fim,
                	    PMC.data_pagamento_conecta AS data_pagamento
                	    FROM `param_mkt_ciclo_transp` PMC
                	    INNER JOIN providers SMK ON SMK.id = PMC.providers_id
                	    WHERE PMC.ativo = 1) CICLO ON ORDS.data_entrega >= CICLO.data_inicio  AND ORDS.data_entrega <= CICLO.data_fim AND frt.CNPJ = CICLO.cnpj
                	    GROUP BY frt.CNPJ,
                	    frt.ship_company,
                	    CICLO.data_pagamento";

        $query = $this->db->query($sql);
        return (false !== $query) ? $query->result_array() : false;

    }

    public function getPedidosExtratoConciliadoResumo($input, $order = null, $post = [])
    {
        
        /*$where = "";
        $where2 = "WHERE 1 = 1";
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND O.company_id = " . $this->data['usercomp'] : " AND O.store_id = " . $this->data['userstore']);
        if (!empty($input)) {
            if ($input['txt_data_inicio'] <> "") {
                $where .= " AND O.date_time >= '" . $input['txt_data_inicio'] . " 00:00:00'";
            }
            if ($input['txt_data_fim'] <> "") {
                $where .= " AND O.date_time <= '" . $input['txt_data_fim'] . " 23:59:59'";
            }
            if ($input['txt_data_inicio_repasse'] <> "") {
                $where .= " AND E.data_transferencia >= '" . $input['txt_data_inicio_repasse'] . " 00:00:00'";
            }
            if ($input['txt_data_fim_repasse'] <> "") {
                $where .= " AND E.data_transferencia <= '" . $input['txt_data_fim_repasse'] . " 23:59:59'";
            }
            if ($input['slc_mktplace'] <> "") {
                $where .= " AND SML.apelido = '" . $input['slc_mktplace'] . "'";
            }
            if ($input['slc_status'] <> "") {
                $where .= " AND O.paid_status in (" . $input['slc_status'] . ")";
            }
            if ($input['slc_loja'] <> "") {
                $where .= " AND O.store_id = '" . $input['slc_loja'] . "'";
            }
        }*/

        $where = "";
        $where2 = "WHERE 1=1 ";
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND O.company_id = " . $this->data['usercomp'] : " AND O.store_id = " . $this->data['userstore']);

        $valorGSOMA = $this->model_settings->getSettingDatabyNameEmptyArray('gsoma_painel_financeiro');
        $valorNMUNDO = $this->model_settings->getSettingDatabyNameEmptyArray('novomundo_painel_financeiro');
        $valorORTB = $this->model_settings->getSettingDatabyNameEmptyArray('ortobom_painel_financeiro');
        $valorSellercenter = $this->model_settings->getSettingDatabyName('sellercenter');

        $irrfDescontado = $this->model_settings->getSettingDatabyName('irrf_painel_financeiro');
        $irrfValorPercentual = $this->model_settings->getSettingDatabyName('irrf_valor_painel_financeiro');

		if($valorSellercenter['value'] <> "conectala"){
			$valorSellercenter['status'] = 1;
		}else{
			$valorSellercenter['status'] = 0;
		}

        if (!empty($input)) {
            if ($input['txt_data_inicio'] <> "") {
                $where .= " AND O.date_time >= '" . $input['txt_data_inicio'] . " 00:00:00'";
            }

            if ($input['txt_data_fim'] <> "") {
                $where .= " AND O.date_time <= '" . $input['txt_data_fim'] . " 23:59:59'";
            }

            if ($input['txt_data_inicio_repasse'] <> "") {
                if($valorGSOMA['status'] == "1" || $valorSellercenter['status'] == "1"){
                    $where .= " AND STR_TO_DATE(data_pagamento_marketplace(O.id),'%d/%m/%Y') >= '" . $input['txt_data_inicio_repasse'] . " 00:00:00'";
                }else{
                    if (array_key_exists("extratonovo", $input)) {
                        $where .= " AND IR.data_repasse_conta_corrente >= '" . $input['txt_data_inicio_repasse'] . " 00:00:00'";
                    }else{
                        $where .= " AND STR_TO_DATE(data_pagamento_conecta(O.id),'%d/%m/%Y') >= '" . $input['txt_data_inicio_repasse'] . " 00:00:00'";
                    }
                }
            }

            if ($input['txt_data_fim_repasse'] <> "") {
                if($valorGSOMA['status'] == "1" || $valorSellercenter['status'] == "1"){
                    $where .= " AND STR_TO_DATE(data_pagamento_marketplace(O.id),'%d/%m/%Y') <= '" . $input['txt_data_fim_repasse'] . " 23:59:59'";
                }else{
                    if (array_key_exists("extratonovo", $input)) {
                        $where .= " AND IR.data_repasse_conta_corrente <= '" . $input['txt_data_fim_repasse'] . " 00:00:00'";
                    }else{
                        $where .= " AND STR_TO_DATE(data_pagamento_conecta(O.id),'%d/%m/%Y') <= '" . $input['txt_data_fim_repasse'] . " 23:59:59'";
                    }
                }
            }

            if ($input['slc_mktplace'] <> "") {
                $where .= " AND SML.apelido = '" . $input['slc_mktplace'] . "'";
            }

            if ($input['slc_status'] <> "") {
                $where .= " AND O.paid_status in (" . $input['slc_status'] . ")";
            }

            if ($input['slc_loja'] <> "") {
                $where .= " AND O.store_id = '" . $input['slc_loja'] . "'";
            }

            if ($input['txt_id_pedido'] <> "") {
                $where .= " AND O.id = " . $input['txt_id_pedido'] . "";
            }
        }

        if ($order === null)
            $order = " ORDER BY SML.descloja, IR.data_transferencia ";

        $select = "
        SELECT  E.* FROM (
                    SELECT
                    	S.name AS seller,
                        SML.descloja AS marketplace,                    	
                    	DATE_FORMAT(IR.data_transferencia,\"%d/%m/%Y\") AS data_transferencia,
                    	round(IFNULL(SUM(IR.valor_parceiro),'0.00'),2) AS valor_parceiro ";

        $count = "
            SELECT COUNT(*) as qtd FROM ( SELECT S.name AS seller,
                                            SML.descloja AS marketplace,                    	
                                            DATE_FORMAT(IR.data_transferencia,\"%d/%m/%Y\") AS data_transferencia,
                                            IFNULL(SUM(IR.valor_parceiro),'0.00') AS valor_parceiro ";

        $sql = "
                FROM orders O
                INNER JOIN stores S ON S . id = O . store_id
                INNER JOIN stores_mkts_linked SML ON SML . apelido = O . origin
                INNER JOIN iugu_repasse IR ON IR.order_id = O.id
                INNER JOIN conciliacao C ON C.id = IR.conciliacao_id
                WHERE IR.data_transferencia IS NOT NULL and  IR.valor_parceiro is not null $where $more
                GROUP BY S.name, SML.descloja, IR.data_transferencia ";

        $sqlLimit = 'LIMIT ' . $post['length'] . ' OFFSET ' . $post['start'];

        $fimSql = ") E $where2";

        //echo '<pre>'.$select.$sql.$order.$sqlLimit.$fimSql;die;

        $query = $this->db->query($select.$sql.$order.$sqlLimit.$fimSql)->result_array();
        $count = $this->db->query($count.$sql.$fimSql)->result_array();

        if(!$count){
            $count[0]['qtd'] = 0;
        }

        return [
            'data' => $query,
            'count' => $count[0]['qtd']
        ];

    }


    public function getPedidosExtratoConciliado($input, $order = null, $post = [], $excel = null, $consolidado = null, $sum_transfer_date = null)
    {

        $frete_100_canal_seller_centers_vtex = $this->model_settings->getStatusbyName('frete_100_canal_seller_centers_vtex');
        $case_frete_100 = '';
        if($frete_100_canal_seller_centers_vtex == 1){
            $case_frete_100 = ' 
                WHEN O.service_charge_freight_value = 100 THEN 
                    CASE WHEN S.freight_seller_type > 0 OR O.freight_seller > 0 THEN
                        ROUND(( O.gross_amount ) - ( ((O.gross_amount - O.total_ship) * ( O.service_charge_rate / 100)) + (O.total_ship * (0)) )  ,2)
                    else
                        ROUND(( O.gross_amount - O.total_ship ) - ( ((O.gross_amount - O.total_ship) * ( O.service_charge_rate / 100)) + (O.total_ship * (0)) )  ,2)
                    end
            ';
        }

        $where = "WHERE 1=1 AND IFNULL(O.exchange_request,0) <> 2 ";
        $where2 = "WHERE 1=1 ";

       // $more = ($this->data['usercomp'] == 1) ? "" :  (($this->data['userstore'] == 0) ?  " AND O.company_id = " . $this->data['usercomp']  : " AND O.store_id = " . $this->data['userstore']);
        $more = '';
        /*if(0 == $this->data['userstore'] && !is_null($this->data['userstore'])){
            $more = " AND O.company_id = {$this->data['usercomp']} ";
        }elseif($this->data['userstore']){
            $more = " AND O.store_id = {$this->data['userstore']} ";
        }*/

        if (isset($this->data['usercomp'])) {
            if(!($this->data['usercomp'] == 1)){
                if(0 == $this->data['userstore'] && !is_null($this->data['userstore'])){
                    if($this->data['usercomp'] <> ""){
                        $more = " AND O.company_id = {$this->data['usercomp']} ";
                    }
                }else{
                    if($this->data['userstore'] <> ""){
                        $more = " AND O.store_id = {$this->data['userstore']} ";
                    }
                }
            }  
        }

        //echo $where.'<br>'.$more;die;

        $valorGSOMA = $this->model_settings->getSettingDatabyNameEmptyArray('gsoma_painel_financeiro');
        $valorNMUNDO = $this->model_settings->getSettingDatabyNameEmptyArray('novomundo_painel_financeiro');
        $valorORTB = $this->model_settings->getSettingDatabyNameEmptyArray('ortobom_painel_financeiro');
        $valorSellercenter = $this->model_settings->getSettingDatabyName('sellercenter');

        $irrfDescontado = $this->model_settings->getSettingDatabyName('irrf_painel_financeiro');
        $irrfValorPercentual = $this->model_settings->getSettingDatabyName('irrf_valor_painel_financeiro');

        $flagNegativoPainelFinanceiro = $this->model_settings->getSettingDatabyName('valores_cancelamento_painel_financeiro');

		if($valorSellercenter['value'] <> "conectala"){
			$valorSellercenter['status'] = 1;
		}else{
			$valorSellercenter['status'] = 0;
		}

        if(!$flagNegativoPainelFinanceiro){
            $flagNegativoPainelFinanceiro['status'] = 0;
        }
        if (!empty($input)) {

            if(array_key_exists('txt_data_inicio',$input)){
                if ($input['txt_data_inicio'] <> "") {
                    $where .= " AND O.date_time >= '" . $input['txt_data_inicio'] . " 00:00:00'";
                }
            }
            
            if(array_key_exists('txt_data_fim',$input)){
                if ($input['txt_data_fim'] <> "") {
                    $where .= " AND O.date_time <= '" . $input['txt_data_fim'] . " 23:59:59'";
                }
            }

            if(array_key_exists('txt_data_inicio_repasse',$input)){
                if ($input['txt_data_inicio_repasse'] <> "") {
                    if($valorGSOMA['status'] == "1" || $valorSellercenter['status'] == "1"){
                        $where .= " AND opd.data_pagamento_marketplace >= '" . $input['txt_data_inicio_repasse'] . " 00:00:00'";
                    }else{
                        if (array_key_exists("extratonovo", $input)) {
                            $where .= " AND IR.data_repasse_conta_corrente >= '" . $input['txt_data_inicio_repasse'] . " 00:00:00'";
                        }else{
                            $where .= " AND opd.data_pagamento_conectala >= '" . $input['txt_data_inicio_repasse'] . " 00:00:00'";
                        }
                    }
                }
            }

            if(array_key_exists('txt_data_fim_repasse',$input)){
                if ($input['txt_data_fim_repasse'] <> "") {
                    if($valorGSOMA['status'] == "1" || $valorSellercenter['status'] == "1"){
                        $where .= " AND opd.data_pagamento_marketplace <= '" . $input['txt_data_fim_repasse'] . " 23:59:59'";
                    }else{
                        if (array_key_exists("extratonovo", $input)) {
                            $where .= " AND IR.data_repasse_conta_corrente <= '" . $input['txt_data_fim_repasse'] . " 00:00:00'";
                        }else{
                            $where .= " AND opd.data_pagamento_conectala <= '" . $input['txt_data_fim_repasse'] . " 23:59:59'";
                        }
                    }
                }
            }

            if(array_key_exists('slc_mktplace',$input)){
                if ($input['slc_mktplace'] <> "") {
                    // $where .= " AND SML.apelido = '" . $input['slc_mktplace'] . "'";
                    $where .= " AND SML.apelido = '" . $this->db->escape_str( $input['slc_mktplace'] ) . "'";
                }
            }

            if(array_key_exists('slc_status',$input)){
                if ($input['slc_status'] <> "") {
                    $where .= " AND O.paid_status in (" . $input['slc_status'] . ")";
                }
            }

            if(array_key_exists('slc_loja',$input)){
                if ($input['slc_loja'] <> "") {
                    $where .= " AND O.store_id = '" . $input['slc_loja'] . "'";
                }
            }

            if(array_key_exists('slc_antecipado',$input)){
                if ($input['slc_antecipado'] == "SIM") {
                    $where .= " AND EXISTS( SELECT * FROM anticipation_transfer atf WHERE atf.order_id = O.id )";
                }
                if ($input['slc_antecipado'] == "NAO") {
                    $where .= " AND NOT EXISTS( SELECT * FROM anticipation_transfer atf WHERE atf.order_id = O.id )";
                }
            }

            if(array_key_exists('txt_id_pedido',$input)){
                if ($input['txt_id_pedido'] <> "") {
                    $where .= " AND O.id = " . $input['txt_id_pedido'] . "";
                }
            }
        }

        $orderTela = 1;

        if(!$excel =="excel"){
            if($post){
            
                if($valorGSOMA['status'] == "1"){
                    $orderTela = isset($post['order'][0]['column']) ? $post['order'][0]['column']+1 : 1;
                }elseif(isset($valorNM['status']) && $valorNMUNDO['status'] == "1"){
                    $orderTela = $post['order'][0]['column']+1;
                }elseif((isset($valorORTB['status']) && $valorORTB['status'] == "1")){
                    $orderTela = $post['order'][0]['column']+1;
                }elseif($valorSellercenter['status'] == "1"){
                    $orderTela = $post['order'][0]['column']+1;
                }else{
                    if (array_key_exists("extratonovo", $input)) {

                        switch ($post['order'][0]['column']) {

                            case 0:
                                $orderTela = " id";
                                break;
                            case 1:
                                $orderTela = " paid_status";
                                break;
                            case 2:
                                $orderTela = " IFNULL( STR_TO_DATE(date_time,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )";
                                break;
                            case 3:
                                $orderTela = " IFNULL( STR_TO_DATE(data_envio,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )";
                                break;
                            case 4:
                                $orderTela = " IFNULL( STR_TO_DATE(data_entrega,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )";
                                break;
                            case 5:
                                $orderTela = " IFNULL( STR_TO_DATE(data_caiu_na_conta,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )";
                                break;
                            case 6:
                                $orderTela = " tratado";
                                break;
                            case 7:
                                $orderTela = " pago";
                                break;
                            case 8:
                                $orderTela = " cast(valor_parceiro as decimal(10,2))";
                                break;
                            default:
                                $orderTela = " id";
                                break;
                        }

                    }else{
                        
                        /*switch ($post['order'][0]['column']) {
                            case 0:
                                $orderTela = " id";
                                break;
                            case 1:
                                $orderTela = " paid_status";
                                break;
                            case 2:
                                $orderTela = " IFNULL( STR_TO_DATE(date_time,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )";
                                break;
                            case 3:
                                $orderTela = " IFNULL( STR_TO_DATE(data_envio,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )";
                                break;
                            case 4:
                                $orderTela = " IFNULL( STR_TO_DATE(data_entrega,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )";
                                break;
                            case 5:
                                $orderTela = " IFNULL( STR_TO_DATE(data_caiu_na_conta,\"%d/%m/%Y\"),'9999-12-31 23:59:59' )";
                                break;
                            case 6:
                                $orderTela = " tratado";
                                break;
                            case 7:
                                $orderTela = " pago";
                                break;
                            case 8:
                                $orderTela = " cast(valor_parceiro as decimal(10,2))";
                                break;
                            default:
                                $orderTela = " id";
                                break;
                        }*/

                        if($post){
                            $orderTela = $post['order'][0]['column']+1;
                        }else{
                            $orderTela = 1;
                        }

                    }
                }
            }else{
                $orderTela = 1;
            }

        }

        $selectConsolidadoLoja = "select    nome_loja,                                            
                                            banco,
                                            agencia,
                                            conta_bancaria,
                                            data_pagamento_mktplace, 
                                            pago, 
                                            round(sum(gross_amount),2) as gross_amount, 
                                            round(sum(total_order),2) as total_order,
                                            round(sum(total_ship),2) as total_ship,
                                            round(sum(expectativaReceb),2) as expectativaReceb,
                                            round(sum(comissao_descontada),2) as comissao_descontada,
                                            '' as observacao,
                                            raz_social,
                                            erp_customer_supplier_code as clifor
                                from (";

        $selectConsolidadoLojaMarca = "select   marketplace,                                                
                                                nome_loja, 
                                                data_pagamento_mktplace, 
                                                pago, 
                                                round(sum(gross_amount),2) as gross_amount, 
                                                round(sum(total_order),2) as total_order,
                                                round(sum(total_ship),2) as total_ship,
                                                round(sum(expectativaReceb),2) as expectativaReceb,
                                                round(sum(comissao_descontada),2) as comissao_descontada,
                                                '' as observacao,
                                                raz_social,
                                                erp_customer_supplier_code as clifor
                                        from (";

        $select = "";
        $select_end = "";

        if ($sum_transfer_date)
        {
            $select .= "select SML.apelido, SML.descloja AS marketplace, '".$sum_transfer_date."' as data_pagamento_mktplace, SUM(expectativaReceb) as expectativaReceb FROM (";
        }

        $select .= "SELECT  E.*
                                from(
                                    SELECT DISTINCT
                                    O.id,
                                    O.paid_status, ";
        if ($sum_transfer_date)
        {
            $select .= "
                                    O.origin,
                                    ";
        }                            
            
        
        $select .= "                DATE_FORMAT(O.date_time,\"%d/%m/%Y\") AS date_time, 
                                    DATE_FORMAT(O.data_entrega,\"%d/%m/%Y\") AS data_entrega,
                                    CASE WHEN IR.data_transferencia IS NULL THEN 'Não' ELSE 'Sim' END AS pago,
                                    IR.data_split,
                                    DATE_FORMAT(IR.data_transferencia,\"%d/%m/%Y\") AS data_transferencia,
                                    DATE_FORMAT(opd.data_pagamento_marketplace, '%d/%m/%Y' ) AS data_pagamento_mktplace,
		                            DATE_FORMAT(opd.data_pagamento_conectala, '%d/%m/%Y' ) AS data_pagamento_conectala, ";

        /*$select = "SELECT  E.*
                                from(
                                    SELECT DISTINCT
                                        O.id,
                                        O.paid_status,
                                        OPD.`data_pagamento_marketplace` AS data_pagamento_mktplace,
                                        OPD.`data_pagamento_conectala` AS data_pagamento_conectala,
                                        DATE_FORMAT(O.date_time,\"%d/%m/%Y\") AS date_time, 
                                        DATE_FORMAT(O.data_entrega,\"%d/%m/%Y\") AS data_entrega,
                                        CASE WHEN IR.data_transferencia IS NULL THEN 'Não' ELSE 'Sim' END AS pago,
                                        IR.data_split,
                                        DATE_FORMAT(IR.data_transferencia,\"%d/%m/%Y\") AS data_transferencia,";*/

        if($valorGSOMA['status'] == "1"){
            //$select .= "ROUND(O.net_amount - ( O.net_amount * (O.service_charge_rate/100) ) - O.total_ship,2) AS expectativaReceb,";
            $select .= " case when paid_status in (90,95,96,97,98,99) then 0 else ROUND( (O.gross_amount - O.total_ship - O.discount) - ( ((O.service_charge_freight_value/100) * O.total_ship) + ((O.service_charge_rate/100) * (O.gross_amount - O.total_ship - O.discount)) ),2) end AS expectativaReceb, ";

        }/*elseif($valorNMUNDO['status'] == "1"){
            $select .= "ROUND(O.gross_amount - ( O.gross_amount * (O.service_charge_rate/100) ) - O.total_ship,2) AS expectativaReceb,";
        }elseif($valorORTB['status'] == "1"){
            $select .= "ROUND(O.gross_amount - ( O.gross_amount * (O.service_charge_rate/100) ) - O.total_ship,2) AS expectativaReceb,";
        }*/
        
        elseif($valorSellercenter['status'] == "1"){

            if( $flagNegativoPainelFinanceiro['status'] == 1 ){
                $select .= "case when O.date_cancel is not null then -1 else 1 end * ";
            }

            $select .= " CASE ".$case_frete_100."
            WHEN S.freight_seller_type > 0 OR O.freight_seller > 0 THEN
                            ROUND(O.gross_amount - ( ((O.gross_amount - O.total_ship)  * (O.service_charge_rate/100)    +   ( O.total_ship  * (O.service_charge_freight_value/100)) ) ),2) 
                        else
                            ROUND(O.gross_amount - O.total_ship - ( ((O.gross_amount - O.total_ship)  * (O.service_charge_rate/100)    +   ( O.total_ship  * (O.service_charge_freight_value/100)) ) ),2) 
                        end ";

            if(isset($irrfDescontado['status']) && $irrfDescontado['status'] == 1){
                $select .= " + ROUND( (".$irrfValorPercentual['value']."/100)*(((O.service_charge_freight_value/100) * O.total_ship) + ((O.service_charge_rate/100) * (O.gross_amount - O.total_ship))),2)";
            }

            $select .= " AS expectativaReceb,";
        }else{
            $select .= "CASE ".$case_frete_100." WHEN UPPER(FR.ship_company) LIKE CONCAT(\"%\",CONCAT(UPPER(S.name),\"%\")) OR S.freight_seller_type > 0 OR O.freight_seller > 0 THEN
                            CASE WHEN O.origin = 'B2W' THEN
                            CASE WHEN date_time BETWEEN '2021-02-01 00:00:00' AND '2021-04-29 23:59:59' THEN
                                    ROUND(O.gross_amount - ( (O.gross_amount - O.total_ship) * (O.service_charge_rate/100) + 5 ),2)
                                ELSE
                                    ROUND(O.gross_amount - ( ((O.gross_amount - O.total_ship)  * (O.service_charge_rate/100)    +   ( O.total_ship  * (O.service_charge_freight_value/100)) ) ),2)
                                END
                            ELSE
                                ROUND(O.gross_amount - ( ((O.gross_amount - O.total_ship)  * (O.service_charge_rate/100)    +   ( O.total_ship  * (O.service_charge_freight_value/100)) ) ),2)
                            END
                        ELSE
                            CASE WHEN O.origin = 'B2W' THEN
                                CASE WHEN date_time BETWEEN '2021-02-01 00:00:00' AND '2021-04-30 23:59:59' THEN
                                    ROUND(O.gross_amount - ( (O.gross_amount - O.total_ship) * (O.service_charge_rate/100) + 5 ) - O.total_ship,2)
                                ELSE
                                    ROUND(O.gross_amount - O.total_ship - ( ((O.gross_amount - O.total_ship)  * (O.service_charge_rate/100)    +   ( O.total_ship  * (O.service_charge_freight_value/100)) ) ),2)
                                END
                            ELSE
                                ROUND(O.gross_amount - O.total_ship - ( ((O.gross_amount - O.total_ship)  * (O.service_charge_rate/100)    +   ( O.total_ship  * (O.service_charge_freight_value/100)) ) ),2)
                            END
                    END ";

            if(isset($irrfDescontado['status']) && $irrfDescontado['status'] == 1){
                $select .= " + ROUND( (".$irrfValorPercentual['value']."/100)*(((O.service_charge_freight_value/100) * O.total_ship) + ((O.service_charge_rate/100) * (O.gross_amount - O.total_ship))),2)";
            }

            if( $flagNegativoPainelFinanceiro['status'] == 1 ){
                $select .= " * case when O.date_cancel is not null then -1 else 1 end ";
            }

            $select .= " AS expectativaReceb,";
        }

        //CM.numero_chamado,
        // DATE_FORMAT(CM.previsao_solucao,\"%d/%m/%Y\") AS previsao_solucao,

        $select .= "
                                    IFNULL(IR.valor_parceiro,'0.00') AS valor_parceiro,
                                    CASE WHEN IR.order_id IS NULL THEN \"Nao\" ELSE \"Sim\" END AS tratado,
                                    '' AS observacao,
                                    '' as numero_chamado,
                                    '' AS previsao_solucao,
                                    O.numero_marketplace AS numero_pedido,                    
                                    C.lote,
                                    SML.descloja AS marketplace,";
        if($valorGSOMA['status'] == "1"){
            $select .= "O.net_amount as gross_amount,";
        }else{
            $select .= "O.gross_amount,";
        }

        if($valorGSOMA['status'] == "1"){
            $select .= "round(O.net_amount - O.total_ship,2) as total_order,";
        }else{
            $select .= "O.total_order,";
        }
        

        $select .= "IR.current_installment,
                           (SELECT max(parcela) FROM orders_payment WHERE orders_payment.order_id = O.id) as total_installments,
                           IR.total_paid,
                                    O.total_ship,
                                    DATE_FORMAT(C.data_criacao,\"%d/%m/%Y\") AS data_recebimento_mktpl,
                                    DATE_FORMAT(IR.data_repasse_conta_corrente,\"%d/%m/%Y\") AS data_caiu_na_conta,
                                    ROUND(O.discount,2) AS discount,";

        if($valorGSOMA['status'] == "1"){
            $select .= "( ((O.service_charge_freight_value/100) * O.total_ship) + ((O.service_charge_rate/100) * (O.gross_amount - O.total_ship - O.discount)) ) AS comission,";
        }else{
            $select .= "
                    CASE WHEN O.service_charge_freight_value = 100 THEN                         
                        ROUND(( ((O.gross_amount - O.total_ship) * ( O.service_charge_rate / 100)) + (O.total_ship * (0)) )  ,2)
                    else
                        O.service_charge
                    END AS comission,";
        }


        $select .= "                
                                    S.name AS store_name,
                                    DATE_FORMAT(O.data_envio,\"%d/%m/%Y\") as data_envio,
                                    S.name AS nome_loja,
                                    S.bank as banco,
                                    S.agency as agencia,
                                    S.account as conta_bancaria,
                                    S.id AS id_loja,
                                    S.freight_seller AS SfreightSeller,
                                    O.service_charge_rate,
                                    S.service_charge_value,
                                    O.service_charge_freight_value,
                                    round(O.service_charge_freight_value,2) AS percentual_frete,
                                    round(O.service_charge_rate,2) AS percentual_produto,";
        if($valorGSOMA['status'] == "1"){
            $select .= "ROUND( ((O.service_charge_freight_value/100) * O.total_ship) + ((O.service_charge_rate/100) * (O.gross_amount - O.total_ship - O.discount)),2) AS calc_comissao_descontada,";
        }else{

            $select .= "
            CASE WHEN O.service_charge_freight_value = 100 THEN                         
                ROUND(( ((O.gross_amount - O.total_ship) * ( O.service_charge_rate / 100)) + (O.total_ship * (0)) )  ,2)
            else
                ROUND( ((O.service_charge_freight_value/100) * O.total_ship) + ((O.service_charge_rate/100) * (O.gross_amount - O.total_ship)),2)
            END ";

            // $select .= "ROUND( ((O.service_charge_freight_value/100) * O.total_ship) + ((O.service_charge_rate/100) * (O.gross_amount - O.total_ship)),2) ";

            if(isset($irrfDescontado['status']) && $irrfDescontado['status'] == 1){
                $select .= " - ROUND( (".$irrfValorPercentual['value']."/100)*(((O.service_charge_freight_value/100) * O.total_ship) + ((O.service_charge_rate/100) * (O.gross_amount - O.total_ship))),2)";
            }

            $select .= "AS calc_comissao_descontada,";

        }


        $select .=                 "ROUND( (".$irrfValorPercentual['value']."/100)*(((O.service_charge_freight_value/100) * O.total_ship) + ((O.service_charge_rate/100) * (O.gross_amount - O.total_ship))),2) AS imposto_renda_comissao_descontada,
                                    ROUND( ((O.service_charge_freight_value/100) * O.total_ship) + ((O.service_charge_rate/100) * (O.gross_amount - O.total_ship)),2) - ROUND( (".$irrfValorPercentual['value']."/100)*(((O.service_charge_freight_value/100) * O.total_ship) + ((O.service_charge_rate/100) * (O.gross_amount - O.total_ship))),2) AS total_comissao_descontada,
                                    S.raz_social,
                                    S.erp_customer_supplier_code,";

        $select .= "                CVO.total_pricetags,
                                    CVO.total_campaigns,
                                    CVO.total_channel,
                                    CVO.total_seller,
                                    CVO.total_promotions,
                                    CVO.total_rebate,
                                    CVO.comission_reduction,
                                    CVO.comission_reduction_marketplace,
                                    CVO.total_rebate_marketplace, ";
                                    
        if($valorGSOMA['status'] == "1"){
            $select .= " ROUND( ( ((O.service_charge_freight_value/100) * O.total_ship) + ((O.service_charge_rate/100) * (O.gross_amount - O.total_ship - O.discount)) ) ,2) AS comissao_descontada, ";
        }else{
            $select .= " ROUND(O.total_order - ROUND(O.gross_amount - ( O.gross_amount * (O.service_charge_rate/100) ) - O.total_ship,2),2) AS comissao_descontada, ";
        }
        
        $count = "
            SELECT
                COUNT(*) AS qtd from ( SELECT DISTINCT O.id
            ";

        $select .= " CASE ".$case_frete_100."
        WHEN S.freight_seller_type > 0 OR O.freight_seller > 0 THEN
                    ROUND(( O.gross_amount ) - ( ((O.gross_amount - O.total_ship) * ( O.service_charge_rate / 100)) + (O.total_ship * ( O.service_charge_freight_value / 100)) )  ,2)
                else ";

		// BUGS-2397
		$select .= " ROUND((O.gross_amount - O.total_ship)  - ((O.gross_amount - O.total_ship) * (O.service_charge_rate / 100))  +  (O.total_ship - (O.total_ship * (O.service_charge_freight_value / 100))) ,2) ";
		//$select .= " ROUND(( O.gross_amount - O.total_ship ) - ( ((O.gross_amount - O.total_ship) * ( O.service_charge_rate / 100)) + (O.total_ship * ( O.service_charge_freight_value / 100)) )  ,2) ";

        $select .= " end AS valor_repasse";

        $sql = " , O.id AS order_id, O.service_charge_freight_value AS taxa_comissao, O.freight_seller AS tipo_frete, 
            CVO.comission_reduction_products, CVO.total_products,            
            ROUND(O.service_charge_rate,2) AS valor_percentual_produto FROM orders O
            INNER JOIN stores S ON S . id = O . store_id
            INNER JOIN stores_mkts_linked SML ON SML . apelido = O . origin
            LEFT JOIN campaign_v2_orders CVO on CVO.order_id = O.id
            LEFT JOIN orders_payment_date opd ON ( opd.order_id = O.id )
            ";

        /*$sql = " FROM orders O
            INNER JOIN stores S ON S . id = O . store_id
            INNER JOIN stores_mkts_linked SML ON SML . apelido = O . origin
            LEFT JOIN orders_payment_date OPD ON OPD . order_id = O . id
            ";*/
            
        if (array_key_exists("extratonovo", $input)) {
            $sql .= " INNER JOIN iugu_repasse IR ON IR.order_id = O.id ";
        }else{
            $sql .= " LEFT JOIN iugu_repasse IR ON IR.order_id = O.id ";
        }
            


        $sql .= " LEFT JOIN conciliacao C ON C.id = IR.conciliacao_id
            LEFT JOIN freights FR ON FR.order_id = O.id
            $where $more";

        $fimSql = ") E $where2";
//        $fimSql = " GROUP by O.numero_marketplace) E $where2";

        $groupConsolidadoLoja = ") A where data_pagamento_mktplace is not null group by nome_loja, banco, agencia, conta_bancaria, data_pagamento_mktplace, pago, raz_social, erp_customer_supplier_code";

        $groupConsolidadoLojaMarca = ") A where data_pagamento_mktplace is not null group by marketplace, nome_loja, data_pagamento_mktplace, pago, raz_social, erp_customer_supplier_code";

        if ($sum_transfer_date)
        {
            $select_end .= ") AS expec
                        INNER JOIN stores_mkts_linked SML ON SML.apelido = expec.origin
                        WHERE 
                            paid_status = 6
                        AND
                            data_pagamento_mktplace = '".$sum_transfer_date."'
                            ";
        }


        if($excel =="excel"){
            
            $sqlLimit = '';
            if (isset($post['length']) && isset($post['start'])){
                $sqlLimit = ' LIMIT ' . $post['length'] . ' OFFSET ' . $post['start'];
            }

            if($consolidado == null){

                $query = $this->db->query($select.$sql.$fimSql.$sqlLimit);
                /*
                ['extract_api_data_count'] ::
                Esse dado é adicionado quando a consulta vem da api de extrato. Se existir, é retornado
                tambem o total de dados (ignorando a argumento "limit" e "offset" da consulta)
                */

                if (isset($input['extract_api_data_count']) && $input['extract_api_data_count']) {
                    $count_total = $this->db->query($select . $sql . $fimSql)->num_rows();
                    return (false !== $query) ? ['count' => $count_total, 'data' => $query->result_array()] : false;
                } else {
                    return (false !== $query) ? $query->result_array() : false;
                }

            }else{
                if($consolidado == "loja"){
                    $query = $this->db->query($selectConsolidadoLoja.$select.$sql.$fimSql.$groupConsolidadoLoja.$sqlLimit);
                    /*
                    ['extract_api_data_count'] ::
                    Esse dado é adicionado quando a consulta vem da api de extrato. Se existir, é retornado
                    tambem o total de dados (ignorando a argumento "limit" e "offset" da consulta)
                    */
                    if (isset($input['extract_api_data_count']) && $input['extract_api_data_count']) {
                        $count_total = $this->db->query($selectConsolidadoLoja . $select . $sql . $fimSql . $groupConsolidadoLoja)->num_rows();
                        return (false !== $query) ? ['count' => $count_total, 'data' => $query->result_array()] : false;
                    } else {
                        return (false !== $query) ? $query->result_array() : false;
                    }

                }elseif($consolidado == "lojamarca"){
                    $query = $this->db->query($selectConsolidadoLojaMarca.$select.$sql.$fimSql.$groupConsolidadoLojaMarca.$sqlLimit);
                    /*
                    ['extract_api_data_count'] ::
                    Esse dado é adicionado quando a consulta vem da api de extrato. Se existir, é retornado
                    tambem o total de dados (ignorando a argumento "limit" e "offset" da consulta)
                    */
                    if (isset($input['extract_api_data_count']) && $input['extract_api_data_count']) {
                        $count_total = $this->db->query($selectConsolidadoLojaMarca . $select . $sql . $fimSql . $groupConsolidadoLojaMarca)->num_rows();
                        return (false !== $query) ? ['count' => $count_total, 'data' => $query->result_array()] : false;
                    } else {
                        return (false !== $query) ? $query->result_array() : false;
                    }
                }
            }
            
        }else{

            $sqlLimit = '';
            if (isset($post['length']) && isset($post['start'])){
                $sqlLimit = ' LIMIT ' . $post['length'] . ' OFFSET ' . $post['start'];
            }
            if ($order === null) {
                if (isset($post['order']) && $post['order']) {
                    $column = $post['order'][0]['column']+1;
    
                    $direction = $post['order'][0]['dir'];
                    $orderBy = " ORDER BY $orderTela $direction ";
                    $order = $orderBy;
                }
            }
           // echo '<pre>'.$count.$sql.$fimSql;die;
            $rows = $this->db->query($select.$sql.$sqlLimit.$fimSql.$order.$select_end)->result_array();
            $count = $this->db->query($count.$sql.$fimSql)->result_array();

            if ($sum_transfer_date)
            {
                return $rows;
            }

            return [
                'data' => $rows,
                'count' => $count[0]['qtd']
                ];

        }

    }

    public function buscachamadomarketplace($numero_marketplace){

        $sql = "select  CMO.numero_marketplace,
                        CM.numero_chamado,
                        DATE_FORMAT(CM.previsao_solucao,\"%d/%m/%Y\") AS previsao_solucao
                from chamado_marketplace_orders CMO
                INNER JOIN `chamado_marketplace` CM ON CMO.chamado_marketplace_id = CM.id AND CM.billet_status_id = 18 and  CMO.ativo = 1
                where  CMO.order_id = $numero_marketplace";

        return $this->db->query($sql )->result_array();

    }

    public function getPrevisaoExtratoConciliado($order = null){

        $where = "WHERE MONTH(STR_TO_DATE(data_pagamento_conecta(O.id),'%d/%m/%Y')) IN ( MONTH(CURRENT_TIMESTAMP),MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL +1 MONTH)) )  ";

        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND O.company_id = " . $this->data['usercomp'] : " AND O.store_id = " . $this->data['userstore']);

        if ($order <> null) {
            $order = " ORDER BY marketplace, data_pagamento_conectala_convert";
        } else {
            $order = " ORDER BY marketplace, data_pagamento_conectala_convert";
        }

        $sql = "SELECT apelido, 
                        marketplace, 
                        data_pagamento_conectala, 
                        SUM(expectativaReceb)  as expectativaReceb 
                FROM (
                        SELECT
                                data_pagamento_conecta(O.id) AS data_pagamento_conectala,
                                data_pagamento_marketplace(O.id) AS data_pagamento_mktplace,
                                ROUND(O.gross_amount - ( O.gross_amount * (O.service_charge_rate/100) ) - O.total_ship,2) AS expectativaReceb,
                                SML.descloja AS marketplace,
                                STR_TO_DATE(data_pagamento_conecta(O.id),'%d/%m/%Y') AS data_pagamento_conectala_convert,
                                SML.apelido
                        FROM orders O
                        INNER JOIN stores S ON S.id = O.store_id
                        INNER JOIN stores_mkts_linked SML ON SML.apelido = O.origin
                        $where $more
                ) PREVI
                GROUP BY apelido, marketplace, data_pagamento_conectala
                $order";
            
            $query = $this->db->query($sql);
            
            return (false !== $query) ? $query->result_array() : false;
                    
	}

    public function getPrevisaoExtratoConciliadogsoma($order=null){

        $valorGSOMA = $this->model_settings->getSettingDatabyNameEmptyArray('gsoma_painel_financeiro');
        $valorNMUNDO = $this->model_settings->getSettingDatabyNameEmptyArray('novomundo_painel_financeiro');
        $valorORTB = $this->model_settings->getSettingDatabyNameEmptyArray('ortobom_painel_financeiro');
        $valorSellercenter = $this->model_settings->getSettingDatabyNameEmptyArray('sellercenter');

        $frete_100_canal_seller_centers_vtex = $this->model_settings->getStatusbyName('frete_100_canal_seller_centers_vtex');
        $case_frete_100 = '';
        if($frete_100_canal_seller_centers_vtex == 1){
            $case_frete_100 = ' 
                WHEN O.service_charge_freight_value = 100 THEN 
                    CASE  WHEN S.freight_seller_type > 0 OR O.freight_seller > 0 THEN
                        ROUND(( O.gross_amount ) - ( ((O.gross_amount - O.total_ship) * ( O.service_charge_rate / 100)) + (O.total_ship * (0)) )  ,2)
                    else
                        ROUND(( O.gross_amount - O.total_ship ) - ( ((O.gross_amount - O.total_ship) * ( O.service_charge_rate / 100)) + (O.total_ship * (0)) )  ,2)
                    end
            ';
        }


        $more = ($this->data['usercomp'] == 1) ? "": (($this->data['userstore'] == 0) ? " AND O.company_id = ".$this->data['usercomp'] : " AND O.store_id = ".$this->data['userstore']);

        if($order <> null){
            $order = " ORDER BY marketplace, data_pagamento_conectala_convert";
        }else{
            $order = " ORDER BY marketplace, data_pagamento_conectala_convert";
        }

        if($valorGSOMA['status'] == "1"){
           // $where = "WHERE MONTH(STR_TO_DATE(data_pagamento_marketplace(O.id),'%d/%m/%Y')) IN ( MONTH(CURRENT_TIMESTAMP),MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL +1 MONTH)) )  ";
            $where = "WHERE MONTH(OPD.data_pagamento_marketplace) IN ( MONTH(CURRENT_TIMESTAMP),MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL +1 MONTH)) )  ";
        }else{
           // $where = "WHERE MONTH(STR_TO_DATE(data_pagamento_conecta(O.id),'%d/%m/%Y')) IN ( MONTH(CURRENT_TIMESTAMP),MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL +1 MONTH)) )  ";
            $where = "WHERE MONTH(OPD.data_pagamento_conectala) IN ( MONTH(CURRENT_TIMESTAMP),MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL +1 MONTH)) )  ";
        }

        if($valorSellercenter['value'] <> "conectala"){
            $valorSellercenter['status'] = 1;
        }else{
            $valorSellercenter['status'] = 0;
        }

        $sql = "SELECT apelido,
                        marketplace,
                        data_pagamento_mktplace,
                        SUM(expectativaReceb)  as expectativaReceb
                FROM (
                        SELECT
                                
                                /* data_pagamento_marketplace(O.id) AS data_pagamento_mktplace, */
                                DATE_FORMAT(OPD.data_pagamento_marketplace,'%d/%m/%Y') AS data_pagamento_mktplace,";

        if($valorGSOMA['status'] == "1"){

            //$sql .= "ROUND(O.net_amount - ( O.net_amount * (O.service_charge_rate/100) ) - O.total_ship,2) AS expectativaReceb,";

            $sql .= "case when paid_status in (90,95,96,97,98,99) then 0 else ROUND( (O.gross_amount - O.total_ship - O.discount) - ( ((O.service_charge_freight_value/100) * O.total_ship) + ((O.service_charge_rate/100) * (O.gross_amount - O.total_ship - O.discount)) ),2) end AS expectativaReceb,";

        }/*elseif($valorNMUNDO['status'] == "1"){
            $sql .= "ROUND(O.gross_amount - ( O.gross_amount * (O.service_charge_rate/100) ) - O.total_ship,2) AS expectativaReceb,";
        }elseif($valorORTB['status'] == "1"){
            $sql .= "ROUND(O.gross_amount - ( O.gross_amount * (O.service_charge_rate/100) ) - O.total_ship,2) AS expectativaReceb,";
        }*/elseif($valorSellercenter['status'] == "1"){

            $sql .= " CASE ".$case_frete_100." 
                        WHEN S.freight_seller_type > 0 OR O.freight_seller > 0 THEN
                        ROUND(O.gross_amount - ( ((O.gross_amount - O.total_ship)  * (O.service_charge_rate/100)    +   ( O.total_ship  * (O.service_charge_freight_value/100)) ) ),2) 
                        else
                        ROUND(O.gross_amount - O.total_ship - ( ((O.gross_amount - O.total_ship)  * (O.service_charge_rate/100)    +   ( O.total_ship  * (O.service_charge_freight_value/100)) ) ),2) 
                        end 
                        AS expectativaReceb,";


            // $sql .= "ROUND(O.gross_amount - ( O.gross_amount * (O.service_charge_rate/100) ) - O.total_ship,2) AS expectativaReceb,";
        }else{
            $sql .= "ROUND(O.net_amount - ( O.net_amount * (O.service_charge_rate/100) ) - O.total_ship,2) AS expectativaReceb,";
        }

        $sql .= "               SML.descloja AS marketplace,
                                /* STR_TO_DATE(data_pagamento_conecta(O.id),'%d/%m/%Y') AS data_pagamento_conectala_convert, */
                                OPD.data_pagamento_conectala AS data_pagamento_conectala_convert,
                                SML.apelido
                        FROM orders O
                        INNER JOIN stores S ON S.id = O.store_id
                        INNER JOIN stores_mkts_linked SML ON SML.apelido = O.origin
                        INNER JOIN  orders_payment_date OPD on OPD.order_id = O.id
                        $where $more
                ) PREVI
                GROUP BY apelido, marketplace, data_pagamento_mktplace
                $order";

        $query = $this->db->query($sql);

        return (false !== $query) ? $query->result_array() : false;

    }
	
	public function getDatasPagamentoPrevisaoExtratoConciliado($order=null){

            if($order <> null){
                $order = " ORDER BY data_pagamento_conecta_tratado, marktplace ";
            }else{
                $order = " ORDER BY marktplace, data_pagamento_conecta_tratado ";
            }
            $sql = "SELECT 
                        CICLO2.marktplace,
                        CICLO2.data_usada as data_corte,
                        DATE_FORMAT(CICLO2.data_inicio, '%d/%m/%Y') AS data_inicio,
                        DATE_FORMAT(CICLO2.data_fim, '%d/%m/%Y') AS data_fim,
                        CICLO2.data_pagamento_mktplace,
                        CICLO2.data_pagamento_conecta
                    FROM (
                        SELECT 	SML.descloja AS marktplace,
                                CICLO.data_inicio,
                                CICLO.data_usada,
                                CICLO.data_fim,
                                CASE WHEN DATEDIFF(DATE(CONCAT( YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)) ,CONCAT('/', CONCAT( MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)) ,CONCAT('/',CONCAT(CICLO.data_pagamento,CONCAT(' 00:00:00'))))))), CICLO.data_fim) < 0 THEN
                                CASE WHEN DATEDIFF(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento,CONCAT(' 00:00:00'))))))), CICLO.data_fim) > 21 THEN
                                CASE WHEN DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento,CONCAT(' 00:00:00'))))))), '%d/%m/%Y') IS NULL THEN
                                CASE WHEN DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento-1,CONCAT(' 00:00:00'))))))), '%d/%m/%Y') IS NULL THEN
                                CASE WHEN DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento-2,CONCAT(' 00:00:00'))))))), '%d/%m/%Y') IS NULL THEN
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento-3,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                ELSE
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento-2,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                END
                                ELSE
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento-1,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                END
                                ELSE
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                END
                                ELSE
                                CASE WHEN DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento,CONCAT(' 00:00:00'))))))), '%d/%m/%Y') IS NULL THEN
                                CASE WHEN DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento-1,CONCAT(' 00:00:00'))))))), '%d/%m/%Y') IS NULL THEN
                                CASE WHEN DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento-2,CONCAT(' 00:00:00'))))))), '%d/%m/%Y') IS NULL THEN
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento-3,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                ELSE
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento-2,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                END
                                ELSE
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento-1,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                END
                                ELSE
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                END
                                END
                                ELSE
                                CASE WHEN DATEDIFF(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento,CONCAT(' 00:00:00'))))))), CICLO.data_fim) > 21 THEN
                                CASE WHEN DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento,CONCAT(' 00:00:00'))))))), '%d/%m/%Y') IS NULL THEN
                                CASE WHEN DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento-1,CONCAT(' 00:00:00'))))))), '%d/%m/%Y') IS NULL THEN
                                CASE WHEN DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento-2,CONCAT(' 00:00:00'))))))), '%d/%m/%Y') IS NULL THEN
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento-3,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                ELSE
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento-2,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                END
                                ELSE
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento-1,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                END
                                ELSE
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                END
                                ELSE
                                CASE WHEN DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento,CONCAT(' 00:00:00'))))))), '%d/%m/%Y') IS NULL THEN
                                CASE WHEN DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento-1,CONCAT(' 00:00:00'))))))), '%d/%m/%Y') IS NULL THEN
                                CASE WHEN DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento-2,CONCAT(' 00:00:00'))))))), '%d/%m/%Y') IS NULL THEN
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento-3,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                ELSE
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento-2,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                END
                                ELSE
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento-1,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                END
                                ELSE
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                END
                                END 
                                END AS data_pagamento_mktplace,
                                CASE WHEN DATEDIFF(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento,CONCAT(' 00:00:00'))))))), CICLO.data_fim) < 0 THEN
                                CASE WHEN DATEDIFF(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento,CONCAT(' 00:00:00'))))))), CICLO.data_fim) > 21 THEN
                                CASE WHEN DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta,CONCAT(' 00:00:00'))))))), '%d/%m/%Y') IS NULL THEN
                                CASE WHEN DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta-1,CONCAT(' 00:00:00'))))))), '%d/%m/%Y') IS NULL THEN
                                CASE WHEN DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta-2,CONCAT(' 00:00:00'))))))), '%d/%m/%Y') IS NULL THEN
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta-3,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                ELSE
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta-2,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                END
                                ELSE
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta-1,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                END
                                ELSE
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                END
                                ELSE
                                CASE WHEN DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta,CONCAT(' 00:00:00'))))))), '%d/%m/%Y') IS NULL THEN
                                CASE WHEN DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta-1,CONCAT(' 00:00:00'))))))), '%d/%m/%Y') IS NULL THEN
                                CASE WHEN DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta-2,CONCAT(' 00:00:00'))))))), '%d/%m/%Y') IS NULL THEN
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta-3,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                ELSE
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta-2,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                END
                                ELSE
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta-1,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                END
                                ELSE
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                END
                                END
                                ELSE
                                CASE WHEN DATEDIFF(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento,CONCAT(' 00:00:00'))))))), CICLO.data_fim) > 21 THEN
                                CASE WHEN DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta,CONCAT(' 00:00:00'))))))), '%d/%m/%Y') IS NULL THEN
                                CASE WHEN DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta-1,CONCAT(' 00:00:00'))))))), '%d/%m/%Y') IS NULL THEN
                                CASE WHEN DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta-2,CONCAT(' 00:00:00'))))))), '%d/%m/%Y') IS NULL THEN
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta-3,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                ELSE
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta-2,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                END
                                ELSE
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta-1,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                END
                                ELSE
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                END
                                ELSE
                                CASE WHEN DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta,CONCAT(' 00:00:00'))))))), '%d/%m/%Y') IS NULL THEN
                                CASE WHEN DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta-1,CONCAT(' 00:00:00'))))))), '%d/%m/%Y') IS NULL THEN
                                CASE WHEN DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta-2,CONCAT(' 00:00:00'))))))), '%d/%m/%Y') IS NULL THEN
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta-3,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                ELSE
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta-2,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                END
                                ELSE
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta-1,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                END
                                ELSE
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                END
                                END 
                                END AS data_pagamento_conecta,
                                STR_TO_DATE(CASE WHEN DATEDIFF(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento,CONCAT(' 00:00:00'))))))), CICLO.data_fim) < 0 THEN
                                CASE WHEN DATEDIFF(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento,CONCAT(' 00:00:00'))))))), CICLO.data_fim) > 21 THEN
                                CASE WHEN DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta,CONCAT(' 00:00:00'))))))), '%d/%m/%Y') IS NULL THEN
                                CASE WHEN DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta-1,CONCAT(' 00:00:00'))))))), '%d/%m/%Y') IS NULL THEN
                                CASE WHEN DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta-2,CONCAT(' 00:00:00'))))))), '%d/%m/%Y') IS NULL THEN
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta-3,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                ELSE
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta-2,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                END
                                ELSE
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta-1,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                END
                                ELSE
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                END
                                ELSE
                                CASE WHEN DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta,CONCAT(' 00:00:00'))))))), '%d/%m/%Y') IS NULL THEN
                                CASE WHEN DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta-1,CONCAT(' 00:00:00'))))))), '%d/%m/%Y') IS NULL THEN
                                CASE WHEN DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta-2,CONCAT(' 00:00:00'))))))), '%d/%m/%Y') IS NULL THEN
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta-3,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                ELSE
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta-2,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                END
                                ELSE
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta-1,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                END
                                ELSE
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 2 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                END
                                END
                                ELSE
                                CASE WHEN DATEDIFF(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento,CONCAT(' 00:00:00'))))))), CICLO.data_fim) > 21 THEN
                                CASE WHEN DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta,CONCAT(' 00:00:00'))))))), '%d/%m/%Y') IS NULL THEN
                                CASE WHEN DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta-1,CONCAT(' 00:00:00'))))))), '%d/%m/%Y') IS NULL THEN
                                CASE WHEN DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta-2,CONCAT(' 00:00:00'))))))), '%d/%m/%Y') IS NULL THEN
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta-3,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                ELSE
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta-2,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                END
                                ELSE
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta-1,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                END
                                ELSE
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 0 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                END
                                ELSE
                                CASE WHEN DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta,CONCAT(' 00:00:00'))))))), '%d/%m/%Y') IS NULL THEN
                                CASE WHEN DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta-1,CONCAT(' 00:00:00'))))))), '%d/%m/%Y') IS NULL THEN
                                CASE WHEN DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta-2,CONCAT(' 00:00:00'))))))), '%d/%m/%Y') IS NULL THEN
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta-3,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                ELSE
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta-2,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                END
                                ELSE
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta-1,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                END
                                ELSE
                                DATE_FORMAT(DATE(CONCAT(YEAR(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CICLO.data_fim, INTERVAL 1 MONTH)),CONCAT('/',CONCAT(CICLO.data_pagamento_conecta,CONCAT(' 00:00:00'))))))), '%d/%m/%Y')
                                END
                                END 
                                END,'%d/%m/%Y') AS data_pagamento_conecta_tratado
                                FROM (
                                SELECT DISTINCT CASE WHEN PMC.data_inicio < PMC.data_fim THEN
                                DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-13) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-13) MONTH)),CONCAT('/',CONCAT(PMC.data_inicio,CONCAT(' 00:00:00')))))))
                                ELSE
                                DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ((MONTH(CURRENT_TIMESTAMP)-13)+-1) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ((MONTH(CURRENT_TIMESTAMP)-13)+-1) MONTH)),CONCAT('/',CONCAT(PMC.data_inicio,CONCAT(' 00:00:00'))))))) 
                                END AS data_inicio,
                                CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-13) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-13) MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00'))))))) IS NULL THEN
                                CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-13) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-13) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-1,CONCAT(' 00:00:00'))))))) IS NULL THEN
                                CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-13) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-13) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-2,CONCAT(' 00:00:00'))))))) IS NULL THEN
                                DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-13) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-13) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-3,CONCAT(' 00:00:00')))))))
                                ELSE
                                DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-13) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-13) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-2,CONCAT(' 00:00:00')))))))
                                END
                                ELSE
                                DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-13) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-13) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-1,CONCAT(' 00:00:00')))))))
                                END
                                ELSE
                                DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-13) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-13) MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00')))))))
                                END AS data_fim,
                                PMC.data_pagamento_conecta,
                                PMC.data_pagamento, PMC.data_usada,
                                SML.apelido
                                FROM `param_mkt_ciclo` PMC
                                INNER JOIN stores_mkts_linked SML ON SML.id_mkt = PMC.integ_id
                                WHERE PMC.ativo = 1
                                union 
                            SELECT DISTINCT CASE WHEN PMC.data_inicio < PMC.data_fim THEN
                            DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-11) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-11) MONTH)),CONCAT('/',CONCAT(PMC.data_inicio,CONCAT(' 00:00:00')))))))
                            ELSE
                            DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ((MONTH(CURRENT_TIMESTAMP)-11)+-1) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ((MONTH(CURRENT_TIMESTAMP)-11)+-1) MONTH)),CONCAT('/',CONCAT(PMC.data_inicio,CONCAT(' 00:00:00'))))))) 
                            END AS data_inicio,
                            CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-11) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-11) MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00'))))))) IS NULL THEN
                            CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-11) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-11) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-1,CONCAT(' 00:00:00'))))))) IS NULL THEN
                            CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-11) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-11) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-2,CONCAT(' 00:00:00'))))))) IS NULL THEN
                            DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-11) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-11) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-3,CONCAT(' 00:00:00')))))))
                            ELSE
                            DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-11) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-11) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-2,CONCAT(' 00:00:00')))))))
                            END
                            ELSE
                            DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-11) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-11) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-1,CONCAT(' 00:00:00')))))))
                            END
                            ELSE
                            DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-11) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-11) MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00')))))))
                            END AS data_fim,
                            PMC.data_pagamento_conecta,
                            PMC.data_pagamento, PMC.data_usada,
                            SML.apelido
                            FROM `param_mkt_ciclo` PMC
                            INNER JOIN stores_mkts_linked SML ON SML.id_mkt = PMC.integ_id
                            WHERE PMC.ativo = 1
                           UNION
                            SELECT DISTINCT CASE WHEN PMC.data_inicio < PMC.data_fim THEN
                            DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-12) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-12) MONTH)),CONCAT('/',CONCAT(PMC.data_inicio,CONCAT(' 00:00:00')))))))
                            ELSE
                            DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ((MONTH(CURRENT_TIMESTAMP)-12)+-1) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ((MONTH(CURRENT_TIMESTAMP)-12)+-1) MONTH)),CONCAT('/',CONCAT(PMC.data_inicio,CONCAT(' 00:00:00'))))))) 
                            END AS data_inicio,
                            CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-12) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-12) MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00'))))))) IS NULL THEN
                            CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-12) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-12) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-1,CONCAT(' 00:00:00'))))))) IS NULL THEN
                            CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-12) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-12) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-2,CONCAT(' 00:00:00'))))))) IS NULL THEN
                            DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-12) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-12) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-3,CONCAT(' 00:00:00')))))))
                            ELSE
                            DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-12) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-12) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-2,CONCAT(' 00:00:00')))))))
                            END
                            ELSE
                            DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-12) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-12) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-1,CONCAT(' 00:00:00')))))))
                            END
                            ELSE
                            DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-12) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-12) MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00')))))))
                            END AS data_fim,
                            PMC.data_pagamento_conecta,
                            PMC.data_pagamento, PMC.data_usada,
                            SML.apelido
                            FROM `param_mkt_ciclo` PMC
                            INNER JOIN stores_mkts_linked SML ON SML.id_mkt = PMC.integ_id
                            WHERE PMC.ativo = 1
                            UNION
                            SELECT DISTINCT CASE WHEN PMC.data_inicio < PMC.data_fim THEN
                            DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-7) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-7) MONTH)),CONCAT('/',CONCAT(PMC.data_inicio,CONCAT(' 00:00:00')))))))
                            ELSE
                            DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ((MONTH(CURRENT_TIMESTAMP)-7)+-1) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ((MONTH(CURRENT_TIMESTAMP)-7)+-1) MONTH)),CONCAT('/',CONCAT(PMC.data_inicio,CONCAT(' 00:00:00'))))))) 
                            END AS data_inicio,
                            CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-7) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-7) MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00'))))))) IS NULL THEN
                            CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-7) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-7) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-1,CONCAT(' 00:00:00'))))))) IS NULL THEN
                            CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-7) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-7) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-2,CONCAT(' 00:00:00'))))))) IS NULL THEN
                            DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-7) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-7) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-3,CONCAT(' 00:00:00')))))))
                            ELSE
                            DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-7) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-7) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-2,CONCAT(' 00:00:00')))))))
                            END
                            ELSE
                            DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-7) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-7) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-1,CONCAT(' 00:00:00')))))))
                            END
                            ELSE
                            DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-7) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-7) MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00')))))))
                            END AS data_fim,
                            PMC.data_pagamento_conecta,
                            PMC.data_pagamento, PMC.data_usada,
                            SML.apelido
                            FROM `param_mkt_ciclo` PMC
                            INNER JOIN stores_mkts_linked SML ON SML.id_mkt = PMC.integ_id
                            WHERE PMC.ativo = 1
                            UNION
                            SELECT DISTINCT CASE WHEN PMC.data_inicio < PMC.data_fim THEN
                            DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-8) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-8) MONTH)),CONCAT('/',CONCAT(PMC.data_inicio,CONCAT(' 00:00:00')))))))
                            ELSE
                            DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ((MONTH(CURRENT_TIMESTAMP)-8)+-1) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ((MONTH(CURRENT_TIMESTAMP)-8)+-1) MONTH)),CONCAT('/',CONCAT(PMC.data_inicio,CONCAT(' 00:00:00'))))))) 
                            END AS data_inicio,
                            CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-8) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-8) MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00'))))))) IS NULL THEN
                            CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-8) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-8) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-1,CONCAT(' 00:00:00'))))))) IS NULL THEN
                            CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-8) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-8) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-2,CONCAT(' 00:00:00'))))))) IS NULL THEN
                            DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-8) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-8) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-3,CONCAT(' 00:00:00')))))))
                            ELSE
                            DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-8) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-8) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-2,CONCAT(' 00:00:00')))))))
                            END
                            ELSE
                            DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-8) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-8) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-1,CONCAT(' 00:00:00')))))))
                            END
                            ELSE
                            DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-8) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-8) MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00')))))))
                            END AS data_fim,
                            PMC.data_pagamento_conecta,
                            PMC.data_pagamento, PMC.data_usada,
                            SML.apelido
                            FROM `param_mkt_ciclo` PMC
                            INNER JOIN stores_mkts_linked SML ON SML.id_mkt = PMC.integ_id
                            WHERE PMC.ativo = 1
                            UNION
                            SELECT DISTINCT CASE WHEN PMC.data_inicio < PMC.data_fim THEN
                            DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-9) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-9) MONTH)),CONCAT('/',CONCAT(PMC.data_inicio,CONCAT(' 00:00:00')))))))
                            ELSE
                            DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ((MONTH(CURRENT_TIMESTAMP)-9)+-1) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ((MONTH(CURRENT_TIMESTAMP)-9)+-1) MONTH)),CONCAT('/',CONCAT(PMC.data_inicio,CONCAT(' 00:00:00'))))))) 
                            END AS data_inicio,
                            CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-9) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-9) MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00'))))))) IS NULL THEN
                            CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-9) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-9) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-1,CONCAT(' 00:00:00'))))))) IS NULL THEN
                            CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-9) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-9) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-2,CONCAT(' 00:00:00'))))))) IS NULL THEN
                            DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-9) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-9) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-3,CONCAT(' 00:00:00')))))))
                            ELSE
                            DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-9) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-9) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-2,CONCAT(' 00:00:00')))))))
                            END
                            ELSE
                            DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-9) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-9) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-1,CONCAT(' 00:00:00')))))))
                            END
                            ELSE
                            DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-9) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-9) MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00')))))))
                            END AS data_fim,
                            PMC.data_pagamento_conecta,
                            PMC.data_pagamento, PMC.data_usada,
                            SML.apelido
                            FROM `param_mkt_ciclo` PMC
                            INNER JOIN stores_mkts_linked SML ON SML.id_mkt = PMC.integ_id
                            WHERE PMC.ativo = 1
                            UNION
                            SELECT DISTINCT CASE WHEN PMC.data_inicio < PMC.data_fim THEN
                            DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-10) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-10) MONTH)),CONCAT('/',CONCAT(PMC.data_inicio,CONCAT(' 00:00:00')))))))
                            ELSE
                            DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ((MONTH(CURRENT_TIMESTAMP)-10)+-1) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ((MONTH(CURRENT_TIMESTAMP)-10)+-1) MONTH)),CONCAT('/',CONCAT(PMC.data_inicio,CONCAT(' 00:00:00'))))))) 
                            END AS data_inicio,
                            CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-10) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-10) MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00'))))))) IS NULL THEN
                            CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-10) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-10) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-1,CONCAT(' 00:00:00'))))))) IS NULL THEN
                            CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-10) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-10) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-2,CONCAT(' 00:00:00'))))))) IS NULL THEN
                            DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-10) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-10) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-3,CONCAT(' 00:00:00')))))))
                            ELSE
                            DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-10) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-10) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-2,CONCAT(' 00:00:00')))))))
                            END
                            ELSE
                            DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-10) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-10) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-1,CONCAT(' 00:00:00')))))))
                            END
                            ELSE
                            DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-10) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-10) MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00')))))))
                            END AS data_fim,
                            PMC.data_pagamento_conecta,
                            PMC.data_pagamento, PMC.data_usada,
                            SML.apelido
                            FROM `param_mkt_ciclo` PMC
                            INNER JOIN stores_mkts_linked SML ON SML.id_mkt = PMC.integ_id
                            WHERE PMC.ativo = 1
                            UNION
                SELECT DISTINCT CASE WHEN PMC.data_inicio < PMC.data_fim THEN
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-6) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-6) MONTH)),CONCAT('/',CONCAT(PMC.data_inicio,CONCAT(' 00:00:00')))))))
                        ELSE
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ((MONTH(CURRENT_TIMESTAMP)-6)+-1) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ((MONTH(CURRENT_TIMESTAMP)-6)+-1) MONTH)),CONCAT('/',CONCAT(PMC.data_inicio,CONCAT(' 00:00:00'))))))) 
                        END AS data_inicio,
                        CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-6) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-6) MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00'))))))) IS NULL THEN
                        CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-6) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-6) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-1,CONCAT(' 00:00:00'))))))) IS NULL THEN
                        CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-6) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-6) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-2,CONCAT(' 00:00:00'))))))) IS NULL THEN
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-6) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-6) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-3,CONCAT(' 00:00:00')))))))
                        ELSE
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-6) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-6) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-2,CONCAT(' 00:00:00')))))))
                        END
                        ELSE
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-6) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-6) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-1,CONCAT(' 00:00:00')))))))
                        END
                        ELSE
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-6) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-6) MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00')))))))
                        END AS data_fim,
                        PMC.data_pagamento_conecta,
                        PMC.data_pagamento, PMC.data_usada,
                        SML.apelido
                        FROM `param_mkt_ciclo` PMC
                        INNER JOIN stores_mkts_linked SML ON SML.id_mkt = PMC.integ_id
                        WHERE PMC.ativo = 1
                UNION
                SELECT DISTINCT CASE WHEN PMC.data_inicio < PMC.data_fim THEN
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-5) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-5) MONTH)),CONCAT('/',CONCAT(PMC.data_inicio,CONCAT(' 00:00:00')))))))
                        ELSE
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ((MONTH(CURRENT_TIMESTAMP)-5)+-1) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ((MONTH(CURRENT_TIMESTAMP)-5)+-1) MONTH)),CONCAT('/',CONCAT(PMC.data_inicio,CONCAT(' 00:00:00'))))))) 
                        END AS data_inicio,
                        CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-5) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-5) MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00'))))))) IS NULL THEN
                        CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-5) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-5) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-1,CONCAT(' 00:00:00'))))))) IS NULL THEN
                        CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-5) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-5) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-2,CONCAT(' 00:00:00'))))))) IS NULL THEN
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-5) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-5) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-3,CONCAT(' 00:00:00')))))))
                        ELSE
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-5) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-5) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-2,CONCAT(' 00:00:00')))))))
                        END
                        ELSE
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-5) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-5) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-1,CONCAT(' 00:00:00')))))))
                        END
                        ELSE
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-5) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-5) MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00')))))))
                        END AS data_fim,
                        PMC.data_pagamento_conecta,
                        PMC.data_pagamento, PMC.data_usada,
                        SML.apelido
                        FROM `param_mkt_ciclo` PMC
                        INNER JOIN stores_mkts_linked SML ON SML.id_mkt = PMC.integ_id
                        WHERE PMC.ativo = 1
                UNION
                SELECT DISTINCT CASE WHEN PMC.data_inicio < PMC.data_fim THEN
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-4) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-4) MONTH)),CONCAT('/',CONCAT(PMC.data_inicio,CONCAT(' 00:00:00')))))))
                        ELSE
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ((MONTH(CURRENT_TIMESTAMP)-4)+-1) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ((MONTH(CURRENT_TIMESTAMP)-4)+-1) MONTH)),CONCAT('/',CONCAT(PMC.data_inicio,CONCAT(' 00:00:00'))))))) 
                        END AS data_inicio,
                        CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-4) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-4) MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00'))))))) IS NULL THEN
                        CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-4) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-4) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-1,CONCAT(' 00:00:00'))))))) IS NULL THEN
                        CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-4) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-4) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-2,CONCAT(' 00:00:00'))))))) IS NULL THEN
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-4) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-4) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-3,CONCAT(' 00:00:00')))))))
                        ELSE
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-4) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-4) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-2,CONCAT(' 00:00:00')))))))
                        END
                        ELSE
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-4) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-4) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-1,CONCAT(' 00:00:00')))))))
                        END
                        ELSE
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-4) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-4) MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00')))))))
                        END AS data_fim,
                        PMC.data_pagamento_conecta,
                        PMC.data_pagamento, PMC.data_usada,
                        SML.apelido
                        FROM `param_mkt_ciclo` PMC
                        INNER JOIN stores_mkts_linked SML ON SML.id_mkt = PMC.integ_id
                        WHERE PMC.ativo = 1
                UNION
                SELECT DISTINCT CASE WHEN PMC.data_inicio < PMC.data_fim THEN
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-3) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-3) MONTH)),CONCAT('/',CONCAT(PMC.data_inicio,CONCAT(' 00:00:00')))))))
                        ELSE
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ((MONTH(CURRENT_TIMESTAMP)-3)+-1) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ((MONTH(CURRENT_TIMESTAMP)-3)+-1) MONTH)),CONCAT('/',CONCAT(PMC.data_inicio,CONCAT(' 00:00:00'))))))) 
                        END AS data_inicio,
                        CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-3) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-3) MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00'))))))) IS NULL THEN
                        CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-3) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-3) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-1,CONCAT(' 00:00:00'))))))) IS NULL THEN
                        CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-3) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-3) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-2,CONCAT(' 00:00:00'))))))) IS NULL THEN
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-3) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-3) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-3,CONCAT(' 00:00:00')))))))
                        ELSE
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-3) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-3) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-2,CONCAT(' 00:00:00')))))))
                        END
                        ELSE
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-3) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-3) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-1,CONCAT(' 00:00:00')))))))
                        END
                        ELSE
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-3) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-3) MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00')))))))
                        END AS data_fim,
                        PMC.data_pagamento_conecta,
                        PMC.data_pagamento, PMC.data_usada,
                        SML.apelido
                        FROM `param_mkt_ciclo` PMC
                        INNER JOIN stores_mkts_linked SML ON SML.id_mkt = PMC.integ_id
                        WHERE PMC.ativo = 1
                UNION
                SELECT DISTINCT CASE WHEN PMC.data_inicio < PMC.data_fim THEN
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-2) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-2) MONTH)),CONCAT('/',CONCAT(PMC.data_inicio,CONCAT(' 00:00:00')))))))
                        ELSE
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ((MONTH(CURRENT_TIMESTAMP)-2)+-1) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ((MONTH(CURRENT_TIMESTAMP)-2)+-1) MONTH)),CONCAT('/',CONCAT(PMC.data_inicio,CONCAT(' 00:00:00'))))))) 
                        END AS data_inicio,
                        CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-2) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-2) MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00'))))))) IS NULL THEN
                        CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-2) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-2) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-1,CONCAT(' 00:00:00'))))))) IS NULL THEN
                        CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-2) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-2) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-2,CONCAT(' 00:00:00'))))))) IS NULL THEN
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-2) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-2) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-3,CONCAT(' 00:00:00')))))))
                        ELSE
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-2) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-2) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-2,CONCAT(' 00:00:00')))))))
                        END
                        ELSE
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-2) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-2) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-1,CONCAT(' 00:00:00')))))))
                        END
                        ELSE
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-2) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-2) MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00')))))))
                        END AS data_fim,
                        PMC.data_pagamento_conecta,
                        PMC.data_pagamento, PMC.data_usada,
                        SML.apelido
                        FROM `param_mkt_ciclo` PMC
                        INNER JOIN stores_mkts_linked SML ON SML.id_mkt = PMC.integ_id
                        WHERE PMC.ativo = 1
                UNION
                SELECT DISTINCT CASE WHEN PMC.data_inicio < PMC.data_fim THEN
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-1) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-1) MONTH)),CONCAT('/',CONCAT(PMC.data_inicio,CONCAT(' 00:00:00')))))))
                        ELSE
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ((MONTH(CURRENT_TIMESTAMP)-1)+-1) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ((MONTH(CURRENT_TIMESTAMP)-1)+-1) MONTH)),CONCAT('/',CONCAT(PMC.data_inicio,CONCAT(' 00:00:00'))))))) 
                        END AS data_inicio,
                        CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-1) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-1) MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00'))))))) IS NULL THEN
                        CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-1) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-1) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-1,CONCAT(' 00:00:00'))))))) IS NULL THEN
                        CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-1) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-1) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-2,CONCAT(' 00:00:00'))))))) IS NULL THEN
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-1) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-1) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-3,CONCAT(' 00:00:00')))))))
                        ELSE
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-1) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-1) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-2,CONCAT(' 00:00:00')))))))
                        END
                        ELSE
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-1) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-1) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-1,CONCAT(' 00:00:00')))))))
                        END
                        ELSE
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-1) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)-1) MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00')))))))
                        END AS data_fim,
                        PMC.data_pagamento_conecta,
                        PMC.data_pagamento, PMC.data_usada,
                        SML.apelido
                        FROM `param_mkt_ciclo` PMC
                        INNER JOIN stores_mkts_linked SML ON SML.id_mkt = PMC.integ_id
                        WHERE PMC.ativo = 1
                UNION
                SELECT DISTINCT CASE WHEN PMC.data_inicio < PMC.data_fim THEN
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)) MONTH)),CONCAT('/',CONCAT(PMC.data_inicio,CONCAT(' 00:00:00')))))))
                        ELSE
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ((MONTH(CURRENT_TIMESTAMP))+-1) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ((MONTH(CURRENT_TIMESTAMP))+-1) MONTH)),CONCAT('/',CONCAT(PMC.data_inicio,CONCAT(' 00:00:00'))))))) 
                        END AS data_inicio,
                        CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)) MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00'))))))) IS NULL THEN
                        CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-1,CONCAT(' 00:00:00'))))))) IS NULL THEN
                        CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-2,CONCAT(' 00:00:00'))))))) IS NULL THEN
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-3,CONCAT(' 00:00:00')))))))
                        ELSE
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-2,CONCAT(' 00:00:00')))))))
                        END
                        ELSE
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-1,CONCAT(' 00:00:00')))))))
                        END
                        ELSE
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)) MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00')))))))
                        END AS data_fim,
                        PMC.data_pagamento_conecta,
                        PMC.data_pagamento, PMC.data_usada,
                        SML.apelido
                        FROM `param_mkt_ciclo` PMC
                        INNER JOIN stores_mkts_linked SML ON SML.id_mkt = PMC.integ_id
                        WHERE PMC.ativo = 1
                
                UNION
                SELECT DISTINCT CASE WHEN PMC.data_inicio < PMC.data_fim THEN
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)+1) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)+1) MONTH)),CONCAT('/',CONCAT(PMC.data_inicio,CONCAT(' 00:00:00')))))))
                        ELSE
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ((MONTH(CURRENT_TIMESTAMP)+1)+-1) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ((MONTH(CURRENT_TIMESTAMP)+1)+-1) MONTH)),CONCAT('/',CONCAT(PMC.data_inicio,CONCAT(' 00:00:00'))))))) 
                        END AS data_inicio,
                        CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)+1) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)+1) MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00'))))))) IS NULL THEN
                        CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)+1) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)+1) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-1,CONCAT(' 00:00:00'))))))) IS NULL THEN
                        CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)+1) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)+1) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-2,CONCAT(' 00:00:00'))))))) IS NULL THEN
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)+1) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)+1) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-3,CONCAT(' 00:00:00')))))))
                        ELSE
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)+1) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)+1) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-2,CONCAT(' 00:00:00')))))))
                        END
                        ELSE
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)+1) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)+1) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-1,CONCAT(' 00:00:00')))))))
                        END
                        ELSE
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)+1) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)+1) MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00')))))))
                        END AS data_fim,
                        PMC.data_pagamento_conecta,
                        PMC.data_pagamento, PMC.data_usada,
                        SML.apelido
                        FROM `param_mkt_ciclo` PMC
                        INNER JOIN stores_mkts_linked SML ON SML.id_mkt = PMC.integ_id
                        WHERE PMC.ativo = 1
                UNION
                SELECT DISTINCT CASE WHEN PMC.data_inicio < PMC.data_fim THEN
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)+2) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)+2) MONTH)),CONCAT('/',CONCAT(PMC.data_inicio,CONCAT(' 00:00:00')))))))
                        ELSE
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ((MONTH(CURRENT_TIMESTAMP)+2)+-1) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ((MONTH(CURRENT_TIMESTAMP)+2)+-1) MONTH)),CONCAT('/',CONCAT(PMC.data_inicio,CONCAT(' 00:00:00'))))))) 
                        END AS data_inicio,
                        CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)+2) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)+2) MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00'))))))) IS NULL THEN
                        CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)+2) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)+2) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-1,CONCAT(' 00:00:00'))))))) IS NULL THEN
                        CASE WHEN DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)+2) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)+2) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-2,CONCAT(' 00:00:00'))))))) IS NULL THEN
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)+2) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)+2) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-3,CONCAT(' 00:00:00')))))))
                        ELSE
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)+2) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)+2) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-2,CONCAT(' 00:00:00')))))))
                        END
                        ELSE
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)+2) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)+2) MONTH)),CONCAT('/',CONCAT(PMC.data_fim-1,CONCAT(' 00:00:00')))))))
                        END
                        ELSE
                        DATE(CONCAT(YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)+2) MONTH)),CONCAT('/',CONCAT(MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL (MONTH(CURRENT_TIMESTAMP)+2) MONTH)),CONCAT('/',CONCAT(PMC.data_fim,CONCAT(' 00:00:00')))))))
                        END AS data_fim,
                        PMC.data_pagamento_conecta,
                        PMC.data_pagamento, PMC.data_usada,
                        SML.apelido
                        FROM `param_mkt_ciclo` PMC
                        INNER JOIN stores_mkts_linked SML ON SML.id_mkt = PMC.integ_id
                        WHERE PMC.ativo = 1
                        ) CICLO 
                        INNER JOIN stores_mkts_linked SML ON SML.apelido = CICLO.apelido
                    ) CICLO2
                    WHERE CONCAT( YEAR( STR_TO_DATE(data_pagamento_conecta, '%d/%m/%Y') ), MONTH( STR_TO_DATE(data_pagamento_conecta, '%d/%m/%Y') ) ) IN ( CONCAT( YEAR(CURRENT_TIMESTAMP), MONTH(CURRENT_TIMESTAMP)), CONCAT( YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL +1 MONTH)), MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL +1 MONTH)) ) ) 
                    $order";
    
            $query = $this->db->query($sql);
    
            return $query->result_array();

    }


    public function buscalojapelopedido($input, $idMktplace)
    {

        if ($idMktplace == "10") { //B2W
            $condicional = " SUBSTRING(O.numero_marketplace,POSITION('-' IN O.numero_marketplace)+1, LENGTH(O.numero_marketplace)- POSITION('-' IN O.numero_marketplace)) = '" . $input['entrega'] . "'";
        } elseif ($idMktplace == "11") { // ML
            $condicional = " O.numero_marketplace = '" . $input['ref_pedido'] . "'";
        } elseif ($idMktplace == "15") { // VIA VAREJO
            $condicional = " O.numero_marketplace = '" . $input['ref_pedido'] . "'";
        } elseif ($idMktplace == "16") { // CARREFOUR
            $condicional = " SUBSTRING(numero_marketplace,1,POSITION('-' IN numero_marketplace)-1)  = SUBSTRING('" . $input['ref_pedido'] . "',1,POSITION('-' IN '" . $input['ref_pedido'] . "')-1) OR O.numero_marketplace = '" . $input['ref_pedido'] . "'";
        } elseif ($idMktplace == "17") { // Madeira Madeira
            $condicional = " O.numero_marketplace = '" . $input['ref_pedido'] . "'";
        }
        elseif ($idMktplace == "30") 
        { // NovoMundo
            $condicional = " O.numero_marketplace = '" . $input['orderid'] . "'";
        }

        $sql = "SELECT DISTINCT S.id, S.name 
                FROM stores S
                INNER JOIN orders O ON O.store_id = S.id where $condicional ";
	    
	    $query = $this->db->query($sql);
	    $saida = $query->result_array();
	    
	    if($saida){
	       return $saida[0];
	    }else{
	        return false;
	    }
	}
	
	public function getdatapagamentoseller($store_id){
	    
	    
	    $sql = "select 
                    distinct DATE_FORMAT(IR.data_transferencia,\"%d/%m/%Y\") as data_transferencia, 
                    left(IR.data_transferencia,10) as data_id 
                from 
                    iugu_repasse IR 
                    inner join orders O on O.id = IR.order_id 
                where 
                    O.store_id = $store_id";
	    
	    $query = $this->db->query($sql);
	    return (false !== $query) ? $query->result_array() : false;
	    
	    
	}

	public function getDataPagamentoByStoreAndMonthAndYear(int $store_id, int $month, int $year){
        return $this->db->distinct('left(ir.data_transferencia,10) as data_id')
            ->select('left(ir.data_transferencia,10) as data_id')
            ->join('orders o', 'o.id = ir.order_id')
            ->where('o.store_id', $store_id)
            ->where("MONTH(ir.data_transferencia) = $month", NULL, FALSE)
            ->where("YEAR(ir.data_transferencia) = $year", NULL, FALSE)
            ->get('iugu_repasse ir')
            ->row_array();
	}

    public function getstoresfromconciliacaosellercenter($lote){
	    
	    
	    $sql = "select distinct id as id_loja, name as nome_loja from stores where id in (select distinct store_id from conciliacao_sellercenter where lote = '$lote')";
	    
	    $query = $this->db->query($sql);
	    return (false !== $query) ? $query->result_array() : false;
	    
	    
	}

    public function getstatusfromconciliacaosellercenter($lote){
	    
	    
	    $sql = "select distinct status_conciliacao as id, REPLACE(status_conciliacao, 'Conciliação', 'Repasse') as status from conciliacao_sellercenter where lote = '$lote'";
	    
	    $query = $this->db->query($sql);
	    return (false !== $query) ? $query->result_array() : false;
	    
	    
	}


    public function getOrdersFromConciliacaoSellercenter($lote)
    {	    
	    $sql = "select * from conciliacao_sellercenter csc left join campaign_v2_orders cv2o on csc.order_id = cv2o.order_id where csc.lote = '$lote'";
	    
	    $query = $this->db->query($sql);
	    return (false !== $query) ? $query->result_array() : false;
	    
	    
	}


	public function getPedidosExtratoConciliadoResumoParceiro($input, $order=null){
	    
	    
	    $where = "WHERE 1=1 and IR.data_transferencia IS NOT NULL ";
        $where2 = "WHERE 1 = 1";

	    $more = ($this->data['usercomp'] == 1) ? "": (($this->data['userstore'] == 0) ? " AND O.company_id = ".$this->data['usercomp'] : " AND O.store_id = ".$this->data['userstore']);
	    
	    if(!empty($input)){
	        if($input['txt_data_inicio'] <> ""){
	            $where .= " AND O.date_time >= '".$input['txt_data_inicio']." 00:00:00'";
	        }
	        
	        if($input['txt_data_fim'] <> ""){
	            $where .= " AND O.date_time <= '".$input['txt_data_fim']." 23:59:59'";
	        }
	        
	        if($input['slc_mktplace'] <> ""){
	            $where .= " AND SML.apelido = '".$input['slc_mktplace']."'";
	        }
	        
	        if($input['slc_status'] <> ""){
	            $where .= " AND O.paid_status in (".$input['slc_status'].")";
	        }
	        
	        if($input['slc_loja'] <> ""){
	            $where .= " AND O.store_id = '".$input['slc_loja']."'";
	        }
	    }
	    
	    if($order <> null){
	        
	        $order = " order by IFNULL( O.data_entrega,'9999-12-31 23:59:59'";
	        
	    }
        $sqlLimit = "";
        $sql_init = " SELECT  E.* FROM (";
	    $count = "SELECT COUNT(*) as qtd FROM (";
        $fimSql = ") E " . $where2;
	    $sql = "SELECT
                    DATE_FORMAT(IR.data_transferencia,\"%d/%m/%Y\") as data_transferencia,
                    IFNULL(round(sum(IR.valor_afiliado),2),'0.00') AS valor_parceiro,
                    SML.descloja as marketplace
                    FROM orders O
                    INNER JOIN stores S ON S.id = O.store_id
                    INNER JOIN company COM ON COM.id = S.company_id
                    INNER JOIN stores_mkts_linked SML ON SML.apelido = O.origin
                    INNER JOIN (SELECT DISTINCT integ_id, valor_aplicado FROM param_mkt_categ_integ WHERE ativo = 1) PMCI ON PMCI.integ_id = id_mkt
                    LEFT JOIN (SELECT DISTINCT CM.id, CM.numero_chamado, CM.previsao_solucao, CMO.numero_marketplace FROM `chamado_marketplace` CM INNER JOIN chamado_marketplace_orders CMO ON CMO.chamado_marketplace_id = CM.id WHERE CMO.ativo = 1 AND CM.billet_status_id = 18) CM ON CM.numero_marketplace = O.numero_marketplace
                    LEFT JOIN iugu_repasse IR ON IR.order_id = O.id
                    LEFT JOIN conciliacao C ON C.id = IR.conciliacao_id
                $where $more
                group by IR.data_transferencia, SML.descloja
                $order
                ";
                if(isset($post['length']) && isset($post['start'])){
                    $sqlLimit = 'LIMIT ' . $post['length'] . ' OFFSET ' . $post['start'];
                }$query = $this->db->query($sql_init.$sql.$order.$sqlLimit.$fimSql)->result_array();

                $count = $this->db->query($count.$sql.$fimSql)->result_array();
                if(!$count){
                    $count[0]['qtd'] = 0;
                }
                return [
                    'data' => $query,
                    'count' => $count[0]['qtd']
                ];
                /*
                $query = $this->db->query($sql);
                
                return (false !== $query) ? $query->result_array() : false; */
                
	}
	
	public function getPedidosExtratoConciliadoParceiro($input, $order = null, $post = []){
	    
	    
        $where = " where 1 = 1 ";
	    $more = ($this->data['usercomp'] == 1) ? "": (($this->data['userstore'] == 0) ? " AND O.company_id = ".$this->data['usercomp'] : " AND O.store_id = ".$this->data['userstore']);
        $where2 = "WHERE 1 = 1";

	    if(!empty($input)){
	        if($input['txt_data_inicio'] <> ""){
	            $where .= " AND O.date_time >= '".$input['txt_data_inicio']." 00:00:00'";
	        }
	        
	        if($input['txt_data_fim'] <> ""){
	            $where .= " AND O.date_time <= '".$input['txt_data_fim']." 23:59:59'";
	        }
	        
	        if($input['slc_mktplace'] <> ""){
	            $where .= " AND SML.apelido = '".$input['slc_mktplace']."'";
	        }
	        
	        if($input['slc_status'] <> ""){
	            $where .= " AND O.paid_status in (".$input['slc_status'].")";
	        }
	        
	        if($input['slc_loja'] <> ""){
	            $where .= " AND O.store_id = '".$input['slc_loja']."'";
	        }
	    }
	    
	    if($order <> null){
	        
	        $order = " order by IFNULL( O.data_entrega,'9999-12-31 23:59:59' )";
	        
	    }
        $sqlLimit = "";
        $sql_init = " SELECT  E.*, data_pagamento_marketplace(E.id) AS data_pagamento_mktplace,
        data_pagamento_conecta(E.id) AS data_pagamento_conectala FROM ( ";
	    $count = "SELECT COUNT(*) as qtd FROM ( ";
        $fimSql = " ) E " . $where2;
	    $sql = "
                    SELECT
                    O.id,
                    O.paid_status,
                    DATE_FORMAT(O.date_time,\"%d/%m/%Y\") AS date_time,
                    DATE_FORMAT(O.data_entrega,\"%d/%m/%Y\") AS data_entrega,
                    CASE WHEN IR.data_transferencia IS NULL THEN 'Não' ELSE 'Sim' END AS pago,
                    IR.data_split,
                    DATE_FORMAT(IR.data_transferencia,\"%d/%m/%Y\") AS data_transferencia,
                    CASE WHEN COM.associate_type > 0 THEN 
                        CASE WHEN O.origin = 'B2W' AND O.date_time >= '2021-02-01' THEN
                            ROUND(   ( O.total_order * ((O.service_charge_rate-PMCI.valor_aplicado)/100) + 5 ) * (COM.service_charge_value/100) , 2)
                        ELSE
                            ROUND(   ( O.gross_amount * ((O.service_charge_rate-PMCI.valor_aplicado)/100) ) * (COM.service_charge_value/100) , 2)
                        END
                    ELSE 
                        0 
                    END AS expectativaReceb, 
                    IFNULL(IR.valor_afiliado,'0.00') AS valor_parceiro,
                    CASE WHEN IR.order_id IS NULL THEN \"Nao\" ELSE \"Sim\" END AS tratado,
                    '' AS observacao,
                    '' AS numero_chamado,
                    '' AS previsao_solucao,
                    O.numero_marketplace AS numero_pedido,
                    C.lote,
                    SML.descloja AS marketplace,
                    O.gross_amount,
                    O.total_order,
                    O.total_ship,
                    DATE_FORMAT(C.data_criacao,\"%d/%m/%Y\") AS data_recebimento_mktpl,
                    DATE_FORMAT(DATE_ADD(IR.data_transferencia, INTERVAL 1 DAY),\"%d/%m/%Y\") AS data_caiu_na_conta,
                    S.name AS nome_loja,
                    ROUND(O.total_order - ROUND(O.gross_amount - ( O.gross_amount * (O.service_charge_rate/100) ) - O.total_ship,2),2) AS comissao_descontada
                    FROM orders O
                    INNER JOIN stores S ON S.id = O.store_id
                    INNER JOIN company COM ON COM.id = S.company_id
                    INNER JOIN stores_mkts_linked SML ON SML.apelido = O.origin
                    INNER JOIN (SELECT DISTINCT integ_id, valor_aplicado FROM param_mkt_categ_integ WHERE ativo = 1) PMCI ON PMCI.integ_id = id_mkt
                    LEFT JOIN iugu_repasse IR ON IR.order_id = O.id
                    LEFT JOIN conciliacao C ON C.id = IR.conciliacao_id
                    $where $more";
                    if(isset($post['length']) && isset($post['start'])){
                        $sqlLimit = ' LIMIT ' . $post['length'] . ' OFFSET ' . $post['start'];
                    }
                    
                    //echo $sql_init.$sql.$order.$sqlLimit.$fimSql;die; 
                    $query = $this->db->query($sql_init.$sql.$order.$sqlLimit.$fimSql)->result_array();
                    
                    $count = $this->db->query($count.$sql.$fimSql)->result_array();
                    if(!$count){
                        $count[0]['qtd'] = 0;
                    }
                    return [
                        'data' => $query,
                        'count' => $count[0]['qtd']
                    ];
	}

    public function getdiscountworksheetgroup($id = null){

        $where = " where 1=1 ";
        if($id <> null){
            $where = " and id = $id";
        }

        $sql = "select C.*, DATE_FORMAT(C.data_criacao,\"%d/%m/%Y\") as data_criacao_tratado, case when ativo = 1 then 'Ativo' else 'Desativado' end as ativo_tratado from carga_desconto_group C".$where;

        $query = $this->db->query($sql);
                    
        return (false !== $query) ? $query->result_array() : false;

    }

    public function desativadiscountworksheetgroup($id = null){

        
        if($id <> null){

            $sql = "UPDATE carga_desconto_group set ativo = case when ativo = 0 then 1 else 0 end WHERE id = $id";

            if($this->db->query($sql)){
                $sql2 = "UPDATE carga_desconto set ativo = case when ativo = 0 then 1 else 0 end WHERE lote = (select lote from carga_desconto_group where id = $id)";

                return $this->db->query($sql2);
            }else{
                return false;
            }

        }else{
            return false;
        }

    }

    public function insertediscountworksheetgroup($param = array()){

        $data['lote']       = $param['hdnLote'];
        $data['data_ciclo'] = $param['slc_ciclo_fiscal'];

        $insert = $this->db->insert('carga_desconto_group', $data);
        $order_id = $this->db->insert_id();
        return ($order_id) ? $order_id : false;

    }

	public function limpatabeladiscountworksheettemp($lote){

        $sql = "delete from carga_desconto_temp where lote = '$lote'";
        return $this->db->query($sql);

    }

	public function inseredadostabeladiscountworksheet($lote){

        $sql1 = "INSERT INTO `carga_desconto` (`lote`,`data_ciclo`,`dsc_departamento`,`dsc_status_pagto`,`dsc_linha`,`cod_pedido`,`cod_entrega`,`id_linha`,`cod_sku`,`dsc_sku`,`parceiro`,
        `cnpj_parceiro`,`marca`,`midia`,`dat_pedido`,`cashback_expirado`,`qtd_item`,`vlr_comissao_seller`,`cash_discount_b2w`,`cash_discount_seller`,`cashback_b2w`,
        `cashback_seller`,`finance_b2w`,`finance_seller`,`finance_cetelem`,`desconto_incondicional_b2w`,`desconto_incondicional_seller`,`frete_b2w`,`frete_seller`,
        `cupom_b2w`,`cupom_seller`,`data_criacao`,`ativo`,`cashback_expirado_seller`,`gmv`,`vl_frete_cliente`,`vl_frete_mais_item`,`usuario`)
        SELECT `lote`,`data_ciclo`,`dsc_departamento`,`dsc_status_pagto`,`dsc_linha`,`cod_pedido`,`cod_entrega`,`id_linha`,`cod_sku`,`dsc_sku`,`parceiro`,
        `cnpj_parceiro`,`marca`,`midia`,`dat_pedido`,`cashback_expirado`,`qtd_item`,`vlr_comissao_seller`,`cash_discount_b2w`,`cash_discount_seller`,`cashback_b2w`,
        `cashback_seller`,`finance_b2w`,`finance_seller`,`finance_cetelem`,`desconto_incondicional_b2w`,`desconto_incondicional_seller`,`frete_b2w`,`frete_seller`,
        `cupom_b2w`,`cupom_seller`,`data_criacao`,`ativo`,`cashback_expirado_seller`,`gmv`,`vl_frete_cliente`,`vl_frete_mais_item`,`usuario`
        FROM `carga_desconto_temp` WHERE lote = '$lote'";

        return $this->db->query($sql1);

    }

    public function saveCampignV2OrderUsingCargaDesconto($lote): void
    {

        $sql = "SELECT carga_desconto.*, orders.id AS order_id
                    FROM `carga_desconto` JOIN orders ON (carga_desconto.cod_pedido = orders.numero_marketplace) 
                    WHERE lote = '$lote' ";
        $query = $this->db->query($sql);
        $rows = $query->result_array();

        if ($rows){
            foreach ($rows as $row){

                $sqlCampaignV2Orders = "SELECT * FROM campaign_v2_orders WHERE order_id = '{$row['order_id']}'";
                $resultCampaignV2Orders = $this->db->query($sqlCampaignV2Orders);
                $rowCampaignV2Order = $resultCampaignV2Orders->row_array();

                $totalCampaigns = $totalChannel = $row['cash_discount_b2w']+$row['finance_b2w']+$row['desconto_incondicional_b2w']+$row['frete_b2w']+$row['cupom_b2w'];

                if ($rowCampaignV2Order){
                    //Atualizar
                    $this->db->query("UPDATE campaign_v2_orders SET total_campaigns = total_campaigns+$totalCampaigns, total_channel = total_channel+$totalChannel WHERE order_id = '{$row['order_id']}'");
                }else{

                    //Cadastrar
                    $this->db->query("INSERT INTO campaign_v2_orders (order_id, total_products, total_pricetags, total_campaigns, total_channel, total_seller, 
                                                                    total_promotions, total_rebate, comission_reduction, comission_reduction_products, 
                                                                    discount_comission, comission_reduction_marketplace, total_rebate_marketplace)
                                                    VALUES ('{$row['order_id']}', 0, 0, '$totalCampaigns', '$totalChannel', 0,
                                                            0, 0, 0, 0,
                                                            0, 0, 0)");

                }

            }
        }

    }

    public function salvarDiscountWorksheetLTable($input, $arquivo){
	    
	    $data['lote'] = $input['hdnLote'];
        $data['data_ciclo'] = $input['slc_ciclo_fiscal'];

	    $data['dsc_departamento'] = $arquivo['departamento'];
        $data['dsc_status_pagto'] = $arquivo['status_pagamento'];
        $data['dsc_linha'] = $arquivo['linha'];
        $data['cod_pedido'] = $arquivo['cod_pedido'];
        $data['cod_entrega'] = $arquivo['cod_entrega'];
        $data['id_linha'] = $arquivo['order_line_id'];
        $data['cod_sku'] = $arquivo['cod_sku'];
        $data['dsc_sku'] = $arquivo['desc_sku'];
        $data['parceiro'] = $arquivo['parceiro'];
        $data['cnpj_parceiro'] = $arquivo['cnpj'];
        $data['marca'] = $arquivo['marca'];
        $data['midia'] = $arquivo['midia'];
        $data['dat_pedido'] = (string)$arquivo['dt_pedido'];
        $data['cashback_expirado'] = $arquivo['cashback_expirado_b2w'];
        $data['cashback_expirado_seller'] = $arquivo['cashback_expirado_seller'];
        $data['qtd_item'] = $arquivo['qtd_itens'];
        $data['gmv'] = $arquivo['gmv'];
        $data['vlr_comissao_seller'] = $arquivo['comissao_seller'];
        $data['cash_discount_seller'] = $arquivo['desconto_condicional_seller'];
        $data['cash_discount_b2w'] = $arquivo['desconto_condicional_b2w'];
        $data['cashback_seller'] = $arquivo['cashback_Seller'];
        $data['cashback_b2w'] = $arquivo['cashback_b2w'];
        $data['finance_seller'] = $arquivo['finance_Seller'];
        $data['finance_b2w'] = $arquivo['finance_b2w'];
        $data['finance_cetelem'] = $arquivo['finance_cetelem'];
        $data['desconto_incondicional_seller'] = $arquivo['desconto_incondicional_seller'];
        $data['desconto_incondicional_b2w'] = $arquivo['desconto_incondicional_b2w'];
        $data['frete_seller'] = $arquivo['frete_seller'];
        $data['frete_b2w'] = $arquivo['frete_b2w'];
        $data['cupom_seller'] = $arquivo['cupom_seller'];
        $data['cupom_b2w'] = $arquivo['cupom_b2w'];
        $data['vl_frete_cliente'] = $arquivo['vl_frete_cliente'];
        $data['vl_frete_mais_item'] = $arquivo['vl_frete_mais_item'];

	    $data['usuario'] = $_SESSION['username'];

	    $insert = $this->db->insert('carga_desconto_temp', $data);
	    $order_id = $this->db->insert_id();
	    return ($order_id) ? $order_id : false;
	    
	}

    public function atualizaValorConciliacaoArquivoMovelo($input, $arquivo){

        $data = array(
            'valor_parceiro_novo' => str_replace( "\"", "", utf8_encode($arquivo[4])),
            'valor_produto_conecta_ajustado' => str_replace( "\"", "", utf8_encode($arquivo[2])),
            'valor_frete_conecta_ajustado' => str_replace( "\"", "", utf8_encode($arquivo[3])),
            'valor_conectala_ajustado' => round(str_replace( "\"", "", utf8_encode($arquivo[3])) + str_replace( "\"", "", utf8_encode($arquivo[2])),2)
        );
        $this->db->where('lote', $input['hdnLote']);
        
        if($input['slc_mktplace'] == "10"){
            $this->db->where('ref_pedido', $arquivo[1]);
            $update = $this->db->update('conciliacao_b2w_tratado', $data);
        }elseif($input['slc_mktplace'] == "11"){
            $this->db->where('external_reference', $arquivo[1]);
            $update = $this->db->update('conciliacao_mercadolivre', $data);
        }elseif($input['slc_mktplace'] == "15"){
            $this->db->where('numero_do_pedido', $arquivo[1]);
            $update = $this->db->update('conciliacao_viavarejo', $data);
        }elseif($input['slc_mktplace'] == "16"){
            $this->db->where('n_do_pedido', $arquivo[1]);
            $update = $this->db->update('conciliacao_carrefour_xls', $data);
            $this->atualizaValorConciliacaoArquivoMoveloCarrefAntigo($input, $arquivo);
            
        }elseif($input['slc_mktplace'] == "17"){
            $this->db->where('n_do_pedido', $arquivo[1]);
            $update = $this->db->update('conciliacao_madeira_tratado', $data);
        }
        elseif ($input['slc_mktplace'] == "30")
        {
            $this->db->where('orderid', $arquivo[1]);
            $update = $this->db->update('conciliacao_nm', $data);
        }
        elseif ($input['slc_mktplace'] == "999")
        {
            $this->db->where('numero_pedido', $arquivo[1]);
            $update = $this->db->update('conciliacao_manual', $data);
        }

	    return $update;

    }

    public function atualizaValorConciliacaoArquivoMoveloCarrefAntigo($input, $arquivo){

        $data = array(
            'valor_parceiro_novo' => str_replace( "\"", "", utf8_encode($arquivo[4])),
            'valor_produto_conecta_ajustado' => str_replace( "\"", "", utf8_encode($arquivo[2])),
            'valor_frete_conecta_ajustado' => str_replace( "\"", "", utf8_encode($arquivo[3])),
            'valor_conectala_ajustado' => round(str_replace( "\"", "", utf8_encode($arquivo[3])) + str_replace( "\"", "", utf8_encode($arquivo[2])),2)
        );
        $this->db->where('lote', $input['hdnLote']);

        $this->db->where('n_do_pedido', $arquivo[1]);
        return $this->db->update('conciliacao_carrefour', $data);

    }

    public function getTempRepasse($lote){

        $sql = "select RT.*, BS.nome as status_billet from repasse_temp RT inner join billet_status BS on BS.id = RT.status_repasse where lote = '$lote'";
        $query = $this->db->query($sql);
        return (false !== $query) ? $query->result_array() : false;

    }

    public function carregaTempRepasse($lote){

        $limpa = $this->limpaTempRepasse($lote);

        if($limpa){


            // Carrega primeiro uma versão já existente da conciliação
            $sql = "insert into repasse_temp (lote, conciliacao_id, store_id, name, valor_conectala, valor_seller, responsavel, status_repasse)
                    SELECT lote, conciliacao_id, store_id, name, valor_conectala, valor_seller, responsavel, status_repasse from repasse where lote = '$lote'";

            $retorno = $this->db->query($sql);

            if($retorno){

                $sql2 = "select count(*) as qtd from repasse_temp where lote = '$lote'";
                $query = $this->db->query($sql2);
                $contagem = $query->result_array();
                if($contagem[0]['qtd'] == "0"){

                    $sql = "INSERT INTO repasse_temp (lote, conciliacao_id, store_id, name, valor_conectala, valor_seller, responsavel, status_repasse)
                            SELECT lote, id, store_id, seller_name, SUM(valor_conectala_ajustado) AS valor_conectala_ajustado, SUM(valor_parceiro_ajustado) AS valor_parceiro_ajustado, responsavel, `status`  FROM (
                            SELECT CB.lote, C.id, CB.store_id, CB.seller_name, CB.valor_conectala_ajustado, CASE WHEN CB.valor_parceiro_novo <> '0.00' THEN CB.valor_parceiro_novo ELSE CB.valor_parceiro END AS valor_parceiro_ajustado, '".$_SESSION['username']."' AS responsavel, 21 AS `status` FROM conciliacao_b2w_tratado CB INNER JOIN conciliacao C ON C.lote = CB.lote LEFT JOIN conciliacao_temp_pedido ctp ON ctp.num_pedido = CB.ref_pedido AND ctp.lote = CB.lote WHERE C.lote = '$lote' and CB.store_id is not null AND CASE WHEN ctp.num_pedido IS NOT NULL and CB.status_conciliacao IS NOT NULL THEN 'Ok' ELSE ifnull(CB.status_conciliacao_novo,CB.status_conciliacao) END = 'ok'
                            UNION
                            SELECT CB.lote, C.id, CB.store_id, CB.seller_name, CB.valor_conectala_ajustado, CASE WHEN CB.valor_parceiro_novo <> '0.00' THEN CB.valor_parceiro_novo ELSE CB.valor_parceiro END AS valor_parceiro_ajustado, '".$_SESSION['username']."' AS responsavel, 21 AS `status` FROM conciliacao_carrefour_xls CB INNER JOIN conciliacao C ON C.lote = CB.lote LEFT JOIN conciliacao_temp_pedido ctp ON ctp.num_pedido = CB.n_do_pedido AND ctp.lote = CB.lote WHERE C.lote = '$lote' and CB.store_id is not null AND CASE WHEN ctp.num_pedido IS NOT NULL and CB.status_conciliacao IS NOT NULL THEN 'Ok' ELSE ifnull(CB.status_conciliacao_novo,CB.status_conciliacao) END = 'ok'
                            UNION
                            SELECT CB.lote, C.id, CB.store_id, CB.seller_name, CB.valor_conectala_ajustado, CASE WHEN CB.valor_parceiro_novo <> '0.00' THEN CB.valor_parceiro_novo ELSE CB.valor_parceiro END AS valor_parceiro_ajustado, '".$_SESSION['username']."' AS responsavel, 21 AS `status` FROM conciliacao_viavarejo CB INNER JOIN conciliacao C ON C.lote = CB.lote LEFT JOIN conciliacao_temp_pedido ctp ON ctp.num_pedido = CB.numero_do_pedido AND ctp.lote = CB.lote WHERE C.lote = '$lote' and CB.store_id is not null AND CASE WHEN ctp.num_pedido IS NOT NULL and CB.status_conciliacao IS NOT NULL THEN 'Ok' ELSE ifnull(CB.status_conciliacao_novo,CB.status_conciliacao) END = 'ok'
                            UNION
                            SELECT CB.lote, C.id, CB.store_id, CB.seller_name, CB.valor_conectala_ajustado, CASE WHEN CB.valor_parceiro_novo <> '0.00' THEN CB.valor_parceiro_novo ELSE CB.valor_parceiro END AS valor_parceiro_ajustado, '".$_SESSION['username']."' AS responsavel, 21 AS `status` FROM conciliacao_nm CB INNER JOIN conciliacao C ON C.lote = CB.lote LEFT JOIN conciliacao_temp_pedido ctp ON ctp.num_pedido = CB.numero_do_pedido AND ctp.lote = CB.lote WHERE C.lote = '$lote' and CB.store_id is not null AND CASE WHEN ctp.num_pedido IS NOT NULL and CB.status_conciliacao IS NOT NULL THEN 'Ok' ELSE ifnull(CB.status_conciliacao_novo,CB.status_conciliacao) END = 'ok'
                            UNION
                            SELECT CB.lote, C.id, CB.store_id, CB.seller_name, CB.valor_conectala_ajustado, CASE WHEN CB.valor_parceiro_novo <> '0.00' THEN CB.valor_parceiro_novo ELSE CB.valor_parceiro END AS valor_parceiro_ajustado, '".$_SESSION['username']."' AS responsavel, 21 AS `status` FROM conciliacao_mercadolivre CB INNER JOIN conciliacao C ON C.lote = CB.lote LEFT JOIN conciliacao_temp_pedido ctp ON ctp.num_pedido = CB.external_reference AND ctp.lote = CB.lote WHERE C.lote = '$lote' and CB.store_id is not null AND CASE WHEN ctp.num_pedido IS NOT NULL and CB.status_conciliacao IS NOT NULL THEN 'Ok' ELSE ifnull(CB.status_conciliacao_novo,CB.status_conciliacao) END = 'ok') a
                            GROUP BY lote, id, store_id, seller_name, responsavel, `status` ";

                    return $this->db->query($sql);

                }else{
                    return true;
                }
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    public function updateRepasseOnUpdateConciliation($lote = null, $payment_gateway_code = null)
    {

        if(empty($lote)) {
            return;
        }

        $this->db->trans_begin();

        $limpaTempRepasse = $this->limpaTempRepasse($lote);
        $limpaRepasse = $this->limpaRepasse($lote);
        if($limpaTempRepasse && $limpaRepasse) {

            $sql = "
                        INSERT INTO repasse_temp (lote, conciliacao_id, store_id, name, valor_conectala, valor_seller, responsavel, status_repasse, refund)
                        SELECT lote, id, store_id, seller_name, SUM(valor_conectala_ajustado) AS valor_conectala_ajustado, round(SUM(valor_parceiro_ajustado),2) AS valor_parceiro_ajustado, responsavel, `status`, SUM(refund)
                         FROM (SELECT CB.status_conciliacao, CB.lote, C.id, CB.store_id, CB.seller_name, 0 AS valor_conectala_ajustado,
                                CASE WHEN CB.valor_repasse_ajustado <> '0.00' THEN CB.valor_repasse_ajustado ELSE CB.valor_repasse END AS valor_parceiro_ajustado,
                                usuario AS responsavel, 21 AS `status`, CB.refund FROM conciliacao_sellercenter CB INNER JOIN conciliacao C ON C.lote = CB.lote WHERE C.lote = '$lote' and status_conciliacao NOT LIKE '%juridico%'
                        ) a
                        GROUP BY lote, id, store_id, seller_name, responsavel, `status`, status_conciliacao
                        UNION
                        SELECT lote, id, store_id, seller_name, valor_conectala_ajustado AS valor_conectala_ajustado, round(valor_parceiro_ajustado,2) as valor_parceiro_ajustado, responsavel, `status`, refund
                        FROM (SELECT CB.status_conciliacao, CB.lote, C.id, CB.store_id, CB.seller_name, 0 AS valor_conectala_ajustado,
                        CASE WHEN CB.valor_repasse_ajustado <> '0.00' THEN CB.valor_repasse_ajustado ELSE CB.valor_repasse END AS valor_parceiro_ajustado,
                        usuario AS responsavel, 21 AS `status`, CB.refund FROM conciliacao_sellercenter CB INNER JOIN conciliacao C ON C.lote = CB.lote WHERE C.lote = '$lote' and status_conciliacao LIKE '%juridico%'
                        ) a 
                            ";

            /*$variavelMOIP = $payment_gateway_code;

            if($variavelMOIP && $variavelMOIP == 'moip')
            {
                $sql = "INSERT INTO repasse_temp (lote, conciliacao_id, store_id, name, valor_conectala, valor_seller, responsavel, status_repasse, refund)
                        SELECT lote, id, store_id, seller_name, valor_conectala_ajustado AS valor_conectala_ajustado, round(valor_parceiro_ajustado,2) AS valor_parceiro_ajustado, responsavel, `status`, (refund)
                         FROM (SELECT CB.status_conciliacao, CB.lote, C.id, CB.store_id, CB.seller_name, 0 AS valor_conectala_ajustado,
                                CASE WHEN CB.valor_repasse_ajustado <> '0.00' THEN CB.valor_repasse_ajustado ELSE CB.valor_repasse END AS valor_parceiro_ajustado,
                                usuario AS responsavel, 21 AS `status`, CB.refund FROM conciliacao_sellercenter CB inner JOIN conciliacao C ON C.lote = CB.lote WHERE C.lote = '$lote' and status_conciliacao NOT LIKE '%juridico%'
                        ) a                        
                        UNION
                        SELECT lote, id, store_id, seller_name, valor_conectala_ajustado AS valor_conectala_ajustado, round(valor_parceiro_ajustado,2) as valor_parceiro_ajustado, responsavel, `status`, refund
                        FROM (SELECT CB.status_conciliacao, CB.lote, C.id, CB.store_id, CB.seller_name, 0 AS valor_conectala_ajustado,
                        CASE WHEN CB.valor_repasse_ajustado <> '0.00' THEN CB.valor_repasse_ajustado ELSE CB.valor_repasse END AS valor_parceiro_ajustado,
                        usuario AS responsavel, 21 AS `status`, CB.refund FROM conciliacao_sellercenter CB inner JOIN conciliacao C ON C.lote = CB.lote WHERE C.lote = '$lote' and status_conciliacao LIKE '%juridico%'
                        ) a ";
            }else{
                $sql = "INSERT INTO repasse_temp (lote, conciliacao_id, store_id, name, valor_conectala, valor_seller, responsavel, status_repasse, refund)
                SELECT lote, id, store_id, seller_name, valor_conectala_ajustado AS valor_conectala_ajustado, valor_parceiro_ajustado AS valor_parceiro_ajustado, responsavel, `status`, refund
                FROM (SELECT CB.status_conciliacao, CB.lote, C.id, CB.store_id, CB.seller_name, 0 AS valor_conectala_ajustado,
                             CASE WHEN CB.valor_repasse_ajustado <> '0.00' THEN CB.valor_repasse_ajustado ELSE CB.valor_repasse END AS valor_parceiro_ajustado,
                             usuario AS responsavel, 21 AS `status`, CB.refund FROM conciliacao_sellercenter CB inner JOIN conciliacao C ON C.lote = CB.lote WHERE C.lote = '$lote') a";
            }*/

            $this->db->query($sql);

            $this->db->trans_commit();

        }else{
            $this->db->trans_rollback();
        }
    }

    public function carregaTempRepasseSellercenter($lote, $refund_from_seller = 0, $payment_gateway_code = null){

        if(empty($lote)) {
            return;
        }

        $limpa = $this->limpaTempRepasse($lote);
        $limpaRepasse = $this->limpaRepasse($lote);

        $this->db->trans_begin();

        if($limpa && $limpaRepasse){

            $sql = "INSERT INTO repasse_temp (lote, conciliacao_id, store_id, name, valor_conectala, valor_seller, responsavel, status_repasse, refund
					,legal_panel_id)
                        SELECT 
                            lote, id, store_id, seller_name, SUM(valor_conectala_ajustado) AS valor_conectala_ajustado, 
                            round(SUM(valor_parceiro_ajustado),2) AS valor_parceiro_ajustado, responsavel, `status`, SUM(refund),
                            legal_panel_id
                         FROM (SELECT 
                                   CB.status_conciliacao, CB.lote, C.id, CB.store_id, CB.seller_name, 0 AS valor_conectala_ajustado,
                                	CASE WHEN CB.valor_repasse_ajustado <> 0.00 THEN CB.valor_repasse_ajustado ELSE CB.valor_repasse END AS valor_parceiro_ajustado,
                                	usuario AS responsavel, 21 AS `status`, CB.refund,
                                	CB.legal_panel_id
                               FROM 
									conciliacao_sellercenter CB 
									INNER JOIN conciliacao C ON C.lote = CB.lote 
                               WHERE 
                                   C.lote = '$lote' 
                               and 
                                   #status_conciliacao NOT LIKE '%juridico%'
									CB.legal_panel_id is null
                        ) a
                        GROUP BY lote, id, store_id, seller_name, responsavel, `status`, status_conciliacao
                        UNION ALL
                        SELECT 
                            lote, id, store_id, seller_name, valor_conectala_ajustado AS valor_conectala_ajustado, 
                            round(valor_parceiro_ajustado,2) as valor_parceiro_ajustado, responsavel, `status`, refund,
                            legal_panel_id
                        FROM (
                        	SELECT 
                        	    CB.status_conciliacao, CB.lote, C.id, CB.store_id, CB.seller_name, 0 AS valor_conectala_ajustado,
                        		CASE WHEN CB.valor_repasse_ajustado <> 0.00 THEN CB.valor_repasse_ajustado ELSE CB.valor_repasse END AS valor_parceiro_ajustado,
                        		usuario AS responsavel, 21 AS `status`, CB.refund,
                        		CB.legal_panel_id
                        	FROM 
                        	    conciliacao_sellercenter CB 
								INNER JOIN conciliacao C ON C.lote = CB.lote 
                        	WHERE 
                        	    C.lote = '$lote' 
                        	and 
                        	    #status_conciliacao LIKE '%juridico%'
                        		CB.legal_panel_id IS NOT null
                        ) a ";

            $repasse_temp_result = $this->db->query($sql);

            $this->db->trans_commit();

            return $repasse_temp_result;

        }else{
            $this->db->trans_rollback();
            return false;
        }
    }


    public function carregaTempRepasseSellercenter666($lote, $refund_from_seller = 0){

        $limpa = $this->limpaTempRepasse($lote);

        if($limpa){

            // Carrega primeiro uma versão já existente da conciliação
            $sql = "insert into repasse_temp (lote, conciliacao_id, store_id, name, valor_conectala, valor_seller, responsavel, status_repasse, refund)
                    SELECT lote, conciliacao_id, store_id, name, valor_conectala, valor_seller, responsavel, status_repasse, refund from repasse where lote = '$lote'";

            $retorno = $this->db->query($sql);

            if($retorno){

                $sql2 = "select count(*) as qtd from repasse_temp where lote = '$lote'";
                $query = $this->db->query($sql2);
                $contagem = $query->result_array();
                if($contagem[0]['qtd'] == "0"){

                    $this->load->model('model_gateway_settings');
                    $variavelMOIP = $this->model_gateway_settings->getSettings(4);
                    
                    if($variavelMOIP){
                        $sql = "INSERT INTO repasse_temp (lote, conciliacao_id, store_id, name, valor_conectala, valor_seller, responsavel, status_repasse, refund)
                            SELECT lote, id, store_id, seller_name, SUM(valor_conectala_ajustado) AS valor_conectala_ajustado, SUM(valor_parceiro_ajustado) AS valor_parceiro_ajustado, responsavel, `status`, SUM(refund)
                             FROM (SELECT CB.status_conciliacao, CB.lote, C.id, CB.store_id, CB.seller_name, 0 AS valor_conectala_ajustado, 
                                    CASE WHEN CB.valor_repasse_ajustado <> '0.00' THEN CB.valor_repasse_ajustado ELSE CB.valor_repasse END AS valor_parceiro_ajustado, 
                                    usuario AS responsavel, 21 AS `status`, CB.refund FROM conciliacao_sellercenter CB INNER JOIN conciliacao C ON C.lote = CB.lote WHERE C.lote = '$lote' and status_conciliacao NOT LIKE '%juridico%'
                            ) a
                            GROUP BY lote, id, store_id, seller_name, responsavel, `status`, status_conciliacao
                            UNION
                            SELECT lote, id, store_id, seller_name, valor_conectala_ajustado AS valor_conectala_ajustado, valor_parceiro_ajustado, responsavel, `status`, refund
                            FROM (SELECT CB.status_conciliacao, CB.lote, C.id, CB.store_id, CB.seller_name, 0 AS valor_conectala_ajustado, 
                            CASE WHEN CB.valor_repasse_ajustado <> '0.00' THEN CB.valor_repasse_ajustado ELSE CB.valor_repasse END AS valor_parceiro_ajustado, 
                            usuario AS responsavel, 21 AS `status`, CB.refund FROM conciliacao_sellercenter CB INNER JOIN conciliacao C ON C.lote = CB.lote WHERE C.lote = '$lote' and status_conciliacao LIKE '%juridico%'
                            ) a ";
                    }else{
                        $sql = "INSERT INTO repasse_temp (lote, conciliacao_id, store_id, name, valor_conectala, valor_seller, responsavel, status_repasse, refund)
                            SELECT lote, id, store_id, seller_name, SUM(valor_conectala_ajustado) AS valor_conectala_ajustado, SUM(valor_parceiro_ajustado) AS valor_parceiro_ajustado, responsavel, `status`, SUM(refund)
                             FROM (SELECT CB.status_conciliacao, CB.lote, C.id, CB.store_id, CB.seller_name, 0 AS valor_conectala_ajustado, 
                                    CASE WHEN CB.valor_repasse_ajustado <> '0.00' THEN CB.valor_repasse_ajustado ELSE CB.valor_repasse END AS valor_parceiro_ajustado, 
                                    usuario AS responsavel, 21 AS `status`, CB.refund FROM conciliacao_sellercenter CB INNER JOIN conciliacao C ON C.lote = CB.lote WHERE C.lote = '$lote'
                            ) a
                            GROUP BY lote, id, store_id, seller_name, responsavel, `status`, status_conciliacao";
                    }
                    

                    $repasse_temp_result = $this->db->query($sql);

                    $repasse_temp_id = $this->db->insert_id();

                    return $repasse_temp_result;

                }else{
                    return true;
                }
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    

    public function limpaTempRepasse($lote){

        $sql = "delete from repasse_temp where lote = '$lote'";
        return $this->db->query($sql);

    }

    public function limpaRepasse($lote){

        $sql = "delete from repasse where lote = '$lote'";
        return $this->db->query($sql);

    }

    public function cadastraRepasseFinal($lote){

         // Carrega primeiro uma versão já existente da conciliação
         $sql = "insert into repasse (lote, conciliacao_id, store_id, name, valor_conectala, valor_seller, responsavel, status_repasse, refund
					,legal_panel_id)
                 SELECT 
                     lote, conciliacao_id, store_id, name, valor_conectala, valor_seller, responsavel, status_repasse, refund,
                     legal_panel_id
                 from repasse_temp where lote = '$lote'";

        return $this->db->query($sql);

    }

    public function alterastatusrepassetemp($inputs){

        $sql = "update repasse_temp set status_repasse = case when status_repasse = 21 then 24 else 21 end where id = ".$inputs['id']." and lote = '".$inputs['lote']."'";
        return $this->db->query($sql);

    }

    public function extratopaymentforecast($relatorio = "resumo"){

        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " AND O.company_id = " . $this->data['usercomp'] : " AND O.store_id = " . $this->data['userstore']);

        $sqlResumo = "SELECT 	
                    marketplace,
                    origin, 
                    data_pagamento, 
                    SUM(expectativaReceb) AS expectativaReceb 
                FROM (
                    SELECT 
                        marketplace,
                        data_pagamento_conecta(id) AS data_pagamento,
                        origin,
                        expectativaReceb 
                    FROM (";
        $sqlDetalhado = "select A.*, data_pagamento_conecta(A.id) AS data_pagamento from ( ";
        $sql = "
                        SELECT 
                            O.id,
                            S.name as nome_loja,
                            SML.descloja AS marketplace,
                            O.numero_marketplace,
                            O.paid_status,
                            DATE_FORMAT(O.date_time,\"%d/%m/%Y\") AS data_pedido, 
                            DATE_FORMAT(O.data_entrega,\"%d/%m/%Y\") AS data_entrega,
                            O.gross_amount as valor_pedido,
                            round(O.gross_amount - O.total_ship,2) as valor_produto,
                            O.total_ship as valor_frete,
                            CASE WHEN O.origin IN ('MLC','H_ML','H_MLC') THEN 'ML' ELSE O.origin END AS origin,
                            CASE WHEN O.paid_status IN (90,95,97,98,99) THEN 
                                /*CASE WHEN UPPER(FR.ship_company) LIKE CONCAT(\"%\",CONCAT(UPPER(S.name),\"%\")) OR S.freight_seller_type > 0 OR O.freight_seller > 0 THEN
                                    CASE WHEN O.origin = 'B2W' THEN
                                        CASE WHEN date_time BETWEEN '2021-02-01 00:00:00' AND '2021-04-29 23:59:59' THEN
                                            ROUND(O.gross_amount - ( (O.gross_amount - O.total_ship) * (O.service_charge_rate/100) + 5 ),2) * -1
                                        ELSE
                                            ROUND(O.gross_amount - ( O.gross_amount * (O.service_charge_rate/100) ),2) * -1
                                        END
                                    ELSE
                                        ROUND(O.gross_amount - ( O.gross_amount * (O.service_charge_rate/100) ),2) * -1
                                    END
                                ELSE
                                    CASE WHEN O.origin = 'B2W' THEN
                                        CASE WHEN date_time BETWEEN '2021-02-01 00:00:00' AND '2021-04-30 23:59:59' THEN
                                            ROUND(O.gross_amount - ( (O.gross_amount - O.total_ship) * (O.service_charge_rate/100) + 5 ) - O.total_ship,2) * -1
                                        ELSE
                                            ROUND(O.gross_amount - ( O.gross_amount * (O.service_charge_rate/100) ) - O.total_ship,2) * -1
                                        END
                                    ELSE
                                        ROUND(O.gross_amount - ( O.gross_amount * (O.service_charge_rate/100) ) - O.total_ship,2) * -1
                                    END
                                END */
                                ROUND(O.gross_amount * -1,2) 
                            ELSE 
                                CASE WHEN UPPER(FR.ship_company) LIKE CONCAT(\"%\",CONCAT(UPPER(S.name),\"%\")) OR S.freight_seller_type > 0 OR O.freight_seller > 0 THEN
                                CASE WHEN O.origin = 'B2W' THEN
                                        CASE WHEN date_time BETWEEN '2021-02-01 00:00:00' AND '2021-04-29 23:59:59' THEN
                                                ROUND(O.gross_amount - ( (O.gross_amount - O.total_ship) * (O.service_charge_rate/100) + 5 ),2)
                                            ELSE
                                                ROUND(O.gross_amount - ( ((O.gross_amount - O.total_ship)  * (O.service_charge_rate/100)    +   ( O.total_ship  * (O.service_charge_freight_value/100)) ) ),2)
                                            END
                                        ELSE
                                            ROUND(O.gross_amount - ( ((O.gross_amount - O.total_ship)  * (O.service_charge_rate/100)    +   ( O.total_ship  * (O.service_charge_freight_value/100)) ) ),2)
                                        END
                                    ELSE
                                        CASE WHEN O.origin = 'B2W' THEN
                                            CASE WHEN date_time BETWEEN '2021-02-01 00:00:00' AND '2021-04-30 23:59:59' THEN
                                                ROUND(O.gross_amount - ( (O.gross_amount - O.total_ship) * (O.service_charge_rate/100) + 5 ) - O.total_ship,2)
                                            ELSE
                                                ROUND(O.gross_amount - O.total_ship - ( ((O.gross_amount - O.total_ship)  * (O.service_charge_rate/100)    +   ( O.total_ship  * (O.service_charge_freight_value/100)) ) ),2)
                                            END
                                        ELSE
                                            ROUND(O.gross_amount - O.total_ship - ( ((O.gross_amount - O.total_ship)  * (O.service_charge_rate/100)    +   ( O.total_ship  * (O.service_charge_freight_value/100)) ) ),2)
                                        END
                                END AS expectativaReceb
                        FROM orders O
                        INNER JOIN stores S ON S.id = O.store_id
                        LEFT JOIN (SELECT DISTINCT order_id, ship_company FROM freights) FR ON FR.order_id = O.id
                        INNER JOIN stores_mkts_linked SML ON SML.apelido = O.origin
                         WHERE O.data_entrega IS NOT NULL $more AND CONVERT(CONCAT(MONTH(O.data_entrega),YEAR(O.data_entrega)),CHAR) IN (
                         CONVERT(CONCAT( MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL -1 MONTH)), YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL -1 MONTH))),CHAR), 
                         CONVERT(CONCAT( MONTH(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 0 MONTH)), YEAR(DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 0 MONTH))),CHAR))";
                   
            $sqlResumo2 = " ) A) B  GROUP BY marketplace, origin, data_pagamento ORDER BY marketplace, origin, data_pagamento";

            $sqlDetalhado2 = ") A order by A.id";

        if($relatorio == "resumo"){
            $query = $this->db->query($sqlResumo.$sql.$sqlResumo2);
        }else{
            $query = $this->db->query($sqlDetalhado.$sql.$sqlDetalhado2);
        }
        return (false !== $query) ? $query->result_array() : false;

    }


    public function createConciliacaoSellerCenterGetData($inputs = array(), string $userName=null, $recadastroDeParcela = false)
    {

        if (!is_array($inputs['data_ciclo'])){
            $inputs['data_ciclo'] = [$inputs['data_ciclo']];
        }

        if (!$userName){
            $userName = $_SESSION['username'];
        }

        $frete_100_canal_seller_centers_vtex = $this->model_settings->getStatusbyName('frete_100_canal_seller_centers_vtex');
        $case_frete_100 = '';
        if($frete_100_canal_seller_centers_vtex == 1){
            $case_frete_100 = ' 
                WHEN O.service_charge_freight_value = 100 THEN 
                    CASE  WHEN S.freight_seller_type > 0 OR O.freight_seller > 0 THEN
                        ROUND(
                        	( O.gross_amount ) - ( ((O.gross_amount - O.total_ship) * ( O.service_charge_rate / 100)) + (O.total_ship * (0)) ) ';
			$case_frete_100 .= '                        	 
                        	,2)
                    else
                        ROUND(
                        	( O.gross_amount - O.total_ship ) - ( ((O.gross_amount - O.total_ship) * ( O.service_charge_rate / 100)) + (O.total_ship * (0)) ) ';

			$case_frete_100 .= '                        	 
                        	,2)
                    end
            ';
        }

        $join_orders_payment = "INNER";
        $accept_order_without_payment_data = $this->model_settings->getStatusbyName('accept_order_without_payment_data');
        if($accept_order_without_payment_data == 1){
            $join_orders_payment = "LEFT";
        }

        //caso seja Grupo Soma passa um parâmetro diferente para a conciliação
        $valorGsoma = $this->model_settings->getSettingDatabyNameEmptyArray('gsoma_painel_financeiro');
        $conciliacao = "sellercenter";

        if($valorGsoma){
            if($valorGsoma['status'] == 1){
                $conciliacao = "gsoma";
            }
        }

        $arrayAnoMes = $this->ajustaanomes($inputs['txt_ano_mes']);

        $wherePedido = '';
        if (isset($inputs['id_pedido']) && $inputs['id_pedido']){
            $wherePedido = " AND O.id = {$inputs['id_pedido']}";
        }else{
			$this->atualizadataspagamentoconciliacao($arrayAnoMes);
        }


        if($recadastroDeParcela == false){
            $whereRestricaoCadastroCiclo        = " AND O.id not in (select distinct order_id from conciliacao_sellercenter where lote =  '".$inputs['hdnLote']."') 
                                                    and O.id not in (select distinct CS.order_id from conciliacao_sellercenter CS inner join conciliacao C on C.lote = CS.lote where status_conciliacao = 'Conciliação Ciclo') ";
            $whereRestricaoCadastroCancelamento = " and O.id not in (select distinct CS.order_id from conciliacao_sellercenter CS inner join conciliacao C on C.lote = CS.lote where status_conciliacao = 'Conciliação Cancelamento')";
        }else{
            $whereRestricaoCadastroCiclo        = "";
            $whereRestricaoCadastroCancelamento = "";
        }


        $sql = "SELECT * FROM (SELECT 
                '".$inputs['hdnLote']."' AS lote,
                S.id AS id_loja,
                S.name AS nome_loja,
                S.cnpj,
                O.paid_status,
                O.id AS id_pedido,
                O.numero_marketplace,
                O.date_time AS data_pedido,
                O.data_entrega,
                cast(DATE_FORMAT(OPDATE.data_pagamento_marketplace, '%d/%m/%Y') as char) AS data_ciclo,
                'Conciliação Ciclo' AS status_conciliacao,";

        if($conciliacao == "sellercenter"){

        	$sql .= "O.gross_amount AS valor_pedido,
                ROUND(O.gross_amount - O.total_ship,2) AS valor_produto,
                O.total_ship AS valor_frete,
                ROUND(O.service_charge_rate,2) AS valor_percentual_produto,
                ROUND(O.service_charge_freight_value,2) AS valor_percentual_frete,
                ROUND( (O.gross_amount - O.total_ship) * ( O.service_charge_rate / 100) ,2) AS valor_comissao_produto,
                ROUND( O.total_ship * ( O.service_charge_freight_value / 100) ,2) AS valor_comissao_frete,
                ROUND( ((O.gross_amount - O.total_ship) * ( O.service_charge_rate / 100)) + (O.total_ship * ( O.service_charge_freight_value / 100)) ,2) AS valor_comissao,
                CASE ".$case_frete_100."
                WHEN S.freight_seller_type > 0 OR O.freight_seller > 0 THEN
                    ROUND(
                    	( O.gross_amount ) - ( ((O.gross_amount - O.total_ship) * ( O.service_charge_rate / 100)) + (O.total_ship * ( O.service_charge_freight_value / 100)) ) ";

			$sql .= "                    	 
						 ,2)
                else ";

			//BUGS-2397 - Alterado a regra de calculo que estava somando o frete no desconto do repasse deixando como PRODUTO - (TAXA PRODUTO + TAXA FRETE)
			$sql .= " ROUND(
						(O.gross_amount - O.total_ship)  - ((O.gross_amount - O.total_ship) * (O.service_charge_rate / 100)  +  ( (O.total_ship * (O.service_charge_freight_value / 100)))) ";

			$sql .= "							
						,2)  end AS valor_repasse,";
        }else{

        	$sql .= "O.net_amount AS valor_pedido,
                ROUND(O.net_amount - O.total_ship,2) AS valor_produto,
                O.total_ship AS valor_frete,
                ROUND(O.service_charge_rate,2) AS valor_percentual_produto,
                ROUND(O.service_charge_freight_value,2) AS valor_percentual_frete,
                ROUND( (O.net_amount - O.total_ship) * ( O.service_charge_rate / 100) ,2) AS valor_comissao_produto,
                ROUND( O.total_ship * ( O.service_charge_freight_value / 100) ,2) AS valor_comissao_frete,
                ROUND( ((O.service_charge_freight_value/100) * O.total_ship) + ((O.service_charge_rate/100) * (O.gross_amount - O.total_ship - O.discount))  ,2) AS valor_comissao,
                ROUND( 
                		(O.gross_amount - O.total_ship - O.discount) - ( ((O.service_charge_freight_value/100) * O.total_ship) + ((O.service_charge_rate/100) * (O.gross_amount - O.total_ship - O.discount)) ) ";

			$sql .= "		
					,2) AS valor_repasse,";
        }

        $sql .= "'".$userName."' AS usuario,
                PO.forma_desc,
                CONCAT(LEFT(PO.first_digits,4),CONCAT('...',LEFT(PO.last_digits,4))) AS digitos_cartao,
                CASE WHEN DTU.data_usada = \"Data Envio\" THEN O.data_mkt_sent ELSE O.data_mkt_delivered END AS data_report
                
                #,cvo.total_pricetags
                
                FROM orders O
                INNER JOIN stores S ON S.id = O.store_id
                ".$join_orders_payment." JOIN orders_payment PO on PO.order_id = O.id
                ".$join_orders_payment." JOIN (SELECT MAX(id) AS id, order_id FROM orders_payment GROUP BY order_id) OPD ON OPD.id = PO.id
                INNER JOIN (SELECT DISTINCT MAX(PMC.data_usada) AS data_usada, apelido FROM `stores_mkts_linked` SML INNER JOIN `param_mkt_ciclo` PMC ON PMC.integ_id = SML.id_mkt GROUP BY apelido) DTU ON DTU.apelido = O.origin
                INNER JOIN orders_payment_date OPDATE ON (OPDATE.order_id = O.id)
                
                left join campaign_v2_orders cvo on cvo.order_id = O.id
                
                WHERE 
                    CONVERT(CONCAT(MONTH(OPDATE.data_pagamento_marketplace),YEAR(OPDATE.data_pagamento_marketplace)),CHAR) IN (".implode(",",$arrayAnoMes).")
                    $whereRestricaoCadastroCiclo
                    $wherePedido 
                UNION
                SELECT 
                '".$inputs['hdnLote']."' AS lote,
                S.id AS id_loja,
                S.name AS nome_loja,
                S.cnpj,
                O.paid_status,
                O.id AS id_pedido,
                O.numero_marketplace,
                O.date_time AS data_pedido,
                O.data_entrega,
                cast(DATE_FORMAT(OPDATE.data_cancelamento_marketplace, '%d/%m/%Y') as char) AS data_ciclo,
                'Conciliação Cancelamento' AS status_conciliacao,
                O.gross_amount AS valor_pedido,
                ROUND(O.gross_amount - O.total_ship,2) AS valor_produto,
                O.total_ship AS valor_frete,
                ROUND(O.service_charge_rate,2) AS valor_percentual_produto,
                ROUND(O.service_charge_freight_value,2) AS valor_percentual_frete,
                ROUND( (O.gross_amount - O.total_ship) * ( O.service_charge_rate / 100) ,2) AS valor_comissao_produto,
                ROUND( O.total_ship * ( O.service_charge_freight_value / 100) ,2) AS valor_comissao_frete,
                ROUND( ((O.gross_amount - O.total_ship) * ( O.service_charge_rate / 100)) + (O.total_ship * ( O.service_charge_freight_value / 100)) ,2) AS valor_comissao,
                case when co.commission_charges_attribute_value = 1 then
                    ROUND( 
                        ((O.gross_amount - O.total_ship) * ( O.service_charge_rate / 100)) + (O.total_ship * ( O.service_charge_freight_value / 100)) ";

            $sql .= "
                        ,2) * -1 
                else 0 end AS valor_repasse,
                '".$userName."' AS usuario,
                PO.forma_desc,
                CONCAT(LEFT(PO.first_digits,4),CONCAT('...',LEFT(PO.last_digits,4))) AS digitos_cartao,
                O.date_cancel AS data_report 
                
                #,cvo.total_pricetags
                
                FROM orders O
                INNER JOIN stores S ON S.id = O.store_id
                ".$join_orders_payment." JOIN orders_payment PO on PO.order_id = O.id
                ".$join_orders_payment." JOIN (SELECT MAX(id) AS id, order_id FROM orders_payment GROUP BY order_id) OPD ON OPD.id = PO.id
                INNER JOIN orders_payment_date OPDATE ON (OPDATE.order_id = O.id)
                left join canceled_orders co on co.order_id = O.id
                left join campaign_v2_orders cvo on cvo.order_id = O.id
                
                WHERE CONVERT(CONCAT(MONTH(O.date_cancel),YEAR(O.date_cancel)),CHAR) IN (".implode(",",$arrayAnoMes).")  AND O.id not in (select distinct order_id from conciliacao_sellercenter where lote =  '".$inputs['hdnLote']."') 
                    $whereRestricaoCadastroCancelamento
                    $wherePedido
                ) A
                WHERE data_ciclo IN (".implode(",",$inputs['data_ciclo']).")";

                $query = $this->db->query($sql);
                
                return $query->result_array();

    }

    /**
     * @param $inputs
     * @return array
     */
    public function generateInstallmentsByInputs($inputs, $setting_api_comission=null, $userName=null, $recadastroDeParcela = false): array
    {
        $gera_conciliacao_data = $this->createConciliacaoSellerCenterGetData($inputs, $userName,$recadastroDeParcela);

        $geraConciliacao = [];
        $conciliation_array = [];

		if (!empty($gera_conciliacao_data))
		{
			$this->load->model('model_campaigns_v2');
			$this->load->model('model_orders_conciliation_installments');
		}

        foreach ($gera_conciliacao_data as $conciliation_data) {

			//Limpando dados antigos antes de continuar
            $this->model_orders_conciliation_installments->clearOldData($conciliation_data['id_pedido'], $inputs['hdnLote']);

            $campaigns_data = $this->model_campaigns_v2->getCampaignsTotalsByOrderId($conciliation_data['id_pedido']);
            $campaigns_seller = (!empty($campaigns_data)) ? $campaigns_data['total_seller'] : 0;
            $campaigns_mktplace = (!empty($campaigns_data)) ? $campaigns_data['total_channel'] : 0;
            $campaigns_rebate = (!empty($campaigns_data)) ? $campaigns_data['total_rebate'] : 0;
            $campaigns_comission_reduction = (!empty($campaigns_data)) ? $campaigns_data['comission_reduction'] : 0;
            $campaigns_comission_reduction_products = (!empty($campaigns_data)) ? $campaigns_data['comission_reduction_products'] : 0;
            $refund = 0;

            $comission = $conciliation_data['valor_percentual_produto'];
            // $new_comission = $this->model_campaigns_v2->getNewComissionByOrder($conciliation_data['id_pedido'])['new_comission'];

            if ($campaigns_mktplace > 0) {
                $valor_repasse = $conciliation_data['valor_repasse'] + $campaigns_comission_reduction + ($campaigns_mktplace - ($campaigns_mktplace * ($comission / 100)));
            } else {
                $valor_repasse = $conciliation_data['valor_repasse'] + $campaigns_comission_reduction;
            }

            $valor_repasse += $campaigns_rebate;

            if ($campaigns_mktplace > 0) {
                $valor_comissao = $conciliation_data['valor_comissao'] - abs($campaigns_comission_reduction + ($campaigns_mktplace - ($campaigns_mktplace * ($comission / 100))));
            } else {
                $valor_comissao = $conciliation_data['valor_comissao'] - $campaigns_comission_reduction;
            }

            if ($setting_api_comission != "1") {
                $refund = abs($valor_repasse - $conciliation_data['valor_repasse']);
            } else {
                $refund = abs($valor_repasse - $conciliation_data['valor_repasse'] - $campaigns_comission_reduction_products);
            }

            if ($this->orders_precancelled_to_zero && $conciliation_data['paid_status'] == '96'
            && $conciliation_data['status_conciliacao'] == 'Conciliação Cancelamento') //cancelado pre
            {
                $conciliation_data['valor_comissao_produto'] = 0;
                $conciliation_data['valor_comissao_frete'] = 0;
                $conciliation_data['valor_repasse'] = 0;
                $valor_comissao = 0;
                $valor_repasse = 0;
            }

            //Alteração pelo estorno de comissão se o pedido não tem cobrança de comissão ele força a ser zero, mesmo cancelado
            if (in_array($conciliation_data['paid_status'], [95,97,98,99])
                && $conciliation_data['status_conciliacao'] == 'Conciliação Cancelamento'
                && $conciliation_data['valor_repasse'] == 0) //cancelado pre
            {
                $valor_comissao = $conciliation_data['valor_comissao'];
                $valor_repasse = $conciliation_data['valor_repasse'];
            }

             //Alteração pelo estorno de comissão se o pedido não tem cobrança de comissão ele força a ser zero, mesmo cancelado
             if (in_array($conciliation_data['paid_status'], [95,97,98,99])
                && $conciliation_data['status_conciliacao'] == 'Conciliação Cancelamento'
                && $conciliation_data['valor_repasse'] != 0
                && $this->model_settings->getValueIfAtiveByName('cancellation_commission_calculate_campaign')) //cancelado pre
            {
                // o valor de comissão está sendo diminuido pois o valor do repasse é negativo, e com isso a conta vira + ao invés de -
                $valor_comissao = round($conciliation_data['valor_comissao'] + $campaigns_mktplace * ($comission / 100),2);
                $valor_repasse = round($conciliation_data['valor_repasse'] - $campaigns_mktplace * ($comission / 100),2);
            }

            $conciliation_array = array(
                'lote' => $conciliation_data['lote'],
                'store_id' => $conciliation_data['id_loja'],
                'seller_name' => $conciliation_data['nome_loja'],
                'order_id' => $conciliation_data['id_pedido'],
                'numero_marketplace' => $conciliation_data['numero_marketplace'],
                'data_pedido' => $conciliation_data['data_pedido'],
                'data_entrega' => $conciliation_data['data_entrega'],
                'data_ciclo' => $conciliation_data['data_ciclo'],
                'status_conciliacao' => $conciliation_data['status_conciliacao'],
                'valor_pedido' => $conciliation_data['valor_pedido'],
                'valor_produto' => $conciliation_data['valor_produto'],
                'valor_frete' => $conciliation_data['valor_frete'],
                'valor_percentual_produto' => $conciliation_data['valor_percentual_produto'],
                'valor_percentual_frete' => $conciliation_data['valor_percentual_frete'],
                'valor_comissao_produto' => $conciliation_data['valor_comissao_produto'],
                'valor_comissao_frete' => $conciliation_data['valor_comissao_frete'],
                'valor_comissao' => $valor_comissao,
                'valor_repasse' => $conciliation_data['valor_repasse'],
                'valor_repasse_ajustado' => $valor_repasse,
                'usuario' => $conciliation_data['usuario'],
                'tipo_pagamento' => $conciliation_data['forma_desc'],
                'refund' => $refund,
                'digitos_cartao' => $conciliation_data['digitos_cartao'],
                'cnpj' => $conciliation_data['cnpj'],
                'data_report' => $conciliation_data['data_report']

            );

            $this->model_orders_conciliation_installments->insertInstallmentsByConciliationData($conciliation_array);

        }
        return array($geraConciliacao, $conciliation_array);
    }

    public function atualizadataspagamentoconciliacao($arrayAnoMes){

        $sql = "select data_pagamento_marketplace(id) as data_pagamento_marketplace, data_pagamento_conecta(id) as data_pagamento_conecta, data_cancelamento_marketplace(id) as data_cancelamento_marketplace
                from orders O
                WHERE CONVERT(CONCAT(LPAD(MONTH(O.date_cancel),2,0),YEAR(O.date_cancel)),CHAR) IN (".implode(",",$arrayAnoMes).") OR
                CONVERT(CONCAT(LPAD(MONTH(O.data_mkt_sent),2,0),YEAR(O.data_mkt_sent)),CHAR) IN (".implode(",",$arrayAnoMes).") OR
                CONVERT(CONCAT(LPAD(MONTH(O.data_mkt_delivered),2,0),YEAR(O.data_mkt_delivered)),CHAR) IN (".implode(",",$arrayAnoMes).")";
        
        $query = $this->db->query($sql);
            
        if($query->result_array()){
            return true;
        }else{
            return false;
        }

    }

    public function createConciliacaoSellerCenter($inputs = array(), string $userName=null, $geraConciliacaoData = null)
    {
        if (empty($geraConciliacaoData) || !is_array($geraConciliacaoData))
            return false;

        if (!$userName)
            $userName = $_SESSION['username'];

        // $arrayAnoMes = $this->ajustaanomes($inputs['txt_ano_mes']);

        if (is_array($inputs['data_ciclo'])){
            foreach ($inputs['data_ciclo'] as &$data) {
                if(strlen($data['data_ciclo']) < 10){
                    $data['data_ciclo'] = "0".$data['data_ciclo'];
                }
            }
        }

        $this->db->insert('conciliacao_sellercenter', $geraConciliacaoData);
        $order_id = $this->db->insert_id();
        // return ($order_id) ? $order_id : false;
        
        // if ($this->db->query($sql))
        if ($order_id)
            return true;
        else
            return false;
    }

    public function createConciliacaoSellerCenterPainelLegal($inputs = array(), string $userName=null){

        if (!$userName){
            $userName = $_SESSION['username'];
        }

        $join_orders_payment = "INNER";
        $accept_order_without_payment_data = $this->model_settings->getStatusbyName('accept_order_without_payment_data');
        if($accept_order_without_payment_data == 1){
            $join_orders_payment = "LEFT";
        }

        $arrayAnoMes = $this->ajustaanomes($inputs['txt_ano_mes']);

        $sqlInsertOrder = "SELECT DISTINCT '".$inputs['hdnLote']."' AS lote,
                                    S.id AS id_loja,
                                    S.name AS nome_loja,
                                    S.cnpj,
                                    O.id AS id_pedido,
                                    O.numero_marketplace,
                                    O.date_time AS data_pedido,
                                    O.data_entrega,
                                    data_cancelamento_marketplace(O.id) AS data_ciclo,
                                    case when LP.notification_type = 'order' and LP.notification_id = 'Estorno de comissão Cobrada' and LP.notification_title = 'Estorno de comissão Cobrada' then
                                        'Conciliação Estorno de Comissão'
                                    else
                                        'Conciliação Painel Jurídico' 
                                    end AS status_conciliacao,
                                    O.gross_amount AS valor_pedido,
                                    ROUND(O.gross_amount - O.total_ship,2) AS valor_produto,
                                    O.total_ship AS valor_frete,
                                    ROUND(O.service_charge_rate,2) AS valor_percentual_produto,
                                    ROUND(O.service_charge_freight_value,2) AS valor_percentual_frete,
                                    ROUND( (O.gross_amount - O.total_ship) * ( O.service_charge_rate / 100) ,2) AS valor_comissao_produto,
                                    ROUND( O.total_ship * ( O.service_charge_freight_value / 100) ,2) AS valor_comissao_frete,
                                    ROUND( ((O.gross_amount - O.total_ship) * ( O.service_charge_rate / 100)) + (O.total_ship * ( O.service_charge_freight_value / 100)) ,2) AS valor_comissao,
                                    ROUND( LP.balance_debit ,2) * -1 AS valor_repasse,
                                    '".$userName."' AS usuario,
                                    PO.forma_desc,
                                    LP.id
                                FROM orders O
                                INNER JOIN stores S ON S.id = O.store_id
                                ".$join_orders_payment." JOIN orders_payment PO on PO.order_id = O.id
                                ".$join_orders_payment." JOIN (SELECT MAX(id) AS id, order_id FROM orders_payment GROUP BY order_id) OPD ON OPD.id = PO.id
                                INNER JOIN legal_panel LP ON LP.orders_id = O.id AND LP.status = 'Chamado Aberto'
                                WHERE O.id not in (select distinct order_id from conciliacao_sellercenter where lote =  '".$inputs['hdnLote']."' and status_conciliacao in ('Conciliação Painel Jurídico', 'Conciliação Estorno de Comissão'))";

        $sqlInsertStore = "SELECT DISTINCT '".$inputs['hdnLote']."' AS lote,
                            S.id AS id_loja,
                            S.NAME AS nome_loja,
                            S.cnpj,
                            '0' AS id_pedido,
                            'painel_juridico_outros',
                            now() AS data_pedido,
                            '' AS data_entrega,
                            '' AS data_ciclo,
                            'Conciliação Painel Jurídico' AS status_conciliacao,
                            LP.balance_paid AS valor_pedido,
                            '0' AS valor_produto,
                            '0' AS valor_frete,
                            '0' AS valor_percentual_produto,
                            '0' AS valor_percentual_frete,
                            '0' AS valor_comissao_produto,
                            '0' AS valor_comissao_frete,
                            '0' AS valor_comissao,
                            ROUND( LP.balance_debit, 2 ) * - 1 AS valor_repasse,
                            '".$userName."' AS usuario,
                            '' AS form_pagamento,
                            LP.id
                        FROM legal_panel LP
                            INNER JOIN stores S ON S.id = LP.store_id
                            AND LP.STATUS = 'Chamado Aberto' 
                        WHERE S.id NOT IN ( SELECT DISTINCT store_id FROM conciliacao_sellercenter WHERE lote = '".$inputs['hdnLote']."' AND status_conciliacao = 'Conciliação Painel Jurídico'  AND order_id = 0 )";

        //Gravando itens de pedidos
        $sql = "INSERT INTO conciliacao_sellercenter (lote,store_id,seller_name, cnpj,order_id,numero_marketplace,data_pedido,data_entrega,data_ciclo,
                                                        status_conciliacao,valor_pedido,valor_produto,valor_frete,valor_percentual_produto,valor_percentual_frete,
                                                        valor_comissao_produto,valor_comissao_frete,valor_comissao,valor_repasse,usuario,tipo_pagamento,legal_panel_id)
                SELECT * FROM ($sqlInsertOrder) A 
                ";

        $result1 = $this->db->query($sql);

        //Gravando itens de lojas
        $sql = "INSERT INTO conciliacao_sellercenter (lote,store_id,seller_name, cnpj,order_id,numero_marketplace,data_pedido,data_entrega,data_ciclo,
                                                        status_conciliacao,valor_pedido,valor_produto,valor_frete,valor_percentual_produto,valor_percentual_frete,
                                                        valor_comissao_produto,valor_comissao_frete,valor_comissao,valor_repasse,usuario,tipo_pagamento,legal_panel_id)
                SELECT * FROM ($sqlInsertStore) A 
                ";

        $result2 = $this->db->query($sql);

        return $result1 || $result2;

    }

    public function getConciliacaoConecta($lote = null){
        
        $pedidos = [];

        if($lote == null){
            return array();
        }else{
            
            $sql = "SELECT * FROM conciliacao_manual cm WHERE cm.lote = ? AND numero_pedido IS NOT NULL";
            $conciliacao = $this->db->query($sql, array($lote))->result();
            if(!$conciliacao){
                return [];
            }
            foreach($conciliacao as $c){

                $idconciliacao = $c->id;
                $marketplace = $c->marketplace;
                $valor_parceiro = $c->valor_parceiro;
                $numero_marketplace = $c->numero_pedido;                
                $num_mkt = explode("-", $numero_marketplace);
                $num_mkt = $num_mkt[1];
                

                $sql = "SELECT * FROM orders o WHERE origin LIKE '".$this->db->escape_like_str($marketplace)."' AND o.numero_marketplace LIKE '%" .$this->db->escape_like_str($num_mkt)."%' ESCAPE '!'";
                $pedido = $this->db->query($sql)->row();
                if($pedido){    
                    $data = [
                        'order_id' => $pedido->id,
                        'numero_marketplace' => $num_mkt,
                        'data_split' => $pedido->data_pago,                        
                        'conciliacao_id' => $idconciliacao,
                        'valor_parceiro' => $valor_parceiro,                        
                    ];
                    array_push($pedidos, $data);
                }
                            
                $this->db->where('lote', $lote);
                $this->db->update('conciliacao', ['status_repasse' => 23]);

            }            
            
        }

        return $pedidos;

    }

    public function getConciliacaoSellerCenter($lote = null, $campos = null, int $storeId = null, $limit = null, $offset = null, $filtros = null, $storeKey = null){

        if($lote == null){
            return array();
        }else{
            $select = "";
            $group = "";
            $where = "";
            $camposSelect = "o.paid_status, 
            CS.*, 
            round(CS.valor_produto-valor_comissao_produto,2) as valor_repasse_produto, 
            round(CS.valor_frete - CS.valor_comissao_frete,2) as valor_repasse_frete, 
            atf.valor_antecipado,
            cpv2o.comission_reduction,
            cpv2o.comission_reduction_products,
            cpv2o.discount_comission,
            cpv2o.comission_reduction_marketplace,
            cpv2o.total_pricetags,
            cpv2o.total_campaigns,
            cpv2o.total_channel,
            cpv2o.total_seller,
            cpv2o.total_promotions,
            cpv2o.total_rebate";
            
                switch ($campos) {
                    case "pedidos":
                        $select = "select count(*) as qtd from ( ";
                        $where = " ";
                        // $where = " AND tratado = 1";
                        $group = ") a";
                        $camposSelect = " CS.order_id";
                        break;
                    case "valores":
                        $select = "select round(sum(valor_pedido),2) as qtd from ( ";
                        $where = " ";
                        // $where = " AND tratado = 1";
                         $group = ") a";
                        $camposSelect = " valor_pedido";
                        break;
                    case "cancelados":
                        $select = "select count(*) as qtd from ( ";
                        $group = " and CS.status_conciliacao = 'Conciliação Cancelamento') a";
                        $where = " ";
                        // $where = " AND tratado = 1";
                        $camposSelect = " CS.order_id";
                        break;
                    case "cancelados_valores":
                        $select = "select ifnull(round(sum(valor_pedido),2),0) as qtd from ( ";
                        $where = " ";
                        // $where = " AND tratado = 1";
                        $group = " and CS.status_conciliacao = 'Conciliação Cancelamento') a";
                        $camposSelect = " valor_pedido";
                        break; 
                    case "sellers":
                        $select = "select count(*) as qtd from ( ";
                        $group = " ) a";
                        $camposSelect = " distinct CS.store_id";
                        break; 
                    case "refund":
                        $select = "";
                        $group = "";
                        $camposSelect = " sum(refund) as total_refund";
                        break;
                    default:
                        $select = "";
                        $group = "";
                        $where = "";
                        $camposSelect = "o.paid_status, CS.*, 
                        round(CS.valor_produto - CS.valor_comissao_produto,2) as valor_repasse_produto, 
                        round(CS.valor_frete - valor_comissao_frete,2) as valor_repasse_frete, 
                        atf.valor_antecipado,
                        cpv2o.comission_reduction,
                        cpv2o.comission_reduction_products,
                        cpv2o.discount_comission,
                        cpv2o.comission_reduction_marketplace,
                        cpv2o.total_pricetags,
                        cpv2o.total_campaigns,
                        cpv2o.total_channel,
                        cpv2o.total_seller,
                        cpv2o.total_promotions,
                        cpv2o.total_rebate,
                        sml.descloja";
                        break;
                }

            $sql = "select $camposSelect from conciliacao_sellercenter CS left join orders o on CS.order_id = o.id  ";
            $sql.= " LEFT JOIN stores_mkts_linked sml ON o.origin = sml.apelido";
            $sql.= " LEFT JOIN anticipation_transfer atf ON atf.order_id = o.id ";
            $sql.= " LEFT JOIN campaign_v2_orders cpv2o ON cpv2o.order_id = o.id ";
            $sql.= " WHERE CS.lote = ".$this->db->escape($lote)." $where";

            //Esconder pedidos cancelado pré
            if (!$this->model_settings->getValueIfAtiveByName('display_pre_canceled_orders_in_financial_reports')){
                $sql.= " AND ifnull(o.paid_status,0) <> 96 ";
            }

            if ($storeId){
                $sql.= " AND CS.store_id = $storeId";
            }

            if ($storeKey){
                $sql.= " AND CS.store_id = $storeKey";
            }

            if($filtros){
                if(is_array($filtros)){
                    
                    if(array_key_exists('txt_numero_pedido',$filtros)){
                        if( $filtros['txt_numero_pedido'] ){
                            $sql.= " AND CS.numero_marketplace = '".$filtros['txt_numero_pedido']."'";
                        }
                    }

                    if(array_key_exists('slc_loja',$filtros)){
                        if( $filtros['slc_loja'] ){
                            $sql.= " AND CS.store_id = '".$filtros['slc_loja']."'";
                        }
                    }

                    if(array_key_exists('slc_status_pedido',$filtros)){
                        if($filtros['slc_status_pedido']){
                            $sql.= " AND CS.status_conciliacao = '".$filtros['slc_status_pedido']."'";
                        }
                    }

                }
            }

            if (!is_null($limit) && !is_null($offset)){
                $sql .= " LIMIT $limit OFFSET $offset";
            }

            $query = $this->db->query($select.$sql.$group);
            return (false !== $query) ? $query->result_array() : false;
        }
    }
    
    public function ajustaanomes($anoMes){

        $aux = explode("-", $anoMes);

        $arraySaida = array();

        if($aux[0] == 12){
            $arraySaida[1] = ($aux[0]-1).$aux[1];
            $arraySaida[2] = str_replace("-","",$anoMes);
            $arraySaida[3] = "1".($aux[1]+1);
        }elseif($aux[0] == 1){
            $arraySaida[1] = "12".($aux[1]-1);
            $arraySaida[2] = str_replace("-","",$anoMes);
            $arraySaida[3] = ($aux[0]+1).$aux[1];
        }else{
            $arraySaida[1] = ($aux[0]-1).$aux[1];
            $arraySaida[2] = str_replace("-","",$anoMes);
            $arraySaida[3] = ($aux[0]+1).$aux[1];
        }

        if(strlen($arraySaida[1]) < 6){
            $arraySaida[1] = "0".$arraySaida[1];
        }
        if(strlen($arraySaida[2]) < 6){
            $arraySaida[2] = "0".$arraySaida[2];
        }
        if(strlen($arraySaida[3]) < 6){
            $arraySaida[3] = "0".$arraySaida[3];
        }
        return $arraySaida;

    }

    public function salvaObservacaosellercenter($input)
    {

        $data = array(
            'observacao' => $input['txt_observacao']            
        );

        $this->db->where('lote', $input['hdnLote']);
        $this->db->where('id', $input['txt_hdn_pedido_obs']);
        return $this->db->update('conciliacao_sellercenter', $data);

    }
    
    public function incluiremovepedidoconciliacaosellercenter($input)
    {

        $sql = "update conciliacao_sellercenter set tratado = case when tratado = 1 then 0 else 1 end
        WHERE lote = ? AND id = ? ";

		return $this->db->query($sql, [$input['hdnLote'], $input['id']]);

    }
    
    public function alteracomissaosellercenter($input)
    {

        $sql = "update conciliacao_sellercenter set valor_repasse_ajustado = round(?,2), valor_repasse = round(?,2)
        WHERE lote = ? AND id = ? ";

		return $this->db->query($sql, [$input['txt_comissao'],$input['txt_comissao'], $input['hdnLote'], $input['txt_hdn_pedido_comissao']]);

    }

    public function buscaobservacaopedidosellercenter($lote, $num_pedido, $tipo = null)
    {

        if ($tipo <> null) {
            $campos = 'numero_marketplace as num_pedido, observacao, DATE_FORMAT(data_criacao, \'%d-%m-%Y\') as data_criacao';
        } else {
            $campos = '*';
        }

        $sql = "SELECT $campos FROM `conciliacao_sellercenter` where id = '$num_pedido' and lote = '$lote'";

        $query = $this->db->query($sql);
        return (false !== $query) ? $query->result_array() : false;

    }

    public function atualizaValorConciliacaoSellerCenterArquivoMovelo($input, $arquivo){
        //echo'<pre>';print_r($arquivo);echo'<br>'.$input['hdnLote'];
        $data = array(
            'valor_repasse_ajustado' => str_replace( "\"", "", utf8_encode($arquivo[3])),
            'valor_repasse' => str_replace( "\"", "", utf8_encode($arquivo[3])),
            'observacao' => str_replace( "\"", "", utf8_encode($arquivo[4]))
        );
        $this->db->where('numero_marketplace', $arquivo[1]);
        $this->db->where('status_conciliacao', str_replace("Repasse", "Conciliação", $arquivo[2]));
        $this->db->where('lote', $input['hdnLote']);
        $update = $this->db->update('conciliacao_sellercenter', $data);
        
	    return $update;

    }

    public function insertIuguRepasse($inputs)
    {

        $data['order_id'] = $inputs['order_id'];
        $data['numero_marketplace'] = $inputs['numero_marketplace'];
        $data['data_split'] = $inputs['data_split'];
        $data['data_transferencia'] = $inputs['data_transferencia'];
        $data['data_repasse_conta_corrente'] = $inputs['data_repasse_conta_corrente'];
        $data['conciliacao_id'] = $inputs['conciliacao_id'];
        $data['valor_parceiro'] = $inputs['valor_parceiro'];
        $data['valor_afiliado'] = $inputs['valor_afiliado'];
        $data['valor_produto_conectala'] = $inputs['valor_produto_conectala'];
        $data['valor_frete_conectala'] = $inputs['valor_frete_conectala'];
        $data['valor_repasse_conectala'] = $inputs['valor_repasse_conectala'];

        $insert = $this->db->insert('iugu_repasse', $data);
        $order_id = $this->db->insert_id();
        return ($order_id) ? $order_id : false;

    }

    public function insertIuguRepasseFiscal($inputs)
    {

        $data['order_id'] = $inputs['order_id'];
        $data['numero_marketplace'] = $inputs['numero_marketplace'];
        $data['data_split'] = $inputs['data_split'];
        $data['data_transferencia'] = $inputs['data_transferencia'];
        $data['data_repasse_conta_corrente'] = $inputs['data_repasse_conta_corrente'];
        $data['conciliacao_fiscal_id'] = $inputs['conciliacao_fiscal_id'];
        $data['valor_parceiro'] = $inputs['valor_parceiro'];
        $data['valor_afiliado'] = $inputs['valor_afiliado'];
        $data['valor_produto_conectala'] = $inputs['valor_produto_conectala'];
        $data['valor_frete_conectala'] = $inputs['valor_frete_conectala'];
        $data['valor_repasse_conectala'] = $inputs['valor_repasse_conectala'];

        $insert = $this->db->insert('iugu_repasse', $data);
        $order_id = $this->db->insert_id();
        return ($order_id) ? $order_id : false;

    }

    public function iugurepassecheck($conciacao_id){

        $sql = "select count(*) as qtd from iugu_repasse where conciliacao_id = ".$conciacao_id;

        $query = $this->db->query($sql);
        $saida = $query->result_array();
        return $saida[0]['qtd'];


    }

    public function iugurepassecheckfiscal($conciacao_id){

        $sql = "select count(*) as qtd from iugu_repasse where conciliacao_fiscal_id = ".$conciacao_id;

        $query = $this->db->query($sql);
        $saida = $query->result_array();
        return $saida[0]['qtd'];


    }


    public function getConciliacaoOrdersIds($lote = null, $conciliation_table = null, $conciliation_table_orderid = null)
    {
        if(empty($lote) || empty($conciliation_table) || empty($conciliation_table_orderid))
            return false;

        $sql = "select ".$conciliation_table_orderid." as order_id from ".$conciliation_table." where lote = '".$lote."'";

        $query = $this->db->query($sql);
        $result = $query->result_array();
        return $result;
    }

    public function getConciliationsAlreadyDebited(int $legalPanelId): array
    {

        $sql = "SELECT 	C.id,
                    DATE_FORMAT(C.data_criacao, '%d/%m/%Y') AS data_criacao,
                    CONCAT('Ciclo: ',CONCAT(C.ano_mes,CONCAT(' - Dia pagamento: ',PMC.data_pagamento))) AS ciclo_conciliacao,
                    CS.usuario,
                    CASE WHEN valor_repasse_ajustado <> '0.00' THEN CS.valor_repasse_ajustado ELSE CS.valor_repasse END AS valor_repasse
                FROM conciliacao C
                INNER JOIN conciliacao_sellercenter CS ON CS.lote = C.lote
                INNER JOIN `param_mkt_ciclo` PMC ON PMC.id = C.param_mkt_ciclo_id
                WHERE CS.legal_panel_id = $legalPanelId";

        $query = $this->db->query($sql);
        $result = $query->result_array();

        return $result;

    }
    
    public function getConciliacaoPagaFlag($lote)
    {

        $sql = "SELECT 	count(*) as qtd
                FROM iugu_repasse IU
                inner join conciliacao C on C.id = IU.conciliacao_id
                WHERE lote = '$lote'";

        $query = $this->db->query($sql);
        $saida = $query->result_array();
        
        if($saida[0]['qtd'] > 0){
            return true;
        }else{
            return false;
        }

    }

    public function getConciliacaoPagaFlagFiscal($lote)
    {

        $sql = "SELECT 	count(*) as qtd
                FROM iugu_repasse IU
                inner join conciliacao_fiscal C on C.id = IU.conciliacao_fiscal_id
                WHERE lote = '$lote'";

        $query = $this->db->query($sql);
        $saida = $query->result_array();
        
        if($saida[0]['qtd'] > 0){
            return true;
        }else{
            return false;
        }

    }

    public function getOrderPaymentDate($order_id = null){
        if(is_null($order_id))
            return null;

        $sql = "SELECT data_pago FROM orders WHERE id = ?";
        $order = $this->db->query($sql, array($order_id))->row();
        if(!$order)
            return null;

        return $order->data_pago;
    }

    public function getOrderData($order_id, $valorRepasseTela){        
        $sql = "SELECT * FROM orders WHERE id = ?";
        $order = $this->db->query($sql, array($order_id))->row();
        if($order){
            if($order->service_charge_freight_value == 100){
                $valorRepasseTela = ($order->gross_amount - $order->total_ship) - (($order->gross_amount - $order->total_ship) * ( $order->service_charge_rate / 100));
                $valorRepasseTela = number_format($valorRepasseTela, 2, ",", ".");
            }
            return $valorRepasseTela;
        }else{
            return $valorRepasseTela;
        }
    }

    public function getMarketplaceNumberFromOrderId($order_id = null){

        if (empty($order_id))
            return 0;

        $sql = "SELECT * FROM orders WHERE id = ?";
        $query = $this->db->query($sql, array($order_id));
        
        $result = $query->row();
        if($result){
            return $result->numero_marketplace;
        }
        return null;
    }

    public function getValueAnticipationTransfer($ref_pedido = null){
        if (empty($ref_pedido))
            return 0;
        
        $sql = "SELECT *, o.id FROM orders o
        JOIN anticipation_transfer atf ON atf.order_id = o.id
        WHERE o.numero_marketplace = ?";
        $query = $this->db->query($sql, array($ref_pedido));

        if ($query){
            $result = $query->row();
            if($result){
                return $result->valor_antecipado;
            }
        }
        return 0;
    }

    public function updateStatusRepasseConciliation($lote = null): void
    {

        if (empty($lote))
            return;

        $sql = "SELECT id FROM conciliacao WHERE lote = ?";
        $result = $this->db->query($sql, array($lote))->row();
        if($result){
            $this->db->where('conciliacao_id', $result->id);
            $this->db->where('lote', $lote);
            $this->db->update('repasse', [
                'status_repasse' => 23
            ]);

            $this->db->where('lote', $lote);
            $this->db->update('conciliacao', [
                'status_repasse' => 23
            ]);
        }

        return;
    }

    public function updateStatusRepasseConciliationFiscal($lote = null): void
    {

        if (empty($lote))
            return;

        $sql = "SELECT id FROM conciliacao_fiscal WHERE lote = ?";
        $result = $this->db->query($sql, array($lote))->row();
        if($result){
            $this->db->where('conciliacao_id', $result->id);
            $this->db->where('lote', $lote);
            $this->db->update('repasse_fiscal', [
                'status_repasse' => 23
            ]);

            $this->db->where('lote', $lote);
            $this->db->update('conciliacao_fiscal', [
                'status_repasse' => 23
            ]);
        }

        return;
    }

    public function getcomissionbylotestore($lote = null, $store = null){

        if($lote == null){
            return array();
        }else{

            $where = "";
            if($store <> null){
                $where = " and cs.store_id = ".$store;
            }

            $sql = "select cs.lote as batch,  ";
            $sql.= " 	concat(cs.lote,'-',cs.store_id) as batch_id,  ";
            $sql.= " 	cs.store_id,  ";
            $sql.= " 	cs.cnpj,  ";
            $sql.= " 	round(sum(cs.valor_comissao),2) as comissionTotal  ";
            $sql.= " from conciliacao c   ";
            $sql.= " inner join conciliacao_sellercenter cs on cs.lote = c.lote   ";
            $sql.= " where cs.lote = ".$this->db->escape($lote)." $where";
            $sql.= " group by cs.lote,cs.store_id  ";
            $sql.= " order by cs.store_id   ";

            $query = $this->db->query($sql);
            return (false !== $query) ? $query->result_array() : false;

        }


    }

    /***** FUNÇÕES DA LIBERAÇÃO DE PAGAMENTO - FISCAL */
    public function getConciliacaoGridDataFiscal($lote = null)
    {

        $sql = "SELECT distinct
                    CON.lote,
                	CON.status,
                	CON.status_repasse,
                    CON.id AS id_con,
                    CON.data_criacao,
                    CON.ano_mes,
                    Case when CON.integ_id = '999' then 'Manual' else SML.descloja end as descloja,
                    PMC.data_inicio,
                    PMC.data_fim,
                    CON.integ_id,
                    Case when CON.integ_id = '999' then 'Manual' else SML.apelido end as apelido,
                    PMC.id AS id_ciclo,
                    Case when CON.integ_id = '999' then 999 else SML.id_mkt end as id_mkt,
                    CASE WHEN IR.conciliacao_id IS NOT NULL THEN 'Conciliação Paga' ELSE 'Conciliação não paga' END AS pagamento_conciliacao,
                    CASE WHEN IR.conciliacao_id IS NOT NULL THEN IR.conciliacao_id ELSE 0 END AS conciliacao_id
                FROM conciliacao_fiscal CON
                LEFT JOIN stores_mkts_linked SML ON SML.id_mkt = CON.integ_id
                LEFT JOIN param_mkt_ciclo_fiscal PMC ON PMC.id = CON.param_mkt_ciclo_id
                LEFT JOIN (SELECT DISTINCT conciliacao_fiscal_id as conciliacao_id FROM iugu_repasse) IR ON IR.conciliacao_id = CON.id
                WHERE CON.ativo = 1 ";

        if ($lote <> null) {
            $sql .= "and CON.lote = '$lote'";

        }

        $sql .= " order by CON.id desc";

        $query = $this->db->query($sql);
        return (false !== $query) ? $query->result_array() : false;

    }

    /**
     * @param $inputs
     * @return array
     */
     public function generateInstallmentsByInputsFiscal($inputs, $setting_api_comission=null, $userName=null): array
     {
         $gera_conciliacao_data = $this->createConciliacaoSellerCenterGetDataFiscal($inputs, $userName);
 
         $geraConciliacao = [];
         $conciliation_array = [];
 
         if (!empty($gera_conciliacao_data))
         {
             $this->load->model('model_campaigns_v2');
             $this->load->model('model_orders_conciliation_installments');
         }
 
         foreach ($gera_conciliacao_data as $conciliation_data) {
 
             //Limpando dados antigos antes de continuar
             $this->model_orders_conciliation_installments->clearOldDataFiscal($conciliation_data['id_pedido'], $inputs['hdnLote']);
 
             $campaigns_data = $this->model_campaigns_v2->getCampaignsTotalsByOrderId($conciliation_data['id_pedido']);
             $campaigns_seller = (!empty($campaigns_data)) ? $campaigns_data['total_seller'] : 0;
             $campaigns_mktplace = (!empty($campaigns_data)) ? $campaigns_data['total_channel'] : 0;
             $campaigns_rebate = (!empty($campaigns_data)) ? $campaigns_data['total_rebate'] : 0;
             $campaigns_comission_reduction = (!empty($campaigns_data)) ? $campaigns_data['comission_reduction'] : 0;
             $campaigns_comission_reduction_products = (!empty($campaigns_data)) ? $campaigns_data['comission_reduction_products'] : 0;
             $refund = 0;
 
             $comission = $conciliation_data['valor_percentual_produto'];
             // $new_comission = $this->model_campaigns_v2->getNewComissionByOrder($conciliation_data['id_pedido'])['new_comission'];
 
             if ($campaigns_mktplace > 0) {
                 $valor_repasse = $conciliation_data['valor_repasse'] + $campaigns_comission_reduction + ($campaigns_mktplace - ($campaigns_mktplace * ($comission / 100)));
             } else {
                 $valor_repasse = $conciliation_data['valor_repasse'] + $campaigns_comission_reduction;
             }
 
             $valor_repasse += $campaigns_rebate;
 
             if ($campaigns_mktplace > 0) {
                 $valor_comissao = $conciliation_data['valor_comissao'] - abs($campaigns_comission_reduction + ($campaigns_mktplace - ($campaigns_mktplace * ($comission / 100))));
             } else {
                 $valor_comissao = $conciliation_data['valor_comissao'] - $campaigns_comission_reduction;
             }
 
             if ($setting_api_comission != "1") {
                 $refund = abs($valor_repasse - $conciliation_data['valor_repasse']);
             } else {
                 $refund = abs($valor_repasse - $conciliation_data['valor_repasse'] - $campaigns_comission_reduction_products);
             }
 
             if ($this->orders_precancelled_to_zero && $conciliation_data['paid_status'] == '96'
             && $conciliation_data['status_conciliacao'] == 'Conciliação Cancelamento') //cancelado pre
             {
                 $conciliation_data['valor_comissao_produto'] = 0;
                 $conciliation_data['valor_comissao_frete'] = 0;
                 $conciliation_data['valor_repasse'] = 0;
                 $valor_comissao = 0;
                 $valor_repasse = 0;
             }
 
             //Alteração pelo estorno de comissão se o pedido não tem cobrança de comissão ele força a ser zero, mesmo cancelado
             if (in_array($conciliation_data['paid_status'], [95,97,98,99])
                 && $conciliation_data['status_conciliacao'] == 'Conciliação Cancelamento'
                 && $conciliation_data['valor_repasse'] == 0) //cancelado pre
             {
                 $valor_comissao = $conciliation_data['valor_comissao'];
                 $valor_repasse = $conciliation_data['valor_repasse'];
             }
 
             
            //Alteração pelo estorno de comissão se o pedido não tem cobrança de comissão ele força a ser zero, mesmo cancelado
            if (in_array($conciliation_data['paid_status'], [95,97,98,99])
                && $conciliation_data['status_conciliacao'] == 'Conciliação Cancelamento'
                && $conciliation_data['valor_repasse'] != 0)
            {
                // o valor de comissão está sendo diminuido pois o valor do repasse é negativo, e com isso a conta vira + ao invés de -
                $valor_comissao = round(($conciliation_data['valor_comissao'] + $campaigns_mktplace * ($comission / 100) - $campaigns_mktplace) ,2);
                $valor_repasse = round(($conciliation_data['valor_comissao'] + $campaigns_mktplace * ($comission / 100) - $campaigns_mktplace) * -1,2);
            }
             
             if ($conciliation_data['status_conciliacao'] == 'Conciliação Ciclo') //cancelado pre
             {
                 $valor_repasse = $valor_comissao;
             }
 
             $conciliation_array = array(
                 'lote' => $conciliation_data['lote'],
                 'store_id' => $conciliation_data['id_loja'],
                 'seller_name' => $conciliation_data['nome_loja'],
                 'order_id' => $conciliation_data['id_pedido'],
                 'numero_marketplace' => $conciliation_data['numero_marketplace'],
                 'data_pedido' => $conciliation_data['data_pedido'],
                 'data_entrega' => $conciliation_data['data_entrega'],
                 'data_ciclo' => $conciliation_data['data_ciclo'],
                 'status_conciliacao' => $conciliation_data['status_conciliacao'],
                 'valor_pedido' => $conciliation_data['valor_pedido'],
                 'valor_produto' => $conciliation_data['valor_produto'],
                 'valor_frete' => $conciliation_data['valor_frete'],
                 'valor_percentual_produto' => $conciliation_data['valor_percentual_produto'],
                 'valor_percentual_frete' => $conciliation_data['valor_percentual_frete'],
                 'valor_comissao_produto' => $conciliation_data['valor_comissao_produto'],
                 'valor_comissao_frete' => $conciliation_data['valor_comissao_frete'],
                 'valor_comissao' => $valor_comissao,
                 'valor_repasse' => $valor_repasse,
                 'valor_repasse_ajustado' => $valor_repasse,
                 'usuario' => $conciliation_data['usuario'],
                 'tipo_pagamento' => $conciliation_data['forma_desc'],
                 'refund' => $refund,
                 'digitos_cartao' => $conciliation_data['digitos_cartao'],
                 'cnpj' => $conciliation_data['cnpj'],
                 'data_report' => $conciliation_data['data_report']
 
             );
 
             $this->model_orders_conciliation_installments->insertInstallmentsByConciliationDataFiscal($conciliation_array);
 
         }
         return array($geraConciliacao, $conciliation_array);
     }


    public function createConciliacaoSellerCenterGetDataFiscal($inputs = array(), string $userName=null)
    {
        if (!$userName){
            $userName = $_SESSION['username'];
        }

        $frete_100_canal_seller_centers_vtex = $this->model_settings->getStatusbyName('frete_100_canal_seller_centers_vtex');
        $case_frete_100 = '';
        if($frete_100_canal_seller_centers_vtex == 1){
            $case_frete_100 = ' 
                WHEN O.service_charge_freight_value = 100 THEN 
                    CASE  WHEN S.freight_seller_type > 0 OR O.freight_seller > 0 THEN
                        ROUND(
                        	( O.gross_amount ) - ( ((O.gross_amount - O.total_ship) * ( O.service_charge_rate / 100)) + (O.total_ship * (0)) ) ';

			$case_frete_100 .= '                        	 
                        	,2)
                    else
                        ROUND(
                        	( O.gross_amount - O.total_ship ) - ( ((O.gross_amount - O.total_ship) * ( O.service_charge_rate / 100)) + (O.total_ship * (0)) ) ';

			$case_frete_100 .= '                        	 
                        	,2)
                    end
            ';
        }

        $join_orders_payment = "INNER";
        $accept_order_without_payment_data = $this->model_settings->getStatusbyName('accept_order_without_payment_data');
        if($accept_order_without_payment_data == 1){
            $join_orders_payment = "LEFT";
        }

        //caso seja Grupo Soma passa um parâmetro diferente para a conciliação
        $valorGsoma = $this->model_settings->getSettingDatabyNameEmptyArray('gsoma_painel_financeiro');
        $conciliacao = "sellercenter";

        if($valorGsoma){
            if($valorGsoma['status'] == 1){
                $conciliacao = "gsoma";
            }
        }

        $arrayAnoMes = $this->ajustaanomes($inputs['txt_ano_mes']);

        $wherePedido = '';
        if (isset($inputs['id_pedido']) && $inputs['id_pedido']){
            $wherePedido = " AND O.id = {$inputs['id_pedido']}";
        }else{
			$this->atualizadataspagamentoconciliacaoFiscal($arrayAnoMes);
        }

        $sql = "SELECT * FROM (SELECT 
                '".$inputs['hdnLote']."' AS lote,
                S.id AS id_loja,
                S.name AS nome_loja,
                S.cnpj,
                O.paid_status,
                O.id AS id_pedido,
                O.numero_marketplace,
                O.date_time AS data_pedido,
                O.data_entrega,
                cast(DATE_FORMAT(OPDATE.data_fechamento_fiscal, '%d/%m/%Y') as char) AS data_ciclo,
                'Conciliação Ciclo' AS status_conciliacao,";

        if($conciliacao == "sellercenter"){

        	$sql .= "O.gross_amount AS valor_pedido,
                ROUND(O.gross_amount - O.total_ship,2) AS valor_produto,
                O.total_ship AS valor_frete,
                ROUND(O.service_charge_rate,2) AS valor_percentual_produto,
                ROUND(O.service_charge_freight_value,2) AS valor_percentual_frete,
                ROUND( (O.gross_amount - O.total_ship) * ( O.service_charge_rate / 100) ,2) AS valor_comissao_produto,
                ROUND( O.total_ship * ( O.service_charge_freight_value / 100) ,2) AS valor_comissao_frete,
                ROUND( ((O.gross_amount - O.total_ship) * ( O.service_charge_rate / 100)) + (O.total_ship * ( O.service_charge_freight_value / 100)) ,2) AS valor_comissao,
                CASE ".$case_frete_100."
                WHEN S.freight_seller_type > 0 OR O.freight_seller > 0 THEN
                    ROUND(
                    	( O.gross_amount ) - ( ((O.gross_amount - O.total_ship) * ( O.service_charge_rate / 100)) + (O.total_ship * ( O.service_charge_freight_value / 100)) ) ";

			$sql .= "                    	 
						 ,2)
                else ";

			//BUGS-2397 - Alterado a regra de calculo que estava somando o frete no desconto do repasse deixando como PRODUTO - (TAXA PRODUTO + TAXA FRETE)
			$sql .= " ROUND(
						(O.gross_amount - O.total_ship)  - ((O.gross_amount - O.total_ship) * (O.service_charge_rate / 100)  +  ( (O.total_ship * (O.service_charge_freight_value / 100)))) ";

			$sql .= "							
						,2)  end AS valor_repasse,";
        }else{

        	$sql .= "O.net_amount AS valor_pedido,
                ROUND(O.net_amount - O.total_ship,2) AS valor_produto,
                O.total_ship AS valor_frete,
                ROUND(O.service_charge_rate,2) AS valor_percentual_produto,
                ROUND(O.service_charge_freight_value,2) AS valor_percentual_frete,
                ROUND( (O.net_amount - O.total_ship) * ( O.service_charge_rate / 100) ,2) AS valor_comissao_produto,
                ROUND( O.total_ship * ( O.service_charge_freight_value / 100) ,2) AS valor_comissao_frete,
                ROUND( ((O.service_charge_freight_value/100) * O.total_ship) + ((O.service_charge_rate/100) * (O.gross_amount - O.total_ship - O.discount))  ,2) AS valor_comissao,
                ROUND( 
                        ((O.gross_amount - O.total_ship) * ( O.service_charge_rate / 100)) + (O.total_ship * ( O.service_charge_freight_value / 100)) ";

			$sql .= "		
					,2) AS valor_repasse,";
        }

        $sql .= "'".$userName."' AS usuario,
                PO.forma_desc,
                CONCAT(LEFT(PO.first_digits,4),CONCAT('...',LEFT(PO.last_digits,4))) AS digitos_cartao,
                CASE 
                    WHEN DTU.data_usada = \"Data Envio\" THEN O.data_mkt_sent 
                    WHEN DTU.data_usada = \"Data Pago\" THEN O.data_pago 
                    ELSE O.data_mkt_delivered END AS data_report
                
                #,cvo.total_pricetags
                
                FROM orders O
                INNER JOIN stores S ON S.id = O.store_id
                ".$join_orders_payment." JOIN orders_payment PO on PO.order_id = O.id
                ".$join_orders_payment." JOIN (SELECT MAX(id) AS id, order_id FROM orders_payment GROUP BY order_id) OPD ON OPD.id = PO.id
                INNER JOIN (SELECT DISTINCT MAX(PMC.data_usada) AS data_usada, apelido FROM `stores_mkts_linked` SML INNER JOIN `param_mkt_ciclo_fiscal` PMC ON PMC.integ_id = SML.id_mkt GROUP BY apelido) DTU ON DTU.apelido = O.origin
                INNER JOIN orders_payment_date OPDATE ON (OPDATE.order_id = O.id)
                
                left join campaign_v2_orders cvo on cvo.order_id = O.id
                
                WHERE 
                    CONVERT(CONCAT(MONTH(OPDATE.data_fechamento_fiscal),YEAR(OPDATE.data_fechamento_fiscal)),CHAR) IN (".implode(",",$arrayAnoMes).")
                    AND O.id not in (select distinct order_id from conciliacao_sellercenter_fiscal where lote =  '".$inputs['hdnLote']."') 
                    and O.id not in (select distinct CS.order_id from conciliacao_sellercenter_fiscal CS inner join conciliacao_fiscal C on C.lote = CS.lote where status_conciliacao = 'Conciliação Ciclo') 
                    $wherePedido 
                UNION
                SELECT 
                '".$inputs['hdnLote']."' AS lote,
                S.id AS id_loja,
                S.name AS nome_loja,
                S.cnpj,
                O.paid_status,
                O.id AS id_pedido,
                O.numero_marketplace,
                O.date_time AS data_pedido,
                O.data_entrega,
                cast(DATE_FORMAT(OPDATE.data_fechamento_fiscal_cancelamento, '%d/%m/%Y') as char) AS data_ciclo,
                'Conciliação Cancelamento' AS status_conciliacao,
                O.gross_amount AS valor_pedido,
                ROUND(O.gross_amount - O.total_ship,2) AS valor_produto,
                O.total_ship AS valor_frete,
                ROUND(O.service_charge_rate,2) AS valor_percentual_produto,
                ROUND(O.service_charge_freight_value,2) AS valor_percentual_frete,
                ROUND( (O.gross_amount - O.total_ship) * ( O.service_charge_rate / 100) ,2) AS valor_comissao_produto,
                ROUND( O.total_ship * ( O.service_charge_freight_value / 100) ,2) AS valor_comissao_frete,
                ROUND( ((O.gross_amount - O.total_ship) * ( O.service_charge_rate / 100)) + (O.total_ship * ( O.service_charge_freight_value / 100)) ,2) AS valor_comissao,
                case when ifnull(co.commission_charges_attribute_value,0) <> 1 then
                    ROUND( 
                        ((O.gross_amount - O.total_ship) * ( O.service_charge_rate / 100)) + (O.total_ship * ( O.service_charge_freight_value / 100)) ";

            $sql .= "
                        ,2) * -1 
                else 0 end AS valor_repasse,
                '".$userName."' AS usuario,
                PO.forma_desc,
                CONCAT(LEFT(PO.first_digits,4),CONCAT('...',LEFT(PO.last_digits,4))) AS digitos_cartao,
                O.date_cancel AS data_report 
                
                #,cvo.total_pricetags
                
                FROM orders O
                INNER JOIN stores S ON S.id = O.store_id
                ".$join_orders_payment." JOIN orders_payment PO on PO.order_id = O.id
                ".$join_orders_payment." JOIN (SELECT MAX(id) AS id, order_id FROM orders_payment GROUP BY order_id) OPD ON OPD.id = PO.id
                INNER JOIN orders_payment_date OPDATE ON (OPDATE.order_id = O.id)
                left join canceled_orders co on co.order_id = O.id
                left join campaign_v2_orders cvo on cvo.order_id = O.id
                
                WHERE CONVERT(CONCAT(MONTH(O.date_cancel),YEAR(O.date_cancel)),CHAR) IN (".implode(",",$arrayAnoMes).")  AND O.id not in (select distinct order_id from conciliacao_sellercenter_fiscal where lote =  '".$inputs['hdnLote']."') 
                    and O.id not in (select distinct CS.order_id from conciliacao_sellercenter_fiscal CS inner join conciliacao_fiscal C on C.lote = CS.lote where status_conciliacao = 'Conciliação Cancelamento')
                    $wherePedido
                ) A
                WHERE data_ciclo IN (".implode(",",$inputs['data_ciclo']).")";

                
                $query = $this->db->query($sql);
                
                return $query->result_array();

    }

    public function atualizadataspagamentoconciliacaoFiscal($arrayAnoMes){

        $sql = "select data_pagamento_marketplace(id) as data_pagamento_marketplace, data_pagamento_conecta(id) as data_pagamento_conecta, data_cancelamento_marketplace(id) as data_cancelamento_marketplace, data_fechamento_fiscal(id) as data_fechamento_fiscal
                from orders O
                WHERE CONVERT(CONCAT(LPAD(MONTH(O.date_cancel),2,0),YEAR(O.date_cancel)),CHAR) IN (".implode(",",$arrayAnoMes).") OR
                CONVERT(CONCAT(LPAD(MONTH(O.data_mkt_sent),2,0),YEAR(O.data_mkt_sent)),CHAR) IN (".implode(",",$arrayAnoMes).") OR
                CONVERT(CONCAT(LPAD(MONTH(O.data_pago),2,0),YEAR(O.data_pago)),CHAR) IN (".implode(",",$arrayAnoMes).") OR
                CONVERT(CONCAT(LPAD(MONTH(O.data_mkt_delivered),2,0),YEAR(O.data_mkt_delivered)),CHAR) IN (".implode(",",$arrayAnoMes).")";
        
        $query = $this->db->query($sql);
            
        if($query->result_array()){
            return true;
        }else{
            return false;
        }

    }

    public function createConciliacaoSellerCenterFiscal($inputs = array(), string $userName=null, $geraConciliacaoData = null)
    {
        if (empty($geraConciliacaoData) || !is_array($geraConciliacaoData))
            return false;

        if (!$userName)
            $userName = $_SESSION['username'];

        // $arrayAnoMes = $this->ajustaanomes($inputs['txt_ano_mes']);

        if(strlen($inputs['data_ciclo']) < 10){
            $inputs['data_ciclo'] = "0".$inputs['data_ciclo'];
        }

        $this->db->insert('conciliacao_sellercenter_fiscal', $geraConciliacaoData);
        $order_id = $this->db->insert_id();
        // return ($order_id) ? $order_id : false;
        
        // if ($this->db->query($sql))
        if ($order_id)
            return true;
        else
            return false;
    }

    public function createConciliacaoSellerCenterPainelLegalFiscal($inputs = array(), string $userName=null){

        if (!$userName){
            $userName = $_SESSION['username'];
        }

        $join_orders_payment = "INNER";
        $accept_order_without_payment_data = $this->model_settings->getStatusbyName('accept_order_without_payment_data');
        if($accept_order_without_payment_data == 1){
            $join_orders_payment = "LEFT";
        }

        $arrayAnoMes = $this->ajustaanomes($inputs['txt_ano_mes']);

        $sqlInsertOrder = "SELECT '".$inputs['hdnLote']."' AS lote,
                                    S.id AS id_loja,
                                    S.name AS nome_loja,
                                    S.cnpj,
                                    O.id AS id_pedido,
                                    O.numero_marketplace,
                                    O.date_time AS data_pedido,
                                    O.data_entrega,
                                    data_fechamento_fiscal_cancelamento(O.id) AS data_ciclo,
                                    case 
                                        when LP.notification_type = 'order' and LP.notification_id = 'Estorno de comissão Cobrada' and LP.notification_title = 'Estorno de comissão Cobrada' then 'Conciliação Estorno de Comissão Cancelamento'
                                        when LP.notification_type = 'order' and LP.notification_id = 'Devolução de produto.' then 'Conciliação Estorno de Comissão Devolução'
                                    else
                                        'Conciliação Painel Jurídico' 
                                    end AS status_conciliacao,
                                    O.gross_amount AS valor_pedido,
                                    ROUND(O.gross_amount - O.total_ship,2) AS valor_produto,
                                    O.total_ship AS valor_frete,
                                    ROUND(O.service_charge_rate,2) AS valor_percentual_produto,
                                    ROUND(O.service_charge_freight_value,2) AS valor_percentual_frete,
                                    ROUND( (O.gross_amount - O.total_ship) * ( O.service_charge_rate / 100) ,2) AS valor_comissao_produto,
                                    ROUND( O.total_ship * ( O.service_charge_freight_value / 100) ,2) AS valor_comissao_frete,
                                    ROUND( ((O.gross_amount - O.total_ship) * ( O.service_charge_rate / 100)) + (O.total_ship * ( O.service_charge_freight_value / 100)) ,2) AS valor_comissao,
                                    ROUND( LP.balance_debit ,2) * 1 AS valor_repasse,
                                    '".$userName."' AS usuario,
                                    PO.forma_desc,
                                    LP.id
                                FROM orders O
                                INNER JOIN stores S ON S.id = O.store_id
                                ".$join_orders_payment." JOIN orders_payment PO on PO.order_id = O.id
                                ".$join_orders_payment." JOIN (SELECT MAX(id) AS id, order_id FROM orders_payment GROUP BY order_id) OPD ON OPD.id = PO.id
                                INNER JOIN legal_panel_fiscal LP ON LP.orders_id = O.id AND LP.status = 'Chamado Aberto'
                                WHERE O.id not in (select distinct order_id from conciliacao_sellercenter_fiscal where lote =  '".$inputs['hdnLote']."' and status_conciliacao in ('Conciliação Painel Jurídico', 'Conciliação Estorno de Comissão'))";

        $sqlInsertStore = "SELECT '".$inputs['hdnLote']."' AS lote,
                            S.id AS id_loja,
                            S.NAME AS nome_loja,
                            S.cnpj,
                            '0' AS id_pedido,
                            'painel_juridico_outros',
                            now() AS data_pedido,
                            '' AS data_entrega,
                            '' AS data_ciclo,
                            case 
                                when LP.notification_type = 'others' and LP.notification_title = 'Débito do Seller com o Marketplace' then 'Conciliação Débito do Seller com o Marketplace'
                            else
                                'Conciliação Painel Jurídico' 
                            end  AS status_conciliacao,
                            LP.balance_paid AS valor_pedido,
                            '0' AS valor_produto,
                            '0' AS valor_frete,
                            '0' AS valor_percentual_produto,
                            '0' AS valor_percentual_frete,
                            '0' AS valor_comissao_produto,
                            '0' AS valor_comissao_frete,
                            '0' AS valor_comissao,
                            ROUND( LP.balance_debit, 2 ) *  1 AS valor_repasse,
                            '".$userName."' AS usuario,
                            '' AS form_pagamento,
                            LP.id
                        FROM legal_panel_fiscal LP
                            INNER JOIN stores S ON S.id = LP.store_id
                            AND LP.STATUS = 'Chamado Aberto' 
                        WHERE S.id NOT IN ( SELECT DISTINCT store_id FROM conciliacao_sellercenter_fiscal WHERE lote = '".$inputs['hdnLote']."' AND status_conciliacao = 'Conciliação Painel Jurídico'  AND order_id = 0 )";

        //Gravando itens de pedidos
        $sql = "INSERT INTO conciliacao_sellercenter_fiscal (lote,store_id,seller_name, cnpj,order_id,numero_marketplace,data_pedido,data_entrega,data_ciclo,
                                                        status_conciliacao,valor_pedido,valor_produto,valor_frete,valor_percentual_produto,valor_percentual_frete,
                                                        valor_comissao_produto,valor_comissao_frete,valor_comissao,valor_repasse,usuario,tipo_pagamento,legal_panel_id)
                SELECT * FROM ($sqlInsertOrder) A 
                ";

        $result1 = $this->db->query($sql);

        //Gravando itens de lojas
        $sql = "INSERT INTO conciliacao_sellercenter_fiscal (lote,store_id,seller_name, cnpj,order_id,numero_marketplace,data_pedido,data_entrega,data_ciclo,
                                                        status_conciliacao,valor_pedido,valor_produto,valor_frete,valor_percentual_produto,valor_percentual_frete,
                                                        valor_comissao_produto,valor_comissao_frete,valor_comissao,valor_repasse,usuario,tipo_pagamento,legal_panel_id)
                SELECT * FROM ($sqlInsertStore) A 
                ";

        $result2 = $this->db->query($sql);
        
        return $result1 || $result2;

    }

    public function getstoresfromconciliacaosellercenterFiscal($lote){
	    
	    
	    $sql = "select distinct id as id_loja, name as nome_loja from stores where id in (select distinct store_id from conciliacao_sellercenter_fiscal where lote = '$lote')";
	    
	    $query = $this->db->query($sql);
	    return (false !== $query) ? $query->result_array() : false;
	    
	    
	}

    public function getstatusfromconciliacaosellercenterFiscal($lote){
	    
	    
	    $sql = "select distinct status_conciliacao as id, REPLACE(status_conciliacao, 'Conciliação', 'Repasse') as status from conciliacao_sellercenter_fiscal where lote = '$lote'";
	    
	    $query = $this->db->query($sql);
	    return (false !== $query) ? $query->result_array() : false;
	    
	    
	}

    public function getConciliacaoSellerCenterFiscal($lote = null, $campos = null, int $storeId = null, $limit = null, $offset = null, $filtros = null){

        if($lote == null){
            return array();
        }else{
            $select = "";
            $group = "";
            $where = "";
            $camposSelect = "o.paid_status, 
            o.data_pago
            CS.*, 
            round(CS.valor_produto-valor_comissao_produto,2) as valor_repasse_produto, 
            round(CS.valor_frete - CS.valor_comissao_frete,2) as valor_repasse_frete, 
            atf.valor_antecipado,
            cpv2o.comission_reduction,
            cpv2o.comission_reduction_products,
            cpv2o.discount_comission,
            cpv2o.comission_reduction_marketplace,
            cpv2o.total_pricetags,
            cpv2o.total_campaigns,
            cpv2o.total_channel,
            cpv2o.total_seller,
            cpv2o.total_promotions,
            cpv2o.total_rebate";
            
                switch ($campos) {
                    case "pedidos":
                        $select = "select count(*) as qtd from ( ";
                        $where = " ";
                        // $where = " AND tratado = 1";
                        $group = ") a";
                        $camposSelect = " CS.order_id";
                        break;
                    case "valores":
                        $select = "select round(sum(valor_pedido),2) as qtd from ( ";
                        $where = " ";
                        // $where = " AND tratado = 1";
                         $group = ") a";
                        $camposSelect = " valor_pedido";
                        break;
                    case "cancelados":
                        $select = "select count(*) as qtd from ( ";
                        $group = " and CS.status_conciliacao = 'Conciliação Cancelamento') a";
                        $where = " ";
                        // $where = " AND tratado = 1";
                        $camposSelect = " CS.order_id";
                        break;
                    case "cancelados_valores":
                        $select = "select ifnull(round(sum(valor_pedido),2),0) as qtd from ( ";
                        $where = " ";
                        // $where = " AND tratado = 1";
                        $group = " and CS.status_conciliacao = 'Conciliação Cancelamento') a";
                        $camposSelect = " valor_pedido";
                        break; 
                    case "sellers":
                        $select = "select count(*) as qtd from ( ";
                        $group = " ) a";
                        $camposSelect = " distinct CS.store_id";
                        break; 
                    case "refund":
                        $select = "";
                        $group = "";
                        $camposSelect = " sum(refund) as total_refund";
                        break;
                    case "fiscalresumo":
                        $select = "select store_id, seller_name, round(sum(valor_repasse_ajustado),2) as valor_fiscal from ( ";
                        $group = " ) a group by store_id, seller_name order by seller_name";
                        $camposSelect = " CS.store_id, CS.seller_name, case when valor_repasse_ajustado <> 0.00 then valor_repasse_ajustado else valor_repasse end as valor_repasse_ajustado";
                        break; 
                    default:
                        $select = "";
                        $group = "";
                        $where = "";
                        $camposSelect = "o.paid_status, o.data_pago, CS.*, 
                        round(CS.valor_produto - CS.valor_comissao_produto,2) as valor_repasse_produto, 
                        round(CS.valor_frete - valor_comissao_frete,2) as valor_repasse_frete, 
                        atf.valor_antecipado,
                        cpv2o.comission_reduction,
                        cpv2o.comission_reduction_products,
                        cpv2o.discount_comission,
                        cpv2o.comission_reduction_marketplace,
                        cpv2o.total_pricetags,
                        cpv2o.total_campaigns,
                        cpv2o.total_channel,
                        cpv2o.total_seller,
                        cpv2o.total_promotions,
                        cpv2o.total_rebate ";
                        break;
                }

            $sql = "select $camposSelect from conciliacao_sellercenter_fiscal CS left join orders o on CS.order_id = o.id  ";
            $sql.= " LEFT JOIN anticipation_transfer atf ON atf.order_id = o.id ";
            $sql.= " LEFT JOIN campaign_v2_orders cpv2o ON cpv2o.order_id = o.id ";
            $sql.= " WHERE CS.lote = ".$this->db->escape($lote)." $where";

            //Esconder pedidos cancelado pré
            if (!$this->model_settings->getValueIfAtiveByName('display_pre_canceled_orders_in_financial_reports')){
                $sql.= " AND ifnull(o.paid_status,0) <> 96 ";
            }

            if ($storeId){
                $sql.= " AND CS.store_id = $storeId";
            }


            if($filtros){
                if(is_array($filtros)){
                    
                    if(array_key_exists('txt_numero_pedido',$filtros)){
                        if( $filtros['txt_numero_pedido'] ){
                            $sql.= " AND CS.numero_marketplace = '".$filtros['txt_numero_pedido']."'";
                        }
                    }

                    if(array_key_exists('slc_loja',$filtros)){
                        if( $filtros['slc_loja'] ){
                            $sql.= " AND CS.store_id = '".$filtros['slc_loja']."'";
                        }
                    }

                    if(array_key_exists('slc_status_pedido',$filtros)){
                        if($filtros['slc_status_pedido']){
                            $sql.= " AND CS.status_conciliacao = '".$filtros['slc_status_pedido']."'";
                        }
                    }

                }
            }

            if (!is_null($limit) && !is_null($offset)){
                $sql .= " LIMIT $limit OFFSET $offset";
            }

            $query = $this->db->query($select.$sql.$group);
            return (false !== $query) ? $query->result_array() : false;
        }


    }

    public function atualizaValorConciliacaoSellerCenterArquivoMoveloFiscal($input, $arquivo){
        
        $data = array(
            'valor_repasse_ajustado' => str_replace( "\"", "", utf8_encode($arquivo[3])),
            'valor_repasse' => str_replace( "\"", "", utf8_encode($arquivo[3])),
            'observacao' => str_replace( "\"", "", utf8_encode($arquivo[4]))
        );
        $this->db->where('numero_marketplace', $arquivo[1]);
        $this->db->where('status_conciliacao', str_replace("Repasse", "Conciliação", $arquivo[2]));
        $this->db->where('lote', $input['hdnLote']);
        $update = $this->db->update('conciliacao_sellercenter_fiscal', $data);
        
	    return $update;

    }

    public function verificaconsiliacaoFiscal($input)
    {

        $sql = "select count(*) as qtd from conciliacao_fiscal where lote = '" . $input['hdnLote'] . "'";

        $query = $this->db->query($sql);
        $saida = $query->result_array();
        return $saida[0];

    }

    public function getOrdersFromConciliacaoSellercenterFiscal($lote)
    {	    
	    $sql = "select * from conciliacao_sellercenter_fiscal csc left join campaign_v2_orders cv2o on csc.order_id = cv2o.order_id where csc.lote = '$lote'";
	    
	    $query = $this->db->query($sql);
	    return (false !== $query) ? $query->result_array() : false;
	    
	    
	}

    public function criaconsiliacaofiscal($input)
    {

        //$status = $this->statusConciliacaotbl($input['hdnLote'], $input['slc_mktplace']);

        $data['lote'] = $input['hdnLote'];
        $data['status'] = "Conciliação com sucesso";
        $data['integ_id'] = $input['slc_mktplace'];
        $data['param_mkt_ciclo_id'] = $input['slc_ciclo'];
        $data['ano_mes'] = $input['slc_ano_mes'];
        $data['users_id'] = $this->session->userdata('id');

        $insert = $this->db->insert('conciliacao_fiscal', $data);
        $order_id = $this->db->insert_id();
        return ($order_id) ? $order_id : false;

    }

    public function carregaTempRepasseSellercenterFiscal($lote, $refund_from_seller = 0, $payment_gateway_code = null){

        if(empty($lote)) {
            return;
        }

        $limpa = $this->limpaTempRepasseFiscal($lote);
        $limpaRepasse = $this->limpaRepasseFiscal($lote);

        $this->db->trans_begin();

        if($limpa && $limpaRepasse){

            //BUGS-2111 -> codigo referente ao painel juridico usado na moip vai entrar tb na pagarme e solucionar isso, precisando apenas de 1 sql
            $sql = "INSERT INTO repasse_temp_fiscal (lote, conciliacao_id, store_id, name, valor_conectala, valor_seller, responsavel, status_repasse, refund)
                        SELECT lote, id, store_id, seller_name, SUM(valor_conectala_ajustado) AS valor_conectala_ajustado, round(SUM(valor_parceiro_ajustado),2) AS valor_parceiro_ajustado, responsavel, `status`, SUM(refund)
                         FROM (SELECT CB.status_conciliacao, CB.lote, C.id, CB.store_id, CB.seller_name, 0 AS valor_conectala_ajustado,
                                CASE WHEN CB.valor_repasse_ajustado <> 0.00 THEN CB.valor_repasse_ajustado ELSE CB.valor_repasse END AS valor_parceiro_ajustado,
                                usuario AS responsavel, 21 AS `status`, CB.refund FROM conciliacao_sellercenter_fiscal CB INNER JOIN conciliacao_fiscal C ON C.lote = CB.lote WHERE C.lote = '$lote' and status_conciliacao NOT LIKE '%juridico%'
                        ) a
                        GROUP BY lote, id, store_id, seller_name, responsavel, `status`, status_conciliacao
                        UNION ALL
                        SELECT lote, id, store_id, seller_name, valor_conectala_ajustado AS valor_conectala_ajustado, round(valor_parceiro_ajustado,2) as valor_parceiro_ajustado, responsavel, `status`, refund
                        FROM (SELECT CB.status_conciliacao, CB.lote, C.id, CB.store_id, CB.seller_name, 0 AS valor_conectala_ajustado,
                        CASE WHEN CB.valor_repasse_ajustado <> 0.00 THEN CB.valor_repasse_ajustado ELSE CB.valor_repasse END AS valor_parceiro_ajustado,
                        usuario AS responsavel, 21 AS `status`, CB.refund FROM conciliacao_sellercenter_fiscal CB INNER JOIN conciliacao_fiscal C ON C.lote = CB.lote WHERE C.lote = '$lote' and status_conciliacao LIKE '%juridico%'
                        ) a ";

            $repasse_temp_result = $this->db->query($sql);

            $this->db->trans_commit();

            return $repasse_temp_result;

        }else{
            $this->db->trans_rollback();
            return false;
        }
    }

    public function limpaTempRepasseFiscal($lote){

        $sql = "delete from repasse_temp_fiscal where lote = '$lote'";
        return $this->db->query($sql);

    }

    public function limpaRepasseFiscal($lote){

        $sql = "delete from repasse_fiscal where lote = '$lote'";
        return $this->db->query($sql);

    }

    public function cadastraRepasseFinalFiscal($lote){

        // Carrega primeiro uma versão já existente da conciliação
        $sql = "insert into repasse_fiscal (lote, conciliacao_id, store_id, name, valor_conectala, valor_seller, responsavel, status_repasse, refund)
                SELECT lote, conciliacao_id, store_id, name, valor_conectala, valor_seller, responsavel, status_repasse, refund from repasse_temp_fiscal where lote = '$lote'";

       return $this->db->query($sql);

   }

   public function editaconsiliacaoFiscal($input)
    {

        $status = $this->statusConciliacaotbl($input['hdnLote'], $input['slc_mktplace']);

        $data = array(
            'status' => $status,
            'integ_id' => $input['slc_mktplace'],
            'param_mkt_ciclo_id' => $input['slc_ciclo'],
            'ano_mes' => $input['slc_ano_mes'],
            'users_id' => $this->session->userdata('id')
        );

        $this->db->where('lote', $input['hdnLote']);
        return $this->db->update('conciliacao_fiscal', $data);

    }

    public function salvaObservacaosellercenterfiscal($input)
    {

        $data = array(
            'observacao' => $input['txt_observacao']            
        );

        $this->db->where('lote', $input['hdnLote']);
        $this->db->where('id', $input['txt_hdn_pedido_obs']);
        return $this->db->update('conciliacao_sellercenter_fiscal', $data);

    }

    public function alteracomissaosellercenterfiscal($input)
    {

        $sql = "update conciliacao_sellercenter_fiscal set valor_repasse_ajustado = round(?,2), valor_repasse = round(?,2)
        WHERE lote = ? AND id = ? ";

		return $this->db->query($sql, [$input['txt_comissao'],$input['txt_comissao'], $input['hdnLote'], $input['txt_hdn_pedido_comissao']]);

    }

    public function buscaobservacaopedidosellercenterFiscal($lote, $num_pedido, $tipo = null)
    {

        if ($tipo <> null) {
            $campos = 'numero_marketplace as num_pedido, observacao, DATE_FORMAT(data_criacao, \'%d-%m-%Y\') as data_criacao';
        } else {
            $campos = '*';
        }

        $sql = "SELECT $campos FROM `conciliacao_sellercenter_fiscal` where id = '$num_pedido' and lote = '$lote'";

        $query = $this->db->query($sql);
        return (false !== $query) ? $query->result_array() : false;

    }

    public function incluiremovepedidoconciliacaosellercenterFiscal($input)
    {

        $sql = "update conciliacao_sellercenter_fiscal set tratado = case when tratado = 1 then 0 else 1 end
        WHERE lote = ? AND id = ? ";

		return $this->db->query($sql, [$input['hdnLote'], $input['id']]);

    }

}