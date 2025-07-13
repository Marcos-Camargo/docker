<?php

use App\Libraries\Enum\CampaignTypeEnum;
use App\Libraries\Enum\DiscountTypeEnum;

defined('BASEPATH') or exit('No direct script access allowed');

class CheckCommissioningChanges
{
    protected $_CI;

    public function __construct()
    {
        $this->_CI = &get_instance();
        $this->_CI->load->model('model_settings');
        $this->_CI->load->model('model_commissionings');
        $this->_CI->load->model('model_commissioning_brands');
        $this->_CI->load->model('model_commissioning_products');
        $this->_CI->load->model('model_commissioning_stores');
        $this->_CI->load->model('model_commissioning_trade_policies');
        $this->_CI->load->model('model_commissioning_categories');
        $this->_CI->load->model('model_commissioning_logs');
        $this->_CI->load->model('model_campaigns_v2_products');

    }

    public function getChangedCommissionings($currentDateTime) {

        $this->_CI->db->from('commissionings');

        // Sempre trazer registros com updated_at > last_checked_at
        $this->_CI->db->group_start()
            ->where('updated_at > last_checked_at') // Alterações recentes
            ->or_where('last_checked_at IS NULL')  // Nunca verificados
            ->group_end();

        // Condições adicionais para vigência e expiração
        $this->_CI->db->group_start()
            ->where('end_date >=', $currentDateTime) // Em vigência ou agendados
            ->or_where('end_date > last_checked_at') // Expirados, mas não verificados
            ->or_where('last_checked_at < end_date') // Última verificação antes de expirar
            ->or_where('last_checked_at IS NULL')   // Nunca verificados
            ->group_end();

        return $this->_CI->db->get()->result_array();
    }

    protected function processCommissioningsByCampaigns(array $campaignProducts, bool $printResult = false)
    {

        //Verificando agora se o percentual deve realizar uma aprovação automática ou remoção do produto
        $max_percentual_auto_approve_products_campaign = $this->_CI->model_settings->getValueIfAtiveByName('max_percentual_auto_approve_products_campaign');
        $min_percentual_auto_repprove_products_campaign = $this->_CI->model_settings->getValueIfAtiveByName('min_percentual_auto_repprove_products_campaign');

        if ($printResult) {
            echo "------------Configurações Iniciais------------".PHP_EOL;
            echo "max_percentual_auto_approve_products_campaign = $max_percentual_auto_approve_products_campaign".PHP_EOL;
            echo "min_percentual_auto_repprove_products_campaign = $min_percentual_auto_repprove_products_campaign".PHP_EOL;
            echo "----------------------------------------------".PHP_EOL;
        }

        foreach ($campaignProducts as $campaignProduct) {

            if ($printResult){
                echo "Analisando campanha {$campaignProduct['id']}, produto {$campaignProduct['product_id']}".PHP_EOL;
            }

            //Buscar comissionamento atual do produto
            $comission = $this->_CI->model_commissionings->getComissionProduct(
                $campaignProduct,
                $campaignProduct['product_id'],
                $campaignProduct['start_date'],
                $campaignProduct['end_date'],
                $campaignProduct['int_to']
            );

            //Mudou, vamos recalcular e executar a ação necessária
            $campaignProduct['percentual_commision'] = $comission['comission'];
            $campaignProduct['commision_hierarchy'] = $comission['hierarchy'];

            $comission_value = ($campaignProduct['product_price'] * $campaignProduct['percentual_commision']) / 100;

            $marketplace_discount_value = 0;
            /**
             * desconto percentual compartilhado
             * desconto percentual marketplace
             */
            if ($campaignProduct['marketplace_discount_percentual']) {

                $marketplace_discount_value = ($campaignProduct['product_price'] * $campaignProduct['marketplace_discount_percentual']) / 100;

                if ($printResult){
                    echo "É desconto percentual de {$campaignProduct['marketplace_discount_percentual']}".PHP_EOL;
                }

                //Desconto fixo custeado pelo canal
            } elseif ($campaignProduct['campaign_type'] == CampaignTypeEnum::CHANNEL_FUNDED_DISCOUNT
                && $campaignProduct['discount_type'] == DiscountTypeEnum::FIXED_DISCOUNT) {

                $marketplace_discount_value = $campaignProduct['fixed_discount'];

                if ($printResult){
                    echo "É desconto fixo marketplace de $marketplace_discount_value".PHP_EOL;
                }

                /**
                 * desconto fixo compartilhado
                 */
            } elseif ($campaignProduct['campaign_type'] == CampaignTypeEnum::SHARED_DISCOUNT
                && $campaignProduct['marketplace_discount_fixed']) {
                $marketplace_discount_value = $campaignProduct['marketplace_discount_fixed'];
                if ($printResult){
                    echo "É desconto fixo compartilhado de $marketplace_discount_value".PHP_EOL;
                }
            }

            $proportion_from_comission = ($marketplace_discount_value / $comission_value) * 100;
            $campaignProduct['percentual_from_commision'] = $proportion_from_comission;

            //Por padrão, fica em aprovação
            $campaignProduct['approved'] = 0;

            //A proporção é inferior ou igual ao minimo para auto aprovação, vamos marcar como aprovado
            if ($proportion_from_comission <= $max_percentual_auto_approve_products_campaign) {
                if ($printResult){
                    echo "Proporção da comissão $proportion_from_comission é inferior ao máximo $max_percentual_auto_approve_products_campaign".PHP_EOL;
                }
                $campaignProduct['approved'] = 1;
            }

            $changes = [
                'approved' => $campaignProduct['approved'],
                'percentual_from_commision' => $campaignProduct['percentual_from_commision'],
                'percentual_commision' => $campaignProduct['percentual_commision'],
                'commision_hierarchy' => $campaignProduct['commision_hierarchy'],
                'removed' => 0,
                'auto_removed' => 0,
                'removed_description' => '',
            ];

            //Se é reprovado automaticamente, parar por aqui e não fazer mais nada
            if ($proportion_from_comission >= $min_percentual_auto_repprove_products_campaign) {
                if ($printResult){
                    echo "Proporção da comissão $proportion_from_comission é superior ao mínimo $min_percentual_auto_repprove_products_campaign".PHP_EOL;
                }
                $changes['approved'] = 0;
                $changes['removed'] = 1;
                $changes['auto_removed'] = 1;
                $changes['removed_description'] = "$proportion_from_comission% da comissão de {$comission['comission']}% da comissão no período.";
            }

            $this->_CI->model_campaigns_v2_products->update($changes, $campaignProduct['campaign_product_id']);

            if (!$campaignProducts){
                echo "Alteração executada: produto {$campaignProduct['campaign_product_id']}, 
                approved: {$campaignProduct['approved']}, 
                percentual_from_commision: {$campaignProduct['percentual_from_commision']}, 
                percentual_commision: {$campaignProduct['percentual_commision']}, 
                commision_hierarchy: {$campaignProduct['commision_hierarchy']}, 
                removed: {$campaignProduct['removed']}, 
                removed_description: {$campaignProduct['removed_description']}".PHP_EOL;
            }

            if ($printResult){
                echo "Produto {$campaignProduct['product_id']} 
                ficou: approved: {$changes['approved']},
                removed: {$changes['removed']},
                auto_removed: {$changes['auto_removed']},
                commision_hierarchy: {$changes['commision_hierarchy']},
                percentual_commision: {$changes['percentual_commision']},
                percentual_from_commision: {$changes['percentual_from_commision']}".PHP_EOL;
                echo "------------FIM DO PRODUTO--------------".PHP_EOL;
            }

        }

    }

