<?php
use App\Libraries\Enum\ComissioningType;

/**
 * @property Model_products $model_products
 * @property Model_campaigns_v2_trade_policies $model_campaigns_v2_trade_policies
 * @property Model_campaigns_v2_payment_methods $model_campaigns_v2_payment_methods
 * @property Model_commissioning_products $model_commissioning_products
 * @property Model_commissioning_trade_policies $model_commissioning_trade_policies
 * @property Model_commissioning_categories $model_commissioning_categories
 * @property Model_commissioning_brands $model_commissioning_brands
 * @property Model_commissioning_stores $model_commissioning_stores
 * @property Model_vtex_trade_policy $model_vtex_trade_policy
 * @property Model_vtex_payment_methods $model_vtex_payment_methods
 * @property Model_stores $model_stores
 */
class Model_commissionings extends CI_Model
{

    private $tableName = 'commissionings';
    private $createLog;

    public static $commisionProducts = array();
    public static $totalTradePolicies = 0;
    public static $totalPaymentMethods = 0;

    public function __construct()
    {
        parent::__construct();

        $this->load->library('CommissioningLogs');
        $this->createLog = new CommissioningLogs();

        $this->load->model('model_products');
        $this->load->model('model_campaigns_v2_payment_methods');
        $this->load->model('model_campaigns_v2_trade_policies');
        $this->load->model('model_commissioning_products');
        $this->load->model('model_commissioning_trade_policies');
        $this->load->model('model_commissioning_categories');
        $this->load->model('model_commissioning_brands');
        $this->load->model('model_commissioning_stores');
        $this->load->model('model_vtex_trade_policy');
        $this->load->model('model_vtex_payment_methods');

    }

    public function create(array $data)
    {
        $insert = $this->db->insert($this->tableName, $data);

        $id = $this->db->insert_id();

        $this->createLog->log($data, $id, $this->tableName, __FUNCTION__);

        return $insert ? $id : false;
    }

    public function update(array $data, int $id)
    {
        $this->db->where('id', $id);
        $update = $this->db->update($this->tableName, $data);

        $this->createLog->log($data, $id, $this->tableName, __FUNCTION__);

        return $update ? $id : false;
    }

    public function getById($id)
    {
        $this->db->where('id', $id);
        $query = $this->db->get($this->tableName);
        return $query->row_array();
    }

    public function delete($id)
    {
        $this->db->where(array('id' => $id));
        $this->db->delete($this->tableName);
    }

    public function storeHasComissioningSameTypeSamePeriod(
        int $storeId,
        string $int_to,
        string $type,
        string $startDate,
        string $endDate,
        string $brandId = null,
        string $categoryId = null,
        string $vtex_trade_policy_id = null,
        string $vtex_payment_method_id = null,
        string $product_id = null,
        int $id = null
    ) {

        $sql = "SELECT count(*) total ";
        $sql .= " FROM commissionings ";
        if ($type == ComissioningType::PRODUCT) {
            $sql .= " JOIN commissioning_products ON (commissionings.id = commissioning_products.commissioning_id) ";
            $sql .= " WHERE commissionings.type = '".ComissioningType::PRODUCT."' 
                        AND commissioning_products.product_id = $product_id
                         AND commissioning_products.vtex_trade_policy_id = $vtex_trade_policy_id
                         AND commissioning_products.vtex_payment_method_id = $vtex_payment_method_id";
        }
        if ($type == ComissioningType::SELLER) {
            $sql .= " JOIN commissioning_stores ON (commissionings.id = commissioning_stores.commissioning_id) ";
            $sql .= " WHERE commissionings.type = '".ComissioningType::SELLER."' AND commissioning_stores.store_id = $storeId ";
        }
        if ($type == ComissioningType::BRAND) {
            $sql .= " JOIN commissioning_brands ON (commissionings.id = commissioning_brands.commissioning_id) ";
            $sql .= " WHERE commissionings.type = '".ComissioningType::BRAND."' 
                        AND commissioning_brands.store_id = $storeId 
                        AND commissioning_brands.brand_id = $brandId ";
        }
        if ($type == ComissioningType::CATEGORY) {
            $sql .= " JOIN commissioning_categories ON (commissionings.id = commissioning_categories.commissioning_id) ";
            $sql .= " WHERE commissionings.type = '".ComissioningType::CATEGORY."' 
                        AND commissioning_categories.store_id = $storeId 
                        AND commissioning_categories.category_id = $categoryId ";
        }
        if ($type == ComissioningType::TRADE_POLICY) {
            $sql .= " JOIN commissioning_trade_policies ON (commissionings.id = commissioning_trade_policies.commissioning_id) ";
            $sql .= " WHERE commissionings.type = '".ComissioningType::TRADE_POLICY."' 
                        AND commissioning_trade_policies.store_id = $storeId 
                        AND commissioning_trade_policies.vtex_trade_policy_id = $vtex_trade_policy_id ";
        }
        if ($id) {
            $sql .= " AND commissionings.id <> $id";
        }
        $sql .= " AND commissionings.int_to = '$int_to' ";
        $sql .= " AND ( ('$startDate' BETWEEN commissionings.start_date AND commissionings.end_date) ";
        $sql .= " OR ('$endDate' BETWEEN commissionings.start_date AND commissionings.end_date) ";
        $sql .= " OR (commissionings.start_date BETWEEN '$startDate' AND '$endDate') ";
        $sql .= " OR (commissionings.end_date BETWEEN '$startDate' AND '$endDate') ) ";

        $query = $this->db->query($sql);

        $result = $query->row_array();

        return $result['total'];

    }

