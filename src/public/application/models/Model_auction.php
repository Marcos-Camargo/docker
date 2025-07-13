<?php 
/*
SW Serviços de Informática 2019

Model de Acesso ao BD para Atributos

*/

require_once APPPATH . "libraries/Microservices/v1/Logistic/Shipping.php";
/**
 * Class Model_auction
 * @property CI_Loader $load
 * @property CI_DB_query_builder $db
 * @property \Microservices\v1\Logistic\Shipping $ms_shipping
 * @property \Model_integrations $model_integrations
 */
class Model_auction extends CI_Model
{
    public function __construct() {
		parent::__construct();

        $this->load->library("Microservices\\v1\\Logistic\\Shipping", [], 'ms_shipping');
        $this->load->model('model_integrations');
    }
    
    public function getHasProviderLogistic($sellerId) {

        $sql = "select * from providers_to_seller where seller_id =".$sellerId;

        $sql1 = "select * from integration_logistic where store_id =".$sellerId;
        
        $query = $this->db->query($sql);
        $query1 = $this->db->query($sql1);

        return count($query->result_array()) || count($query1->result_array());
    }

    public function statusAuction() {
        if ($this->ms_shipping->use_ms_shipping) {
            return $this->ms_shipping->getAuctionTypes();
        }
        $query = $this->db->query("select * from rules_seller_conditions_status");

        return $query->result_array();
    }

    public function getRuleAuction($id, $mkt = null) 
    {
        if ($this->ms_shipping->use_ms_shipping) {
            $int_to = $this->model_integrations->getIntegrationsData($mkt)['int_to'] ?? '';
            $msRuleAction = $this->ms_shipping->getRuleAuction($int_to, $id);
            if(!empty($msRuleAction)) {
                return [
                    $msRuleAction
                ];
            }
        }
        $sql = "select * from rules_seller_conditions where store_id = ". $id." AND mkt_id=".$mkt;
        if(is_null($mkt)) {
            $sql = "select * from rules_seller_conditions where store_id = ". $id." ;";
        }

        
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function configRule($params)
    {
        $param = [
            'store_id' => $params['seller'],
            'mkt_id' => $params['mkt'],
            'rules_seller_conditions_status_id' => $params['rules']
        ];
        
        if (empty($params['status'])) {
            return $this->addRuleAuction($param);
        } 

        return $this->updateRuleAuction($param, $params['status']);
    }

    public function addRuleAuction($rows)
    {
        if ($this->ms_shipping->use_ms_shipping) {
            $result = $this->ms_shipping->saveRuleAuction(array_merge($rows, ['marketplace' => $rows['mkt_id']]));
            if (!$this->ms_shipping->use_ms_shipping_replica) {
                return $result;
            }
            return $this->db->insert('rules_seller_conditions', $rows) ? $this->db->insert_id() : false;
        }
        return $this->db->insert('rules_seller_conditions', $rows) ? $this->db->insert_id() : false;
    }

    public function updateRuleAuction($params, $id, $marketplace = null)
    {
        if ($this->ms_shipping->use_ms_shipping) {
            $result = $this->ms_shipping->saveRuleAuction(array_merge($params, ['id' => $id, 'marketplace' => $marketplace]));
            if (!$this->ms_shipping->use_ms_shipping_replica) {
                return $result;
            }
            return $this->db->where('id', $id)->update('rules_seller_conditions', $params);
        }
        return $this->db->where('id', $id)->update('rules_seller_conditions', $params);
    }

    public function searchTableShipping($params)
    {
        $sql = "select ts.* , pr.name 
        from  table_shipping ts 
        join  providers_to_seller p on  p.idproviders_to_seller  = ts.idproviders_to_seller 
        join  providers pr on  pr.id  = p.provider_id 
        where p.store_id = ".$params['store_id'];

        $query = $this->db->query($sql);
        return $query->result_array();
    }

    /**
     * @param   int|null    $offset
     * @param   int|null    $limit
     * @param   array       $orderby
     * @param   string|null $search_text
     * @param   array       $filters        $filters = ['where' => ['column' => 'value']]
     * @param   bool        $return_count
     * @return  array|array[]|int
     */
    public function getFetchFileProcessData(?int $offset = 0, ?int $limit = 200, array $orderby = array(), string $search_text = null, array $filters = [], bool $return_count = false)
    {
        if (!empty($search_text) && strlen($search_text) >= 2) {
            $this->db->group_start();
            $this->db->or_like(
                [
                    'rules_seller_conditions.id'                => $search_text,
                    'integrations.name'                         => $search_text,
                    'rules_seller_conditions_status.descricao'  => $search_text
                ]
            );
            $this->db->group_end();
        }

        /**
         *
         * $filters = [
         *  'where' => [
         *      'column' => 'value'
         *  ]
         * ]
         *
         */
        foreach ($filters as $type_filter => $filter) {
            foreach ($filter as $column => $value) {
                $this->db->$type_filter($column, $value);
            }
        }

        $this->db->select("rules_seller_conditions.*, integrations.name as marketplace_name, rules_seller_conditions_status.descricao as rule_name")
            ->join('integrations', "integrations.id = rules_seller_conditions.mkt_id")
            ->join('rules_seller_conditions_status', "rules_seller_conditions_status.id = rules_seller_conditions.rules_seller_conditions_status_id");

        if (!empty($orderby)) {
            $this->db->order_by($orderby[0], $orderby[1]);
        }

        if (!is_null($limit) && !is_null($offset)){
            $this->db->limit($limit, $offset);
        }

        return $return_count ? $this->db->get('rules_seller_conditions')->num_rows() : $this->db->get('rules_seller_conditions')->result_array();
    }

    public function removeRuleAuction(int $id)
    {
        return $this->db->where('id', $id)->delete('rules_seller_conditions');
    }

    public function createRulesSellerConditionsBatch(array $data): bool
    {
        return $data && $this->db->insert_batch('rules_seller_conditions', $data);
    }

}