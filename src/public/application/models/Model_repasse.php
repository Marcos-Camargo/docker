<?php

/**
 * Class Model_repasse
 */
class Model_repasse extends CI_Model
{

    public $tableName = 'repasse';

    public function __construct()
    {
        parent::__construct();
    }


    public function updateTransferStatus($status = false, $id = false, $blocked = false)
    {
        $status_number = 26;

        if ($status === true)
            $status_number = 23;

		if ($status === true && $blocked === true)
			$status_number = 33;

        return $this->db->update($this->tableName, array('status_repasse' => $status_number), array('id' => $id));
    }


	//braun
	public function updatePositiveNegativeStatus($conciliation_id = null, $store_id = null, $status_repasse = null): bool
	{
		if (!$conciliation_id || !$store_id || !$status_repasse)
		{
			return false;
		}

		return $this->db->update(
			$this->tableName,
			array('status_repasse' => $status_repasse),
			array('conciliacao_id' => $conciliation_id, 'store_id' => $store_id)
		);
	}

    public function markAsPaid($storeId, $conciliationId, $responsible)
    {
        if (!$storeId || !$conciliationId || !$responsible){
            throw new Exception('Dados Obrigatórios não fornecidos');
        }

        $sql = "UPDATE {$this->tableName} SET status_repasse = 23, paid_status_responsible='$responsible' 
             WHERE store_id = $storeId AND conciliacao_id = $conciliationId";
        return $this->db->query($sql);

    }

    public function updateTransferLegal($store_id, $amount, $legal_panel_id = null)
    {
        $where = "  lp.status = 'Chamado Aberto' ";
        if (!is_null($amount)){
            $where.= "and lp.store_id = $store_id AND lp.balance_debit = ".floatVal($amount);
        }
        $where.= " LIMIT 1 ";
        if ($legal_panel_id) {
            $where = " lp.id = ".$legal_panel_id;
        }
        $sql = "
                UPDATE legal_panel AS legal
                inner JOIN (
                    SELECT lp.id from legal_panel lp 
                    #INNER JOIN conciliacao_sellercenter cs ON lp.id=cs.legal_panel_id 
                    WHERE 
                    ".$where."
                ) AS conciliacao
                SET legal.status = 'Chamado Fechado'
                WHERE legal.id = conciliacao.id
        ";

        return $this->db->query($sql);
    }

    public function updateTransferLegalCloseLegalPanel($legal_panel_id = null)
    {
        
        if ($legal_panel_id) {
            $where = " lp.id = ".$legal_panel_id;

            $sql = "
            UPDATE legal_panel lp
            SET lp.status = 'Chamado Fechado'
            WHERE $where ";

            return $this->db->query($sql);

        } else{

            return false;

        }
       
    }

    public function updateTransferLegalCloseLegalPanelFiscal($legal_panel_id = null)
    {
        
        if ($legal_panel_id) {
            $where = " lp.id = ".$legal_panel_id;

            $sql = "
            UPDATE legal_panel_fiscal lp
            SET lp.status = 'Chamado Fechado'
            WHERE $where ";

            return $this->db->query($sql);

        } else{

            return false;

        }
       
    }


    public function setStoreTransferFail(array $transfer): bool
    {
        $lot = $transfer['lote'];
        $store_id = $transfer['store_id'];

        if ($lot && $store_id)
        {
            $sql = "update repasse set status_repasse = 26 where lote = ? and store_id = ?";

            if ($this->db->query($sql, array($lot, $store_id)))
            {
                return true;
            }
        }

        return false;
    }


    public function sumValorSellerByStoreId(int $storeId): float
    {

        $sql = "SELECT SUM(valor_seller) AS valor_sum FROM `repasse` WHERE store_id = ? AND valor_seller > ?";

        $query = $this->db->query($sql, array($storeId, 0)); //@todo dilnei não podemos transferir valor negativo, então só aceitaremos valor > 0

        $row = $query->row_array();

        return is_null($row['valor_sum']) ? 0.0 : (float)$row['valor_sum'];

    }

    public function countOpenTransferByStoreId(int $storeId): int
    {

        $sql = "SELECT count(*) AS total_itens FROM repasse WHERE store_id = ? AND valor_seller > ?";

        $query = $this->db->query($sql, array($storeId, 0)); //@todo dilnei não podemos transferir valor negativo, então só aceitaremos valor > 0

        $row = $query->row_array();

        return is_null($row['total_itens']) ? 0 : $row['total_itens'];

    }


    public function getOrdersFromTransfer($lote = null, $store_id = null)
    {
        if (!$lote || !$store_id)
            return false;

        $sql    = "select * from conciliacao_sellercenter where lote = ? and store_id = ?";
        $query  = $this->db->query($sql, array($lote, $store_id));
        $orders = ($query && $query->num_rows() > 0) ? $query->result_array() : false;

        return ($orders) ?: false;
    }

