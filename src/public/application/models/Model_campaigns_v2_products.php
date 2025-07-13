<?php
use App\Libraries\Enum\CampaignTypeEnum;
use App\Libraries\Enum\DiscountTypeEnum;

class Model_campaigns_v2_products extends CI_Model
{

    private $tableName = 'campaign_v2_products';
    private $createLog;

    public function __construct()
    {
        parent::__construct();

        $this->load->library('CampaignsV2Logs');
        $this->load->model('model_stores');
        $this->createLog = new CampaignsV2Logs();
    }

    public function create(array $data)
    {
        $insert = $this->db->insert($this->tableName, $data);

        $id = $this->db->insert_id();

        $this->createLog->log($data, $id, $this->tableName, __FUNCTION__);

        return ($insert == true) ? $id : false;
    }

    public function update($data, $id)
    {
        if ($data && $id) {
            $this->db->where('id', $id);
            $update = $this->db->update($this->tableName, $data);

            $this->createLog->log($data, $id, $this->tableName, __FUNCTION__);

            return ($update == true) ? $id : false;
        }
    }

    public function getByPk(int $campaignId, int $pk): array
    {

        $this->db->select("*");

        $this->db->from($this->tableName);

        $this->db->where('campaign_v2_id', $campaignId);
        $this->db->where('id', $pk);

        return $this->db->get()->row_array();

    }

    public function changeProductStatus($data, $campaignId, $id)
    {
        if ($data && $id) {
            $this->db->where('id', $id);
            $this->db->where('campaign_v2_id', $campaignId);
            $update = $this->db->update($this->tableName, $data);

            $this->createLog->log($data, $id, $this->tableName, __FUNCTION__);

            return ($update == true) ? $id : false;
        }
    }

    public function getProductsBatch($campaignId, $offset, $limit)
    {
        return $this->db->query("
        SELECT 
            cv2p.product_id,
            cv2p.approved,
            cv2p.removed,
            cv2p.auto_removed,
            cv2p.percentual_from_commision,
            cv2p.commision_hierarchy,
            cv2p.percentual_commision,
            products.name as product_name,
            products.price,
            products.qty,
            stores.name as store_name,
            categories.name as category_name
        FROM campaign_v2_products cv2p
        JOIN products ON products.id = cv2p.product_id
        LEFT JOIN stores ON stores.id = products.store_id
        LEFT JOIN categories ON categories.id = products.category_id
        WHERE cv2p.campaign_v2_id = ?
        LIMIT ?, ?
    ", [$campaignId, $offset, $limit])->result_array();
    }

    public function getProductsByCampaign(
        int $campaignId,
        int $storeId = null,
        bool $onlyApproved = false,
        bool $onlyNotApproved = false,
        string $discount_type = '',
        string $discount_percentage = '',
        string $fixed_discount = ''
    ): ?array {

        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')) {
            $sql = "SELECT cv2p.product_id, cv2p.int_to, stores.name as store_name, stores.id as store_id, 
                    CASE 
                        WHEN products.has_variants != '' AND pv.sku IS NOT NULL THEN pv.sku 
                        ELSE products.sku 
                    END as sku, 
                    products.name as product_name,
                    CASE 
                        WHEN products.has_variants != '' AND pv.name IS NOT NULL THEN pv.name 
                        ELSE NULL 
                    END as variant_name,
                    CASE 
                        WHEN products.has_variants != '' AND pv.price IS NOT NULL THEN CAST(pv.price AS DECIMAL(12,2)) 
                        ELSE CAST(products.price AS DECIMAL(12,2)) 
                    END as price, 
                    CASE 
                        WHEN products.has_variants != '' AND pv.qty IS NOT NULL THEN pv.qty 
                        ELSE products.qty 
                    END as qty, 
                    REPLACE(REPLACE(REPLACE(products.category_id,'\"',''),']',''),'[','') as category_id, 
                    cv2p.discount_type, cv2p.discount_percentage, cv2p.fixed_discount,                      
                    cv2p.comission_rule, cv2p.new_comission, cv2p.rebate_value, cv2p.product_price, cv2p.product_promotional_price, cv2p.active,
                    cv2p.approved, cv2p.percentual_from_commision, cv2p.percentual_commision, cv2p.commision_hierarchy,
                    products.has_variants,
                    CASE 
                        WHEN products.has_variants != '' THEN pv.id 
                        ELSE NULL 
                    END as prd_variant_id,
                    CASE 
                        WHEN products.has_variants != '' THEN pv.variant 
                        ELSE NULL 
                    END as variant
                FROM campaign_v2_products as cv2p
                JOIN campaign_v2 ON ( cv2p.campaign_v2_id = campaign_v2.id ) 
                LEFT JOIN campaign_v2_stores ON ( campaign_v2_stores.campaign_v2_id = campaign_v2.id )
                JOIN products ON (products.id = cv2p.product_id)
                LEFT JOIN prd_variants pv ON (products.id = pv.prd_id AND products.has_variants != '' AND cv2p.prd_variant_id = pv.id)
                LEFT JOIN stores ON (stores.id = products.store_id)
                WHERE cv2p.campaign_v2_id = $campaignId";
        } else {
            $sql = "SELECT cv2p.product_id, cv2p.int_to, stores.name as store_name, stores.id as store_id, products.sku, products.name as product_name,
                    products.price, products.qty, REPLACE(REPLACE(REPLACE(products.category_id,'\"',''),']',''),'[','') as category_id, cv2p.discount_type, cv2p.discount_percentage, cv2p.fixed_discount,                      
                    cv2p.comission_rule, cv2p.new_comission, cv2p.rebate_value, cv2p.product_price, cv2p.product_promotional_price, cv2p.active,
                    cv2p.approved, cv2p.percentual_from_commision, cv2p.percentual_commision, cv2p.commision_hierarchy
                FROM campaign_v2_products as cv2p
                JOIN campaign_v2 ON ( cv2p.campaign_v2_id = campaign_v2.id ) 
                LEFT JOIN campaign_v2_stores ON ( campaign_v2_stores.campaign_v2_id = campaign_v2.id )
                JOIN products ON (products.id = cv2p.product_id)
                LEFT JOIN stores ON (stores.id = products.store_id)
                WHERE cv2p.campaign_v2_id = $campaignId";
        }

        if ($onlyApproved) {
            $sql .= " AND cv2p.approved = 1 AND cv2p.active = 1 ";
        }

        if ($onlyNotApproved) {
            $sql .= " AND cv2p.approved = 0 ";
        }

        if ($discount_type) {
            $sql .= " AND cv2p.discount_type = '$discount_type' ";
        }

        if ($discount_percentage) {
            $sql .= " AND cv2p.discount_percentage = '$discount_percentage' ";
        }

        if ($fixed_discount) {
            $sql .= " AND cv2p.fixed_discount = '$fixed_discount' ";
        }

        if ($storeId) {
            $sql .= " AND campaign_v2_stores.store_id = $storeId AND stores.id = $storeId ";
        }

        $sql .= " GROUP BY cv2p.id ";

        $query = $this->db->query($sql);

        return $query->result_array();

    }

