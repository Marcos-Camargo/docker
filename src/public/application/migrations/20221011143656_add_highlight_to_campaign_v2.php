<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        $fieldUpdate = array(
            'highlight' => array(
                'type' => 'TINYINT',
                'constraint' => ('1'),
                'null' => FALSE,
                'default' => '0',
                'after' => 'id'
            )
        );
        if (!$this->dbforge->column_exists('highlight', 'campaign_v2')) {
            $this->dbforge->add_column('campaign_v2', $fieldUpdate);
        }
    }

    public function down()
    {
        $this->dbforge->drop_column('campaign_v2', 'highlight');
    }
};