<?php

/**
 * Classe responsável por salvar os logs de cadastro de conta nos Gateways de Pagamento
 * Class Model_payment_gateway_store_logs
 */
class Model_payment_gateway_store_logs extends CI_Model
{

    public const STATUS_ERROR = "error";
    public const STATUS_SUCCESS = "success";
    public const STATUS_PENDENCIES = "pendencies";

    public $tableName = 'payment_gateway_store_logs';

    public function __construct()
    {
        parent::__construct();

    }

    /**
     * @param int $storeId
     * @param int $paymentGatewayId
     * @param string $status
     * @param string $description
     */
    public function insertLog(int $storeId, int $paymentGatewayId, string $description, string $status = self::STATUS_ERROR): void
    {

        $data = [
            'store_id' => $storeId,
            'payment_gateway_id' => $paymentGatewayId,
            'status' => $status,
            'description' => $description,
        ];

        $this->create($data);

    }

    public function create(array $data): bool
    {
        $insert = $this->db->insert($this->tableName, $data);
        return $insert == true;
    }

    public function hasLog(int $storeId, int $paymentGatewayId, $status = self::STATUS_ERROR): bool
    {
        $sql = "SELECT COUNT(*) total FROM {$this->tableName} WHERE store_id = ? AND payment_gateway_id = ? AND status = ?";
        $query = $this->db->query($sql, [$storeId, $paymentGatewayId, $status]);
        $query = $query->row_array();
        return $query['total'];
    }

    public function getLogs(int $storeId, int $paymentGatewayId): array
    {
        $sql = "SELECT l.* 
                    FROM payment_gateway_store_logs l
                    WHERE l.store_id = ? AND l.payment_gateway_id = ?
                    ORDER BY l.date_insert DESC LIMIT 20";
        $query = $this->db->query($sql, [$storeId, $paymentGatewayId]);
        return $query ? $query->result_array() : [];
    }

    public function update(array $data, int $id): bool
    {
        $this->db->where('id', $id);
        $update = $this->db->update($this->tableName, $data);
        return $update == true;
    }

    public function remove(int $id): bool
    {
        $this->db->where('id', $id);
        $delete = $this->db->delete($this->tableName);
        return $delete == true;
    }

}
