<?php

namespace Publish;

require_once 'system/libraries/Vendor/autoload.php';

use CI_DB_query_builder;
use CI_Lang;
use CI_Loader;
use CI_Session;
use Model_integrations;
use Model_products;

/**
 * @property CI_DB_query_builder $db
 * @property CI_Loader $load
 * @property CI_Lang $lang
 * @property CI_Session $session
 *
 * @property Model_integrations $model_integrations
 * @property Model_products $model_products
 */

class Publishing
{
    /**
     * Instantiate a new Publishing instance.
     */
    public function __construct()
    {
        $this->load->model(
            array(
                'model_integrations',
                'model_products',
            )
        );
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

    public function setPublish(int $product_id, $intos, $publish, $remove)
    {
        $sem_variants = array('H_ML','H_MLC','H_B2W');
        if (is_null($intos)) {
            $intos=array();
        }
        if (!is_array($intos)){
            return;
        }

        $prd_integrations = $this->model_integrations->getPrdIntegrationHub($product_id); // Leio todas as prd_to_integration deste produto que é HUB
        foreach($prd_integrations as $key => $prd_integration) { // marco todos como não alterados
            $prd_integrations[$key]['alterei'] = false;
        }
        $product = $this->model_products->getProductData(0,$product_id);
        $user_id = (isset($this->session->userdata['id'])) ? $this->session->userdata['id'] : "1";
        foreach($intos as $int_to) { // vejo todos que foram marcados
            if (($product['has_variants'] == '')  || (in_array($int_to, $sem_variants))) {  // não tem variação então vejo com variant null
                $this->createOrChangePublish($prd_integrations, $product, $publish, $user_id, $int_to, null);
            } else { // tem variante, cria ou altera para cada variante
                $variants = $this->model_products->getVariants($product_id);
                foreach ($variants as $variant) {
                    $this->createOrChangePublish($prd_integrations, $product, $publish, $user_id, $int_to, $variant['variant'] );
                }
            }
        }
        // quem não foi alterado, desliga a integração do produto se for marcado para remover.
        if ($remove) {
            foreach($prd_integrations as $prd_integration) {
                if ((!($prd_integration['alterei'])) && ($prd_integration['status'] !== 0)) {
                    $this->model_integrations->changeStatus($prd_integration['int_id'],$product['id'],$product['store_id'], 0, $user_id);
                    get_instance()->log_data(__CLASS__, 'ChangeStatus','Integracao do produto '.$product['id'].' loja '.$product['store_id'].' alterado para 0 com '.$prd_integration['int_to'], "I");
                }
            }
        }
    }

    private function createOrChangePublish(&$prd_integrations, $product, $publish, $user_id, $int_to, $variant) {

        $achei = false;
        foreach($prd_integrations as $key => $prd_integration) {
            if (($prd_integration['int_to'] == $int_to) && ($prd_integration['variant'] == $variant)) {
                $achei = true;
                if ($prd_integration['status'] !== $publish) { // estava desligado, ligou
                    $prd_integration['status'] = $publish;
                    $prd_integrations[$key]['alterei'] = true;
                    $this->model_integrations->changeStatus($prd_integration['int_id'],$product['id'],$product['store_id'], $publish, $user_id);
                    get_instance()->log_data(__CLASS__, 'ChangeStatus','Integracao do produto '.$product['id'].' loja '.$product['store_id'].' alterado para '.$prd_integration['status'].' com '.$int_to, "I");
                }
            }
        }
        if ((!$achei) && ($publish==1)) { // Se não achei e é para publicar, tenho que criar
            // leio a definição da integração deste marketplace da loja deste produto para pegar o id e se auto aprovo
            $integration = $this->model_integrations->getIntegrationbyStoreIdAndInto($product['store_id'],$int_to);
            if(!$integration){
                $this->session->set_flashdata('error', $this->lang->line('application_integration_dont_defined_to_this_store_and_int_to').' Marketplace '.$int_to.' Loja '.$product['store_id'] );
                return;
            }
            $prd = Array(
                'int_id' 		=> $integration['id'],
                'prd_id' 		=> $product['id'],
                'company_id' 	=> $product['company_id'],
                'store_id' 		=> $product['store_id'],
                'date_last_int' => '',
                'status' 		=> $publish,
                'user_id' 		=> $user_id,
                'status_int' 	=> 1,
                'int_type' 		=> 13,
                'int_to'		=> $int_to ,
                'skubling'		=> null,
                'skumkt'		=> null,
                'variant'		=> $variant,
                'approved'		=> ($integration['auto_approve'] == 1) ? 1 : 3,
            );

            if($integration['auto_approve'] == 1 && empty($prd_integrations['approved_curatorship_at'])){
                $prd['approved_curatorship_at'] = dateNow(TIMEZONE_DEFAULT)->format(DATETIME_INTERNATIONAL);
            }

            $this->model_integrations->setProductToMkt($prd);
            get_instance()->log_data(__CLASS__, 'IntegrationCreate','Integracao do produto '.$product['id'].' loja '.$product['store_id'].' criado para '.$int_to.' json='.json_encode($prd), "I");
        }
    }
}