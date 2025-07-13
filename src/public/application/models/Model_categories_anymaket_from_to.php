<?php
/*

Model de Acesso ao BD para tabela de fretes de pedidos

 */

/**
 * Class Model_categories_anymaket_from_to
 * @property CI_DB_query_builder $db
 */
class Model_categories_anymaket_from_to extends CI_Model
{
    const TABLE = 'categories_anymaket_from_to';

    public function __construct()
    {
        parent::__construct();
    }

    public function create($data)
    {
        $this->db->trans_begin();
        $whereData = [
            'idCategoryAnymarket' => $data['idCategoryAnymarket'],
            'api_integration_id' => $data['api_integration_id'],
        ];
        $qtd = $this->db->select('*')->from(Model_categories_anymaket_from_to::TABLE)->where($whereData)->get()->num_rows();
        if ($qtd != 0) {
            return false;
        }
        $response = $this->db->insert(Model_categories_anymaket_from_to::TABLE, $data);
        $this->db->trans_commit();
        return $response;
    }

    public function update($id, $data)
    {
        return $this->db->update(Model_categories_anymaket_from_to::TABLE, $data, ['id' => $id]);
    }

    public function getData($whereData)
    {
        return $this->db->select('*')->from(Model_categories_anymaket_from_to::TABLE)->where($whereData)->get()->row_array();
    }

    public function delete($id)
    {
        return $this->db->delete(Model_categories_anymaket_from_to::TABLE, array('id' => $id));
    }

    public function getLinkedCategoriesByIntegrationId(int $integrationId): array
    {
        $this->db->select([
            'any.idCategoryAnymarket as category_external_id', 'cat.*', 'cat.id as category_id'
        ])->from('categories_anymaket_from_to as any')
            ->join('api_integrations api', 'any.api_integration_id = api.id')
            ->join('stores store', 'api.store_id = store.id')
            ->join('categories cat', 'any.categories_id = cat.id');

        $this->db->where('any.api_integration_id', (string)$integrationId);

        $this->db->group_by(['any.categories_id']);
        return $this->db->get()->result_array() ?? [];
    }

    public function getCategoryByExternalId($externalId, $integrationId): array
    {
        $this->db->select([
            'any.idCategoryAnymarket as category_external_id', 'cat.*', 'cat.id as category_id'
        ])->from('categories_anymaket_from_to as any')
            ->join('api_integrations api', 'any.api_integration_id = api.id')
            ->join('stores store', 'api.store_id = store.id')
            ->join('categories cat', 'any.categories_id = cat.id');

        $this->db->where('any.api_integration_id', (string)$integrationId);
        $this->db->where([$this->db->compile_binds("any.idCategoryAnymarket = ?", $externalId) => null]);

        return $this->db->get()->row_array() ?? [];
    }
}
