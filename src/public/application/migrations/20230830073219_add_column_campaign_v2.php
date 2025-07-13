<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property CI_DB_driver $db
 */

return new class extends CI_Migration
{

    //campaign_v2
	public function up() {

        $fieldNew = array(
            'ds_vtex_campaign_creation' => array(
                'type' => 'TEXT',
                'null' => true,
                'after' => 'vtex_campaign_update'
            )
        );

        if (!$this->dbforge->column_exists('ds_vtex_campaign_creation', 'campaign_v2')){
            $this->dbforge->add_column('campaign_v2', $fieldNew);
        }
	}

	public function down()	{
		### Drop table integration_erps ##
		$this->dbforge->drop_column("campaign_v2", 'ds_vtex_campaign_creation');

	}
};