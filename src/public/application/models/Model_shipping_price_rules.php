<?php
/*
Model de acesso ao BD para as tabelas shipping_price_rules e shipping_price_history.
*/

class Model_shipping_price_rules extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->dbReadOnly = $this->load->database('readonly', true);
    }

    public function getAllShippingPriceRules()
    {
        $sql = 'SELECT * FROM shipping_pricing_rules';
        $query = $this->dbReadOnly->query($sql);
        return $query->result_array();
    }

    public function getActiveShippingPriceRules()
    {
        $sql = 'SELECT * FROM shipping_pricing_rules WHERE active = 1';
        $query = $this->dbReadOnly->query($sql);
        return $query->result_array();
    }

    public function searchShippingAndIntegration(array $integration_id, array $shipping_id)
    {
        // $shipping = "";
        $shipping = array();
        // $shipping_integration = "";
        $shipping_integration = array();

        $aux_shipping = explode(",", implode(",", $shipping_id));
        foreach($aux_shipping as $aux_shipping_value) {
            if (strpos($aux_shipping_value, '100000') === false) {
                $shipping[] = $aux_shipping_value;
            } else {
                $shipping_integration[] = str_replace('100000', '', $aux_shipping_value);
            }
        }

        if (empty($shipping)) {
            $shipping[] = "''";
        }

        if (empty($shipping_integration)) {
            $shipping_integration[] = "''";
        }

        $integration = explode(",", $integration_id[0]);

        $sql = "SELECT group_concat(concat(`name`) SEPARATOR ';') AS integrations_res,
                    (SELECT group_concat(concat(`name`) SEPARATOR ';')
                    FROM shipping_company sc
                    WHERE sc.freight_seller = 0 AND sc.active = 1 AND sc.id IN ?
                    ORDER BY sc.id ASC) AS shipping_company_res,
                    (SELECT group_concat(concat(ig.description) SEPARATOR ';')
                    FROM integration_logistic il
                    LEFT JOIN integrations_logistic ig
                    ON il.id_integration = ig.id 
                    WHERE il.store_id = 0 AND il.active = 1 AND ig.id IN ?
                    ORDER BY ig.id ASC) AS shipping_integration_res
                FROM integrations it
                WHERE it.active = 1 AND it.id IN ?
                ORDER BY it.id ASC";

        $query = $this->dbReadOnly->query($sql, array($shipping, $shipping_integration, $integration));
        // echo '<script>console.log(' . print_r($this->dbReadOnly->queries) . ')</script>';
        return $query->result_array();
    }

    public function getIntegrations()
    {
        $sql = 'SELECT id, name 
                FROM integrations 
                WHERE active = 1 AND store_id = 0
                ORDER BY id ASC';

        $query = $this->dbReadOnly->query($sql);
        return $query->result_array();
    }

    public function getShippingCompanies()
    {
        $sql = 'SELECT id, name 
                FROM shipping_company 
                WHERE freight_seller = 0 AND active = 1 
                ORDER BY id ASC';

        $query = $this->dbReadOnly->query($sql);
        return $query->result_array();
    }

    public function getShippingIntegrations()
    {
        $sql = 'SELECT il.id_integration AS id, ig.description AS name
                FROM integration_logistic il
                LEFT JOIN integrations_logistic ig
                ON il.id_integration = ig.id 
                WHERE il.store_id = 0 AND il.active = 1
                ORDER BY ig.id ASC';

        $query = $this->dbReadOnly->query($sql);
        return $query->result_array();
    }

    public function loadRule($id)
    {
        if ($id != '[NA]') {
            $sql = 'SELECT *, 
                        (SELECT group_concat(concat(`id`, ",", `name`) separator ";")
                        FROM integrations 
                        WHERE active = 1 AND store_id = 0
                        ORDER BY id ASC) AS integrations_res,
                        (SELECT group_concat(concat(`id`, ",", `name`) separator ";")
                        FROM shipping_company
                        WHERE freight_seller = 0 AND active = 1 
                        ORDER BY id ASC) AS shipping_company_res 
                    FROM shipping_pricing_rules 
                    WHERE id = ' . $id;
        } else {
            $sql = 'SELECT group_concat(concat(`id`, ",", `name`) separator ";") AS integrations_res,
                        (SELECT group_concat(concat(`id`, ",", `name`) separator ";")
                        FROM shipping_company
                        WHERE freight_seller = 0 AND active = 1 
                        ORDER BY id ASC) AS shipping_company_res
                    FROM integrations 
                    WHERE active = 1 AND store_id = 0
                    ORDER BY id ASC';
        }

        $query = $this->dbReadOnly->query($sql);
        return $query->result_array();
    }

    public function toggleStatus(int $id)
    {
        $datetime_now = date("Y-m-d H:i:s");
        $status_value = 0;
        $enable_disable = 'disabled';
        $current_row = $this->db->where('id', $id)->get('shipping_pricing_rules')->row_array();
        if ($current_row['active'] == '0') {
            $status_value = 1;
            $enable_disable = 'enabled';
        }

        $update_array = [
            "active" => $status_value,
            "date_$enable_disable" => $datetime_now
        ];

        $result = false;
        $this->db->where('id', $id);
        if ($this->db->update('shipping_pricing_rules', $update_array)) {
            $result = true;
        }

        return $result;
    }

    public function deleteRule(int $id)
    {
        $query = $this->db->get_where('shipping_pricing_rules', array('id' => $id));

        $deleted_rule = array();
        foreach ($query->result() as $row) {
            $deleted_rule["shipping_pricing_rules_id"] = $id;
            $deleted_rule["shipping_pricing_range"] = $row->price_range;
            $deleted_rule["shipping_company_ids"] = $row->table_shipping_ids;
            $deleted_rule["action_name"] = 'delete';
            $deleted_rule["log_date"] = date("Y-m-d H:i:s");
        }

        $result = false;
        if ($this->db->insert('shipping_pricing_history', $deleted_rule)) {
            if ($this->db->delete('shipping_pricing_rules', array('id' => $id))) {
                $result = true;
            }
        }

        return $result;
    }

    public function saveRules(array $rule)
    {
        $set_rule = array();
        if (isset($rule['table_shipping_ids']) && ($rule['table_shipping_ids'] != -1)) {
            $set_rule['table_shipping_ids'] = $rule['table_shipping_ids'];
        }

        if (isset($rule['mkt_channels_ids']) && ($rule['mkt_channels_ids'] != -1)) {
            $set_rule['mkt_channels_ids'] = $rule['mkt_channels_ids'];
        }

        if (isset($rule['price_range']) && ($rule['price_range'] != -1)) {
            $set_rule['price_range'] = $rule['price_range'];
        }

        if (isset($set_rule['table_shipping_ids']) && isset($set_rule['mkt_channels_ids'])) {
            $shipping_integration = $this->searchShippingAndIntegration(
                array(str_replace(";", ",", $set_rule['mkt_channels_ids'])), 
                array(str_replace(";", ",", $set_rule['table_shipping_ids']))
            );

            $aux_shipping = "";
            if (!empty($shipping_integration[0]['shipping_company_res'])) {
                $aux_shipping = $shipping_integration[0]['shipping_company_res'];
            }

            if (!empty($shipping_integration[0]['shipping_integration_res'])) {
                if (!empty($aux_shipping)) {
                    $aux_shipping = $aux_shipping . ";" . $shipping_integration[0]['shipping_integration_res'];
                } else {
                    $aux_shipping = $shipping_integration[0]['shipping_integration_res'];
                }
            }
            $set_rule['shipping_companies'] = $aux_shipping;

            $set_rule['mkt_channels'] = $shipping_integration[0]['integrations_res'];
        }

        $set_rule['date_created'] = date("Y-m-d H:i:s");
        $set_rule['active'] = 1;

        $created_rule = array();
        $result = false;
        if (isset($rule['id']) && ($rule['id'] == -1)) {
            if ($this->db->insert('shipping_pricing_rules', $set_rule)) {
                $query = $this->db->get_where('shipping_pricing_rules', $set_rule);

                foreach ($query->result() as $row) {
                    $created_rule["shipping_pricing_rules_id"] = $row->id;
                    $created_rule["shipping_pricing_range"] = $row->price_range;
                    $created_rule["shipping_company_ids"] = $row->table_shipping_ids;
                    $created_rule["action_name"] = 'create';
                    $created_rule["log_date"] = date("Y-m-d H:i:s");
                }
        
                $result = false;
                if ($this->db->insert('shipping_pricing_history', $created_rule)) {
                    $result = true;
                }
            }
        } else if (isset($rule['id']) && ($rule['id'] > -1)) {
            $this->db->where('id', $rule['id']);
            if ($this->db->update('shipping_pricing_rules', $set_rule)) {
                $query = $this->db->get_where('shipping_pricing_rules', $set_rule);

                foreach ($query->result() as $row) {
                    $created_rule["shipping_pricing_rules_id"] = $row->id;
                    $created_rule["shipping_pricing_range"] = $row->price_range;
                    $created_rule["shipping_company_ids"] = $row->table_shipping_ids;
                    $created_rule["action_name"] = 'update';
                    $created_rule["log_date"] = date("Y-m-d H:i:s");
                }

                $result = false;
                if ($this->db->insert('shipping_pricing_history', $created_rule)) {
                    $result = true;
                }
            }
        }

        return $result;
    }

    public function getShippingData(array $quote_data)
    {
        $logistic_integration = $quote_data['data']['logistic']['type'];
        // $logistic_integration_cnpj = $quote_data['data']['logistic']['cnpj'];
        $marketplace = $quote_data['data']['marketplace'];
        $table_name = $quote_data['table_name'];

        $counter = 0;
        $result = false;

        $shipping_id = null;
        $is_integration = false;

        // Integradora.
        if ($quote_data['data']['logistic']['type'] !== false) {
            $is_integration = true;
            $shipping_id = $quote_data['data']['logistic']['shipping_id'];
        }

        $data_marketplace = $this->dbReadOnly->select('id')->where([
            'int_to' => $marketplace, 
            'store_id' => 0
        ])->get('integrations')->row_object();

        foreach($quote_data['data']['services'] as $current_product) {
            reset($quote_data['data']['skus']);
            $skumkt = key($quote_data['data']['skus']);

            if (
                !isset($quote_data['data']['skus'][$skumkt]['store_id']) ||
                !isset($quote_data['data']['skus'][$skumkt]['list_price']) ||
                !isset($data_marketplace) ||
                !isset($data_marketplace->id)
            ) {
                $result = $quote_data;
                break;
            }

            $store_id = $quote_data['data']['skus'][$skumkt]['store_id'];
            $list_price = $quote_data['data']['skus'][$skumkt]['list_price'];
            $marketplace_id = $data_marketplace->id;

            $result[$counter]['shipping_company_id']            = null;
            $result[$counter]['shipping_integration_id']        = null;

            // É uma transportadora.
            if ($is_integration === false) {
                $result[$counter]['shipping_company_id']        = $current_product['shipping_id'];

            // É uma integradora.
            } else {
                $result[$counter]['shipping_integration_id']    = $shipping_id;
            }

            $result[$counter]['product_price']                  = $list_price;
            $result[$counter]['skumkt']                         = $skumkt;
            $result[$counter]['marketplace_id']                 = $marketplace_id;

            $counter += 1;
        }

        return $result;
    }
}