<?php
/*
Model de Ciclos de Pagamento
*/

/**
 * @property CI_DB_query_builder $db
 */

class Model_cycles extends CI_Model
{

    const CICLO_VALIDO = 1;

    const CICLO_INVALIDO = 0;

    const TABLE_CICLO = 'param_mkt_ciclo';

    const TABLE_MODEL_CICLO = 'model_cycle';

    public function __construct()
    {
        parent::__construct();
    }

    public function insert($data)
    {
        $this->db->insert(self::TABLE_CICLO, $data);
    }

    public function insertModel($data): bool
    {
        return $this->db->insert(self::TABLE_MODEL_CICLO, $data);
    }

    public function exists($id, $table = self::TABLE_CICLO, $column = "id")
    {
        $this->db->where($column, $id);
        $this->db->from($table);
        return $this->db->count_all_results() > 0;
    }

    public function delete($id = null, $table)
    {
        if (!empty($id)) {
            $this->db->where('id', $id);
            $this->db->delete($table);
        }
    }

    public function update($id = null, $data, $table)
    {
        if (!empty($id)) {
            $this->db->where('id', $id);
            return $this->db->update($table, $data);
        }

        return false;
    }

    public function checkModelExists($data = []): bool
    {
        $this->db->where('data_inicio', $data['data_inicio']);
        $this->db->where('data_fim', $data['data_fim']);
        $this->db->where('data_pagamento', $data['data_pagamento']);
        $this->db->from(self::TABLE_MODEL_CICLO);
        return $this->db->count_all_results() > 0;
    }

    public function getCutDates($row = false, $id = null)
    {

        $cut_date = $this->db->from('cut_date_cycle');
        if ($row && !empty($id)) {
            return $cut_date->where('id', $id)->get()->row();
        }

        return $cut_date->get()->result();
    }

    public function getCutDatesByName($row = false, $name = null)
    {

        $cut_date = $this->db->from('cut_date_cycle');
        if ($row && !empty($name)) {
            return $cut_date->where('cut_date', $name)->get()->row();
        }

        return $cut_date->get()->result();
    }

    public function getListCycles($group = false, $store = 'all', $request = [], $rest = []): array
    {

        $store = $store == 'marketplace' ? 'mkt' : $store;

        $this->db->select(' *, cut_date_cycle.id AS cut_id, param_mkt_ciclo.id as pmc_id');
        $this->db->join('stores_mkts_linked', 'stores_mkts_linked.id_mkt = param_mkt_ciclo.integ_id');
        $this->db->join('cut_date_cycle', 'cut_date_cycle.cut_date = param_mkt_ciclo.data_usada');
        $this->db->from('param_mkt_ciclo');

        if ($group) {
            $this->db->group_by('data_inicio, data_fim, data_pagamento');
        }

        if ($store == 'all') {
            $this->db->join('stores', 'stores.id = param_mkt_ciclo.store_id', 'left');
        } else if ($store == 'store') {
            $this->db->join('stores', 'stores.id = param_mkt_ciclo.store_id');
            $this->db->where('param_mkt_ciclo.store_id IS NOT NULL');
        } else if ($store == 'mkt') {
            $this->db->where('param_mkt_ciclo.store_id IS NULL');
        }

        if (!empty($request)) {
            if (isset($request['vCycleId']) && !empty($request['vCycleId']) ) {
                $this->db->where('param_mkt_ciclo.id', $request['vCycleId']);
            }
            if (isset($request['vStore']) && !empty($request['vStore'])) {
                $this->db->where('store_id', $request['vStore']);
            }
            if (isset($request['gatilho']) && !empty($request['gatilho'])) {
                $this->db->like('data_usada', $request['gatilho']);
            }
            if (isset($request['store_name']) && $store != 'mkt' && !empty($request['store_name'])) {
                $this->db->like('stores.name', $request['store_name']);
            }
            if (isset($request['marketplace_name']) && !empty($request['marketplace_name'])) {
                $this->db->like('descloja', $request['marketplace_name']);
            }
            if (isset($request['vInicio']) && !empty($request['vInicio'])) {
                $this->db->where('data_inicio', $request['vInicio']);
            }
            if (isset($request['vFim']) && !empty($request['vFim'])) {
                $this->db->where('data_fim', $request['vFim']);
            }
            if (isset($request['vDataPagamento']) && !empty($request['vDataPagamento'])) {
                $this->db->where('data_pagamento', $request['vDataPagamento']);
            }
            if (isset($request['vDataPagamentoConectala']) && !empty($request['vDataPagamentoConectala'])) {
                $this->db->where('data_pagamento_conecta', $request['vDataPagamentoConectala']);
            }
            if (isset($request['active']) && !empty($request['active'])) {
                $active = $request['active'] == 'SIM' ? 1 : 0;
                $this->db->where('ativo', $active);
            }else{
                $this->db->where('ativo', 1);
            }
        }else{
            $this->db->where('ativo', 1);
        }

        return $this->extractedQuery($rest);
    }

