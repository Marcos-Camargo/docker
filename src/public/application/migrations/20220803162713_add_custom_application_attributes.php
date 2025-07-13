<?php defined('BASEPATH') or exit('No direct script access allowed');

return new
/**
 * Class
 * @property CI_DB_query_builder $db
 */
class extends CI_Migration {

    public function up()
    {
        if (!$this->dbforge->column_exists('category_id', 'custom_application_attributes')){
            $this->db->query('ALTER TABLE `custom_application_attributes` ADD COLUMN `category_id` INT(11) NOT NULL DEFAULT 0 AFTER `attribute_id`;');
        }

        if (!$this->dbforge->index_exists('account_category_code_module', 'custom_application_attributes')){
            $this->db->query('ALTER TABLE `custom_application_attributes` ADD INDEX `account_category_code_module` (`company_id` ASC, `store_id` ASC, `category_id` ASC, `code` ASC, `module` ASC)');
        }
        if (!$this->dbforge->index_exists('account_code_module', 'custom_application_attributes')){
            $this->db->query('ALTER TABLE `custom_application_attributes` ADD INDEX `account_code_module` (`company_id` ASC, `store_id` ASC, `code` ASC, `module` ASC);');
        }
    }

    public function down()
    {
        $this->db->query('ALTER TABLE `custom_application_attributes` DROP COLUMN `category_id`;');
        $this->db->query('ALTER TABLE `custom_application_attributes` DROP INDEX `account_code_module`;');
        $this->db->query('ALTER TABLE `custom_application_attributes` DROP INDEX `account_category_code_module`;');
    }
};