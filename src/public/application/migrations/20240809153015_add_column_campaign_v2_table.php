<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $fieldUpdate = array(
            'percentual_from_commision' => array(
                'type' => 'DECIMAL',
                'constraint' => ('6,2'),
                'null' => FALSE,
            )
        );

        if (!$this->dbforge->column_exists('percentual_from_commision', 'campaign_v2_products')) {
            $this->dbforge->add_column('campaign_v2_products', $fieldUpdate);
        }

        $fieldUpdate = array(
            'commision_hierarchy' => array(
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true
            )
        );

        if (!$this->dbforge->column_exists('commision_hierarchy', 'campaign_v2_products')) {
            $this->dbforge->add_column('campaign_v2_products', $fieldUpdate);
        }

        $fieldUpdate = array(
            'percentual_commision' => array(
                'type' => 'DECIMAL',
                'constraint' => ('6,2'),
                'null' => FALSE,
            )
        );

        if (!$this->dbforge->column_exists('percentual_commision', 'campaign_v2_products')) {
            $this->dbforge->add_column('campaign_v2_products', $fieldUpdate);
        }
    }

	public function down()	{
        $this->dbforge->drop_column('campaign_v2_products', 'percentual_from_commision');
        $this->dbforge->drop_column('campaign_v2_products', 'percentual_commision');
        $this->dbforge->drop_column('campaign_v2_products', 'commision_hierarchy');
	}
};