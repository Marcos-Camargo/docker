<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        $fieldNew = array(
            'vtex_campaign_update' => array(
                'type' => 'TINYINT',
                'constraint' => ('1'),
                'unsigned' => TRUE,
                'null' => FALSE,
                'default' => 0,
                'after' => 'schedule_sent_status'
            )
        );

        if (!$this->dbforge->column_exists('vtex_campaign_update', 'campaign_v2')){
            $this->dbforge->add_column('campaign_v2', $fieldNew);
        }

	}

	public function down()	{
        $this->dbforge->drop_column("campaign_v2", 'vtex_campaign_update');
	}

};