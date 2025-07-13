<?php
use App\Libraries\Enum\CampaignSegment;
use App\Libraries\Enum\CampaignTypeEnum;

class Model_campaigns_v2 extends CI_Model
{

    private $tableName = 'campaign_v2';
    private $createLog;

    public function __construct()
    {
        parent::__construct();

        $this->load->library('CampaignsV2Logs');
        $this->load->model('model_products');
        $this->load->model('model_campaigns_v2_products');
        $this->load->model('model_stores');
        $this->createLog = new CampaignsV2Logs();
    }

    /**
     * Atenção, esse método só vai retornar o valor que ficou na campanha, onde está usando campanhas que dão descontos
     * @param  int  $productId
     * @param  string  $int_to_non_utf8
     * @return string|null
     */
    public function getProductPriceInCampaigns(int $productId, string $int_to_non_utf8, $variant = null): ?string
    {
        //alterar no frete do calculo depois
        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')) {
            $productsInCampaign = $this->getProductsCampaignWithDiscount($productId, $int_to_non_utf8, '', null, null, $variant);
        }
        else
        {
            $productsInCampaign = $this->getProductsCampaignWithDiscount($productId, $int_to_non_utf8);
        }

        if (!$productsInCampaign) {
            return null;
        }

        $lastProductCampaign = end($productsInCampaign);
        $lastProductCampaignPrice = $lastProductCampaign['product_promotional_price'];

        //@todo campanha cumulativa ainda não é possível
//        if (count($productsInCampaign) == 2) {
//
//            $firstProductCampaign = $productsInCampaign[0];
//            $totalDiscountFirstProductCampaign = $firstProductCampaign['total_discount'];
//
//            return $lastProductCampaignPrice - $totalDiscountFirstProductCampaign;
//
//        }

