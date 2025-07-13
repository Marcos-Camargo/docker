<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        $this->db->query(
            "CREATE TABLE `custom_attribute_map_types` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `company_id` int(11) NOT NULL,
              `store_id` int(11) NOT NULL,
              `custom_attribute_id` int(11) NOT NULL,
              `enabled` tinyint(1) NOT NULL DEFAULT '1',
              `visible` tinyint(1) NOT NULL DEFAULT '1',
              `value` varchar(255) NOT NULL DEFAULT '',
              `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
              `updated_at` datetime DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `store_attributes` (`company_id`,`store_id`,`custom_attribute_id`),
              KEY `store_attributes_enabled` (`company_id`,`store_id`,`custom_attribute_id`,`enabled`,`visible`),
              KEY `store_attribute_values` (`company_id`,`store_id`,`custom_attribute_id`,`enabled`,`value`),
              KEY `custom_app_attr_idx` (`custom_attribute_id`),
              KEY `store_value_attr` (`company_id`,`store_id`,`enabled`,`value`),
              CONSTRAINT `custom_app_attr` FOREIGN KEY (`custom_attribute_id`) REFERENCES `custom_application_attributes` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
            ) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8;"
        );
    }

    public function down()
    {
        ### Drop table custom_application_attribute_map_values ##
        $this->dbforge->drop_table("custom_attribute_map_types", TRUE);

    }

};