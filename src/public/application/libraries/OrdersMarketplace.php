<?php
defined('BASEPATH') or exit('No direct script access allowed');

use App\Libraries\Enum\CampaignTypeEnum;
use App\Libraries\Enum\ComissionRuleEnum;
use App\Libraries\Enum\DiscountTypeEnum;

require_once APPPATH . "libraries/Marketplaces/Utilities/Order.php";

/**
 * @property CI_Loader $load
 * @property CI_DB_driver $db
 *
 * @property Model_orders $model_orders
 * @property Model_settings $model_settings
 * @property Model_freights $model_freights
 * @property Model_parametrosmktplace $model_parametrosmktplace
 * @property Model_campaign_v2_orders_items $model_campaign_v2_orders_items
 * @property Model_campaigns_v2 $model_campaigns_v2
 * @property Model_stores $model_stores
 * @property Model_products $model_products
 * @property Model_orders_item $model_orders_item
 * @property Model_integrations $model_integrations
 * @property Model_products_marketplace $model_products_marketplace
 * @property Model_nfes $model_nfes
 * @property Model_orders_to_integration $model_orders_to_integration
 * @property Model_change_seller_histories $model_change_seller_histories

 * @property Model_integrations_webhook $model_change_seller_histories

 * @property Model_users $model_users
 * @property Model_attributes $model_attributes

 * @property CalculoFrete $calculofrete
 * @property Model_orders_to_process_commission $model_orders_to_process_commission
 * @property Marketplaces\Utilities\Order $marketplace_order
 */

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\InvalidArgumentException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Utils;
class OrdersMarketplace
{

    //@todo vai sumir quando implementar oep-1443-campanhas-occ
    public static $orderItemHasVtexCampaign = array();
    //@todo vai sumir quando implementar oep-1443-campanhas-occ
    public static $orderItemVtexCampaign = array();

    public static $orderItemHasMarketplaceCampaign = array();
    public static $orderItemMarketplaceCampaignDiscount = array();

    public function __construct()
    {
        $this->load->model('model_orders');
        $this->load->model('model_orders_item');
        $this->load->model('model_settings');
        $this->load->model('model_freights');
        $this->load->model('model_parametrosmktplace');
        $this->load->model('model_integrations');
        $this->load->model('model_products_marketplace');
        $this->load->model('model_nfes');
        $this->load->model('model_orders_to_integration');
        $this->load->model('model_change_seller_histories');

        $this->load->model('model_integrations_webhook');

        $this->load->model('model_users');
        $this->load->model('model_orders_to_process_commission');
        $this->load->model('model_attributes');
        $this->load->model('model_commissioning_orders_items');

        $this->load->library('calculoFrete');
        $this->load->library("Marketplaces\\Utilities\\Order", [], 'marketplace_order');

        $this->load->library('Tunalibrary');

        //Starting Tuna integration library
        $this->integration = new Tunalibrary();
    }

    /**
     * Método mágico para utilização do CI_Controller
     *
     * @param   string  $var    Propriedade para consulta
     * @return  mixed           Objeto da propriedade
     */
    public function __get(string $var)
    {
        return get_instance()->$var;
    }

    /**
     * Cancelamento de pedido, com intuito de
     *
     * @param int $order_id Código do pedido no seller center
     * @param bool $sendMkt TRUE=cancelar o pedido. seller center -> marketplace | FALSE=cancelar o pedido. marketplace -> seller center
     * @param bool $validStatus Não será feito a validação de status
     * @return bool                 Retorno o status do cancelamento
     */
    public function cancelOrder(int $order_id, bool $sendMkt, bool $validStatus = true, bool $incomplete = false): bool
    {
        $timeCancel = null;
        $sellerCancel = false;
        $log_name = __CLASS__ . '/' . __FUNCTION__;
        $external_marketplace_integration = $this->model_settings->getValueIfAtiveByName('external_marketplace_integration');

        // Dados do parametro de tempo de cancelamento
        $settingTimeCancel = $this->model_settings->getSettingDatabyName('time_not_return_stock_cancel_order');
        if ($settingTimeCancel && $settingTimeCancel['status'] == 1) {
            $timeCancel = (int)$settingTimeCancel['value'];
        }

        // Dados do motivo do pedido de cancelamento
        $setting_change_rules_cancellation_status = $this->model_settings->getValueIfAtiveByName('change_rules_cancellation_status');
        $regOrderCancel = $this->model_orders->getPedidosCanceladosByOrderId($order_id);
        if ($regOrderCancel) {
            // Nova regra de cancelamento
            if ($setting_change_rules_cancellation_status) {
                $user_id_canceled = $regOrderCancel['user_id'];
                // No cancelamento existe um usuário.
                if ($user_id_canceled) {
                    $data_user_group = $this->model_users->getUserGroup($user_id_canceled);
                    // O grupo do usuário não é administrador.
                    if ($data_user_group && $data_user_group['only_admin'] != 1) {
                        $sellerCancel = true;
                    }
                }
            } elseif ($regOrderCancel['penalty_to'] == '1-Seller') {
                $sellerCancel = true;
            }
        }

        // Dados do pedido
        $order = $this->model_orders->getOrdersData(0, $order_id);

        // Pedido não está no status para cancelar
        if ($validStatus && $sendMkt && $order['paid_status'] != 99) {
            return false;
        }

        // Pedido já está cancelado ou em processo de cancelamento
        elseif ($validStatus && !$sendMkt && in_array($order['paid_status'], array(95, 96, 97, 98))) {
            return false;
        }

        // Cancela definitivo pré pagamento
        elseif ($order['paid_status'] == 1) {
            $update = array(
                'is_incomplete' => $incomplete,
                'paid_status'   => $this->model_orders->PAID_STATUS['canceled_before_payment']
            );
            $this->model_orders->updateOrderById($order_id, $update);
            // Rick informou na daily do dia 04/03/21 que os não pagos irá para o 96 (cancelado pelo MKT)
            // elseif ($order['paid_status'] == 1) $this->model_orders->updatePaidStatus($order_id, $sellerCancel ? 94 : 96);

            // Devolvo o estoque para os produtos
            if (!$sendMkt && ($timeCancel === null || time() < strtotime("+{$timeCancel} minutes", strtotime($order['date_time'])))) {
                $items = $this->model_orders->getOrdersItemData($order['id']);
                foreach ($items as $item) {
                    $this->model_products->adicionaEstoque($item['product_id'], $item['qty'], $item['variant'], $order['id']);
                }
            }

            // crio data de cancelamento
            $this->model_orders->updateByOrigin($order_id, array('date_cancel' => date('Y-m-d H:i:s')));

            // Crio log do pedido cancelado
            get_instance()->log_data('batch', $log_name, "Pedido ( {$order['id']} ) cancelado antes de ser pago. \n\n ORDER=" . json_encode($order), 'I');
            return true;
        }

        // Apenas verifica se não já está com algum desses status, evitando erro no caso de notificação duplicada.
        if (!in_array($order['paid_status'], array(95, 96, 97))) {
            $this->model_orders->updatePaidStatus($order_id, $sellerCancel ? 95 : 97);
        }

        // crio data de cancelamento
        $this->model_orders->updateByOrigin($order_id, array('date_cancel' => date('Y-m-d H:i:s')));


        $cancelOrderWebhook  = [
            'id' =>  "$order_id",
            "code" => $order['paid_status'],
            'status' => 'pedido_cancelado'
        ];
        $this->load->model('model_integrations_webhook');

        if ($this->model_integrations_webhook->storeExists($order['store_id'])) {
            $this->load->library('ordersmarketplace');

            $store_id_wh = $order['store_id'];
            $typeIntegration = "pedido_cancelado";
            $this->ordersmarketplace->sendDataWebhook($store_id_wh,$typeIntegration,$cancelOrderWebhook);
        }    

        try {
            if ($external_marketplace_integration) {
                $this->marketplace_order->setExternalIntegration($external_marketplace_integration);
                $this->marketplace_order->external_integration->notifyOrder($order_id, 'cancel');
            }
        } catch (Exception | Error $exception) {
            get_instance()->log_data('batch', $log_name, "Não foi possível notificar o integrador externo sobre o cancelamento para o pedido $order_id. {$exception->getMessage()}", "E");
        }

        //envia o cancelamento para a Tuna caso o Gateway esteja configurado para ele -- payment_gateway_id
        $gatewayID = $this->model_settings->getSettingDatabyName('payment_gateway_id');
        if($gatewayID['value'] == 8){
            $this->integration->geracancelamentotuna($order);
        }


        get_instance()->log_data('batch', $log_name, "Pedido ( {$order['id']} ) cancelado. \n\n ORDER=" . json_encode($order) . "\n\n REGISTRO_ORDER_CANCEL_SELLER=" . json_encode($regOrderCancel), 'I');
        return true;
    }

