<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    private $table_name = "getnet_gatilho_lancamento";

    public function up()
    {

        $fields = array(
            '`created_at` datetime NULL DEFAULT CURRENT_TIMESTAMP ',
        );
        $this->dbforge->add_column($this->table_name, $fields);
    }

    public function down()
    {
        $this->dbforge->drop_column($this->table_name, 'created_at');
    }
};