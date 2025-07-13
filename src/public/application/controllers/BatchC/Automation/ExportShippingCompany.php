<?php

require 'system/libraries/Vendor/autoload.php';

class ExportShippingCompany extends BatchBackground_Controller
{   
    public function __construct()
    {
        parent::__construct();
        $logged_in_sess = array(
            'id' 		=> 1,
            'username'  => 'batch',
            'email'     => 'batch@conectala.com.br',
            'usercomp' 	=> 1,
            'userstore' => 0,
            'logged_in' => TRUE
        );
        $this->session->set_userdata($logged_in_sess);
    }

    public function run($id = null, $params = null)
    {
        /* inicia o job */
        $this->setIdJob($id);
        $log_name = $this->router->fetch_class().'/'.__FUNCTION__;
        if (!$this->gravaInicioJob($this->router->fetch_class(), __FUNCTION__)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
            return ;
        }

        $this->log_data('batch', $log_name, 'start '.trim($id." ".$params), "I");

        $this->processExport();
        
        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }

    private function processExport()
    {
        $proceed = true;
        $offset = 0;

        $query = $this->db->query(
            'SELECT idtype_table_shipping AS id, id_provider, id_type
            FROM type_table_shipping
            ORDER BY idtype_table_shipping ASC');
        $table_type = $query->result_array();

        while ($proceed) {
            $query = $this->db->query(
                'SELECT id, active, name, razao_social, insc_estadual, cnpj, phone, observacao, regiao_entrega, regiao_coleta, tempo_coleta, fluxo_fin, val_credito, ship_min, val_ship_min, qtd_min, val_qtd_min, tipo_pagamento, tipo_produto, tracking_web_site, cubage_factor, ad_valorem, gris, toll, shipping_revenue, freight_calculation_standard, freight_seller, date_created, date_updated, address, addr_num, addr_compl, zipcode, addr_neigh, addr_city, addr_uf, responsible_name, responsible_cpf, responsible_email, bank, agency, account_type, account, responsible_oper_name, responsible_oper_cpf, responsible_oper_email, responsible_finan_name, responsible_finan_cpf, responsible_finan_email
                FROM shipping_company
                ORDER BY id ASC
                LIMIT ?, 50000',
            array($offset));

            $offset += 50000;
            $results = $query->result_array();

            if (empty($results)) {
                $proceed = false;
                break;
            }

            // SHIPPING COMPANIES
            $insert = "SET NAMES utf8; INSERT INTO shipping_companies (id, active, name, corporate_name, state_registration, cnpj, phone, observation, delivery_region, region_collection, collection_time, cash_flow, credit_value, minimum_shipping, minimum_quantity, payment_type, product_type, tracking_website, cubage_factor, ad_valorem, gris, toll, shipping_revenue, freight_seller, type, created_at, updated_at) VALUES "; 
            $first = true;
            $iteration = 0;
            foreach ($results as $result) {
                $iteration += 1;

                $id = $result["id"];
                $active = addslashes($result["active"]);
                $name = addslashes($result["name"]);
                $corporate_name = addslashes($result["razao_social"]);

                $state_registration = null;
                if (!is_null($result["insc_estadual"]) && !empty($result["insc_estadual"])) {
                    $state_registration = addslashes(preg_replace("/[^0-9]/", "", $result["insc_estadual"]));
                }

                $cnpj = addslashes(preg_replace("/[^0-9]/", "", $result["cnpj"]));
                $phone = addslashes(preg_replace("/[^0-9]/", "", $result["phone"]));

                $observation = null;
                if (!is_null($result["observacao"]) && !empty($result["observacao"])) {
                    $observation = addslashes($result["observacao"]);
                }

                $delivery_region = null;
                if (!is_null($result["regiao_entrega"]) && !empty($result["regiao_entrega"])) {
                    $delivery_region = addslashes($result["regiao_entrega"]);
                }

                $region_collection = null;
                if (!is_null($result["regiao_coleta"]) && !empty($result["regiao_coleta"])) {
                    $region_collection = addslashes($result["regiao_coleta"]);
                }

                $collection_time = null;
                if (!is_null($result["tempo_coleta"]) && !empty($result["tempo_coleta"])) {
                    $collection_time = addslashes($result["tempo_coleta"]);
                }

                $cash_flow = null;
                if (!is_null($result["fluxo_fin"]) && !empty($result["fluxo_fin"])) {
                    $cash_flow = addslashes($result["fluxo_fin"]);
                }

                $credit_value = "0.00";
                if (!is_null($result["val_credito"]) && !empty($result["val_credito"])) {
                    $credit_value = addslashes(str_replace(",", ".", preg_replace("/[^0-9.,]/", "", $result["val_credito"])));
                }

                $minimum_shipping = "0.00";
                if (!is_null($result["ship_min"]) && !empty($result["ship_min"]) && ($result["ship_min"] == "Sim")) {
                    $minimum_shipping = addslashes(str_replace(",", ".", preg_replace("/[^0-9.,]/", "", $result["val_ship_min"])));
                }

                $minimum_quantity = 0;
                if (!is_null($result["qtd_min"]) && !empty($result["qtd_min"]) && ($result["qtd_min"] == "Sim")) {
                    $minimum_quantity = addslashes(preg_replace("/[^0-9]/", "", $result["val_qtd_min"]));
                }

                $payment_type = null;
                if (!is_null($result["tipo_pagamento"]) && !empty($result["tipo_pagamento"])) {
                    $payment_type = addslashes($result["tipo_pagamento"]);
                }

                $product_type = null;
                if (!is_null($result["tipo_produto"]) && !empty($result["tipo_produto"])) {
                    $product_type = addslashes($result["tipo_produto"]);
                }

                $tracking_website = null;
                if (!is_null($result["tracking_web_site"]) && !empty($result["tracking_web_site"])) {
                    $tracking_website = addslashes($result["tracking_web_site"]);
                }

                $cubage_factor = "0.00";
                if (!is_null($result["cubage_factor"]) && !empty($result["cubage_factor"])) {
                    $cubage_factor = addslashes(str_replace(",", ".", preg_replace("/[^0-9.,]/", "", $result["cubage_factor"])));
                }

                $ad_valorem = "0.00";
                if (!is_null($result["ad_valorem"]) && !empty($result["ad_valorem"])) {
                    $ad_valorem = addslashes(str_replace(",", ".", preg_replace("/[^0-9.,]/", "", $result["ad_valorem"])));
                }

                $gris = "0.00";
                if (!is_null($result["gris"]) && !empty($result["gris"])) {
                    $gris = addslashes(str_replace(",", ".", preg_replace("/[^0-9.,]/", "", $result["gris"])));
                }

                $toll = "0.00";
                if (!is_null($result["toll"]) && !empty($result["toll"])) {
                    $toll = addslashes(str_replace(",", ".", preg_replace("/[^0-9.,]/", "", $result["toll"])));
                }

                $shipping_revenue = "0.00";
                if (!is_null($result["shipping_revenue"]) && !empty($result["shipping_revenue"])) {
                    $shipping_revenue = addslashes(str_replace(",", ".", preg_replace("/[^0-9.,]/", "", $result["shipping_revenue"])));
                }

                $freight_seller = 1;
                if (!is_null($result["freight_seller"]) && !empty($result["freight_seller"])) {
                    $freight_seller = addslashes($result["freight_seller"]);
                }

                $type = 0;
                for ($i = 0; $i < count($table_type); $i++) {
                    if ($table_type[$i]["id_provider"] == $result["id"]) {
                        $type = addslashes($table_type[$i]["id_type"]);
                        if($type == 2) $type = 0;
                        break;
                    }
                }

                $created_at = $result["date_created"];
                $updated_at = $result["date_updated"];

                if ($first) {
                    $first = false;
                } else {
                    $insert .= ", ";
                }

                $insert .= "($id, '$active', '$name', '$corporate_name', '$state_registration', '$cnpj', '$phone', '$observation', '$delivery_region', '$region_collection', '$collection_time', '$cash_flow', '$credit_value', '$minimum_shipping', '$minimum_quantity', '$payment_type', '$product_type', '$tracking_website', '$cubage_factor', '$ad_valorem', '$gris', '$toll', '$shipping_revenue', '$freight_seller', '$type', '$created_at', '$updated_at')";
            }
            $insert .= ";";

            $dir = "assets/files/migration/";
            $exists = $this->checkCreatePath($dir);
            $dir = getcwd() . "/$dir";
            if (is_dir($dir)) {
                file_put_contents("$dir/shipping_companies_" . date("Ymd_His") . "_$iteration-$offset.sql", $insert);
            }

            // SHIPPING COMPANY ADDRESS
            $insert = "INSERT INTO shipping_company_address (shipping_company_id, address_place, address_number, address_complement, address_zipcode, address_neighborhood, address_city, address_state, created_at, updated_at) VALUES "; 
            $first = true;
            $iteration = 0;
            foreach ($results as $result) {
                $iteration += 1;

                $shipping_company_id = $result["id"];

                $address_place = null;
                if (!is_null($result["address"]) && !empty($result["address"])) {
                    $address_place = addslashes($result["address"]);
                }

                $address_number = null;
                if (!is_null($result["addr_num"]) && !empty($result["addr_num"])) {
                    $address_number = addslashes($result["addr_num"]);
                }

                $address_complement = null;
                if (!is_null($result["addr_compl"]) && !empty($result["addr_compl"])) {
                    $address_complement = addslashes($result["addr_compl"]);
                }

                $address_zipcode = null;
                if (!is_null($result["zipcode"]) && !empty($result["zipcode"])) {
                    $address_zipcode = addslashes(preg_replace("/[^0-9]/", "", $result["zipcode"]));
                }

                $address_neighborhood = null;
                if (!is_null($result["addr_neigh"]) && !empty($result["addr_neigh"])) {
                    $address_neighborhood = addslashes($result["addr_neigh"]);
                }

                $address_city = null;
                if (!is_null($result["addr_city"]) && !empty($result["addr_city"])) {
                    $address_city = addslashes($result["addr_city"]);
                }

                $address_state = null;
                if (!is_null($result["addr_uf"]) && !empty($result["addr_uf"])) {
                    $address_state = addslashes($result["addr_uf"]);
                }

                $created_at = $result["date_created"];
                $updated_at = $result["date_updated"];

                if ($first) {
                    $first = false;
                } else {
                    $insert .= ", ";
                }

                $insert .= "($shipping_company_id, '$address_place', '$address_number', '$address_complement', '$address_zipcode', '$address_neighborhood', '$address_city', '$address_state', '$created_at', '$updated_at')";
            }
            $insert .= ";";

            $dir = "assets/files/migration/";
            $exists = $this->checkCreatePath($dir);
            $dir = getcwd() . "/$dir";
            if (is_dir($dir)) {
                file_put_contents("$dir/shipping_company_address_" . date("Ymd_His") . "_$iteration-$offset.sql", $insert);
            }

            // SHIPPING COMPANY RESPONSIBLE
            $insert = "INSERT INTO shipping_company_responsible (shipping_company_id, responsible_name, responsible_cpf, responsible_email, bank, agency, account_type, account, responsible_operational_name, responsible_operational_cpf, responsible_operational_email, responsible_financial_name, responsible_financial_cpf, responsible_financial_email, created_at, updated_at) VALUES "; 
            $first = true;
            $iteration = 0;
            foreach ($results as $result) {
                $iteration += 1;

                $shipping_company_id = $result["id"];

                $responsible_name = null;
                if (!is_null($result["responsible_name"]) && !empty($result["responsible_name"])) {
                    $responsible_name = addslashes($result["responsible_name"]);
                }

                $responsible_cpf = null;
                if (!is_null($result["responsible_cpf"]) && !empty($result["responsible_cpf"])) {
                    $responsible_cpf = addslashes(preg_replace("/[^0-9]/", "", $result["responsible_cpf"]));
                }

                $responsible_email = null;
                if (!is_null($result["responsible_email"]) && !empty($result["responsible_email"])) {
                    $responsible_email = addslashes($result["responsible_email"]);
                }

                $bank = null;
                if (!is_null($result["bank"]) && !empty($result["bank"])) {
                    $bank = addslashes($result["bank"]);
                }

                $agency = null;
                if (!is_null($result["agency"]) && !empty($result["agency"])) {
                    $agency = addslashes($result["agency"]);
                }

                $account_type = null;
                if (!is_null($result["account_type"]) && !empty($result["account_type"])) {
                    $account_type = addslashes($result["account_type"]);
                }

                $account = null;
                if (!is_null($result["account"]) && !empty($result["account"])) {
                    $account = addslashes($result["account"]);
                }

                $responsible_operational_name = null;
                if (!is_null($result["responsible_oper_name"]) && !empty($result["responsible_oper_name"])) {
                    $responsible_operational_name = addslashes($result["responsible_oper_name"]);
                }

                $responsible_operational_cpf = null;
                if (!is_null($result["responsible_oper_cpf"]) && !empty($result["responsible_oper_cpf"])) {
                    $responsible_operational_cpf = addslashes(preg_replace("/[^0-9]/", "", $result["responsible_oper_cpf"]));
                }

                $responsible_operational_email = null;
                if (!is_null($result["responsible_oper_email"]) && !empty($result["responsible_oper_email"])) {
                    $responsible_operational_email = addslashes($result["responsible_oper_email"]);
                }

                $responsible_financial_name = null;
                if (!is_null($result["responsible_finan_name"]) && !empty($result["responsible_finan_name"])) {
                    $responsible_financial_name = addslashes($result["responsible_finan_name"]);
                }

                $responsible_financial_cpf = null;
                if (!is_null($result["responsible_finan_cpf"]) && !empty($result["responsible_finan_cpf"])) {
                    $responsible_financial_cpf = addslashes(preg_replace("/[^0-9]/", "", $result["responsible_finan_cpf"]));
                }

                $responsible_financial_email = null;
                if (!is_null($result["responsible_finan_email"]) && !empty($result["responsible_finan_email"])) {
                    $responsible_financial_email = addslashes($result["responsible_finan_email"]);
                }

                $disable = 0;

                $created_at = $result["date_created"];
                $updated_at = $result["date_updated"];

                if ($first) {
                    $first = false;
                } else {
                    $insert .= ", ";
                }

                $insert .= "($shipping_company_id, '$responsible_name', '$responsible_cpf', '$responsible_email', '$bank', '$agency', '$account_type', '$account', '$responsible_operational_name', '$responsible_operational_cpf', '$responsible_operational_email', '$responsible_financial_name', '$responsible_financial_cpf', '$responsible_financial_email', '$created_at', '$updated_at')";
            }
            $insert .= ";";

            $dir = "assets/files/migration/";
            $exists = $this->checkCreatePath($dir);
            $dir = getcwd() . "/$dir";
            if (is_dir($dir)) {
                file_put_contents("$dir/shipping_company_responsible_" . date("Ymd_His") . "_$offset-$iteration.sql", $insert);
            }
        }
    }
}