    /**
     * OEP-1525 - Dilnei
     * @param  array  $campaign
     * @param  int  $product_id
     * @param  string  $dateStart
     * @param  string  $dateEnd
     * @param  string  $int_to
     * @return  array|null
     */
    public function getComissionProduct(
        array $campaign,
        int $product_id,
        string $dateStart,
        string $dateEnd,
        string $int_to
    ): array {

        if (isset(self::$commisionProducts[$campaign['id']][$product_id][$int_to][$dateStart][$dateEnd])) {
            return self::$commisionProducts[$campaign['id']][$product_id][$int_to][$dateStart][$dateEnd];
        }

        //Identificador para descobrir se temos comissionamento por um numero de política comercial diferente do total que temos hoje
        $hasComissionByTradePolicyOrPaymentMethod = false;
        //Aqui definiremos os comissionamentos para comparar ao final
        $comissionsToCompare = [];

        //Precisamos saber quantas políticas comerciais temos e quantos meios de pagamento temos para saber se o comissionamento foi criado para todos ou só pra um
        if (self::$totalPaymentMethods == 0 || self::$totalTradePolicies == 0){
            self::$totalTradePolicies = $this->model_vtex_trade_policy->countActiveByMarketplace($int_to);
            self::$totalPaymentMethods = $this->model_vtex_payment_methods->countActiveByMarketplace($int_to);
        }

        $tradePolicies = [];
        $paymentMethods = [];
        if ($campaign['vtex_campaign_update'] > 0) {
            $tradePolicies = $this->model_campaigns_v2_trade_policies->getCampaignV2TradePoliciesIds($campaign['id']);
            $paymentMethods = $this->model_campaigns_v2_payment_methods->getCampaignV2PaymentMethods($campaign['id']);
        }

        // commissioning_products
        $commissioning_products = $this->model_commissioning_products->getCommissionByProductAndDateRange(
            $int_to,
            $product_id,
            $paymentMethods,
            $tradePolicies,
            $dateStart,
            $dateEnd
        );

        if ($commissioning_products) {

            $totalFoundByPaymentMethods = count($commissioning_products);

            //O primeiro item já é o menor percentual
            $commissioning_products = reset($commissioning_products);

            $comissionPaymentMethod = [
                'comission' => $commissioning_products['comission'],
                'hierarchy' => ComissioningType::PRODUCT
            ];

            $comissionsToCompare[] = $comissionPaymentMethod;

            /**
             * Se não é campanha vtex e o total de comissionamento encontrado for !=
             * do nr de meios de pagamento x políticas comerciais, devemos considerar então o menor percentual até a
             * próxima hierarquia
             */
            if (!$paymentMethods && $totalFoundByPaymentMethods != (self::$totalTradePolicies * self::$totalPaymentMethods)) {
                $hasComissionByTradePolicyOrPaymentMethod = true;
            }

            //Não tem um numero diferente de comissionamentos, então encontramos na hierarquia
            if (!$hasComissionByTradePolicyOrPaymentMethod) {
                return self::$commisionProducts[$campaign['id']][$product_id][$int_to][$dateStart][$dateEnd] = $this->getLowestCommission($comissionsToCompare);
            }

        }

        $product_data = $this->model_products->getProductData(0, $product_id);
        $category_id = str_replace('"]', '', str_replace('["', '', $product_data['category_id']));
        $brand_id = str_replace('"]', '', str_replace('["', '', $product_data['brand_id']));
        $store_id = $product_data['store_id'];

        // commissioning_trade_policies - não precisa ser por produto.
        if ($tradePolicies) {
            $commissioning_trade_policies = $this->model_commissioning_trade_policies->getCommissionByTradePolicyAndStoreAndDateRangeAndIntTo(
                $tradePolicies,
                $dateStart,
                $dateEnd,
                $store_id,
                $int_to
            );
            if ($commissioning_trade_policies){
                $commissioning_trade_policies = reset($commissioning_trade_policies);

                $commisionTradePolicy = [
                    'comission' => $commissioning_trade_policies['comission'],
                    'hierarchy' => ComissioningType::TRADE_POLICY
                ];
                $comissionsToCompare[] = $commisionTradePolicy;

                if (!$hasComissionByTradePolicyOrPaymentMethod && $commissioning_trade_policies) {
                    return self::$commisionProducts[$campaign['id']][$product_id][$int_to][$dateStart][$dateEnd] = $this->getLowestCommission($comissionsToCompare);
                }
            }
        }else{
            //Pode estar em qualquer política comercial, então precisamos olhar para cá também
            $commissioning_trade_policies = $this->model_commissioning_trade_policies->getCommissionByTradePolicyAndStoreAndDateRangeAndIntTo(
                [],
                $dateStart,
                $dateEnd,
                $store_id,
                $int_to
            );

            if ($commissioning_trade_policies){
                $totalFoundByTradePolicy = count($commissioning_trade_policies);

                //O primeiro item já é o de menor percentual
                $commissioning_trade_policies = reset($commissioning_trade_policies);
                $commisionTradePolicy = [
                    'comission' => $commissioning_trade_policies['comission'],
                    'hierarchy' => ComissioningType::TRADE_POLICY
                ];

                $comissionsToCompare[] = $commisionTradePolicy;

                /**
                 * Se o número de comissionamentos encontrados por política comercial for diferente do numero de políticas
                 * comerciais cadastradas, não podemos usar ela ainda,
                 * precisamos descobrir o menor percentual entre elas e o próximo nível de hierarquia
                 */
                if ($totalFoundByTradePolicy != self::$totalTradePolicies) {
                    $hasComissionByTradePolicyOrPaymentMethod = true;
                }

                //Não tem um numero diferente de comissionamentos, então encontramos na hierarquia
                if (!$hasComissionByTradePolicyOrPaymentMethod) {
                    return self::$commisionProducts[$campaign['id']][$product_id][$int_to][$dateStart][$dateEnd] = $this->getLowestCommission($comissionsToCompare);
                }

            }

        }

        // commissioning_categories
        if (!empty($category_id)) {
            $commissioning_categories = $this->model_commissioning_categories->getCommissionByCategoryDateRange(
                $store_id,
                $int_to,
                $category_id,
                $dateStart,
                $dateEnd
            );

            if ($commissioning_categories) {
                $comissionByCategory = [
                    'comission' => $commissioning_categories['comission'],
                    'hierarchy' => ComissioningType::CATEGORY
                ];
                $comissionsToCompare[] = $comissionByCategory;
                return self::$commisionProducts[$campaign['id']][$product_id][$int_to][$dateStart][$dateEnd] = $this->getLowestCommission($comissionsToCompare);
            }
        }

        // commissioning_brands
        if (!empty($brand_id)) {
            $commissioning_brands = $this->model_commissioning_brands->getCommissionByBrandAndDateRange(
                $int_to,
                $brand_id,
                $store_id,
                $dateStart,
                $dateEnd
            );

            if ($commissioning_brands) {
                $comissionByBrand = [
                    'comission' => $commissioning_brands['comission'],
                    'hierarchy' => ComissioningType::BRAND
                ];
                $comissionsToCompare[] = $comissionByBrand;
                return self::$commisionProducts[$campaign['id']][$product_id][$int_to][$dateStart][$dateEnd] = $this->getLowestCommission($comissionsToCompare);
            }
        }

        // commissioning_stores
        $commissioning_stores = $this->model_commissioning_stores->getCommissionDateRange(
            $int_to,
            $store_id,
            $dateStart,
            $dateEnd
        );

        if ($commissioning_stores) {
            $comissionByStore = [
                'comission' => $commissioning_stores['comission'],
                'hierarchy' => ComissioningType::SELLER
            ];
            $comissionsToCompare[] = $comissionByStore;
            return self::$commisionProducts[$campaign['id']][$product_id][$int_to][$dateStart][$dateEnd] = $this->getLowestCommission($comissionsToCompare);
        }

        $store_data = $this->model_stores->getStoresData($store_id);

        $comissionByStoreData = [
            'comission' => $store_data['service_charge_value'],
            'hierarchy' => ComissioningType::STORE_REGISTER
        ];
        $comissionsToCompare[] = $comissionByStoreData;

        return self::$commisionProducts[$campaign['id']][$product_id][$int_to][$dateStart][$dateEnd] = $this->getLowestCommission($comissionsToCompare);

    }

    /**
     * Encontra o registro com menor comissão em um array de comissionamentos
     *
     * @param array $commissionings Array contendo registros com comission e hierarchy
     * @return array|null Retorna o array com menor comissão ou null se array vazio
     */
    public function getLowestCommission(array $commissionings): ?array
    {
        if (empty($commissionings)) {
            return null;
        }

        return array_reduce($commissionings, function($lowest, $current) {
            if (!$lowest || $current['comission'] < $lowest['comission']) {
                return $current;
            }
            return $lowest;
        });
    }


}