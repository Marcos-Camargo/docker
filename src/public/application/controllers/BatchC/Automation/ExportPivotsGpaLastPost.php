<?php

require 'system/libraries/Vendor/autoload.php';

class ExportPivotsGpaLastPost extends BatchBackground_Controller
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

    private function getProducts($product_id)
    {
        $sql = 'SELECT category_id,products_package
            FROM products
            WHERE id = ?';
        $query = $this->db->query($sql, array($product_id));
        return $query->row_array();
    }

    private function processExport()
    {
        $proceed = true;
        $offset = 0;

        while ($proceed) {
            $query = $this->db->query(
                'SELECT *
                FROM gpa_last_post
                GROUP BY int_to, store_id, prd_id, variant
                ORDER BY id ASC
                LIMIT ?, 50000',
            array($offset));

            $offset += 50000;
            $results = $query->result_array();

            if (empty($results)) {
                $proceed = false;
                break;
            }

            // MsShippingIntegrator configures
            $insert = "SET NAMES utf8; INSERT INTO pivots (marketplace, company_id, store_id, seller_id, cnpj, zip_code, ean, sku_seller, sku_mkt, sku_local, product_id, product_variant, price, list_price, category_id, quantity, quantity_total, height, width, length, gross_weight, quantity_per_package, crossdocking, created_at, updated_at) VALUES "; 
            $first = true;
            $iteration = 0;
            foreach ($results as $result) {
                $iteration += 1;
               
                $marketplace          = addslashes($result["int_to"]);
                $company_id           = $result["company_id"];
                $store_id             = $result["store_id"];
                $cnpj                 = addslashes(preg_replace("/[^0-9]/", "", $result["CNPJ"]));
                $zip_code             = addslashes(preg_replace("/[^0-9]/", "", $result["zipcode"]));
                $ean                  = addslashes($result["EAN"]);
                $sku_seller           = addslashes($result["sku"]);
                $sku_mkt              = addslashes($result["skumkt"]);
                $sku_local            = addslashes($result["skulocal"]);
                $product_id           = $result["prd_id"];
                $quantity             = $result["qty"];
                $quantity_total       = $result["qty_total"];
                $height               = addslashes(str_replace(",", ".", preg_replace("/[^0-9.,]/", "", $result["height"])));
                $width                = addslashes(str_replace(",", ".", preg_replace("/[^0-9.,]/", "", $result["width"])));
                $length               = addslashes(str_replace(",", ".", preg_replace("/[^0-9.,]/", "", $result["length"])));
                $gross_weight         = addslashes(str_replace(",", ".", preg_replace("/[^0-9.,]/", "", $result["gross_weight"])));
                $crossdocking         = $result["crossdocking"];

                if (is_null($crossdocking)){
                    $crossdocking = 0;
                }

                $products = '';
                $products = $this->getProducts($product_id);
                if (!$products) {
                    echo "Falhou a busca pelo produto no Product Id: ".$product_id;
                    continue;
                }
                $category_id          = intVal($this->onlyNumbers($products["category_id"]));
                $quantity_per_package = $products["products_package"];

                $seller_id            = null;
                
                $product_variant      = 'null';
                if ((!is_null($result["variant"]) && !empty($result["variant"])) || $result["variant"] == 0) {
                    $product_variant  = addslashes($result["variant"]);
                }
                if($product_variant == ''){
                    $product_variant = 'null';
                }

                $price           = 'null';
                if (!is_null($result["price"]) && !empty($result["price"])) {
                    $price            = addslashes(str_replace(",", ".", preg_replace("/[^0-9.,]/", "", $result["price"])));
                } else {
                    echo "Produto com preço zerado, será descontiderado na exportação ".$product_id;
                    continue;
                }
                
                $list_price           = 'null';
                if (!is_null($result["list_price"]) && !empty($result["list_price"])) {
                    $list_price       = addslashes(str_replace(",", ".", preg_replace("/[^0-9.,]/", "", $result["list_price"])));
                }
                
                if (!is_null($result["date_last_sent"]) && !empty($result["date_last_sent"])) {
                    $created_at       = $result["date_last_sent"];
                    $updated_at       = $result["date_last_sent"];
                } else{
                    $created_at       = date("Y-m-d H:i:s");
                    $updated_at       = date("Y-m-d H:i:s");
                }

                if ($first) {
                    $first = false;
                } else {
                    $insert .= ", ";
                }

                $insert .= "('$marketplace', $company_id, $store_id, NULL, '$cnpj', '$zip_code', '$ean', '$sku_seller', '$sku_mkt', '$sku_local', $product_id, $product_variant, $price, $list_price, $category_id, $quantity, $quantity_total, $height, $width, $length, $gross_weight, $quantity_per_package, $crossdocking, '$created_at', '$updated_at')";
            }
            $insert .= ";";

            $dir = "assets/files/migration/";
            $exists = $this->checkCreatePath($dir);
            $dir = getcwd() . "/$dir";
            if (is_dir($dir)) {
                file_put_contents("$dir/ms_shipping_pivots_gpa_last_post" . date("Ymd_His") . "_$offset-$iteration.sql", $insert);
            }
        }
    }
}
