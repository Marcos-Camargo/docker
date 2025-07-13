<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property CI_DB_driver $db
 */

return new class extends CI_Migration
{

    //campaign_v2
	public function up() {

        $fieldNew = array(
            'is_incomplete' => array(
                'type'       => 'TINYINT',
                'constraint' => ('1'),
                'null'       => false,
                'default'    => false
            )
        );

        if (!$this->dbforge->column_exists('is_incomplete', 'orders')){
            $this->dbforge->add_column('orders', $fieldNew);
        }
	}

	public function down()	{
		### Drop table orders.is_incomplete ##
		$this->dbforge->drop_column("orders", 'is_incomplete');
	}
};