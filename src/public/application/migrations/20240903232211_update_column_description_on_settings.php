<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up() {
        $this->db->query("
		ALTER TABLE settings MODIFY COLUMN description text CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;
		");

    }

	public function down()	{
	}
};