    public function getAllProductsActiveCampaigns($date, array $campaignIds = []): array
    {
        $this->db->select('cp.id as campaign_product_id, cp.product_id, cp.int_to, cp.discount_type, 
        cp.fixed_discount, cp.discount_percentage, cp.marketplace_discount_percentual, cp.marketplace_discount_fixed, 
        cp.active, cp.approved, cp.removed, cp.product_price, cp.product_promotional_price, cp.removed_description,
         cp.percentual_from_commision, commision_hierarchy, percentual_commision, auto_removed, 
        c.id, c.campaign_type, c.start_date, c.end_date, c.vtex_campaign_update');
        $this->db->from($this->tableName . ' cp');
        $this->db->join('campaign_v2 c', 'cp.campaign_v2_id = c.id');
        if ($campaignIds){
            $this->db->where('c.id IN ('.implode(',', $campaignIds).')');
        }else{
            $this->db->where('c.start_date <=', $date);
            $this->db->where('c.end_date >=', $date);
        }
        $this->db->where('cp.product_promotional_price >', '0');

        $this->db->group_start()
            ->where('cp.removed', '0')
            ->or_group_start()
            ->where('cp.removed', '1')
            ->where('cp.auto_removed', '1')
            ->group_end()
            ->group_end();

        $query = $this->db->get();
        return $query->result_array();
    }

    public function findProductsInCampaign(array $postData, int $campaignId, $count = false, $userstore = null): array
    {

        $usersStores = $this->model_stores->getMyCompanyStoresArrayIds();

        $search = $postData['search']['value'] ?? null;
        $offset = $postData['start'] ?? 0;
        $limit = $postData['length'] ?? 10;
        $orderColumn = $postData['order'][0]['column'] ?? 'id';
        $orderColumnDir = $postData['order'][0]['dir'] ?? 'desc';

        $this->load->model('model_campaigns_v2');
        $product = $this->getProductsCampaignByCampaign($campaignId)[0] ?? [];
        $campaign = $this->model_campaigns_v2->getCampaignById($campaignId);

        switch ($orderColumn) {
            case 'gmv_last_30_days':
                $orderColumn = '';
                break;
            case 'marketplace_discount':
                $orderColumn = '';
                if (!empty($product) && in_array($campaign['campaign_type'], [CampaignTypeEnum::SHARED_DISCOUNT, CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT])) {
                    if ($product['discount_type'] == DiscountTypeEnum::PERCENTUAL) {
                        $orderColumn = "{$this->tableName}.marketplace_discount_percentual";
                    } else {
                        $orderColumn = "{$this->tableName}.marketplace_discount_fixed";
                    }
                }
                break;
            case 'seller_discount':
                $orderColumn = '';
                if (!empty($product) && in_array($campaign['campaign_type'], [CampaignTypeEnum::SHARED_DISCOUNT, CampaignTypeEnum::MERCHANT_DISCOUNT])) {
                    if ($product['discount_type'] == DiscountTypeEnum::PERCENTUAL) {
                        $orderColumn = "{$this->tableName}.seller_discount_percentual";
                    } else {
                        $orderColumn = "{$this->tableName}.seller_discount_fixed";
                    }
                }
                break;
            case 'discount':
                $orderColumn = "{$this->tableName}.fixed_discount";
                if (!empty($product) && $product['discount_type'] == DiscountTypeEnum::PERCENTUAL) {
                    $orderColumn = "{$this->tableName}.discount_percentage";
                }
                break;
            case 'discount_type_name':
                $orderColumn = '';
                if (!in_array($campaign['campaign_type'], [CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE, CampaignTypeEnum::MARKETPLACE_TRADING])) {
                    $orderColumn = "{$this->tableName}.discount_type";
                }
                break;
        }

        if ($count) {
            $this->db->select('count(*) as count');
        } else {
            if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')) {
                $this->db->select(
                    "stores.name store, products.id, 
                     CASE 
                        WHEN products.has_variants != '' AND prd_variants.sku IS NOT NULL THEN prd_variants.sku 
                        ELSE products.sku 
                     END as sku, 
                     products.name,
                     CASE 
                        WHEN products.has_variants != '' AND prd_variants.sku IS NOT NULL THEN prd_variants.qty 
                        ELSE products.qty 
                     END as qty,  
                     products.has_variants,
                     prd_variants.name as variant_name, {$this->tableName}.prd_variant_id,
                     {$this->tableName}.int_to, {$this->tableName}.created_at, {$this->tableName}.id campaign_product_key,
                     coalesce({$this->tableName}.product_price, products.price) product_price, 
                     {$this->tableName}.product_promotional_price action_value,{$this->tableName}.discount_type, 
                     {$this->tableName}.maximum_share_sale_price,
                     {$this->tableName}.fixed_discount, {$this->tableName}.discount_percentage,  
                     {$this->tableName}.comission_rule, {$this->tableName}.new_comission, {$this->tableName}.rebate_value, 
                     {$this->tableName}.active, {$this->tableName}.approved, {$this->tableName}.removed, {$this->tableName}.removed_description,
                     {$this->tableName}.discount_percentage, {$this->tableName}.fixed_discount, {$this->tableName}.comission_rule, 
                     {$this->tableName}.seller_discount_percentual, {$this->tableName}.seller_discount_fixed,
                     {$this->tableName}.marketplace_discount_percentual, {$this->tableName}.marketplace_discount_fixed
                     ");
            } else {
                $this->db->select(
                    "stores.name store, products.id, products.sku, products.name, products.qty, 
                     {$this->tableName}.int_to, {$this->tableName}.created_at, {$this->tableName}.id campaign_product_key,
                     coalesce({$this->tableName}.product_price, products.price) product_price, 
                     {$this->tableName}.product_promotional_price action_value,{$this->tableName}.discount_type, 
                     {$this->tableName}.maximum_share_sale_price,
                     {$this->tableName}.fixed_discount, {$this->tableName}.discount_percentage,  
                     {$this->tableName}.comission_rule, {$this->tableName}.new_comission, {$this->tableName}.rebate_value, 
                     {$this->tableName}.active, {$this->tableName}.approved, {$this->tableName}.removed, {$this->tableName}.removed_description,
                     {$this->tableName}.discount_percentage, {$this->tableName}.fixed_discount, {$this->tableName}.comission_rule, 
                     {$this->tableName}.seller_discount_percentual, {$this->tableName}.seller_discount_fixed,
                     {$this->tableName}.marketplace_discount_percentual, {$this->tableName}.marketplace_discount_fixed
                     ");
            }
        }

        $this->db->from($this->tableName)
            ->join('products', 'products.id = '.$this->tableName.'.product_id')
            ->join('campaign_v2', 'campaign_v2.id = '.$this->tableName.'.campaign_v2_id')
            ->join('stores', "stores.id = products.store_id  AND products.store_id = stores.id");

        // Add join to prd_variants table if feature flag is enabled
        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')) {
            $this->db->join('prd_variants', "prd_variants.id = {$this->tableName}.prd_variant_id", 'left');
        }

        $this->db->where('campaign_v2.id', $campaignId);
        if ($usersStores) {
            $this->db->where_in('stores.id', $usersStores);
        } elseif ($userstore) {
            $this->db->where('stores.id', $userstore);
        }
        if ($search) {
            $this->db->group_start();
            $this->db->like('products.name', $search);
            $this->db->or_like('products.description', $search);
            $this->db->or_where('products.id', $search);
            $this->db->or_where("{$this->tableName}.int_to", $search);
            $this->db->group_end();
        }

        if ($count) {
            return $this->db->get()->row_array();
        }

        $this->db->order_by($orderColumn, $orderColumnDir);

        $this->db->limit($limit, $offset);

        $q = $this->db->get();

        return $q->result_array();

    }


    public function isProductInCampaign(int $campaignId, int $productId, string $prd_variant_id = null): bool
    {

        $this->db->select('count(*) as count');
        $this->db->from($this->tableName);
        $this->db->where('product_id', $productId);
        $this->db->where('campaign_v2_id', $campaignId);
        $this->db->where('removed', 0);

        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku') && $prd_variant_id) {
            $this->db->where('prd_variant_id', $prd_variant_id);
        }

        $row = $this->db->get()->row_array();

        return $row['count'] > 0;

    }

