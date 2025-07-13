<?php

/**
 * Class Model_integration_logistic
 * @property CI_DB_query_builder $db
 */
class Model_integration_logistic extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function createBatch(array $data): bool
    {
        return $data && $this->db->insert_batch('integration_logistic', $data);
    }

    public function getIntegrationsSellerActiveNotUse()
    {
        return $this->db->select('id, name, external_integration_id')->where(['active' => true, 'use_seller' => false])->get('integrations_logistic')->result_array();
    }

    public function getIntegrationsSellerCenterActiveNotUse()
    {
        return $this->db->select('id, name, description, external_integration_id')->where(['active' => true, 'only_store' => false, 'use_sellercenter' => false])->get('integrations_logistic')->result_array();
    }

    public function getIntegrationsByName($name)
    {
        return $this->db->where('name', $name)->get('integrations_logistic use index(ix_integrations_logistic_name)')->row_array();
    }

    public function getIntegrationsById($id)
    {
        return $this->db->where('id', $id)->get('integrations_logistic')->row_array();
    }

    public function getIntegrationByName(string $name, int $store_id)
    {
        return $this->db->where(array('store_id' => $store_id, 'active' => true, 'integration' => $name))->get('integration_logistic use index(ix_integration_logistic_store_id_active_integration)')->row_array();
    }

    public function createNewIntegrationLogistic(array $data): bool
    {
        return $data && $this->db->insert('integrations_logistic', $data);
    }

    public function createNewIntegrationByStore($data)
    {
        return $data && $this->db->insert('integration_logistic', $data);
    }

    public function updateIntegrationByIntegration($id, $data)
    {
        return $this->db->where('id', $id)->update('integration_logistic', $data);
    }

    public function getIntegrationsInUseSellerCenter()
    {
        return $this->db->where(['use_sellercenter' => true, 'only_store' => false])->get('integrations_logistic')->result_array();
    }

    public function getIntegrationsInUseSeller()
    {
        return $this->db->get('integrations_logistic')->result_array();
    }

    public function updateIntegrationsInUse($data, $id)
    {
        return $this->db->where('id', $id)->update('integrations_logistic', $data);
    }

    public function removeIntegrationSellerCenter($integration, $store_id)
    {
        $backup = $this->db->where(array('integration' => $integration, 'store_id' => $store_id))->get('integration_logistic')->result_array();
        $removed = $this->db->delete('integration_logistic', array('integration' => $integration, 'store_id' => $store_id));
        return array_merge($backup, ['_deleted' => $removed]);
    }

    public function removeIntegrationSeller($integration)
    {
        $backup = $this->db->where(array('integration' => $integration, 'store_id !=' => 0))->get('integration_logistic')->result_array();
        $this->db->delete('integration_logistic', array('integration' => $integration, 'store_id !=' => 0));
        return $backup;
    }

    public function removeAllIntegrationSellerCenter()
    {
        $backup = $this->db->where('store_id', 0)->or_where('credentials', null)->get('integration_logistic')->result_array();
        $this->db->where('store_id', 0)->or_where('credentials', null)->delete('integration_logistic');
        return $backup;
    }

    public function removeAllIntegrationSeller()
    {
        $backup = $this->db->where('store_id !=', 0)->where('credentials !=', null)->get('integration_logistic')->result_array();
        $this->db->where('store_id !=', 0)->where('credentials !=', null)->delete('integration_logistic');
        return $backup;
    }

    public function updateAllIntegrationsInUse($data):bool
    {
        return $this->db->update('integrations_logistic', $data);
    }

    public function getIntegrationSeller($storeId)
    {
        return $this->db->where('store_id', $storeId)->get('integration_logistic')->row_array();
    }

    public function getIntegrationLogistic(int $store, int $status = null): ?array
    {
        $sql = "select
                    il.id,
                    ils.description,
                    il.active,
                    il.integration,
                    il.credentials,
                    il.id_integration,
                    ils.id as id_ils,
                    ils.use_seller,
                    il.store_id,
                    s.name as store_name
                FROM integration_logistic as il
                INNER JOIN integrations_logistic as ils ON (il.id_integration = ils.id)
                LEFT JOIN stores as s ON (s.id = il.store_id)
                WHERE il.store_id = ?";

        if ($status !== null) {
            $sql .= " AND il.active = $status";
        }

        $query = $this->db->query($sql, array($store));
        return $query->result_array();
    }

    public function removeIntegrationByStore(int $storeId): bool
    {
        return (bool)$this->db->delete('integration_logistic', array('store_id' => $storeId));
    }

    public function getLogisticAvailableBySellerCenter(string $integration): bool
    {
        if ($dataIntegration = $this->db->where(['name' => $integration, 'use_sellercenter' => true, 'active' => true])->get('integrations_logistic')->row_array()) {
            return (bool)$this->db->where(['store_id' => 0, 'id_integration' => $dataIntegration['id'], 'credentials !=' => '{}'])->get('integration_logistic')->row_array();
        }

        return false;
    }

    public function getIntegrationsSellerByIntegration(int $id_integration): array
    {
        return $this->db->where('id_integration', $id_integration)
            ->where('credentials IS NULL', NULL, FALSE)
            ->get('integration_logistic')
            ->result_array();
    }

    public function getAllIntegrationSellerCenter()
    {
        return $this->db->where(['use_sellercenter' => true, 'active' => true])->get('integrations_logistic')->result_array();
    }

    public function getStoresIntegrationsSellerCenterByInt(int $integration)
    {
        return $this->db->where('id_integration', $integration)->where('credentials IS NULL', NULL, FALSE)->get('integration_logistic')->result_array();
    }

    public function getIntegrationsByStoreId($store_id)
	{
		$sql = "SELECT credentials FROM integration_logistic WHERE store_id = ?";
		$query = $this->db->query($sql, array($store_id));
		return $query->row_array();
		if ($row) {
			return $row['credentials'];
		} else {
			return false;
		}
		
	}

    public function getAllIntegration()
    {
        return $this->db->where('active', true)->get('integrations_logistic')->result_array();
    }

    public function getIntegrationLogisticeById($id): array
    {
        return $this->db->where('id', $id)->get('integration_logistic')->row_array() ?? [];
    }

    public function removeIntegrationsSellerByMarketplaceContract(string $integration)
    {
        $this->db->where('credentials IS NULL', NULL, FALSE)
            ->where(array('integration' => $integration, 'store_id !=' => 0))
            ->delete('integration_logistic');
    }

    public function updateIntegrationByIntegrationName(array $data, string $name)
    {
        return $this->db->where('name', $name)->update('integrations_logistic', $data);
    }

    public function getByExternalIntegrationId(int $external_integration_id): ?array
    {
        return $this->db->get_where('integrations_logistic', array('external_integration_id' => $external_integration_id))->row_array();
    }

}