        return $lastProductCampaignPrice;

    }


    public function getProductsCampaignWithDiscount(
        int $productId,
        string $int_to_non_utf8,
        string $marketplace_campaign_id = '',
        string $arrayCampaigns = null,
        string $date = null,
        $variant = null
    ): ?array {

        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')) {
            if (is_array($variant))
            {
                $variant = $variant['variant'];
            }
        }

        $int_to = utf8_encode($int_to_non_utf8);

        $sql = "SELECT campaign_v2_products.product_promotional_price, campaign_v2.id, campaign_v2.name, 
            (campaign_v2_products.product_price-campaign_v2_products.product_promotional_price) total_discount, campaign_v2.campaign_type
            ,campaign_v2_products.discount_type
            ,campaign_v2_products.discount_percentage
            ,campaign_v2_products.fixed_discount
            ,campaign_v2_products.seller_discount_percentual
            ,campaign_v2_products.seller_discount_fixed
            ,campaign_v2_products.marketplace_discount_percentual
            ,campaign_v2_products.marketplace_discount_fixed
            ,campaign_v2_products.product_price,
            campaign_v2.b2w_type
            FROM campaign_v2_products
                JOIN campaign_v2 ON ( campaign_v2_products.campaign_v2_id = campaign_v2.id ) 
                JOIN products ON (products.id = campaign_v2_products.product_id) ";

        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku') && !is_null($variant)) {
            $sql .= " JOIN prd_variants on prd_variants.prd_id = campaign_v2_products.product_id AND prd_variants.id = campaign_v2_products.prd_variant_id ";
        }

        if ($arrayCampaigns) {

            $sql .= " WHERE 
                        campaign_v2_products.product_id = ?
                        AND campaign_v2.id in ('$arrayCampaigns')
                    ";

            $query = $this->db->query($sql, array($productId));

        } else {

            $sql .= " WHERE campaign_v2_products.active = 1
                        AND campaign_v2_products.approved = 1
                        AND campaign_v2_products.removed = 0
                        AND campaign_v2_products.product_id = ?
                        AND campaign_v2.approved = 1
                        AND campaign_v2.active = 1
                        AND campaign_v2.b2w_type = 0
                        AND (campaign_v2_products.int_to = ? or campaign_v2_products.int_to = ?)
                        AND campaign_v2_products.product_promotional_price > 0 ";

            if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku'))
            {
                if (is_null($variant))
                {
                    $sql .= " AND campaign_v2_products.prd_variant_id IS NULL ";
                }
                else
                {
                    $sql .= " AND prd_variants.variant = ".$variant." ";
                }
            }

            if ($date){
                $sql .= " AND '$date' BETWEEN campaign_v2.start_date AND campaign_v2.end_date";
            }else{
                $sql .= "AND campaign_v2.start_date <= NOW()
                        AND campaign_v2.end_date > NOW()";
            }

            if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){

                if ($marketplace_campaign_id) {
                    $sql .= " AND campaign_v2.id IN (
                        SELECT campaign_v2_id FROM campaign_v2_vtex_campaigns WHERE vtex_campaign_id = '$marketplace_campaign_id'
                        UNION
                        SELECT campaign_v2_id FROM campaign_v2_occ_campaigns WHERE occ_campaign_id = '$marketplace_campaign_id'
                  ) ";
                } else {
                    $sql .= " AND campaign_v2.vtex_campaign_update = 0
                              AND (SELECT count(*) FROM campaign_v2_vtex_campaigns WHERE campaign_v2_id = campaign_v2.id) = 0
                              AND (SELECT count(*) FROM campaign_v2_occ_campaigns WHERE campaign_v2_id = campaign_v2.id) = 0 ";
                }

            }else{
                //@todo pode remover
                if ($marketplace_campaign_id) {
                    $sql .= " AND campaign_v2.id IN (SELECT campaign_v2_id FROM campaign_v2_vtex_campaigns WHERE vtex_campaign_id = '$marketplace_campaign_id') ";
                } else {
                    $sql .= " AND campaign_v2.vtex_campaign_update = 0
                        AND (SELECT count(*) FROM campaign_v2_vtex_campaigns WHERE campaign_v2_vtex_campaigns.campaign_v2_id = campaign_v2.id) = 0 ";
                }
            }

            $query = $this->db->query($sql, array($productId, $int_to, $int_to_non_utf8));
        }


        return $query->result_array();

    }

    public function getNewComission(int $productId, string $int_to_non_utf8)
    {
        $int_to = utf8_encode($int_to_non_utf8);

        $has_new_comission = $this->getProductsCampaignWithComissionReductionRebate($productId, $int_to_non_utf8);

        if (!empty($has_new_comission['new_comission'])) {
            return $has_new_comission;
        }

        return false;
    }

    /**
     * Retorna o preço do produto na campanha se ativa
     */
    // todo colocar um array com as campanhas participantes por conta do change seller
    // Alterar o where para caso venha o array utilizar o produto e as campanhas
    public function getProductsCampaignWithComissionReductionRebate(
        int $productId,
        string $int_to_non_utf8,
        string $arrayCampaigns = null,
        string $productVariantId = null
    ): ?array {

        $int_to = utf8_encode($int_to_non_utf8);

        $sql = "SELECT campaign_v2.id, campaign_v2.name, campaign_v2_products.comission_rule, campaign_v2_products.new_comission, 
                        campaign_v2_products.rebate_value, campaign_v2_products.maximum_share_sale_price, campaign_v2_products.product_id
                FROM campaign_v2_products
                    JOIN campaign_v2 ON ( campaign_v2_products.campaign_v2_id = campaign_v2.id 
                                              AND campaign_v2.campaign_type = '".CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE."' ) 
                    JOIN products ON (products.id = campaign_v2_products.product_id)";

        if ($arrayCampaigns) {

            $sql .= " WHERE 
                        campaign_v2_products.product_id = ?";

            if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku') && $productVariantId) {
                $sql .= " AND campaign_v2_products.prd_variant_id = '$productVariantId'";
            }

            $sql .= " AND campaign_v2.id in ('$arrayCampaigns')
                    ";

            $query = $this->db->query($sql, array($productId));

        } else {
            $sql .= " WHERE campaign_v2_products.active = 1 
                    AND campaign_v2_products.approved = 1
                    AND campaign_v2_products.removed = 0
                    AND campaign_v2_products.product_id = ?";

            if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku') && $productVariantId) {
                $sql .= " AND campaign_v2_products.prd_variant_id = '$productVariantId'";
            }

            $sql .= " AND campaign_v2.start_date <= NOW()
	                AND campaign_v2.end_date > NOW()
	                AND campaign_v2.active = 1
	                AND (campaign_v2_products.int_to = ? or campaign_v2_products.int_to = ?)
                    AND campaign_v2_products.product_promotional_price IS NULL
                    AND campaign_v2_products.comission_rule IS NOT NULL
                ";

            $query = $this->db->query($sql, array($productId, $int_to, $int_to_non_utf8));
        }
        return $query->row_array();

    }

    // todo colocar um array com as campanhas participantes por conta do change seller
    // Alterar o where para caso venha o array utilizar o produto e as campanhas

    public function getNewComissionByOrder($order_id)
    {
        $sql = "SELECT c.new_comission from campaign_v2_orders_campaigns oc LEFT JOIN campaign_v2 c ON oc.campaign_id = c.id WHERE oc.order_id = ? AND total_reduced IS NOT null";

        $query = $this->db->query($sql, array($order_id));

        return $query->row_array();
    }

    public function getProductsCampaignWithMarketplaceTrading(int $productId, string $int_to_non_utf8): ?array
    {

        $int_to = utf8_encode($int_to_non_utf8);

        $sql = "SELECT campaign_v2.id, campaign_v2.name, campaign_v2_products.comission_rule, campaign_v2_products.new_comission, 
                        campaign_v2_products.rebate_value, campaign_v2_products.product_id
                FROM campaign_v2_products
                    JOIN campaign_v2 ON ( campaign_v2_products.campaign_v2_id = campaign_v2.id 
                                              AND campaign_v2.campaign_type = '".CampaignTypeEnum::MARKETPLACE_TRADING."' ) 
                    JOIN products ON (products.id = campaign_v2_products.product_id)
                WHERE campaign_v2_products.active = 1 
                    AND campaign_v2_products.approved = 1
                    AND campaign_v2_products.removed = 0
                    AND campaign_v2_products.product_id = ?
                    AND campaign_v2.start_date <= NOW()
	                AND campaign_v2.end_date > NOW()
	                AND campaign_v2.active = 1
	                AND (campaign_v2_products.int_to = ? or campaign_v2_products.int_to = ?)
                    AND campaign_v2_products.product_promotional_price IS NULL
                    AND campaign_v2_products.comission_rule IS NOT NULL
                ";

        $query = $this->db->query($sql, array($productId, $int_to, $int_to_non_utf8));

        return $query->row_array();

    }

    public function getActiveCampaigns(
        array $postData,
        int $userstore = null,
        int $sellerIndex = null,
        int $comission = null
    ) {

        $search = $postData['search']['value'] ?? null;
        $startDate = $postData['startDate'] ?? null;
        $endDate = $postData['endDate'] ?? null;
        $offset = $postData['start'] ?? 0;
        $limit = $postData['length'] ?? 10;
        $orderColumn = $postData['order'][0]['column'] ?? 'id';
        $orderColumnDir = $postData['order'][0]['dir'] ?? 'desc';

        $sql = "SELECT campaign_v2.* ";
        $sql .= $this->generateQueryFromToFindActiveCampaigns($search, $userstore, $sellerIndex, $comission, $startDate,
            $endDate);
        $sql .= " ORDER BY campaign_v2.highlight = 1 DESC, campaign_v2.highlight = 0 DESC, campaign_v2.$orderColumn $orderColumnDir ";
        $sql .= " LIMIT $offset,$limit ";

        $query = $this->db->query($sql);

        return $query->result_array();

    }

    private function generateQueryFromToFindActiveCampaigns(
        string $search = null,
        int $userstore = null,
        int $sellerIndex = null,
        int $comission = null,
        string $startDate = null,
        string $endDate = null
    ): string {
        $sql = " FROM campaign_v2 ";
        $sql .= " WHERE campaign_v2.end_date > NOW() AND campaign_v2.active = 1 ";

        if ($userstore || $this->data['usercomp'] != 1) {

            $usersStores = $this->model_stores->getMyCompanyStoresArrayIds();
            $usersStoresString = implode(',', $usersStores);

            $sql .= " AND ((campaign_v2.segment = '".CampaignSegment::STORE."' 
            AND campaign_v2.id IN(SELECT campaign_v2_id FROM campaign_v2_stores 
            WHERE campaign_v2_stores.campaign_v2_id = campaign_v2.id AND campaign_v2_stores.store_id IN($usersStoresString) AND campaign_v2_stores.joined = 0 )) ";

            $sql .= " OR (campaign_v2.segment NOT IN ('".CampaignSegment::STORE."','".CampaignSegment::PRODUCT."') AND campaign_v2.id NOT IN(SELECT campaign_v2_id 
            FROM campaign_v2_stores 
            WHERE campaign_v2_stores.campaign_v2_id = campaign_v2.id AND campaign_v2_stores.store_id IN($usersStoresString) AND campaign_v2_stores.joined = 1 )) 
            AND campaign_v2.campaign_type NOT IN ('".CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE."','".CampaignTypeEnum::MARKETPLACE_TRADING."' )";

            $sql .= " OR (campaign_v2.segment = '".CampaignSegment::PRODUCT."' AND campaign_v2.id IN(SELECT campaign_v2_id 
            FROM campaign_v2_elegible_products
            inner join products on products.id =  campaign_v2_elegible_products.product_id
            WHERE  products.store_id IN($usersStoresString) )) 
            AND campaign_v2.id NOT IN ( SELECT campaign_v2_id FROM campaign_v2_stores WHERE campaign_v2_stores.campaign_v2_id = campaign_v2.id AND campaign_v2_stores.store_id IN ( $usersStoresString ) AND campaign_v2_stores.joined = 1 ) 
            ) 
            AND campaign_v2.campaign_type NOT IN ('".CampaignTypeEnum::COMMISSION_REDUCTION_AND_REBATE."','".CampaignTypeEnum::MARKETPLACE_TRADING."' )";

            $sql .= " AND (campaign_v2.seller_type = 0 ";

            //Se é um seller vendo, ele só pode ver + as campanhas criadas por ele e da company dele
            //Se é uma company vendo, ele pode ver + de todos seus sellers e da própria company
            $sql .= " OR (campaign_v2.seller_type = 1 AND campaign_v2.store_seller_campaign_owner IN ($usersStoresString)) ";
            $sql .= " OR (campaign_v2.seller_type = 2 AND campaign_v2.store_seller_campaign_owner = {$this->data['usercomp']}) ";

            $sql .= " ) ";

        }

        if ($sellerIndex) {
            $sql .= " AND campaign_v2.min_seller_index <= $sellerIndex ";
        }
        if ($comission) {
            $sql .= " AND ( ( campaign_v2.participating_comission_from <= $comission AND campaign_v2.participating_comission_to >= $comission ) 
                            OR (campaign_v2.participating_comission_from is null AND campaign_v2.participating_comission_to is null)
                            OR (campaign_v2.participating_comission_from = 0 AND campaign_v2.participating_comission_to = 0))";
        }

        if ($search) {

            $search = addslashes($search);

            $sql .= " AND ( ";
            $sql .= "  campaign_v2.name LIKE '%$search%' ";
            $sql .= " OR campaign_v2.description LIKE '%$search%' ";
            $sql .= " OR campaign_v2.id IN(SELECT DISTINCT(campaign_v2_id) FROM campaign_v2_products JOIN products ON (products.id = campaign_v2_products.product_id AND (products.id = '$search' OR products.sku like '%$search%' OR products.name like '%$search%' OR products.description like '%$search%')) ) ";
            $sql .= " ) ";

        }

        if ($startDate) {
            $sql .= " AND campaign_v2.start_date >= '$startDate 00:00:00' ";
        }
        if ($endDate) {
            $sql .= " AND campaign_v2.end_date <= '$endDate 23:59:59' ";
        }

        return $sql;

    }

    public function getRevenues(
        $postData = null,
        $userstore = null,
        $sellerIndex = null,
        $comission = null,
        $get_total = null
    ) {
        $offset = $postData['start'] ?? 0;
        $limit = $postData['length'] ?? 10;

        $select = "
					c.id,
					c.name,
					c.start_date,
					c.end_date,
					c.active,
					c.campaign_type,
					#c.discount_type,				
					case
						when 
							discount_type = 'fixed_discount'
						then 
							case 
								when 
									campaign_type = 'channel_funded_discount' 
								then
									#CONCAT('R$ ', fixed_discount)	
									Concat('R$ ', Replace  (Replace  (Replace  (Format(fixed_discount, 2), '.', '|'), ',', '.'), '|', ','))   
								when 
									campaign_type = 'shared_discount'	
								then
									#CONCAT('R$ ', marketplace_discount_fixed)	
									Concat('R$ ', Replace  (Replace  (Replace  (Format(marketplace_discount_fixed, 2), '.', '|'), ',', '.'), '|', ','))							
								else
									'R$ 0,00'
								END
						when 
							discount_type = 'discount_percentage'
						then
							case 
								when 
									campaign_type = 'channel_funded_discount' 
								then
									CONCAT(discount_percentage, '%')
								when 
									campaign_type = 'shared_discount'	
								then
									CONCAT(marketplace_discount_percentual, '%')
								else
									'0%'
								END	
						else
							''												
					END AS marketplace_share,

					case
						when 
							discount_type = 'fixed_discount'
						then 
							case 
								when 
									campaign_type = 'merchant_discount'	
								then
									#CONCAT('R$ ', fixed_discount)	
									Concat('R$ ', Replace  (Replace  (Replace  (Format(fixed_discount, 2), '.', '|'), ',', '.'), '|', ','))
								when 
									campaign_type = 'shared_discount'	
								then
									#CONCAT('R$ ', seller_discount_fixed)
									Concat('R$ ', Replace  (Replace  (Replace  (Format(seller_discount_fixed, 2), '.', '|'), ',', '.'), '|', ','))								
								else
									'R$ 0,00'
								END
						when 
							discount_type = 'discount_percentage'
						then
							case 
								when 
									campaign_type = 'merchant_discount'	
								then
									CONCAT(discount_percentage, '%')	
								when 
									campaign_type = 'shared_discount'	
								then
									CONCAT(seller_discount_percentual, '%')
								else
									'0%'
								END
						else
							''							
					END AS seller_share,

					#concat('R$ ', round(SUM(o.total_order), 2)) AS total_revenue
					#Concat('R$ ', Replace  (Replace  (Replace  (Format(round(SUM(o.total_order), 2), 2), '.', '|'), ',', '.'), '|', ',')) as total_revenue
					case
						when 
							SUM(o.total_order) > 0 
						then	
							Concat('R$ ', Replace  (Replace  (Replace  (Format(round(SUM(o.total_order), 2), 2), '.', '|'), ',', '.'), '|', ',')) 
						else
							'R$ 0,00'
						end
					as total_revenue
				";

        $order = " GROUP BY c.id 
					ORDER BY marketplace_share desc ";

        if ($get_total) {
            $select = "
					count(distinct c.id) as total
					";
            $order = "";
        }

        $where = ' where 1 = 1 ';

        if (isset($postData['seller_type'])) {
            $where .= " and c.seller_type ";
            $where .= ($postData['seller_type'] == 0) ? " = 0 " : " > 0 ";
        }

        if (isset($userstore) && !$this->data['only_admin']) {
            $where .= " and c.store_seller_campaign_owner = ".$userstore." ";
        }

        $limit = " LIMIT ".$offset.",".$limit." ";

        if ($get_total) {
            $limit = '';
        }
//		$where .= $this->generateQueryFromToFindActiveCampaigns($search, $userstore, $sellerIndex, $comission, $startDate, $endDate);


        // montando a query final ----------------------------------------------

        $sql = "
				SELECT 
				".$select."									
				FROM 
/*					orders o
					INNER JOIN campaign_v2_orders_campaigns coc ON coc.id = o.id
					INNER JOIN campaign_v2 c ON campaign_v2.id = coc.campaign_id
*/
					campaign_v2 c
					LEFT JOIN campaign_v2_orders_campaigns coc ON coc.campaign_id = c.id
					left JOIN orders o ON o.id = coc.order_id	
				".$where." 																
				".$order." 
				".$limit;

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getCardActiveCampaigns()
    {
        $sql = "SELECT
					count(id) as total 
				FROM 
				 	campaign_v2  
				WHERE 
					campaign_v2.start_date <= NOW()
				and
					campaign_v2.end_date >= NOW() 
				AND 
					campaign_v2.active = 1
				";

        $query = $this->db->query($sql);
        $result = $query->row_array();

        if (!empty($result)) {
            return $result['total'];
        }

        return null;
    }

    public function getCardEndThisMonth()
    {
        $sql = "
				SELECT
					COUNT(id) as total
				FROM 
					campaign_v2
				WHERE 
					MONTH(end_date) = MONTH(CURRENT_DATE())
				AND 
					YEAR(end_date) = YEAR(CURRENT_DATE())
				AND 
					start_date <= NOW()
				and
					end_date >= NOW() 
				and
					ACTIVE = 1
				";

        $query = $this->db->query($sql);
        $result = $query->row_array();

        if (!empty($result)) {
            return $result['total'];
        }

        return null;
    }

    public function getCardAdherence()
    {
        $sql = "
				SELECT ROUND(
					(100 - (
						100 * (
									select
										COUNT(cs.id)
									FROM
										campaign_v2_stores cs
										INNER JOIN campaign_v2 c ON cs.campaign_v2_id = c.id
									WHERE 
										c.start_date <= NOW()
									and
										c.end_date >= NOW() 
									AND 
										c.active = 1
									AND
										cs.joined = 0
								)
						/
								(	
									SELECT
										COUNT(id)
									from
										campaign_v2
									WHERE 
										start_date <= NOW()
									AND
										end_date >= NOW() 
									AND 
										active = 1
								)
					)
					), 2) AS total_adherence
				";

        $query = $this->db->query($sql);
        $result = $query->row_array();

        if (!empty($result)) {
            return $result['total_adherence'];
        }

        return null;
    }

    public function getCardProducts($userstore = null)
    {
        $where = '';

        if ($userstore) {
            $where = " and c.store_seller_campaign_owner in ( ".$userstore.") ";
        }

        $sql = "
				SELECT
					COUNT(distinct cp.product_id) AS total
				FROM
					campaign_v2_products cp
					LEFT JOIN campaign_v2 c ON cp.campaign_v2_id = c.id
				WHERE
					c.active = 1
				AND
					c.end_date >= NOW()	
				".$where."				
				";

        $query = $this->db->query($sql);
        $result = $query->row_array();

        if (!empty($result)) {
            return $result['total'];
        }

        return null;
    }

    public function getCardApproval()
    {
        $sql = "
				SELECT
					COUNT(cp.id) as total
				FROM
					campaign_v2_products cp
					INNER JOIN campaign_v2 c on c.id = cp.campaign_v2_id
				WHERE 
					c.start_date <= NOW()
				AND
					c.end_date >= NOW() 
				AND 
					c.active = 1
				AND
					cp.active = 1
				AND 
					cp.approved = 0
				AND
					cp.removed = 0

				ORDER BY start_date desc		
				";

        $query = $this->db->query($sql);
        $result = $query->row_array();

        if (!empty($result)) {
            return $result['total'];
        }

        return null;
    }

    public function getCardRevenue($userstore = null)
    {
        $where = '';

        if (isset($userstore) && !$this->data['only_admin']) {
            $where .= " where c.store_seller_campaign_owner in ( ".$userstore.") ";
        }

        /*		$sql = "
                        SELECT
                            round(SUM(o.total_order), 2) AS total_revenue
                        FROM
                            orders o
                            INNER JOIN campaign_v2_orders_campaigns coc ON coc.id = o.id
                            inner join campaign_v2 c on coc.campaign_id = c.id
                        ".$where."
                        ";	*/

        $sql = "
				SELECT 
					round(SUM(o.total_order), 2) AS total_revenue
				FROM 
					campaign_v2 c
					inner JOIN campaign_v2_orders_campaigns coc ON coc.campaign_id = c.id
					inner JOIN orders o ON o.id = coc.order_id
				".$where." 				
				";


        $query = $this->db->query($sql);
        $result = $query->row_array();

        if (!empty($result)) {
            return $result['total_revenue'];
        }

        return null;
    }

    public function graphTopSellers($limit = null)
    {
        $top_sellers = $this->getTopSellers($limit);

        if (is_array($top_sellers) && !empty($top_sellers)) {
            $this->db->truncate('campaign_v2_topsellers');

            foreach ($top_sellers as $seller) {
                $this->db->insert('campaign_v2_topsellers', $seller);
            }
        }
    }

    public function getTopSellers($limit = '')
    {
        if (is_numeric($limit) && $limit > 0) {
            $limit = ' limit '.intVal($limit);
        }

        $sql = "
				SELECT
					cs.store_id,
					SUM(co.total_products) AS amount_sales
				FROM
					campaign_v2 c
					INNER JOIN campaign_v2_stores cs ON cs.campaign_v2_id = c.id
					INNER join campaign_v2_orders_campaigns coc ON coc.campaign_id = c.id
					INNER JOIN campaign_v2_orders co ON co.order_id = coc.order_id	
				WHERE 
					cs.joined = 1	
				AND
					c.active = 1
				GROUP BY cs.store_id	
				ORDER BY amount_sales desc
				".$limit;

        $query = $this->db->query($sql);
        $result = $query->result_array();

        if (!empty($result)) {
            return $result;
        }

        return null;
    }

    public function graphTopProducts($limit = null)
    {
        $top_products = $this->getTopProducts($limit);

        if (is_array($top_products) && !empty($top_products)) {
            $this->db->truncate('campaign_v2_topproducts');

            foreach ($top_products as $product) {
                $this->db->insert('campaign_v2_topproducts', $product);
            }
        }
    }

    public function getTopProducts($limit = '')
    {
        if (is_numeric($limit) && $limit > 0) {
            $limit = ' limit '.intVal($limit);
        }

        $sql = "
				SELECT
					cp.product_id
					,COUNT(product_id) AS total_sales
					,SUM(ROUND(
							p.price - (
								case
									when cp.discount_type = cp.fixed_discount
										then cp.fixed_discount
									else
										case
											when cp.discount_type = cp.discount_percentage
												then p.price * (cp.discount_percentage / 100)
											else
												0	
										end
									end
							),2)
					) AS amount_sales
				FROM 
					campaign_v2_products cp
				#	INNER JOIN campaign_v2 c ON c.id = cp.campaign_v2_id
					INNER JOIN products p ON p.id = cp.product_id	
				WHERE
					cp.active = 1
				AND
					cp.approved = 1
				AND
					cp.removed= 0	
				GROUP BY cp.product_id
				ORDER BY total_sales desc
				".$limit;

        $query = $this->db->query($sql);
        $result = $query->result_array();

        if (!empty($result)) {
            return $result;
        }

        return null;
    }

    public function canShowButtonCampaignToAdmin($campaign_v2_id, $user_id)
    {

        $sql = "SELECT * FROM user_group ug JOIN `groups` g ON g.id = ug.group_id WHERE ug.user_id = ? AND g.only_admin = ?";
        $data = $this->db->query($sql, array($user_id, 1))->row();
        if (!$data) {
            return true; // se o usuario logado não for admin
        }

        $sql = "SELECT campaign_v2_logs.user_id FROM campaign_v2_logs 
        WHERE model LIKE ? AND model_id = ? AND campaign_v2_logs.user_id = ?";
        $data = $this->db->query($sql, array("campaign_v2", $campaign_v2_id, $user_id))->row();
        if ($data) {
            return true; // é um admin logado e a campanha é dele
        }

        return false;
    }

    public function getExpiredCampaigns(array $postData, int $userstore = null)
    {

        $search = $postData['search']['value'] ?? null;
        $startDate = $postData['startDate'] ?? null;
        $endDate = $postData['endDate'] ?? null;
        $offset = $postData['start'] ?? 0;
        $limit = $postData['length'] ?? 10;
        $orderColumn = $postData['order'][0]['column'] ?? 'id';
        $orderColumnDir = $postData['order'][0]['dir'] ?? 'desc';

        $sql = "SELECT campaign_v2.* ";
        $sql .= $this->generateQueryFromToFindExpiredCampaigns($search, $userstore, $startDate, $endDate);
        $sql .= " ORDER BY campaign_v2.$orderColumn $orderColumnDir ";
        $sql .= " LIMIT $offset,$limit ";

        $query = $this->db->query($sql);

        return $query->result_array();

    }

    private function generateQueryFromToFindExpiredCampaigns(
        string $search,
        int $userstore = null,
        string $startDate = null,
        string $endDate = null
    ): string {

        $sql = " FROM campaign_v2 ";
        $usersStoresString = '';
        if ($userstore || $this->data['usercomp'] != 1) {

            $usersStores = $this->model_stores->getMyCompanyStoresArrayIds();
            $usersStoresString = implode(',', $usersStores);

            $sql .= " JOIN campaign_v2_stores ON (campaign_v2_stores.campaign_v2_id = campaign_v2.id AND campaign_v2_stores.store_id IN($usersStoresString)) ";
        }
        $sql .= " WHERE (campaign_v2.end_date <= NOW() OR campaign_v2.active = 0 )";
        if ($usersStoresString) {
            $sql .= " AND campaign_v2_stores.store_id IN ($usersStoresString) 
                        AND campaign_v2_stores.joined = 1";

            $sql .= " AND campaign_v2.campaign_type NOT IN ('".CampaignTypeEnum::MARKETPLACE_TRADING."' )";

        }
        if ($search) {

            $search = addslashes($search);

            $sql .= " AND ( ";
            $sql .= "  campaign_v2.name LIKE '%$search%' ";
            $sql .= " OR campaign_v2.description LIKE '%$search%' ";
            $sql .= " OR campaign_v2.id IN(SELECT DISTINCT(campaign_v2_id) FROM campaign_v2_products JOIN products ON (products.id = campaign_v2_products.product_id AND (products.id = '$search' OR products.sku like '%$search%' OR products.name like '%$search%' OR products.description like '%$search%')) ) ";
            $sql .= " ) ";

        }

        if ($startDate) {
            $sql .= " AND campaign_v2.start_date >= '$startDate 00:00:00' ";
        }
        if ($endDate) {
            $sql .= " AND campaign_v2.end_date <= '$endDate 23:59:59' ";
        }

        return $sql;

    }

    public function getMyCampaigns(array $postData, string $usersStoresString)
    {

        $search = $postData['search']['value'] ?? null;
        $startDate = $postData['startDate'] ?? null;
        $endDate = $postData['endDate'] ?? null;
        $offset = $postData['start'] ?? 0;
        $limit = $postData['length'] ?? 10;
        $orderColumn = $postData['order'][0]['column'] ?? 'id';
        $orderColumnDir = $postData['order'][0]['dir'] ?? 'desc';

        $sql = "SELECT campaign_v2.* ";
        $sql .= $this->generateQueryFromToFindMyCampaigns($search, $usersStoresString, $startDate, $endDate);
        $sql .= " GROUP BY campaign_v2.id ";
        $sql .= " ORDER BY campaign_v2.$orderColumn $orderColumnDir ";
        $sql .= " LIMIT $offset,$limit ";

        $query = $this->db->query($sql);

        return $query->result_array();

    }

    private function generateQueryFromToFindMyCampaigns(
        string $search = null,
        string $usersStoresString,
        string $startDate = null,
        string $endDate = null
    ): string {

        $sql = " FROM campaign_v2 ";
        $sql .= " JOIN campaign_v2_stores ON (campaign_v2_stores.campaign_v2_id = campaign_v2.id AND campaign_v2_stores.store_id IN ($usersStoresString) ) ";
        $sql .= " WHERE campaign_v2.end_date > NOW() AND campaign_v2.active = 1 ";
        $sql .= " AND campaign_v2_stores.store_id IN ($usersStoresString) 
                    AND campaign_v2_stores.joined = 1";

        $sql .= " AND campaign_v2.campaign_type NOT IN ('".CampaignTypeEnum::MARKETPLACE_TRADING."' )";

        if ($search) {

            $search = addslashes($search);

            $sql .= " AND ( ";
            $sql .= "  campaign_v2.name LIKE '%$search%' ";
            $sql .= " OR campaign_v2.description LIKE '%$search%' ";
            $sql .= " OR campaign_v2.id IN(SELECT DISTINCT(campaign_v2_id) FROM campaign_v2_products JOIN products ON (products.id = campaign_v2_products.product_id AND (products.id = '$search' OR products.sku like '%$search%' OR products.name like '%$search%' OR products.description like '%$search%')) ) ";
            $sql .= " ) ";

        }

        if ($startDate) {
            $sql .= " AND campaign_v2.start_date >= '$startDate 00:00:00' ";
        }
        if ($endDate) {
            $sql .= " AND campaign_v2.end_date <= '$endDate 23:59:59' ";
        }

        return $sql;

    }

    public function countTotalActiveCampaigns(
        array $postData,
        int $userstore = null,
        int $sellerIndex = null,
        int $comission = null
    ) {

        $search = $postData['search']['value'] ?? null;
        $startDate = $postData['startDate'] ?? null;
        $endDate = $postData['endDate'] ?? null;

        $sql = "SELECT count(*) total ";
        $sql .= $this->generateQueryFromToFindActiveCampaigns($search, $userstore, $sellerIndex, $comission, $startDate,
            $endDate);

        $query = $this->db->query($sql);

        $result = $query->row_array();

        return $result['total'];

    }

    public function countTotalExpiredCampaigns(array $postData, int $userstore = null)
    {

        $search = $postData['search']['value'] ?? null;
        $startDate = $postData['startDate'] ?? null;
        $endDate = $postData['endDate'] ?? null;

        $sql = "SELECT count(*) total ";
        $sql .= $this->generateQueryFromToFindExpiredCampaigns($search, $userstore, $startDate, $endDate);

        $query = $this->db->query($sql);

        $result = $query->row_array();

        return $result['total'];

    }

    public function countTotalMyCampaigns(array $postData, string $usersStoresString = null)
    {

        $search = $postData['search']['value'] ?? null;
        $startDate = $postData['startDate'] ?? null;
        $endDate = $postData['endDate'] ?? null;

        $sql = "SELECT count(DISTINCT campaign_v2.id) total ";
        $sql .= $this->generateQueryFromToFindMyCampaigns($search, $usersStoresString, $startDate, $endDate);

        $query = $this->db->query($sql);

        $result = $query->row_array();

        return $result['total'];

    }

    public function companyIsInCampaign(int $campaignId): bool
    {

        $where = "";

        if ($this->data['usercomp'] != 1) {
            $usersStores = $this->model_stores->getMyCompanyStoresArrayIds();
            $usersStoresString = implode(',', $usersStores);
            $where = " campaign_v2_stores.store_id IN($usersStoresString) AND ";
        }

        $sql = "SELECT count(*) total
                FROM campaign_v2_stores
                WHERE $where campaign_v2_stores.campaign_v2_id = $campaignId AND campaign_v2_stores.joined = 1
                ";

        $query = $this->db->query($sql);

        $row = $query->row_array();

        return $row['total'] > 0;

    }

    public function storeIsInCampaign(int $campaignId, int $storeId): bool
    {

        $sql = "SELECT count(*) total
                FROM campaign_v2_stores
                WHERE campaign_v2_stores.store_id = $storeId AND campaign_v2_stores.campaign_v2_id = $campaignId AND campaign_v2_stores.joined = 1
                ";

        $query = $this->db->query($sql);

        $row = $query->row_array();

        return $row['total'] > 0;

    }

    public function exitCampaign(int $campaignId, int $storeId): void
    {

        $sql = "UPDATE campaign_v2_stores SET joined = 0 WHERE campaign_v2_id = $campaignId AND store_id = $storeId";
        $this->db->query($sql);

        $this->createLog->log(array('joined' => 0), $campaignId, 'campaign_v2_stores', __FUNCTION__);

        $sql = "UPDATE campaign_v2_products 
                JOIN products ON (products.id = campaign_v2_products.product_id)
                SET campaign_v2_products.active = 0
                WHERE products.store_id = $storeId AND campaign_v2_products.campaign_v2_id = $campaignId";
        $this->db->query($sql);

        $this->createLog->log(array('campaign_v2_products.active' => 0), $campaignId, 'campaign_v2_products',
            __FUNCTION__);

    }

    /**
     * Grava as totalizações para referencia futura em conciliacao e extrato
     */
    public function saveTotalDiscounts($campaign_v2_orders = null)
    {
        if (empty($campaign_v2_orders)) {
            return false;
        }

        $insert = $this->db->insert('campaign_v2_orders', $campaign_v2_orders);

        $insert_id = $this->db->insert_id();

        $this->createLog->log($campaign_v2_orders, $insert_id, 'campaign_v2_orders', __FUNCTION__);

        return ($insert == true) ? $insert_id : false;
    }

    public function getConciliationTotals($order_ids = null, $type = null, $by_order = null)
    {
        $arraySaida = array();
        $arraySaida['total'] = "0.0";

        if (empty($order_ids)) {
            return $arraySaida;
        }

        $promotions = '';

        if ($type == 'promotions') {
            $promotions = " and total_campaigns = 0";
        }

        if (!in_array($type, array(
            'comission_reduction', 'comission_reduction_products', 'discount_comission',
            'comission_reduction_marketplace'
        ))) {
            $type = 'total_'.$type;
        }

        $types = [
            'comission_reduction',
            'comission_reduction_products',
            'discount_comission',
            'comission_reduction_marketplace',
            'total_pricetags',
            'total_campaigns',
            'total_channel',
            'total_seller',
            'total_promotions',
            'total_rebate',
        ];

        if (!is_array($order_ids)) {
            $sql = "SELECT ".$type." as total from campaign_v2_orders where order_id = ".$order_ids." ".$promotions;
        } else {
            if (!is_numeric($order_ids[0])) {
                // $sql = "SELECT sum(".$type.") as total from campaign_v2_orders cv2o left join orders o on cv20.order_id = o.id and o.numero_marketplace = '".."' where order_id in (".implode(',', (array)$order_ids).")".$promotions;
                $sql = "SELECT sum(".$type.") as total from campaign_v2_orders where order_id in (
                select id from orders where numero_marketplace in ('".implode('\',\'', (array) $order_ids)."')
                ) ".$promotions;
            } else {
                $sql = "SELECT sum(".$type.") as total from campaign_v2_orders where order_id in (".implode(',',
                        (array) $order_ids).")".$promotions;
            }
        }


        // if($by_order == true)
        // $sql = "SELECT ".$type." as total from campaign_v2_orders where order_id = ".$order_ids." ".$promotions;

        $query = $this->db->query($sql);

        $result = $query->row_array();

        if (is_array($order_ids)) {
            return ($result) ? $result : $arraySaida;
        }

        return ($result) ? $result['total'] : $arraySaida['total'];
    }

    public function getConciliationTotals222($order_ids = null, $type = null, $by_order = null)
    {
        if (empty($order_ids) || empty($type)) {
            return false;
        }

        $promotions = '';

        if ($type == 'promotions') {
            $promotions = " and total_campaigns = 0";
        }

        if (!in_array($type, array('comission_reduction', 'discount_comission', 'comission_reduction_marketplace'))) {
            $type = 'total_'.$type;
        }

        $sql = "SELECT sum(".$type.") as total from campaign_v2_orders where order_id in (".implode(',',
                (array) $order_ids).")".$promotions;

        if ($by_order == true) {
            $sql = "SELECT ".$type." as total from campaign_v2_orders where order_id = ".$order_ids." ".$promotions;
        }

        $query = $this->db->query($sql);

        $result = $query->row_array();

        return ($result) ? $result['total'] : 0;
    }

    /**
     * recupera os dados de campanhas de um determinado order_id
     */
    public function getCampaignsTotalsByOrderId($order_id = null)
    {
        if (empty($order_id)) {
            return false;
        }

        $sql = "SELECT * from campaign_v2_orders where order_id = ? order by id desc";

        $query = $this->db->query($sql, array($order_id));
        $result = $query->row_array();

        return ($result) ? $result : false;
    }

    /**
     * recupera os dados de campanhas de um determinado id de referencia do pedido
     */
    public function getCampaignsTotalsByRefId($ref_id = null)
    {
        if (empty($ref_id)) {
            return false;
        }

        // $sql = "SELECT * from campaign_v2_orders v2or left join orders or on v2or.order_id = or.id where or.numero_marketplace = ?";
        $sql = "SELECT * from campaign_v2_orders where order_id = (select id from orders where numero_marketplace = ? order by id desc LIMIT 1)";

        $query = $this->db->query($sql, array($ref_id));
        $result = $query->row_array();

        return ($result) ? $result : false;
    }


    //braun

    /**
     * Grava os detalhes de cada campanha em cada pedido
     */
    public function saveTotalsFromCampaigns($order_campaign)
    {
        if (empty($order_campaign)) {
            return false;
        }

        $insert = $this->db->insert('campaign_v2_orders_campaigns', $order_campaign);

        $id = $this->db->insert_id();

        $this->createLog->log($order_campaign, $id, 'campaign_v2_orders_campaigns', __FUNCTION__);

        return ($insert == true) ? $id : false;
    }

    public function isStoreAllowedJoinCampaign(int $campaignId, int $storeId): bool
    {

        $campaign = $this->getCampaignById($campaignId);
        if (!$campaign) {
            return false;
        }

        //Primeiramente vamos validar se está dentro da data limite
        if (!$this->campaignIsAbleToJoinByDateLimit($campaign)) {
            return false;
        }

        /**
         * Se o segmento for por loja, vamos primeiro verificar se a loja pode participar
         * Outros segmentos não interessam, então vamos agora validar somente o comissão participante, seller index etc.
         */
        if ($campaign['segment'] == CampaignSegment::STORE && !$this->isStoreAvailableJoin($campaignId, $storeId)) {
            return false;
        }

        return true;

    }

    /**
     * @param  int  $campaignId
     * @return array|null
     */
    public function getCampaignById(int $campaignId): ?array
    {

        $sql = "SELECT * FROM campaign_v2 WHERE id = $campaignId ";

        $query = $this->db->query($sql);

        $row = $query->row_array();

        if ($row) {
            return $row;
        }

        return null;

    }

    public function campaignIsAbleToJoinByDateLimit(array $campaign): bool
    {

        //Se a data final já passou, não pode mais
        if ($campaign['end_date'] <= dateNow()->format(DATETIME_INTERNATIONAL)) {
            return false;
        }

        //Se não está usando data limite, ou ainda não passou da data limite, vamos permitir
        return is_null($campaign['deadline_for_joining']) || $campaign['deadline_for_joining'] > dateNow()->format(DATETIME_INTERNATIONAL);

    }

    /**
     * @param  int  $campaignId
     * @param  int  $storeId
     * @return bool
     * Aqui vai apenas validar se a loja está cadastrada como elegível
     */
    public function isStoreAvailableJoin(int $campaignId, int $storeId): bool
    {

        $this->db->select("campaign_v2_stores.*, campaign_v2.deadline_for_joining, campaign_v2.end_date");
        $this->db->from('campaign_v2_stores');
        $this->db->join('campaign_v2', 'campaign_v2.id = '.'campaign_v2_stores'.'.campaign_v2_id');
        $this->db->where('campaign_v2_id', $campaignId);
        $this->db->where('store_id', $storeId);
        $this->db->where('joined', 0);

        $result = $this->db->get();
        if (!$result) {
            return false;
        }

        return true;

    }

    public function migrate_discounts_temp($sellercenter)
    {

        $alteracoes = [];

        $this->load->model('model_orders');

        //Buscando primeiramente todos os pedidos que estão em campanhas
        $sql = "SELECT * FROM orders WHERE id IN (SELECT order_id FROM campaign_v2_orders)";

        $query = $this->db->query($sql);

        $orders = $query->result_array();
        $queriesBackup = [];

        if ($orders) {

            foreach ($orders as $order) {

                $alteracao = [];
                $alteracao['order_id'] = $order['id'];

                $sqlItem = "SELECT * FROM orders_item WHERE order_id = {$order['id']}";

                $queryItem = $this->db->query($sqlItem);

                $ordersItems = $queryItem->result_array();

                $queriesBackup[] = "UPDATE orders SET discount = '{$order['discount']}',total_order = '{$order['total_order']}', net_amount = '{$order['net_amount']}', gross_amount = '{$order['gross_amount']}' WHERE id = {$order['id']}";

                foreach ($ordersItems as $orderItem) {

                    $campaigns = $this->getPriceOrderCampaignByOrderProductIntto($order['id'], $orderItem['product_id'],
                        $order['origin']);

                    if ($campaigns) {

                        $queriesBackup[] = "UPDATE orders_item SET discount = '{$orderItem['discount']}', rate = '{$orderItem['rate']}', amount = '{$orderItem['amount']}' WHERE id = {$orderItem['id']}";

                        $alteracao['item'] = [
                            'discount_de' => $orderItem['discount'], 'rate_de' => $orderItem['rate'],
                            'amount_de' => $orderItem['amount']
                        ];
                        $alteracao['order'] = [
                            'discount_de' => $order['discount'], 'total_order_de' => $order['total_order'],
                            'net_amount_de' => $order['net_amount'], 'gross_amount_de' => $order['gross_amount']
                        ];

                        foreach ($campaigns as $campaign) {

                            $orderItem['discount'] += $campaign['discount'];
                            $order['discount'] += $campaign['discount'];

                            if ($sellercenter == 'somaplace') {
                                $order['total_order'] += $campaign['discount'];
                                $order['net_amount'] -= $campaign['discount'];
                                $order['gross_amount'] += $campaign['discount'];
                            }

                            $alteracao['campaign'] = $campaign['id'];

                        }

                        $alteracao['item']['discount_por'] = $orderItem['discount'];
                        $alteracao['item']['rate_por'] = $orderItem['rate'];
                        $alteracao['item']['amount_por'] = $orderItem['amount'];
                        $alteracao['order']['discount_por'] = $order['discount'];
                        $alteracao['order']['total_order_por'] = $order['total_order'];
                        $alteracao['order']['net_amount_por'] = $order['net_amount'];
                        $alteracao['order']['gross_amount_por'] = $order['gross_amount'];

                        $alteracoes[] = $alteracao;

                        $this->model_orders->updateOrderById($order['id'], $order);
                        $this->model_orders->updateItenByOrderAndId($orderItem['id'], $orderItem);

                    }

                }

            }

            echo "<style>
table, th, td {
  border: 1px solid black;
  border-collapse: collapse;
}
</style><table border='1' >
                    <tr>
                        <th>order_id</th>
                        <th>campaign_id</th>
                        <th>item discount</th>
                        <th>item rate</th>
                        <th>item amount</th>
                        <th>order discount</th>
                        <th>order total_order</th>
                        <th>order net_amount</th>
                        <th>order gross_amount</th>
                    </tr>";

            foreach ($alteracoes as $alteracao) {

                $item = $alteracao['item'];
                $order = $alteracao['order'];
                echo "<tr>";
                echo "<td><a href='".base_url('orders/update')."/{$alteracao['order_id']}'>{$alteracao['order_id']}</a></td>";
                echo "<td><a href='".base_url('campaigns_v2/products')."/{$alteracao['campaign']}'>{$alteracao['campaign']}</a></td>";

                echo "<td>De {$item['discount_de']} para {$item['discount_por']} </td>";
                echo "<td>De {$item['rate_de']} para {$item['rate_por']} </td>";
                echo "<td>De {$item['amount_de']} para {$item['amount_por']} </td>";

                echo "<td>De {$order['discount_de']} para {$order['discount_por']} </td>";
                echo "<td>De {$order['total_order_de']} para {$order['total_order_por']} </td>";
                echo "<td>De {$order['net_amount_de']} para {$order['net_amount_por']} </td>";
                echo "<td>De {$order['gross_amount_de']} para {$order['gross_amount_por']} </td>";
                echo "</tr>";

            }

            echo "</table>";

            foreach ($queriesBackup as $queryBackup) {
                echo $queryBackup.';<br />';
            }

        }

    }

    /**
     * Recupera o preço de antes e depois da campanha, de um produto para uma venda
     *
     * @param  int  $order  Código do pedido (orders.id)
     * @param  int  $product  Código do produto (orders_item.product_id)
     * @param  string  $int_to  Marketplace do pedido (orders.origin)
     * @return  mixed
     */
    public function getPriceOrderCampaignByOrderProductIntto(int $order, int $product, string $int_to)
    {
        return $this->db->select('campaign_v2_products.campaign_v2_id as id, campaign_v2_products.product_price, campaign_v2_products.product_promotional_price, (campaign_v2_products.product_price-campaign_v2_products.product_promotional_price) discount')
            ->from('campaign_v2_orders_campaigns')
            ->join('campaign_v2_products',
                'campaign_v2_orders_campaigns.campaign_id = campaign_v2_products.campaign_v2_id')
            ->join('campaign_v2_orders', 'campaign_v2_orders.order_id = campaign_v2_orders_campaigns.order_id')
            ->where('campaign_v2_orders_campaigns.order_id', $order)
            ->where('campaign_v2_products.product_id', $product)
            ->where('campaign_v2_products.int_to', $int_to)
            ->where('campaign_v2_orders_campaigns.total_discount IS NOT NULL', null, false)
            ->get()
            ->result_array();

        /*
        SELECT campaign_v2_products.product_price, campaign_v2_products.product_promotional_price
        FROM `campaign_v2_orders_campaigns`
        join `campaign_v2_products` on campaign_v2_orders_campaigns.campaign_id = campaign_v2_products.campaign_v2_id
        join campaign_v2_orders on campaign_v2_orders.order_id = campaign_v2_orders_campaigns.order_id
        where campaign_v2_orders_campaigns.order_id=630
        and campaign_v2_products.product_id=3887
        and campaign_v2_orders_campaigns.total_discount is not null
        and campaign_v2_products.int_to = 'Decathlon';
         */
    }

    public function getCampaignV2OrderByOrderId(int $orderId)
    {
        return $this->db->select('*')
            ->from('campaign_v2_orders')
            ->where('order_id', $orderId)
            ->get()
            ->row_array();

    }

    public function getCampaignsProductsByOrder(int $orderId)
    {

        $sql = "
            SELECT o.product_id, c.id FROM orders_item o
            JOIN campaign_v2_products cp ON cp.product_id = o.product_id  
            JOIN campaign_v2 c ON c.id = cp.campaign_v2_id
            WHERE order_id = ? AND c.active = ?
        ";

        $items = $this->db->query($sql, array($orderId, 1))->result();
        return $items;

    }

    /**
     * De acordo com as campanhas agendadas, caso o preço estiver diferente, vamos atualizar
     * @return void
     */
    public function updateProductsPriceInScheduledCampaigns(): void
    {

        $products = $this->db->select('campaign_v2.id, products_marketplace.prd_id, products_marketplace.price AS price_marketplace, campaign_v2_products.product_promotional_price, campaign_v2_products.product_price, campaign_v2_products.int_to')
            ->from('campaign_v2_products')
            ->join('products_marketplace',
                'products_marketplace.prd_id = campaign_v2_products.product_id AND products_marketplace.int_to = campaign_v2_products.int_to')
            ->join('campaign_v2', 'campaign_v2_products.campaign_v2_id = campaign_v2.id')
            ->where('campaign_v2.b2w_type', 0)
            ->where('campaign_v2.active', 1)
            ->where('campaign_v2_products.active', 1)
            ->where('campaign_v2_products.approved', 1)
            ->where('campaign_v2_products.removed', 0)
            ->where('campaign_v2_products.product_promotional_price > 0', null, false)
            ->where('campaign_v2.start_date <= NOW()', null, false)
            ->where('products_marketplace.price <> campaign_v2_products.product_promotional_price', null, false)
            ->where('campaign_v2.schedule_sent_status', 'not_sent')
            ->group_by('campaign_v2_products.product_id')
            ->get()
            ->result_array();

        if ($products) {

            $campaigns = array();

            foreach ($products as $product) {

                if (!isset($product['campaign_v2_id'])) {
                    continue;
                }

                if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){

                    $this->model_products->setDateUpdatedProduct($product['prd_id'], null, __METHOD__,
                        array(
                            'int_to' => $product['int_to'],
                            'active' => $this->model_campaigns_v2_products->isProductParticipatingAnotherDiscountCampaign(
                                $product['campaign_v2_id'],
                                $product['prd_id'],
                                array($product['int_to']),
                                [],
                                [],
                                0,
                                0
                            ),
                            'price' => $product['product_promotional_price'],
                            'list_price' => $product['product_price']
                        )
                    );

                }else{

                    $this->model_products->setDateUpdatedProduct($product['prd_id'], null, __METHOD__,
                        array(
                            'int_to' => $product['int_to'],
                            'active' => $this->model_campaigns_v2_products->isProductParticipatingAnotherDiscountCampaign(
                                $product['campaign_v2_id'],
                                $product['prd_id'],
                                array($product['int_to']),
                                [],
                                [],
                                0
                            ),
                            'price' => $product['product_promotional_price'],
                            'list_price' => $product['product_price']
                        )
                    );

                }
                $campaigns[$product['id']] = $product['id'];
            }

            foreach ($campaigns as $campaign) {
                $ret = $this->setCampaignsStatus($campaign, 'sent');
                if ($ret) {
                    echo 'Atualizado o status da campanha com sucesso';
                } else {
                    echo 'Erro ao atualizar o status da campanha'.$ret;
                }
            }

        }

    }

    public function setCampaignsStatus($campaign_id, $status)
    {

        $data = array(
            'schedule_sent_status' => $status
        );

        $this->db->where('id', $campaign_id);
        return $this->db->update($this->tableName, $data);

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

    /**
     * De acordo com as campanhas expiradas, vamos atualizar o preço do produto para o original assim que expirado
     * @return void
     */
    public function deactivateProductsCampaignExpired(): void
    {

        $products = $this->db->select('campaign_v2_products.product_id, campaign_v2.id AS campaign_id, campaign_v2_products.product_promotional_price, campaign_v2_products.product_price, campaign_v2_products.int_to')
            ->from('campaign_v2_products')
            ->join('campaign_v2', 'campaign_v2.id = campaign_v2_products.campaign_v2_id')
            ->where('campaign_v2.active', 1)
            ->where('campaign_v2.b2w_type', 0)
            ->where('campaign_v2.end_date <= NOW()', null, false)
            ->where('campaign_v2_products.product_promotional_price > 0', null, false)
            ->get()
            ->result_array();

        if ($products) {

            //Criando array para não fazer entrar mais de uma vez o mesmo produto
            $idsProducts = [];
            $campaignsIds = [];

            //Primeiro atualizando todos os produtos que vieram
            foreach ($products as $product) {
                $campaignsIds[$product['campaign_id']] = $product['campaign_id'];
                if (!in_array($product['product_id'], $idsProducts)) {
                    $idsProducts[] = $product['product_id'];
                    if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){
                        $this->model_products->setDateUpdatedProduct($product['product_id'], null, __METHOD__,
                            array(
                                'int_to' => $product['int_to'],
                                'active' => $this->model_campaigns_v2_products->isProductParticipatingAnotherDiscountCampaign(
                                    $product['campaign_v2_id'],
                                    $product['product_id'],
                                    array($product['int_to']),
                                    [],
                                    [],
                                    0,
                                    0
                                ),
                                'price' => $product['product_promotional_price'],
                                'list_price' => $product['product_price']
                            )
                        );
                    }else{

                        $this->model_products->setDateUpdatedProduct($product['product_id'], null, __METHOD__,
                            array(
                                'int_to' => $product['int_to'],
                                'active' => $this->model_campaigns_v2_products->isProductParticipatingAnotherDiscountCampaign(
                                    $product['campaign_v2_id'],
                                    $product['product_id'],
                                    array($product['int_to']),
                                    [],
                                    [],
                                    0
                                ),
                                'price' => $product['product_promotional_price'],
                                'list_price' => $product['product_price']
                            )
                        );

                    }
                }
            }

            //Atualizando agora todas as campanhas para inativa
            $this->db->query("UPDATE campaign_v2 set active = 0 WHERE id IN (".implode(',', $campaignsIds).")");

        }

    }

    public function addAllProductsSegmentByStoreInCampaignTypeMarketplaceTrading(): void
    {

        $products = $this->findAllProductsInCampaignTypeMarketplaceTradingNotInsertedYet();
        if ($products) {

            $this->db->trans_begin();

            foreach ($products as $product) {

                //Sempre verificando se já foi cadastrado em outra campanha de negociação marketplace também
                $this->removeProductsCampaignAnotherCampaignMarketplaceTrading($product['product_id'],
                    $product['campaign_v2_id']);

                $this->model_campaigns_v2_products->create($product);

            }

            $this->db->trans_commit();

        }

    }

    public function findAllProductsInCampaignTypeMarketplaceTradingNotInsertedYet(): ?array
    {

        $products = $this->db->query("SELECT
                                campaign_v2.id AS campaign_v2_id,
                                products.id AS product_id,
                                campaign_v2_marketplaces.int_to AS int_to,
                                campaign_v2.comission_rule,
                                campaign_v2.new_comission,
                                campaign_v2.rebate_value,
                                '1' AS active,
                                '1' AS approved ,
                                99999999 AS maximum_share_sale_price
                            FROM
                                products
                                JOIN stores ON ( stores.id = products.store_id )
                                JOIN campaign_v2_stores ON ( campaign_v2_stores.store_id = stores.id )
                                JOIN campaign_v2 ON ( campaign_v2_stores.campaign_v2_id = campaign_v2.id )
                                JOIN campaign_v2_marketplaces ON ( campaign_v2_marketplaces.campaign_v2_id = campaign_v2.id )
                                LEFT JOIN campaign_v2_products ON ( campaign_v2_products.product_id = products.id AND campaign_v2_products.campaign_v2_id = campaign_v2.id ) 
                            WHERE
                                campaign_v2_products.product_id IS NULL 
                                AND campaign_v2.campaign_type IN ( 'marketplace_trading' ) 
                                AND campaign_v2.segment = 'store' 
                                AND campaign_v2.end_date > now( ) 
                                AND (
                                    ( campaign_v2.participating_comission_from <= stores.service_charge_value AND campaign_v2.participating_comission_to >= stores.service_charge_value ) 
                                    OR ( campaign_v2.participating_comission_from IS NULL AND campaign_v2.participating_comission_to IS NULL ) 
                                    OR ( campaign_v2.participating_comission_from = 0 AND campaign_v2.participating_comission_to = 0 ) 
                                ) 
                                AND campaign_v2.min_seller_index <= ( SELECT seller_index FROM seller_index_history WHERE seller_index_history.store_id = stores.id ORDER BY id DESC LIMIT 1 ) 
                                AND campaign_v2.active = 1 
                            ORDER BY
                                products.id");

        return $products->result_array();

    }

    public function removeProductsCampaignAnotherCampaignMarketplaceTrading(int $newProductId, int $newCampaignId): void
    {

        $this->db->query("UPDATE campaign_v2_products
			                SET removed = 1
		                    WHERE
			                campaign_v2_id IN ( SELECT id FROM campaign_v2 WHERE id <> $newCampaignId AND `active` = 1 AND `end_date` > NOW( ) AND campaign_type = 'marketplace_trading' ) 
			                AND product_id = $newProductId");

    }

    public function create($data)
    {
        if ($data) {
            $insert = $this->db->insert($this->tableName, $data);

            $idCampanha = $this->db->insert_id();

            $this->createLog->log($data, $idCampanha, $this->tableName, __FUNCTION__);

            return ($insert == true) ? $idCampanha : false;
        }
    }

    public function getPendingVtexCampaignsToSincronizeWithVtex()
    {

        $sql = "SELECT campaign_v2.id, 
                    campaign_v2.name, 
                    campaign_v2.description, 
                    campaign_v2.start_date, 
                    campaign_v2.end_date, 
                    campaign_v2.updated_at, 
                    campaign_v2_products.discount_type, 
                    campaign_v2_products.discount_percentage, 
                    campaign_v2_products.fixed_discount
                FROM campaign_v2 
                JOIN campaign_v2_products ON (campaign_v2_products.campaign_v2_id = campaign_v2.id)
                WHERE vtex_campaign_update = 1 
                  AND campaign_v2.approved = 1 
                  AND campaign_v2.campaign_type IN ('".CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT."', '".CampaignTypeEnum::MERCHANT_DISCOUNT."', '".CampaignTypeEnum::SHARED_DISCOUNT."')";
        $sql .= " GROUP BY campaign_v2.id, campaign_v2_products.discount_type, campaign_v2_products.discount_percentage, campaign_v2_products.fixed_discount";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getPendingOccCampaignsToSincronizeWithOcc()
    {

        $sql = "SELECT campaign_v2.id, 
                    campaign_v2.name, 
                    campaign_v2.description, 
                    campaign_v2.start_date, 
                    campaign_v2.end_date, 
                    campaign_v2.updated_at, 
                    campaign_v2_products.discount_type, 
                    campaign_v2_products.discount_percentage, 
                    campaign_v2_products.fixed_discount
                FROM campaign_v2 
                JOIN campaign_v2_products ON (campaign_v2_products.campaign_v2_id = campaign_v2.id)
                WHERE occ_campaign_update = 1 
                  AND campaign_v2.approved = 1 
                  AND campaign_v2.campaign_type IN ('".CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT."', '".CampaignTypeEnum::MERCHANT_DISCOUNT."', '".CampaignTypeEnum::SHARED_DISCOUNT."')";
        $sql .= " GROUP BY campaign_v2.id, campaign_v2_products.discount_type, campaign_v2_products.discount_percentage, campaign_v2_products.fixed_discount";
        $query = $this->db->query($sql);
        return $query->result_array();
    }


    public function getCountCampaignsByStore(int $userstore = null): ?int
    {
        $sql = "select count(id) as total from campaign_v2 where store_seller_campaign_owner ";

        $where = " = ".$userstore;

        if (!$userstore) {
            $where = " is null";
        }

        $query = $this->db->query($sql.$where);
        return $query->row_array()['total'];
    }

    /**
     * recupera todas as campanhas de um determinado order_id
     */
    public function getAllCampaignsByOrderId($order_id = null)
    {
        if (empty($order_id)) {
            return false;
        }

        $sql = "select DISTINCT campaign_id from campaign_v2_orders_campaigns cvoc  where order_id = ? order by id desc";

        $query = $this->db->query($sql, array($order_id));
        $result = $query->result_array();

        return ($result) ? $result : false;
    }

    public function clearCampaignsTablesFromChangeSeller($orderId)
    {

        try {
            $sql1 = "delete from campaign_v2_orders_campaigns where order_id = $orderId";
            $sql2 = "delete from campaign_v2_orders_items where item_id in (select id from orders_item oi where order_id = $orderId)";
            $sql3 = "UPDATE campaign_v2_orders
                    SET total_campaigns=0, 
                        total_pricetags=0,
                        total_channel = 0, 
                        total_seller = 0, 
                        total_promotions = 0, 
                        total_rebate = 0,
                        comission_reduction= 0 , 
                        comission_reduction_products = 0, 
                        discount_comission = 0, 
                        comission_reduction_marketplace = 0, 
                        total_rebate_marketplace = 0, 
                        updated_at = now()
                    WHERE order_id= $orderId";

            $this->db->query($sql1);
            $this->db->query($sql2);
            $this->db->query($sql3);

            return true;

        } catch (Exception $e) {
            echo $e;
            return false;
        }


    }
}