    public function getAllMarketplaces()
    {
        $sql = "SELECT DISTINCT INTG.id_mkt AS id, INTG.descloja AS mkt_place
                FROM stores_mkts_linked INTG 
                LEFT JOIN param_mkt_categ_integ MCI ON INTG.id_mkt = MCI.integ_id
                LEFT JOIN param_mkt_categ MC ON MC.id = MCI.mkt_categ_id
                ORDER BY INTG.descloja ";
        return $this->db->query($sql)->result();
    }

    public function marketplaceNameExists(string $marketplaceName): bool
    {
        $sql = "SELECT count(*) total
                FROM stores_mkts_linked 
                WHERE descloja = '$marketplaceName'";
        $return = $this->db->query($sql)->row_array();
        return $return['total'] > 0;
    }

    public function getMarketplaceByName(string $marketplaceName): ?array
    {
        $sql = "SELECT *
                FROM stores_mkts_linked 
                WHERE descloja = '$marketplaceName'";
        return $this->db->query($sql)->row_array();
    }

    public function cutDateExists(string $cutDate): bool
    {
        $sql = "SELECT count(*) total
                FROM cut_date_cycle 
                WHERE cut_date = '$cutDate'";
        $return = $this->db->query($sql)->row_array();
        return $return['total'] > 0;
    }

    public function getAllStores()
    {
        $sql = "SELECT * FROM stores";
        return $this->db->query($sql)->result();
    }

    public function getModelCycles($rest = [], $filters = []): array
    {

        $this->db->from('model_cycle');

        if (!empty($filters)) {
            if (isset($filters['id']) && !empty($filters['id']) ) {
                $this->db->where('id', $filters['id']);
            }
            if (isset($filters['start_day']) && !empty($filters['start_day'])) {
                $this->db->where('data_inicio', $filters['start_day']);
            }
            if (isset($filters['end_day']) && !empty($filters['end_day'])) {
                $this->db->where('data_fim', $filters['end_day']);
            }
            if (isset($filters['payment_day']) && !empty($filters['payment_day'])) {
                $this->db->where('data_pagamento', $filters['payment_day']);
            }
        }

        return $this->extractedQuery($rest);

    }

    public function getCyclesByMarketplace($integ_id = null, $hidden_id = null, $vStore = null)
    {
        $where_store = "AND param_mkt_ciclo.store_id IS NULL";
        $where_hidden = "";
        if (!empty($hidden_id)) {
            $where_hidden = "AND param_mkt_ciclo.id != $hidden_id";
        }
        if (!empty($vStore)) {
            $where_store = "AND param_mkt_ciclo.store_id = $vStore";
        }
        $sql = "SELECT * FROM param_mkt_ciclo WHERE integ_id = ? $where_store  $where_hidden AND ativo = 1";

        $query = $this->db->query($sql, array($integ_id));

        if($query->num_rows() == 0){
            return [];
        }

        return $query->result_array();
    }

    public function removeAllCyclesByStoreId(int $storeId)
    {
        return false;
    }

    public function getAllCycles()
    {
        $sql = "SELECT *,  cut_date_cycle.id  AS cut_id, param_mkt_ciclo.id as pmc_id, group_concat(distinct name) AS name,
                group_concat(distinct data_usada) AS corte
                FROM param_mkt_ciclo
                JOIN stores_mkts_linked sml ON sml.id_mkt = param_mkt_ciclo.integ_id
                JOIN cut_date_cycle ON cut_date_cycle.cut_date = param_mkt_ciclo.data_usada
                LEFT JOIN stores ON stores.id = param_mkt_ciclo.store_id
                WHERE ativo = 1 GROUP BY data_inicio, data_fim, data_pagamento";

        return $this->db->query($sql)->result_array();

        $this->db->where('ativo', 1);
        $this->db->where('store_id', $storeId);
        $this->db->update($this->tableName, ['ativo' => 0]);

        $this->db->query('DELETE FROM `conciliacao_sellercenter` WHERE lote NOT IN (SELECT lote FROM conciliacao)');
        $this->db->query('DELETE FROM `orders_conciliation_installments` WHERE lote = "" OR lote IS NULL OR lote NOT IN (SELECT lote FROM conciliacao_sellercenter)');
        $this->db->query('DELETE FROM `orders_payment_date` WHERE order_id NOT IN (SELECT order_id FROM orders_conciliation_installments)');

    }

    public function setStoreUsingCycle(int $storeId, $use_exclusive_cycle = 1)
    {

        $this->db->where('id', $storeId);
        return $this->db->update('stores', ['use_exclusive_cycle' => $use_exclusive_cycle]);

    }