    /**
     * @param string $key Chave da NFe
     * @param int $serie Série da NFe
     * @param int $number Número da NFe
     * @param string $dateEmission Data de emissão da NFe (Y-m-d)
     * @return  array                   Retorna um array com duas posições, onde a primeira é o status da validação e a segunda a mensagem adicional
     */
    public function checkKeyNFe(string $key, int $serie, int $number, string $dateEmission, int $store_id, ?int $ignore_order_id = null): array
    {

        // criado o parâmetro que desabilita ou habilita a verificação dos dados da NFe
        if ($this->model_settings->getStatusbyName('nfe_rule_validation') == '2'){

            $uf = array(11, 12, 13, 14, 15, 16, 17, 21, 22, 23, 24, 25, 26, 27, 28, 29, 31, 32, 33, 35, 41, 42, 43, 50, 51, 52, 53);
            $emissionType = array(1, 2, 3);

            if (strlen($key) != 44) {
                return array(false, 'Chave deve conter 44 dígitos');//Key with 44 digits.
            } else if (!in_array(substr($key, 0, 2), $uf)) {
                return array(false, 'Chave com código do estado emissor inválido');//Key with invalid issuer state code
                //} elseif (substr($dateEmission,2,2)!=substr($key,2,2)) {
                //    return array(false, 'Year of key is different from year on issue date');
                //} elseif (substr($dateEmission,5,2)!=substr($key,4,2)) {
                //    return array(false, 'Month of key is different from month on issue date');
            } elseif ($serie != substr($key, 22, 3)) {
                return array(false, 'O número de série da chave é diferente do que foi informado.');//Serial number of the key is different from what was informed.
            } elseif ($number != substr($key, 25, 9)) {
                return array(false, 'O número da chave da fatura é diferente da informada.');//The number invoice of key is different from the one informed.
            } elseif (substr($key, 20, 2) != '55') {
                return array(false, 'O número da chave informado não pode ser um cupom fiscal.');
            }
            //elseif (!in_array(substr($key,34,1), $emissionType)){
            //    return array(false, 'Key with invalid emission type');
            //}
            $digitKey = substr($key, 43, 1);
            $digitCheck = $this->getDvNfe($key);

            if ($digitKey != $digitCheck) {
                return array(false, 'Chave com dígito de verificação inválido');
            }
        
        } // fim do bloco de validação do parâmetro da NF

        // Verifica se a chave já existe para algum pedido da loja.
        $nfe_order = $this->model_nfes->getNfeByChaveAndStore($key, $ignore_order_id);
        if ($nfe_order) {
            return array(false, "Nota fiscal já enviada para o pedido {$nfe_order['order_id']}. Verifique se a chave da nota está correta e tente novamente.");
        }

        return array(true, 'Chave correta');
    }

    public function getDvNfe(string $key): int
    {
        $key_validate = substr($key, 0, 43);
        $weight = array(2, 3, 4, 5, 6, 7, 8, 9);
        $countWeight = 0;
        $sumWeight = 0;
        for ($i = strlen($key_validate) - 1; $i >= 0; $i--) {
            $numero = substr($key_validate, $i, 1);
            $ponderacao = (int) $numero * $weight[$countWeight];
            $sumWeight = $sumWeight + $ponderacao;
            $countWeight++;
            if ($countWeight > 7) {
                $countWeight = 0;
            }
        }
        $rest = ($sumWeight % 11);

        if ($rest == 0 || $rest == 1) {
            $digitCheck = 0;
        } else {
            $digitCheck = 11 - $rest;
        }

        return $digitCheck;
    }

    /**
     * Recupera a descrição do status
     *
     * @param   int     $status    Status do pedido (orders.paid_status)
     * @param   string  $language  Idioma de retorno (en | pt_br)
     * @return  string             Descrição do status
     */
    public function getStatusOrder(int $status, string $language = 'pt_br'): string
    {
        $lang = array();

        switch ($language) {
            case 'en':
                $language = 'english';
                break;
            case 'pt_br':
                $language = 'portuguese_br';
                break;
            default:
                return '';
        }

        // carrega arquivo de linguagem para indentificar a descrição do status
        include(APPPATH . "language/{$language}/application_lang.php");

        return $lang["application_order_{$status}"] ?? '';
    }


