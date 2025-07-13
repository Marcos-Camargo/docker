<?php

require 'system/libraries/Vendor/autoload.php';

class DB_ExportPivots extends BatchBackground_Controller
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
        if (!$this->gravaInicioJob('Automation/'.$this->router->fetch_class(), __FUNCTION__)) {
            $this->log_data('batch', $log_name, 'Já tem um job rodando ou que foi cancelado', "E");
            return ;
        }

        $this->log_data('batch', $log_name, 'start '.trim($id." ".$params), "I");

        $this->processExport("Conectala2020#");
        
        /* encerra o job */
        $this->log_data('batch', $log_name, 'finish', "I");
        $this->gravaFimJob();
    }

    private function getProducts($product_id)
    {
        $sql = 'SELECT sku,category_id,products_package
            FROM products
            WHERE id = ?';
        $query = $this->db->query($sql, array($product_id));
        return $query->row_array();
    }

    private function processExport($params)
    {
        // parte nova para conectar no banco do MS
        $this->load->model("model_settings");
        $settingSellerCenter = $this->model_settings->getSettingDatabyName('sellercenter');
		if ($settingSellerCenter) {
            $sellercenter = $settingSellerCenter['value'];
        } else {
			echo "não achei o sellercenter\n";
			return;
		}
        
        // $hostdb   = '10.150.17.106';
        // $hostdb   = '10.150.16.113';
        // $hostdb   = '10.150.23.128';
        if (in_array(ENVIRONMENT, ['production', 'production_x', 'production_oci'])) {
            $hostdb = '10.150.23.188';
        } else {
            $hostdb = '10.151.100.221';
        }
        $database = 'ms_shipping_'.$sellercenter;

        $db_migra = [
            'dsn'   => '',
            'hostname' => $hostdb,
            'username' => 'admin',
            'password' => $params,
            'database' => $database ,
            'dbdriver' => 'mysqli',
            'dbprefix' => '',
            'pconnect' => false,
            'db_debug'  => true,
            'cache_on' => FALSE,
            'cachedir' => '',
            'char_set' => 'utf8',
            'dbcollat' => 'utf8_general_ci',
            'swap_pre' => '',
            'encrypt'  => FALSE,
            'compress' => FALSE,
            'stricton' => FALSE,
            'failover' => array(),
            'save_queries' => TRUE
    
        ];
        $db_ms = $this->load->database($db_migra,TRUE);

        if ($this->conta('pivots', $db_ms) > 0) {
            echo "Tabelas pivots já contém registro em Shipping. Não pode ser migrada novamente. Caso precisa migrar, deve limpar os dados.\n";
            return false;
        }

        $tableExists = array_map(function($table){
            return $table['TABLE_NAME'];
        }, $this->db->query(
            "SELECT table_name 
                FROM information_schema.tables 
                WHERE table_name LIKE '%_ult_envio%' 
                   OR table_name LIKE '%_last_post%'"
        )->result_array());

        if (in_array('occ_last_post', $tableExists)) {
            $this->vsLastPost($db_ms, 'occ_last_post');
        }
        if (in_array('vs_last_post', $tableExists)) {
            $this->vsLastPost($db_ms, 'vs_last_post');
        }
        if (in_array('vtex_ult_envio', $tableExists)) {
            $this->vsLastPost($db_ms, 'vtex_ult_envio');
        }
        if (in_array('sellercenter_last_post', $tableExists)) {
            $this->vsLastPost($db_ms, 'sellercenter_last_post');
        }
        if (in_array('integration_last_post', $tableExists)) {
            $this->vsLastPost($db_ms, 'integration_last_post');
        }

        $this->conta('pivots', $db_ms);
    }

    function conta($table, $dbvar) {
        if ($dbvar->database == 'conectala') {
            echo "[Monolito] ";
        } else {
            echo "[MS] ";
        }

        $ret = $dbvar->query('select count(*) as cnt from '.$table);
        $cnt = $ret->row_array();        
        echo "tabela ".$table." com ".$cnt['cnt']." registros\n";
        return $cnt['cnt'];
    }

    private function vsLastPost($db_ms, $table)
    {
        $this->conta($table, $this->db);

        $offset = 0;
        echo "$table\n";
        while (true) {
            $results = $this->db
                ->group_by('int_to, store_id, prd_id, variant')
                ->order_by('id', 'ASC')
                ->limit(50000, $offset)
                ->get($table)
                ->result_array();

            echo "Processando iniciando em $offset lido ".count($results)."\n";
            $offset += 50000;

            if (empty($results)) {
                break;
            }

            // MsShippingIntegrator configures
            $insert = [];
            foreach ($results as $result) {
                $marketplace        = addslashes($result["int_to"]);
                $company_id         = $result["company_id"];
                $store_id           = $result["store_id"];
                $cnpj               = addslashes(onlyNumbers($result["CNPJ"] ?? $result["cnpj"]));
                $zip_code           = addslashes(onlyNumbers($result["zipcode"]));
                $ean                = addslashes($result["EAN"]);
                $sku_seller         = addslashes($result["sku"]);
                $sku_mkt            = addslashes($result["skumkt"]);
                $sku_local          = null;
                $product_id         = $result["prd_id"];
                $quantity           = $result["qty"] ?? $result['qty_atual'];
                $quantity_total     = $result["qty_total"] ?? $result['qty_atual'];
                $height             = addslashes(str_replace(",", ".", preg_replace("/[^0-9.,]/", "", $result["height"] ?? $result['altura'])));
                $width              = addslashes(str_replace(",", ".", preg_replace("/[^0-9.,]/", "", $result["width"] ?? $result['largura'])));
                $length             = addslashes(str_replace(",", ".", preg_replace("/[^0-9.,]/", "", $result["length"] ?? $result['profundidade'])));
                $gross_weight       = addslashes(str_replace(",", ".", preg_replace("/[^0-9.,]/", "", $result["gross_weight"] ?? $result['peso_bruto'])));
                $crossdocking       = $result["crossdocking"] ?? 0;
                $seller_id          = null;
                $product_variant    = null;
                $sku_product_seller = null;
                $created_at         = date("Y-m-d H:i:s");
                $updated_at         = date("Y-m-d H:i:s");
                $list_price         = null;

                $products = $this->getProducts($product_id);
                if (!$products) {
                    echo "Falhou a busca pelo produto no Product Id: $product_id\n";
                    continue;
                }

                $category_id            = intVal($this->onlyNumbers($products["category_id"]));
                $quantity_per_package   = $products["products_package"];

                if ($result["variant"] !== null && $result["variant"] !== '') {
                    $product_variant    = addslashes($result["variant"]);
                    $sku_product_seller = $products['sku'];
                }
                if ($product_variant == '') {
                    $product_variant = null;
                }

                if (!empty($result["price"])) {
                    $price = addslashes(str_replace(",", ".", preg_replace("/[^0-9.,]/", "", $result["price"])));
                } else {
                    echo "Produto com preço zerado, será desconsiderado na exportação $product_id\n";
                    continue;
                }

                if (!empty($result["list_price"])) {
                    $list_price = addslashes(str_replace(",", ".", preg_replace("/[^0-9.,]/", "", $result["list_price"])));
                }

                if (!empty($result["date_last_sent"])) {
                    $created_at = $result["date_last_sent"];
                    $updated_at = $result["date_last_sent"];
                }

                if (!empty($result["seller_id"])) {
                    $seller_id = addslashes($result["seller_id"]);
                }

                if (!empty($result["skulocal"])) {
                    $sku_local = addslashes($result["skulocal"]);
                }

                $insert[] = [
                    'marketplace'           => $marketplace,
                    'company_id'            => $company_id,
                    'store_id'              => $store_id,
                    'seller_id'             => $seller_id,
                    'cnpj'                  => $cnpj,
                    'zip_code'              => $zip_code,
                    'ean'                   => $ean,
                    'sku_seller'            => $sku_seller,
                    'sku_mkt'               => $sku_mkt,
                    'sku_local'             => $sku_local,
                    'product_id'            => $product_id,
                    'product_variant'       => $product_variant,
                    'price'                 => $price,
                    'list_price'            => $list_price,
                    'category_id'           => $category_id,
                    'quantity'              => $quantity,
                    'quantity_total'        => $quantity_total,
                    'height'                => $height,
                    'width'                 => $width,
                    'length'                => $length,
                    'gross_weight'          => $gross_weight,
                    'quantity_per_package'  => $quantity_per_package,
                    'crossdocking'          => $crossdocking,
                    'sku_product_seller'    => $sku_product_seller,
                    'created_at'            => $created_at,
                    'updated_at'            => $updated_at
                ];
            }

            if (!empty($insert)) {
                $db_ms->insert_batch('pivots', $insert);
            }
        }
        echo "-------------------------------------------\n";
    }
}