    /**
     * Exclui ‘items’ não pagos para reconstruir nova data de pagamento para os pedidos
     */
    public function deletePreviousGeneratedConciliationData()
    {
        //Deleting not paid itens to regenerate new payment date for orders
        $this->db->query('DELETE FROM `conciliacao_sellercenter` WHERE lote NOT IN (SELECT lote FROM conciliacao)');
        $this->db->query('DELETE FROM `orders_conciliation_installments` WHERE lote = "" OR lote IS NULL OR lote NOT IN (SELECT lote FROM conciliacao_sellercenter)');
        $this->db->query('DELETE FROM `orders_payment_date` WHERE order_id NOT IN (SELECT order_id FROM orders_conciliation_installments)');
    }

    /**
     * @param $rest
     * @return array|array[]
     */
    public function extractedQuery($rest): array
    {
        if (count($rest) > 0) {
            $this->db->limit($rest['per_page']);
            $this->db->offset($rest['page_per_page']);
        }

        $query = $this->db->get();

        $qty = $query->num_rows();

        $result = $query->result_array();
        $querys = $this->db->last_query();
        return count($rest) > 0 ? ['qty' => $qty, 'data' => $result] : $result;

    }

    /**
     * Pega os ciclos de um marketplace, compara com o novo ciclo enviado e retorna se o novo ciclo pode ser salvo
     *
     * @param int $integ_id
     * @param string|null $data_inicio
     * @param string|null $data_fim
     * @param null $vStore
     * @param null $hidden_id
     * @return bool
     */
    public function checkValidCycles(int $integ_id = 0, string $data_inicio = null, string $data_fim = null, $vStore = null, $hidden_id = null): bool
    {

        $cycles = $this->getCyclesByMarketplace($integ_id, $hidden_id, $vStore);

        $used_days = [];
        foreach ($cycles as $c) {
            $used_days = $this->getDaysCycle($c['data_inicio'], $c['data_fim'], $used_days);
        }

        $selected_days = [];
        $selected_days = $this->getDaysCycle($data_inicio, $data_fim, $selected_days);

        /**
         * Ordena os valores de cada array
         * @todo Verificar a necessidade da ordenação
         */
        sort($used_days, SORT_NUMERIC);
        sort($selected_days, SORT_NUMERIC);

        if ($this->checkValuesRepeatingArray($used_days, $selected_days)) {
            return self::CICLO_INVALIDO;
        }

        return self::CICLO_VALIDO;
    }

    /**
     * Pega individualmente os dias de início e fim de um ciclo e retorna em um array
     *
     * @param string $data_inicio
     * @param string $data_fim
     * @param array $array
     * @return    array
     */
    public function getDaysCycle(string $data_inicio, string $data_fim, array $array = []): array
    {

        if ($data_inicio > $data_fim) {
            for ($i = 31; $i >= $data_inicio; $i--) {
                if (!in_array($i, $array, true)) {
                    $array[] = $i;
                }
            }
            for ($i = 1; $i <= $data_fim; $i++) {
                if (!in_array($i, $array, true)) {
                    $array[] = $i;
                }
            }
        } else {
            for ($i = $data_inicio; $i <= $data_fim; $i++) {
                if (!in_array($i, $array, true)) {
                    $array[] = $i;
                }
            }
        }

        return $array;
    }

    /**
     * Verifica se algum valor do array A existe no array B
     *
     * @param array $array_1
     * @param array $array_2
     * @return    bool
     */
    public function checkValuesRepeatingArray(array $array_1 = [], array $array_2 = []): bool
    {
        foreach ($array_1 as $a) {
            if (in_array($a, $array_2)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $data_inicio
     * @param $data_fim
     * @param string $vDataPagamentoMkt
     * @param string $vDataPagamentoConectala
     * @param $vDateCut
     * @param $vStore
     * @param $hiddenId
     * @param $mktplace_choice
     * @param $vStores
     * @return false
     */
    public function saveCycle($data_inicio, $data_fim, string $vDataPagamentoMkt, string $vDataPagamentoConectala, $vDateCut, $vStore, $hiddenId, $mktplace_choice, $vStores): bool
    {

        $this->db->trans_begin();

        $data = [
            'data_inicio' => $data_inicio,
            'data_fim' => $data_fim,
            'data_pagamento' => $vDataPagamentoMkt,
            'data_pagamento_conecta' => empty($vDataPagamentoConectala) ? null : $vDataPagamentoConectala,
            'data_usada' => $vDateCut->cut_date,
            'data_inclusao' => date('Y-m-d H:i:s'),
            'store_id' => $vStore,
            'ativo' => 1,
        ];

        if ($hiddenId == 0) {
            $data['integ_id'] = $mktplace_choice;
            $this->insert($data);

            if (!is_null($vStore)) {
                $this->update($vStore, ['use_exclusive_cycle' => 1], 'stores');
            }

        } else {
            $this->update($hiddenId, $data, self::TABLE_CICLO);
        }

        if ($vStores) {
            $this->setStoreUsingCycle($vStores);
        }

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return false;
        }

        $this->db->trans_commit();
        return true;


    }

}