    //braun
    public function saveTotalDiscounts(&$data, int $orderItemId, array $ArrayIdCampaigns = array()): void
    {

        error_reporting(E_ERROR | E_WARNING | E_PARSE);

        $this->load->model('model_campaign_v2_orders_items');
        $this->load->model('model_campaigns_v2');
        $this->load->model('model_stores');
        $this->load->model('model_products');
        $this->load->model('model_settings');
        
        $product_id = $data['product_id'];

        if($data['fulfillment_product_id']){
            $product_id = $data['fulfillment_product_id'];
        }

        $order_id = $data['order_id'];
        // $discountComission = 0;
        $total_in_campaigns = 0;
        $totalFinalCampaigns = 0;
        $totalDescontoItem = 0;
        $line_data = [];
        $line_data_campaigns = [];

        $order_data = $this->model_orders->getOrdersData(0, $order_id);
        $int_to = $order_data['origin'];
        $store_data =  $this->model_stores->getStoresData($order_data['store_id']);
        $product_data = $this->model_products->getProductData(0, $product_id);

        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')) {
            $marketplaceCampaignId = self::$orderItemHasMarketplaceCampaign[$data['skumkt']] ?? false;
            $marketplaceCampaignDiscount = self::$orderItemMarketplaceCampaignDiscount[$data['skumkt']] ?? false;
        } else {
            //@todo pode remover
            $marketplaceCampaignId = self::$orderItemHasVtexCampaign[$data['skumkt']] ?? false;
            $marketplaceCampaignDiscount = self::$orderItemVtexCampaign[$data['skumkt']] ?? false;
        }

