<?php

class Model_campaigns_v2_elegible_products extends CI_Model
{

    private $tableName = 'campaign_v2_elegible_products';
    private $createLog;

    public function __construct()
    {
        parent::__construct();

        $this->load->library('CampaignsV2Logs');
        $this->createLog = new CampaignsV2Logs();
    }

    public function create(array $data)
    {
        $insert = $this->db->insert($this->tableName, $data);

        $id = $this->db->insert_id();

        $this->createLog->log($data, $id, $this->tableName, __FUNCTION__);

        return ($insert == true) ? $id : false;
    }

    public function createOrUpdate(array $data)
    {
        // Define o identificador único com base nas chaves relevantes
        $uniqueIdentifier = [
            'campaign_v2_id' => $data['campaign_v2_id'],
            'product_id' => $data['product_id']
        ];

        // Include prd_variant_id in the unique identifier if feature flag is enabled and prd_variant_id is set
        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku') && isset($data['prd_variant_id'])) {
            $uniqueIdentifier['prd_variant_id'] = $data['prd_variant_id'];
        }

        // Verifica se o registro já existe com base no identificador único
        $this->db->where($uniqueIdentifier);
        $query = $this->db->get($this->tableName);

        if ($query->num_rows() > 0) {
            // Se o registro já existir, atualize os dados
            $this->db->where($uniqueIdentifier);
            $update = $this->db->update($this->tableName, $data);

            $id = $query->row()->id ?? null; // Presume que a coluna 'id' é a chave primária, ajuste se necessário

            $this->createLog->log($data, $id, $this->tableName, __FUNCTION__ . '_update');

            //Recalculando o desconto do produto caso já tenha sido adicionado
            $this->model_campaigns_v2_products->recalculateDiscount($data['campaign_v2_id'], $data['product_id'], $data);

            return $update ? $id : false;
        } else {
            // Caso não exista, insira os dados
            $insert = $this->db->insert($this->tableName, $data);

            $id = $this->db->insert_id();

            $this->createLog->log($data, $id, $this->tableName, __FUNCTION__ . '_create');

            return $insert ? $id : false;
        }
    }


    public function getByPk(int $pk): array
    {

        $this->db->select("*");

        $this->db->from($this->tableName);

        $this->db->where('id', $pk);

        return $this->db->get()->row_array();

    }

    public function deleteByPk(int $pk): bool
    {
        return $this->db->delete($this->tableName, array('id' => $pk));
    }

    public function getByCampaignId(int $campaignId): array
    {

        $this->db->select("*");

        $this->db->from($this->tableName);

        $this->db->where('campaign_v2_id', $campaignId);

        return $this->db->get()->result_array();

    }

    public function getByCampaignIdPaginated(int $campaignId, $limit = null, $offset = null): array
    {

        $this->db->select("*");
        $this->db->from($this->tableName);
        $this->db->where('campaign_v2_id', $campaignId);
        $this->db->limit($limit, $offset);
        $products = $this->db->get()->result_array();

        $this->db->where('campaign_v2_id', $campaignId);
        $total_records = $this->db->count_all_results($this->tableName);

        return [
            'products' => $products,
            'total' => $total_records,
        ];

    }

    public function getProductByCampaignId(int $campaignId, int $productId, string $prd_variant_id = null): ?array
    {

        $this->db->select("*");

        $this->db->from($this->tableName);

        $this->db->where('campaign_v2_id', $campaignId);
        $this->db->where('product_id', $productId);

        // Include prd_variant_id in the query if feature flag is enabled and prd_variant_id is provided
        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku') && $prd_variant_id) {
            $this->db->where('prd_variant_id', $prd_variant_id);
        }

        return $this->db->get()->row_array();

    }

    public function getProductParticipatingAnotherCampaign(
        string $startDate,
        string $endDate,
        ?int $productId = null,
        ?string $tradePolicyId = null,
        ?string $paymentMethodId = null,
        ?string $storeId = null,
        ?string $brandId = null,
        ?string $categoryId = null
    ): ?array {

        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){
            $sql = "SELECT campaign_v2_elegible_products.*, campaign_v2.vtex_campaign_update, campaign_v2.occ_campaign_update
                    FROM campaign_v2_elegible_products
                        JOIN campaign_v2 ON ( campaign_v2_elegible_products.campaign_v2_id = campaign_v2.id ) 
                        LEFT JOIN products ON (products.id = campaign_v2_elegible_products.product_id) ";
        }else{
            //@todo pode remover
            $sql = "SELECT campaign_v2_elegible_products.*, campaign_v2.vtex_campaign_update
                    FROM campaign_v2_elegible_products
                        JOIN campaign_v2 ON ( campaign_v2_elegible_products.campaign_v2_id = campaign_v2.id ) 
                        LEFT JOIN products ON (products.id = campaign_v2_elegible_products.product_id) ";
        }
        if ($tradePolicyId) {
            $sql .= " LEFT JOIN campaign_v2_trade_policies ON (campaign_v2_trade_policies.campaign_v2_id = campaign_v2.id) ";
        }
        if ($paymentMethodId) {
            $sql .= " LEFT JOIN campaign_v2_payment_methods ON (campaign_v2_payment_methods.campaign_v2_id = campaign_v2.id ) ";
        }
        $sql .= " WHERE campaign_v2.b2w_type = 0 ";
        if ($productId) {
            $sql .= " AND campaign_v2_elegible_products.product_id = $productId ";
        }

        if ($brandId && $storeId) {
            $sql .= " AND campaign_v2_elegible_products.product_id IN (SELECT id FROM products WHERE products.store_id = $storeId AND products.brand_id = '[\"$brandId\"]') ";
        } elseif ($categoryId && $storeId) {
            $sql .= " AND campaign_v2_elegible_products.product_id IN (SELECT id FROM products WHERE products.store_id = $storeId AND products.category_id = '[\"$categoryId\"]') ";
        } elseif ($storeId) {
            $sql .= " AND campaign_v2_elegible_products.product_id IN (SELECT id FROM products WHERE products.store_id = $storeId) ";
        }

        $sql .= "   AND (('$startDate' BETWEEN campaign_v2.start_date AND campaign_v2.end_date ) OR ('$endDate' BETWEEN campaign_v2.start_date AND campaign_v2.end_date) )
                    AND campaign_v2.active = 1 ";

        //Comissão por produto
        if ($tradePolicyId && $paymentMethodId) {
            $sql .= "AND ( ";

            //Pode estar em uma política comercial
            $sql .= " campaign_v2_trade_policies.trade_policy_id = $tradePolicyId ";
            //Pode estar em um método de pagamento
            $sql .= " OR campaign_v2_payment_methods.method_id = $paymentMethodId ";
            //Se não tá em campanha por meio de pagamento e política comercial, pegamos também todos os produtos da loja
            $sql .= " OR (campaign_v2_trade_policies.id IS NULL 
                            AND campaign_v2_payment_methods.method_id IS NULL 
                            AND products.store_id = $storeId)";

            $sql .= " ) ";
        } elseif ($tradePolicyId) {
            $sql .= " AND ( campaign_v2_trade_policies.trade_policy_id = $tradePolicyId";
            $sql .= " OR (campaign_v2_trade_policies.id IS NULL AND products.store_id = $storeId))";
        }

        $query = $this->db->query($sql);

        return $query->result_array();

    }

}
