<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    private $table_name = "repasse";

	public function up() {

        $fields = array(
            '`paid_status_responsible` varchar(255) NULL',
        );
        $this->dbforge->add_column($this->table_name, $fields);
	 }

	public function down()	{

        $this->dbforge->drop_column($this->table_name, 'paid_status_responsible');

	}
};