    public function isProductParticipatingAnotherDiscountCampaign(
        $campaignV2Id,
        int $productId,
        array $participatingMarketplacesIntTo = [],
        array $participatingTradePolicies = [],
        array $payment_methods_array = [],
        int $vtex_campaign = null,
        int $occ_campaign = null,
        string $prd_variant_id = null
    ): bool {

        $sql = "SELECT count(*) total
            FROM campaign_v2_products
                JOIN campaign_v2 ON ( campaign_v2_products.campaign_v2_id = campaign_v2.id 
                                          AND campaign_v2.campaign_type IN ('".CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT."', '".CampaignTypeEnum::MERCHANT_DISCOUNT."', '".CampaignTypeEnum::SHARED_DISCOUNT."')) 
                JOIN products ON (products.id = campaign_v2_products.product_id) ";
        if ($campaignV2Id){
            $sql.= " JOIN (SELECT start_date, end_date FROM campaign_v2 WHERE id = $campaignV2Id) as ref_campaign ";
        }
        $sql.= " WHERE campaign_v2_products.active = 1
                AND campaign_v2.b2w_type = 0
                AND campaign_v2_products.removed = 0
                AND campaign_v2_products.product_id = ?";

        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku') && $prd_variant_id) {
            $sql.= " AND campaign_v2_products.prd_variant_id = '$prd_variant_id'";
        }

