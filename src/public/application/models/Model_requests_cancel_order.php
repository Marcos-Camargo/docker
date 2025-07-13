<?php
/*
 Model de Acesso ao BD para requests_cancel_order
 */

class Model_requests_cancel_order extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Recupera o motivo da solicitação de cancelamento
     *
     * @param   int         $order_id   Código do pedido (orders.id)
     * @return  false|array             Retorna os dados do motivo da solicitação do cancelamento, caso não encontre retornará null
     */
    public function getLastReasonByOrder(int $order_id)
    {
        if($order_id) {
            $query = $this->db->select('rco.reason, rco.date_created, rco.old_status, u.email')
                ->from('requests_cancel_order rco')
                ->where(array('rco.order_id' => $order_id))
                ->join('users u', 'rco.user_id = u.id', 'left')
                ->order_by('rco.id', 'DESC')
                ->limit(1)
                ->get();

            if ($query->num_rows() === 1)
                return $query->row_array();
        }

        return null;
    }

    /**
     * Criar uma nova solicitação de cancelamento
     *
     * @param   array $data Dados da solicitação de cancelamento
     * @return  false       Retonar o id do registro criado, caso encontre algo problema retornará 'false'
     */
    public function create(array $data): bool
    {
        if($data) {
            $insert = $this->db->insert('requests_cancel_order', $data);
            return ($insert == true) ? $this->db->insert_id() : false;
        }
        return false;
    }

    public function getFirstReasonCancel(int $order_id)
    {
        if($order_id) {
            $query = $this->db->select('rco.reason, rco.date_created, rco.old_status, u.email')
                ->from('requests_cancel_order rco')
                ->where(array('rco.order_id' => $order_id))
                ->where('rco.old_status !=', '90')
                ->join('users u', 'rco.user_id = u.id', 'left')
                ->order_by('rco.id', 'DESC')
                ->limit(1)
                ->get();

            if ($query->num_rows() === 1)
                return $query->row_array();
        }

        return null;
    }
}