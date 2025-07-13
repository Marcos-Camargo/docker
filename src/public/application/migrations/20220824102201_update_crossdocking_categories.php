<?php defined('BASEPATH') or exit('No direct script access allowed');
return new class extends CI_Migration
{
    public function up()
    {

        if(!$this->db->field_exists('force_update', 'categories')){
        	$this->db->query('ALTER TABLE `categories` ADD COLUMN `force_update` INT(1) NOT NULL DEFAULT 0 AFTER `blocked_cross_docking`');
		}


        $this->load->model('model_products');
        $this->load->model('model_category');
        $this->load->model('model_settings');

        $seller =  $this->model_settings->getSettingDatabyName('sellercenter');
        if ($seller['value'] == 'conectala') {
            $list = $this->model_category->getActiveCategroy(); //seleciono na model
            $i = 0;
            $soma = "";
            foreach ($list as $v) {
                $id = $v['id'];
                $i++;
                $txtnome = str_replace( array(' ', 'à','á','â','ã','ä', 'ç', 'è','é','ê','ë', 'ì','í','î','ï', 'ñ', 'ò','ó','ô','õ','ö', 'ù','ú','û','ü', 'ý','ÿ', 'À','Á','Â','Ã','Ä', 'Ç', 'È','É','Ê','Ë', 'Ì','Í','Î','Ï', 'Ñ', 'Ò','Ó','Ô','Õ','Ö', 'Ù','Ú','Û','Ü', 'Ý'), array('_', 'a','a','a','a','a', 'c', 'e','e','e','e', 'i','i','i','i', 'n', 'o','o','o','o','o', 'u','u','u','u', 'y','y', 'A','A','A','A','A', 'C', 'E','E','E','E', 'I','I','I','I', 'N', 'O','O','O','O','O', 'U','U','U','U', 'Y'), $v['name']); 
                $categoryName = strtolower($txtnome);
                if ((preg_match("/moda/", $categoryName)) || (preg_match_all("/moveis/", $categoryName)) || (preg_match("/fashion/", $categoryName)) || (preg_match("/arte/", $categoryName)) || (preg_match("/pcs/", $categoryName))) {
                    $data = array(
                        'days_cross_docking' => '15',
                        'blocked_cross_docking' => '1',
                        'force_update' => '1',
                    );
                    $update = $this->model_category->update($data, $id);
                } else {
                    $data = array(
                        'days_cross_docking' => '2',
                        'blocked_cross_docking' => '1',
                        'force_update' => '1',
                    );
                    $update = $this->model_category->update($data, $id);
                }
            }
            echo "\n";
            echo ' Total Afetados ' . $soma .= $i;
            echo "\n";
        }
    }

    public function down()
    {
        $this->dbforge->drop_column("categories", 'force_update');
        ### rollback
    }
};