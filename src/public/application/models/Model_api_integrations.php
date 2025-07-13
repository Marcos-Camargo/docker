<?php
/*

Model de Acesso ao BD para tabela de fretes de pedidos

 */


require_once APPPATH . "libraries/Microservices/v1/Logistic/ShippingIntegrator.php";

use Microservices\v1\Logistic\ShippingIntegrator;

/**
 * Class Model_api_integrations
 * @property CI_DB_query_builder $db
 * @property CI_Loader $load
 * @property ShippingIntegrator $ms_shipping_integrator
 */
class Model_api_integrations extends CI_Model
{
    const TABLE = 'api_integrations';

    const ACTIVE_STATUS = 1;
    const INACTIVE_STATUS = 2;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('model_users');

        $this->load->library("Microservices\\v1\\Logistic\\ShippingIntegrator", [], 'ms_shipping_integrator');
    }

    /**
     * Cria registro da integração da loja.
     *
     * @param   array   $data   Dados para atualização
     * @return  bool            Estado da atualização
     */
    public function create(array $data, bool $remove_to_create = false): bool
    {
        if ($remove_to_create) {
            $this->db->where(array(
                'integration' => $data['integration'],
                'store_id' => $data['store_id'],
            ))->delete(Model_api_integrations::TABLE);
        }
        $this->saveApiIntegrationMS($data);
        $data['date_created'] = $data['date_created'] ?? date('Y-m-d H:i:s');
        $data['date_updated'] = $data['date_updated'] ?? date('Y-m-d H:i:s');
        return $this->db->insert(Model_api_integrations::TABLE, $data);
    }

    public function getInsertId(): int
    {
        return $this->db->insert_id() ?? 0;
    }

    public function getUserByOI($oi)
    {
        $integration = $this->db->select(
            '`int`.*, `int`.id as integration_id, c.id as company_id, s.name as store_name'
        )->from('api_integrations `int`')
        ->join('stores s', 'int.store_id = s.id')
        ->join('company c', 's.company_id = c.id')
        ->where('`int`.id_anymarket_oi', $oi)->get()->result_array();

        if (empty($integration)) {
            return false;
        } else {
            return $integration;
        }
    }
    public function getAllAnyMarket()
    {
        $integration = $this->db->select('int.*, s.name as store_name')
            ->from('api_integrations int')
            ->join('stores s', 'int.store_id = s.id')
            ->join('company c', 's.company_id = c.id')
            ->where('id_anymarket_oi <>', 'NULL', '')->get()->result_array();
        if (empty($integration)) {
            return false;
        } else {
            return $integration;
        }
    }

    public function getIntegrationById(int $id)
    {
        return $this->db->select('int.*, s.name as store_name, s.company_id')
            ->from('api_integrations int')
            ->join('stores s', 'int.store_id = s.id')
            ->join('company c', 's.company_id = c.id')
            ->where([
                'int.id' => $id
            ])
            ->get()->row_array();
    }

    public function getByIntegrationsName(array $integrations = [])
    {
        return $this->db->select('int.*, s.name as store_name, s.company_id')
            ->from('api_integrations int')
            ->join('stores s', 'int.store_id = s.id')
            ->join('company c', 's.company_id = c.id')
            ->where_in('LOWER(int.integration)', $integrations)
            ->where([
                'int.status' => 1,
                's.active' => 1
            ])
            ->get()->result_array();
    }

    public function getStoresAndIntegrationIfExists(array $criteria = [])
    {
        $this->db->select('int.*, int.id as integration_id, s.name as store_name, s.company_id, s.id as store_id')
            ->from('stores s')
            ->join('company c', 's.company_id = c.id')
            ->join('api_integrations int', '(s.id = int.store_id AND int.status = 1)', 'LEFT');
        if (!empty($criteria['store_id'] ?? 0)) {
            $this->db->where('s.id', $criteria['store_id']);
        }
        if (!empty($criteria['integrations'] ?? [])) {
            $this->db->where_in('LOWER(int.integration)', $criteria['integrations']);
        }
        return $this->db->where('s.active', 1)->get()->result_array();
    }

    public function getDataOnIntegrationAnyMarket($dados)
    {
        $user = $this->model_users->getUserByEmailOrLogin($dados['login']);
        // $store = $this->model_stores->getStoreByTokenAndNameOrId($dados['token_in'], $dados['store']);
        $or_store=[
			'id' => trim($dados['store']),
			'name' => trim($dados['store'])
		];
		$store= $this->db->select('*')->from('stores')->where('token_api', trim($dados['token_in']))->group_start()->or_where($or_store)->group_end()->get()->row_array();
        return ['dados' => $dados, 'user' => $user, 'store' => $store];
    }

    public function createIntegrationAnymarket($dados)
    {
        return $this->db->insert(Model_api_integrations::TABLE, $dados);
    }

    public function update($id, array $data)
    {
        $this->saveApiIntegrationMS(array_merge($data, ['id' => $id]));
        $this->db->where('id', $id)->update(Model_api_integrations::TABLE, $data);
    }

    public function getDataByIntegration($name)
    {
        $this->db->select('*')
            ->from('api_integrations');
        $this->db->where_in('integration', is_array($name) ? $name : [$name]);
        $integration = $this->db->get()->result_array();
        if (empty($integration)) {
            return false;
        } else {
            return $integration;
        }
    }

    public function getUserByAnyByStore($store)
    {
        $query = $this->db->select(
            'int.*, int.id as integration_id, c.id as company_id'
        )->from(Model_api_integrations::TABLE.' int')
            ->join('stores s', 'int.store_id = s.id')
            ->join('company c', 's.company_id = c.id')
            ->where('id_anymarket_oi IS NOT NULL', null, false)
            ->where(['store_id' => $store])->get();
        if ($query->num_rows() != 1) {
            return false;
        } else {
            return $query->row_array();
        }
    }

    public function getIntegrationByStoreId($storeId)
    {
        return $this->db->select(['int.*', 'c.id as company_id', 's.active', 's.name as store_name', 'erp.hash', 'erp.description as int_description'])
            ->from('api_integrations int')
            ->join('integration_erps erp', '(int.integration_erp_id = erp.id)', 'LEFT')
            ->join('stores s', 'int.store_id = s.id')
            ->join('company c', 's.company_id = c.id')
            ->where('s.id', $storeId)->get()->row_array();
    }

    public function getStoreByCompanyIdAndIntegration($companyId, $integration)
    {
        $db = $this->db->select(['s.*'])
            ->from('api_integrations int')
            ->join('stores s', 'int.store_id = s.id')
            ->join('company c', 's.company_id = c.id')
            ->where('LOWER(int.integration)', strtolower($integration));
        $db->where('c.id', $companyId);
        return $db->get()->row_array();
    }

    public function getStoresByCompanyIdWithoutIntegration($companyId)
    {
        $db = $this->db->select(['s.*', 'c.name as company_name'])
            ->from('stores s')
            ->join('company c', 's.company_id = c.id')
            ->join('api_integrations int', 's.id = int.store_id', 'LEFT')
            ->where('int.id IS NULL', null, false)
            ->where('s.active', 1);
        if ($companyId !== 1) {
            $db->where('c.id', $companyId);
        }
        return $db->get()->result_array();
    }

    public function getIntegrationByCompanyId($companyId, $integration)
    {
        $db = $this->db->select(['int.*', 'c.id as company_id', 's.active', 's.name as store_name'])
            ->from('api_integrations int')
            ->join('stores s', 'int.store_id = s.id')
            ->join('company c', 's.company_id = c.id')
            ->where('LOWER(int.integration)', strtolower($integration))
            ->where('s.active', 1);
        if ($companyId !== 1) {
            $db->where('c.id', $companyId);
        }
        return $db->get()->row_array();
    }

    public function getDataByStore($store_id, $first = false)
    {
        $integration = $this->db->select('*')->from('api_integrations')->where('store_id', $store_id)->get()->result_array();
        if (empty($integration)) {
            return false;
        } else {
            if ($first) {
                return $integration[0];
            }
            return $integration;
        }
    }

    /**
     * Recupera os dados de integração de uma loja
     *
     * @param   int     $store_id   Código da loja (stores.id)
     * @return  mixed
     */
    public function getIntegrationByStore(int $store_id)
    {
        return $this->db
            ->select(['`int`.*', 's.name as store_name', 's.active', 'erp.configuration'])
            ->from("api_integrations `int`")
            ->join('stores s', 'int.store_id = s.id')
            ->join('integration_erps erp', 'int.integration_erp_id = erp.id', 'LEFT')
            ->where('int.store_id', $store_id)
            ->get()
            ->row_array();
    }

    /**
     * Atualiza dados da integração da loja
     *
     * @param   int     $store  Código da loja
     * @param   array   $data   Dados para atualização
     * @return  bool            Estado da atualização
     */
    public function updateByStore(int $store, array $data): bool
    {
        $this->saveApiIntegrationMS(array_merge($data, ['store_id' => $store]));
        return (bool)$this->db->where('store_id', $store)->update(Model_api_integrations::TABLE, $data);
    }

    /**
     * Atualiza dados da integração da loja.
     *
     * @param   string      $data   Dado da integração para consulta.
     * @return  string|null         Estado da atualização.
     */
    public function getStoreByDataCredentials(string $data): ?string
    {
        $dataIntegration = $this->db->from(Model_api_integrations::TABLE)->like('credentials', $data)->get()->row_array();
        return $dataIntegration['store_id'] ?? null;
    }

    /**
     * Recupera as integradoras disponíveis.
     *
     * @return  array Nome das integradoras
     */
    public function getNameIntegrationsActive(): array
    {
        return $this->db->select('integration')->from(Model_api_integrations::TABLE)->group_by('integration')->get()->result_array();
    }

    public function getIntegrationsByCredentialsFieldValue($field, $value): array
    {
        $jsonSearch = json_encode([$field => $value]);
        if (empty($jsonSearch)) return [];

        return $this->db->select([
            'int.*', 'comp.id as company_id', 'comp.name as company_name', 'store.name as store_name', 'store.active', 'int.id as integration_id'
        ])
            ->join('stores store', 'int.store_id = store.id')
            ->join('company comp', 'store.company_id = comp.id')
            ->get_where('api_integrations int',
                $this->db->compile_binds("JSON_CONTAINS(IF(JSON_VALID(int.credentials), int.credentials, '{}'), ?)", [$jsonSearch])
            )
            ->result_array();
    }

    public static function isActiveIntegration($integration)
    {
        if(empty($integration)) return false;
        $integration['status'] = (int)($integration['status'] ?? Model_api_integrations::ACTIVE_STATUS);
        $integration['active'] = (int)($integration['active'] ?? Model_api_integrations::ACTIVE_STATUS);
        return (
            $integration['status'] === Model_api_integrations::ACTIVE_STATUS
            && $integration['active'] === Model_api_integrations::ACTIVE_STATUS
        );
    }

    public function getIntegrationsWithCredentials(): array
    {
        return $this->db
            ->select('integration')
            ->where('credentials != ', '{}')
            ->group_by("integration")
            ->get('api_integrations')
            ->result_array();
    }

    protected function saveApiIntegrationMS($integration): bool
    {
        if ($this->ms_shipping_integrator->use_ms_shipping) {
            $data = [];
            if (($integration['id'] ?? 0) > 0) {
                $data = $this->getIntegrationById($integration['id']);
            } else {
                if (($integration['store_id'] ?? 0) > 0) {
                    $data = $this->getIntegrationByStoreId($integration['store_id']);
                }
            }
            $dataIntegration = empty($data) ? $integration : array_merge($data, $integration);
            $this->ms_shipping_integrator->setStore($dataIntegration['store_id'] ?? null);
            $dataIntegration['integration'] = $dataIntegration['integration'] ?? $dataIntegration['integration_name'] ?? $dataIntegration['name'] ?? '';
            if (!empty($this->ms_shipping_integrator->getConfigure($dataIntegration['integration']))) {
                try {
                    if (!empty($dataIntegration['credentials'] ?? [])) {
                        $dataIntegration['credentials'] = is_string($dataIntegration['credentials']) ? json_decode($dataIntegration['credentials']) : $dataIntegration['credentials'];
                    }
                    $this->ms_shipping_integrator->updateConfigure($dataIntegration['integration'], array_merge($dataIntegration, ['active' => true]));
                    return true;
                } catch (Throwable $e) {
                }
            }
        }
        return false;
    }

    /**
     * Recupera os dados de integração de lojas Via Varejo
     *
     * @return  mixed
     */
    public function getIntegrationByStoreVia()
    {
        return $this->db
        ->select(['`int`.*', 's.company_id', 's.name as nome_loja'])
        ->from("api_integrations `int`")
        ->join('stores s', 'int.store_id = s.id')
        ->where('s.active', 1)
        ->where('int.description_integration', 'Via Varejo B2B')
        ->where('int.status', 1)
        ->get()->result_array();
    }
}
