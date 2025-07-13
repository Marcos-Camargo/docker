<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $fieldUpdate = array(
            'approved' => array(
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 1
            )
        );

        if (!$this->dbforge->column_exists('approved', 'campaign_v2')) {
            $this->dbforge->add_column('campaign_v2', $fieldUpdate);
        }
    }

	public function down()	{
        $this->dbforge->drop_column('campaign_v2', 'approved');
	}
};