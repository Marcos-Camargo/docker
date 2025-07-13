<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up() {

        $columnsDrop = [
            'order_id',
            'simulations_anticipations_store_id',
            'amount',
            'anticipation_fee',
            'fee',
            'created_at',
            'updated_at',
        ];

        foreach ($columnsDrop as $column){
            if ($this->db->field_exists($column, 'integration_erps')){
                $this->db->query("ALTER TABLE `integration_erps` DROP COLUMN `$column`;");
            }
        }


        $this->db->query("INSERT INTO `integration_erps` (
            `name`,
            `description`,
            `type`,
            `hash`,
            `active`,
            `visible`,
            `support_link`,
            `configuration_form`,
            `configuration`,
            `image`,
            `provider_id`,
            `user_created`,
            `user_updated`,
            `date_created`,
            `date_updated`
        )
        VALUES
            (
                'magalu',
                'Magalu',
                1,
                'be75927233d645107df190552b17e0c1d658f8b4',
                0,
                1,
                '[]',
                NULL,
                '{}',
                'magalu.png',
                NULL,
                NULL,
                NULL,
                '2024-03-19 16:59:49',
                '2024-03-21 15:50:45'
            );
        ");
    }

    public function down()	{
        $this->db->query("DELETE FROM integration_erps WHERE `name` = 'magalu';");
    }
};