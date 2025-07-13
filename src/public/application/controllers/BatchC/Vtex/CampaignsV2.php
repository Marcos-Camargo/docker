<?php

require APPPATH . "controllers/BatchC/GenericBatch.php";

/**
 * @property Model_campaigns_v2_vtex_campaigns $model_campaigns_v2_vtex_campaigns
 * @property OrdersMarketplace $ordersmarketplace
 * @property Model_orders $model_orders
 * @property Model_campaigns_v2 $model_campaigns_v2
 */
class CampaignsV2 extends GenericBatch
{
    private $gateway_name;
    private $gateway_id;


	public function __construct()
	{
		parent::__construct();

        $logged_in_sess = array(
            'id'        => 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp'  => 1,
            'logged_in' => TRUE
        );
        
		$this->session->set_userdata($logged_in_sess);

		$this->load->library('VtexCampaigns');
        $this->load->library('ordersMarketplace');

        $this->load->model('model_orders');
        $this->load->model('model_log_integration_order_marketplace');
        $this->load->model('model_settings');
        $this->load->model('model_campaigns_v2');
        $this->load->model('model_campaigns_v2_vtex_campaigns');

    }

	public function updatePaymentMethods($id=null, $marketplace = null)
	{               
        $this->startJob(__FUNCTION__ , $id, $marketplace);

        $this->vtexcampaigns->updatePaymentMethods();

        $this->endJob();
	}

	public function updateTradePolicies($id=null, $marketplace = null)
	{
        $this->startJob(__FUNCTION__ , $id, $marketplace);

        $this->vtexcampaigns->updateTradePolicies();

        $this->endJob();
	}

	public function updateVtexCampaigns($id=null)
	{               
        $this->startJob(__FUNCTION__ , $id, null);

        $this->vtexcampaigns->sincronizeCampaigns();

        $this->endJob();

	}

