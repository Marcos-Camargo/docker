
<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up()
    {
        if (!$this->dbforge->index_exists('mkt_type', 'integrations')) {
            $this->db->query('ALTER TABLE `integrations` ADD COLUMN `mkt_type` varchar(50) AFTER `auto_approve`');
        }
    }
    public function down()
    {
        $this->dbforge->drop_column("integrations", 'mkt_type');
    }
};
