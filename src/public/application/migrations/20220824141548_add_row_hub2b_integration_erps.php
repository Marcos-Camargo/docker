<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        $this->db->query("UPDATE `integration_erps` SET `image` = 'hub2b.jpg' WHERE (`name` = 'hub2b');");
        $this->db->query("INSERT INTO `settings` (`id`, `name`, `value`, `status`, `user_id`, `date_updated`) VALUES ('', 'hub2b_app_config', '{\"client_id\":\"UwSjZbV99eZN9sVXkvaIsow20AIciQ\",\"client_secret\":\"hKDpu93dvUTJMqO83IHk9nV9vSLtFJ\",\"auth_scope\":\"marketplace.name.mercadolivre marketplace.id.4\",\"marketplace_name\":\"mercadolivre\",\"marketplace_id\":\"4\",\"sales_channel_id\":\"4\"}', '1', '1', '2022-08-29 14:20:51');");
    }

    public function down()
    {
        $this->db->query("DELETE FROM settings WHERE `name` = 'hub2b_app_config';");
    }
};