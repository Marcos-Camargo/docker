<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration
{

  public function up()
  {

      if (!$this->db->table_exists('campaign_v2_orders_items')) {

          $this->dbforge->add_field(array(
              'id' => array(
                  'type' => 'INT',
                  'constraint' => ('11'),
                  'null' => FALSE,
                  'auto_increment' => TRUE
              ),
              'item_id' => array(
                  'type' => 'INT',
                  'unsigned' => FALSE,
                  'constraint' => 11,
                  'null' => FALSE,
              ),
              'campaign_v2_id' => array(
                  'type' => 'INT',
                  'unsigned' => TRUE,
                  'constraint' => 11,
                  'null' => FALSE,
              ),
              'channel_discount' => array(
                  'type' => 'DECIMAL',
                  'constraint' => ('10,2'),
                  'DEFAULT' => null
              ),
              'seller_discount' => array(
                  'type' => 'DECIMAL',
                  'constraint' => ('10,2'),
                  'DEFAULT' => null
              ),
              'total_discount' => array(
                  'type' => 'DECIMAL',
                  'constraint' => ('10,2'),
                  'DEFAULT' => null
              ),
              'total_reduced' => array(
                  'type' => 'DECIMAL',
                  'constraint' => ('10,2'),
                  'DEFAULT' => null
              ),
              'total_rebate' => array(
                  'type' => 'DECIMAL',
                  'constraint' => ('10,2'),
                  'DEFAULT' => null
              ),
              'total_reduced_marketplace' => array(
                  'type' => 'DECIMAL',
                  'constraint' => ('10,2'),
                  'DEFAULT' => null
              ),
              'total_rebate_marketplace' => array(
                  'type' => 'DECIMAL',
                  'constraint' => ('10,2'),
                  'DEFAULT' => null
              ),
              '`created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ',
              '`updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
          ));
          $this->dbforge->add_key("id", true);
          $this->dbforge->create_table("campaign_v2_orders_items", TRUE);
          $this->db->query('ALTER TABLE `campaign_v2_orders_items` CHANGE `item_id` `item_id` INT NOT NULL;');
          $this->db->query('ALTER TABLE `campaign_v2_orders_items` ADD CONSTRAINT `campaign_v2_orders_items_item_id` FOREIGN KEY (`item_id`) REFERENCES `orders_item` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
          $this->db->query('ALTER TABLE `campaign_v2_orders_items` ADD CONSTRAINT `campaign_v2_orders_items_campaign_v2_id` FOREIGN KEY (`campaign_v2_id`) REFERENCES `campaign_v2` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT');
          $this->db->query('ALTER TABLE `campaign_v2_orders_items` ENGINE = InnoDB');

      }

      if ($this->db->field_exists('campaign_id', 'campaign_v2_orders_items')) {
          $this->db->query('ALTER TABLE `campaign_v2_orders_items` CHANGE `campaign_id` `campaign_v2_id` INT NOT NULL;');
      }
      $this->db->query('ALTER TABLE `campaign_v2_orders_items` CHANGE `campaign_v2_id` `campaign_v2_id` INT UNSIGNED NOT NULL;');

  }

  public function down()
  {
    $this->dbforge->drop_table("campaign_v2_orders_items", TRUE);
  }
};
