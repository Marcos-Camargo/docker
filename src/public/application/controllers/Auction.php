<?php
/*
SW Serviços de Informática 2019

Controller de Fornecedores

 */
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . "libraries/Microservices/v1/Logistic/Shipping.php";

use Microservices\v1\Logistic\Shipping;

/**
 * @property CI_Loader $load
 * @property CI_Output $output
 *
 * @property Model_auction $model_auction
 * @property Model_integration_logistic $model_integration_logistic
 * @property Model_integrations $model_integrations
 * @property Model_stores $model_stores
 *
 * @property Shipping $ms_shipping
 */

class Auction extends Admin_Controller
{
	public function __construct()
	{
  		parent::__construct();

        $this->not_logged_in();

		$this->data['page_title'] = $this->lang->line('application_manage rules');
		$this->load->model('model_auction');
		$this->load->model('model_integration_logistic');
		$this->load->model('model_integrations');
		$this->load->model('model_stores');
        $this->load->library("Microservices\\v1\\Logistic\\Shipping", array(), 'ms_shipping');
	}

    public function introduction()
    {
        if (!empty($this->model_auction->getFetchFileProcessData())) {
            redirect('auction/addRulesAuction');
        }

        $this->data['page_now'] = 'manage rules';
        $this->render_template('auction/introduction', $this->data);
    }

	public function addRulesAuction()
	{
		$this->data['rules']    = $this->model_auction->statusAuction();
		$this->data['mkt']      = $this->model_integrations->getIntegrationsbyStoreId(0);
        $this->data['page_now'] = 'manage rules';

		$this->render_template('auction/add_rules_auction', $this->data);	
	}

    public function getAuctionInMicroserviceByMarketplace(): array
    {
        $rules = [];

        foreach ($this->model_integrations->getIntegrationsbyStoreId(0) as $integration) {
            $rule = $this->ms_shipping->getRuleAuction($integration['int_to']);
            if ($rule) {
                $rules[] = $rule;
            }
        }

        return array(
            'data'              => $rules,
            'recordsFiltered'   => count($rules),
            'recordsTotal'      => count($rules)
        );
    }

    public function fetchData(): CI_Output
    {
        try {
            $draw           = $this->postClean('draw');
            $result         = array();
            $filter_default = array('where' => array('integrations.store_id' => 0));
            $fields_order   = array('id', 'mkt_id', 'rules_seller_conditions_status_id', '');

            if ($this->ms_shipping->use_ms_shipping) {
                $rules_name = $this->ms_shipping->getAuctionTypes();
                $data = $this->getAuctionInMicroserviceByMarketplace();
            } else {
                $data = $this->fetchDataTable('model_auction', 'getFetchFileProcessData', [], [], $fields_order, $filter_default);
            }
        } catch (Exception $exception) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([]));
        }

        foreach ($data['data'] as $key => $value) {
            $rules_seller_conditions_status_id = $value['rules_seller_conditions_status_id'] ?? $value['rule'];
            $mkt_id = $value['mkt_id'] ?? 0;
            $id = $value['id'];
            $marketplace_name = $value['marketplace_name'] ?? '';

            if (empty($mkt_id) || empty($marketplace_name)) {
                $integration = $this->model_integrations->getIntegrationByIntTo($value['marketplace'], 0);
                if ($integration) {
                    $mkt_id = $integration['id'];
                    $marketplace_name = $integration['name'];
                }
            }

            $result[$key] = array(
                $id,
                $marketplace_name,
                $value['rule_name'] ?? getArrayByValueIn($rules_name ?? [], $rules_seller_conditions_status_id, 'id')['descricao'],
                "<button type='button' class='btn btn-outline-primary btn-sm update-rule' data-rule-id='$id' data-rule-status='$rules_seller_conditions_status_id' data-rule-mkt='$mkt_id'><i class='fa fa-edit'></i> Alterar Regra</button>"
                 //."<button class='btn btn-link remove-rule' data-rule-id='$id'><i class='fa fa-trash '></i></button>"
            );
        }

        $output = array(
            "draw"              => $draw,
            "recordsTotal"      => $data['recordsTotal'],
            "recordsFiltered"   => $data['recordsFiltered'],
            "data"              => $result,
        );

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($output));
    }

	public function saveRules(): CI_Output
    {
        $rule_id     = $this->postClean('rule_id');
        $rules       = $this->postClean('rules');
        $marketplace = $this->postClean('marketplace');

        if (empty($rule_id)) {
            if (!empty($this->model_auction->getRuleAuction(0, $marketplace))) {
                return $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(array('success' => false, 'message' => 'Regra já existente para o marketplace.')));
            }

            $this->model_auction->addRuleAuction(array(
                'store_id' => 0,
                'rules_seller_conditions_status_id' => $rules,
                'mkt_id' => $marketplace
            ));
        } else {
            $this->model_auction->updateRuleAuction(array(
                'rules_seller_conditions_status_id' => $rules
            ), $rule_id, $marketplace);
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array('success' => true, 'message' => 'Regra atualizada.')));
	}

    public function removerRule(): CI_Output
    {
        $rule_id = $this->postClean('rule_id');

        $this->model_auction->removeRuleAuction($rule_id);

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array('success' => true, 'message' => 'Regra excluída.')));
    }
}
