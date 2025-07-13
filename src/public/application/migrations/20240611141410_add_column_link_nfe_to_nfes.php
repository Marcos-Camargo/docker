<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration
{
    private $table_name = "nfes";
    public function up()
    {
        $fields = array(
            '`link_nfe` varchar(255) NULL',
        );
        $this->dbforge->add_column($this->table_name, $fields);
    }

    public function down()
    {
        $this->dbforge->drop_column($this->table_name, 'link_nfe');
    }
};