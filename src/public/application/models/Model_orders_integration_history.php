<?php

/**
 * @property CI_DB_driver $db
 */
class Model_orders_integration_history extends CI_Model
{
    protected $table = 'orders_integration_history';

	public function __construct()
	{
		parent::__construct();
	}

    public function create(array $data): bool
    {
        if (
            array_key_exists('order_id', $data) &&
            array_key_exists('type', $data) &&
            array_key_exists('request_method', $data)
        ) {
            $data_exists = $this->db->get_where($this->table, array(
                'order_id'       => $data['order_id'],
                'type'           => $data['type'] ,
                'request_method' => $data['request_method']
            ))->row_array();

            if ($data_exists) {
                $this->db->delete($this->table, array('id' => $data_exists['id']));
            }
        }


        return (bool)$this->db->insert($this->table, $data);
    }

    public function getByOrderIdAndType(int $order_id, string $type): array
    {
        return $this->db->get_where($this->table, array('order_id' => $order_id, 'type' => $type))->result_array();
    }
	
}