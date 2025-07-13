<?php

class Model_order_payment_transactions extends CI_Model
{
    const TABLE = 'order_payment_transactions';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Cria um registro na tabela.
     *
     * @param   array       $data   Dados para criação.
     * @return  int|null            Retorna o código gerado ou nulo em caso de erro.
     */
    public function create(array $data = array()): ?int
    {
        if ($data && $this->db->insert(self::TABLE, $data)) {
            return $this->db->insert_id();
        }

        return null;
    }

    public function getTransaction(array $where = array(), $fields = '*'): array
    {
        if (empty($where)) {
            return array();
        }

        return $this->db->select($fields)->from(self::TABLE)->where($where)->order_by('interaction_date', 'DESC')->get()->result_array();
    }

    public function getLastTransactionsByOrder(int $order): array
    {
        $this->db->select('MAX(b.interaction_date)');
        $this->db->where('b.order_id = a.order_id');
        $this->db->where('b.payment_id = a.payment_id');
        $this->db->group_by('b.order_id, b.payment_id');
        $subQuery = $this->db->get_compiled_select(self::TABLE . ' AS b', true);

        $this->db->select("a.interaction_date, a.payment_id, a.status, op.transaction_status");
        $this->db->join('orders_payment as op', 'op.id = a.payment_id');
        $this->db->where("a.interaction_date = ($subQuery)");
        $this->db->where('a.order_id', $order);
        $this->db->group_by('a.order_id, a.payment_id');

        return $this->db->get(self::TABLE . ' AS a')->result_array();
    }

    public function update($data, $id)
    {
        if ($data && $id) {
            $this->db->where('id', $id);
            $update = $this->db->update(self::TABLE, $data);
            return $update ? $id : false;
        }
    }

}