        $sql.= " AND campaign_v2.end_date > NOW()
                AND campaign_v2.active = 1
                AND campaign_v2_products.product_promotional_price > 0
                ";
        if ($campaignV2Id){
            $sql.= "AND campaign_v2.id != '$campaignV2Id'
                AND (
                        (campaign_v2.start_date <= ref_campaign.end_date AND campaign_v2.start_date >= ref_campaign.start_date) OR
                        (campaign_v2.end_date >= ref_campaign.start_date AND campaign_v2.end_date <= ref_campaign.end_date) OR
                        (campaign_v2.start_date <= ref_campaign.start_date AND campaign_v2.end_date >= ref_campaign.end_date)
                    )
                ";
        }


        if (!is_null($vtex_campaign)) {
            $sql .= " AND campaign_v2.vtex_campaign_update = $vtex_campaign";
        }
        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ') && !is_null($occ_campaign)) {
            $sql .= " AND campaign_v2.occ_campaign_update = $vtex_campaign";
        }

        if ($participatingMarketplacesIntTo) {
            $strintParticipatingMarketplacesIntTo = "'".implode("','", $participatingMarketplacesIntTo)."'";
            $sql .= " AND campaign_v2_products.int_to IN ($strintParticipatingMarketplacesIntTo)";
        }

        if ($participatingTradePolicies) {
            $string_participatingTradePolicies = implode(',', $participatingTradePolicies);
            $sql .= " AND (campaign_v2.id IN ( SELECT campaign_v2_id FROM campaign_v2_trade_policies 
                                            WHERE campaign_v2_trade_policies.campaign_v2_id = campaign_v2.id 
                                                AND trade_policy_id IN ( SELECT id FROM vtex_trade_policies WHERE trade_policy_id IN( $string_participatingTradePolicies) ))
                    OR (campaign_v2.id NOT IN (SELECT campaign_v2_id FROM campaign_v2_trade_policies 
                                                WHERE campaign_v2_trade_policies.campaign_v2_id = campaign_v2.id )
                    AND campaign_v2.id NOT IN (SELECT campaign_v2_id FROM campaign_v2_payment_methods 
                                                WHERE campaign_v2_payment_methods.campaign_v2_id = campaign_v2.id )))";
        }

        if ($payment_methods_array) {
            $payment_methods_array = implode(',', $payment_methods_array);
            $sql .= " AND (campaign_v2.id IN ( SELECT campaign_v2_id FROM campaign_v2_payment_methods 
                                            WHERE campaign_v2_payment_methods.campaign_v2_id = campaign_v2.id 
                                                AND method_id IN ( SELECT id FROM vtex_payment_methods WHERE method_id IN( $payment_methods_array) ))
                    OR (campaign_v2.id NOT IN (SELECT campaign_v2_id FROM campaign_v2_trade_policies 
                                                WHERE campaign_v2_trade_policies.campaign_v2_id = campaign_v2.id )
                    AND campaign_v2.id NOT IN (SELECT campaign_v2_id FROM campaign_v2_payment_methods 
                                                WHERE campaign_v2_payment_methods.campaign_v2_id = campaign_v2.id )))";
        }

        $query = $this->db->query($sql, array($productId));

        $row = $query->row_array();

        return $row['total'] > 0;
    }

    public function getProductParticipatingAnotherDiscountCampaign(
        $campaign_v2_id,
        $productId,
        $intTo = null,
        array $participatingTradePolicies = [],
        array $payment_methods_array = [],
        $productVariantId = null
    ): ?array {

        $sql = "SELECT campaign_v2_products.*
                FROM campaign_v2_products
                    JOIN campaign_v2 ON ( campaign_v2_products.campaign_v2_id = campaign_v2.id 
                                              AND campaign_v2.campaign_type IN ('".CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT."', '".CampaignTypeEnum::MERCHANT_DISCOUNT."', '".CampaignTypeEnum::SHARED_DISCOUNT."')) 
                    JOIN products ON (products.id = campaign_v2_products.product_id)
                    JOIN (SELECT start_date, end_date FROM campaign_v2 WHERE id = '$campaign_v2_id') as ref_campaign
                WHERE campaign_v2_products.active = 1
                    AND campaign_v2.b2w_type = 0
                    AND campaign_v2_products.product_id = ? ";

        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){
            if ($productVariantId){
                $sql .= " AND campaign_v2_products.prd_variant_id = '$productVariantId' ";
            }
        }

        if ($intTo) {
            $sql .= " AND campaign_v2_products.int_to = '$intTo' ";
        }

        $sql .= "   AND campaign_v2.end_date > NOW()
                    AND campaign_v2.active = 1
                    AND campaign_v2.id != '$campaign_v2_id'
                    AND campaign_v2_products.product_promotional_price > 0
                    AND (
                    (campaign_v2.start_date <= ref_campaign.end_date AND campaign_v2.start_date >= ref_campaign.start_date) OR
                    (campaign_v2.end_date >= ref_campaign.start_date AND campaign_v2.end_date <= ref_campaign.end_date) OR
                    (campaign_v2.start_date <= ref_campaign.start_date AND campaign_v2.end_date >= ref_campaign.end_date)
                )";

        if ($participatingTradePolicies) {
            $string_participatingTradePolicies = implode(',', $participatingTradePolicies);
            $sql .= " AND (campaign_v2.id IN ( SELECT campaign_v2_id FROM campaign_v2_trade_policies 
                                                WHERE campaign_v2_trade_policies.campaign_v2_id = campaign_v2.id 
                                                    AND trade_policy_id IN ( SELECT id FROM vtex_trade_policies WHERE trade_policy_id IN( $string_participatingTradePolicies ) ))
                        OR (campaign_v2.id NOT IN (SELECT campaign_v2_id FROM campaign_v2_payment_methods 
                                                    WHERE campaign_v2_payment_methods.campaign_v2_id = campaign_v2.id )
                        AND campaign_v2.id NOT IN (SELECT campaign_v2_id FROM campaign_v2_trade_policies 
                                                    WHERE campaign_v2_trade_policies.campaign_v2_id = campaign_v2.id )))";
        }

        if ($payment_methods_array) {

            $ids = [];
            foreach ($payment_methods_array as $method) {
                $ids[] = $method['method_id'];
            }

            $payment_methods_array_string = implode(',', $ids);
            $sql .= " AND (campaign_v2.id IN ( SELECT campaign_v2_id FROM campaign_v2_payment_methods 
                                                WHERE campaign_v2_payment_methods.campaign_v2_id = campaign_v2.id
                                                    AND method_id IN ( SELECT id FROM vtex_payment_methods WHERE method_id IN ( $payment_methods_array_string ) ))  
                        OR (campaign_v2.id NOT IN (SELECT campaign_v2_id FROM campaign_v2_payment_methods 
                                                    WHERE campaign_v2_payment_methods.campaign_v2_id = campaign_v2.id )
                        AND campaign_v2.id NOT IN (SELECT campaign_v2_id FROM campaign_v2_trade_policies 
                                                    WHERE campaign_v2_trade_policies.campaign_v2_id = campaign_v2.id )))";
        }

        $query = $this->db->query($sql, array($productId));

        return $query->result_array();

    }

    public function isProductParticipatingSameDiscountCampaign(
        string $campaign_v2_id,
        int $productId,
        string $intTo = null,
        $productVariantId = null
    ): ?array {

        $sql = "SELECT campaign_v2_products.*
                FROM campaign_v2_products
                WHERE campaign_v2_products.product_id = ?
                AND campaign_v2_products.removed = 0 ";

        if ($intTo) {
            $sql .= " AND campaign_v2_products.int_to = '$intTo' ";
        }

        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')){
            if ($productVariantId) {
                $sql .= " AND campaign_v2_products.prd_variant_id = '$productVariantId' ";
            }
        }

        $sql .= "   AND campaign_v2_products.campaign_v2_id = '$campaign_v2_id' ";

        $query = $this->db->query($sql, array($productId));

        return $query->result_array();

    }

    /**
     * Utilizado para identificar os produtos que estão em qualquer outra campanha
     */
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
            $sql = "SELECT campaign_v2_products.*, campaign_v2.vtex_campaign_update, campaign_v2.occ_campaign_update
                    FROM campaign_v2_products
                        JOIN campaign_v2 ON ( campaign_v2_products.campaign_v2_id = campaign_v2.id ) 
                        JOIN products ON (products.id = campaign_v2_products.product_id) ";
        }else{
            //@todo pode remover
            $sql = "SELECT campaign_v2_products.*, campaign_v2.vtex_campaign_update
                FROM campaign_v2_products
                    JOIN campaign_v2 ON ( campaign_v2_products.campaign_v2_id = campaign_v2.id ) 
                    JOIN products ON (products.id = campaign_v2_products.product_id) ";

        }

        if ($tradePolicyId) {
            $sql .= " LEFT JOIN campaign_v2_trade_policies ON (campaign_v2_trade_policies.campaign_v2_id = campaign_v2.id ) ";
        }
        if ($paymentMethodId) {
            $sql .= " LEFT JOIN campaign_v2_payment_methods ON (campaign_v2_payment_methods.campaign_v2_id = campaign_v2.id ) ";
        }
        $sql .= " WHERE campaign_v2_products.active = 1
                    AND campaign_v2.b2w_type = 0 ";
        if ($productId) {
            $sql .= " AND campaign_v2_products.product_id = $productId ";
        }
        if ($brandId && $storeId) {
            $sql .= " AND campaign_v2_products.product_id IN (SELECT id FROM products WHERE products.store_id = $storeId AND products.brand_id = '[\"$brandId\"]') ";
        } elseif ($categoryId && $storeId) {
            $sql .= " AND campaign_v2_products.product_id IN (SELECT id FROM products WHERE products.store_id = $storeId AND products.category_id = '[\"$categoryId\"]') ";
        } elseif ($storeId) {
            $sql .= " AND campaign_v2_products.product_id IN (SELECT id FROM products WHERE products.store_id = $storeId) ";
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

    public function isProductParticipatingAnotherB2wCampaign(int $productId, string $prd_variant_id = null): bool
    {

        $sql = "SELECT count(*) total
                FROM campaign_v2_products
                    JOIN campaign_v2 ON ( campaign_v2_products.campaign_v2_id = campaign_v2.id 
                                              AND campaign_v2.campaign_type IN ('".CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT."', '".CampaignTypeEnum::MERCHANT_DISCOUNT."', '".CampaignTypeEnum::SHARED_DISCOUNT."')) 
                    JOIN products ON (products.id = campaign_v2_products.product_id)
                WHERE campaign_v2_products.active = 1
                    AND campaign_v2_products.removed = 0
                    AND campaign_v2_products.product_id = ?";

        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku') && $prd_variant_id) {
            $sql.= " AND campaign_v2_products.prd_variant_id = '$prd_variant_id'";
        }

        $sql.= " AND campaign_v2.end_date > NOW()
                    AND campaign_v2.active = 1
                    AND campaign_v2_products.product_promotional_price > 0
                    AND campaign_v2_products.int_to = 'B2W'
                ";

        $query = $this->db->query($sql, array($productId));

        $row = $query->row_array();

        return $row['total'] > 0;

    }

    public function isProductParticipatingAnotherComissionReductionRebateCampaign(
        int $productId,
        array $participatingMarketplacesIntTo = []
    ): bool {

        $sql = "SELECT count(*) total
                FROM campaign_v2_products
                    JOIN campaign_v2 ON ( campaign_v2_products.campaign_v2_id = campaign_v2.id 
                                            AND campaign_v2.campaign_type IN ('".CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE."')) 
                    JOIN products ON (products.id = campaign_v2_products.product_id)
                WHERE campaign_v2_products.active = 1
                    AND campaign_v2_products.removed = 0
                    AND campaign_v2_products.product_id = ?
	                AND campaign_v2.end_date > NOW()
	                AND campaign_v2.active = 1
                    AND campaign_v2_products.product_promotional_price IS NULL
                    AND campaign_v2.campaign_type = '".CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE."'
                ";
        if ($participatingMarketplacesIntTo) {
            $strintParticipatingMarketplacesIntTo = "'".implode("','", $participatingMarketplacesIntTo)."'";
            $sql .= " AND campaign_v2_products.int_to IN ($strintParticipatingMarketplacesIntTo)";
        }

        $query = $this->db->query($sql, array($productId));

        $row = $query->row_array();

        return $row['total'] > 0;

    }

    public function isProductParticipatingAnotherMarketplaceTradingCampaign(int $productId): bool
    {

        $sql = "SELECT count(*) total
                FROM campaign_v2_products
                    JOIN campaign_v2 ON ( campaign_v2_products.campaign_v2_id = campaign_v2.id 
                                            AND campaign_v2.campaign_type IN ('".CampaignTypeEnum::MARKETPLACE_TRADING."')) 
                    JOIN products ON (products.id = campaign_v2_products.product_id)
                WHERE campaign_v2_products.active = 1
                    AND campaign_v2_products.removed = 0
                    AND campaign_v2_products.product_id = ?
	                AND campaign_v2.end_date > NOW()
                    AND campaign_v2.active = 1
                    AND campaign_v2_products.product_promotional_price IS NULL
                    AND campaign_v2.campaign_type = '".CampaignTypeEnum::MARKETPLACE_TRADING."'
                ";

        $query = $this->db->query($sql, array($productId));

        $row = $query->row_array();

        return $row['total'] > 0;

    }

    public function getProductInCampaign(int $campaignId, int $productId): ?array
    {

        $this->db->select('*');
        $this->db->from($this->tableName);
        $this->db->where('product_id', $productId);
        $this->db->where('campaign_v2_id', $campaignId);

        return $this->db->get()->row_array();

    }

    public function campaignHasProductsInAnalisys(int $campaignId): bool
    {

        $this->db->select('count(*) as count');
        $this->db->from($this->tableName);
        $this->db->where('campaign_v2_id', $campaignId);
        $this->db->where('approved', 0);
        $this->db->where('removed', 0);

        $row = $this->db->get()->row_array();

        return $row['count'] > 0;

    }

    public function approveAllProducts(int $campaignId, array $productIds = []): void
    {

        $data = [
            'approved' => 1,
        ];

        $this->db->where('campaign_v2_id', $campaignId);

        if ($productIds) {
            $this->db->where('product_id IN('.implode(',', $productIds).')');
        }

        $this->db->update($this->tableName, $data);

        $this->createLog->log($data, 'product_id IN('.implode(',', $productIds).')', $this->tableName, __FUNCTION__);

    }

    public function desactivateProduct(
        int $campaignId,
        int $productId,
        $withDiscount = true,
        string $int_to = null,
        string $variantId = null
    ): void {

        $data = [
            'approved' => 0,
            'active' => 0,
            'removed' => 1
        ];

        $this->db->where('product_id', $productId);
        $this->db->where('campaign_v2_id', $campaignId);
        if ($withDiscount) {
            $this->db->where('product_promotional_price >', 0);
        } else {
            $this->db->where('product_promotional_price IS NULL');
        }
        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku') && $variantId) {
            $this->db->where('prd_variant_id =', $variantId);
        }

        if ($int_to) {
            $this->db->where('int_to', $int_to);
        }

        $this->db->update($this->tableName, $data);

        $this->createLog->log($data, $productId, $this->tableName, __FUNCTION__);
    }

    public function desactivateProductAnyCampaign(int $campaignId, int $productId): void
    {

        $data = [
            'approved' => 0,
            'removed' => 1
        ];

        $this->db->where('product_id', $productId);
        $this->db->where('campaign_v2_id', $campaignId);

        $this->db->update($this->tableName, $data);

        $this->createLog->log($data, $productId, $this->tableName, __FUNCTION__);
    }

    public function desactivateProductsByCampaign(int $campaignId): void
    {

        $data = [
            'approved' => 0,
            'active' => 0,
            'removed' => 1
        ];

        $this->db->where('campaign_v2_id', $campaignId);

        $this->db->update($this->tableName, $data);

        $this->createLog->log($data, $campaignId, $this->tableName, __FUNCTION__);
    }

    public function anyStoreHasAnyProductInAnotherCampaignMarketplaceTrading(
        array $storesIds,
        array $marketplacesIntTos
    ): bool {

        $this->db->select('count(*) as count');

        $this->db->from($this->tableName);
        $this->db->join('campaign_v2', 'campaign_v2.id = '.$this->tableName.'.campaign_v2_id');
        $this->db->where("product_id IN (SELECT id FROM products WHERE store_id IN (".implode(',', $storesIds)."))");
        $intTos = [];
        foreach ($marketplacesIntTos as $marketplacesIntTo) {
            $intTos[] = $marketplacesIntTo['int_to'];
        }
        $intTos = "'".implode("','", $intTos)."'";
        $this->db->where("int_to IN ($intTos)");
        $this->db->where("campaign_type", CampaignTypeEnum::MARKETPLACE_TRADING);
        $this->db->where("removed", 0);
        $this->db->where("campaign_v2.end_date > NOW()");
        $this->db->where("campaign_v2.active", 1);
        return $this->db->get()->row_array()['count'] > 0;

    }

    public function getProductsCampaignByCampaign(int $campaign_v2_id): array
    {
        return $this->db->get_where($this->tableName, array('campaign_v2_id' => $campaign_v2_id))->result_array();
    }

    public function recalculateDiscount(int $campaignId, int $productId, array $newData): void
    {

        $productCampaign = $this->getProductInCampaign($campaignId, $productId);

        if (!$productCampaign){
            return;
        }

        if ($newData['discount_type'] == DiscountTypeEnum::FIXED_DISCOUNT) {
            $productCampaign['product_promotional_price'] = $productCampaign['product_price'] - $newData['fixed_discount'];
        }

        if ($productCampaign['discount_type'] == DiscountTypeEnum::PERCENTUAL) {
            $price = $productCampaign['product_price'];
            $percentualDiscount = $newData['discount_percentage'];
            $productCampaign['product_promotional_price'] = $price - ($price * $percentualDiscount / 100);
            $productCampaign['product_promotional_price'] = roundDecimalsDown($productCampaign['product_promotional_price']);

            $productCampaign['discount_percentage'] = $newData['discount_percentage'];
            $productCampaign['seller_discount_percentual'] = $newData['seller_discount_percentual'];
            $productCampaign['marketplace_discount_percentual'] = $newData['marketplace_discount_percentual'];
        }

        $this->update($productCampaign, $productCampaign['id']);

    }

}