    public function reprocessAllOrdersVtex($id=null)
    {
        $this->startJob(__FUNCTION__ , $id, null);

        $allow_campaign_payment_method = $this->model_settings->getValueIfAtiveByName('allow_campaign_payment_method');
        $allow_campaign_trade_policies = $this->model_settings->getValueIfAtiveByName('allow_campaign_trade_policies');

        $orders = $this->model_log_integration_order_marketplace->getAll();

        foreach ($orders as $order) {

            //Resetando cada pedido para garantir que não vai interferir
            if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){
                OrdersMarketplace::$orderItemHasMarketplaceCampaign = [];
                OrdersMarketplace::$orderItemMarketplaceCampaignDiscount = [];
            }else{
                OrdersMarketplace::$orderItemHasVtexCampaign = [];
                OrdersMarketplace::$orderItemVtexCampaign = [];
            }

            $discountItem = [];
            $prices = [];
            $totalDiscountPriceTags = 0;

            if (strstr($order['received'], 'discount@price-')){
                $payload = json_decode($order['received'], true);

                foreach ($payload['items'] as $payloadItem) {

                    $prices[$payloadItem['id']] = $payloadItem['price'];

                    $payloadItem['price'] = (float)($payloadItem['price'] / 100);
                    $discountItem[$payloadItem['id']] = 0;
                    $discountVtexCampaigns[$payloadItem['id']] = 0;

                    if (isset($payloadItem['priceTags'])) {
                        foreach ($payloadItem['priceTags'] as $priceTag) {

                            $isPricetags = true;

                            if (($allow_campaign_payment_method || $allow_campaign_trade_policies) && isset($priceTag['name']) && strstr($priceTag['name'], 'discount@price-') && strstr($priceTag['name'], '#')) {
                                $campaignId = explode('discount@price-', $priceTag['name']);
                                $campaignId = explode('#', $campaignId[1]);
                                $campaignId = $campaignId[0];
                                if ($campaignId && $this->model_campaigns_v2_vtex_campaigns->vtexCampaignIdExists($campaignId)) {
                                    /**
                                     * Produto de campanha existente na vtex + sellercenter,
                                     * mas o produto não está cadastrado na campanha sellercenter,
                                     * então precisamos abortar o recebimento do pedido
                                     */
                                    if (!$this->model_campaigns_v2_vtex_campaigns->vtexProductCampaignExists($campaignId, $payloadItem['id'])) {
                                        return [false, "Item pedido ( {$payloadItem['id']} ) de promoção vtex, não está na campanha sellercenter"];
                                    }
                                    if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){
                                        OrdersMarketplace::$orderItemHasMarketplaceCampaign[$payloadItem['id']] = $campaignId;
                                        OrdersMarketplace::$orderItemMarketplaceCampaignDiscount[$payloadItem['id']] = $priceTag;
                                    }else{
                                        OrdersMarketplace::$orderItemHasVtexCampaign[$payloadItem['id']] = $campaignId;
                                        OrdersMarketplace::$orderItemVtexCampaign[$payloadItem['id']] = $priceTag;
                                    }
                                    $isPricetags = false;
                                    $discountVtexCampaigns[$payloadItem['id']] += (float)(($priceTag['value'] * (-1) / 100)) / (int)$payloadItem['quantity'];
                                }
                            }
                            if ($isPricetags) {
                                $discountItem[$payloadItem['id']] += $priceTag['value'] * (-1);
                                $totalDiscountPriceTags += $discountItem[$payloadItem['id']];
                            }
                        }
                    }
                    if ($discountItem[$payloadItem['id']]) {
                        $discountItem[$payloadItem['id']] = (float)(($discountItem[$payloadItem['id']] / 100) / (int)$payloadItem['quantity']);
                    }
                }

                $items = $this->model_orders->getOrdersItemData($order['order_id']);
                $hasVtex = false;
                $ArrayIdCampaigns = [];
                foreach($items as $item){
                    if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){

                        if (isset(OrdersMarketplace::$orderItemHasVtexCampaign[$item['skumkt']])){

                            //Buscar todas as campanhas ativas do atual pedido
                            $AllIdCampaigns = $this->model_campaigns_v2->getAllCampaignsByOrderId($order['order_id']);
                            if($AllIdCampaigns) {
                                foreach ($AllIdCampaigns as $camp) {
                                    $ArrayIdCampaigns[$camp['campaign_id']] = $camp['campaign_id'];
                                }

                            }

                            $newRate = ($prices[$item['skumkt']] / 100) - $discountItem[$item['skumkt']] - $discountVtexCampaigns[$item['skumkt']];
                            $newDiscount = $discountItem[$item['skumkt']] > 0 ? (float)number_format($discountItem[$item['skumkt']], 3, '.', '') : $discountItem[$item['skumkt']];
                            $newAmount = (float)$newRate * $item['qty'];

                            $query = "UPDATE orders_item SET discount = '{$newDiscount}', rate = '{$newRate}', amount='{$newAmount}' WHERE order_id = {$order['order_id']} AND product_id = '{$item['product_id']}'";
                            $this->db->query($query);
                            echo $query.PHP_EOL;
                            $query = "UPDATE orders SET discount = '{$totalDiscountPriceTags}' WHERE id = {$order['order_id']}";
                            $this->db->query($query);
                            echo $query.PHP_EOL;
                            $this->model_campaigns_v2->clearCampaignsTablesFromChangeSeller($order['order_id']);
                            $hasVtex = true;
                        }

                    }else{
                        //@todo remover quando tirar a feature
                        if (isset(OrdersMarketplace::$orderItemHasMarketplaceCampaign[$item['skumkt']])){

                            //Buscar todas as campanhas ativas do atual pedido
                            $AllIdCampaigns = $this->model_campaigns_v2->getAllCampaignsByOrderId($order['order_id']);
                            if($AllIdCampaigns) {
                                foreach ($AllIdCampaigns as $camp) {
                                    $ArrayIdCampaigns[$camp['campaign_id']] = $camp['campaign_id'];
                                }

                            }

                            $newRate = ($prices[$item['skumkt']] / 100) - $discountItem[$item['skumkt']] - $discountVtexCampaigns[$item['skumkt']];
                            $newDiscount = $discountItem[$item['skumkt']] > 0 ? (float)number_format($discountItem[$item['skumkt']], 3, '.', '') : $discountItem[$item['skumkt']];
                            $newAmount = (float)$newRate * $item['qty'];

                            $query = "UPDATE orders_item SET discount = '{$newDiscount}', rate = '{$newRate}', amount='{$newAmount}' WHERE order_id = {$order['order_id']} AND product_id = '{$item['product_id']}'";
                            $this->db->query($query);
                            echo $query.PHP_EOL;
                            $query = "UPDATE orders SET discount = '{$totalDiscountPriceTags}' WHERE id = {$order['order_id']}";
                            $this->db->query($query);
                            echo $query.PHP_EOL;
                            $this->model_campaigns_v2->clearCampaignsTablesFromChangeSeller($order['order_id']);
                            $hasVtex = true;
                        }
                    }

                }

                if ($hasVtex){

                    $items = $this->model_orders->getOrdersItemData($order['order_id']);
                    foreach($items as $item){
                        $this->ordersmarketplace->saveTotalDiscounts($item,$item['id'], $ArrayIdCampaigns);
                    }
                    $itemsFinal = $this->model_orders->getOrdersItemData($order['order_id']);
                    foreach ($items as $key => $item){
                        if ($item['discount'] != $itemsFinal[$key]['discount']){
                            echo "Pedido {$item['order_id']}, Sku {$item['sku']}, Desconto de {$item['discount']} para {$itemsFinal[$key]['discount']}".PHP_EOL;
                        }
                    }
                }

            }
        }

        $this->endJob();

    }

}