    public function getByAmountAndStoreId($amount, $store_id)
    {

        $sql    = "SELECT lote, store_id, ROUND(SUM(valor_seller), 2) AS total
                    FROM repasse
                    WHERE store_id = $store_id
                    GROUP BY lote, store_id
                    HAVING total = '$amount'";
        $query  = $this->db->query($sql);
        $itens = ($query && $query->num_rows() > 0) ? $query->result_array() : false;

        return ($itens) ?: false;
    }

    public function getByAmountTotal($amount)
    {

        $sql    = "SELECT lote, store_id,conciliacao_id,name,status_repasse, ROUND(SUM(valor_seller), 2) AS total
                    FROM repasse
                    GROUP BY lote, store_id
                    HAVING total = '$amount'";
        $query  = $this->db->query($sql);
        $itens = ($query && $query->num_rows() > 0) ? $query->result_array() : false;

        return ($itens) ?: false;
    }

    public function getByAmountAndStoreIdNormal($amount, $store_id)
    {

        $sql    = "select * from repasse where valor_seller = ? and store_id = ?";
        $query  = $this->db->query($sql, array($amount, $store_id));
        $orders = ($query && $query->num_rows() > 0) ? $query->result_array() : false;

        return ($orders) ?: false;
    }


    public function saveStatement($statement_array = null)
    {
        if (!$statement_array)
            return false;
        
        $this->db->set('data_transferencia', 'NOW()', FALSE);
        
        if (!isset($statement_array['data_repasse_conta_corrente']))
            $this->db->set('data_repasse_conta_corrente', 'NOW() + INTERVAL 1 DAY', FALSE);

        $insert = $this->db->insert('iugu_repasse', $statement_array);

        return ($insert) ? $insert : false;
    }


    public function getLegalPanelTransfersByLot($lot): ?array
    {
//        $sql = "select * from repasse where lote = ? and refund is null";
        $sql = "SELECT store_id, legal_panel_id, valor_repasse FROM conciliacao_sellercenter WHERE lote = ? AND legal_panel_id IS NOT null";

        $query = $this->db->query($sql, [$lot]);
        return (!empty($query->result_array())) ? $query->result_array() : null;
    }

    public function getLegalPanelTransfersByLotFiscal($lot): ?array
    {
//        $sql = "select * from repasse where lote = ? and refund is null";
        $sql = "SELECT store_id, legal_panel_id, valor_repasse FROM conciliacao_sellercenter_fiscal WHERE lote = ? AND legal_panel_id IS NOT null";

        $query = $this->db->query($sql, [$lot]);
        return (!empty($query->result_array())) ? $query->result_array() : null;
    }

	public function getPaidStores($lote): array
	{
		$sql = "select 
    				store_id, 
    				sum(valor_seller) as total 
				from 
				    repasse
				where
				    lote = '".$lote."'
				group by store_id";

		$query = $this->db->query($sql);

		return $query->result_array();
	}

	public function getPaymentUsers($conciliacao_id, $store_id, $users_id = null)
	{
		$sql = "select 
    				#distinct store_id, 
				 	GROUP_CONCAT(distinct responsavel)  as responsavel
				from 
					repasse r 
				where 
				    conciliacao_id = ".$conciliacao_id." 
				and
					store_id = '".$store_id."'
				and 
					responsavel is not null group by store_id";

		if ($users_id)
		{
			$sql = "select username as responsavel from users where id=".$users_id;
		}

		$query = $this->db->query($sql);
		$row = $query->row_array();
		return is_null($row['responsavel']) ? 0 : $row['responsavel'];
	}

    public function sumValorSellerByStoreIdAndConciliacaoId(int $store_id, int $conciliacao_id): ?array
    {
        return $this->db->select('
                status_repasse,
                MAX(date_insert) as date_insert,
                SUM(valor_seller) as sum_valor_seller
            ')
            ->where(array(
                'store_id' => $store_id,
                'conciliacao_id' => $conciliacao_id,
                'status_repasse' => 50
            ))
            ->get('repasse')
            ->row_array();
    }

    public function updatePositiveNegativeStatusFiscal($conciliation_id = null, $store_id = null, $status_repasse = null): bool
	{
		if (!$conciliation_id || !$store_id || !$status_repasse)
		{
			return false;
		}

		return $this->db->update(
			'repasse_fiscal',
			array('status_repasse' => $status_repasse),
			array('conciliacao_id' => $conciliation_id, 'store_id' => $store_id)
		);
	}

    public function getPaymentUsersFiscal($conciliacao_id, $store_id)
	{
		$sql = "select 
    				#distinct store_id, 
				 	GROUP_CONCAT(distinct responsavel)  as responsavel
				from 
					repasse_fiscal r 
				where 
				    conciliacao_id = ".$conciliacao_id." 
				and
					store_id = '".$store_id."'
				and 
					responsavel is not null group by store_id";

		$query = $this->db->query($sql);
		$row = $query->row_array();
		return is_null($row['responsavel']) ? 0 : $row['responsavel'];
	}
}