<?php defined('BASEPATH') or exit('No direct script access allowed');

return
    new
    /**
     * Class
     * @property CI_DB_query_builder $db
     */
    class extends CI_Migration {

        public function up()
        {
            if (!$this->dbforge->column_exists('variant_id_erp', 'prd_variants')) {
                $this->db->query('ALTER TABLE `prd_variants` CHANGE COLUMN `variant_id_erp` `variant_id_erp` VARCHAR(40) NULL DEFAULT NULL;');
            }
            if (!$this->dbforge->column_exists('id_anymarket_oi', 'api_integrations')) {
                $this->db->query('ALTER TABLE `api_integrations` ADD COLUMN `id_anymarket_oi` VARCHAR(256) NULL DEFAULT NULL AFTER `integration`;');
            }
            $this->db->query('CREATE TABLE IF NOT EXISTS `anymarket_temp_product` (`id` INT NOT NULL,`integration_id` VARCHAR(45) NOT NULL,`id_sku_product` VARCHAR(45) NOT NULL,`data` MEDIUMBLOB NOT NULL,`json_received` LONGBLOB NOT NULL,`date_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,`date_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,PRIMARY KEY (`id`));');
            $this->db->query('CREATE TABLE IF NOT EXISTS `categories_anymaket_from_to` (`id` INT NOT NULL AUTO_INCREMENT,`idCategoryAnymarket` VARCHAR(45) NOT NULL,`categories_id` VARCHAR(45) NOT NULL,`api_integration_id` VARCHAR(45) NOT NULL,PRIMARY KEY (`id`));');
            $this->db->query('CREATE TABLE IF NOT EXISTS `brand_anymaket_from_to` (`id` INT NOT NULL,`idBrandAnymarket` VARCHAR(45) NOT NULL,`brand_id` INT(11) NOT NULL,PRIMARY KEY (`id`));');
            if (!$this->dbforge->column_exists('api_integration_id', 'brand_anymaket_from_to')) {
                $this->db->query('ALTER TABLE `brand_anymaket_from_to` ADD COLUMN `api_integration_id` INT(11) NOT NULL AFTER `brand_id`;');
            }
            $this->db->query('CREATE TABLE IF NOT EXISTS `anymarket_temp_product` (`id` INT NOT NULL,`integration_id` VARCHAR(45) NOT NULL,`id_sku_product` VARCHAR(45) NOT NULL,`data` MEDIUMBLOB NOT NULL,`json_received` LONGBLOB NOT NULL,`date_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,`date_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,PRIMARY KEY (`id`));');

            if (!$this->dbforge->column_exists('idAccount', 'anymarket_temp_product')) {
                $this->db->query('ALTER TABLE `anymarket_temp_product` ADD COLUMN `idAccount` VARCHAR(45) NULL AFTER `variants`;');
            }
            $this->db->query('ALTER TABLE `anymarket_temp_product` CHANGE COLUMN `id` `id` INT(11) NOT NULL AUTO_INCREMENT;');

            if (!$this->dbforge->column_exists('variants', 'anymarket_temp_product')) {
                $this->db->query('ALTER TABLE `anymarket_temp_product` ADD COLUMN `variants` MEDIUMBLOB NULL AFTER `date_update`;');
            }
            if (!$this->dbforge->column_exists('need_update', 'anymarket_temp_product')) {
                $this->db->query('ALTER TABLE `anymarket_temp_product` ADD COLUMN `need_update` INT NOT NULL DEFAULT 0 AFTER `idAccount`;');
            }
            $this->db->query('CREATE TABLE IF NOT EXISTS `anymarket_order_to_update` (`id` int(11) NOT NULL AUTO_INCREMENT,`order_anymarket_id` varchar(45) NOT NULL,`order_id` varchar(45) NOT NULL,`old_status` varchar(45) NOT NULL,`new_status` varchar(45) NOT NULL,`date_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,`date_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,`is_new` int(11) NOT NULL DEFAULT \'1\',PRIMARY KEY (`id`) USING BTREE) ENGINE=InnoDB AUTO_INCREMENT=93 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;');

            $this->db->query('CREATE TABLE IF NOT EXISTS `anymarket_queue` (`id` int(11) NOT NULL AUTO_INCREMENT,`received_body` longblob NOT NULL,`integration_id` int(11) NOT NULL,`idSku` varchar(100) NOT NULL,`checked` int(11) NOT NULL DEFAULT \'0\',`date_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,`date_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,PRIMARY KEY (`id`)) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=latin1;');

            $this->db->query('CREATE TABLE IF NOT EXISTS `anymarket_log` (`id` int(11) NOT NULL AUTO_INCREMENT,`endpoint` varchar(100) NOT NULL,`body_received` longblob NOT NULL,`store_id` int(11) NOT NULL,`date_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,`date_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,PRIMARY KEY (`id`)) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=latin1;');

            if (!$this->dbforge->column_exists('skuInMarketplace', 'anymarket_temp_product')) {
                $this->db->query('ALTER TABLE anymarket_temp_product ADD COLUMN skuInMarketplace varchar(255) NULL;');
            }
            //if (!$this->dbforge->column_exists('idProduct', 'anymarket_temp_product')) {
            //    $this->db->query('ALTER TABLE anymarket_queue ADD COLUMN idProduct varchar(255) NULL;');
            //}
            if (!$this->dbforge->column_exists('skuInMarketplace', 'anymarket_temp_product')) {
                $this->db->query('ALTER TABLE anymarket_queue ADD COLUMN idSkuMarketplace varchar(100) NULL;');
            }
            if (!$this->dbforge->column_exists('anymarketId', 'anymarket_temp_product')) {
                $this->db->query('ALTER TABLE anymarket_temp_product ADD COLUMN anymarketId varchar(100) NOT NULL;');
            }
            $this->db->query('ALTER TABLE brand_anymaket_from_to CHANGE COLUMN id id INT(11) NOT NULL AUTO_INCREMENT;');
            if (!$this->dbforge->column_exists('company_id', 'anymarket_order_to_update')) {
                $this->db->query('ALTER TABLE `anymarket_order_to_update` ADD COLUMN `company_id` INT(11) NULL DEFAULT NULL AFTER `id`;');
            }
            if (!$this->dbforge->column_exists('company_id', 'anymarket_order_to_update')) {
                $this->db->query('ALTER TABLE `anymarket_order_to_update` ADD COLUMN `store_id` INT(11) NULL DEFAULT NULL AFTER `company_id`;');
            }
            $this->db->query('ALTER TABLE `anymarket_order_to_update` CHANGE COLUMN `is_new` `is_new` TINYINT(1) NOT NULL DEFAULT \'1\';');
            if (!$this->dbforge->index_exists('comany_id', 'anymarket_order_to_update')) {
                $this->db->query('ALTER TABLE `anymarket_order_to_update` ADD INDEX `comany_id` (`company_id` ASC);');
            }
            if (!$this->dbforge->index_exists('store_id', 'anymarket_order_to_update')) {
                $this->db->query('ALTER TABLE `anymarket_order_to_update` ADD INDEX `store_id` (`company_id` ASC);');
            }
        }

        public function down()
        {

        }
    };