<?php

/**
 * Class Model_conciliation
 */
class Model_conciliation extends CI_Model
{

    public $tableName = 'conciliacao';

    public function __construct()
    {
        parent::__construct();
    }

    public function getAll(): array
    {

        $sql = "SELECT c.id AS conciliacao_id, p.data_pagamento, c.status_repasse
                FROM conciliacao c
                INNER JOIN param_mkt_ciclo p ON c.param_mkt_ciclo_id = p.id ";

        $query = $this->db->query($sql);

        return $query->result_array();

    }

    /**
     * Retorna apenas a lista de conciliações que devem ser feitas (onde o dia do pagamento é o dia que está executando
     * o script e o status do repasse é 21 ou o status do repasse é 25
     * @param false $gateway
     * @return array
     */
    public function getOpenConciliations($gateway = false, $conciliation_id = null, $user_command = null): array
    {

        $current_day = date("j");

		$where = "
					c.ativo = 1 
				AND 
					p.ativo = 1 
				";

		if (true === $user_command)
		{
			$where .= " AND  c.status_repasse in (21, 25) ";
		}
		else
		{
			$where .= " AND ( (p.data_pagamento = '".$current_day."' AND c.status_repasse = 21) OR c.status_repasse = 25) ";
		}

		if ($conciliation_id)
		{
			$where = "
						 c.id = ".$conciliation_id." 
					";
		}

        $sql = "SELECT c.id AS conciliacao_id, p.data_pagamento, c.status_repasse, c.lote, c.ano_mes, c.users_id
                FROM conciliacao c
                INNER JOIN param_mkt_ciclo p ON c.param_mkt_ciclo_id = p.id 
                WHERE 
                ".$where;

        $query = $this->db->query($sql);

        return ($query) ? $query->result_array() : array();
    }


    /**
     * @param false $conciliation_id
     * @param false $conciliation_status
     * @return false
     */
    public function updateConciliationStatus($conciliation_id = false, $conciliation_status = false)
    {
        if (!$conciliation_status)
            return false;

        return $this->db->update($this->tableName, array('status_repasse' => $conciliation_status), array('id' => $conciliation_id));
    }

    public function getByYearMonth(string $year_month): array
    {
        return $this->db->select('c.id AS conciliacao_id, c.lote, p.data_pagamento, c.status_repasse, c.data_criacao, p.data_inicio, p.data_fim, c.ano_mes')
            ->join('param_mkt_ciclo p','c.param_mkt_ciclo_id = p.id')
            ->where(array(
                'c.ano_mes' => $year_month,
                'c.status_repasse' => 23
            ))
            ->get('conciliacao c')
            ->result_array();
    }

    public function getConciliationByLegalPanelId(int $legal_panel_id): ?array
    {
        return $this->db
            ->select('lp.*')
            ->join('conciliacao_sellercenter cs', 'cs.lote = c.lote')
            ->join('legal_panel lp', 'lp.id = cs.legal_panel_id ')
            ->where('cs.legal_panel_id', $legal_panel_id)
            ->get('conciliacao c')
            ->row_array();
    }

    public function getOpenConciliationsFiscal($gateway = false, $conciliation_id = null, $user_command = null): array
    {

        $current_day = date("j");

		$where = "
					c.ativo = 1 
				AND 
					p.ativo = 1 
				";

		if (true === $user_command)
		{
			$where .= " AND  c.status_repasse in (21, 25) ";
		}
		else
		{
			$where .= " AND ( (p.data_ciclo_fiscal = '".$current_day."' AND c.status_repasse = 21) OR c.status_repasse = 25) ";
		}

		if ($conciliation_id)
		{
			$where = "
						 c.id = ".$conciliation_id." 
					";
		}

        $sql = "SELECT c.id AS conciliacao_id, p.data_ciclo_fiscal as data_pagamento, c.status_repasse, c.lote
                FROM conciliacao_fiscal c
                INNER JOIN param_mkt_ciclo_fiscal p ON c.param_mkt_ciclo_id = p.id 
                WHERE 
                ".$where;

        $query = $this->db->query($sql);

        return ($query) ? $query->result_array() : array();
    }

    public function updateConciliationStatusFiscal($conciliation_id = false, $conciliation_status = false)
    {
        if (!$conciliation_status)
            return false;

        return $this->db->update('conciliacao_fiscal', array('status_repasse' => $conciliation_status), array('id' => $conciliation_id));
    }

}