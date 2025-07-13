<?php
/**
 * @property CI_Loader $load
 * @property Model_orders $model_orders
 */

class Model_orders_with_problem extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Cria o registro de algum problema no pedido
     *
     * @param   array    $data      Array com os dados para criação do registro
     * @param   int|null $status    Status do pedido para ser atualizado
     * @return  bool                Status da criação do registro
     */
	public function createProblem(array $data, int $status = null): bool
	{
	    if ($data) {
            // inativa todos os erros.
            $this->inactiveProblemsOrder($data['order_id']);

	        $existProblem = $this->getProblemsByOrderAndDescription($data['order_id'], $data['description']);
	        if (!$existProblem) { // não existe o problema, vou criá-lo.
                $this->db->insert('orders_with_problem', $data);
            } else { // existe, atualizo a data de atualização
                $this->db->where('id', $existProblem['id']);
	            $this->db->update('orders_with_problem', array('date_updated' => date('Y-m-d H:i:s'), 'active' => true));
            }

	        if ($status) { // existe status para enviar o pedido
                $this->load->model('model_orders');

                // peiddo está no status diferente do que deverá enviar
                if ($this->model_orders->verifyStatus($data['order_id']) != $status) {
                    $this->model_orders->updatePaidStatus($data['order_id'], $status);
                }
            }
        }

	    return false;
	}

    /**
     * Recupera problema de um pedido
     *
     * @param   int     $order_id   Código do pedido
     * @return  array               Problemas do pedido
     */
    public function getProblemsByOrder(int $order_id): array
    {
        if($order_id) {
            return $this->db
                ->from('orders_with_problem')
                ->where([
                    'order_id'  => $order_id,
                    'active'    => 1
                ])
                ->get()
                ->result_array();
        }
        return [];
    }

    /**
     * Recupera problema de um pedido pela descrição
     *
     * @param   int         $order_id    Código do pedido
     * @param   string      $description Descrição do problema
     * @return  array|null               Problemas do pedido
     */
    public function getProblemsByOrderAndDescription(int $order_id, string $description): ?array
    {
        if($order_id) {
            return $this->db
                ->from('orders_with_problem')
                ->where([
                    'order_id'      => $order_id,
                    'description'   => $description
                ])
                ->get()
                ->row_array();
        }
        return null;
    }

    /**
     * Inativa os problemas de um pedido
     *
     * @param int $order_id Código do pedido
     */
    public function inactiveProblemsOrder(int $order_id)
    {
        $this->db->where('order_id', $order_id);
        $this->db->update('orders_with_problem', array('active' => 0));
    }

}