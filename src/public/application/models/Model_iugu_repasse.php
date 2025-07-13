<?php

/**
 * Class Model_iugu_repasse
 */
class Model_iugu_repasse extends CI_Model
{

    public $tableName = 'iugu_repasse';

    public function __construct()
    {
        parent::__construct();
    }

    public function insert(int $orderId, string $marketplaceNumber, string $splitDate, string $transferDate,
                           string $transferDateBankAccount, int $conciliationId, int $partnerValue, int $affiliateValue): bool
    {

        $data = [];
        $data['order_id'] = $orderId;
        $data['numero_marketplace'] = $marketplaceNumber;
        $data['data_split'] = $transferDate;
        $data['data_transferencia'] = $splitDate;
        $data['data_repasse_conta_corrente'] = $transferDateBankAccount;
        $data['conciliacao_id'] = $conciliationId;
        $data['valor_parceiro'] = $partnerValue;
        $data['valor_afiliado'] = $affiliateValue;

        return $this->create($data);

    }

    public function create($data): bool
    {
        if ($data) {
            $insert = $this->db->insert($this->tableName, $data);
            return $insert == true;
        }
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

    public function cycleExistsByStoreIdCycleDay(string $transferDate, int $storeId): bool
    {

        $sql = "SELECT count(*) count FROM `iugu_repasse` WHERE DATE_FORMAT(data_transferencia, '%Y-%m-%d') = '$transferDate'
                AND order_id IN (SELECT DISTINCT id FROM orders WHERE store_id = $storeId) ";

        $query = $this->db->query($sql);
        $retorno = $query->row_array();

        return $retorno['count'] > 0;

    }

    public function getTotalPaidByOrder(int $orderId): float
    {

        $sql = "SELECT SUM(valor_parceiro) as total_paid FROM iugu_repasse WHERE order_id = $orderId";

        $query = $this->db->query($sql);

        $retorno = $query->row_array();

        return $retorno['total_paid'] ?: 0;

    }

}