    public function processCommissionings(array $campaignIds = []) {

        if (!$this->_CI->model_settings->getValueIfAtiveByName('allow_hierarchy_comission')){
            if (!$campaignIds){
                echo "Parametro allow_hierarchy_comission não ativado".PHP_EOL;
            }
            return;
        }

        //Verificando agora se o percentual deve realizar uma aprovação automática ou remoção do produto
        $max_percentual_auto_approve_products_campaign = $this->_CI->model_settings->getValueIfAtiveByName('max_percentual_auto_approve_products_campaign');
        $min_percentual_auto_repprove_products_campaign = $this->_CI->model_settings->getValueIfAtiveByName('min_percentual_auto_repprove_products_campaign');

        if (!($max_percentual_auto_approve_products_campaign && $min_percentual_auto_repprove_products_campaign)){
            if (!$campaignIds){
                echo "Parametros max_percentual_auto_approve_products_campaign e min_percentual_auto_repprove_products_campaign não configurados".PHP_EOL;
            }
            return;
        }

        $currentDateTime = date('Y-m-d H:i:s');

        $changedCommissionings = $this->getChangedCommissionings($currentDateTime);

        //Roda só uma vez tds produtos todas campanhas
        if (!$changedCommissionings && !$campaignIds) {
            echo "Nenhum comissionamento para verificar".PHP_EOL;
            return;
        }

        $campaignProducts = $this->_CI->model_campaigns_v2_products->getAllProductsActiveCampaigns(
            $currentDateTime,
            $campaignIds
        );

        if (!$campaignProducts) {
            if (!$campaignIds){
                echo "Nenhum produto em campanha vigente para verificar".PHP_EOL;
            }
            return;
        }

        $this->_CI->db->trans_begin();

        $this->processCommissioningsByCampaigns($campaignProducts, $campaignIds ? false : true);

        //Acabou de validar os produtos, vamos marcar todos os itens que vieram na lista como verificado
        foreach ($changedCommissionings as $commissioning) {
            $this->_CI->db->where('id', $commissioning['id']);
            $this->_CI->db->update('commissionings', ['last_checked_at' => $currentDateTime]);
        }

        $this->_CI->db->trans_commit();

    }

}