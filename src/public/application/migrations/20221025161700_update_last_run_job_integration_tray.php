
<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up()
    {
        $this->db->where('integration', 'tray')->update('job_integration', array('last_run' => null));
	}

	public function down() {}
};