        $variant_position = null;

        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('OEP-1987-campanhas-por-sku')) {
            if ($data['variant'] !== '') {
                $variant_position = $data['variant'];
            }
        }

        // Busca as campanhas pelos Ids já fornecidos na função
        if ($ArrayIdCampaigns) {
            $IdCampaigns = str_replace(",", "','", implode(",", $ArrayIdCampaigns));
            $has_campaigns = $this->model_campaigns_v2->getProductsCampaignWithDiscount(
                $product_id,
                $int_to,
                $marketplaceCampaignId,
                $IdCampaigns,
                null,
                $variant_position
            );
        } else {
            $has_campaigns = $this->model_campaigns_v2->getProductsCampaignWithDiscount(
                $product_id,
                $int_to,
                $marketplaceCampaignId,
                null,
                null,
                $variant_position
            );
        }

        $line_data['total_pricetags'] = $data['discount'] * $data['qty'];
        $line_data['total_products'] = (float)$product_data['price'] * $data['qty'];
        $line_data['discount_comission'] = 0;
        $line_data['comission_reduction_products'] = 0;
        $unity_price = (float)$product_data['price'];

        if ($has_campaigns) {

            foreach ($has_campaigns as $campaign) {

                $campaign_id = $campaign['id'];

                $total_channel = 0;
                $total_seller = 0;
                $discount_comission = 0;

                if ($campaign['discount_type'] == DiscountTypeEnum::PERCENTUAL) {

                    //Se o desconto foi dado em campanha vtex, devemos pegar o desconto dado pela vtex e não um novo desconto
                    $bool = \App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ') ? $marketplaceCampaignId : $marketplaceCampaignId;
                    if ($bool){

                        if (\App\Libraries\FeatureFlag\FeatureManager::isFeatureAvailable('oep-1443-campanhas-occ')){
                            if ($marketplaceCampaignDiscount){
                                $totalDiscount = -($marketplaceCampaignDiscount / $data['qty']);
                            }
                        }else{
                            $totalDiscount = -($marketplaceCampaignDiscount['rawValue'] / $data['qty']);
                        }

                        switch ($campaign['campaign_type']) {
                            case CampaignTypeEnum::MERCHANT_DISCOUNT:
                                $total_channel  = 0;
                                $total_seller  = (float)($totalDiscount * $data['qty']);
                                break;
                            case CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT:
                                $total_channel  = (float)($totalDiscount * $data['qty']);
                                $total_seller  = 0;
                                $discount_comission  = (float)($totalDiscount * $data['qty']);
                                break;
                            default:
                                $total_channel  = (float)(($campaign['marketplace_discount_percentual'] * $totalDiscount / $campaign['discount_percentage']) * $data['qty']);
                                $total_seller  = (float)(($campaign['seller_discount_percentual'] * $totalDiscount / $campaign['discount_percentage']) * $data['qty']);
                                $discount_comission  = (float)(($campaign['marketplace_discount_percentual'] * $totalDiscount / $campaign['discount_percentage']) * $data['qty']);
                        }

                    }else{

                        $totalDiscount = $campaign['total_discount'];

                        switch ($campaign['campaign_type']) {
                            case CampaignTypeEnum::MERCHANT_DISCOUNT:
                                $total_channel  = 0;
                                $total_seller  = (float)($totalDiscount * $data['qty']);
                                break;
                            case CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT:
                                $total_channel  = (float)($totalDiscount * $data['qty']);
                                $total_seller  = 0;
                                $discount_comission  = (float)($totalDiscount * $data['qty']);
                                break;
                            default:
                                $total_channel  = (float)(($campaign['marketplace_discount_percentual'] * $totalDiscount / $campaign['discount_percentage']) * $data['qty']);
                                $total_seller  = (float)(($campaign['seller_discount_percentual'] * $totalDiscount / $campaign['discount_percentage']) * $data['qty']);
                                $discount_comission  = (float)(($campaign['marketplace_discount_percentual'] * $totalDiscount / $campaign['discount_percentage']) * $data['qty']);
                        }

                    }

                } else {

                    switch ($campaign['campaign_type']) {
                        case CampaignTypeEnum::MERCHANT_DISCOUNT:
                            $total_channel = 0;
                            $total_seller = (float)($campaign['fixed_discount'] * $data['qty']);
                            break;
                        case CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT:
                            $total_channel = (float)($campaign['fixed_discount'] * $data['qty']);
                            $total_seller = 0;
                            $discount_comission = (float)($campaign['fixed_discount'] * $data['qty']);
                            break;
                        default:
                            $total_channel = (float)($campaign['marketplace_discount_fixed'] * $data['qty']);
                            $total_seller = (float)($campaign['seller_discount_fixed'] * $data['qty']);
                            $discount_comission = (float)($campaign['marketplace_discount_fixed'] * $data['qty']);
                    }
                }

                if ($campaign['b2w_type'] == 0) {
                    $line_data['total_channel'] = $total_channel;
                    $line_data['total_seller'] = $total_seller;
                    $line_data['discount_comission'] = $discount_comission;
                }

                // $line_data['channel_total_discount']  += $per_campaign['channel_discount'];
                // $line_data['seller_total_discount']   += $per_campaign['seller_discount'];

                $line_data['total_campaigns']               = (float)($totalDiscount * $data['qty']);
                $total_in_campaigns                         = (float)($totalDiscount * $data['qty']);
                $line_data_campaigns['total_discount']      = (float)($totalDiscount * $data['qty']);
                $line_data_campaigns['channel_discount']    = (float)$total_channel;
                $line_data_campaigns['seller_discount']     = (float)$total_seller;
                $totalDescontoItem                          += $totalDiscount;

                $this->model_orders->addOrderDiscountCampaings($line_data_campaigns, $order_id, $campaign_id);

                $totalFinalCampaigns += $total_in_campaigns;

                $data_table = [
                    'item_id' => $orderItemId,
                    'campaign_v2_id' => $campaign_id,
                    'channel_discount' => $line_data_campaigns['channel_discount'],
                    'seller_discount' => $line_data_campaigns['seller_discount'],
                    'total_discount' => $line_data_campaigns['total_discount']
                ];
                $this->model_campaign_v2_orders_items->save($data_table);


            }
        }

        $queryPromotion = $this->db->query("SELECT p.* FROM promotions AS p JOIN promotions_group AS pr ON p.lote = pr.lote WHERE p.product_id = ? AND p.active = 1 AND pr.ativo = 1 AND
                            (pr.marketplace = ? || pr.marketplace = 'Todos')", array($product_id, $int_to));
        $promotion = $queryPromotion->row_array();

        if ($promotion) {

            $total_in_campaigns            = ($totalFinalCampaigns == 0) ? $unity_price - (float)$promotion['price'] : $totalFinalCampaigns;

            if (!$has_campaigns) {

                $line_data['total_promotions'] = ($unity_price - (float)$promotion['price']) * $data['qty'];

                $data['rate'] += $line_data['total_promotions'];
                $data['amount'] = (float)$data['rate'] * $data['qty'];
            }
        }

        $this->model_orders->setNewDiscountOnOrder($data['order_id'], $orderItemId, $total_in_campaigns, $totalDescontoItem);

        if($ArrayIdCampaigns){
            $IdCampaigns = implode(",",$ArrayIdCampaigns);
            $has_reduxrebate =  $this->model_campaigns_v2->getProductsCampaignWithComissionReductionRebate($product_id, $int_to, $IdCampaigns);
        }else{
            $has_reduxrebate =  $this->model_campaigns_v2->getProductsCampaignWithComissionReductionRebate($product_id, $int_to);
        }

        if ($has_reduxrebate) {

            unset($line_data_campaigns);
            $line_data_campaigns = [];
            $data_table = [];

            $final_price = $data['rate']; //estava antes como amount, o problema que eh amout eh * quantidade e vai bugar no if a seguir

            if ($promotion && !$has_campaigns) {
                $final_price -= $total_in_campaigns; //no caso o total em campanhas eh * quantidade enquanto em promocoes nao é
            }

            if ($final_price <= (float)$has_reduxrebate['maximum_share_sale_price']) {

                if ($has_reduxrebate['comission_rule'] == ComissionRuleEnum::COMISSION_REBATE) {

                    $line_data['total_rebate'] = $line_data_campaigns['total_rebate'] = $has_reduxrebate['rebate_value'] * $data['qty'];
                    $data_table['total_rebate'] = $line_data['total_rebate'];
                } else if ($has_reduxrebate['comission_rule'] == ComissionRuleEnum::NEW_COMISSION) {

                    $tax_percentual = $store_data['service_charge_value'];

                    $tax_total      = (float)((($tax_percentual / 100) * ($final_price + ($line_data['discount_comission'] / $data['qty'])) * $data['qty']));

                    $new_percentual = $has_reduxrebate['new_comission'];
                    $new_tax_total  = (float)((($new_percentual / 100) * ($final_price + ($line_data['discount_comission'] / $data['qty'])) * $data['qty']));

                    $line_data['comission_reduction'] = $tax_total - $new_tax_total;
                    $line_data_campaigns['total_reduced'] = $tax_total - $new_tax_total;
                    $data_table['total_reduced'] = $line_data_campaigns['total_reduced'];

                    if ($this->model_settings->getStatusbyName('api_comission') == '1')
                    {
                        $product_store_comission = (float)(($tax_percentual / 100) * $data['rate']);
                        $product_new_comission = (float)(($new_percentual / 100) * $data['rate']);

                        $line_data['comission_reduction_products'] = (abs($product_store_comission - $product_new_comission) > 0) ? abs(($product_store_comission - $product_new_comission) * $data['qty']) : null;
                    }
                }
            }
            if (count($data_table) > 0) {
                $data_table['item_id'] = $orderItemId;
                $data_table['campaign_v2_id'] = $has_reduxrebate['id'];
                $this->model_campaign_v2_orders_items->save($data_table);
            }

            $this->model_orders->addOrderDiscountCampaings($line_data_campaigns, $order_id, $has_reduxrebate['id']);

        }

        if ($this->model_settings->getSettingDatabyName('negociacao_marketplace_campanha')['value'] == 1){

            $hasReduxrebateMarketplace =  $this->model_campaigns_v2->getProductsCampaignWithMarketplaceTrading($product_id, $int_to);

            if ($hasReduxrebateMarketplace) {

                $line_data_campaigns = [];
                $data_table = [];

                $final_price = $data['amount'];

                if ($promotion && !$has_campaigns) {
                    $final_price -= $total_in_campaigns;
                }

                if ($hasReduxrebateMarketplace['comission_rule'] == ComissionRuleEnum::COMISSION_REBATE) {

                    $line_data['total_rebate_marketplace'] = $line_data_campaigns['total_rebate_marketplace'] = $hasReduxrebateMarketplace['rebate_value'] * $data['qty'];
                    $data_table['total_rebate_marketplace'] = $line_data['total_rebate_marketplace'];
                } else if ($hasReduxrebateMarketplace['comission_rule'] == ComissionRuleEnum::NEW_COMISSION) {

                    $tax_percentual = $store_data['service_charge_value'];

                    //Verifica se tem o percentual da comissão diferenciado na categoria
                    $taxPercentualCategoria = $this->model_parametrosmktplace->getValorAplicadoByProductIdIntTo($data['product_id'], $int_to);
                    if ($taxPercentualCategoria){
                        $tax_percentual = $taxPercentualCategoria;
                    }

                    $tax_total      = (float)((($tax_percentual / 100) * ($final_price + ($line_data['discount_comission'] / $data['qty'])) * $data['qty']));

                    $new_percentual = $hasReduxrebateMarketplace['new_comission'];
                    $new_tax_total  = (float)((($new_percentual / 100) * ($final_price + ($line_data['discount_comission'] / $data['qty'])) * $data['qty']));

                    $line_data['comission_reduction_marketplace'] = $tax_total - $new_tax_total;
                    $line_data_campaigns['total_reduced_marketplace'] = $tax_total - $new_tax_total;
                    $data_table['total_reduced_marketplace'] = $line_data_campaigns['total_reduced_marketplace'];
                }

                if (count($data_table) > 0) {
                    $data_table['item_id'] = $orderItemId;

                    $data_table['campaign_v2_id'] = $hasReduxrebateMarketplace['id'];
                    $this->model_campaign_v2_orders_items->save($data_table);
                }

                $this->model_orders->addOrderDiscountCampaings($line_data_campaigns, $order_id, $hasReduxrebateMarketplace['id']);

            }

        }

        $this->model_orders->addOrderDiscount($line_data, $order_id);

        $this->setNewComissionItemOrder($order_data['origin'], $order_data['id']);
    }

    /**
     * Valida os itens do pedido de API.
     *
     * @param   array        $items         Itens do pedido. O vetor deve conter os dados de sku, qty e seller(Caso seja VTEX).
     * @param   string       $platform      Plataforma do marketplace (VTEX, OCC, B2W, VIA).
     * @param   string       $channel       Canal da venda (Plishop, Decathlon, B2W, VIA).
     * @throws Exception
     */
    public function validItemsOrder(array $items, string $platform, string $channel): array
    {
        $mkt = [
            'platform'  => $platform,
            'channel'   => $channel
        ];

        $columnMkt      = $this->calculofrete->getColumnsMarketplace($platform);
        $table          = $columnMkt['table'];
        $columnTotalQty = $columnMkt['qty'];

        $this->calculofrete->setFieldSKUQuote($platform);
        
        try {
            $response = $this->calculofrete->validItemsQuote($items, $mkt, $table, $columnTotalQty);

            if (isset($response['quoteResponse']['success']) && $response['quoteResponse']['success'] === false) {
                throw new Exception($response['quoteResponse']['data']['message']);
            }

        } catch (Exception | Error $exception) {
            throw new Exception($exception->getMessage());
        }

        return $response;
    }

    /**
     * Consulta se um pedido já existe para um marketplace.
     *
     * @param   string  $code    Código do pedido no marketplace.
     * @param   string  $channel Marketplace.
     * @throws  Exception
     */
    public function validCodeMarketpalceOrder(string $code, string $channel)
    {
        if ($this->model_orders->getOrderBynumeroMarketplaceAndOrigin($code, $channel)) {
            throw new Exception("Código '$code', já existente para o marketplace '$channel'");
        }
    }

    /**
     * Valida os valores do pedido de API.
     *
     * @param   array       $items
     * @param   array       $datapayment
     * @param   float       $shippingValue
     * @throws  Exception
     */
    public function validAmountOrderByApi(array $items, array $datapayment, float $shippingValue)
    {
        $totalItemsNet      = 0;
        $totalItemsGross    = 0;
        $totalItemsDiscount = 0;
        $totalItemsProduct  = 0;

        foreach ($items as $item) {
            $qty = (int)$item['qty'];
            $itenNet = $qty * ($item['original_price'] - $item['discount']);

            $totalItemsNet      += $itenNet;
            $totalItemsGross    += $qty * $item['original_price'];
            $totalItemsDiscount += $qty * $item['discount'];
            $totalItemsProduct  += $itenNet;
        }

        // Adicionando o valor do frete.
        $totalItemsNet      += $shippingValue;
        $totalItemsGross    += $shippingValue;

        $totalItemsNet      = roundDecimal($totalItemsNet);
        $totalItemsGross    = roundDecimal($totalItemsGross);
        $totalItemsDiscount = roundDecimal($totalItemsDiscount);
        $totalItemsProduct  = roundDecimal($totalItemsProduct);

        $totalNet       = roundDecimal($datapayment['net_amount']);
        $totalGross     = roundDecimal($datapayment['gross_amount']);
        $totalDiscount  = roundDecimal($datapayment['discount']);
        $totalProducts  = roundDecimal($datapayment['total_products']);

        if ($totalDiscount != $totalItemsDiscount) {
            throw new Exception("Total de desconto do pagamento, difere do total de desconto dos itens.");
        }

        if ($totalNet != $totalItemsNet) {
            throw new Exception("Total líquido do pagamento, difere do total líquido dos itens.");
        }

        if ($totalGross != $totalItemsGross) {
            throw new Exception("Total bruto do pagamento, difere do total bruto dos itens.");
        }

        if ($totalProducts != $totalItemsProduct) {
            throw new Exception("Total dos produtos do pagamento, difere do total do produto dos itens.");
        }
    }

    /**
     * Consulta se o marketplace existe no ambiente.
     *
     * @param   string      $marketpalce Conhecido como: int_to, origin.
     * @throws  Exception
     */
    public function validIfMarketplaceExist(string $marketpalce)
    {
        if (!$this->model_integrations->getIntegrationByIntTo($marketpalce, 0)) {
            throw new Exception("Marketplace '$marketpalce' não existente.");
        }
    }

    /**
     * Formata os dados do pedido de API.
     *
     * @param   array   $order      Dados do pedido.
     * @param   array   $store      Dados da loja.
     * @param   int     $clientId   Código do cliente.
     * @return  array
     */
    public function formatOrderToCreateApi(array $order, array $store, int $clientId): array
    {
        $shipping = $order['shipping'];
        $shippingAddress = $shipping['shipping_address'];
        $payments = $order['payments'];

        $orderFormat = array(
            'ship_company_preview'          => $shipping['shipping_carrier_preview'],
            'ship_service_preview'          => $shipping['service_method_preview'],
            'ship_time_preview'             => $shipping['estimated_delivery_days'],
            'customer_name'                 => $shippingAddress['full_name'],
            'customer_address_zip'          => $shippingAddress['postcode'],
            'customer_address'              => $shippingAddress['street'],
            'customer_address_num'          => $shippingAddress['number'],
            'customer_address_compl'        => $shippingAddress['complement'],
            'customer_reference'            => $shippingAddress['reference'],
            'customer_address_neigh'        => $shippingAddress['neighborhood'],
            'customer_address_city'         => $shippingAddress['city'],
            'customer_address_uf'           => $shippingAddress['region'],
            'bill_no'                       => $order['marketplace_number'],
            'numero_marketplace'            => $order['marketplace_number'],
            'date_time'                     => $order['created_at'],
            'customer_id'                   => $clientId,
            'customer_phone'                => $shippingAddress['phone'],
            'total_order'                   => $payments['total_products'], // por padrão é o valor líquido dos itens
            'total_ship'                    => $shipping['seller_shipping_cost'],
            'gross_amount'                  => $payments['gross_amount'],
            'net_amount'                    => $payments['net_amount'],
            'service_charge_rate'           => $store['service_charge_value'],
            'service_charge_freight_value'  => $store['service_charge_freight_value'],
            'discount'                      => $payments['discount'],
            'paid_status'                   => 1, // por padrão sem entrará como não pago.
            'company_id'                    => $store['company_id'],
            'store_id'                      => $store['id'],
            'origin'                        => $order['system_marketplace_code'],
            'user_id'                       => 1,
            'data_limite_cross_docking'     => null,
        );

        $orderFormat['service_charge']       = number_format(($orderFormat['total_order'] * ($store['service_charge_value'] / 100)) + ($orderFormat['total_ship'] * ($store['service_charge_freight_value'] / 100)), 2, '.', '');
        $orderFormat['vat_charge_rate']      = 0;
        $orderFormat['vat_charge']           = number_format(($orderFormat['gross_amount'] - $orderFormat['total_ship']) * ($orderFormat['vat_charge_rate'] / 100), 2, '.', '');

        return $orderFormat;
    }

    /**
     * Formata o cliente do pedido de API.
     *
     * @param   array $order    Dados do pedido.
     * @return  array
     */
    public function formatClientToCreateApi(array $order): array
    {
        $billingAddress = $order['billing_address'];
        $customer = $order['customer'];

        return array(
            'customer_name'     => $customer['full_name'],
            'cpf_cnpj'          => onlyNumbers($customer['cpf'] ?? $customer['cnpj']),
            'ie'                => onlyNumbers($customer['ie']),
            'phone_1'           => onlyNumbers(str_replace('+55', '', $customer['phones'][0] ?? $customer['phones'][1] ?? '')),
            'phone_2'           => onlyNumbers(str_replace('+55', '', $customer['phones'][1] ?? $customer['phones'][0] ?? '')),
            'email'             => $customer['email'],
            'customer_address'  => $billingAddress['street'],
            'addr_num'          => $billingAddress['number'],
            'addr_compl'        => $billingAddress['complement'],
            'addr_neigh'        => $billingAddress['neighborhood'],
            'addr_city'         => $billingAddress['city'],
            'addr_uf'           => $billingAddress['region'],
            'country'           => $billingAddress['country'],
            'zipcode'           => onlyNumbers($billingAddress['postcode']),
            'origin'            => $order['system_marketplace_code'],
            'origin_id'         => 1
        );
    }

    /**
     * Fromata os itens do pedido de API.
     *
     * @param   array   $order      Dados do pedido.
     * @param   int     $orderId    Código do pedido (orders.id).
     * @param   string  $platform   Plataforma do marketplace.
     * @return  array
     * @throws  Exception
     */
    public function formatOrderItemToCreateApi(array $order, int $orderId, string $platform): array
    {
        $itemsOrder = array();

        try {
            $response = $this->validItemsOrder($order['items'], $platform, $order['system_marketplace_code']);
        } catch (Exception | Error $exception) {
            throw new Exception($exception->getMessage());
        }

        foreach($order['items'] as $item) {
            $prf = $response['arrDataAd'][$item['sku']];

            $dataPrdVar = $dataPrd = $this->model_products->getProductData(0, $prf['prd_id']);

            if ($prf['variant'] !== null && $prf['variant'] !== '') { // é variação
                $dataPrdVar = $this->model_products->getVariants($prf['prd_id'], $prf['variant']);
            }

            // Produto ou variação não encontrada.
            if (!$dataPrdVar) {
                throw new Exception("Produto ( {$item['sku']} ) não localizado!");
            }

            $dataMkt = $this->model_products_marketplace->getDataByUniqueKey($order['system_marketplace_code'], $prf['prd_id'], $prf['variant'] === null ? '' : $prf['variant']);

            // Existe estoque por marketplace.
            if ($dataMkt) {
                $qtyPrd = $dataMkt['same_qty'] == 1 ? $dataPrdVar['qty'] : ($dataMkt['qty'] === '' ? $dataPrdVar['qty'] : $dataMkt['qty']);
            } else {
                $qtyPrd = $dataPrdVar['qty'];
            }

            // Estoque da comprar é maior do que disponível.
            if ($item['qty'] > $qtyPrd) {
                throw new Exception("Produto ( {$item['sku']} ) sem estoque!");
            }

            if ($dataPrd['is_kit'] == 0) {

                $variant='';
                if (!is_null($prf['variant'])) {
                    $variant = $prf['variant'];
                }

                $rate = $item['original_price'] - $item['discount'];

                $items = array(
                    'qty'           => (int)$item['qty'],
                    'rate'          => $rate,
                    'amount'        => $rate * $item['qty'],
                    'discount'      => $item['discount'],
                    'skumkt'        => $item['sku'],
                    'order_id'      => $orderId,
                    'product_id'    => $prf['prd_id'],
                    'sku'           => $prf['sku'],
                    'variant'       => $variant,
                    'name'          => $dataPrd['name'],
                    'company_id'    => $dataPrd['company_id'],
                    'store_id'      => $dataPrd['store_id'],
                    'un'            => 'un',
                    'pesobruto'     => $dataPrd['peso_bruto'],
                    'largura'       => $dataPrd['largura'],
                    'altura'        => $dataPrd['altura'],
                    'profundidade'  => $dataPrd['profundidade'],
                    'unmedida'      => 'cm',
                    'kit_id'        => null
                );

                $itemsOrder[] = $items;
            } else {
                $productsKit = $this->model_products->getProductsKit($prf['prd_id']);
                foreach ($productsKit as $productKit){
                    $prd_kit = $this->model_products->getProductData(0,$productKit['product_id_item']);

                    // Produto do kit não encontrado.
                    if (!$prd_kit) {
                        throw new Exception("Produto ( {$item['sku']} ) do kit não localizado!");
                    }

                    $quantity = $item['qty'] * $productKit['qty'];

                    $items = array(
                        'order_id'      => $orderId,
                        'skumkt'        => $item['sku'],
                        'kit_id'        => $productKit['product_id'],
                        'product_id'    => $prd_kit['id'],
                        'sku'           => $prd_kit['sku'],
                        'variant'       => '',
                        'name'          => $prd_kit['name'],
                        'qty'           => $quantity,
                        'rate'          => $productKit['price'],
                        'amount'        => ($productKit['price'] * $quantity) - $item['discount'],
                        'discount'      =>  $item['discount'],
                        'company_id'    => $prd_kit['company_id'],
                        'store_id'      => $prd_kit['store_id'],
                        'un'            =>  $iten['measurementUnit'] ?? 'un',
                        'pesobruto'     => $prd_kit['peso_bruto'],
                        'largura'       => $prd_kit['largura'],
                        'altura'        => $prd_kit['altura'],
                        'profundidade'  => $prd_kit['profundidade'],
                        'unmedida'      => 'cm'
                    );

                    $itemsOrder[] = $items;
                }

            }
        }

        return $itemsOrder;
    }

    /**
     * Trocar pedido de seller Multi CD.
     *
     * @param   int     $order_id               Código do pedido(order.id).
     * @param   array   $data_principal_store   Dados da loja principal(stores).
     * @param   array   $order                  Dados do pedido(orders).
     * @param   string  $log_name               Ação para salvar na tabela de logs batch.
     *
     * @throws  Exception
     */
    public function changeSeller(int $order_id, array $data_principal_store, array $order, string $log_name = __CLASS__ . '/' . __FUNCTION__)
    {
        $this->load->model('model_campaigns_v2');

        // Colocado no início para garantir que todas as mudanças serão efetivadas ao mesmo tempo, mudança de pedido/item/campanha
        $this->db->trans_begin();


        $date_now = dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL);
        $items = $this->model_orders->getOrdersItemData($order_id);
        $update_order_items = array();

        // Validação se todos os skus existem na loja principal.
        foreach ($items as $item) {
            $update_order_item = array(
                'store_id'   => $data_principal_store['id'],
                'company_id' => $data_principal_store['company_id']
            );

            // Produto simples
            if ($item['variant'] === '' || is_null($item['variant'])) {
                $product = $this->model_products->getProductBySkuAndStore($item['sku'], $data_principal_store['id']);

                if (!$product) {
                    $this->db->trans_rollback();
                    throw new Exception("Produto $item[sku] não encontrado na loja principal.");
                }

                $update_order_item['product_id'] = $product['id'];
                $update_order_item['name']       = $product['name'];
            }
            // Produto com variação.
            else {
                $variant = $this->model_products->getVariantsBySkuAndStore($item['sku'], $data_principal_store['id']);

                if (!$variant) {
                    $this->db->trans_rollback();
                    throw new Exception("Variação $item[sku] não encontrado na loja principal.");
                }

                $update_order_item['product_id'] = $variant['prd_id'];
                $update_order_item['name']       = $variant['product_name'];
                $update_order_item['variant']    = $variant['variant'];
            }

            $update_order_items[$item['id']] = $update_order_item;
        }

        foreach ($update_order_items as $item_id => $update_order_item) {
            // Reduz estoque do sku da nova loja.
            $this->model_products->reduzEstoque($update_order_item['product_id'], $item_id, $update_order_item['variant'] ?? null, $order_id);

            // Trocar o sku de loja.
            $this->model_orders->updateItenByOrderAndId($item_id, $update_order_item);
        }

        $update_order = array(
            'store_id'                      => $data_principal_store['id'],
            'company_id'                    => $data_principal_store['company_id'],
            'service_charge_rate'           => $data_principal_store['service_charge_value'],
            'service_charge_freight_value'  => $data_principal_store['service_charge_freight_value'],
            'service_charge'                => number_format(($order['total_order'] * $data_principal_store['service_charge_value'] / 100) + ($order['total_ship'] * $data_principal_store['service_charge_freight_value'] / 100), 2, '.', ''),
            'vat_charge'                    => number_format(($order['gross_amount'] - $order['total_ship']) * $order['vat_charge_rate'] / 100, 2, '.', '') //pegar na tabela de empresa - Não está sendo usado.....
        );

        $data_old_store = array(
            'order_id'      => $order_id,
            'company_id'    => $order['company_id'],
            'store_id'      => $order['store_id'],
            'paid_status'   => $order['paid_status'] == 1 ? 96 : 97,
            'new_order'     => 0,
            'updated_at'    => $date_now
        );
        $data_new_store = array(
            'order_id'      => $order_id,
            'company_id'    => $data_principal_store['company_id'],
            'store_id'      => $data_principal_store['id'],
            'paid_status'   => $order['paid_status'],
            'new_order'     => 1,
            'updated_at'    => $date_now
        );

        // Adicionar na fila o status para a loja e adiciona status de cancelamento para a loja anterior.
        $this->model_orders_to_integration->create($data_old_store);
        $this->model_orders_to_integration->create($data_new_store);

        // Salvar histório de mudança de loja.
        $this->model_change_seller_histories->create(array(
            'order_id'          => $order['id'],
            'old_store_id'      => $order['store_id'],
            'new_store_id'      => $data_principal_store['id'],
            'old_company_id'    => $order['company_id'],
            'new_company_id'    => $data_principal_store['company_id'],
            'updated_data'      => json_encode(array(
                'order' => $update_order,
                'items' => $update_order_items
            ), JSON_UNESCAPED_UNICODE)
        ));

        // Trocar pedido de loja e recalcular a comissão.
        $this->model_orders->updateByOrigin($order['id'], $update_order);

        //Buscar todas as campanhas ativas do atual pedido
        $AllIdCampaigns = $this->model_campaigns_v2->getAllCampaignsByOrderId($order_id);

        if($AllIdCampaigns){
            foreach($AllIdCampaigns as $camp){
                $ArrayIdCampaigns[$camp['campaign_id']] = $camp['campaign_id'];
            }
    
            //Chamar a função de clean
            if(!$this->cleanCampaignFulFillment($order_id)){
                $this->db->trans_rollback();
                throw new Exception("Erro ao limpar as campanhas já cadastradas.");
            }
            //Select nos itens do pedido para recalcular as campanhas
            $items = $this->model_orders->getOrdersItemData($order_id);
            foreach($items as $item){
                if(!$this->saveTotalDiscounts($item,$item['id'], $ArrayIdCampaigns)){
                    $this->db->trans_rollback();
                    throw new Exception("Erro ao cadastrar descontos nos produtos.");
                }
            }
        }

        get_instance()->log_data('change_seller', $log_name, "OLD_ORDER=".json_encode($order, JSON_UNESCAPED_UNICODE)."\n\nOLD_ITEMS=".json_encode($items, JSON_UNESCAPED_UNICODE)."\n\nUPDATE=ORDER=".json_encode($update_order)."\n\nUPDATE_ITEMS=".json_encode($update_order_items));
        
        //Commita a transação
        $this->db->trans_commit();
        
    }

    // Função responsável por limpar as campanhas dos pedidos de Multi CD
    public function cleanCampaignFulFillment($orderId){

        $this->load->model('model_campaigns_v2');
        //limpar as tabelas de campanha
        
        //Acerta os descontos nas tabelas Orders e Orders_itens
        if(!$this->model_orders->setReverseDiscountOnOrder($orderId)){
            return false;
        }

        // Limpa as tabelas de campanha, excluindo em campaign_v2_orders_campaigns e campaign_v2_orders_items e limpando os valores em campaign_v2_orders
        if(!$this->model_campaigns_v2->clearCampaignsTablesFromChangeSeller($orderId)){
            return false;
        }

        return true;

    }

     /**
     * Update do status do pedido de troca com intuito de enviar para o status correto
     *
     * @param int $order_id Código do pedido no seller center
     * @param bool $orderMkt Código do pedido marketplace
     * @param int $paid_status status da order
     * @return bool                 Retorno o status do cancelamento
     */
    public function updateOrderTroca(int $order_id, string $orderMkt, int $paid_status): bool
    {

        // verifica se existe a palavra troca na pedido Marktplace
        if ($orderMkt && (stripos($orderMkt, 'troca') !== false)) {

            switch ($paid_status) {
                case 51:
                    $status = 53;
                    break;
                case 52:
                    $status = 50;
                    break;
                case 55:
                    $status = 5;
                    break;
                case 60:
                    $status = 6;
                    break;
                case 99:
                    $status = 99;
                    break;                      
                default:
                    return '';
            }

            //atualiza o pedido de troca
            if($status){
                if($status == 99){
                    $this->cancelOrder($order_id, false);
                }else{
                    $this->model_orders->updatePaidStatus($order_id, $status);   
                }
                return true;
            }else{
                return false;
            }    
        }else{
            return false;
        }
    }

    public function formatsendDataWebhook($store_id_wh,$typeIntegration,$order_id,$arrayData){
           
        $this->load->model('model_settings');
        $this->load->model('model_stores');
        $this->load->model('model_settings');
        $this->load->model('model_api_integrations');
        $this->load->model('model_users');

        //return para não quebrar caso o internal_api_url não esteja setado
        $urlApi = $this->model_settings->getSettingDatabyName('internal_api_url');
        if (!$urlApi) {
            return true;
        }

        $this->url_conectala = $urlApi['value'];
      
        $urlOrder = $this->url_conectala . "Api/V1/Orders/".$order_id;
     
        //Chamadas para criar o options
        $store = $this->model_stores->getStoresData($store_id_wh);  
    
        $integration = $this->model_api_integrations->getDataByStore($store['id']);
        $integration = $integration[0];
        $user = $this->model_users->getUserData($integration['user_id']);
        if ($user['store_id'] != $store['id']) {
            $storeUsers = $this->model_users->getUsersByStore($store['id']);
            foreach ($storeUsers as $storeUser) {
                if (((int)$storeUser['active']) === 1) {
                    $user = $storeUser;
                    break;
                }
            }
        }
        
        $options['headers']['x-user-email'] = $user['email'];
        $options['headers']['x-api-key'] = $store["token_api"];
        $options['headers']['x-store-key'] = $store['id'];
        $options['headers']['Content-Type'] = 'application/json;';
        $options['headers']['Accept'] = 'application/json';
                
        $client = new Client([
            'verify' => false
        ]);
        
       
        try {
            $request = $client->request('GET', $urlOrder,$options);
        }catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
            $error = $exception->getMessage();
                        
            $log_name = __CLASS__ . '/' . __FUNCTION__;
            get_instance()->log_data('orders', $log_name, "Erro ao tentar pegar o getOrders da api - ($error)", 'E');
            
            return true;
        }
        
        $bodyOrder = Utils::jsonDecode($request->getBody()->getContents());
        
        $this->ordersmarketplace->sendDataWebhook($store_id_wh,$typeIntegration,$bodyOrder);


    }


     // Função responsável por enviar os dados para as url callback
    public function sendDataWebhook($storeIdIntegration, $typeIntegration, $bodyOrder){
    
        $this->load->model('model_integrations_webhook');
  
        $body = [
            'order' => $bodyOrder
        ];
          
        $returnModelWebhook = $this->model_integrations_webhook->getDataToSend($storeIdIntegration);
          
          if($returnModelWebhook){
              foreach ($returnModelWebhook as $data){
  
                  $types = explode(";", $data['type_webhook']);
  
                      $url = $data['url'];
  
                      if(in_array($typeIntegration, $types)){
                        $client = new Client([
                            'verify' => false
                        ]);
                     
                        try {
                            $response = $client->request('POST', $url, ['json' => $body]);      
                        } catch (ClientException | InvalidArgumentException | GuzzleException $exception) {
                            $error = $exception->getMessage();
                       
                            $log_name = __CLASS__ . '/' . __FUNCTION__;
                            get_instance()->log_data('orders', $log_name, "Erro ao enviar o pedido na url ($url) - ($error)", 'E');
                        }
                    }
              }
          }
    
          return true;
      }
      
    public function setNewComissionItemOrder(string $int_to, int $order_id)
    {
        $integration = $this->model_integrations->getIntegrationByIntTo($int_to, 0);

        if (!$integration) {
            return;
        }

        $mkt_type = $integration['mkt_type'];

        if ($mkt_type !== 'vtex') {
            return;
        }

        if (!$this->model_orders_to_process_commission->getByOrder($order_id)) {
            $this->model_orders_to_process_commission->create(array(
                'order_id' => $order_id
            ));
        }
    }

    public function getCancelReasonDefault(): ?string
    {
        $setting_change_rules_cancellation_status = $this->model_settings->getValueIfAtiveByName('change_rules_cancellation_status');
        if (!$setting_change_rules_cancellation_status) {
            return '1-Seller';
        }
        $attribute = $this->model_attributes->getAttributeDefaultByAttributeName('cancel_penalty_to');
        return $attribute['value'] ?? '1-Seller';
    }

    public function getCancelReasonWithoutCommission(): ?string
    {
        $attribute = $this->model_attributes->getAttributeWithoutCommission('cancel_penalty_to');
        return $attribute['value'] ?? '0-Sem penalidade';
    }

    public function checkComissionOrdersItem($orderID = null, $productId = null): ?array
    {

        $this->load->model('model_campaign_v2_orders_items');

        // Descobre a comissão do Item do Pedido / o Id do item estornado / desconto do item estornado
        $commission = 0;
        $arrayRetorno['commission'] = 0;
        $arrayRetorno['total_channel'] = 0;

        if(!$orderID || !$productId){
            return $arrayRetorno;    
        }

        $arrayCommission = $this->model_commissioning_orders_items->getCommissionByOrderAndProduct($orderID, $productId);

        if($arrayCommission){
            $arrayRetorno['commission'] = $arrayCommission['comission'];
        }else{
            $arrayCommissionOrder = $this->model_orders->getOrdersData(0,$orderID);
            if($arrayCommissionOrder){
                $arrayRetorno['commission'] = $arrayCommissionOrder['service_charge_rate'];
            }
        }

        //Busca ID para descobrir o desconto
        $idItem = $this->model_orders->getOrdersItemDataByOrderIdProductId($orderID,$productId);

        $campanhaEnvolvida = $this->model_campaign_v2_orders_items->getAllItemCampaignsByOrderItemId($idItem['id']);

        if($campanhaEnvolvida){
            $arrayRetorno['total_channel'] = roundDecimal($campanhaEnvolvida['channel_discount']/$idItem['qty']);
        }

        return $arrayRetorno;

     }


}