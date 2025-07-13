<?php

class Model_external_integration_history extends CI_Model
{
    protected $table = 'external_integration_history';

	public function __construct()
	{
		parent::__construct();
	}

    public function create(array $data): bool
    {
        if (array_key_exists('register_id', $data) && array_key_exists('type', $data) && array_key_exists('method', $data)) {
            $this->deleteByRegisterIdAndTypeAndMethod($data['register_id'], $data['type'], $data['method']);
        }
        return $this->db->insert($this->table, $data);
    }

    public function deleteByRegisterIdAndTypeAndMethod(string $register_id, string $type, string $method)
    {
        return $this->db->delete($this->table, array(
            'register_id'   => $register_id,
            'type'          => $type,
            'method'        => $method
        ));
    }

    public function getByRegisterIdAndTypeAndMethod(string $register_id, string $type, string $method): array
    {
        return $this->db->where(array(
                'register_id'   => $register_id,
                'type'          => $type,
                'method'        => $method)
            )
            ->order_by('id', 'DESC')
            ->get($this->table)
            ->result_array();
    }

    public function getByTypeAndMethod(string $type, string $method): array
    {
        return $this->db->get_where($this->table, array('type' => $type, 'method' => $method))->result_array();
    }

    public function updateByExternalId(string $external_id, array $data): bool
    {
        return $this->db->where('external_id', $external_id)->update($this->table, $data);
    }

    public function getByExternalId(string $external_id): ?array
    {
        return $this->db->where('external_id', $external_id)->get($this->table)->row_array();
    }

    public function getById(int $id): ?array
    {
        return $this->db->get_where($this->table, array('id' => $id))->row_array();
    }

    public function getLastRowByTypeAndMethodAndRegisterId(string $type, string $method, string $register_id): ?array
    {
        return $this->db->where(array(
            'type' => $type,
            'method' => $method,
            'register_id' => $register_id
        ))
        ->order_by('id', 'DESC')
        ->limit(1)
        ->get($this->table)
        ->row_array();
    }

    public function getErroNotifications(): array
    {
        return $this->db->get_where($this->table, array('status_webhook' => 0))->result_array();
    }

    public function getOrdersToIntegrationByDate(string $date): array
    {
        return $this->db
            ->select("orders.*")
            ->join($this->table, "orders.id = $this->table.register_id",'left')
            ->where("$this->table.id IS NULL", null, false)
            ->where("CAST(orders.date_time AS DATE) >= '$date'", null, false)
            ->get('orders')
            ->result_array();
    }
	
}